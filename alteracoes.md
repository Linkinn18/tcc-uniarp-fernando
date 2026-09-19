Resumo das alterações (branch atual)
=================================

Este documento resume, em formato pronto para commit, todas as mudanças realizadas nesta branch do repositório.

Resumo rápido
-------------
- Refatoração do projeto para organizar o webroot em `public/` e código aplicacional em `src/` (PSR-4).
- Extração de Services e Repositories (ex.: `AssinaturaService`, `MedicamentoRepository`, `ValidacaoService`).
- Consolidação de assets JS/CSS em `public/assets/js` e pequenas melhorias front-end.
- Configuração e testes para servir o site via XAMPP Apache em `*:8080` com DocumentRoot em `/opt/lampp/htdocs/tcc/public`.
- Geração e gerenciamento de chaves RSA no caminho definido por `TCC_STORAGE_PATH` (por padrão `/tmp/tcc-storage-linkinn`).
- Correções de bugs críticos e melhorias de segurança/robustez (CSRF, permissões, tratamento de assinaturas).

Detalhamento das mudanças principais
----------------------------------

1) Estrutura e tooling
- Web root movido para `public/`.
- Código PHP organizado em `src/` com autoload Composer (PSR-4).
- Ferramentas de desenvolvimento: Composer, testes e linting configurados durante o refactor.

2) Servidor e ambiente
- Apache XAMPP configurado para servir `public/` em `http://localhost:8080/` (ajustes em `/opt/lampp/etc/httpd.conf`).
- Variáveis de ambiente e storage:
  - `.env` contendo `TCC_STORAGE_PATH=/tmp/tcc-storage-linkinn`, `TCC_KEY_PASSPHRASE`, e credenciais de admin.
  - Criação das pastas `keys/` e `sessions/` sob o storage root com permissão/ownership apropriada para o usuário do Apache (`daemon`).

3) Criação e gerenciamento de chaves
- Script `bin/gerar_chaves.php` utilizado para gerar par RSA.
- Chaves colocadas em `/tmp/tcc-storage-linkinn/keys` (private.pem, public.pem) e ownership ajustado para `daemon:daemon`.

4) Correções de runtime e bugs
- Fatal de `Cannot redeclare tcc_sign_identifier()`:
  - Motivo: duplicação entre `crypto.php` e `src/helpers.php`.
  - Solução: funcoes RSA renomeadas para `tcc_sign_identifier_rsa()` / `tcc_verify_identifier_signature_rsa()`; `src/helpers.php` passa a ser a API pública `tcc_sign_identifier()` / `tcc_verify_identifier_signature()` que escolhe RSA quando chaves existem e HMAC como fallback de teste.

- Erro HTTP 500 em `/api/salvar_medicamento.php`:
  - Instrumentado com logging temporário (`/tmp/salvar_debug.log`) e handler de shutdown para capturar fatals durante depuração.
  - Após correções de assinatura e permissões, endpoint passou a retornar JSON com `success:true` e `data.sig`.

- Validador cliente (`public/validador.php`) recebeu hardening:
  - Normalização do formato retornado por `html5-qrcode` (string ou objetos com diferentes chaves).
  - Aceitação de envelopes/formatos legados (ex.: `data`, `payload`, `identifier`/`signature`) e do campo `assinatura` em PT-BR.
  - Correção para enviar `id` e `sig` ao endpoint `api/validar_unicidade.php` para que o servidor possa re-verificar a assinatura antes de marcar a validação.

5) Frontend: QR Code
- QR renderizado via `public/assets/js/qr.js` com padrão 256×256.
- Adicionado suporte para download do QR em alta resolução (512×512) via `tccQr.downloadQr()`.
- Tela de visualização (`public/index.php`) e painel do fabricante (`public/fabricante.php`) atualizados para expor botão de download do QR.

6) Banco de dados e limpeza
- Backup do banco SQLite: `/opt/lampp/htdocs/data/tcc.sqlite.bak-<timestamp>` criado.
- Limpeza solicitada: remoção de registros em `medicamentos` e `validacoes`, seguido de `VACUUM`.

7) Permissões e execução
- Garantido que o processo web (Apache/XAMPP) possui acesso ao storage e chaves (chown para `daemon:daemon`).
- Observado que CLI PHP e Apache PHP podem ter versões diferentes; preferir executar `bin/gerar_chaves.php` como root/sudo e ajustar ownership para `daemon` em seguida.

Artefatos temporários criados durante o debug (recomenda-se remover em commit final)
- `/tmp/salvar_debug.log` — arquivo de depuração com traces de inclusão e erros capturados.
- Entradas temporárias em `public/api/salvar_medicamento.php`, `bootstrap.php`, `db.php`, `auth.php` utilizadas para diagnóstico (remover antes do push).

Arquivos modificados (lista exemplar)
- `crypto.php` — renomeação de funções RSA e leitura de chaves.
- `src/helpers.php` — centralização de API de assinatura/validação.
- `public/api/salvar_medicamento.php` — tratamento de payload, debug temporário.
- `public/validador.php` — normalização e correção do fluxo de validação.
- `public/assets/js/qr.js` — renderização do QR e adição de `downloadQr()`.
- `public/fabricante.php`, `public/index.php` — UI para download do QR.
- `public/api/validar_unicidade.php` / `src/Service/ValidacaoService.php` — validação e marcação atômica de unicidade.

Instruções para commit (mensagem sugerida)
---------------------------------------
Resumo das alterações e motivos:

"Refactor project structure (public/, src/), add RSA signing support and validator fixes. Fix redeclare fatal by centralizing signing API, add RSA key generation and storage under TCC_STORAGE_PATH, ensure Apache (daemon) ownership. Harden client validator (accept multiple QR formats, send signature to server), add QR download helper (512px PNG), clean DB and remove generated keys for regeneration. Temporary debug logging added — remove before final release."

Sugestão de body detalhado (copiar se necessário):
- Moved web root to `public/` and organized PHP code under `src/` (PSR-4).
- Implemented Services/Repository pattern for signatures and validations.
- Resolved fatal redeclare by renaming RSA functions and centralizing `tcc_sign_identifier()` in `src/helpers.php`.
- Added RSA key generation script and configured storage path via `.env`.
- Fixed `/api/salvar_medicamento.php` flow and CSRF/session handling; ensured API returns signature in `data.sig`.
- Hardened `validador.php` to accept multiple QR payload shapes and to pass signature to server validation.
- Added `tccQr.downloadQr()` and download buttons in UI to export high-resolution QR images.
- Performed DB backup and cleared generated records upon request.

Checklist before push
---------------------
- [ ] Remover logs de depuração (`/tmp/salvar_debug.log` e file_put_contents no código).
- [ ] Validar todos os testes unitários (se aplicável) e rodar phpstan/phpunit.
- [ ] Atualizar changelog/README conforme necessário.
- [ ] Commitar com a mensagem sugerida.

Contato
-------
Se quiser, eu removo os artefatos de debug e faço o commit (sem forçar push). Posso também gerar as chaves novamente e executar um teste completo create->validate.
