<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

file_put_contents('/tmp/salvar_debug.log', date('c') . " ENTER auth.php\n", FILE_APPEND);

function tcc_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    tcc_ensure_directory(tcc_sessions_dir(), 0700);

    session_name('tcc_session');
    session_save_path(tcc_sessions_dir());
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

function tcc_is_authenticated(): bool
{
    tcc_start_session();
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

function tcc_authenticated_username(): ?string
{
    tcc_start_session();
    return $_SESSION['username'] ?? null;
}

function tcc_csrf_token(): string
{
    tcc_start_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function tcc_verify_csrf_token(?string $token): bool
{
    tcc_start_session();

    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function tcc_require_csrf_token(?string $token, bool $expectsJson = false): void
{
    if (tcc_verify_csrf_token($token)) {
        return;
    }

    if ($expectsJson) {
        tcc_json_response(['success' => false, 'message' => 'Token CSRF inválido.'], 419);
    }

    http_response_code(419);
    exit('Token CSRF inválido.');
}

function tcc_login(PDO $pdo, string $username, string $password): bool
{
    tcc_start_session();

    $statement = $pdo->prepare('SELECT id, username, password_hash FROM usuarios WHERE username = ? LIMIT 1');
    $statement->execute([$username]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return true;
}

function tcc_logout(): void
{
    tcc_start_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function tcc_require_authentication(bool $expectsJson = false): void
{
    if (tcc_is_authenticated()) {
        return;
    }

    if ($expectsJson) {
        tcc_json_response(['success' => false, 'message' => 'Autenticação obrigatória.'], 401);
    }

    header('Location: login.php');
    exit;
}
