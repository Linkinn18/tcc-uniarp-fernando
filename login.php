<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

tcc_start_session();

if (tcc_is_authenticated()) {
    header('Location: fabricante.php');
    exit;
}

$errorMessage = '';
$infoMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    tcc_require_csrf_token($_POST['csrf_token'] ?? null);

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errorMessage = 'Informe usuário e senha.';
    } elseif (tcc_login($pdo, $username, $password)) {
        header('Location: fabricante.php');
        exit;
    } else {
        $errorMessage = 'Credenciais inválidas.';
    }
}

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
if ($userCount === 0) {
    $infoMessage = 'Nenhum usuário foi configurado ainda. Defina TCC_ADMIN_USER e TCC_ADMIN_PASSWORD no arquivo .env.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Fabricante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .login-card { max-width: 420px; margin: 8vh auto; border: 0; border-radius: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow login-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-2">Acesso do Fabricante</h1>
                    <p class="text-muted mb-0">Faça login para emitir novos medicamentos.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if ($infoMessage !== ''): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>

                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none">Voltar ao início</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
