<?php
declare(strict_types=1);

require_once __DIR__ . '/../crypto.php';

function tcc_backup_existing_keys(): ?string
{
    if (!tcc_keys_present()) {
        return null;
    }

    $backupDir = tcc_keys_dir() . '/backup-' . date('Ymd-His');
    tcc_ensure_directory($backupDir, 0700);

    foreach ([tcc_private_key_path(), tcc_public_key_path()] as $sourcePath) {
        $targetPath = $backupDir . '/' . basename($sourcePath);
        if (!copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Falha ao criar backup da chave existente em: ' . $targetPath);
        }
    }

    return $backupDir;
}

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script só pode ser executado pela linha de comando.\n");
    exit(1);
}

$force = in_array('--force', $argv, true);

try {
    $passphrase = tcc_private_key_passphrase();

    if (tcc_keys_exist() && !$force) {
        fwrite(STDOUT, "As chaves já existem em " . tcc_keys_dir() . ".\n");
        fwrite(STDOUT, "Fingerprint da chave pública atual: " . tcc_public_key_fingerprint() . "\n");
        fwrite(STDOUT, "Use --force apenas se quiser ROTACIONAR as chaves. A mesma passphrase não recria o mesmo par RSA.\n");
        exit(0);
    }

    $backupDir = null;
    if ($force && tcc_keys_present()) {
        $backupDir = tcc_backup_existing_keys();
    }

    $keyPair = tcc_generate_key_pair($passphrase);
    tcc_store_key_pair($keyPair);

    fwrite(STDOUT, "Par de chaves RSA gerado com sucesso.\n");
    fwrite(STDOUT, "Chaves salvas em: " . tcc_keys_dir() . "\n");
    fwrite(STDOUT, "Fingerprint da chave pública atual: " . tcc_public_key_fingerprint($keyPair['public']) . "\n");
    if ($backupDir !== null) {
        fwrite(STDOUT, "Backup das chaves anteriores salvo em: " . $backupDir . "\n");
    }
    fwrite(STDOUT, "A passphrase protege a chave privada exportada, mas não determina o par RSA.\n");
    fwrite(STDOUT, "Se as chaves antigas forem substituídas, assinaturas e QR Codes antigos deixarão de validar com a nova chave pública.\n");
    fwrite(STDOUT, "Permissões da chave privada ajustadas para 600.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
