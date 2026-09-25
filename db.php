<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/autoload.php';

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
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, 'DB error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    tcc_log_exception($e, 'db');
    tcc_abort_internal_error('Erro interno.', 500);
}
