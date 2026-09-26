# Sistema Criptográfico de Autenticidade e Unicidade

Projeto em PHP para emissão e validação de medicamentos com assinatura digital e controle de unicidade por QR Code.

## Fluxo atual

- **Configuração:** `bootstrap.php` lê `.env`; `db.php` conecta ao SQLite e cria o usuário inicial quando `TCC_ADMIN_USER` e `TCC_ADMIN_PASSWORD` estão definidos e ainda não existe usuário.
- **Emissão:** o fabricante autentica-se em `public/login.php`. `public/fabricante.php` envia nome e lote à API; o servidor gera UUID, assina-o com RSA-SHA256 e grava a unidade no SQLite.
- **QR Code:** o conteúdo é compacto (`i=<UUID>&s=<assinatura-base64>`). A página permite exibir e baixar PNG em alta resolução (1024 px, além da margem branca externa).
- **Validação:** `public/validador.php` aceita PNG/JPG enviado pelo usuário; não há leitura por câmera. Em contexto seguro, o navegador também confere a assinatura como feedback. A API `public/api/validar_unicidade.php` sempre verifica a assinatura no servidor antes de alterar o status.
- **Unicidade:** a mudança de `status = 0` para `status = 1` é feita por um `UPDATE` condicional atômico; somente uma requisição concorrente pode consumir o identificador.
- **Busca:** a página inicial oferece busca autenticada por nome/lote e exibição de QR para usuários autorizados.

O sistema é uma aplicação web PHP responsiva; não existe aplicativo nativo para Android/iOS. A validação criptográfica pode ocorrer no navegador, mas a validação de unicidade depende do servidor e do banco central.

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

4. Opcional: instale as ferramentas de desenvolvimento com Composer. O autoload local do projeto permite executar o sistema sem esta etapa:

```bash
composer install
```

5. Crie o diretório de storage fora da área pública:

```bash
sudo mkdir -p /var/lib/tcc-storage
sudo chown -R $USER:www-data /var/lib/tcc-storage
sudo chmod -R 770 /var/lib/tcc-storage
```

6. Crie o arquivo `.env` do projeto:

```bash
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
	ServerAlias tcc.localhost
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

### Passo a passo completo no Windows (XAMPP)

1. Instale o [XAMPP](https://www.apachefriends.org/) em `C:\xampp` ou ajuste os caminhos abaixo para o diretório em que ele for instalado.
2. Clone o projeto em `C:\xampp\htdocs\tcc-uniarp-fernando`.
3. Abra `C:\xampp\php\php.ini` e confirme que as extensões `openssl` e `pdo_sqlite` estão habilitadas para o PHP do Apache e para o PHP CLI.
4. Abra um PowerShell na raiz do projeto e valide o ambiente:

```powershell
php -v
php -m | Select-String 'openssl|pdo_sqlite'
```

5. Crie o arquivo `.env` na raiz do projeto com os valores do exemplo Windows abaixo. Se o diretório de storage ainda não existir, crie `C:\xampp\storage\tcc`.
6. Inicialize o banco SQLite:

```powershell
php .\bin\setup_db.php
```

7. Gere o par de chaves RSA. Se o XAMPP reclamar de arquivo de configuração do OpenSSL ausente, informe explicitamente o `openssl.cnf` da instalação:

```powershell
$env:OPENSSL_CONF = 'C:\xampp\php\extras\ssl\openssl.cnf'
php .\bin\gerar_chaves.php
```

8. Configure o Apache do XAMPP para apontar o site para `public/`, conforme o bloco de VirtualHost abaixo.
9. Adicione `127.0.0.1 tcc.local` ao arquivo `C:\Windows\System32\drivers\etc\hosts` com um editor executado como Administrador.
10. No XAMPP Control Panel, reinicie o Apache e valide o acesso pelos comandos abaixo:

```powershell
& 'C:\xampp\apache\bin\httpd.exe' -t
& 'C:\xampp\apache\bin\httpd.exe' -S
Test-NetConnection 127.0.0.1 -Port 8080
ping -n 1 tcc.local
```

11. Acesse `http://tcc.local:8080/login.php`.

12. Se quiser rodar ferramentas de desenvolvimento como PHPUnit, PHPStan ou PHP-CS-Fixer, instale o [Composer](https://getcomposer.org/) e execute:

```powershell
composer install
```

O sistema em produção/local não depende mais do `vendor/autoload.php` do Composer para carregar as classes internas do projeto. O Composer é opcional para execução do sistema e necessário apenas para ferramentas de desenvolvimento e testes.

##### VirtualHost no XAMPP

Os caminhos `/etc/apache2/sites-available` e `/var/www` usados no exemplo Linux não se aplicam ao XAMPP. No Windows, confirme em `C:\xampp\apache\conf\httpd.conf` que a inclusão abaixo está ativa (sem `#`):

```apache
Include conf/extra/httpd-vhosts.conf
```

Neste XAMPP, `C:\xampp\apache\conf\httpd.conf` define `Listen 8080`. O VirtualHost precisa usar a mesma porta. Adicione este bloco ao final de `C:\xampp\apache\conf\extra\httpd-vhosts.conf`; use barras `/` nos caminhos:

```apache
<VirtualHost *:8080>
	ServerName tcc.local
	ServerAlias tcc.localhost
	DocumentRoot "C:/xampp/htdocs/tcc-uniarp-fernando/public"

	<Directory "C:/xampp/htdocs/tcc-uniarp-fernando/public">
		AllowOverride All
		Require all granted
		DirectoryIndex index.php
	</Directory>

	ErrorLog "logs/tcc_error.log"
	CustomLog "logs/tcc_access.log" combined
</VirtualHost>
```

Se o XAMPP ou o projeto estiver em outro diretório, ajuste os dois caminhos de `DocumentRoot` e `Directory`. Se a diretiva `Listen` estiver em outra porta, use-a também em `<VirtualHost *:PORTA>`, no teste de porta e na URL. Para mapear o nome, abra o menu Iniciar, procure **Bloco de Notas**, clique com o botão direito e escolha **Executar como administrador**. No Bloco de Notas, abra `C:\Windows\System32\drivers\etc\hosts` (se o arquivo não aparecer, troque o filtro de tipo para **Todos os arquivos**) e adicione esta linha ao final:

```text
127.0.0.1 tcc.local
```

Salve o arquivo. Depois, valide a configuração do Apache, confirme que o VirtualHost aparece na porta correta e teste conectividade e resolução do nome:

```powershell
& 'C:\xampp\apache\bin\httpd.exe' -t
& 'C:\xampp\apache\bin\httpd.exe' -S
Test-NetConnection 127.0.0.1 -Port 8080
ping -n 1 tcc.local
```

`Syntax OK` verifica apenas a sintaxe; não inicia nem reinicia o Apache. Na saída de `-S`, confirme que `tcc.local` aparece em `*:8080`. No XAMPP Control Panel, pare e inicie o Apache para carregar a configuração. `Test-NetConnection` deve retornar `TcpTestSucceeded: True` e o `ping` deve resolver para `127.0.0.1`. Se a conexão falhar, confira se o Apache está iniciado, se a porta corresponde à diretiva `Listen` e consulte `C:\xampp\apache\logs\error.log` ou os avisos de conflito de porta no painel.

Acesse `http://tcc.local:8080/login.php`. O VirtualHost seleciona o site pelo nome `tcc.local`; `http://localhost:8080/` aponta para o DocumentRoot padrão do XAMPP, não para este projeto.

#### Firefox e Web Crypto

Firefox só disponibiliza `crypto.subtle` em contextos seguros. O domínio `tcc.local` em HTTP não é considerado seguro. Para desenvolvimento local, os VirtualHosts acima também declaram `ServerAlias tcc.localhost`; acesse `http://tcc.localhost:8080/login.php` no XAMPP ou `http://tcc.localhost/login.php` no Linux (ajuste a porta se necessário). O sufixo especial `.localhost` resolve para a própria máquina e é tratado como contexto seguro pelo Firefox, sem precisar adicionar outra entrada ao arquivo `hosts`. O validador usa upload de imagem e não solicita permissão de câmera.

Se continuar usando `tcc.local` por HTTP, a validação criptográfica local pode não estar disponível, mas o validador envia o identificador e a assinatura ao servidor, que sempre verifica a assinatura antes de aceitar ou marcar o código. Em produção, use HTTPS.

## Segurança implementada

- Chave privada fora do diretório público.
- Geração de chaves apenas por CLI.
- Endpoint web de setup de chaves desativado.
- Login com sessão PHP, regeneração de sessão e proteção CSRF.
- Assinatura gerada no servidor, nunca no navegador.
- Verificação da assinatura também no servidor.

## Configuração

Crie `.env` na raiz do projeto (não versione esse arquivo). Configure os caminhos do banco/storage, usuário e senha iniciais do fabricante e a passphrase da chave privada. Os exemplos completos para Linux/macOS e Windows estão abaixo. O arquivo `.env.example` não está presente neste snapshot; não dependa de copiá-lo.

Depois de criar `.env`, inicialize o schema com [bin/setup_db.php](bin/setup_db.php) e gere as chaves com [bin/gerar_chaves.php](bin/gerar_chaves.php). A tabela `usuarios` é provisionada pelo fluxo de conexão quando as variáveis administrativas estão definidas. Acesse `public/login.php` pela URL do VirtualHost.

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

O schema atual contém:

- `medicamentos`: UUID, nome, lote textual, data de cadastro, assinatura, status e data da primeira validação.
- `usuarios`: credenciais do fabricante armazenadas como hash de senha.
- `validacoes`: estrutura para eventos de validação; a persistência dos eventos ainda não foi implementada.

O SQLite cria o arquivo e diretório-pai quando necessário, mas o usuário do Apache precisa ter permissão de escrita no banco e no diretório de storage. Em Linux, mantenha chaves/storage fora do webroot e conceda ao usuário/grupo do Apache somente o acesso necessário. Em Windows, revise as ACLs NTFS de `C:\xampp\storage\tcc`.

## Arquitetura e interfaces

- `public/`: único diretório que deve ser publicado pelo Apache. Contém páginas, endpoints e assets estáticos.
- `src/`: conexão SQLite, repositório e serviços de assinatura/validação; não deve ser `DocumentRoot`.
- `bootstrap.php`: carregamento de `.env`, resolução de storage e helpers comuns.
- `autoload.php`: carrega classes `App\\` e helpers locais; o runtime não depende do autoload Composer.
- `schema.sql`: fonte do esquema SQLite, aplicada pelo script CLI `bin/setup_db.php`.
- `storage/` ou o caminho de `TCC_STORAGE_PATH`: chaves RSA e arquivos de sessão; mantenha fora de `public/`.
- `public/assets/vendor/`: bibliotecas estáticas vendorizadas e versionadas, como Bootstrap, `qrcodejs` e `html5-qrcode`.
- `/vendor/` na raiz: dependências de desenvolvimento instaladas pelo Composer; é ignorada pelo Git.

Rotas principais:

- `GET /` ou `/index.php`: início e busca de unidades (a API de busca requer autenticação).
- `GET/POST /login.php`: autenticação do fabricante.
- `/fabricante.php`: emissão de unidade e geração do QR, somente autenticado.
- `/validador.php`: leitura e validação de QR a partir de upload PNG/JPG; não usa câmera.
- `POST /api/salvar_medicamento.php`: emissão autenticada, com token CSRF.
- `GET /api/buscar_medicamentos.php`: busca autenticada.
- `POST /api/validar_unicidade.php`: revalidação da assinatura e consumo atômico do código.

`api/setup_chaves.php` é um endpoint legado desativado; gere chaves somente pela CLI.

## Assets frontend

Os arquivos de terceiros do frontend ficam versionados em `public/assets/vendor/` para evitar dependência de CDN e permitir execução offline do projeto.

- `public/assets/vendor/` contém os arquivos fixos do frontend usados pela interface, como Bootstrap, `html5-qrcode` e `qrcodejs`.
- A pasta `vendor/` da raiz é diferente: ela é gerada pelo Composer para dependências PHP de desenvolvimento e continua ignorada pelo Git.
- Se algum CSS ou JS não carregar no navegador, confirme que os arquivos existem em `public/assets/vendor/` e que a URL está sendo servida pelo VirtualHost correto (`http://tcc.local:8080/`).
- O QR exportado é gerado em 1024 px e inclui uma margem branca externa (quiet zone) para leitura pela imagem. Windows e Linux usam o mesmo código JavaScript no navegador; baixe novamente PNGs antigos para receber a resolução e a margem atualizadas.
- Para conferir os assets no Linux, use a URL e a porta configuradas no Apache (por exemplo, `curl -I http://tcc.local/assets/vendor/bootstrap/bootstrap.min.css`); a resposta esperada é `200 OK`. No XAMPP deste projeto, use `http://tcc.local:8080/`.

## Segurança e limitações conhecidas

- A chave privada RSA-2048 é cifrada com `TCC_KEY_PASSPHRASE` e fica fora de `public/`; o navegador recebe somente a chave pública e a assinatura do QR. A geração de chaves é feita por CLI.
- Em origens HTTP como `tcc.local`, Firefox pode desabilitar Web Crypto. O servidor ainda verifica a assinatura; para habilitar também a conferência local use o alias `tcc.localhost` ou configure HTTPS.
- O protótipo consome o código na primeira validação. Leituras legítimas posteriores também podem gerar alerta; validação por etapa/perfil ainda não existe.
- A tabela `validacoes` está no schema, mas o helper de auditoria é no-op: tentativas de sucesso/falha ainda não são persistidas.
- O modelo não possui entidades próprias de fabricante e lote nem data de validade do medicamento; lote é texto associado a cada unidade.
- Nome/lote não têm limites máximos na API, o ID não tem validação explícita de formato UUID e a busca por ID usa correspondência parcial.
- O QR usa RSA-2048, payload compacto e correção M; ainda faltam medições físicas de versão/densidade e distância/taxa de leitura para impressão.
- A busca autenticada retorna assinatura e permite remontar QR. Não exponha essa API publicamente nem compartilhe uma conta de fabricante.
- A aplicação ainda tem mensagens dinâmicas inseridas com `innerHTML` e alguns erros operacionais são apresentados como falha de autenticidade; esses fluxos devem ser refinados.
- Chaves privadas que já tenham sido publicadas em commits antigos devem ser consideradas comprometidas. Gerar chaves novas não remove segredos do histórico Git.

Os arquivos Docker existem, mas a configuração atual do `docker-compose.yml` ajusta o `DocumentRoot` para `/var/www/html/tcc/public`, enquanto o volume monta o projeto em `/var/www/html`. Essa configuração não foi validada para a estrutura atual; use o procedimento Apache/XAMPP ou corrija o caminho do Docker antes de depender dele.

## Testes e ferramentas de desenvolvimento

Os testes ficam em `tests/` e cobrem atualmente criação de assinatura e rejeição de assinatura inválida; ainda não cobrem os seis cenários de ataque listados em `alteracoes.md`, incluindo concorrência e repetição de QR. O script `bin/demonstrar_p03.php` é uma demonstração manual da condição de corrida.

Para instalar PHPUnit, PHPStan e PHP-CS-Fixer no ambiente de desenvolvimento:

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

No Windows PowerShell, os equivalentes são `composer install`, `vendor\\bin\\phpunit.bat`, `vendor\\bin\\phpstan.bat analyse` e `vendor\\bin\\php-cs-fixer.bat fix --dry-run --diff`. Composer é necessário para essas ferramentas, não para o runtime da aplicação.

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

### Windows (PowerShell)

Execute na raiz do projeto, no PowerShell. Os comandos abaixo cobrem a configuração mais comum em Windows com XAMPP:

```powershell
php -m | Select-String 'openssl|pdo_sqlite'
```

Inicialize o schema SQLite. O arquivo será criado no caminho definido por `TCC_DB_SQLITE_PATH`, e os diretórios necessários serão criados automaticamente:

```powershell
php .\bin\setup_db.php
```

Gere o par de chaves RSA após configurar `TCC_KEY_PASSPHRASE`. No XAMPP, se a geração falhar com erro de arquivo de configuração do OpenSSL, indique o `openssl.cnf` desta instalação:

```powershell
$env:OPENSSL_CONF = 'C:\xampp\php\extras\ssl\openssl.cnf'
php .\bin\gerar_chaves.php
```

Regenerar chaves (força):

```powershell
php .\bin\gerar_chaves.php --force
```

Se o XAMPP estiver instalado em outro diretório, ajuste o caminho de `OPENSSL_CONF`. O script não substitui chaves existentes sem `--force`. No Windows, avisos de falha ao ajustar owner/group podem aparecer porque esses ajustes são específicos de sistemas Unix; confirme as permissões NTFS da chave privada antes de usar o sistema em produção.

Demonstrar a correção da condição de corrida (P03):

```powershell
$env:TCC_STORAGE_PATH="C:\xampp\storage\tcc-p03-demo"; php .\bin\demonstrar_p03.php
```

> **Nota**: A maioria dos comandos trabalha com caminhos relativos. Se necessário usar o PHP de um caminho específico (como `/opt/lampp/bin/php` ou `C:\xampp\php\php.exe`), substitua `php` pelo caminho completo.
## Observações sobre o comportamento antigo

- Antes, você rodava o Apache local (XAMPP/LAMP) apontando o DocumentRoot para a raiz do projeto e acessava `http://localhost`. Com a reorganização, o conteúdo público foi movido para `tcc/public` — se preferir continuar usando o Apache do sistema, ajuste o DocumentRoot para `.../tcc/public`.
- O projeto possui um autoload local para classes `App\\` e helpers internos. O `vendor/autoload.php` do Composer é útil para ferramentas de desenvolvimento, mas não é obrigatório para executar o sistema em Windows/XAMPP.

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
