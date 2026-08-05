<?php
$publicKeyStr = @file_get_contents('keys/public.key');
if (!$publicKeyStr) {
    die("Chave pública não encontrada. Por favor, gere as chaves primeiro.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validador de Medicamentos - TCC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/openpgp/5.10.1/openpgp.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { background-color: #f0f2f5; }
        .scanner-box { border: 2px dashed #198754; border-radius: 15px; overflow: hidden; background: #fff;}
        #reader { width: 100%; min-height: 300px; }
        .result-box { display: none; padding: 20px; border-radius: 10px; text-align: center; margin-top: 20px;}
        .result-box.success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .result-box.danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">⬅ Voltar</a>
            <span class="navbar-text text-white fw-bold">Validador Descentralizado</span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <h4>Verificação de Unicidade</h4>
                    <p class="text-muted">Aponte a câmera para o QR Code da embalagem do medicamento.</p>
                </div>

                <div class="scanner-box shadow-sm mb-4">
                    <div id="reader"></div>
                </div>

                <div id="loading" class="text-center" style="display:none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted fw-bold">Analisando Criptografia...</p>
                </div>

                <div id="result-container" class="result-box shadow-sm">
                    <h3 id="result-title"></h3>
                    <p id="result-msg" class="mb-0"></p>
                    <button class="btn btn-outline-dark mt-3 btn-sm" onclick="startScanner()">Escanear Outro</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const PUBLIC_KEY_ARMORED = `<?php echo $publicKeyStr; ?>`;
        let html5QrcodeScanner;
        let isProcessing = false;

        async function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return;
            isProcessing = true;
            
            // Stop scanning visually
            html5QrcodeScanner.pause(true);
            document.getElementById('loading').style.display = 'block';
            document.getElementById('result-container').style.display = 'none';

            try {
                // Parse QR Code JSON
                const data = JSON.parse(decodedText);
                
                if (!data.id || !data.sig) {
                    throw new Error("Formato do QR Code inválido ou não pertencente ao sistema.");
                }

                // 1. Verificação Criptográfica (Descentralizada - no Front-End)
                const publicKey = await openpgp.readKey({ armoredKey: PUBLIC_KEY_ARMORED });
                const message = await openpgp.createMessage({ text: data.id });
                const signature = await openpgp.readSignature({ armoredSignature: data.sig });

                const verificationResult = await openpgp.verify({
                    message: message,
                    signature: signature,
                    verificationKeys: publicKey
                });

                const { verified, keyID } = verificationResult.signatures[0];
                
                try {
                    await verified; // throws on invalid signature
                } catch (e) {
                    throw new Error("Assinatura PGP Inválida! O medicamento não foi gerado pelo laboratório legítimo.");
                }

                // 2. Prova de Unicidade (Requisição ao Back-End)
                const response = await fetch('api/validar_unicidade.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: data.id })
                });

                const result = await response.json();

                if (result.success) {
                    showResult('success', '✅ Medicamento Autêntico', result.message);
                } else {
                    // Clonado ou status = 1
                    showResult('danger', '❌ ALERTA DE CLONAGEM', result.message);
                }

            } catch (err) {
                console.error(err);
                showResult('danger', '❌ Falsificação Detectada', err.message || "Não foi possível validar o código.");
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        function onScanFailure(error) {
            // handle scan failure, usually better to ignore and keep scanning
        }

        function showResult(type, title, message) {
            const container = document.getElementById('result-container');
            container.className = `result-box ${type}`;
            document.getElementById('result-title').innerText = title;
            document.getElementById('result-msg').innerText = message;
            container.style.display = 'block';
        }

        function startScanner() {
            isProcessing = false;
            document.getElementById('result-container').style.display = 'none';
            document.getElementById('loading').style.display = 'none';
            
            if (html5QrcodeScanner) {
                html5QrcodeScanner.resume();
            } else {
                html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader",
                    { fps: 10, qrbox: {width: 250, height: 250} },
                    /* verbose= */ false);
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            }
        }

        // Start scanner on load
        window.addEventListener('load', startScanner);
    </script>
</body>
</html>
