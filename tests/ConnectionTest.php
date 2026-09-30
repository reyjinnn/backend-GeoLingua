<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

function checkConnection(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $db = databaseConnection();

    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    checkConnection(count($tables) === 21, 'Expected 21 tables, got ' . count($tables));

    $expected = [
        'roles' => 2,
        'languages' => 3,
        'levels' => 6,
        'courses' => 1,
        'users' => 1,
    ];

    foreach ($expected as $table => $minimum) {
        $count = (int) $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        checkConnection($count >= $minimum, "Expected at least {$minimum} rows in {$table}, got {$count}");
    }

    echo "Connection test passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Connection test failed: " . $e->getMessage() . "\n");
    exit(1);
}