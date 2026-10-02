<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/services/CurriculumService.php';
require_once dirname(__DIR__) . '/repositories/VocabularyRepository.php';

final class CurriculumController
{
    public function __construct(
        private readonly CurriculumService $service,
        private readonly AuthMiddleware $auth
    ) {
    }

    public function listModules(): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat melihat modul.', 403);
        }
        ResponseHelper::jsonResponse($this->service->listModulesForUser($session['user']['id']));
    }

    public function showModule(string $id): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat melihat modul.', 403);
        }
        ResponseHelper::jsonResponse(
            $this->service->moduleWithLessons($session['user']['id'], (int) $id)
        );
    }

    public function vocabulary(string $lessonId): void
    {
    $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
    if ($session['user']['role'] !== 'learner') {
        throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat melihat kosakata.', 403);
    }

    $vocabRepo = VocabularyRepository::fromConfig();
    $lessonIdInt = (int) $lessonId;

    if (!$vocabRepo->lessonBelongsToUserCourse($lessonIdInt, $session['user']['id'])) {
        throw new ApiException('LESSON_NOT_FOUND', 'Lesson tidak ditemukan.', 404);
    }

    ResponseHelper::jsonResponse($vocabRepo->vocabularyForLesson($lessonIdInt, 1));
    }
}