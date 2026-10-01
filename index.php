<?php
declare(strict_types=1);

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/routes/api.php';
require_once __DIR__ . '/helpers/ApiException.php';

try {
    $app = require __DIR__ . '/config/app.php';
    applyCors($app['cors_allowed_origins']);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $path = preg_replace('#^.*?(/api(?:/.*)?)$#', '$1', $rawPath);
    dispatchApi($method, rtrim($path, '/') ?: '/');
} catch (ApiException $error) {
    ResponseHelper::jsonError($error->errorCode, $error->getMessage(), $error->status, $error->details);
} catch (Throwable $error) {
    error_log($error);
    ResponseHelper::jsonError('INTERNAL_SERVER_ERROR', 'Terjadi kesalahan pada server.', 500);
}
