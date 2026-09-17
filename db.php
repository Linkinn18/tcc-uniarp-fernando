<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

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

try {
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

    $defaultAdminUser = tcc_env('TCC_ADMIN_USER');
    $defaultAdminPassword = tcc_env('TCC_ADMIN_PASSWORD');

    if ($defaultAdminUser !== null && $defaultAdminUser !== '' && $defaultAdminPassword !== null && $defaultAdminPassword !== '') {
        $checkUser = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? LIMIT 1');
        $checkUser->execute([$defaultAdminUser]);

        if (!$checkUser->fetch()) {
            $insertUser = $pdo->prepare('INSERT INTO usuarios (username, password_hash) VALUES (?, ?)');
            $insertUser->execute([
                $defaultAdminUser,
                password_hash($defaultAdminPassword, PASSWORD_DEFAULT),
            ]);
        }
    }
} catch (\PDOException $e) {
    die(json_encode(['error' => 'Falha na conexão com o banco de dados. Verifique se o MySQL (XAMPP) está rodando e se o usuário tem permissão para criar o banco. Erro: ' . $e->getMessage()]));
}
?>
