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
        $this->dateColumn = $this->detectDateColumn();
    }

    public function insert(string $id, string $nome, string $lote, string $criadoEm, string $assinatura): void
    {
        $stmt = $this->pdo->prepare(sprintf('INSERT INTO medicamentos (id, nome, lote, %s, assinatura, status) VALUES (?, ?, ?, ?, ?, 0)', $this->dateColumn));
        $stmt->execute([$id, $nome, $lote, $criadoEm, $assinatura]);
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

    public function markValidatedAtomic(string $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $update = $this->pdo->prepare("UPDATE medicamentos SET status = 1, data_validacao = ? WHERE id = ? AND status = 0");
        $update->execute([$now, $id]);
        return $update->rowCount() > 0;
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
