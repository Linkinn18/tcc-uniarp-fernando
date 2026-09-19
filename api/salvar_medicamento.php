<?php
// Deprecated: moved to public/api/salvar_medicamento.php
http_response_code(410);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['success' => false, 'message' => 'Endpoint movido: use /public/api/salvar_medicamento.php'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
tcc_require_authentication(true);
