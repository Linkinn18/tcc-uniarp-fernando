<?php
$host = 'localhost';
$db   = 'tcc_medicamentos';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die(json_encode(['error' => 'Falha na conexão com o banco de dados. Verifique se o MySQL (XAMPP) está rodando e o banco tcc_medicamentos foi criado. Erro: ' . $e->getMessage()]));
}
?>
