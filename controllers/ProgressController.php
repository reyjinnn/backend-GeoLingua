<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/ProgressRepository.php';

final class ProgressController
{
    public function __construct(
        private readonly ProgressRepository $progress,
        private readonly AuthMiddleware $auth
    ) {
    }

    public function summary(): void
    {
        $session = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        if ($session['user']['role'] !== 'learner') {
            throw new ApiException('FORBIDDEN', 'Hanya pelajar yang dapat melihat progres belajar.', 403);
        }
        ResponseHelper::jsonResponse($this->progress->summaryForUser($session['user']['id']));
    }
}
