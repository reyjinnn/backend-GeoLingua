<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/DrillRepository.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

final class DrillController
{
    public function __construct(
        private readonly DrillRepository $drills,
        private readonly CurriculumRepository $curriculum,
        private readonly AuthMiddleware $auth
    ) {
    }

    private function getAuthenticatedUser(): array
    {
        $auth = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        return $auth['user'];
    }

    public function getDrills(int $lessonId): void
    {
        $user = $this->getAuthenticatedUser();
        
        // Ensure access using Curriculum logic
        $lesson = $this->curriculum->getLessonById($lessonId, $user['id']);
        if (!$lesson || $lesson['module_status'] !== 'published') {
            throw new ApiException('NOT_FOUND', 'Lesson tidak ditemukan.', 404);
        }
        if ($lesson['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Lesson terkunci. Selesaikan prasyarat terlebih dahulu.', 403);
        }
        
        $exercises = $this->drills->getExercisesForLesson($lessonId);
        ResponseHelper::jsonResponse(['exercises' => $exercises]);
    }

    public function submitAnswer(int $exerciseId): void
    {
        $user = $this->getAuthenticatedUser();
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['answer'])) {
            throw new ApiException('BAD_REQUEST', 'Jawaban diperlukan.', 400);
        }
        
        $exercise = $this->drills->getExerciseWithAnswer($exerciseId);
        if (!$exercise) {
            throw new ApiException('NOT_FOUND', 'Soal tidak ditemukan.', 404);
        }
        
        // Ensure access to the lesson
        $lesson = $this->curriculum->getLessonById($exercise['lesson_id'], $user['id']);
        if (!$lesson || $lesson['module_status'] !== 'published' || $lesson['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Anda tidak memiliki akses ke soal ini.', 403);
        }
        
        $userAnswer = (string) $input['answer'];
        $isCorrect = $this->evaluateAnswer($exercise['question_type'], $exercise['correct_answer'], $userAnswer);
        
        // Save attempt
        $this->drills->saveAttempt($user['id'], $exerciseId, $isCorrect, $userAnswer);
        
        ResponseHelper::jsonResponse([
            'is_correct' => $isCorrect,
            'correct_answer' => $exercise['correct_answer'],
            'explanation' => $exercise['explanation']
        ]);
    }
    
    private function evaluateAnswer(string $type, string $correct, string $user): bool
    {
        if ($type === 'typing' || $type === 'fill_blank') {
            // Case-insensitive and trim
            return strcasecmp(trim($correct), trim($user)) === 0;
        }
        
        // For MCQ, just strict check or case-insensitive if needed. Let's do case-insensitive trim as well.
        return strcasecmp(trim($correct), trim($user)) === 0;
    }
}
