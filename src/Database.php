<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    public static function getPath(): string
    {
        $configuredPath = getenv('TCC_DB_SQLITE_PATH') ?: ($_ENV['TCC_DB_SQLITE_PATH'] ?? null);

        if ($configuredPath !== null && $configuredPath !== '') {
            $dir = dirname($configuredPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            return $configuredPath;
        }

        $candidates = [
            __DIR__ . '/../../data/tcc.sqlite',
            __DIR__ . '/../../sqlite/tcc.sqlite',
            __DIR__ . '/../../tcc.sqlite',
        ];

        foreach ($candidates as $candidate) {
            $dir = dirname($candidate);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (is_dir($dir) && is_writable($dir)) {
                return $candidate;
            }
        }

        return sys_get_temp_dir() . '/tcc.sqlite';
    }

    public static function getPdo(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }
        $path = self::getPath();

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON;');

            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException('Falha na conexão com o banco de dados SQLite.', (int) $e->getCode(), $e);
        }
    }

    public static function initializeSchema(PDO $pdo, ?string $schemaPath = null): void
    {
        $schemaPath ??= dirname(__DIR__) . '/schema.sql';

        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new PDOException('Falha ao carregar o arquivo de schema.');
        }

        $pdo->exec($schema);
    }
}
