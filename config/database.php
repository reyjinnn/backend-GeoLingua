<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/** Open the database only when a repository first needs it. */
function databaseConnection(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = appEnv('DB_HOST', '') ?? '';
    $name = appEnv('DB_NAME', '') ?? '';
    $user = appEnv('DB_USER', '') ?? '';
    $password = appEnv('DB_PASSWORD', '') ?? '';
    $port = appEnv('DB_PORT', '3306') ?? '3306';
    $charset = appEnv('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4';

    if ($host === '' || $name === '' || $user === '') {
        throw new RuntimeException('Database configuration is incomplete.');
    }
    if (!ctype_digit($port) || !in_array($charset, ['utf8mb4', 'utf8'], true)) {
        throw new RuntimeException('Database configuration is invalid.');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $connection = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
