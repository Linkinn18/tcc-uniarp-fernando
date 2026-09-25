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

## Reavaliação item a item de `docs/Análise-TCC-Fernando.docx`

Validação realizada em 2026-09-25 contra o código presente no working tree. O documento analisado descreve uma versão anterior do sistema (MySQL/OpenPGP); abaixo, cada ponto foi reavaliado para a implementação atual (PHP, SQLite e assinatura RSA). “Parcial” significa que a correção principal existe, mas restam as pendências descritas.

- **P01 — Implementado.** O `=` isolado citado no JavaScript antigo de `fabricante.php` não existe no arquivo atual.
- **P02 — Implementado.** `public/api/validar_unicidade.php` recebe ID e assinatura, e `ValidacaoService` verifica a assinatura no servidor antes de consultar ou alterar o status.
- **P03 — Implementado no código; teste pendente.** `MedicamentoRepository::markValidatedAtomic()` atualiza com `WHERE status = 0` e decide pelo número de linhas afetadas. Falta um teste concorrente que demonstre que somente uma requisição vence.
- **P04 — Parcial.** A chave atual é gerada por CLI e armazenada em `TCC_STORAGE_PATH`, fora de `public/`; não há arquivo de chave rastreado no working tree. Porém, o histórico Git contém commits que tocaram caminhos de chave privada. Trate chaves históricas como comprometidas; a limpeza do histórico e a confirmação da rotação para todos os ambientes ainda não foram realizadas.
- **P05 — Implementado.** Há login com `password_hash()`/`password_verify()`, sessão regenerada, CSRF e proteção das páginas/API do fabricante. O endpoint HTTP de setup de chaves responde 403.
- **P06 — Implementado para acesso anônimo.** A busca exige autenticação em `require_auth_json()`; o ID/assinatura para remontar QR só é devolvido a uma sessão autenticada. O risco de exposição por conta autenticada continua sendo uma decisão de produto, não uma API pública anônima.
- **P07 — Implementado no fluxo web verificado.** Erros internos são registrados e as respostas de banco usam mensagem genérica; não encontrei `die()` com detalhe SQL nas rotas atuais.
- **P08 — Implementado.** Bootstrap, `qrcodejs` e `html5-qrcode` estão locais em `public/assets/vendor/`, com versões fixas, sem depender de CDN nas páginas examinadas.
- **P09 — Não aplicável na forma original; configuração equivalente parcial.** O projeto atual usa SQLite, não credenciais MySQL/root, e lê configuração de `.env`. O `.env.example` está apagado no working tree (`git status` mostra `D .env.example`); por isso a cópia de exemplo referenciada no README não está disponível neste snapshot. Essa exclusão local não foi desfeita.
- **P10 — Implementado.** `schema.sql` é a fonte do DDL e `bin/setup_db.php` aplica o esquema explicitamente; `db.php` abre o PDO e provisiona o usuário, sem criar tabelas em cada requisição.
- **P11 — Implementado.** O UUID é gerado no servidor por `tcc_generate_uuid_v4()` usando `random_bytes()`.
- **P12 — Parcial.** Dados de linha são escapados e `tccDom.escapeHtml()` converte valores nulos com `String()`. Ainda há mensagens dinâmicas de respostas inseridas por `innerHTML` sem escape em `public/index.php` e em mensagens de sucesso de `public/fabricante.php`; trocar por `textContent`/templates seguros ou escapar explicitamente.
- **P13 — Implementado na maior parte.** A API usa status HTTP semânticos (por exemplo, 400, 401, 403, 405, 409, 419, 422 e 500) via o helper de resposta JSON.
- **P14 — Implementado nos caminhos examinados.** Os `require_once` usam `__DIR__` e não encontrei operador `@` nas rotas/aplicação atuais.
- **P15 — Não implementado como fluxo por etapas; limitação intencional.** A primeira validação consome o código e leituras legítimas posteriores podem gerar alerta. A decisão de manter essa simplificação está registrada nesta documentação; ainda precisa ser defendida na metodologia da monografia.
- **P16 — Não implementado.** A tabela `validacoes` existe no schema e o serviço chama `tcc_audit_validation()`, mas essa função em `src/helpers.php` é no-op. Nenhuma tentativa real é persistida.
- **P17 — Parcial.** Foram adotados `criado_em`, `status INTEGER` e índices em nome/lote; o formulário trata a emissão como unidade e a assinatura é base64. Ainda não há entidades próprias para fabricante/lote nem validade do medicamento.
- **P18 — Parcial.** As datas de criação e validação usam `date()` no PHP, então não há o antigo conflito PHP/MySQL, mas o timezone da aplicação não é fixado. A listagem também mostra SHA-256 da assinatura sem uso na decisão de segurança; justificar esse campo ou removê-lo.
- **P19 — Parcialmente implementado.** O webroot fica em `public/` e serviços/repositórios estão em `src/`, isolados do DocumentRoot configurado. As páginas ainda misturam PHP/HTML/JavaScript e a separação de apresentação/API não está completa.
- **P20 — Implementado na estrutura principal.** DDL está centralizado; API compartilha `public/api/_bootstrap.php`; QR e escape HTML usam `public/assets/js/qr.js` e `dom.js`. Revisões futuras ainda podem reduzir markup/fluxos repetidos.
- **P21 — Parcial.** Existe `composer.json` com PSR-4, autoload local em `autoload.php`, `strict_types` em vários arquivos e configuração de PHPUnit/PHPStan/CS-Fixer. A adoção de tipos/estilo não é uniforme. No ambiente validado, Composer e `vendor/bin/phpunit` não estão disponíveis.
- **P22 — Parcial, cobertura insuficiente.** Existem apenas dois testes de serviço, sem os seis cenários de ataque propostos nem teste concorrente. A tentativa de executar o PHPUnit global falhou porque é uma versão antiga incompatível com PHP 8.2 (`each()` removida); não foi possível executar a suíte configurada.
- **P23 — Parcial.** Os campos nome/lote rejeitam string vazia sem rejeitar literalmente `0`, mas não têm limites de tamanho; o ID não é validado como UUID e a busca de ID usa `LIKE` em vez de igualdade.
- **P24 — Parcial.** A validação por arquivo está ligada à interface e não é código morto. Ainda faltam `.editorconfig` e `.gitattributes`, e há mistura de convenções/idiomas e comentários narrativos em partes do projeto.
- **P25 — Parcial.** O QR usa payload compacto `i=...&s=...`, correção M e PNG exportado com margem branca. Ainda usa RSA-2048, sem medição experimental de versão/densidade/tamanho físico mínimo e taxa de leitura; o domínio de produção/URL curta também não foi definido.
- **P26 — Parcial.** A chave pública é carregada uma vez por página, mas exceções de leitura, erro de rede/API e QR não reconhecido ainda podem cair no mesmo fluxo visual de “Falsificação Detectada”. Diferenciar falha de leitura, indisponibilidade, assinatura inválida e código já usado.
- **P27 — Parcial.** O README atual descreve PHP/SQLite e contém instalação e execução para Windows/Linux. Ainda faltam diagrama da arquitetura e seção consolidada de limitações; a metodologia da monografia não foi fornecida para confronto. A falta local de `.env.example` também contradiz a instrução de copiá-lo.
- **P28 — Parcial.** `.gitignore` existe e ignora `.env`, `keys/`, `storage/` e `vendor/` da raiz; os assets fixos em `public/assets/vendor/` permanecem versionáveis. Não há `docs/artigos/` nem PDFs bibliográficos no snapshot examinado. A presença de chaves no histórico permanece pendente de tratamento, e mensagens/tagging de releases não foram auditadas por completo.

### Pendências confirmadas

1. Implementar persistência real de auditoria P16 (incluindo ID ausente, assinatura inválida, já validado e sucesso).
2. Fechar validações P23: limites de tamanho, UUID e busca por ID exata.
3. Completar os cenários de teste P22, especialmente concorrência, adulteração/forja de assinatura, ID inexistente e API sem assinatura; instalar uma versão compatível do PHPUnit para executá-los.
4. Distinguir erro operacional de fraude no validador (P26) e remover `innerHTML` de mensagens dinâmicas (P12).
5. Revisar o `.env.example` apagado no working tree sem descartar a alteração local; restaurá-lo somente após confirmar que os valores são placeholders.
6. Rotacionar qualquer chave que tenha sido armazenada no histórico Git e decidir, com o responsável pelo repositório, se o histórico público será reescrito.
7. Completar a decisão/documentação acadêmica de P15, P17 e P25: limitações do protótipo, modelo de dados e medições físicas do QR.
