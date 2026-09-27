<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/OnboardingController.php';
require_once dirname(__DIR__) . '/controllers/ProgressController.php';

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

    require_once dirname(__DIR__) . '/controllers/CurriculumController.php';
    if ($method === 'GET') {
        if ($path === '/api/modules') {
            $users = UserRepository::fromConfig();
            $controller = new CurriculumController(CurriculumRepository::fromConfig(), $users, new AuthMiddleware($users));
            $controller->listModules();
            return;
        }
        if (preg_match('#^/api/modules/(\d+)$#', $path, $matches)) {
            $users = UserRepository::fromConfig();
            $controller = new CurriculumController(CurriculumRepository::fromConfig(), $users, new AuthMiddleware($users));
            $controller->getModule((int)$matches[1]);
            return;
        }
        if (preg_match('#^/api/lessons/(\d+)$#', $path, $matches)) {
            $users = UserRepository::fromConfig();
            $controller = new CurriculumController(CurriculumRepository::fromConfig(), $users, new AuthMiddleware($users));
            $controller->getLesson((int)$matches[1]);
            return;
        }
        if (preg_match('#^/api/lessons/(\d+)/vocabulary$#', $path, $matches)) {
            $users = UserRepository::fromConfig();
            $controller = new CurriculumController(CurriculumRepository::fromConfig(), $users, new AuthMiddleware($users));
            $controller->getLessonVocabulary((int)$matches[1]);
            return;
        }
        if (preg_match('#^/api/lessons/(\d+)/drills$#', $path, $matches)) {
            require_once dirname(__DIR__) . '/controllers/DrillController.php';
            $users = UserRepository::fromConfig();
            $controller = new DrillController(DrillRepository::fromConfig(), CurriculumRepository::fromConfig(), new AuthMiddleware($users));
            $controller->getDrills((int)$matches[1]);
            return;
        }
        if (preg_match('#^/api/lessons/(\d+)/writing$#', $path, $matches)) {
            require_once dirname(__DIR__) . '/controllers/WritingController.php';
            $users = UserRepository::fromConfig();
            $controller = new WritingController(WritingRepository::fromConfig(), CurriculumRepository::fromConfig(), new AuthMiddleware($users));
            $controller->getWriting((int)$matches[1]);
            return;
        }
        if (preg_match('#^/api/modules/(\d+)/quiz$#', $path, $matches)) {
            require_once dirname(__DIR__) . '/controllers/QuizController.php';
            $users = UserRepository::fromConfig();
            $controller = new QuizController(QuizRepository::fromConfig(), CurriculumRepository::fromConfig(), new AuthMiddleware($users));
            $controller->getQuiz((int)$matches[1]);
            return;
        }
    }
    
    if ($method === 'POST') {
        if (preg_match('#^/api/drills/(\d+)/answer$#', $path, $matches)) {
            require_once dirname(__DIR__) . '/controllers/DrillController.php';
            $users = UserRepository::fromConfig();
            $controller = new DrillController(DrillRepository::fromConfig(), CurriculumRepository::fromConfig(), new AuthMiddleware($users));
            $controller->submitAnswer((int)$matches[1]);
            return;
        }
        if (preg_match('#^/api/writing/(\d+)/submit$#', $path, $matches)) {
            require_once dirname(__DIR__) . '/controllers/WritingController.php';
            $users = UserRepository::fromConfig();
            $controller = new WritingController(WritingRepository::fromConfig(), CurriculumRepository::fromConfig(), new AuthMiddleware($users));
            $controller->submitWriting((int)$matches[1]);
            return;
        }
        if (preg_match('#^/api/quizzes/(\d+)/submit$#', $path, $matches)) {
            require_once dirname(__DIR__) . '/controllers/QuizController.php';
            $users = UserRepository::fromConfig();
            $controller = new QuizController(QuizRepository::fromConfig(), CurriculumRepository::fromConfig(), new AuthMiddleware($users));
            $controller->submitQuiz((int)$matches[1]);
            return;
        }
        
        // Admin
        if ($path === '/api/admin/setup') {
            require_once dirname(__DIR__) . '/controllers/AdminController.php';
            $users = UserRepository::fromConfig();
            $controller = new AdminController(AdminRepository::fromConfig(), new AuthMiddleware($users));
            $controller->setupAdmin();
            return;
        }
        if ($path === '/api/admin/modules') {
            require_once dirname(__DIR__) . '/controllers/AdminController.php';
            $users = UserRepository::fromConfig();
            $controller = new AdminController(AdminRepository::fromConfig(), new AuthMiddleware($users));
            $controller->createModule();
            return;
        }
        if ($path === '/api/admin/lessons') {
            require_once dirname(__DIR__) . '/controllers/AdminController.php';
            $users = UserRepository::fromConfig();
            $controller = new AdminController(AdminRepository::fromConfig(), new AuthMiddleware($users));
            $controller->createLesson();
            return;
        }
    }

    if ($method === 'PUT') {
        if (preg_match('#^/api/admin/modules/(\d+)/publish$#', $path, $matches)) {
            require_once dirname(__DIR__) . '/controllers/AdminController.php';
            $users = UserRepository::fromConfig();
            $controller = new AdminController(AdminRepository::fromConfig(), new AuthMiddleware($users));
            $controller->publishModule((int)$matches[1]);
            return;
        }
    }

    ResponseHelper::jsonError('NOT_FOUND', 'Endpoint tidak ditemukan.', 404);
}
