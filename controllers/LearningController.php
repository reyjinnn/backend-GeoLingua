<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/RequestHelper.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/services/DrillEngineService.php';
require_once dirname(__DIR__) . '/repositories/ExerciseRepository.php';

final class LearningController
{
    public function __construct(
        private readonly DrillEngineService $drillService,
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
}