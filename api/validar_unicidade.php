<?php
// Deprecated: moved to public/api/validar_unicidade.php
http_response_code(410);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['success' => false, 'message' => 'Endpoint movido: use /public/api/validar_unicidade.php'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>