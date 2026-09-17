<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/crypto.php';

tcc_require_authentication();

$keysExist = tcc_keys_exist();
$csrfToken = tcc_csrf_token();
$username = tcc_authenticated_username();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Fabricante - TCC Medicamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; border: none; }
        .qr-container { display: flex; justify-content: center; align-items: center; padding: 20px; background: white; border-radius: 10px; margin-top: 20px;}
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">⬅ Voltar ao Início</a>
            <div class="d-flex align-items-center gap-3 text-white">
                <span class="navbar-text text-white fw-bold">Módulo do Fabricante</span>
                <span class="small">Usuário: <?= htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8') ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-light">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white p-3">
                        <h5 class="mb-0">Cadastrar Lote de Medicamento</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!$keysExist): ?>
                            <div class="alert alert-warning mb-0">
                                As chaves seguras ainda não foram geradas. Execute <strong>php bin/gerar_chaves.php</strong> no servidor após configurar o arquivo <strong>.env</strong>.
                            </div>
                        <?php else: ?>
                            <form id="medicamentoForm">
                                <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="mb-3">
                                    <label class="form-label">Nome do Medicamento</label>
                                    <input type="text" class="form-control" id="nome" required placeholder="Ex: Omeprazol 20mg">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Número do Lote</label>
                                    <input type="text" class="form-control" id="lote" required placeholder="Ex: LOTE-8902A">
                                </div>
                                <button type="submit" class="btn btn-primary w-100 btn-lg mt-3" id="btn-gerar">
                                    Gerar e Assinar Medicamento
                                </button>
                            </form>
                        <?php endif; ?>

                        <div id="status" class="mt-4"></div>

                        <div id="resultado" style="display:none;" class="mt-4 text-center">
                            <h5>QR Code Seguro Gerado:</h5>
                            <div class="qr-container shadow-sm">
                                <div id="qrcode"></div>
                            </div>
                            <p class="text-muted mt-2 small">Este QR Code contém o ID único e a assinatura digital do laboratório.</p>
                            <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">Gerar Novo Medicamento</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($keysExist): ?>
    <script>
        document.getElementById('medicamentoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-gerar');
            const status = document.getElementById('status');
            const nome = document.getElementById('nome').value.trim();
            const lote = document.getElementById('lote').value.trim();
            const csrfToken = document.getElementById('csrf_token').value;

            btn.disabled = true;
            btn.innerText = 'Gerando assinatura segura...';
            status.innerHTML = '';

            try {
                const response = await fetch('api/salvar_medicamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome, lote, csrf_token: csrfToken })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    status.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                    const id = result.data.id;
                    const signature = result.data.sig;
                    
                    const qrData = JSON.stringify({ id: id, sig: signature });
                    
                    document.getElementById('resultado').style.display = 'block';
                    document.getElementById('medicamentoForm').style.display = 'none';
                    
                    new QRCode(document.getElementById("qrcode"), {
                        text: qrData,
                        width: 256,
                        height: 256,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.L
                    });

                } else {
                    status.innerHTML = `<div class="alert alert-danger">${result.message || 'Não foi possível gerar o medicamento.'}</div>`;
                    btn.disabled = false;
                    btn.innerText = 'Tentar Novamente';
                }
            } catch (err) {
                console.error(err);
                status.innerHTML = `<div class="alert alert-danger">Erro ao comunicar com o servidor: ${err.message}</div>`;
                btn.disabled = false;
                btn.innerText = 'Tentar Novamente';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
