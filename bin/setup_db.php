<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../autoload.php';

use App\Database;

try {
    $pdo = Database::getPdo();
    Database::initializeSchema($pdo);

    fwrite(STDOUT, "Schema SQLite aplicado com sucesso em: " . Database::getPath() . PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    tcc_log_exception($exception, 'setup_db');
    fwrite(STDERR, 'Falha ao aplicar schema SQLite.' . PHP_EOL);
    exit(1);
}