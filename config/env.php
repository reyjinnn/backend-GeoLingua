<?php
declare(strict_types=1);

/** Read process environment first, then the local .env file. */
function appEnv(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    static $fileValues = null;
    if ($fileValues === null) {
        $fileValues = [];
        $path = dirname(__DIR__) . '/.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                if (preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $matches) !== 1) {
                    continue;
                }

                $value = trim($matches[2]);
                if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'")) {
                    if ($value[strlen($value) - 1] === $value[0]) {
                        $value = substr($value, 1, -1);
                    }
                }
                $fileValues[$matches[1]] = $value;
            }
        }
    }

    return $fileValues[$key] ?? $default;
}
