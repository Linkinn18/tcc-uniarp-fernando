<?php
/*
 * Banco: nesta branch de teste usamos SQLite local em `data/tcc.sqlite`.
 * O arquivo será criado automaticamente ao incluir este arquivo.
 */

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Caminho do arquivo sqlite (relativo ao diretório do projeto)
// Preferência por alguns caminhos possíveis (tenta escrever no primeiro disponível)
$candidatePaths = [
    __DIR__ . '/data/tcc.sqlite',
    __DIR__ . '/sqlite/tcc.sqlite',
    __DIR__ . '/tcc.sqlite',
];

$sqliteFile = null;
foreach ($candidatePaths as $p) {
    $dir = dirname($p);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        $sqliteFile = $p;
        break;
    }
}

if ($sqliteFile === null) {
    // fallback para /tmp se nenhum diretório do projeto for gravável
    $sqliteFile = sys_get_temp_dir() . '/tcc.sqlite';
}

try {
    // Conectar via SQLite (arquivo local)
    $pdo = new PDO('sqlite:' . $sqliteFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Ajustes SQLite
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Criar tabela compatível com SQLite
    $pdo->exec("CREATE TABLE IF NOT EXISTS medicamentos (
        id TEXT PRIMARY KEY,
        nome TEXT NOT NULL,
        lote TEXT NOT NULL,
        data_fabricacao TEXT NOT NULL,
        assinatura TEXT NOT NULL,
        status INTEGER DEFAULT 0,
        data_validacao TEXT
    )");

} catch (\PDOException $e) {
    die(json_encode(['error' => 'Falha na conexão com o banco de dados SQLite. Erro: ' . $e->getMessage()]));
}
