<?php
declare(strict_types=1);

$tests = glob(__DIR__ . '/tests/*Test.php');
sort($tests);

if ($tests === []) {
    fwrite(STDERR, "No test files found in tests/\n");
    exit(1);
}

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    $name = basename($test);
    echo "=== Running {$name} ===\n";

    $output = [];
    $exitCode = 0;
    exec("php " . escapeshellarg($test) . " 2>&1", $output, $exitCode);
    echo implode("\n", $output) . "\n";

    if ($exitCode === 0) {
        $passed++;
    } else {
        $failed++;
        echo "FAILED: {$name}\n";
    }
    echo "\n";
}

echo "================================\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "================================\n";

exit($failed > 0 ? 1 : 0);