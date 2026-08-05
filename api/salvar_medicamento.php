<?php
header('Content-Type: application/json');
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id']) || empty($data['nome']) || empty($data['lote']) || empty($data['assinatura'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
        exit;
    }

    $id = $data['id'];
    $nome = $data['nome'];
    $lote = $data['lote'];
    $assinatura = $data['assinatura'];
    $data_fabricacao = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("INSERT INTO medicamentos (id, nome, lote, data_fabricacao, assinatura, status) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$id, $nome, $lote, $data_fabricacao, $assinatura]);
        
        echo json_encode(['success' => true, 'message' => 'Medicamento gerado e assinado com sucesso!']);
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Erro: ID já existe.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
?>
