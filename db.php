<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php';

file_put_contents('/tmp/salvar_debug.log', date('c') . " ENTER db.php\n", FILE_APPEND);

use App\Database;

try {
    $pdo = Database::getPdo();

    // Seed default admin if env is present
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
    // If database cannot be created, fail early with a JSON message for APIs
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, 'DB error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode(['error' => 'Falha na conexão com o banco de dados SQLite. Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
