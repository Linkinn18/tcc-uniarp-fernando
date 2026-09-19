<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    public static function getPdo(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $configuredPath = getenv('TCC_DB_SQLITE_PATH') ?: ($_ENV['TCC_DB_SQLITE_PATH'] ?? null);

        if ($configuredPath !== null && $configuredPath !== '') {
            $path = $configuredPath;
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        } else {
            $candidates = [
                __DIR__ . '/../../data/tcc.sqlite',
                __DIR__ . '/../../sqlite/tcc.sqlite',
                __DIR__ . '/../../tcc.sqlite',
            ];

            $path = sys_get_temp_dir() . '/tcc.sqlite';
            foreach ($candidates as $candidate) {
                $dir = dirname($candidate);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                if (is_dir($dir) && is_writable($dir)) {
                    $path = $candidate;
                    break;
                }
            }
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON;');

            // Ensure schema exists (idempotent)
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS medicamentos (
                    id TEXT PRIMARY KEY,
                    nome TEXT NOT NULL,
                    lote TEXT NOT NULL,
                    data_fabricacao TEXT NOT NULL,
                    assinatura TEXT NOT NULL,
                    status INTEGER DEFAULT 0,
                    data_validacao TEXT
                )"
            );

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS usuarios (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT NOT NULL UNIQUE,
                    password_hash TEXT NOT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                )"
            );

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS validacoes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    medicamento_id TEXT,
                    resultado TEXT NOT NULL,
                    data TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    ip_address TEXT,
                    user_agent TEXT
                )"
            );

            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException('Falha na conexão com o banco de dados SQLite. Erro: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}
