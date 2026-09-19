<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class MedicamentoRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert(string $id, string $nome, string $lote, string $dataFabricacao, string $assinatura): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO medicamentos (id, nome, lote, data_fabricacao, assinatura, status) VALUES (?, ?, ?, ?, ?, 0)');
        $stmt->execute([$id, $nome, $lote, $dataFabricacao, $assinatura]);
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
        $query = "SELECT id, nome, lote, data_fabricacao, status, data_validacao FROM medicamentos WHERE 1=1";
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

        $query .= " ORDER BY data_fabricacao DESC LIMIT 100";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $key => $row) {
            $rows[$key]['status_text'] = ((int)($row['status'] ?? 0)) === 1 ? 'Validado' : 'Não validado';
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
}
