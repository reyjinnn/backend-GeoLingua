<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';

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

    ResponseHelper::jsonError('NOT_FOUND', 'Endpoint tidak ditemukan.', 404);
}
