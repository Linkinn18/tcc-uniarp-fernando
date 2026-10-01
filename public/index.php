<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../bootstrap.php';

$keys_exist = tcc_keys_exist();
$isAuthenticated = tcc_is_authenticated();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROJETO</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <style>
        :root {
            --ink: #17212b;
            --muted: #6b7785;
            --line: #dce3e8;
            --surface: #ffffff;
            --wash: #f2f5f7;
            --accent: #0f766e;
            --accent-soft: #dff3ef;
        }

        body { min-height: 100vh; background: var(--wash); color: var(--ink); }
        .app-shell { display: flex; min-height: 100vh; }
        .app-sidebar { width: 264px; flex: 0 0 264px; padding: 28px 18px; background: #16232d; color: #fff; }
        .brand-mark { display: flex; align-items: center; gap: 12px; margin: 0 10px 38px; }
        .brand-symbol { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 10px; background: var(--accent); font-weight: 700; }
        .brand-title { margin: 0; font-size: 1rem; letter-spacing: .02em; }
        .brand-subtitle { margin: 3px 0 0; color: #aebbc4; font-size: .76rem; }
        .sidebar-label { margin: 0 12px 10px; color: #82909a; font-size: .7rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .sidebar-nav { display: grid; gap: 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 11px; width: 100%; padding: 12px; border: 0; border-radius: 8px; color: #dce5ea; background: transparent; text-align: left; text-decoration: none; }
        .sidebar-link:hover, .sidebar-link.active { color: #fff; background: #263943; }
        .sidebar-icon { width: 20px; color: #8ed6ca; text-align: center; }
        .sidebar-footer { margin: 42px 10px 0; padding-top: 18px; border-top: 1px solid #30414b; color: #aebbc4; font-size: .78rem; line-height: 1.5; }
        .app-main { flex: 1; min-width: 0; padding: 34px clamp(20px, 5vw, 64px); }
        .page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 28px; }
        .eyebrow { margin: 0 0 7px; color: var(--accent); font-size: .76rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .page-title { margin: 0; font-size: clamp(1.7rem, 3vw, 2.45rem); letter-spacing: -.02em; }
        .page-intro { max-width: 620px; margin: 8px 0 0; color: var(--muted); }
        .content-panel { padding: clamp(22px, 4vw, 38px); border: 1px solid var(--line); border-radius: 12px; background: var(--surface); box-shadow: 0 12px 30px rgba(22, 35, 45, .06); }
        .welcome-panel { min-height: 330px; display: grid; align-content: center; }
        .welcome-panel h2 { max-width: 620px; margin-bottom: 12px; font-size: clamp(1.5rem, 3vw, 2.2rem); }
        .welcome-panel p { max-width: 650px; color: var(--muted); }
        .status-note { display: flex; gap: 10px; align-items: flex-start; margin-top: 26px; padding: 14px 16px; border-left: 3px solid #d59b27; background: #fff8e7; color: #6c511a; }
        .search-panel { display: none; }
        .search-panel.is-visible { display: block; }
        .search-heading { margin-bottom: 22px; }
        .search-heading h2 { margin-bottom: 6px; font-size: 1.35rem; }
        .search-heading p { margin: 0; color: var(--muted); }
        .table-wrap { overflow-x: auto; }
        .qr-container { min-height: 280px; display: grid; place-items: center; border: 1px solid var(--line); }
        @media (max-width: 760px) {
            .app-shell { display: block; }
            .app-sidebar { width: auto; padding: 18px 16px; }
            .brand-mark { margin-bottom: 20px; }
            .sidebar-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .sidebar-footer { display: none; }
            .app-main { padding: 26px 16px; }
            .page-header { display: block; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="brand-mark">
            <div class="brand-symbol">T</div>
            <div>
                <p class="brand-title">Projeto TCC</p>
            </div>
        </div>
        <p class="sidebar-label">Módulos</p>
        <nav class="sidebar-nav" aria-label="Navegação principal">
            <button class="sidebar-link active" id="tab-home" type="button"><span class="sidebar-icon">⌂</span><span>Início</span></button>
            <a class="sidebar-link" href="fabricante.php"><span class="sidebar-icon">＋</span><span>Cadastrar Lote</span></a>
            <button class="sidebar-link" id="tab-search" type="button"><span class="sidebar-icon">⌕</span><span>Pesquisar</span></button>
            <a class="sidebar-link" href="validador.php"><span class="sidebar-icon">✓</span><span>Validar Lotes</span></a>
            <?php if ($isAuthenticated): ?>
                <a class="sidebar-link" href="logout.php"><span class="sidebar-icon">↪</span><span>Sair</span></a>
            <?php else: ?>
                <a class="sidebar-link" href="login.php"><span class="sidebar-icon">→</span><span>Login</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <?php if ($isAuthenticated): ?>
                Fabricante autenticado.
            <?php else: ?>
                A emissão de lotes requer autenticação.
            <?php endif; ?>
        </div>
    </aside>

    <main class="app-main">
        <!--
        <header class="page-header">
            <div>
                <p class="eyebrow">Painel operacional</p>
                <h1 class="page-title">Sistema de medicamentos</h1>
                <p class="page-intro">Escolha uma operação no menu lateral para cadastrar, pesquisar ou validar lotes.</p>
            </div>
        </header>
        -->
        <?php if (!$keys_exist): ?>
            <div class="status-note" role="status">
                <strong>Atenção:</strong>
                <span>O par de chaves seguro ainda não foi gerado. Configure o arquivo <strong>.env</strong> e execute o script de linha de comando.</span>
            </div>
        <?php endif; ?>

        <section id="home-panel" class="content-panel welcome-panel" aria-labelledby="home-title">
            <p class="eyebrow">Visão geral</p>
        </section>

        <section id="search-panel" class="content-panel search-panel" aria-labelledby="search-title">
            <div class="search-heading">
                <p class="eyebrow">Consulta</p>
                <h2 id="search-title">Pesquisar lotes gerados</h2>
                <p>Busque por nome do medicamento e/ou número do lote. A pesquisa exige autenticação.</p>
            </div>
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
                    <button type="submit" class="btn btn-success">Pesquisar</button>
                </div>
            </form>
            <div id="searchMessage" class="mt-3"></div>

            <div id="resultsSection" style="display:none;">
                <div class="table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Lote</th>
                                <th>Cadastro</th>
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

            <div id="qrContainer" class="mt-4" style="display:none;">
                <h3 class="h5">QR Code do Registro</h3>
                <div class="qr-container p-4 bg-white rounded" id="qrCodeHolder"></div>
                <div class="d-flex gap-2 mt-3">
                    <button id="btn-download-qr" class="btn btn-sm btn-outline-success">Baixar QR (PNG)</button>
                </div>
                <pre id="qrJson" class="mt-3 p-3 bg-light rounded"></pre>
            </div>
        </section>
    </main>
</div>

<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/qrcodejs/qrcode.min.js"></script>
<script src="assets/js/dom.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/qr.js"></script>
<script>
(function () {
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

    if (homeTab && searchTab && homePanel && searchPanel && searchForm) {

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

        const initialTab = new URLSearchParams(window.location.search).get('tab');
        setActiveTab(initialTab === 'search' ? 'search' : 'home');

        async function loadResults(nome = '', lote = '') {
            const query = new URLSearchParams();
            if (nome) query.set('nome', nome);
            if (lote) query.set('lote', lote);

            searchMessage.innerHTML = '<div class="spinner-border text-primary" role="status"></div> Carregando...';
            resultsSection.style.display = 'none';
            qrContainer.style.display = 'none';

            try {
                const response = await tccApi.fetchJson('api/buscar_medicamentos.php?' + query.toString());
                const result = response.json || { success: false, message: 'Resposta inválida' };

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
                    const hashLabel = (item.hash || '').length > 20 ? (item.hash || '').slice(0, 20) + '…' : (item.hash || '');
                    return `
                        <tr>
                            <td>${tccDom.escapeHtml(item.nome)}</td>
                            <td>${tccDom.escapeHtml(item.lote)}</td>
                            <td>${tccDom.escapeHtml(item.criado_em)}</td>
                            <td><code title="${tccDom.escapeHtml(item.id)}">${tccDom.escapeHtml(idLabel)}</code></td>
                            <td><code title="${tccDom.escapeHtml(item.hash || '')}">${tccDom.escapeHtml(hashLabel)}</code></td>
                            <td>${tccDom.escapeHtml(item.status_text)}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary show-qr" data-id="${tccDom.escapeHtml(item.id)}" data-sig="${tccDom.escapeHtml(item.assinatura ?? '')}" data-nome="${tccDom.escapeHtml(item.nome)}" data-lote="${tccDom.escapeHtml(item.lote)}">Mostrar QR</button>
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

            document.addEventListener('click', (event) => {
            if (!event.target.classList.contains('show-qr')) {
                return;
            }

            const button = event.target;
            const id = button.getAttribute('data-id');
            const sig = button.getAttribute('data-sig');
            const nome = button.getAttribute('data-nome');
            const lote = button.getAttribute('data-lote');
            const qrPayload = tccQr.buildPayload({ id, sig });

            qrContainer.style.display = 'block';
            qrCodeHolder.innerHTML = '';
            qrJson.textContent = qrPayload;

            if (currentQr) {
                try { currentQr.clear(); } catch(e) {}
            }

            currentQr = tccQr.renderQr(qrCodeHolder, { id, sig });
            // remember last data for download
            window._lastTccQrData = { id, sig };
            const dlBtn = document.getElementById('btn-download-qr');
            if (dlBtn) {
                dlBtn.onclick = () => {
                    const filename = `tcc-qr-${id}.png`;
                    tccQr.downloadQr(qrCodeHolder, { id, sig }, filename, 1024).catch(err => console.error(err));
                };
            }
        });

        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            loadResults(searchName.value.trim(), searchLote.value.trim());
        });
    }
})();
</script>
</body>
</html>
