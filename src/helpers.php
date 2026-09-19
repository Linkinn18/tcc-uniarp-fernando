<?php

declare(strict_types=1);

// Minimal helper functions used by services and tests.

if (!function_exists('tcc_generate_uuid_v4')) {
    function tcc_generate_uuid_v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('tcc_sign_identifier')) {
    function tcc_sign_identifier(string $id): string
    {
        // Prefer RSA signing if keys exist and RSA signer is available.
        if (function_exists('tcc_sign_identifier_rsa') && function_exists('tcc_keys_exist') && tcc_keys_exist()) {
            return tcc_sign_identifier_rsa($id);
        }

        // Fallback: simple HMAC-based signature for tests/local usage.
        $key = 'tcc-test-key';
        return base64_encode(hash_hmac('sha256', $id, $key, true));
    }
}

if (!function_exists('tcc_verify_identifier_signature')) {
    function tcc_verify_identifier_signature(string $id, string $sig): bool
    {
        // If RSA verifier exists and keys are present, prefer it.
        if (function_exists('tcc_verify_identifier_signature_rsa') && function_exists('tcc_keys_exist') && tcc_keys_exist()) {
            return tcc_verify_identifier_signature_rsa($id, $sig);
        }

        $expected = tcc_sign_identifier($id);
        return hash_equals($expected, $sig);
    }
}

if (!function_exists('tcc_audit_validation')) {
    function tcc_audit_validation(PDO $pdo, string $id, string $event): void
    {
        // No-op helper used in tests. Real implementation should persist audit info.
    }
}
