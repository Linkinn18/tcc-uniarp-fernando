<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function tcc_assert_openssl(): void
{
    if (!extension_loaded('openssl')) {
        throw new RuntimeException('A extensão OpenSSL do PHP não está habilitada.');
    }
}

function tcc_open_ssl_error_messages(): string
{
    $messages = [];

    while (($error = openssl_error_string()) !== false) {
        $messages[] = $error;
    }

    return implode(' | ', array_unique($messages));
}

function tcc_openssl_config_path(): ?string
{
    $configuredPath = getenv('OPENSSL_CONF');
    if (is_string($configuredPath) && $configuredPath !== '' && is_file($configuredPath)) {
        return $configuredPath;
    }

    $phpDir = dirname((string) PHP_BINARY);
    $candidates = [
        $phpDir . '/extras/ssl/openssl.cnf',
        dirname($phpDir) . '/apache/bin/openssl.cnf',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function tcc_openssl_keygen_config(): array
{
    $config = [
        'digest_alg' => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    $configPath = tcc_openssl_config_path();
    if ($configPath !== null) {
        $config['config'] = $configPath;
        putenv('OPENSSL_CONF=' . $configPath);
    }

    return $config;
}

function tcc_private_key_passphrase(): string
{
    $passphrase = tcc_env('TCC_KEY_PASSPHRASE');
    if ($passphrase === null || $passphrase === '') {
        throw new RuntimeException('Defina TCC_KEY_PASSPHRASE no arquivo .env antes de gerar ou usar as chaves.');
    }

    return $passphrase;
}

function tcc_generate_key_pair(string $passphrase): array
{
    tcc_assert_openssl();

    $config = tcc_openssl_keygen_config();

    $keyResource = openssl_pkey_new($config);
    if ($keyResource === false) {
        $details = tcc_open_ssl_error_messages();
        $message = 'Falha ao gerar o par de chaves RSA.';

        if ($details !== '') {
            $message .= ' OpenSSL: ' . $details;
        }

        throw new RuntimeException($message);
    }

    $privateKey = '';
    if (!openssl_pkey_export($keyResource, $privateKey, $passphrase, $config)) {
        $details = tcc_open_ssl_error_messages();
        $message = 'Falha ao exportar a chave privada.';

        if ($details !== '') {
            $message .= ' OpenSSL: ' . $details;
        }

        throw new RuntimeException($message);
    }

    $details = openssl_pkey_get_details($keyResource);
    if ($details === false || empty($details['key'])) {
        $opensslDetails = tcc_open_ssl_error_messages();
        $message = 'Falha ao obter a chave pública.';

        if ($opensslDetails !== '') {
            $message .= ' OpenSSL: ' . $opensslDetails;
        }

        throw new RuntimeException($message);
    }

    return [
        'private' => $privateKey,
        'public' => $details['key'],
    ];
}

function tcc_store_key_pair(array $keyPair): void
{
    tcc_ensure_directory(tcc_keys_dir(), 0700);
    tcc_sync_owner_with_storage_root(tcc_keys_dir());

    if (file_put_contents(tcc_private_key_path(), $keyPair['private'], LOCK_EX) === false) {
        throw new RuntimeException('Falha ao gravar a chave privada.');
    }

    if (file_put_contents(tcc_public_key_path(), $keyPair['public'], LOCK_EX) === false) {
        throw new RuntimeException('Falha ao gravar a chave pública.');
    }

    chmod(tcc_private_key_path(), 0600);
    chmod(tcc_public_key_path(), 0600);
    tcc_sync_owner_with_storage_root(tcc_private_key_path());
    tcc_sync_owner_with_storage_root(tcc_public_key_path());
}

function tcc_get_public_key(): string
{
    if (!tcc_keys_present()) {
        throw new RuntimeException('As chaves ainda não foram geradas.');
    }

    if (!is_readable(tcc_public_key_path())) {
        throw new RuntimeException('A chave pública existe, mas o processo atual não tem permissão de leitura.');
    }

    $publicKey = file_get_contents(tcc_public_key_path());
    if ($publicKey === false || trim($publicKey) === '') {
        throw new RuntimeException('Não foi possível ler a chave pública.');
    }

    return $publicKey;
}

function tcc_public_key_fingerprint(?string $publicKeyPem = null): string
{
    $publicKeyPem ??= tcc_get_public_key();

    $normalized = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', $publicKeyPem);
    if (!is_string($normalized) || $normalized === '') {
        throw new RuntimeException('Não foi possível normalizar a chave pública para gerar o fingerprint.');
    }

    $der = base64_decode($normalized, true);
    if ($der === false) {
        throw new RuntimeException('Não foi possível decodificar a chave pública para gerar o fingerprint.');
    }

    return implode(':', str_split(hash('sha256', $der), 2));
}

function tcc_sign_identifier_rsa(string $identifier): string
{
    if (!tcc_keys_present()) {
        throw new RuntimeException('As chaves ainda não foram geradas.');
    }

    if (!is_readable(tcc_private_key_path())) {
        throw new RuntimeException('A chave privada existe, mas o processo atual não tem permissão de leitura.');
    }

    $privateKeyPem = file_get_contents(tcc_private_key_path());
    if ($privateKeyPem === false || trim($privateKeyPem) === '') {
        throw new RuntimeException('Não foi possível ler a chave privada.');
    }

    $privateKey = openssl_pkey_get_private($privateKeyPem, tcc_private_key_passphrase());
    if ($privateKey === false) {
        throw new RuntimeException('Não foi possível desbloquear a chave privada com a passphrase configurada.');
    }

    $signature = '';
    if (!openssl_sign($identifier, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Falha ao assinar o identificador do medicamento.');
    }

    return base64_encode($signature);
}

function tcc_verify_identifier_signature_rsa(string $identifier, string $signatureBase64): bool
{
    try {
        $publicKey = tcc_get_public_key();
        $publicKeyResource = openssl_pkey_get_public($publicKey);

        if ($publicKeyResource === false) {
            return false;
        }

        $normalizedSignature = function_exists('tcc_normalize_signature_base64')
            ? tcc_normalize_signature_base64($signatureBase64)
            : $signatureBase64;
        if ($normalizedSignature === null) {
            return false;
        }

        $signature = base64_decode($normalizedSignature, true);
        if ($signature === false) {
            return false;
        }

        $result = openssl_verify($identifier, $signature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        return $result === 1;
    } catch (Throwable $e) {
        return false;
    }
}

// `tcc_generate_uuid_v4` is intentionally implemented in `src/helpers.php`.
