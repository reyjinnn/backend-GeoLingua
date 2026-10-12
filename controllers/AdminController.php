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

        // ============================================================
    // LESSONS
    // ============================================================

    public function listLessons(string $moduleId): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $moduleIdInt = (int) $moduleId;
        if (!$this->repo->moduleExists($moduleIdInt)) {
            throw new ApiException('MODULE_NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }
        ResponseHelper::jsonResponse($this->repo->lessonsForModule($moduleIdInt));
    }

    public function createLesson(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $body = RequestHelper::jsonBody();

        $details = [];
        foreach (['module_id', 'lesson_name', 'lesson_objective'] as $field) {
            if (!isset($body[$field])) {
                $details[$field] = "Field '{$field}' wajib diisi.";
            }
        }
        if (isset($body['module_id']) && !$this->repo->moduleExists((int) $body['module_id'])) {
            $details['module_id'] = 'Modul tidak ditemukan.';
        }
        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }

        $id = $this->repo->createLesson($body);
        ResponseHelper::jsonResponse(['id' => $id, 'message' => 'Lesson berhasil dibuat.'], 201);
    }

    public function updateLesson(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $lessonId = (int) $id;
        if (!$this->repo->lessonExists($lessonId)) {
            throw new ApiException('LESSON_NOT_FOUND', 'Lesson tidak ditemukan.', 404);
        }
        $body = RequestHelper::jsonBody();
        $this->repo->updateLesson($lessonId, $body);
        ResponseHelper::jsonResponse(['id' => $lessonId, 'message' => 'Lesson berhasil diperbarui.']);
    }

    public function deleteLesson(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $lessonId = (int) $id;
        if (!$this->repo->lessonExists($lessonId)) {
            throw new ApiException('LESSON_NOT_FOUND', 'Lesson tidak ditemukan.', 404);
        }
        $this->repo->deleteLesson($lessonId);
        ResponseHelper::jsonResponse(['id' => $lessonId, 'message' => 'Lesson berhasil dihapus.']);
    }

    // ============================================================
    // VOCABULARIES
    // ============================================================

    public function createVocabulary(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $body = RequestHelper::jsonBody();

        $details = [];
        $required = [
            'lesson_id', 'target_language_id', 'base_language_id', 'word',
            'pronunciation', 'part_of_speech', 'translation', 'definition',
            'example_sentence', 'example_translation',
        ];
        foreach ($required as $field) {
            if (!isset($body[$field])) {
                $details[$field] = "Field '{$field}' wajib diisi.";
            }
        }
        if (isset($body['lesson_id']) && !$this->repo->lessonExists((int) $body['lesson_id'])) {
            $details['lesson_id'] = 'Lesson tidak ditemukan.';
        }
        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }

        $id = $this->repo->createVocabulary($body);
        ResponseHelper::jsonResponse(['id' => $id, 'message' => 'Kosakata berhasil dibuat.'], 201);
    }

    public function deleteVocabulary(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $vocabId = (int) $id;
        if (!$this->repo->vocabularyExists($vocabId)) {
            throw new ApiException('VOCABULARY_NOT_FOUND', 'Kosakata tidak ditemukan.', 404);
        }
        $this->repo->deleteVocabulary($vocabId);
        ResponseHelper::jsonResponse(['id' => $vocabId, 'message' => 'Kosakata berhasil dihapus.']);
    }

    // ============================================================
    // EXERCISES
    // ============================================================

    public function createExercise(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $body = RequestHelper::jsonBody();

        $details = [];
        $required = ['lesson_id', 'vocabulary_id', 'question_type', 'prompt', 'correct_answer'];
        foreach ($required as $field) {
            if (!isset($body[$field])) {
                $details[$field] = "Field '{$field}' wajib diisi.";
            }
        }
        if (isset($body['lesson_id']) && !$this->repo->lessonExists((int) $body['lesson_id'])) {
            $details['lesson_id'] = 'Lesson tidak ditemukan.';
        }
        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }

        $id = $this->repo->createExercise($body);
        ResponseHelper::jsonResponse(['id' => $id, 'message' => 'Soal drill berhasil dibuat.'], 201);
    }

    public function deleteExercise(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $exerciseId = (int) $id;
        if (!$this->repo->exerciseExists($exerciseId)) {
            throw new ApiException('EXERCISE_NOT_FOUND', 'Soal drill tidak ditemukan.', 404);
        }
        $this->repo->deleteExercise($exerciseId);
        ResponseHelper::jsonResponse(['id' => $exerciseId, 'message' => 'Soal drill berhasil dihapus.']);
    }

    // ============================================================
    // WRITING EXERCISES
    // ============================================================

    public function createWritingExercise(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $body = RequestHelper::jsonBody();

        $details = [];
        $required = ['lesson_id', 'instruction', 'writing_prompt', 'required_vocabulary', 'benchmark_answer', 'evaluation_guide'];
        foreach ($required as $field) {
            if (!isset($body[$field])) {
                $details[$field] = "Field '{$field}' wajib diisi.";
            }
        }
        if (isset($body['lesson_id']) && !$this->repo->lessonExists((int) $body['lesson_id'])) {
            $details['lesson_id'] = 'Lesson tidak ditemukan.';
        }
        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }

        $id = $this->repo->createWritingExercise($body);
        ResponseHelper::jsonResponse(['id' => $id, 'message' => 'Latihan menulis berhasil dibuat.'], 201);
    }

    public function deleteWritingExercise(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $writingId = (int) $id;
        if (!$this->repo->writingExerciseExists($writingId)) {
            throw new ApiException('WRITING_NOT_FOUND', 'Latihan menulis tidak ditemukan.', 404);
        }
        $this->repo->deleteWritingExercise($writingId);
        ResponseHelper::jsonResponse(['id' => $writingId, 'message' => 'Latihan menulis berhasil dihapus.']);
    }

    // ============================================================
    // QUIZZES
    // ============================================================

    public function createQuiz(): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $body = RequestHelper::jsonBody();

        $details = [];
        $required = ['module_id', 'title'];
        foreach ($required as $field) {
            if (!isset($body[$field])) {
                $details[$field] = "Field '{$field}' wajib diisi.";
            }
        }
        if (isset($body['module_id']) && !$this->repo->moduleExists((int) $body['module_id'])) {
            $details['module_id'] = 'Modul tidak ditemukan.';
        }
        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }

        $id = $this->repo->createQuiz($body);
        ResponseHelper::jsonResponse(['id' => $id, 'message' => 'Kuis berhasil dibuat.'], 201);
    }

    public function deleteQuiz(string $id): void
    {
        $this->admin->authorize(AdminMiddleware::authorizationHeader());
        $quizId = (int) $id;
        if (!$this->repo->quizExists($quizId)) {
            throw new ApiException('QUIZ_NOT_FOUND', 'Kuis tidak ditemukan.', 404);
        }
        $this->repo->deleteQuiz($quizId);
        ResponseHelper::jsonResponse(['id' => $quizId, 'message' => 'Kuis berhasil dihapus.']);
    }
}