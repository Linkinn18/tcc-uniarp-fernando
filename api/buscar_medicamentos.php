<?php
// Deprecated: moved to public/api/buscar_medicamentos.php
http_response_code(410);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['success' => false, 'message' => 'Endpoint movido: use /public/api/buscar_medicamentos.php'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>