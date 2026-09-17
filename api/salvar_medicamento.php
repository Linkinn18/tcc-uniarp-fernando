<?php
declare(strict_types=1);

require_once '../db.php';
require_once '../auth.php';
require_once '../crypto.php';

tcc_require_authentication(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    tcc_json_response(['success' => false, 'message' => 'Método inválido.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    tcc_json_response(['success' => false, 'message' => 'Payload inválido.'], 400);
}

tcc_require_csrf_token($data['csrf_token'] ?? null, true);

$nome = trim((string) ($data['nome'] ?? ''));
$lote = trim((string) ($data['lote'] ?? ''));

if ($nome === '' || $lote === '') {
    tcc_json_response(['success' => false, 'message' => 'Dados incompletos.'], 422);
}

$id = tcc_generate_uuid_v4();
$dataFabricacao = date('Y-m-d H:i:s');

try {
    $assinatura = tcc_sign_identifier($id);

    $stmt = $pdo->prepare('INSERT INTO medicamentos (id, nome, lote, data_fabricacao, assinatura, status) VALUES (?, ?, ?, ?, ?, 0)');
    $stmt->execute([$id, $nome, $lote, $dataFabricacao, $assinatura]);

    tcc_json_response([
        'success' => true,
        'message' => 'Medicamento gerado e assinado com sucesso!',
        'data' => [
            'id' => $id,
            'sig' => $assinatura,
        ],
    ]);
} catch (RuntimeException $e) {
    tcc_json_response(['success' => false, 'message' => $e->getMessage()], 500);
} catch (\PDOException $e) {
    if ($e->getCode() == 23000) {
        tcc_json_response(['success' => false, 'message' => 'Erro: ID já existe.'], 409);
    }

    tcc_json_response(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()], 500);
}
