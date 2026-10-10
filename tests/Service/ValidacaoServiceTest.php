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
        $this->assertSame('Assinatura inválida para a chave pública atual. O código pode ter sido adulterado ou emitido por outro fabricante/chave.', $result['message']);
    }

    public function testValidateAndMarkRejectsSignatureFromAnotherKeyPair(): void
    {
        $id = 'foreign-key-case';
        $signature = $this->signWithForeignKey($id);

        $repo = $this->createMock(MedicamentoRepository::class);
        $pdo = $this->createMock(\PDO::class);

        $service = new ValidacaoService($repo, $pdo);

        $result = $service->validateAndMark($id, $signature);

        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['status']);
        $this->assertSame('Assinatura inválida para a chave pública atual. O código pode ter sido adulterado ou emitido por outro fabricante/chave.', $result['message']);
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

    private function signWithForeignKey(string $id): string
    {
        $config = \tcc_openssl_keygen_config();
        $keyResource = \openssl_pkey_new($config);

        $this->assertNotFalse($keyResource, 'Falha ao gerar chave RSA temporária para o teste.');

        $privateKey = '';
        $exported = \openssl_pkey_export($keyResource, $privateKey, 'foreign-test-pass', $config);
        $this->assertTrue($exported, 'Falha ao exportar chave privada temporária do teste.');

        $privateKeyResource = \openssl_pkey_get_private($privateKey, 'foreign-test-pass');
        $this->assertNotFalse($privateKeyResource, 'Falha ao reabrir chave privada temporária do teste.');

        $signature = '';
        $signed = \openssl_sign($id, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        $this->assertTrue($signed, 'Falha ao assinar identificador com chave temporária do teste.');

        return base64_encode($signature);
    }
}
