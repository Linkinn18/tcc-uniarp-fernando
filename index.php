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
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
