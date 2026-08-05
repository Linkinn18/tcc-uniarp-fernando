# Sistema Criptográfico de Autenticação e Controle de Duplicidade

Este projeto visa mitigar os danos causados pela venda de medicamentos falsificados e adulterados através de uma plataforma de **validação de autenticidade** baseada em criptografia de chaves públicas. Diferente de sistemas de rastreabilidade convencionais, o foco aqui é garantir a **unicidade** do produto e a **integridade** da origem, combatendo diretamente a clonagem de embalagens e códigos.

## 📌 O Problema

A circulação de produtos médicos falsificados é uma crise de saúde global. Estima-se que pelo menos **1 em cada 10 medicamentos** em países de baixa e média renda sejam falsificados ou abaixo do padrão. 

*   **Impacto na Saúde:** Medicamentos falsificados podem conter ingredientes tóxicos, dosagens incorretas ou ser totalmente ineficazes, levando à falha no tratamento, aumento da resistência antimicrobiana e até a morte.
*   **Vulnerabilidade Técnica:** Códigos de barras tradicionais e etiquetas físicas são facilmente clonados, não oferecendo garantias reais de que o item em mãos é o mesmo que saiu da fábrica.
*   **Custo Econômico:** Governos e consumidores perdem bilhões anualmente em produtos que não apenas falham em curar, mas sobrecarregam os sistemas de saúde com complicações evitáveis.

## 🚀 A Solução

O sistema abandona a dependência exclusiva de etiquetas físicas frágeis e implementa um mecanismo de **"prova de vida" digital** para cada unidade produzida.

### Principais Funcionalidades

1.  **Assinatura Digital Única (PGP/RSA):** O laboratório fabricante gera uma assinatura criptográfica para cada item, garantindo que apenas ele poderia ter "carimbado" aquele produto.
2.  **Verificação de Duplicidade (Core):** O ponto central do projeto é um banco de dados que monitora se um código original clonado está sendo validado múltiplas vezes. Isso impede que uma única assinatura legítima seja replicada em milhares de produtos falsos.
3.  **Validação Descentralizada:** Utilizando um aplicativo móvel, hospitais e consumidores finais podem validar a procedência do medicamento usando a chave pública do fabricante.
4.  **Minimização de Danos:** Ao detectar códigos duplicados ou assinaturas inválidas, o sistema atua como uma barreira crítica, prevenindo o consumo de substâncias potencialmente perigosas.

## 🛠️ Tecnologias Utilizadas

*   **Criptografia:** Bibliotecas de código aberto (OpenPGP, GnuPG ou Crypto-JS) para implementação de chaves RSA/PGP.
*   **Backend:** Python ou Node.js para gestão das assinaturas e logs de auditoria.
*   **Frontend Mobile:** React Native ou Flutter para a ferramenta de leitura e validação em campo.
*   **Banco de Dados:** PostgreSQL ou Firebase para o controle rigoroso de status e duplicidade de cada unidade.
*   **Interface Física:** Gerador de códigos 2D (QR Code ou DataMatrix) capazes de comportar a assinatura digital.

## 🌍 Impacto Social

Segundo a Organização Mundial da Saúde (OMS), o combate a produtos médicos falsificados exige soluções tecnológicas que aumentem a detecção e protejam a integridade da cadeia de suprimentos. Este projeto propõe uma camada de segurança robusta e acessível, focando em fundamentos sólidos de computação para proteger o direito do consumidor e a saúde pública.
