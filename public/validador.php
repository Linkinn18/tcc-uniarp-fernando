<?php
declare(strict_types=1);

require_once __DIR__ . '/../crypto.php';
require_once __DIR__ . '/../auth.php';

$isAuthenticated = tcc_is_authenticated();
$username = tcc_authenticated_username();

$publicKeyStr = '';
$keyError = '';

try {
    $publicKeyStr = tcc_get_public_key();
} catch (RuntimeException $exception) {
    $keyError = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validador de Medicamentos - TCC</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <script src="assets/vendor/html5-qrcode/html5-qrcode.min.js" type="text/javascript"></script>
    <style>
        .result-box { display: none; padding: 20px; border-radius: 10px; text-align: center; margin-top: 20px;}
        .result-box.success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .result-box.danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .sidebar-user-card { padding: 16px; border: 1px solid #30414b; border-radius: 12px; background: linear-gradient(180deg, rgba(142, 214, 202, .18) 0%, rgba(22, 35, 45, .18) 100%); color: #dce5ea; }
        .sidebar-user-label { margin: 0 0 6px; color: #8ed6ca; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .sidebar-user-name { margin: 0; font-size: 1rem; font-weight: 700; word-break: break-word; }
        .sidebar-user-help { margin: 8px 0 0; color: #aebbc4; font-size: .8rem; line-height: 1.5; }
        .sidebar-user-card .link-light { color: #fff !important; font-weight: 600; }
    </style>
</head>
<body class="app-page">
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="brand-mark">
            <div class="brand-symbol">T</div>
            <div>
                <p class="brand-title">TCC Medicamentos</p>
                <p class="brand-subtitle">Autenticidade e unicidade</p>
            </div>
        </div>
        <p class="sidebar-label">Módulos</p>
        <nav class="sidebar-nav" aria-label="Navegação principal">
            <a class="sidebar-link" href="index.php"><span class="sidebar-icon">⌂</span><span>Início</span></a>
            <a class="sidebar-link" href="fabricante.php"><span class="sidebar-icon">＋</span><span>Cadastrar Lote</span></a>
            <a class="sidebar-link" href="index.php?tab=search"><span class="sidebar-icon">⌕</span><span>Pesquisar</span></a>
            <a class="sidebar-link active" href="validador.php"><span class="sidebar-icon">✓</span><span>Validar Lotes</span></a>
            <?php if ($isAuthenticated): ?>
                <a class="sidebar-link" href="logout.php"><span class="sidebar-icon">↪</span><span>Sair</span></a>
            <?php else: ?>
                <a class="sidebar-link" href="login.php"><span class="sidebar-icon">→</span><span>Login</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <?php if ($isAuthenticated): ?>
                <div class="sidebar-user-card">
                    <p class="sidebar-user-label">Fabricante conectado</p>
                    <p class="sidebar-user-name"><?= htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="sidebar-user-help">Use esta área para validar lotes e conferir a autenticidade dos QR Codes.</p>
                    <a class="link-light text-decoration-none" href="logout.php">Sair</a>
                </div>
            <?php else: ?>
                <div class="sidebar-user-card">
                    <p class="sidebar-user-label">Acesso</p>
                    <p class="sidebar-user-name">Visitante</p>
                    <p class="sidebar-user-help">Valide um QR Code a partir de uma imagem PNG ou JPG.</p>
                    <a class="link-light text-decoration-none" href="login.php">Ir para login</a>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <main class="app-main">
        <header class="page-header">
            <div>
                <p class="eyebrow">Verificação</p>
                <h1 class="page-title">Validar lotes</h1>
                <p class="page-intro">Envie a imagem do QR Code para verificar a assinatura e a unicidade do lote.</p>
            </div>
        </header>

    <div class="module-panel">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <h4>Verificação de Unicidade</h4>
                    <p class="text-muted">Selecione a imagem PNG ou JPG do QR Code para validar.</p>
                </div>

                <?php if ($keyError !== ''): ?>
                    <div class="alert alert-warning shadow-sm"><?= htmlspecialchars($keyError, ENT_QUOTES, 'UTF-8') ?> Execute <strong>php bin/gerar_chaves.php</strong> no servidor.</div>
                <?php else: ?>

                <div class="mb-3">
                    <label for="qrFileInput" class="form-label">Imagem do QR Code</label>
                    <div class="input-group">
                        <input type="file" id="qrFileInput" accept="image/png,image/jpeg" class="form-control" />
                        <button type="button" id="btnValidateFile" class="btn btn-success">Validar imagem</button>
                    </div>
                    <div class="form-text">Use o PNG original baixado ou uma imagem nítida, completa e sem recortes.</div>
                </div>

                <div id="loading" class="text-center" style="display:none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted fw-bold">Validando assinatura digital...</p>
                </div>

                <div id="result-container" class="result-box shadow-sm">
                    <h3 id="result-title"></h3>
                    <p id="result-msg" class="mb-0"></p>
                    <button class="btn btn-outline-dark mt-3 btn-sm" type="button" onclick="resetFileSelection()">Selecionar outra imagem</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </main>
</div>

    <div id="qr-file-reader" style="display:none;"></div>

    <?php if ($keyError === ''): ?>
    <script src="assets/js/dom.js"></script>
    <script src="assets/js/api.js"></script>
    <script>
        const PUBLIC_KEY_PEM = `<?php echo $publicKeyStr; ?>`;
        let html5QrcodeFileScanner;
        let isProcessing = false;
        let cachedPublicKey;

        function pemToArrayBuffer(pem) {
            const base64 = pem.replace('-----BEGIN PUBLIC KEY-----', '')
                .replace('-----END PUBLIC KEY-----', '')
                .replace(/\s+/g, '');
            const binary = atob(base64);
            const bytes = new Uint8Array(binary.length);

            for (let index = 0; index < binary.length; index += 1) {
                bytes[index] = binary.charCodeAt(index);
            }

            return bytes.buffer;
        }

        function base64ToArrayBuffer(base64) {
            base64 = normalizeQrSignature(base64);
            const binary = atob(base64);
            const bytes = new Uint8Array(binary.length);

            for (let index = 0; index < binary.length; index += 1) {
                bytes[index] = binary.charCodeAt(index);
            }

            return bytes.buffer;
        }

        function normalizeQrSignature(signature) {
            const trimmed = String(signature || '').trim();
            if (!trimmed) {
                throw new Error('Assinatura ausente no QR Code.');
            }

            const normalized = trimmed.replace(/-/g, '+').replace(/_/g, '/');
            const padding = normalized.length % 4;
            return padding === 0 ? normalized : normalized + '='.repeat(4 - padding);
        }

        async function getPublicKey() {
            if (!window.crypto || !window.crypto.subtle) {
                throw new Error('O navegador não suporta a API Web Crypto necessária para validar a assinatura.');
            }

            if (!cachedPublicKey) {
                cachedPublicKey = await window.crypto.subtle.importKey(
                    'spki',
                    pemToArrayBuffer(PUBLIC_KEY_PEM),
                    { name: 'RSASSA-PKCS1-v1_5', hash: 'SHA-256' },
                    false,
                    ['verify']
                );
            }

            return cachedPublicKey;
        }

        function extractPayloadFromObject(data) {
            const id = data.id ?? (data.data && data.data.id) ?? (data.payload && data.payload.id) ?? data.identifier ?? data.i ?? null;
            const sig = data.sig ?? (data.data && data.data.sig) ?? (data.payload && data.payload.sig) ?? data.signature ?? data.assinatura ?? data.s ?? null;

            if (!id || !sig) {
                return null;
            }

            return { id, sig };
        }

        function parseQrPayload(rawText) {
            try {
                const parsedJson = JSON.parse(rawText);
                const payloadFromJson = extractPayloadFromObject(parsedJson);
                if (payloadFromJson) {
                    return payloadFromJson;
                }
            } catch (error) {
            }

            try {
                const url = new URL(rawText, window.location.href);
                const id = url.searchParams.get('i') || url.searchParams.get('id');
                const sig = url.searchParams.get('s') || url.searchParams.get('sig') || url.searchParams.get('signature');
                if (id && sig) {
                    return { id, sig };
                }
            } catch (error) {
            }

            const queryString = rawText.startsWith('?') ? rawText.slice(1) : rawText;
            const params = new URLSearchParams(queryString);
            const id = params.get('i') || params.get('id');
            const sig = params.get('s') || params.get('sig') || params.get('signature');

            if (id && sig) {
                return { id, sig };
            }

            throw new Error('Erro ao interpretar o QR Code. Formato não suportado.');
        }

        async function handleDecodedText(decodedText) {
            console.log('Texto decodificado do QR:', decodedText);

            // html5-qrcode can return either a string or an object with several shapes.
            // Normalize to a raw JSON string first.
            let rawText = decodedText;
            if (typeof decodedText === 'object' && decodedText !== null) {
                rawText = decodedText.decodedText ?? decodedText.text ?? (decodedText.result && decodedText.result.text) ?? JSON.stringify(decodedText);
            }
            let data;
            try {
                data = parseQrPayload(rawText);
                data.sig = normalizeQrSignature(data.sig);
            } catch (error) {
                console.error('Erro ao interpretar payload do QR:', error, 'Texto:', rawText);
                throw error;
            }

            if (window.crypto && window.crypto.subtle && typeof window.crypto.subtle.verify === 'function') {
                const verificationKey = await getPublicKey();
                const valid = await window.crypto.subtle.verify(
                    { name: 'RSASSA-PKCS1-v1_5' },
                    verificationKey,
                    base64ToArrayBuffer(data.sig),
                    new TextEncoder().encode(data.id)
                );

                if (!valid) {
                    throw new Error('Assinatura inválida. O medicamento não foi gerado por este sistema.');
                }
            }

            const response = await tccApi.postJson('api/validar_unicidade.php', { id: data.id, sig: data.sig });
            const result = response.json || { success: false, message: 'Resposta inválida' };
            if (result.success) {
                showResult('success', 'Medicamento Autêntico', result.message);
            } else {
                showResult('danger', 'ALERTA DE CLONAGEM', result.message);
            }
        }

        function showResult(type, title, message) {
            const container = document.getElementById('result-container');
            container.className = `result-box ${type}`;
            document.getElementById('result-title').innerText = title;
            document.getElementById('result-msg').innerText = message;
            container.style.display = 'block';
        }

        function resetFileSelection() {
            document.getElementById('qrFileInput').value = '';
            document.getElementById('result-container').style.display = 'none';
            document.getElementById('loading').style.display = 'none';
            document.getElementById('qrFileInput').focus();
        }

        async function validateFileImage() {
            if (isProcessing) return;
            const fileInput = document.getElementById('qrFileInput');
            const file = fileInput.files[0];

            if (!file) {
                showResult('danger', 'Arquivo não selecionado', 'Selecione uma imagem PNG ou JPG contendo o QR Code.');
                return;
            }

            isProcessing = true;
            document.getElementById('loading').style.display = 'block';
            document.getElementById('result-container').style.display = 'none';

            let validationStage = 'decode';
            try {
                if (!html5QrcodeFileScanner) {
                    html5QrcodeFileScanner = new Html5Qrcode('qr-file-reader');
                }

                const decodedText = await html5QrcodeFileScanner.scanFileV2(file, false);
                validationStage = 'validate';
                console.log('Texto decodificado da imagem:', decodedText);
                await handleDecodedText(decodedText);
            } catch (err) {
                console.error(err);
                if (validationStage === 'decode') {
                    const message = err.message && err.message.includes('No MultiFormat Readers were able to detect the code.')
                        ? 'Nenhum QR Code legível foi encontrado. Selecione o PNG original completo, sem recortar, redimensionar ou comprimir a imagem. Gere um novo arquivo se ele foi baixado antes desta correção.'
                        : err.message || 'Não foi possível decodificar o QR Code da imagem.';
                    showResult('danger', 'QR Code não reconhecido', message);
                } else if (err instanceof TypeError) {
                    showResult('danger', 'Falha de conexão', 'Não foi possível contactar o servidor de validação. Verifique a conexão e tente novamente.');
                } else if (err.message && err.message.includes('Assinatura inválida')) {
                    showResult('danger', 'Assinatura inválida', err.message);
                } else {
                    showResult('danger', 'Falha na validação', err.message || 'Não foi possível validar este QR Code.');
                }
            } finally {
                document.getElementById('loading').style.display = 'none';
                isProcessing = false;
                if (html5QrcodeFileScanner) {
                    try {
                        const clearResult = html5QrcodeFileScanner.clear && html5QrcodeFileScanner.clear();
                        if (clearResult && typeof clearResult.then === 'function') {
                            clearResult.catch(() => {});
                        }
                    } catch (clearErr) {
                        console.warn('Erro ao limpar file scanner:', clearErr);
                    }
                    html5QrcodeFileScanner = null;
                }
            }
        }

        document.getElementById('btnValidateFile').addEventListener('click', validateFileImage);

        window.addEventListener('load', async () => {
            const query = new URLSearchParams(window.location.search);
            const initialId = query.get('i') || query.get('id');
            const initialSig = query.get('s') || query.get('sig');

            if (initialId && initialSig) {
                document.getElementById('loading').style.display = 'block';
                try {
                    await handleDecodedText(`i=${encodeURIComponent(initialId)}&s=${encodeURIComponent(initialSig)}`);
                } catch (err) {
                    console.error(err);
                    showResult('danger', 'Falha na validação automática', err.message || 'Não foi possível validar o payload da URL.');
                } finally {
                    document.getElementById('loading').style.display = 'none';
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
