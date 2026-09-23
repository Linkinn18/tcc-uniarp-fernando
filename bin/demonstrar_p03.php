<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../crypto.php';

const TCC_DEMO_HOST = '127.0.0.1';
const TCC_DEMO_PORT = 8765;

function tcc_demo_wait_for_server(string $url, int $timeoutSeconds = 10): void
{
	$start = time();

	while ((time() - $start) < $timeoutSeconds) {
		$handle = curl_init($url);
		curl_setopt_array($handle, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_TIMEOUT => 1,
		]);

		curl_exec($handle);
		$statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		curl_close($handle);

		if ($statusCode > 0) {
			return;
		}

		usleep(200000);
	}

	throw new RuntimeException('O servidor temporário não respondeu dentro do tempo esperado.');
}

function tcc_demo_start_server(): array
{
	$command = sprintf(
		'PHP_CLI_SERVER_WORKERS=4 %s -S %s:%d -t %s',
		escapeshellarg('/opt/lampp/bin/php'),
		TCC_DEMO_HOST,
		TCC_DEMO_PORT,
		escapeshellarg(dirname(__DIR__))
	);

	$descriptorSpec = [
		0 => ['pipe', 'r'],
		1 => ['file', sys_get_temp_dir() . '/tcc-demo-p03.stdout.log', 'a'],
		2 => ['file', sys_get_temp_dir() . '/tcc-demo-p03.stderr.log', 'a'],
	];

	$process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__));
	if (!is_resource($process)) {
		throw new RuntimeException('Não foi possível iniciar o servidor temporário para a demonstração.');
	}

	fclose($pipes[0]);

	$baseUrl = sprintf('http://%s:%d', TCC_DEMO_HOST, TCC_DEMO_PORT);
	tcc_demo_wait_for_server($baseUrl . '/api/validar_unicidade.php');

	return [$process, $baseUrl];
}

function tcc_demo_stop_server($process): void
{
	if (is_resource($process)) {
		proc_terminate($process);
		proc_close($process);
	}
}

function tcc_demo_seed_medicamento(PDO $pdo): array
{
	$id = tcc_generate_uuid_v4();
	$signature = tcc_sign_identifier($id);
	$now = date('Y-m-d H:i:s');

	$statement = $pdo->prepare('INSERT INTO medicamentos (id, nome, lote, criado_em, assinatura, status) VALUES (?, ?, ?, ?, ?, 0)');
	$statement->execute([$id, 'Medicamento Teste Concorrência', 'LOTE-P03', $now, $signature]);

	return ['id' => $id, 'sig' => $signature];
}

function tcc_demo_cleanup(PDO $pdo, string $id): void
{
	$statement = $pdo->prepare('DELETE FROM medicamentos WHERE id = ?');
	$statement->execute([$id]);
}

function tcc_demo_create_handle(string $url, array $payload)
{
	$handle = curl_init($url);
	curl_setopt_array($handle, [
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		CURLOPT_TIMEOUT => 10,
	]);

	return $handle;
}

function tcc_demo_run_parallel_validation(string $baseUrl, array $payload): array
{
	$url = $baseUrl . '/api/validar_unicidade.php';
	$multi = curl_multi_init();

	$first = tcc_demo_create_handle($url, $payload);
	$second = tcc_demo_create_handle($url, $payload);

	curl_multi_add_handle($multi, $first);
	curl_multi_add_handle($multi, $second);

	do {
		$status = curl_multi_exec($multi, $running);
		if ($running) {
			curl_multi_select($multi, 1.0);
		}
	} while ($running && $status === CURLM_OK);

	$results = [];
	foreach ([$first, $second] as $index => $handle) {
		$results[] = [
			'request' => $index + 1,
			'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
			'body' => json_decode((string) curl_multi_getcontent($handle), true),
		];
		curl_multi_remove_handle($multi, $handle);
		curl_close($handle);
	}

	curl_multi_close($multi);

	return $results;
}

try {
	if (!tcc_keys_exist()) {
		throw new RuntimeException('As chaves ainda não foram geradas. Gere-as antes de executar a demonstração do P03.');
	}

	[$serverProcess, $baseUrl] = tcc_demo_start_server();
	$payload = tcc_demo_seed_medicamento($pdo);

	try {
		$results = tcc_demo_run_parallel_validation($baseUrl, $payload);
	} finally {
		tcc_demo_cleanup($pdo, $payload['id']);
		tcc_demo_stop_server($serverProcess);
	}

	$successCount = 0;
	foreach ($results as $result) {
		$body = is_array($result['body']) ? $result['body'] : ['success' => false, 'message' => 'Resposta inválida'];
		if (!empty($body['success'])) {
			$successCount += 1;
		}

		fwrite(STDOUT, sprintf(
			"Requisição %d -> HTTP %d | success=%s | mensagem=%s\n",
			$result['request'],
			$result['status'],
			!empty($body['success']) ? 'true' : 'false',
			$body['message'] ?? 'sem mensagem'
		));
	}

	if ($successCount === 1) {
		fwrite(STDOUT, "Resultado esperado: exatamente uma validação venceu a corrida. P03 mitigado.\n");
		exit(0);
	}

	fwrite(STDERR, "Resultado inesperado: o teste não produziu exatamente um sucesso.\n");
	exit(1);
} catch (Throwable $exception) {
	fwrite(STDERR, $exception->getMessage() . "\n");
	exit(1);
}
