# Sistema Criptográfico de Autenticação e Verificação de Unicidade de Medicamentos

Este documento detalha o plano de implementação para o sistema proposto em seu TCC, visando combater a falsificação de medicamentos utilizando criptografia de chaves públicas (PGP/RSA/ECC) e controle de unicidade por QR Code.

## 🚨 User Review Required

Antes de iniciarmos a codificação, preciso da sua aprovação e que responda a algumas perguntas abertas importantes abaixo para definirmos o escopo final e as tecnologias exatas.

## ❓ Open Questions (Dúvidas)

Conforme solicitado, seguem as dúvidas para elaboração detalhada:

1. **Local do Projeto:** Posso criar o projeto dentro da pasta do XAMPP (ex: `C:\xampp\htdocs\tcc_medicamentos`) para rodar nativamente com PHP?
2. **Banco de Dados:** O XAMPP já possui MariaDB/MySQL. Podemos utilizar essa estrutura de banco de dados para a tabela de controle de unicidade (medicamentos e status)? Ou prefere SQLite por ser mais portátil?
3. **Geração de Chaves e Assinatura:** O texto menciona OpenPGP no Back-end com PHP e validação descentralizada com `OpenPGP.js`. Podemos gerar as assinaturas no PHP (usando a extensão gnupg ou via chamada de sistema para o gpg) e realizar a validação via web (Front-end) utilizando a biblioteca `OpenPGP.js` junto com a Chave Pública, simulando o app descentralizado?
4. **Design e Interface:** Posso adotar um framework CSS leve ou Bootstrap para acelerar o desenvolvimento e entregar uma interface esteticamente agradável e responsiva, ou prefere CSS puro conforme sugerem minhas diretrizes padrões?

## 🛠 Proposed Changes (Arquitetura Proposta)

O sistema será dividido em 3 pilares fundamentais, desenvolvidos em PHP, HTML, CSS e JavaScript (OpenPGP.js).

### Módulo do Fabricante (Back-office)
- **Painel de Geração:** Interface onde o fabricante informará o lote e detalhes do medicamento.
- **Assinatura Digital:** Ao gerar o medicamento, o PHP utilizará uma **Chave Privada (PGP)** para criar uma assinatura digital única vinculada a um identificador (ID) do medicamento.
- **Geração de QR Code:** A assinatura digital e o ID do produto serão codificados em um QR Code.
- **Banco de Dados:** O ID e os dados do medicamento serão salvos no banco de dados com o status de "Não Validado" (evitando clonagem/duplicidade).

### Banco de Dados (Controle de Duplicidade)
- **Tabela `medicamentos`**: ID (UUID), Nome, Lote, Assinatura Digital, Status (0 = Não validado, 1 = Validado), Data de Criação, Data de Validação.

### Aplicação de Validação (Hospital / Consumidor Final)
- **Leitor de QR Code:** Uma página (PWA/Mobile-friendly) usando a câmera do dispositivo via HTML5/JS para ler o QR Code.
- **Prova Criptográfica (Front-end):** Utilização do `OpenPGP.js` junto com a **Chave Pública** do fabricante, embutida no aplicativo, para validar se a assinatura lida no QR Code é autêntica e foi gerada pelo laboratório verdadeiro.
- **Prova de Unicidade (Back-end/API):** Após passar na verificação criptográfica, o sistema faz uma requisição AJAX para o PHP verificando se o código já foi consumido/validado antes. Se o status estiver como "Não Validado", ele é autenticado e consumido (passando para "Validado"). Se já estiver como "Validado", emite um alerta de **Falsificação/Clonagem Detectada**.

---

## 🧪 Verification Plan

1. **Testes do Módulo Fabricante:** Gerar um medicamento e verificar a gravação no Banco de Dados e a emissão do QR Code.
2. **Teste de Autenticidade (Caso de Sucesso):** Ler o QR Code recém-criado na tela de validação e obter a mensagem de medicamento autêntico, verificando se o status no Banco de Dados mudou para validado.
3. **Teste de Clonagem/Duplicidade:** Tentar ler o MESMO QR Code uma segunda vez na tela de validação. O sistema deve identificar que, embora a criptografia seja válida, o item já foi validado anteriormente, acusando possível clonagem.
4. **Teste de Assinatura Falsa:** Gerar um QR Code com uma assinatura que não corresponda à chave privada original do laboratório. O front-end (OpenPGP.js) deve acusar assinatura inválida e negar a validação imediatamente.

Aguardo sua aprovação e respostas às perguntas para prosseguir com o desenvolvimento!
