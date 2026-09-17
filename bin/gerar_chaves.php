<?php
declare(strict_types=1);

require_once __DIR__ . '/../crypto.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script só pode ser executado pela linha de comando.\n");
    exit(1);
}

$force = in_array('--force', $argv, true);

try {
    $passphrase = tcc_private_key_passphrase();

    if (tcc_keys_exist() && !$force) {
        fwrite(STDOUT, "As chaves já existem em " . tcc_keys_dir() . ". Use --force para regenerar.\n");
        exit(0);
    }

    $keyPair = tcc_generate_key_pair($passphrase);
    tcc_store_key_pair($keyPair);

    fwrite(STDOUT, "Par de chaves RSA gerado com sucesso.\n");
    fwrite(STDOUT, "Chaves salvas em: " . tcc_keys_dir() . "\n");
    fwrite(STDOUT, "Permissões da chave privada ajustadas para 600.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
