<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../autoload.php';

use App\Repository\MedicamentoRepository;

require_auth_json();

$nome = trim($_GET['nome'] ?? '');
$lote = trim($_GET['lote'] ?? '');
$id = trim($_GET['id'] ?? '');
$fabricanteUsername = tcc_authenticated_username();

try {
    $repo = new MedicamentoRepository($pdo);
    $rows = $repo->search($nome, $lote, $id, $fabricanteUsername);

    json_response(['success' => true, 'data' => $rows]);
} catch (\PDOException $e) {
    json_internal_error($e);
}
