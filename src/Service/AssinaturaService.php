<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MedicamentoRepository;

final class AssinaturaService
{
    private MedicamentoRepository $repo;

    public function __construct(MedicamentoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createAndStore(string $nome, string $lote, ?string $fabricanteUsername): array
    {
        $id = \tcc_generate_uuid_v4();
        $assinatura = \tcc_sign_identifier($id);
        $criadoEm = date('Y-m-d H:i:s');

        $this->repo->insert($id, $nome, $lote, $criadoEm, $assinatura, $fabricanteUsername);

        return ['id' => $id, 'sig' => $assinatura];
    }
}
