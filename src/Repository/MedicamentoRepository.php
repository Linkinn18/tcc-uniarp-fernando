<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class MedicamentoRepository
{
    private PDO $pdo;
    private string $dateColumn;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchemaCompatibility();
        $this->dateColumn = $this->detectDateColumn();
    }

    public function insert(string $id, string $nome, string $lote, string $criadoEm, string $assinatura, ?string $fabricanteUsername): void
    {
        $stmt = $this->pdo->prepare(sprintf('INSERT INTO medicamentos (id, nome, lote, %s, fabricante_username, assinatura, status) VALUES (?, ?, ?, ?, ?, ?, 0)', $this->dateColumn));
        $stmt->execute([$id, $nome, $lote, $criadoEm, $fabricanteUsername, $assinatura]);
    }

    public function find(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM medicamentos WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function search(?string $nome, ?string $lote, ?string $id): array
    {
        $query = sprintf('SELECT id, nome, lote, %s AS criado_em, assinatura, status, data_validacao FROM medicamentos WHERE 1=1', $this->dateColumn);
        $params = [];

        if ($nome !== null && $nome !== '') {
            $query .= " AND nome LIKE ?";
            $params[] = "%$nome%";
        }

        if ($lote !== null && $lote !== '') {
            $query .= " AND lote LIKE ?";
            $params[] = "%$lote%";
        }

        if ($id !== null && $id !== '') {
            $query .= " AND id LIKE ?";
            $params[] = "%$id%";
        }

        $query .= sprintf(' ORDER BY %s DESC LIMIT 100', $this->dateColumn);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $key => $row) {
            $rows[$key]['status_text'] = ((int)($row['status'] ?? 0)) === 1 ? 'Validado' : 'Não validado';
            $rows[$key]['hash'] = hash('sha256', (string) ($row['assinatura'] ?? ''));
        }

        return $rows;
    }

    public function getDashboardSummary(): array
    {
        $totals = $this->pdo->query('SELECT COUNT(*) AS total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS validated, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS not_validated FROM medicamentos')
            ?->fetch(PDO::FETCH_ASSOC) ?: [];

        $byManufacturer = $this->pdo->query("SELECT COALESCE(NULLIF(fabricante_username, ''), 'Não informado') AS fabricante_username, COUNT(*) AS total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS validated, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS not_validated FROM medicamentos GROUP BY COALESCE(NULLIF(fabricante_username, ''), 'Não informado') ORDER BY total DESC, fabricante_username ASC")
            ?->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'totals' => [
                'total' => (int) ($totals['total'] ?? 0),
                'validated' => (int) ($totals['validated'] ?? 0),
                'not_validated' => (int) ($totals['not_validated'] ?? 0),
            ],
            'by_manufacturer' => array_map(static function (array $row): array {
                return [
                    'fabricante_username' => (string) ($row['fabricante_username'] ?? 'Não informado'),
                    'total' => (int) ($row['total'] ?? 0),
                    'validated' => (int) ($row['validated'] ?? 0),
                    'not_validated' => (int) ($row['not_validated'] ?? 0),
                ];
            }, $byManufacturer),
        ];
    }

    public function markValidatedAtomic(string $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $update = $this->pdo->prepare("UPDATE medicamentos SET status = 1, data_validacao = ? WHERE id = ? AND status = 0");
        $update->execute([$now, $id]);
        return $update->rowCount() > 0;
    }

    private function ensureSchemaCompatibility(): void
    {
        $columns = $this->pdo->query('PRAGMA table_info(medicamentos)')?->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $hasFabricanteUsername = false;

        foreach ($columns as $column) {
            if (($column['name'] ?? null) === 'fabricante_username') {
                $hasFabricanteUsername = true;
                break;
            }
        }

        if (!$hasFabricanteUsername) {
            $this->pdo->exec('ALTER TABLE medicamentos ADD COLUMN fabricante_username TEXT NULL');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_medicamentos_fabricante_username ON medicamentos(fabricante_username)');
        }
    }

    private function detectDateColumn(): string
    {
        $columns = $this->pdo->query('PRAGMA table_info(medicamentos)')->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $column) {
            if (($column['name'] ?? null) === 'criado_em') {
                return 'criado_em';
            }
        }

        return 'data_fabricacao';
    }
}
