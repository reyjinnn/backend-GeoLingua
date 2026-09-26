<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';

/** Add the documented /api/* endpoints here as their controllers are built. */
function dispatchApi(string $method, string $path): void
{
    if ($method === 'GET' && $path === '/api/health') {
        ResponseHelper::jsonResponse(['status' => 'ok']);
        return;
    }

    ResponseHelper::jsonError('NOT_FOUND', 'Endpoint tidak ditemukan.', 404);
}
