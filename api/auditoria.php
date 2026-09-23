<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

// Autenticar usuário - apenas administrador pode ver auditoria
if (!tcc_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Faça login primeiro.']);
    exit;
}

$filtro = $_GET['filtro'] ?? 'todos'; // todos, autentico, fraude, nao_encontrado, assinatura_invalida, ja_validado
$medicamento_id = $_GET['medicamento_id'] ?? '';
$dias = (int) ($_GET['dias'] ?? 7); // Últimos N dias (padrão 7)

try {
    $query = "SELECT id, medicamento_id, resultado, data, ip_address FROM validacoes WHERE 1=1";
    $params = [];

    // Filtrar por tipo de resultado
    if ($filtro === 'fraude') {
        $query .= " AND resultado IN ('nao_encontrado', 'assinatura_invalida', 'ja_validado')";
    } elseif ($filtro !== 'todos') {
        $query .= " AND resultado = ?";
        $params[] = $filtro;
    }

    // Filtrar por medicamento específico
    if ($medicamento_id !== '') {
        $query .= " AND medicamento_id = ?";
        $params[] = $medicamento_id;
    }

    // Filtrar por período (últimos N dias)
    $query .= " AND date(data) >= date('now', '-' || ? || ' days')";
    $params[] = $dias;

    $query .= " ORDER BY data DESC LIMIT 1000";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Contar tentativas de fraude por resultado
    $countStmt = $pdo->prepare(
        "SELECT 
            resultado, 
            COUNT(*) as quantidade 
        FROM validacoes 
        WHERE date(data) >= date('now', '-' || ? || ' days')
        GROUP BY resultado
        ORDER BY quantidade DESC"
    );
    $countStmt->execute([$dias]);
    $estatisticas = $countStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'filtro' => $filtro,
        'periodo_dias' => $dias,
        'total_registros' => count($rows),
        'estatisticas' => $estatisticas,
        'registros' => $rows,
    ]);
} catch (\PDOException $e) {
    tcc_log_exception($e, 'auditoria');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno.']);
}
?>
