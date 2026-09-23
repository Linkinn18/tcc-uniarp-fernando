<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

function json_response(array $payload, int $status = 200): void
{
    tcc_json_response($payload, $status);
}

function json_internal_error(\Throwable $exception, string $message = 'Erro interno.'): void
{
    tcc_log_exception($exception, 'api');
    json_response(['success' => false, 'message' => $message], 500);
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
