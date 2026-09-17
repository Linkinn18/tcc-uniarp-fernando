<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

function tcc_seed_default_user(PDO $pdo): void
{
    $defaultAdminUser = tcc_env('TCC_ADMIN_USER');
    $defaultAdminPassword = tcc_env('TCC_ADMIN_PASSWORD');

    if ($defaultAdminUser === null || $defaultAdminUser === '' || $defaultAdminPassword === null || $defaultAdminPassword === '') {
        return;
    }

    $checkUser = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? LIMIT 1');
    $checkUser->execute([$defaultAdminUser]);

    if ($checkUser->fetch()) {
        return;
    }

    $insertUser = $pdo->prepare('INSERT INTO usuarios (username, password_hash) VALUES (?, ?)');
    $insertUser->execute([
        $defaultAdminUser,
        password_hash($defaultAdminPassword, PASSWORD_DEFAULT),
    ]);
}

function tcc_audit_validation(PDO $pdo, ?string $medicamentoId, string $resultado): void
{
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
        
        $stmt = $pdo->prepare(
            'INSERT INTO validacoes (medicamento_id, resultado, data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $medicamentoId,
            $resultado,
            date('Y-m-d H:i:s'),
            $ipAddress,
            $userAgent,
        ]);
    } catch (Throwable $e) {
        // Log silenciosamente para não quebrar a validação
    }
}

function tcc_resolve_sqlite_path(): string
{
    $configuredPath = tcc_env('TCC_DB_SQLITE_PATH');
    if ($configuredPath !== null && $configuredPath !== '') {
        $directory = dirname($configuredPath);
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $configuredPath;
    }

    $candidatePaths = [
        __DIR__ . '/data/tcc.sqlite',
        __DIR__ . '/sqlite/tcc.sqlite',
        __DIR__ . '/tcc.sqlite',
    ];

    foreach ($candidatePaths as $path) {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        if (is_dir($directory) && is_writable($directory)) {
            return $path;
        }
    }

    return sys_get_temp_dir() . '/tcc.sqlite';
}

try {
    $sqliteFile = tcc_resolve_sqlite_path();
    $pdo = new PDO('sqlite:' . $sqliteFile, null, null, $options);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS medicamentos (
            id TEXT PRIMARY KEY,
            nome TEXT NOT NULL,
            lote TEXT NOT NULL,
            data_fabricacao TEXT NOT NULL,
            assinatura TEXT NOT NULL,
            status INTEGER DEFAULT 0,
            data_validacao TEXT
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS validacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            medicamento_id TEXT,
            resultado TEXT NOT NULL,
            data TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address TEXT,
            user_agent TEXT
        )"
    );

    tcc_seed_default_user($pdo);
} catch (\PDOException $e) {
    die(json_encode(['error' => 'Falha na conexão com o banco de dados SQLite. Erro: ' . $e->getMessage()]));
}
