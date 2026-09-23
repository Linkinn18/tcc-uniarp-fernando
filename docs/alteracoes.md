P07 [Alto] Mensagens de erro internas do banco enviadas ao cliente
Status: corrigido

Contexto original:
- O backend retornava `PDOException::getMessage()` em respostas JSON e também podia emitir erro inadequado quando `db.php` era incluído por páginas HTML.

Risco:
- Exposição de detalhes internos como caminhos do banco SQLite, nomes de tabelas, estrutura do schema e mensagens técnicas do PDO.

Situação atual:
- O problema continuava existindo mesmo após a troca de MySQL para SQLite, porque a origem do risco era a exposição de mensagens internas, não o SGBD em si.
- O tratamento foi corrigido para registrar a exceção no log do servidor e retornar apenas mensagem genérica ao cliente.

Correção aplicada:
- Centralização do tratamento seguro de erro no bootstrap da aplicação.
- Respostas HTTP 500 com mensagem genérica para APIs e páginas.
- Remoção de concatenação de `getMessage()` nas rotas afetadas.

Arquivos impactados:
- `bootstrap.php`
- `db.php`
- `public/api/_bootstrap.php`
- `public/api/salvar_medicamento.php`
- `public/api/buscar_medicamentos.php`
- `public/api/validar_unicidade.php`
- `api/auditoria.php`

---

P08 [Alto] Bibliotecas carregadas de CDN sem versão fixa e sem verificação de integridade
Status: corrigido

Contexto original:
- As páginas públicas carregavam bibliotecas de frontend diretamente de CDN, incluindo Bootstrap, `qrcodejs` e `html5-qrcode`.

Risco:
- Dependência de disponibilidade externa.
- Possibilidade de quebra por alteração remota de versão.
- Fragilidade de cadeia de suprimentos.
- Baixa reprodutibilidade para demonstração e avaliação futura do projeto.

Situação atual:
- O risco ainda existia no código recente e foi removido.
- Os assets de terceiros foram internalizados no projeto e agora são servidos localmente.

Correção aplicada:
- Vendoring dos arquivos para `public/assets/vendor/`.
- Remoção das referências a CDN nas páginas públicas.

Arquivos impactados:
- `public/index.php`
- `public/login.php`
- `public/fabricante.php`
- `public/validador.php`
- `public/gerar_chaves.html`
- `public/assets/vendor/bootstrap/bootstrap.min.css`
- `public/assets/vendor/bootstrap/bootstrap.bundle.min.js`
- `public/assets/vendor/qrcodejs/qrcode.min.js`
- `public/assets/vendor/html5-qrcode/html5-qrcode.min.js`

---

P09 [Médio] Credenciais do banco fixas no código (root sem senha)
Status: não se aplica mais na arquitetura atual

Contexto original:
- O sistema antigo utilizava MySQL com credenciais fixas no código-fonte.

Risco original:
- Exposição de credenciais de banco.
- Uso de conta privilegiada demais para a aplicação.

Situação atual:
- O projeto não usa mais MySQL.
- O acesso a dados é feito exclusivamente por SQLite usando `TCC_DB_SQLITE_PATH`.
- Não existem mais usuário, senha ou nome de banco MySQL no runtime atual.
- As credenciais remanescentes no `.env` são da autenticação da aplicação (`TCC_ADMIN_USER` e `TCC_ADMIN_PASSWORD`), não do banco, e a senha é armazenada em hash.

Conclusão:
- O achado histórico deve ser tratado como obsoleto para a arquitetura atual.
- Não há correção adicional de código pendente para este ponto.

---

P10 [Médio] DDL executado no runtime e duplicidade de definição de schema
Status: corrigido

Contexto original:
- O sistema antigo criava estrutura de banco em tempo de execução e mantinha definição duplicada de schema.

Risco:
- Custo desnecessário em requisições.
- Acoplamento entre bootstrap da aplicação e criação estrutural do banco.
- Risco de divergência entre múltiplas definições do schema.

Situação atual:
- Após a migração para SQLite, o problema mudou de forma, mas ainda existia: `src/Database.php` executava `CREATE TABLE IF NOT EXISTS` durante o bootstrap, enquanto `schema.sql` ainda refletia sintaxe legada de MySQL.

Correção aplicada:
- `schema.sql` passou a ser a fonte única do schema.
- O DDL foi removido do fluxo normal da aplicação.
- Foi criado um setup explícito via CLI para inicialização do banco.

Arquivos impactados:
- `src/Database.php`
- `schema.sql`
- `bin/setup_db.php`
- `README.md`

Conclusão geral

- P07: corrigido.
- P08: corrigido.
- P09: não se aplica mais após a migração para SQLite.
- P10: corrigido.

Estado final:
- Os pontos do relatório presentes neste arquivo estão encerrados para a arquitetura atual do projeto.