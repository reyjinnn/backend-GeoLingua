<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/QuizRepository.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

require_once dirname(__DIR__) . '/repositories/UserRepository.php';

final class QuizController
{
    public function __construct(
        private readonly QuizRepository $quiz,
        private readonly CurriculumRepository $curriculum,
        private readonly UserRepository $users,
        private readonly AuthMiddleware $auth
    ) {
    }

    private function getAuthenticatedUser(): array
    {
        $auth = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        return $auth['user'];
    }

    private function getActiveCourse(int $userId): array
    {
        $course = $this->users->activeCourseForUser($userId);
        if (!$course) {
            throw new ApiException('FORBIDDEN', 'Silakan selesaikan onboarding terlebih dahulu.', 403);
        }
        return $course;
    }

    public function getQuiz(int $moduleId): void
    {
        $user = $this->getAuthenticatedUser();
        $course = $this->getActiveCourse($user['id']);
        
        $module = $this->curriculum->getModuleById($moduleId, $user['id'], (int) $course['id'], (int) $course['current_level_id']);
        if (!$module || ($module['module_status'] ?? $module['status'] ?? '') !== 'published') {
            throw new ApiException('NOT_FOUND', 'Modul tidak ditemukan.', 404);
        }
        if (($module['progress_status'] ?? '') === 'locked' || ($module['status'] ?? '') === 'locked') {
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
        $course = $this->getActiveCourse($user['id']);
        
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
        $module = $this->curriculum->getModuleById($quiz['module_id'], $user['id'], (int) $course['id'], (int) $course['current_level_id']);
        if (!$module || ($module['module_status'] ?? $module['status'] ?? '') !== 'published' || ($module['progress_status'] ?? '') === 'locked') {
            throw new ApiException('FORBIDDEN', 'Anda tidak memiliki akses ke kuis ini.', 403);
        }

        // Normalize answers format (accepts array of { question_id, selected_option_id } or map)
        $submittedAnswers = [];
        $seenQuestions = [];

        if (array_is_list($input['answers'])) {
            foreach ($input['answers'] as $item) {
                if (!is_array($item) || !isset($item['question_id']) || !isset($item['selected_option_id'])) {
                    throw new ApiException('BAD_REQUEST', 'Format butir jawaban tidak valid.', 400);
                }
                $qId = (int) $item['question_id'];
                if (isset($seenQuestions[$qId])) {
                    throw new ApiException('BAD_REQUEST', "Pertanyaan #$qId dijawab lebih dari sekali.", 400);
                }
                $seenQuestions[$qId] = true;
                $submittedAnswers[$qId] = is_array($item['selected_option_id'])
                    ? array_map('intval', $item['selected_option_id'])
                    : [(int) $item['selected_option_id']];
            }
        } else {
            foreach ($input['answers'] as $qId => $optVal) {
                $qId = (int) $qId;
                $submittedAnswers[$qId] = is_array($optVal)
                    ? array_map('intval', $optVal)
                    : [(int) $optVal];
            }
        }

        // Validate that each answered question and option belongs to this quiz
        foreach ($submittedAnswers as $qId => $selectedOptIds) {
            if (!isset($quiz['questions'][$qId])) {
                throw new ApiException('BAD_REQUEST', "Soal #$qId bukan bagian dari kuis ini.", 400);
            }
            $qData = $quiz['questions'][$qId];
            foreach ($selectedOptIds as $optId) {
                if (!in_array($optId, $qData['valid_option_ids'], true)) {
                    throw new ApiException('BAD_REQUEST', "Opsi jawaban #$optId tidak valid untuk soal #$qId.", 400);
                }
            }
        }

        // Evaluate
        $totalWeight = 0;
        $earnedScore = 0;
        
        foreach ($quiz['questions'] as $qId => $qData) {
            $totalWeight += $qData['score_weight'];
            
            if (isset($submittedAnswers[$qId])) {
                $userAns = $submittedAnswers[$qId];
                sort($userAns);
                $correct = $qData['correct_option_ids'];
                sort($correct);
                
                if ($userAns === $correct) {
                    $earnedScore += $qData['score_weight'];
                }
            }
        }
        
        $finalScore = $totalWeight > 0 ? (int) round(($earnedScore / $totalWeight) * 100) : 0;
        $isPassed = $finalScore >= $quiz['passing_score'];
        
        // Save in transaction and unlock next module if passed
        $unlockResult = $this->quiz->saveQuizResultTransaction($user['id'], $quizId, $quiz['module_id'], $finalScore, $isPassed, $duration);
        
        ResponseHelper::jsonResponse([
            'score' => $finalScore,
            'passing_score' => $quiz['passing_score'],
            'is_passed' => $isPassed,
            'next_module_unlocked' => $unlockResult['next_module_unlocked'] ?? false,
            'unlocked_module_id' => $unlockResult['unlocked_module_id'] ?? null
        ]);
    }
}
