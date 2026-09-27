<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/RequestHelper.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/services/OnboardingService.php';

final class OnboardingController
{
    public function __construct(
        private readonly OnboardingRepository $repository,
        private readonly AuthMiddleware $auth
    ) {
    }

    public function languages(): void
    {
        ResponseHelper::jsonResponse($this->repository->languages());
    }

    public function levels(): void
    {
        ResponseHelper::jsonResponse($this->repository->levels());
    }

    public function courses(): void
    {
        ResponseHelper::jsonResponse($this->repository->courses());
    }

    public function savePreferences(): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat mengatur preferensi belajar.', 403);
        }
        $body = RequestHelper::jsonBody();
        $service = new OnboardingService($this->repository);
        ResponseHelper::jsonResponse($service->save($session['user']['id'], $body));
    }
}
