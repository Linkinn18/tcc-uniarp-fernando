<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../autoload.php';

use App\Database;

try {
    $pdo = Database::getPdo();
    Database::initializeSchema($pdo);

    $legacyRows = (int) ($pdo->query("SELECT COUNT(*) FROM medicamentos WHERE fabricante_username IS NULL OR TRIM(fabricante_username) = ''")?->fetchColumn() ?: 0);
    if ($legacyRows > 0) {
        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM validacoes');
        $pdo->exec('DELETE FROM medicamentos');
        $pdo->commit();

        fwrite(STDOUT, "Foram removidos registros antigos sem vínculo de fabricante para reconstrução correta da base." . PHP_EOL);
    }

    fwrite(STDOUT, "Schema SQLite aplicado com sucesso em: " . Database::getPath() . PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    tcc_log_exception($exception, 'setup_db');
    fwrite(STDERR, 'Falha ao aplicar schema SQLite.' . PHP_EOL);
    exit(1);
}