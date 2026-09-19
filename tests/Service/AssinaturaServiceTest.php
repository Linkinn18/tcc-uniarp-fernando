<?php
declare(strict_types=1);

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\AssinaturaService;
use App\Repository\MedicamentoRepository;

class AssinaturaServiceTest extends TestCase
{
    public function testCreateAndStore(): void
    {
        $repo = $this->createMock(MedicamentoRepository::class);
        $repo->expects($this->once())->method('insert');

        $service = new AssinaturaService($repo);
        $result = $service->createAndStore('Nome', 'LOTE-1');

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('sig', $result);
        $this->assertIsString($result['id']);
        $this->assertIsString($result['sig']);
    }
}
