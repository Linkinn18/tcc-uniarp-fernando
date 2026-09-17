<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../crypto.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        tcc_audit_validation($pdo, null, 'id_vazio');
        echo json_encode(['success' => false, 'message' => 'ID não fornecido.']);
        exit;
    }

    $id = $data['id'];
    $signature = $data['sig'] ?? '';

    try {
        // Verificar se a assinatura é válida
        if (!tcc_verify_identifier_signature($id, $signature)) {
            tcc_audit_validation($pdo, $id, 'assinatura_invalida');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Falsificação detectada: Assinatura inválida.']);
            exit;
        }

        // Buscar medicamento no banco
        $stmt = $pdo->prepare("SELECT status FROM medicamentos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            tcc_audit_validation($pdo, $id, 'nao_encontrado');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Falsificação detectada: Medicamento não encontrado no sistema.']);
            exit;
        }

        if ($row['status'] == 1) {
            tcc_audit_validation($pdo, $id, 'ja_validado');
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Falsificação/Clonagem Detectada: Este código já foi validado anteriormente!']);
            exit;
        }

        // Atualizar para validado com UPDATE atômico (P03)
        $now = date('Y-m-d H:i:s');
        $update = $pdo->prepare("UPDATE medicamentos SET status = 1, data_validacao = ? WHERE id = ? AND status = 0");
        $update->execute([$now, $id]);
        
        if ($update->rowCount() === 0) {
            // Falha na atualização atômica (foi validado por outra requisição)
            tcc_audit_validation($pdo, $id, 'ja_validado');
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Falsificação/Clonagem Detectada: Este código já foi validado anteriormente!']);
            exit;
        }

        tcc_audit_validation($pdo, $id, 'autentico');
        echo json_encode(['success' => true, 'message' => 'Medicamento Autêntico. Unicidade validada com sucesso!']);
        
    } catch (\PDOException $e) {
        tcc_audit_validation($pdo, $id, 'erro_banco');
        echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
?>