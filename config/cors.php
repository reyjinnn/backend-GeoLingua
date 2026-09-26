<?php
declare(strict_types=1);

/** Apply CORS only for explicitly configured browser origins. */
function applyCors(array $allowedOrigins): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 600');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code($origin !== '' && in_array($origin, $allowedOrigins, true) ? 204 : 403);
        exit;
    }
}
