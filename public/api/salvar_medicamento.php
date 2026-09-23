<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../crypto.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Repository\MedicamentoRepository;
use App\Service\AssinaturaService;

require_auth_json();
require_method('POST');

$data = read_json_body();
if ($data === null) {
    json_response(['success' => false, 'message' => 'Payload inválido.'], 400);
}

if (empty($data['csrf_token']) || !tcc_verify_csrf_token($data['csrf_token'])) {
    json_response(['success' => false, 'message' => 'Token CSRF inválido.'], 419);
}

$nome = trim((string) ($data['nome'] ?? ''));
$lote = trim((string) ($data['lote'] ?? ''));

if ($nome === '' || $lote === '') {
    json_response(['success' => false, 'message' => 'Dados incompletos.'], 422);
}

try {
    $repo = new MedicamentoRepository($pdo);
    $service = new AssinaturaService($repo);
    $result = $service->createAndStore($nome, $lote);

    json_response([
        'success' => true,
        'message' => 'Medicamento gerado e assinado com sucesso!',
        'data' => $result,
    ]);
} catch (RuntimeException $e) {
    json_internal_error($e);
} catch (\PDOException $e) {
    if ($e->getCode() == 23000) {
        json_response(['success' => false, 'message' => 'Erro: ID já existe.'], 409);
    }

    json_internal_error($e);
}
