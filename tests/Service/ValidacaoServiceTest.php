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
}
