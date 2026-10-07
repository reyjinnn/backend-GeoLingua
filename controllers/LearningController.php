<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/RequestHelper.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/services/DrillEngineService.php';
require_once dirname(__DIR__) . '/repositories/ExerciseRepository.php';
require_once dirname(__DIR__) . '/services/WritingValidationService.php';
require_once dirname(__DIR__) . '/repositories/WritingRepository.php';
require_once dirname(__DIR__) . '/services/QuizService.php';
require_once dirname(__DIR__) . '/repositories/QuizRepository.php';
require_once dirname(__DIR__) . '/services/ModuleProgressService.php';

final class LearningController
{
    public function __construct(
        private readonly DrillEngineService $drillService,
        private readonly WritingValidationService $writingService,
        private readonly QuizService $quizService,
        private readonly AuthMiddleware $auth
    ) {
    }

    /**
     * GET /api/lessons/:id/drills
     */
    public function drills(string $lessonId): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat mengakses drill.', 403);
        }

        $lessonIdInt = (int) $lessonId;
        ResponseHelper::jsonResponse($this->drillService->listDrillsForLesson($lessonIdInt));
    }

    /**
     * POST /api/drills/:id/answer
     */
    public function answerDrill(string $drillId): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat menjawab drill.', 403);
        }

        $body = RequestHelper::jsonBody();
        $answer = $body['answer'] ?? null;
        if (!is_string($answer) || trim($answer) === '') {
            throw new ApiException('VALIDATION_FAILED', 'Jawaban wajib diisi.', 422);
        }

        $drillIdInt = (int) $drillId;

        $repo = ExerciseRepository::fromConfig();
        if (!$repo->exerciseBelongsToUserCourse($drillIdInt, $session['user']['id'])) {
            throw new ApiException('DRILL_NOT_FOUND', 'Soal drill tidak ditemukan.', 404);
        }

        ResponseHelper::jsonResponse($this->drillService->evaluateAnswer($drillIdInt, $answer));
    }

        /**
     * GET /api/lessons/:id/writing
     */
    public function writing(string $lessonId): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat mengakses latihan menulis.', 403);
        }

        ResponseHelper::jsonResponse($this->writingService->promptForLesson((int) $lessonId));
    }

    /**
     * POST /api/writing/:id/submit
     */
    public function submitWriting(string $writingId): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat mengirim tulisan.', 403);
        }

        $body = RequestHelper::jsonBody();
        $text = $body['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new ApiException('VALIDATION_FAILED', 'Teks wajib diisi.', 422);
        }

        $writingIdInt = (int) $writingId;
        $repo = WritingRepository::fromConfig();

        if (!$repo->exerciseBelongsToUserCourse($writingIdInt, $session['user']['id'])) {
            throw new ApiException('WRITING_NOT_FOUND', 'Latihan menulis tidak ditemukan.', 404);
        }

        ResponseHelper::jsonResponse(
            $this->writingService->validateSubmission($session['user']['id'], $writingIdInt, $text)
        );
    }


        /**
     * GET /api/modules/:id/quiz
     */
    public function quiz(string $moduleId): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat mengakses kuis.', 403);
        }

        ResponseHelper::jsonResponse($this->quizService->quizForModule((int) $moduleId));
    }

    /**
     * POST /api/quizzes/:id/submit
     */
    public function submitQuiz(string $quizId): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat mengirim kuis.', 403);
        }

        $body = RequestHelper::jsonBody();
        $answers = $body['answers'] ?? null;
        $duration = $body['duration_seconds'] ?? 0;

        if (!is_array($answers) || $answers === []) {
            throw new ApiException('VALIDATION_FAILED', 'Jawaban kuis wajib diisi.', 422);
        }
        if (!is_int($duration) || $duration < 0) {
            $duration = 0;
        }

        $quizIdInt = (int) $quizId;
        $repo = QuizRepository::fromConfig();

        if (!$repo->quizBelongsToUserCourse($quizIdInt, $session['user']['id'])) {
            throw new ApiException('QUIZ_NOT_FOUND', 'Kuis tidak ditemukan.', 404);
        }

        $result = $this->quizService->submitQuiz($session['user']['id'], $quizIdInt, $answers, $duration);

        $unlockedModuleId = null;
        if ($result['is_passed']) {
            $moduleId = $repo->moduleIdForQuiz($quizIdInt);
            if ($moduleId !== null) {
                $progressService = ModuleProgressService::fromConfig();
                $unlockedModuleId = $progressService->completeModuleAndUnlockNext(
                    $session['user']['id'],
                    $moduleId,
                    $result['score']
                );
            }
        }

        ResponseHelper::jsonResponse([
            'score' => $result['score'],
            'passing_score' => $result['passing_score'],
            'is_passed' => $result['is_passed'],
            'next_module_unlocked' => $unlockedModuleId !== null,
            'unlocked_module_id' => $unlockedModuleId,
        ]);
    }
}