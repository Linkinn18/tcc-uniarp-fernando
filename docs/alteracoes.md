P15 [Alto] A regra "uma única validação" gera falsos positivos no fluxo real da cadeia
Status: limitação assumida do protótipo

Onde:
- `public/api/validar_unicidade.php`
- `src/Service/ValidacaoService.php`
- `src/Repository/MedicamentoRepository.php`

Problema:
- Na cadeia farmacêutica real, uma mesma unidade pode ser verificada legitimamente mais de uma vez: no recebimento pelo distribuidor, na conferência da farmácia, na dispensação e até pelo consumidor final.
- No protótipo atual, a primeira leitura válida altera o status da unidade para validado/consumido.
- Qualquer leitura posterior da mesma unidade é tratada como possível clonagem e retorna alerta.

Impacto:
- Esse comportamento pode gerar falsos positivos em cenários legítimos de conferência repetida.
- Portanto, o alerta de segunda leitura não deve ser interpretado, neste protótipo, como prova conclusiva de falsificação em todos os contextos operacionais.
- Trata-se de uma simplificação deliberada para demonstrar o mecanismo de unicidade com baixo custo de implementação.

Análise da decisão:
- Este ponto não caracteriza bug de implementação.
- A regra atual está coerente com a proposta mínima do protótipo: demonstrar que apenas a primeira validação é aceita de forma atômica.
- O que ela não cobre é a complexidade operacional da cadeia farmacêutica real, na qual múltiplas leituras podem ser esperadas e legítimas.

Decisão adotada neste projeto:
- Seguir a opção C.
- Manter a regra atual de "primeira validação consome o código".
- Declarar explicitamente essa escolha como limitação do protótipo acadêmico.
- Tratar modelos mais realistas de validação como evolução futura, e não como requisito da versão atual.

Texto sugerido para documentação do projeto/monografia:
- "Este protótipo adota uma política simplificada de unicidade em que a primeira validação bem-sucedida consome o identificador da unidade. Assim, leituras posteriores do mesmo código podem gerar alerta, mesmo em situações legítimas da cadeia logística e de dispensação. Essa escolha foi mantida intencionalmente para reduzir a complexidade do modelo e demonstrar de forma objetiva o mecanismo de validação única. Portanto, os resultados deste protótipo devem ser interpretados dentro dessa limitação, não representando ainda o comportamento completo esperado em um ambiente produtivo da cadeia farmacêutica."

Trabalhos futuros recomendados:
- Opção A: validação por etapa ou perfil de ator (distribuidor, farmácia, consumidor).
- Opção B: registro de múltiplas leituras e detecção de anomalias por contexto, tempo, local e estágio do fluxo.

Conclusão:
- O P15 permanece válido como observação de desenho de sistema.
- Não haverá alteração de código nesta etapa.
- O tratamento adotado é documental: registrar a restrição como limitação explícita do protótipo.


P17 [Médio] Esquema simplificado demais e nomes que não correspondem ao dado armazenado
Status: parcialmente corrigido

Onde:
- `schema.sql`
- `src/Repository/MedicamentoRepository.php`
- `src/Service/AssinaturaService.php`
- `public/fabricante.php`
- `public/index.php`

Problema original:
- O campo `data_fabricacao` armazenava na prática a data de cadastro/emissão.
- A interface tratava o envio como "cadastro de lote", embora cada operação gerasse uma unidade individual.
- Não havia índices de apoio às buscas por nome e lote.
- O item também apontava limitações mais amplas de domínio: ausência de entidade fabricante, ausência de validade e modelo simplificado de lote/unidade.

Correções aplicadas nesta etapa:
- Renomeação do campo persistido de `data_fabricacao` para `criado_em`, com migração automática para bases SQLite existentes ao executar `php bin/setup_db.php`.
- Ajuste da camada de repositório e da API de busca para usar `criado_em` como data de cadastro/emissão.
- Ajuste da interface do fabricante para deixar explícito que o protótipo gera uma unidade e apenas associa um lote informado.
- Ajuste da tela de consulta para refletir busca por unidades emitidas.
- Criação de índices `idx_medicamentos_nome` e `idx_medicamentos_lote`.

Pontos que já não se aplicam mais:
- O item sobre `status TINYINT` ficou obsoleto: o schema atual em SQLite já usa `INTEGER`.
- O item sobre assinatura armored/PGP também ficou desatualizado: o sistema atual trabalha com assinatura em base64, não com bloco armored PGP.

Limitações que permanecem:
- O modelo ainda não possui entidade própria para fabricante/laboratório com múltiplas chaves públicas.
- O lote continua sendo um atributo textual da unidade, e não uma entidade separada do domínio.
- Ainda não existe campo de validade do medicamento.

Conclusão:
- O P17 foi corrigido parcialmente nas inconsistências mais imediatas de nomenclatura e desempenho.
- As limitações estruturais restantes devem ser tratadas como evolução futura do modelo de domínio, não como bug pontual de implementação.


P25 [Médio] QR Code muito denso e com o menor nível de correção de erro
Status: parcialmente corrigido

Onde:
- `public/assets/js/qr.js`
- `public/fabricante.php`
- `public/index.php`
- `public/validador.php`

Problema original:
- O QR era gerado com `correctLevel: L`, o menor nível de correção de erro.
- O payload era serializado em JSON, acrescentando overhead textual desnecessário.
- O diagnóstico antigo mencionava assinatura armored/PGP, mas isso já não corresponde ao código atual: hoje a assinatura é transmitida em base64.

Situação atual:
- Mesmo sem bloco armored PGP, a assinatura RSA-2048 em base64 ainda produz payload relativamente grande.
- Em etiqueta física, isso continua sendo um fator de risco para leitura em cenários com baixa qualidade de impressão ou dano parcial.

Correções aplicadas nesta etapa:
- O formato do QR foi reduzido de JSON para payload compacto em query string (`i=...&s=...`).
- O nível de correção de erro foi elevado de `L` para `M`.
- O validador passou a entender tanto o formato legado em JSON quanto o novo formato compacto.
- O validador também passou a aceitar validação automática quando acessado com parâmetros na URL.

Limitações que permanecem:
- A assinatura RSA-2048 em base64 ainda gera payload maior do que alternativas modernas como Ed25519.
- O QR ainda não usa uma URL pública curta e definitiva de produção; o formato atual foi compactado sem acoplar o protótipo a um domínio específico.
- Ainda não há medição quantitativa registrada de versão do QR, densidade, tamanho físico mínimo e taxa de leitura sob dano.

Trabalhos futuros recomendados:
- Adotar assinatura menor (por exemplo, Ed25519) para reduzir drasticamente o payload.
- Evoluir para QR com URL curta pública de validação em ambiente de produção.
- Medir experimentalmente legibilidade, tamanho mínimo e tolerância a dano para incluir resultados quantitativos na monografia.

Conclusão:
- O P25 foi mitigado de forma prática no protótipo atual, mas não eliminado por completo.
- O principal ganho desta etapa foi reduzir overhead do payload e melhorar a robustez de leitura sem alterar o modelo criptográfico central.