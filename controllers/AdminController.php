<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/RequestHelper.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/middleware/AdminMiddleware.php';
require_once dirname(__DIR__) . '/repositories/AdminRepository.php';

final class AdminController
{
    public function __construct(
        private readonly AdminRepository $repo,
        private readonly AdminMiddleware $admin
    ) {
    }

    /**
     * GET /api/admin/dashboard
     */
    public function dashboard(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        ResponseHelper::jsonResponse($this->repo->dashboardMetrics());
    }

    /**
     * GET /api/admin/modules
     */
    public function listModules(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        ResponseHelper::jsonResponse($this->repo->allModules());
    }

    /**
     * GET /api/admin/modules/:id
     */
    public function showModule(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $moduleId = (int) $id;
        $module = $this->repo->findModuleById($moduleId);
        if ($module === null) {
            throw new ApiException('MODULE_NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }
        ResponseHelper::jsonResponse($module);
    }

    /**
     * POST /api/admin/modules
     */
    public function createModule(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $body = RequestHelper::jsonBody();

        $this->validateModulePayload($body, false);

        if ($this->repo->moduleCodeExists($body['module_code'])) {
            throw new ApiException('VALIDATION_FAILED', 'Module code sudah dipakai.', 422, [
                'module_code' => 'Kode modul sudah terdaftar.',
            ]);
        }

        $id = $this->repo->createModule($body);
        ResponseHelper::jsonResponse([
            'id' => $id,
            'message' => 'Modul berhasil dibuat dalam status draft.',
        ], 201);
    }

    /**
     * PUT /api/admin/modules/:id
     */
    public function updateModule(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $moduleId = (int) $id;

        if (!$this->repo->moduleExists($moduleId)) {
            throw new ApiException('MODULE_NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }

        $body = RequestHelper::jsonBody();
        $this->repo->updateModule($moduleId, $body);

        ResponseHelper::jsonResponse([
            'id' => $moduleId,
            'message' => 'Modul berhasil diperbarui.',
        ]);
    }

    /**
     * PUT /api/admin/modules/:id/publish
     */
    public function publishModule(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $moduleId = (int) $id;

        if (!$this->repo->moduleExists($moduleId)) {
            throw new ApiException('MODULE_NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }

        $this->repo->setModuleStatus($moduleId, 'published');

        ResponseHelper::jsonResponse([
            'id' => $moduleId,
            'status' => 'published',
            'message' => 'Modul berhasil dipublikasikan.',
        ]);
    }

    /**
     * PUT /api/admin/modules/:id/draft
     */
    public function unpublishModule(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $moduleId = (int) $id;

        if (!$this->repo->moduleExists($moduleId)) {
            throw new ApiException('MODULE_NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }

        $this->repo->setModuleStatus($moduleId, 'draft');

        ResponseHelper::jsonResponse([
            'id' => $moduleId,
            'status' => 'draft',
            'message' => 'Modul dikembalikan ke status draft.',
        ]);
    }

    /**
     * DELETE /api/admin/modules/:id
     */
    public function deleteModule(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $moduleId = (int) $id;

        if (!$this->repo->moduleExists($moduleId)) {
            throw new ApiException('MODULE_NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }

        $this->repo->deleteModule($moduleId);

        ResponseHelper::jsonResponse([
            'id' => $moduleId,
            'message' => 'Modul berhasil dihapus.',
        ]);
    }

    // ============================================================
    // VALIDATION
    // ============================================================

    private function validateModulePayload(array $body, bool $isUpdate): void
    {
        $details = [];

        if (!$isUpdate) {
            foreach (['course_id', 'level_id', 'module_code', 'title', 'topic', 'description', 'learning_objectives', 'order_index'] as $field) {
                if (!isset($body[$field])) {
                    $details[$field] = "Field '{$field}' wajib diisi.";
                }
            }

            if (isset($body['course_id']) && !$this->repo->courseExists((int) $body['course_id'])) {
                $details['course_id'] = 'Course tidak ditemukan.';
            }
            if (isset($body['level_id']) && !$this->repo->levelExists((int) $body['level_id'])) {
                $details['level_id'] = 'Level tidak ditemukan.';
            }
            if (isset($body['module_code']) && (!is_string($body['module_code']) || trim($body['module_code']) === '')) {
                $details['module_code'] = 'Module code tidak valid.';
            }
            if (isset($body['title']) && (!is_string($body['title']) || mb_strlen(trim($body['title'])) < 3)) {
                $details['title'] = 'Judul minimal 3 karakter.';
            }
            if (isset($body['order_index']) && (!is_int($body['order_index']) || $body['order_index'] < 1)) {
                $details['order_index'] = 'Order index harus bilangan positif.';
            }
        }

        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }
    }
}