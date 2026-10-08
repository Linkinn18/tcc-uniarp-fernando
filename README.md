# Sistema Criptográfico de Autenticidade e Unicidade

Projeto em PHP desenvolvido para emissão e validação de medicamentos com assinatura digital RSA, controle de unicidade por QR Code e armazenamento local em SQLite. A aplicação foi estruturada para uso web no ambiente Windows com XAMPP, mantendo a chave privada fora da pasta pública e exigindo autenticação para a emissão de novos registros.

## Fluxo atual

- O sistema lê as configurações do arquivo `.env`, conecta ao banco SQLite e garante a existência do usuário inicial do fabricante.
- O acesso começa pela tela de login. Após autenticação, o fabricante pode cadastrar uma unidade informando nome e lote.
- Para cada cadastro, o backend gera um identificador único, assina esse identificador com RSA-SHA256 e grava os dados no banco.
- O QR Code gerado contém o identificador e a assinatura digital em formato compacto.
- A validação é feita por upload de imagem PNG ou JPG na página de validação. O projeto não possui leitura por câmera.
- No momento da validação, o servidor confere a assinatura digital e tenta consumir o identificador apenas uma vez. Se o código já tiver sido validado antes, o sistema sinaliza reutilização do lote.

## Pré-requisitos

Este projeto deve ser configurado considerando apenas ambiente Windows.

- XAMPP instalado no Windows.
- PHP 8.0 ou superior no ambiente do XAMPP.
- Extensões `openssl` e `pdo_sqlite` habilitadas no PHP.
- Apache em execução pelo XAMPP.
- Git para clonar ou versionar o projeto.

## Nota sobre Linux

Não foi feita adaptação para execução nativa em Linux neste projeto, por questão de tempo e praticidade. O fluxo documentado e considerado suportado neste repositório é o de Windows com XAMPP.

## IP e Rotas para acesso

No cenário padrão deste projeto em XAMPP local, o ponto principal de acesso é a tela de login:

- `http://localhost/tcc-uniarp-fernando/public/login.php`

Se o Apache estiver configurado em outra porta ou se a pasta do projeto tiver outro nome, ajuste a URL conforme o seu ambiente.

## Segurança implementada (RSA)

O projeto utiliza um par de chaves RSA para assinar digitalmente o identificador de cada medicamento. A chave privada permanece no servidor e é usada apenas no backend para gerar a assinatura. A chave pública é usada para validar se o conteúdo do QR Code foi realmente emitido pelo sistema.

Além da assinatura, o sistema aplica controle de unicidade no banco de dados. Isso significa que não basta a assinatura ser válida: o identificador também só pode ser consumido uma única vez como unidade autêntica dentro do fluxo previsto.

As chaves são geradas apenas por linha de comando. A passphrase configurada em `TCC_KEY_PASSPHRASE` protege a chave privada exportada, mas não recria deterministicamente o mesmo par RSA.

## Configurações

Crie manualmente o arquivo `.env` na raiz do projeto. Esse arquivo não é versionado pelo Git e deve conter os parâmetros necessários para banco, autenticação inicial e armazenamento seguro.

### Exemplo de `.env`

```env
TCC_DB_SQLITE_PATH=C:\xampp\storage\tcc\tcc.sqlite
TCC_ADMIN_USER=fabricante
TCC_ADMIN_PASSWORD=troque-esta-senha
TCC_KEY_PASSPHRASE=troque-esta-passphrase
TCC_STORAGE_PATH=C:\xampp\storage\tcc
```

### Significado das variáveis

- `TCC_DB_SQLITE_PATH`: caminho do arquivo SQLite da aplicação.
- `TCC_ADMIN_USER`: nome do usuário inicial de acesso do fabricante.
- `TCC_ADMIN_PASSWORD`: senha inicial do fabricante.
- `TCC_KEY_PASSPHRASE`: frase usada para proteger a chave privada exportada.
- `TCC_STORAGE_PATH`: diretório onde ficam as chaves RSA, sessões e demais arquivos de storage.

### Passos de configuração no Windows

1. Instale o XAMPP em `C:\xampp` ou ajuste os caminhos conforme sua instalação.
2. Coloque o projeto em `C:\xampp\htdocs\tcc-uniarp-fernando`.
3. Confirme em `C:\xampp\php\php.ini` que `openssl` e `pdo_sqlite` estão habilitados.
4. Crie o arquivo `.env` com base no modelo acima.
5. Crie o diretório `C:\xampp\storage\tcc`, caso ainda não exista.
6. Na raiz do projeto, execute o setup do banco:

```powershell
php .\bin\setup_db.php
```

7. Depois gere as chaves RSA:

```powershell
php .\bin\gerar_chaves.php
```

Se o OpenSSL do XAMPP exigir configuração explícita, informe o arquivo `openssl.cnf` antes de executar a geração:

```powershell
$env:OPENSSL_CONF = 'C:\xampp\php\extras\ssl\openssl.cnf'
php .\bin\gerar_chaves.php
```

### Banco de dados

O projeto usa SQLite como banco principal. O schema é inicializado por [bin/setup_db.php](bin/setup_db.php), e o arquivo é criado no caminho definido em `TCC_DB_SQLITE_PATH`.

Atualmente o banco contempla:

- `medicamentos`: armazena identificador, nome, lote, assinatura, status e dados de validação.
- `usuarios`: armazena as credenciais do fabricante.
- `validacoes`: estrutura prevista para auditoria de validações.

### Aviso sobre rotação de chaves

Gerar um novo par de chaves substitui a identidade criptográfica anterior do sistema. Isso significa que QR Codes e assinaturas gerados com a chave antiga deixarão de validar com a nova chave pública.

Se houver necessidade de regeneração, preserve backup das chaves anteriores. Usar a mesma passphrase não faz o sistema recriar a mesma chave RSA.

## Estrutura/Arquitetura do projeto

- `public/`: páginas acessíveis pelo navegador, assets e endpoints usados pela interface.
- `src/`: serviços, repositórios e regras internas da aplicação.
- `bin/`: scripts de linha de comando para inicialização e manutenção, como geração de chaves e setup do banco.
- `bootstrap.php`: leitura do `.env` e resolução de caminhos do storage.
- `db.php`: conexão com SQLite e provisionamento do usuário inicial.
- `crypto.php`: geração de chaves, assinatura digital e validação criptográfica.
- `auth.php`: autenticação, sessão e proteção CSRF.
- `schema.sql`: definição do schema usado na inicialização do banco.
- `tests/`: testes automatizados do projeto.
- `vendor/`: dependências de desenvolvimento instaladas pelo Composer.

## Testes e ferramentas de desenvolvimento

Os testes atuais cobrem principalmente criação de assinatura e rejeição de assinatura inválida. O projeto também possui ferramentas de apoio para análise estática e padronização de código.

Para uso dessas ferramentas no Windows, instale as dependências de desenvolvimento com Composer e execute os binários na raiz do projeto:

```powershell
composer install
vendor\bin\phpunit.bat
vendor\bin\phpstan.bat analyse
vendor\bin\php-cs-fixer.bat fix --dry-run --diff
```

O Composer é necessário para os testes e ferramentas de desenvolvimento, mas não é obrigatório para o runtime da aplicação no fluxo atual.

## Limitações conhecidas

- A validação é feita por upload de imagem; o projeto não possui leitura por câmera.
- O navegador pode não oferecer suporte uniforme à validação criptográfica local em todos os cenários, então a verificação definitiva continua sendo a do servidor.
- A tabela `validacoes` existe no schema, mas o fluxo de auditoria ainda não está completamente implementado.
- O código é consumido na primeira validação válida, então leituras posteriores do mesmo QR podem ser tratadas como reutilização.
- Se chaves antigas forem perdidas ou substituídas, assinaturas anteriores deixam de ser reconhecidas pela nova chave pública.
