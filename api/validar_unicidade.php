<?php
header('Content-Type: application/json');
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID não fornecido.']);
        exit;
    }

    $id = $data['id'];

    try {
        $stmt = $pdo->prepare("SELECT status FROM medicamentos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Falsificação detectada: Medicamento não encontrado no sistema.']);
            exit;
        }

        if ($row['status'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Falsificação/Clonagem Detectada: Este código já foi validado anteriormente!']);
        } else {
            // Update to validated
            $update = $pdo->prepare("UPDATE medicamentos SET status = 1, data_validacao = NOW() WHERE id = ?");
            $update->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Medicamento Autêntico. Unicidade validada com sucesso!']);
        }

    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
?>
