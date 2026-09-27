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
        $setupKey = $_SERVER['HTTP_X_ADMIN_SETUP_KEY'] ?? getenv('ADMIN_SETUP_KEY') ?: ($_ENV['ADMIN_SETUP_KEY'] ?? null);
        $expectedKey = getenv('ADMIN_SETUP_KEY') ?: ($_ENV['ADMIN_SETUP_KEY'] ?? null);
        if (!$expectedKey || !is_string($setupKey) || !hash_equals($expectedKey, $setupKey)) {
            throw new ApiException('FORBIDDEN', 'Inisialisasi admin melalui HTTP memerlukan setup key yang valid.', 403);
        }

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
            $missing[] = 'Modul belum memiliki pelajaran (lesson).';
        } else {
            if ($details['lessons_without_vocabulary'] > 0) {
                $missing[] = "Terdapat {$details['lessons_without_vocabulary']} pelajaran tanpa kosakata.";
            }
            if ($details['lessons_without_drill'] > 0) {
                $missing[] = "Terdapat {$details['lessons_without_drill']} pelajaran tanpa latihan drill.";
            }
            if ($details['lessons_without_writing'] > 0) {
                $missing[] = "Terdapat {$details['lessons_without_writing']} pelajaran tanpa latihan menulis.";
            }
        }
        if (!$details['has_quiz']) {
            $missing[] = 'Modul tidak memiliki kuis.';
        } elseif ($details['quiz_question_count'] < 10) {
            $missing[] = 'Kuis harus memiliki minimal 10 soal (saat ini ' . $details['quiz_question_count'] . ').';
        } elseif ($details['invalid_quiz_questions'] > 0) {
            $missing[] = "Terdapat {$details['invalid_quiz_questions']} soal kuis yang belum memiliki minimal 2 opsi dan 1 kunci jawaban benar.";
        }

        if (count($missing) > 0) {
            ResponseHelper::jsonError('VALIDATION_ERROR', 'Modul belum lengkap dan tidak dapat dipublish.', 400, $missing);
            return;
        }

        $this->admin->updateModuleStatus($moduleId, 'published');
        ResponseHelper::jsonResponse(['message' => 'Modul berhasil dipublish.']);
    }

    public function listModules(): void
    {
        $this->ensureAdmin();
        $modules = $this->admin->listModules();
        ResponseHelper::jsonResponse(['modules' => $modules]);
    }

    public function getLessons(int $moduleId): void
    {
        $this->ensureAdmin();
        $lessons = $this->admin->getLessonsForModule($moduleId);
        ResponseHelper::jsonResponse(['lessons' => $lessons]);
    }

    public function createVocabulary(): void
    {
        $this->ensureAdmin();
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['word']) || empty($input['translation'])) {
            throw new ApiException('BAD_REQUEST', 'Field word dan translation wajib diisi.', 400);
        }

        $vocabId = $this->admin->createVocabulary($input);
        ResponseHelper::jsonResponse(['id' => $vocabId, 'message' => 'Kosakata berhasil ditambahkan.'], 201);
    }

    public function createExercise(): void
    {
        $this->ensureAdmin();
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['lesson_id']) || empty($input['vocabulary_id']) || empty($input['question_type']) || empty($input['prompt']) || !isset($input['correct_answer'])) {
            throw new ApiException('BAD_REQUEST', 'Field lesson_id, vocabulary_id, question_type, prompt, dan correct_answer wajib diisi.', 400);
        }

        $exerciseId = $this->admin->createExercise($input);
        ResponseHelper::jsonResponse(['id' => $exerciseId, 'message' => 'Latihan drill berhasil ditambahkan.'], 201);
    }
}
