<?php
$host = 'localhost';
$db   = 'tcc_medicamentos';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

/* Ao testar em outro computador, me deparei com a falta do banco, logo, vou garantir que não tenha esse problema.
Já que decidi não utilizar SQLite para facilitar a movimentação */
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
} catch (\PDOException $e) {
    die(json_encode(['error' => 'Falha na conexão com o banco de dados. Verifique se o MySQL (XAMPP) está rodando e se o usuário tem permissão para criar o banco. Erro: ' . $e->getMessage()]));
}
?>
