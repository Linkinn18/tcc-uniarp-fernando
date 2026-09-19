Resumo curto das alterações
---------------------------
- Refatoração: webroot em `public/`, código app em `src/` (PSR-4), extração de Services/Repositories.
- Correção crítica: removido conflito de função de assinatura (duplicata entre `crypto.php` e `src/helpers.php`) — centralizado `tcc_sign_identifier()` e renomeadas as funções RSA internas.
- Chaves RSA: suporte de geração via `bin/gerar_chaves.php`; chaves gravadas em `TCC_STORAGE_PATH` (por padrão `/tmp/tcc-storage-linkinn`) e ownership ajustado para o usuário web (daemon).
- API: fix em `/api/salvar_medicamento.php` para retornar `data.sig`; CSRF/session flow ajustado (token precisa ser obtido após login). Debug temporário foi usado e deve ser removido antes do commit final.
- Validador: `public/validador.php` robustecido para aceitar variações do JSON do QR, aceitar `assinatura` como alias e enviar `id`+`sig` ao servidor para validação.
- QR: `public/assets/js/qr.js` mantido em 256×256 por padrão; adicionei `tccQr.downloadQr()` para exportar PNG 512×512; `fabricante.php` e `index.php` exibem botão de download.
- Banco: backup criado e registros gerados removidos conforme pedido; esquema SQLite mantido.

Pontos importantes a ressaltar
----------------------------
- Permissões: o processo Apache (usuário `daemon`) precisa ter leitura/escrita em `TCC_STORAGE_PATH`/`keys` e acesso ao arquivo SQLite. Após gerar chaves com sudo, rodar `chown -R daemon:daemon /tmp/tcc-storage-linkinn`.
- CSRF: ao testar com curl ou scripts, obter o token da página autenticada (ex.: `fabricante.php`) após o POST de login; o token muda pela sessão.
- Assinaturas: o fluxo de verificação depende que o cliente envie a assinatura (`sig`) junto com o `id` para que o servidor reverifique antes de marcar como validado.