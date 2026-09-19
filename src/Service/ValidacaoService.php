<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MedicamentoRepository;
use PDO;

final class ValidacaoService
{
    private MedicamentoRepository $repo;
    private PDO $pdo;

    public function __construct(MedicamentoRepository $repo, PDO $pdo)
    {
        $this->repo = $repo;
        $this->pdo = $pdo;
    }

    public function validateAndMark(string $id, string $signature): array
    {
        if (!\tcc_verify_identifier_signature($id, $signature)) {
            \tcc_audit_validation($this->pdo, $id, 'assinatura_invalida');
            return ['success' => false, 'status' => 403, 'message' => 'Falsificação detectada: Assinatura inválida.'];
        }

        $row = $this->repo->find($id);
        if ($row === null) {
            \tcc_audit_validation($this->pdo, $id, 'nao_encontrado');
            return ['success' => false, 'status' => 404, 'message' => 'Falsificação detectada: Medicamento não encontrado no sistema.'];
        }

        if (((int)($row['status'] ?? 0)) === 1) {
            \tcc_audit_validation($this->pdo, $id, 'ja_validado');
            return ['success' => false, 'status' => 409, 'message' => 'Falsificação/Clonagem Detectada: Este código já foi validado anteriormente!'];
        }

        $updated = $this->repo->markValidatedAtomic($id);
        if (!$updated) {
            \tcc_audit_validation($this->pdo, $id, 'ja_validado');
            return ['success' => false, 'status' => 409, 'message' => 'Falsificação/Clonagem Detectada: Este código já foi validado anteriormente!'];
        }

        \tcc_audit_validation($this->pdo, $id, 'autentico');
        return ['success' => true, 'status' => 200, 'message' => 'Medicamento Autêntico. Unicidade validada com sucesso!'];
    }
}
