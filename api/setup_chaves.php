<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['publicKey']) || empty($data['privateKey'])) {
        echo json_encode(['success' => false, 'message' => 'Chaves ausentes.']);
        exit;
    }

    $pubPath = '../keys/public.key';
    $privPath = '../keys/private.key';

    // We allow overwriting for the sake of the TCC demonstration
    $pubResult = file_put_contents($pubPath, $data['publicKey']);
    $privResult = file_put_contents($privPath, $data['privateKey']);

    if ($pubResult === false || $privResult === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar as chaves no servidor. Verifique as permissões da pasta keys.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Chaves salvas com sucesso!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
?>
