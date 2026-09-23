# Sistema Criptográfico de Autenticidade e Unicidade

Projeto em PHP para emissão e validação de medicamentos com assinatura digital e controle de unicidade por QR Code.

## Fluxo atual

- O fabricante faz login antes de emitir medicamentos.
- O servidor gera o `id` de cada unidade e assina esse `id` com a chave privada.
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
sudo apt install -y apache2 libapache2-mod-php php php-cli php-sqlite3 sqlite3 git curl unzip composer
sudo a2enmod rewrite
sudo systemctl restart apache2
```

> **Importante**: `php-openssl` e `php-session` normalmente **não existem** como pacotes separados no Ubuntu/Debian. O suporte a OpenSSL e sessão já vem no pacote principal do PHP.

### Passo a passo para outro computador/servidor Linux (Ubuntu/Debian)

1. Instale Apache, PHP, SQLite, Git e Composer:

```bash
sudo apt update
sudo apt install -y apache2 libapache2-mod-php php php-cli php-sqlite3 sqlite3 git curl unzip composer
php -m | grep -E 'openssl|pdo_sqlite|sqlite3'
```

2. Clone o projeto no servidor:

```bash
cd /var/www
sudo git clone https://github.com/Linkinn18/tcc-uniarp-fernando.git tcc
cd /var/www/tcc
```

3. Ajuste permissões básicas do projeto:

```bash
sudo chown -R $USER:www-data /var/www/tcc
sudo find /var/www/tcc -type d -exec chmod 775 {} \;
sudo find /var/www/tcc -type f -exec chmod 664 {} \;
```

4. Instale o autoload do Composer:

```bash
composer install --no-dev --optimize-autoloader
```

5. Crie o diretório de storage fora da área pública:

```bash
sudo mkdir -p /var/lib/tcc-storage
sudo chown -R $USER:www-data /var/lib/tcc-storage
sudo chmod -R 770 /var/lib/tcc-storage
```

6. Crie o arquivo `.env` do projeto:

```bash
cp .env.example .env
cat > .env <<'EOF'
TCC_DB_SQLITE_PATH=/var/lib/tcc-storage/tcc.sqlite
TCC_ADMIN_USER=fabricante
TCC_ADMIN_PASSWORD=troque-esta-senha
TCC_KEY_PASSPHRASE=troque-esta-passphrase
TCC_STORAGE_PATH=/var/lib/tcc-storage
EOF
```

7. Inicialize o banco SQLite:

```bash
php bin/setup_db.php
```

8. Gere o par de chaves RSA:

```bash
php bin/gerar_chaves.php
setfacl -m u:www-data:r /var/lib/tcc-storage/keys/private.pem
setfacl -m u:www-data:r /var/lib/tcc-storage/keys/public.pem
```

9. Crie o VirtualHost apontando para `public/`:

```bash
sudo tee /etc/apache2/sites-available/tcc.conf > /dev/null <<'EOF'
<VirtualHost *:80>
	ServerName tcc.local
	DocumentRoot /var/www/tcc/public

	<Directory /var/www/tcc/public>
		AllowOverride All
		Require all granted
		DirectoryIndex index.php
	</Directory>

	ErrorLog ${APACHE_LOG_DIR}/tcc_error.log
	CustomLog ${APACHE_LOG_DIR}/tcc_access.log combined
</VirtualHost>
EOF
```

10. Ative o site e recarregue o Apache:

```bash
sudo a2dissite 000-default.conf
sudo a2ensite tcc.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

11. Se quiser testar localmente pelo nome `tcc.local`, adicione no hosts:

```bash
echo '127.0.0.1 tcc.local' | sudo tee -a /etc/hosts
```

12. Acesse o sistema:

```bash
xdg-open http://tcc.local/login.php
```

Se o servidor não tiver ambiente gráfico, abra no navegador de outro computador usando `http://IP-DO-SERVIDOR/login.php` ou configurando o DNS para o `ServerName` escolhido.

#### Linux (Fedora/RedHat)

```bash
sudo dnf install php php-cli php-openssl php-pdo php-pdo_sqlite httpd
sudo systemctl restart httpd
```

#### Windows

1. Baixe [XAMPP](https://www.apachefriends.org/) ou [PHP for Windows](https://windows.php.net/download/)
2. Instale em `C:\xampp` (ou outro caminho)
3. Certifique-se de que PHP está no PATH do sistema

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
4. Inicialize o banco SQLite com [bin/setup_db.php](bin/setup_db.php).
5. Gere as chaves com [bin/gerar_chaves.php](bin/gerar_chaves.php).
6. Acesse [public/login.php](public/login.php) pela URL configurada no seu servidor web.

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

O projeto utiliza **SQLite** exclusivamente para máxima portabilidade. O arquivo de banco é aberto no caminho definido por `TCC_DB_SQLITE_PATH`, e o schema é inicializado explicitamente com `php bin/setup_db.php`. O usuário inicial do fabricante é provisionado automaticamente a partir do `.env`.

## Assets frontend

Os arquivos de terceiros do frontend ficam versionados em `public/assets/vendor/` para evitar dependência de CDN e permitir execução offline do projeto.

## Comandos úteis

### Linux/macOS

Gerar chaves:

```bash
php bin/gerar_chaves.php
```

Inicializar o banco SQLite:

```bash
php bin/setup_db.php
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

Inicializar o banco SQLite:

```powershell
php bin\setup_db.php
```

Regenerar chaves (força):

```powershell
php bin\gerar_chaves.php --force
```

Demonstrar a correção da condição de corrida (P03):

```powershell
$env:TCC_STORAGE_PATH="C:\xampp\storage\tcc-p03-demo"; php bin\demonstrar_p03.php
```

> **Nota**: A maioria dos comandos trabalha com caminhos relativos. Se necessário usar o PHP de um caminho específico (como `/opt/lampp/bin/php` ou `C:\xampp\php\php.exe`), substitua `php` pelo caminho completo.
## Observações sobre o comportamento antigo

- Antes, você rodava o Apache local (XAMPP/LAMP) apontando o DocumentRoot para a raiz do projeto e acessava `http://localhost`. Com a reorganização, o conteúdo público foi movido para `tcc/public` — se preferir continuar usando o Apache do sistema, ajuste o DocumentRoot para `.../tcc/public`.
- Composer fornece autoload PSR-4 (`App\\` → `src/`). Após `composer install`, carregue `vendor/autoload.php` em scripts CLI ou ao usar endpoints que dependem de classes `App\\`.

## Rotas públicas

- Página inicial: [public/index.php](public/index.php)
- Login do fabricante: [public/login.php](public/login.php)
- Painel do fabricante: [public/fabricante.php](public/fabricante.php)
- Validador: [public/validador.php](public/validador.php)

## Arquivos principais

- [auth.php](auth.php): autenticação, sessão e CSRF.
- [bootstrap.php](bootstrap.php): leitura do `.env` e caminhos de storage.
- [crypto.php](crypto.php): geração de chaves, assinatura e verificação da assinatura.
- [db.php](db.php): conexão com SQLite e provisionamento do usuário inicial a partir do `.env`.
- [public/fabricante.php](public/fabricante.php): área autenticada para emissão de unidades vinculadas a um lote informado.
- [public/validador.php](public/validador.php): leitura do QR Code e envio de `id` e `sig`.
- [public/api/salvar_medicamento.php](public/api/salvar_medicamento.php): emissão de medicamento no backend.
- [public/api/validar_unicidade.php](public/api/validar_unicidade.php): verificação de assinatura e validação atômica de unicidade.
- [api/setup_chaves.php](api/setup_chaves.php): endpoint desativado por segurança.
- [bin/gerar_chaves.php](bin/gerar_chaves.php): geração segura das chaves.
- [bin/setup_db.php](bin/setup_db.php): inicialização explícita do schema SQLite.
- [bin/demonstrar_p03.php](bin/demonstrar_p03.php): demonstração prática da mitigação de corrida.

## Observações

- Se chaves antigas já foram versionadas, elas devem ser consideradas comprometidas.
- Remover arquivos no commit atual não limpa o histórico antigo; se necessário, limpe o histórico com `git filter-repo`.
- Em produção, o ideal é usar um mecanismo dedicado para proteger a chave privada.
