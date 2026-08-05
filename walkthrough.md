# Walkthrough: Sistema Criptográfico de Medicamentos

O desenvolvimento do sistema de autenticação e controle de duplicidade de medicamentos foi concluído com sucesso e implementado no diretório solicitado.

## 🚀 Como acessar
O projeto foi criado na sua área de trabalho e foi feito um link seguro para o servidor web.
- **Link Local:** Acesse [http://localhost/tcc_medicamentos/](http://localhost/tcc_medicamentos/) no seu navegador (Certifique-se de que o Apache e MySQL no XAMPP estão rodando).

> [!IMPORTANT]
> **Inicialização do Banco de Dados:**
> Antes do primeiro uso, você precisa importar o banco de dados.
> Você pode abrir o `http://localhost/phpmyadmin/`, criar um banco de dados chamado `tcc_medicamentos` e importar o arquivo `schema.sql` (localizado na pasta do projeto).

## 🧩 Funcionalidades Implementadas

### 1. Geração de Chaves (Setup Inicial)
- **Onde:** Página `gerar_chaves.html` (Acessível via tela inicial caso as chaves não existam).
- **O que faz:** Utiliza **OpenPGP.js** no navegador para gerar um par seguro de Chaves RSA (2048 bits) e salva a Chave Pública e a Privada localmente na pasta `keys/` no servidor. Isso simula o certificado digital do laboratório.

### 2. Módulo do Fabricante (Back-office)
- **Onde:** Página `fabricante.php`
- **O que faz:**
  1. Captura o Nome e o Lote do medicamento.
  2. Gera um ID Único (UUID v4) para a unidade.
  3. Carrega a Chave Privada do laboratório e, utilizando o padrão OpenPGP, gera uma assinatura digital desanexada para aquele ID único.
  4. Salva o registro no Banco de Dados via API PHP (`api/salvar_medicamento.php`).
  5. Plota na tela um **QR Code** estruturado contendo o ID e a respectiva Assinatura PGP.

### 3. Aplicativo Validador (Hospital / Consumidor)
- **Onde:** Página `validador.php`
- **O que faz:**
  1. Acessa a câmera do dispositivo via HTML5 (funciona no PC com webcam ou no celular pela mesma rede local).
  2. Escaneia o QR Code gerado pelo fabricante.
  3. **Prova Criptográfica:** O front-end carrega a Chave Pública e valida, de forma *descentralizada*, se a assinatura PGP no QR Code é autêntica e corresponde ao ID lido. Se houver divergência, acusa Falsificação imediatamente (não chegou nem a ir ao banco de dados).
  4. **Prova de Unicidade:** Se a assinatura for válida, envia o ID para a API (`api/validar_unicidade.php`), que verifica se o código já foi consumido. Se não foi, autentica e marca como lido. Se for uma leitura repetida, emite **ALERTA DE CLONAGEM/DUPLICIDADE**.

## 📱 Dica para Apresentação do TCC
Para demonstrar o leitor de QR Code usando o celular:
1. Certifique-se de que o computador e o celular estão na mesma rede Wi-Fi.
2. Descubra o IP do seu computador (abra o Prompt de Comando e digite `ipconfig`, anote o IPv4).
3. No celular, acesse `http://SEU_IP/tcc_medicamentos/validador.php`.
4. Gere um QR Code no computador (`fabricante.php`) e aponte a câmera do celular para a tela!

> [!TIP]
> Caso acesse via IP no celular, certifique-se de usar `https://` (se configurado no XAMPP) ou as configurações de flag locais do Chrome para permitir que o navegador acesse a câmera em origens não-HTTPS em testes locais. (Ex: `chrome://flags/#unsafely-treat-insecure-origin-as-secure`).
