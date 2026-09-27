<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/AdminRepository.php';

final class AdminController
{
    public function __construct(
        private readonly AdminRepository $admin,
        private readonly AuthMiddleware $auth
    ) {
    }

    private function ensureAdmin(): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'admin') {
            throw new ApiException('FORBIDDEN', 'Akses ditolak. Anda bukan admin.', 403);
        }
    }

    public function setupAdmin(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['email']) || empty($input['password'])) {
            throw new ApiException('BAD_REQUEST', 'Email dan password diperlukan.', 400);
        }

        $name = $input['name'] ?? 'Admin';
        $hash = password_hash($input['password'], PASSWORD_DEFAULT);

        $success = $this->admin->initAdmin($input['email'], $hash, $name);
        if (!$success) {
            throw new ApiException('CONFLICT', 'Akun admin sudah ada di sistem.', 409);
        }

        ResponseHelper::jsonResponse(['message' => 'Akun admin berhasil dibuat.'], 201);
    }

    public function createModule(): void
    {
        $this->ensureAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['course_id']) || empty($input['level_id']) || empty($input['module_code']) || empty($input['title'])) {
            throw new ApiException('BAD_REQUEST', 'Field course_id, level_id, module_code, title wajib diisi.', 400);
        }

        $moduleId = $this->admin->createModule($input);
        ResponseHelper::jsonResponse(['id' => $moduleId, 'message' => 'Modul berhasil dibuat.'], 201);
    }

    public function createLesson(): void
    {
        $this->ensureAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['module_id']) || empty($input['lesson_name'])) {
            throw new ApiException('BAD_REQUEST', 'Field module_id, lesson_name wajib diisi.', 400);
        }

        $lessonId = $this->admin->createLesson($input);
        ResponseHelper::jsonResponse(['id' => $lessonId, 'message' => 'Lesson berhasil dibuat.'], 201);
    }

    public function publishModule(int $moduleId): void
    {
        $this->ensureAdmin();

        $details = $this->admin->getModuleDetailsForPublishing($moduleId);
        $missing = [];

        if (!$details['has_lesson']) {
            $missing[] = 'Modul tidak memiliki lesson.';
        }
        if (!$details['has_vocabulary']) {
            $missing[] = 'Lesson tidak memiliki vocabulary.';
        }
        if (!$details['has_drill']) {
            $missing[] = 'Lesson tidak memiliki soal drill.';
        }
        if (!$details['has_writing']) {
            $missing[] = 'Lesson tidak memiliki latihan menulis.';
        }
        if (!$details['has_quiz']) {
            $missing[] = 'Modul tidak memiliki kuis.';
        } elseif ($details['quiz_question_count'] < 10) {
            $missing[] = 'Kuis harus memiliki minimal 10 soal (saat ini ' . $details['quiz_question_count'] . ').';
        }

        if (count($missing) > 0) {
            ResponseHelper::jsonResponse([
                'success' => false,
                'message' => 'Modul belum lengkap dan tidak dapat dipublish.',
                'missing' => $missing
            ], 400); // Bad request because rules are not met
            exit; // Usually ResponseHelper terminates, but just in case
        }

        $this->admin->updateModuleStatus($moduleId, 'published');
        ResponseHelper::jsonResponse(['message' => 'Modul berhasil dipublish.']);
    }
}
