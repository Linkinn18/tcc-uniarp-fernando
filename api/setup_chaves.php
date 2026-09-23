<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

tcc_json_response([
    'success' => false,
    'message' => 'A geração de chaves via HTTP foi desativada. Use o comando: php bin/gerar_chaves.php',
], 403);
