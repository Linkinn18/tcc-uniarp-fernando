<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
file_put_contents('/tmp/salvar_debug.log', date('c') . " ENTER included bootstrap.php (from _bootstrap)\n", FILE_APPEND);
require_once __DIR__ . '/../../db.php';
file_put_contents('/tmp/salvar_debug.log', date('c') . " ENTER included db.php (from _bootstrap)\n", FILE_APPEND);
require_once __DIR__ . '/../../auth.php';
file_put_contents('/tmp/salvar_debug.log', date('c') . " ENTER included auth.php (from _bootstrap)\n", FILE_APPEND);

function json_response(array $payload, int $status = 200): void
{
    tcc_json_response($payload, $status);
}

function read_json_body(): ?array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : null;
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        json_response(['success' => false, 'message' => 'Método inválido.'], 405);
    }
}

function require_auth_json(): void
{
    tcc_require_authentication(true);
}
