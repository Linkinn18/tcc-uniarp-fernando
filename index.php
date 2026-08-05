<?php
$keys_exist = file_exists('keys/public.key') && file_exists('keys/private.key');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCC Medicamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .hero { padding: 80px 0; background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white; border-radius: 0 0 30px 30px; margin-bottom: 40px; }
    </style>
</head>
<body>

<div class="hero text-center shadow">
    <div class="container">
        <h1 class="display-4 fw-bold">Sistema Criptográfico de Medicamentos</h1>
        <p class="lead">Autenticação e Verificação de Unicidade (TCC Fernando Willrich)</p>
    </div>
</div>

<div class="container">
    <?php if (!$keys_exist): ?>
        <div class="alert alert-warning text-center shadow-sm">
            <h4>Atenção!</h4>
            <p>O par de chaves PGP ainda não foi gerado. Antes de testar o sistema, você precisa configurá-lo.</p>
            <a href="gerar_chaves.html" class="btn btn-warning fw-bold">Gerar Chaves PGP Agora</a>
        </div>
    <?php else: ?>
        <ul class="nav nav-pills nav-justified mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-home" type="button">Início</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-search" type="button">Pesquisar Lotes / Medicamentos</button>
            </li>
        </ul>

        <div id="home-panel" class="tab-panel">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <span style="font-size: 3rem;">🏭</span>
                            </div>
                            <h3 class="card-title">Módulo do Fabricante</h3>
                            <p class="card-text text-muted">Acesse a área restrita do laboratório para emitir novos medicamentos e gerar QR Codes com Assinatura Digital PGP.</p>
                            <a href="fabricante.php" class="btn btn-primary btn-lg w-100 mt-3">Acessar Fabricante</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <span style="font-size: 3rem;">📱</span>
                            </div>
                            <h3 class="card-title">Aplicativo Validador</h3>
                            <p class="card-text text-muted">Simulação do aplicativo usado por hospitais e consumidores para ler o QR Code, validar a criptografia e garantir que o código é único.</p>
                            <a href="validador.php" class="btn btn-success btn-lg w-100 mt-3">Abrir Validador</a>
                        </div>
                    </div>
                </div>
            </div>
        </div> 

        <div id="search-panel" class="tab-panel" style="display:none;">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h4 class="card-title">Pesquisar Lotes e Medicamentos</h4>
                    <p class="text-muted">Busque por nome do medicamento e/ou número do lote. Clique em QR para ver o código gerado e o hash correspondente.</p>
                    <form id="searchForm" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label for="searchName" class="form-label">Nome do Medicamento</label>
                            <input type="search" id="searchName" class="form-control" placeholder="Ex: Omeprazol">
                        </div>
                        <div class="col-md-5">
                            <label for="searchLote" class="form-label">Número do Lote</label>
                            <input type="search" id="searchLote" class="form-control" placeholder="Ex: LOTE-8902A">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Pesquisar</button>
                        </div>
                    </form>
                    <div id="searchMessage" class="mt-3"></div>
                </div>
            </div>

            <div id="resultsSection" style="display:none;">
                <div class="table-responsive shadow-sm rounded bg-white">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Lote</th>
                                <th>Data</th>
                                <th>ID</th>
                                <th>Hash SHA-256</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="resultsBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="qrContainer" class="card shadow-sm border-0 mt-4" style="display:none;">
                <div class="card-body">
                    <h5 class="card-title">QR Code do Registro</h5>
                    <div class="qr-container shadow-sm p-4 bg-white rounded" id="qrCodeHolder"></div>
                    <pre id="qrJson" class="mt-3 p-3 bg-light rounded"></pre>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const homeTab = document.getElementById('tab-home');
    const searchTab = document.getElementById('tab-search');
    const homePanel = document.getElementById('home-panel');
    const searchPanel = document.getElementById('search-panel');
    const searchForm = document.getElementById('searchForm');
    const searchName = document.getElementById('searchName');
    const searchLote = document.getElementById('searchLote');
    const searchMessage = document.getElementById('searchMessage');
    const resultsSection = document.getElementById('resultsSection');
    const resultsBody = document.getElementById('resultsBody');
    const qrContainer = document.getElementById('qrContainer');
    const qrCodeHolder = document.getElementById('qrCodeHolder');
    const qrJson = document.getElementById('qrJson');
    let currentQr;

    function setActiveTab(tab) {
        homeTab.classList.toggle('active', tab === 'home');
        searchTab.classList.toggle('active', tab === 'search');
        homePanel.style.display = tab === 'home' ? 'block' : 'none';
        searchPanel.style.display = tab === 'search' ? 'block' : 'none';

        if (tab === 'search') {
            loadResults(searchName.value.trim(), searchLote.value.trim());
        }
    }

    homeTab.addEventListener('click', () => setActiveTab('home'));
    searchTab.addEventListener('click', () => setActiveTab('search'));

    async function loadResults(nome = '', lote = '') {
        const query = new URLSearchParams();
        if (nome) query.set('nome', nome);
        if (lote) query.set('lote', lote);

        searchMessage.innerHTML = '<div class="spinner-border text-primary" role="status"></div> Carregando...';
        resultsSection.style.display = 'none';
        qrContainer.style.display = 'none';

        try {
            const response = await fetch('api/buscar_medicamentos.php?' + query.toString());
            const result = await response.json();

            if (!result.success) {
                searchMessage.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                return;
            }

            const data = result.data || [];
            if (!data.length) {
                searchMessage.innerHTML = '<div class="alert alert-info">Nenhum registro encontrado.</div>';
                resultsBody.innerHTML = '';
                return;
            }

            searchMessage.innerHTML = `<div class="alert alert-success">Encontrados ${data.length} registro(s).</div>`;
            resultsBody.innerHTML = data.map(item => {
                const idLabel = item.id.length > 20 ? item.id.slice(0, 20) + '…' : item.id;
                const hashLabel = item.hash.length > 20 ? item.hash.slice(0, 20) + '…' : item.hash;
                return `
                    <tr>
                        <td>${escapeHtml(item.nome)}</td>
                        <td>${escapeHtml(item.lote)}</td>
                        <td>${escapeHtml(item.data_fabricacao)}</td>
                        <td><code title="${escapeHtml(item.id)}">${escapeHtml(idLabel)}</code></td>
                        <td><code title="${escapeHtml(item.hash)}">${escapeHtml(hashLabel)}</code></td>
                        <td>${escapeHtml(item.status_text)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary show-qr" data-id="${escapeHtml(item.id)}" data-sig="${escapeHtml(item.assinatura)}" data-nome="${escapeHtml(item.nome)}" data-lote="${escapeHtml(item.lote)}">Mostrar QR</button>
                        </td>
                    </tr>
                `;
            }).join('');

            resultsSection.style.display = 'block';
        } catch (error) {
            console.error(error);
            searchMessage.innerHTML = '<div class="alert alert-danger">Erro ao consultar o banco de dados.</div>';
        }
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadResults(searchName.value.trim(), searchLote.value.trim());
    });

    document.addEventListener('click', (event) => {
        if (!event.target.classList.contains('show-qr')) {
            return;
        }

        const button = event.target;
        const id = button.getAttribute('data-id');
        const sig = button.getAttribute('data-sig');
        const nome = button.getAttribute('data-nome');
        const lote = button.getAttribute('data-lote');

        qrContainer.style.display = 'block';
        qrCodeHolder.innerHTML = '';
        qrJson.textContent = JSON.stringify({ id, assinatura: sig, nome, lote }, null, 2);

        if (currentQr) {
            currentQr.clear();
        }

        currentQr = new QRCode(qrCodeHolder, {
            text: JSON.stringify({ id, sig }),
            width: 256,
            height: 256,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.L
        });
    });
</script>
</body>
</html>
