<?php

declare(strict_types=1);
// Enable verbose error reporting for local debug
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

file_put_contents('/tmp/salvar_debug.log', date('c') . " ENTER salvar_medicamento.php\n", FILE_APPEND);

// Capture fatal errors on shutdown
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err) {
        file_put_contents('/tmp/salvar_debug.log', date('c') . " SHUTDOWN_ERROR: " . print_r($err, true) . "\n", FILE_APPEND);
    }
});

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../crypto.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Repository\MedicamentoRepository;
use App\Service\AssinaturaService;

require_auth_json();
require_method('POST');

$data = read_json_body();
file_put_contents('/tmp/salvar_debug.log', date('c') . " START\n", FILE_APPEND);
file_put_contents('/tmp/salvar_debug.log', "raw_body=" . file_get_contents('php://input') . "\n", FILE_APPEND);
file_put_contents('/tmp/salvar_debug.log', 'data=' . json_encode($data) . "\n", FILE_APPEND);
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
    file_put_contents('/tmp/salvar_debug.log', "Before createAndStore\n", FILE_APPEND);
    $result = $service->createAndStore($nome, $lote);
    file_put_contents('/tmp/salvar_debug.log', "After createAndStore\n", FILE_APPEND);

    json_response([
        'success' => true,
        'message' => 'Medicamento gerado e assinado com sucesso!',
        'data' => $result,
    ]);
} catch (RuntimeException $e) {
    file_put_contents('/tmp/salvar_debug.log', 'RuntimeException: ' . $e->getMessage() . "\n", FILE_APPEND);
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
} catch (\PDOException $e) {
    file_put_contents('/tmp/salvar_debug.log', 'PDOException: ' . $e->getMessage() . "\n", FILE_APPEND);
    if ($e->getCode() == 23000) {
        json_response(['success' => false, 'message' => 'Erro: ID já existe.'], 409);
    }

    json_response(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()], 500);
}
