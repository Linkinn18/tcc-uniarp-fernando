# Sistema Criptográfico de Autenticidade e Unicidade

Projeto em PHP para emissão e validação de medicamentos com assinatura digital e controle de unicidade por QR Code.

## Fluxo atual

- O fabricante faz login antes de emitir medicamentos.
- O servidor gera o `id` do medicamento e assina esse `id` com a chave privada.
- O QR Code contém `{ id, sig }`.
- O validador pode verificar a assinatura no navegador como feedback imediato.
- A decisão final acontece no servidor: [api/validar_unicidade.php](api/validar_unicidade.php) valida a assinatura e só então tenta marcar o medicamento como validado.
- A validação de unicidade é atômica: apenas a primeira requisição com `status = 0` consegue atualizar o registro.

## Pré-requisitos

- **PHP 8.0+** com suporte a OpenSSL (para RSA) e SQLite
- **Servidor Web** (Apache, Nginx, etc.) ou servidor built-in do PHP
- **Git** (para clonar o repositório)
- **SQLite3** (geralmente incluído no PHP)

### Instalação rápida

#### Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install php php-cli php-openssl php-sqlite3 php-session apache2
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Linux (Fedora/RedHat)

```bash
sudo dnf install php php-cli php-openssl php-pdo php-pdo_sqlite httpd
sudo systemctl restart httpd
```

#### Windows

1. Baixe [XAMPP](https://www.apachefriends.org/) ou [PHP for Windows](https://windows.php.net/download/)
2. Instale em `C:\xampp` (ou outro caminho)
3. Certifique-se de que PHP está no PATH do sistema

#### macOS

```bash
brew install php@8.2 openssl
# ou use MAMP/XAMPP
```

## Segurança implementada

- Chave privada fora do diretório público.
- Geração de chaves apenas por CLI.
- Endpoint web de setup de chaves desativado.
- Login com sessão PHP, regeneração de sessão e proteção CSRF.
- Assinatura gerada no servidor, nunca no navegador.
- Verificação da assinatura também no servidor.

## Configuração

1. Copie [.env.example](.env.example) para `.env`.
2. Defina as credenciais do fabricante, a passphrase da chave e o caminho do banco SQLite.
3. Configure um diretório de storage fora do web root em `TCC_STORAGE_PATH`.
4. Gere as chaves com [bin/gerar_chaves.php](bin/gerar_chaves.php).
5. Acesse [login.php](login.php).

### Exemplo de `.env` (Linux/macOS)

```env
# Configuração de Banco de Dados (SQLite)
TCC_DB_SQLITE_PATH=/tmp/tcc-storage/tcc.sqlite

# Credenciais Administrativas
TCC_ADMIN_USER=fabricante
TCC_ADMIN_PASSWORD=troque-esta-senha

# Segurança Criptográfica
TCC_KEY_PASSPHRASE=troque-esta-passphrase

# Armazenamento de Chaves RSA
TCC_STORAGE_PATH=/tmp/tcc-storage
```

### Exemplo de `.env` (Windows)

```env
# Configuração de Banco de Dados (SQLite)
TCC_DB_SQLITE_PATH=C:\xampp\storage\tcc\tcc.sqlite

# Credenciais Administrativas
TCC_ADMIN_USER=fabricante
TCC_ADMIN_PASSWORD=troque-esta-senha

# Segurança Criptográfica
TCC_KEY_PASSPHRASE=troque-esta-passphrase

# Armazenamento de Chaves RSA
TCC_STORAGE_PATH=C:\xampp\storage\tcc
```

> **Nota**: Em Windows com XAMPP, use `C:\xampp\storage\tcc` ou outro caminho dentro de `C:\xampp`. Em produção, prefira caminhos fora do web root.

## Banco de dados

O projeto utiliza **SQLite** exclusivamente para máxima portabilidade. O banco é criado automaticamente no caminho definido por `TCC_DB_SQLITE_PATH`. O usuário inicial do fabricante é provisionado automaticamente a partir do `.env`.

## Comandos úteis

### Linux/macOS

Gerar chaves:

```bash
php bin/gerar_chaves.php
```

Regenerar chaves (força):

```bash
php bin/gerar_chaves.php --force
```

Demonstrar a correção da condição de corrida (P03):

```bash
TCC_STORAGE_PATH=/tmp/tcc-p03-demo php bin/demonstrar_p03.php
```

### Windows (PowerShell ou CMD)

Gerar chaves:

```powershell
php bin\gerar_chaves.php
```

Regenerar chaves (força):

```powershell
php bin\gerar_chaves.php --force
```

Demonstrar a correção da condição de corrida (P03):

```powershell
$env:TCC_STORAGE_PATH="C:\xampp\storage\tcc-p03-demo"; php bin\demonstrar_p03.php
```

### Usando Docker (cross-platform)

Caso queira rodar em container sem instalar PHP localmente:

```bash
docker run --rm -v $(pwd):/app -w /app php:8-cli php bin/gerar_chaves.php
```

> **Nota**: A maioria dos comandos trabalham com caminhos relativos. Se necessário usar o PHP de um caminho específico (como `/opt/lampp/bin/php` ou `C:\xampp\php\php.exe`), substitua `php` pelo caminho completo.

## Arquivos principais

- [auth.php](auth.php): autenticação, sessão e CSRF.
- [bootstrap.php](bootstrap.php): leitura do `.env` e caminhos de storage.
- [crypto.php](crypto.php): geração de chaves, assinatura e verificação da assinatura.
- [db.php](db.php): conexão e criação automática das tabelas.
- [fabricante.php](fabricante.php): área autenticada do fabricante.
- [validador.php](validador.php): leitura do QR Code e envio de `id` e `sig`.
- [api/salvar_medicamento.php](api/salvar_medicamento.php): emissão de medicamento no backend.
- [api/validar_unicidade.php](api/validar_unicidade.php): verificação de assinatura e validação atômica de unicidade.
- [api/setup_chaves.php](api/setup_chaves.php): endpoint desativado por segurança.
- [bin/gerar_chaves.php](bin/gerar_chaves.php): geração segura das chaves.
- [bin/demonstrar_p03.php](bin/demonstrar_p03.php): demonstração prática da mitigação de corrida.

## Observações

- Se chaves antigas já foram versionadas, elas devem ser consideradas comprometidas.
- Remover arquivos no commit atual não limpa o histórico antigo; se necessário, limpe o histórico com `git filter-repo`.
- Em produção, o ideal é usar um mecanismo dedicado para proteger a chave privada.
