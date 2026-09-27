<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/QuizRepository.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

final class QuizController
{
    public function __construct(
        private readonly QuizRepository $quiz,
        private readonly CurriculumRepository $curriculum,
        private readonly AuthMiddleware $auth
    ) {
    }

    private function getAuthenticatedUser(): array
    {
        $auth = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        return $auth['user'];
    }

    public function getQuiz(int $moduleId): void
    {
        $user = $this->getAuthenticatedUser();
        
        $module = $this->curriculum->getModuleById($moduleId, $user['course_id'] ?? 0, $user['level_id'] ?? 0, $user['id']);
        if (!$module || $module['status'] !== 'published') {
            throw new ApiException('NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }
        if ($module['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Modul terkunci. Selesaikan prasyarat terlebih dahulu.', 403);
        }
        
        $quizData = $this->quiz->getQuizForModule($moduleId);
        if (!$quizData) {
            throw new ApiException('NOT_FOUND', 'Kuis tidak ditemukan untuk modul ini.', 404);
        }
        
        ResponseHelper::jsonResponse(['quiz' => $quizData]);
    }

    public function submitQuiz(int $quizId): void
    {
        $user = $this->getAuthenticatedUser();
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['answers']) || !is_array($input['answers'])) {
            throw new ApiException('BAD_REQUEST', 'Format jawaban tidak valid.', 400);
        }
        
        $duration = isset($input['duration_seconds']) ? (int) $input['duration_seconds'] : 0;
        if ($duration < 0) {
            throw new ApiException('BAD_REQUEST', 'Durasi tidak valid.', 400);
        }
        
        $quiz = $this->quiz->getQuizWithAnswers($quizId);
        if (!$quiz) {
            throw new ApiException('NOT_FOUND', 'Kuis tidak ditemukan.', 404);
        }
        
        // Validate access
        $module = $this->curriculum->getModuleById($quiz['module_id'], $user['course_id'] ?? 0, $user['level_id'] ?? 0, $user['id']);
        if (!$module || $module['status'] !== 'published' || $module['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Anda tidak memiliki akses ke kuis ini.', 403);
        }

        // Evaluate
        $totalWeight = 0;
        $earnedScore = 0;
        
        foreach ($quiz['questions'] as $qId => $qData) {
            $totalWeight += $qData['score_weight'];
            
            // Check if user answered this question
            // $input['answers'] format expected: { "question_id": selected_option_id } or { "question_id": [selected_option_ids] }
            if (isset($input['answers'][$qId])) {
                $userAns = $input['answers'][$qId];
                if (!is_array($userAns)) {
                    $userAns = [(int)$userAns];
                } else {
                    $userAns = array_map('intval', $userAns);
                }
                
                // Sort to compare arrays easily
                sort($userAns);
                sort($qData['correct_option_ids']);
                
                if ($userAns === $qData['correct_option_ids']) {
                    $earnedScore += $qData['score_weight'];
                }
            }
        }
        
        $finalScore = $totalWeight > 0 ? (int) round(($earnedScore / $totalWeight) * 100) : 0;
        $isPassed = $finalScore >= $quiz['passing_score'];
        
        // Save in transaction
        $this->quiz->saveQuizResultTransaction($user['id'], $quizId, $quiz['module_id'], $finalScore, $isPassed, $duration);
        
        ResponseHelper::jsonResponse([
            'score' => $finalScore,
            'passing_score' => $quiz['passing_score'],
            'is_passed' => $isPassed
        ]);
    }
}
