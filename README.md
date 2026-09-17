# Sistema Criptográfico de Autenticação e Controle de Duplicidade

Este projeto visa mitigar os danos causados pela venda de medicamentos falsificados e adulterados por meio de uma plataforma de validação de autenticidade baseada em criptografia de chaves públicas. O foco é garantir a unicidade do produto e a integridade da origem, combatendo diretamente a clonagem de embalagens e códigos.

## 📌 O Problema

A circulação de produtos médicos falsificados é uma crise de saúde global. Estima-se que pelo menos **1 em cada 10 medicamentos** em países de baixa e média renda sejam falsificados ou abaixo do padrão. 

*   **Impacto na Saúde:** Medicamentos falsificados podem conter ingredientes tóxicos, dosagens incorretas ou ser totalmente ineficazes, levando à falha no tratamento, aumento da resistência antimicrobiana e até a morte.
*   **Vulnerabilidade Técnica:** Códigos de barras tradicionais e etiquetas físicas são facilmente clonados, não oferecendo garantias reais de que o item em mãos é o mesmo que saiu da fábrica.
*   **Custo Econômico:** Governos e consumidores perdem bilhões anualmente em produtos que não apenas falham em curar, mas sobrecarregam os sistemas de saúde com complicações evitáveis.

## 🚀 A Solução

O sistema abandona a dependência exclusiva de etiquetas físicas frágeis e implementa um mecanismo de prova de autenticidade digital para cada unidade produzida.

### Principais Funcionalidades

1. **Assinatura digital única:** o laboratório fabricante gera uma assinatura criptográfica para cada item, garantindo que apenas ele poderia ter emitido aquele identificador.
2. **Verificação de duplicidade:** o banco monitora se um código legítimo está sendo validado múltiplas vezes, ajudando a detectar clonagem.
3. **Validação descentralizada:** hospitais e consumidores podem validar a procedência do medicamento usando a chave pública do fabricante.
4. **Minimização de danos:** ao detectar código duplicado ou assinatura inválida, o sistema sinaliza possível falsificação.

## ✅ Fluxo Atual

- O acesso do fabricante exige login com sessão PHP e proteção CSRF.
- O navegador não gera mais identificadores nem assina dados localmente.
- A emissão acontece no servidor: o cliente envia apenas nome e lote; o backend gera o UUID, assina e devolve o conteúdo do QR Code.
- A geração de chaves não acontece mais pelo site.
- O endpoint web de setup de chaves foi desativado.
- As chaves são geradas apenas por linha de comando e armazenadas fora do diretório público.

## 🔐 Configuração Inicial

1. Copie [.env.example](.env.example) para `.env`.
2. Defina no `.env` os valores de `TCC_ADMIN_USER`, `TCC_ADMIN_PASSWORD` e `TCC_KEY_PASSPHRASE`.
3. Ajuste `TCC_STORAGE_PATH` para um diretório fora do web root e gravável pelo usuário do servidor web.
4. Gere o par de chaves com o script CLI `bin/gerar_chaves.php`.
5. Acesse [login.php](login.php) para entrar no módulo do fabricante.

Exemplo de `.env`:

```env
TCC_ADMIN_USER=fabricante
TCC_ADMIN_PASSWORD=troque-esta-senha
TCC_KEY_PASSPHRASE=troque-esta-passphrase
TCC_STORAGE_PATH=/tmp/tcc-storage
```

## 🖥️ Primeira execução em outro computador

Ao clonar ou copiar o projeto para outra máquina, o fluxo esperado é:

1. Garantir que PHP 8 com OpenSSL e MySQL/MariaDB estejam disponíveis.
2. Copiar [.env.example](.env.example) para `.env` e preencher as credenciais.
3. Definir um `TCC_STORAGE_PATH` fora do diretório público.
4. Executar o script de geração de chaves com o usuário que roda o servidor web, ou com um usuário que tenha permissão de escrita nesse diretório.
5. Abrir [index.php](index.php); o banco e as tabelas são criados automaticamente em [db.php](db.php), e o usuário inicial também é provisionado automaticamente se o `.env` estiver preenchido.

No ambiente XAMPP deste projeto, o usuário do Apache é `daemon`, então o comando costuma ser:

```bash
sudo -u daemon /opt/lampp/bin/php /opt/lampp/htdocs/tcc/bin/gerar_chaves.php
```

Em outros ambientes, substitua `daemon` pelo usuário real do servidor web.

## 🔑 Geração de chaves

- Página de instruções: [gerar_chaves.html](gerar_chaves.html)
- Script real de geração: [bin/gerar_chaves.php](bin/gerar_chaves.php)
- Endpoint HTTP desativado: [api/setup_chaves.php](api/setup_chaves.php)

Se as chaves já existirem e você quiser regenerá-las:

```bash
/opt/lampp/bin/php /opt/lampp/htdocs/tcc/bin/gerar_chaves.php --force
```

## 🧱 Arquitetura Atual

- **Assinatura no servidor:** o navegador envia apenas nome e lote. O PHP gera o ID, assina com a chave privada e devolve o payload do QR Code.
- **Validação no cliente:** o validador usa a chave pública e a Web Crypto API do navegador para verificar a assinatura antes de consultar a unicidade.
- **Provisionamento mínimo:** o usuário do fabricante é inicializado a partir do `.env` na tabela `usuarios` com `password_hash()`.
- **Armazenamento seguro:** a chave privada não fica mais em [keys/](keys) nem é exposta em HTML.

## 📁 Arquivos principais

- [auth.php](auth.php): autenticação, sessão e CSRF.
- [bootstrap.php](bootstrap.php): leitura do `.env` e definição dos caminhos de storage.
- [crypto.php](crypto.php): geração de chaves, assinatura e leitura da chave pública.
- [db.php](db.php): conexão com o banco, criação de tabelas e provisionamento do usuário inicial.
- [fabricante.php](fabricante.php): interface autenticada do fabricante.
- [validador.php](validador.php): leitura do QR Code e validação da assinatura.
- [api/salvar_medicamento.php](api/salvar_medicamento.php): emissão de medicamento e assinatura no backend.
- [bin/gerar_chaves.php](bin/gerar_chaves.php): geração segura das chaves via CLI.

## 🛠️ Tecnologias Utilizadas

- **Criptografia:** OpenSSL no PHP para geração das chaves RSA e assinatura digital no servidor.
- **Backend:** PHP 8 com sessões, proteção CSRF e endpoints JSON.
- **Validação no cliente:** Web Crypto API no navegador para verificar a assinatura com a chave pública.
- **Banco de dados:** MySQL/MariaDB para o controle de status e duplicidade de cada unidade.
- **Interface física:** QR Code com payload contendo identificador único e assinatura digital.

## ⚠️ Observações de segurança

- O par antigo de chaves deve ser considerado comprometido se já foi versionado em Git ou exposto no diretório público.
- Remover um arquivo em um novo commit não limpa o histórico antigo; para isso, use `git filter-repo` ou recrie o repositório.
- Em produção, o ideal é armazenar a chave privada em HSM/KMS ou outro mecanismo dedicado de proteção de segredos.

## 🌍 Impacto Social

Segundo a Organização Mundial da Saúde (OMS), o combate a produtos médicos falsificados exige soluções tecnológicas que aumentem a detecção e protejam a integridade da cadeia de suprimentos. Este projeto propõe uma camada de segurança robusta e acessível, focada em fundamentos sólidos de computação para proteger o direito do consumidor e a saúde pública.
