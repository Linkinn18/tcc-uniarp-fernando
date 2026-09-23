<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../crypto.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Repository\MedicamentoRepository;
use App\Service\ValidacaoService;

require_method('POST');

$data = read_json_body();
if ($data === null || empty($data['id'])) {
    tcc_audit_validation($pdo, null, 'id_vazio');
    json_response(['success' => false, 'message' => 'ID não fornecido.'], 400);
}

$id = $data['id'];
$signature = $data['sig'] ?? '';

try {
    $repo = new MedicamentoRepository($pdo);
    $service = new ValidacaoService($repo, $pdo);

    $result = $service->validateAndMark($id, $signature);

    json_response(['success' => $result['success'], 'message' => $result['message']], $result['status']);
} catch (\PDOException $e) {
    tcc_audit_validation($pdo, $id, 'erro_banco');
    json_internal_error($e);
}
