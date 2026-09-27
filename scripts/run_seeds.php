<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

echo "=== GeoLingua Database Seed Runner ===\n";

try {
    $pdo = databaseConnection();
    echo "Connected to database successfully.\n";

    $seedFiles = [
        'onboarding.sql',
        'a1_curriculum.sql',
        'a1_drills.sql',
        'a1_writing.sql',
        'a1_quiz.sql',
    ];

    $seedDir = __DIR__ . '/../seeds';

    foreach ($seedFiles as $file) {
        $path = $seedDir . '/' . $file;
        if (!file_exists($path)) {
            echo "[SKIP] Seed file not found: {$file}\n";
            continue;
        }

        echo "[RUN] Executing {$file}...\n";
        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            echo "[WARN] Empty file: {$file}\n";
            continue;
        }

        $pdo->exec($sql);
        echo "[DONE] {$file} executed successfully.\n";
    }

    echo "=== All Seeds Completed Successfully! ===\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Seed error: " . $e->getMessage() . "\n");
    exit(1);
}
