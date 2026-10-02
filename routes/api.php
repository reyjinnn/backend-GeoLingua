<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/OnboardingController.php';
require_once dirname(__DIR__) . '/controllers/ProgressController.php';
require_once dirname(__DIR__) . '/controllers/CurriculumController.php';
require_once dirname(__DIR__) . '/services/CurriculumService.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

function dispatchApi(string $method, string $path): void
{
    if ($method === 'GET' && $path === '/api/health') {
        ResponseHelper::jsonResponse(['status' => 'ok']);
        return;
    }

    $authRoutes = [
        'POST /api/auth/register' => 'register',
        'POST /api/auth/login' => 'login',
        'GET /api/auth/me' => 'me',
        'POST /api/auth/logout' => 'logout',
    ];
    $action = $authRoutes[$method . ' ' . $path] ?? null;
    if ($action !== null) {
        $users = UserRepository::fromConfig();
        $controller = new AuthController(new AuthService($users), new AuthMiddleware($users), $users);
        $controller->$action();
        return;
    }

    $onboardingRoutes = [
        'GET /api/languages' => 'languages',
        'GET /api/levels' => 'levels',
        'GET /api/courses' => 'courses',
        'POST /api/user/preferences' => 'savePreferences',
    ];
    $action = $onboardingRoutes[$method . ' ' . $path] ?? null;
    if ($action !== null) {
        $users = UserRepository::fromConfig();
        $controller = new OnboardingController(OnboardingRepository::fromConfig(), new AuthMiddleware($users));
        $controller->$action();
        return;
    }

    if ($method === 'GET' && $path === '/api/progress/summary') {
        $users = UserRepository::fromConfig();
        $controller = new ProgressController(ProgressRepository::fromConfig(), new AuthMiddleware($users));
        $controller->summary();
        return;
    }

    // CURRICULUM ROUTES

    // GET /api/modules
    if ($method === 'GET' && $path === '/api/modules') {
        $users = UserRepository::fromConfig();
        $controller = new CurriculumController(
            new CurriculumService(CurriculumRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->listModules();
        return;
    }

    // GET /api/modules/:id
    if ($method === 'GET' && preg_match('#^/api/modules/(\d+)$#', $path, $matches) === 1) {
        $users = UserRepository::fromConfig();
        $controller = new CurriculumController(
            new CurriculumService(CurriculumRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->showModule($matches[1]);
        return;
    }


    // GET /api/lessons/:id/vocabulary
    if ($method === 'GET' && preg_match('#^/api/lessons/(\d+)/vocabulary$#', $path, $matches) === 1) {
    $users = UserRepository::fromConfig();
    $controller = new CurriculumController(
        new CurriculumService(CurriculumRepository::fromConfig()),
        new AuthMiddleware($users)
    );
    $controller->vocabulary($matches[1]);
    return;
    }
    
    ResponseHelper::jsonError('NOT_FOUND', 'Endpoint tidak ditemukan.', 404);
}