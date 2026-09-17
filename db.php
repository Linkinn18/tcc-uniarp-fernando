<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$driver = strtolower((string) tcc_env('TCC_DB_DRIVER', 'mysql'));
$host = tcc_env('TCC_DB_HOST', 'localhost');
$db = tcc_env('TCC_DB_NAME', 'tcc_medicamentos');
$user = tcc_env('TCC_DB_USER', 'root');
$pass = tcc_env('TCC_DB_PASS', '');
$charset = tcc_env('TCC_DB_CHARSET', 'utf8mb4');

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
    if ($driver === 'sqlite') {
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

        tcc_seed_default_user($pdo);
    } else {
        $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db`");

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS medicamentos (
                id VARCHAR(36) PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                lote VARCHAR(50) NOT NULL,
                data_fabricacao DATETIME NOT NULL,
                assinatura TEXT NOT NULL,
                status TINYINT(1) DEFAULT 0 COMMENT '0 = Nao Validado, 1 = Validado',
                data_validacao DATETIME NULL
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS usuarios (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(80) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );

        tcc_seed_default_user($pdo);
    }
} catch (\PDOException $e) {
    $dbLabel = $driver === 'sqlite' ? 'SQLite' : 'MySQL';
    die(json_encode(['error' => 'Falha na conexão com o banco de dados ' . $dbLabel . '. Erro: ' . $e->getMessage()]));
}
