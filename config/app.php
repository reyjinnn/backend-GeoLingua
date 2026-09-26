<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

$timezone = appEnv('APP_TIMEZONE', 'UTC') ?: 'UTC';
date_default_timezone_set($timezone);

return [
    'environment' => appEnv('APP_ENV', 'local'),
    'debug' => filter_var(appEnv('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'cors_allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', appEnv('CORS_ALLOWED_ORIGINS', '') ?? '')
    ))),
];
