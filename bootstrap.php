<?php
declare(strict_types=1);

file_put_contents('/tmp/salvar_debug.log', date('c') . " ENTER root bootstrap.php\n", FILE_APPEND);

const TCC_APP_ROOT = __DIR__;

function tcc_load_env_file(string $filePath): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', $trimmed, 2), 2, '');
        $name = trim($name);
        if ($name === '') {
            continue;
        }

        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) !== false || isset($_ENV[$name]) || isset($_SERVER[$name])) {
            continue;
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

tcc_load_env_file(TCC_APP_ROOT . '/.env');

function tcc_env(string $name, ?string $default = null): ?string
{
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

function tcc_storage_root(): string
{
    static $storageRoot;

    if ($storageRoot !== null) {
        return $storageRoot;
    }

    $configured = tcc_env('TCC_STORAGE_PATH');
    $storageRoot = $configured !== null
        ? rtrim($configured, '/\\')
        : dirname(TCC_APP_ROOT, 2) . '/storage/tcc';

    return $storageRoot;
}

function tcc_ensure_directory(string $directory, int $permissions = 0700): void
{
    if (is_dir($directory)) {
        return;
    }

    if (!mkdir($directory, $permissions, true) && !is_dir($directory)) {
        throw new RuntimeException('Não foi possível criar o diretório: ' . $directory);
    }
}

function tcc_keys_dir(): string
{
    return tcc_storage_root() . '/keys';
}

function tcc_sessions_dir(): string
{
    return tcc_storage_root() . '/sessions';
}

function tcc_public_key_path(): string
{
    return tcc_keys_dir() . '/public.pem';
}

function tcc_private_key_path(): string
{
    return tcc_keys_dir() . '/private.pem';
}

function tcc_keys_exist(): bool
{
    return is_file(tcc_public_key_path()) && is_file(tcc_private_key_path());
}

function tcc_json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
