<?php
declare(strict_types=1);

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\ValidacaoService;
use App\Repository\MedicamentoRepository;

class ValidacaoServiceTest extends TestCase
{
    public function testValidateAndMarkInvalidSignature(): void
    {
        $repo = $this->createMock(MedicamentoRepository::class);
        $pdo = $this->createMock(\PDO::class);

        $service = new ValidacaoService($repo, $pdo);

        $result = $service->validateAndMark('some-id', 'invalid-sig');

        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['status']);
    }

    public function testValidateAndMarkAcceptsUrlSafeSignature(): void
    {
        $id = 'some-id';
        $signature = \tcc_signature_to_qr_value(\tcc_sign_identifier($id));

        $repo = $this->createMock(MedicamentoRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(['id' => $id, 'status' => 0]);
        $repo->expects($this->once())
            ->method('markValidatedAtomic')
            ->with($id)
            ->willReturn(true);

        $pdo = $this->createMock(\PDO::class);

        $service = new ValidacaoService($repo, $pdo);

        $result = $service->validateAndMark($id, $signature);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
    }
}
