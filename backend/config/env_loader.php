<?php
/**
 * env_loader.php
 *
 * Loads key=value pairs from a .env file into PHP constants / getenv().
 * This is a lightweight alternative to composer packages like vlucas/phpdotenv.
 *
 * Usage (call once, before any code that needs env vars):
 *   require_once __DIR__ . '/env_loader.php';
 *   load_env(__DIR__ . '/../../.env');
 *
 * Then read values with:
 *   getenv('GMAIL_USER')
 *   $_ENV['GMAIL_USER']
 */

/**
 * Parse and load a .env file.
 *
 * Rules:
 *  - Lines starting with # are comments and are ignored.
 *  - Blank lines are ignored.
 *  - Format: KEY=VALUE  (no surrounding quotes needed, but supported)
 *  - Already-set environment variables are NOT overwritten.
 *
 * @param  string $path  Absolute path to the .env file.
 * @return void
 * @throws RuntimeException if the file cannot be read.
 */
function load_env(string $path): void
{
    if (!is_readable($path)) {
        throw new RuntimeException(
            "env_loader: Cannot read .env file at: {$path}\n" .
            "Make sure the file exists and is readable by the web server."
        );
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split on the first '=' only
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue; // Malformed line — skip silently
        }

        $key   = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));

        // Strip surrounding quotes (single or double)
        if (
            strlen($value) >= 2 &&
            (
                ($value[0] === '"'  && $value[-1] === '"') ||
                ($value[0] === "'"  && $value[-1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        // Only set if not already defined in the environment
        if (!isset($_ENV[$key]) && getenv($key) === false) {
            $_ENV[$key]  = $value;
            putenv("{$key}={$value}");
        }
    }
}
