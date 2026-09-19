<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_auth_json();

$nome = trim($_GET['nome'] ?? '');
$lote = trim($_GET['lote'] ?? '');
$id = trim($_GET['id'] ?? '');

try {
    $query = "SELECT id, nome, lote, data_fabricacao, status, data_validacao FROM medicamentos WHERE 1=1";
    $params = [];

    if ($nome !== '') {
        $query .= " AND nome LIKE ?";
        $params[] = "%$nome%";
    }

    if ($lote !== '') {
        $query .= " AND lote LIKE ?";
        $params[] = "%$lote%";
    }

    if ($id !== '') {
        $query .= " AND id LIKE ?";
        $params[] = "%$id%";
    }

    $query .= " ORDER BY data_fabricacao DESC LIMIT 100";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $rows = $stmt->fetchAll();
    foreach ($rows as $key => $row) {
        $rows[$key]['status_text'] = $row['status'] == 1 ? 'Validado' : 'Não validado';
    }

    json_response(['success' => true, 'data' => $rows]);
} catch (\PDOException $e) {
    json_response(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()], 500);
}
