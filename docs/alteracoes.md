P04  [Crítico]  Chave privada do fabricante exposta em três lugares diferentes
Onde: keys/private.key; fabricante.php (linha 74); histórico Git
Problema: (a) A chave privada está versionada e publicada no GitHub — git ls-files lista keys/private.key e não existe .gitignore. (b) A pasta keys/ fica dentro do diretório servido pelo Apache: qualquer pessoa pode baixar http://servidor/keys/private.key. (c) fabricante.php injeta a chave privada inteira no HTML (const PRIVATE_KEY_ARMORED = `...`) — basta "Exibir código-fonte" em qualquer visita à página.
Por que importa: A chave privada é o único ativo que diferencia o fabricante de um falsificador. Com ela, qualquer pessoa assina IDs novos que passam na verificação. Todo o modelo de segurança descrito no TCC depende de ela nunca sair do ambiente do fabricante. Além disso, por estar no histórico do Git, o par atual deve ser considerado comprometido permanentemente — remover o arquivo em um novo commit não apaga o histórico.
Como corrigir:
    • Gerar um novo par de chaves e descartar o atual. Adicionar .gitignore com keys/, storage/, .env, vendor/, node_modules/. Se quiser limpar o histórico: git filter-repo (ou recriar o repositório).
    • Mover as chaves para fora do web root (ex.: storage/keys/ um nível acima de public/, ou um caminho absoluto lido de variável de ambiente), com permissão 600.
    • A assinatura deve acontecer no servidor: o navegador envia apenas { nome, lote }; o PHP gera o ID, assina com a chave privada e devolve { id, sig } para montar o QR. A chave privada nunca viaja para o navegador. Isso também resolve o P11 (ID gerado no cliente).
    • Proteger a chave privada com passphrase (openpgp.generateKey aceita passphrase; no libsodium, a chave pode ser cifrada em repouso), lida de variável de ambiente.
    • Na monografia, discutir que em produção a chave ficaria em um HSM/KMS — a banca tende a perguntar "e se vazar a chave?".


P05  [Crítico]  Nenhuma autenticação: qualquer visitante é "fabricante" e pode até trocar as chaves
Onde: fabricante.php; api/salvar_medicamento.php; api/setup_chaves.php
Problema: A "Área Segura" do fabricante é apenas um rótulo na barra de navegação — não há login, sessão ou verificação de permissão. O caso mais grave é api/setup_chaves.php: um POST não autenticado sobrescreve public.key e private.key. Um atacante instala o próprio par de chaves; a partir daí todos os produtos falsos dele passam na verificação e todos os legítimos passam a falhar.
Por que importa: Autorização é pré-requisito para qualquer sistema que emite credenciais. Sem ela, os endpoints de emissão e de configuração são a porta de entrada mais fácil — não é preciso quebrar nenhuma criptografia.
Como corrigir:
    • Implementar login mínimo para o fabricante: tabela usuarios com senha via password_hash()/password_verify(), sessão PHP (session_start, regeneração de ID no login) e verificação no topo de fabricante.php e salvar_medicamento.php. Token CSRF nos formulários.
    • setup_chaves.php não deve ser um endpoint HTTP. Transformar em script de linha de comando executado uma vez pelo administrador (php bin/gerar_chaves.php) e, se permanecer via web, recusar quando as chaves já existirem e exigir autenticação.
    • Para um TCC, um único perfil "fabricante" com senha no .env já demonstra o conceito — mas precisa existir e ser descrito no texto.

P28  [Médio]  Higiene do repositório Git
Onde: .git; docs/artigos/
Problema: Sem .gitignore (consequência direta: chaves versionadas — P04). Mensagens de commit pouco descritivas ("a"). PDFs de artigos de terceiros versionados em docs/artigos/ — pertencem à monografia, não ao código, e há questão de direitos autorais ao republicá-los em repositório público. Dois .docx de rascunho do TCC misturados ao código.
Como corrigir:
    • Adicionar .gitignore antes de qualquer outro commit.
    • Manter em docs/ somente documentação do próprio projeto (arquitetura, decisões, roteiro de testes). Referências bibliográficas ficam no texto da monografia.
    • Mensagens de commit descritivas (o que e por quê); criar tags para as versões entregues ao orientador (v0.1-entrega-1, ...).