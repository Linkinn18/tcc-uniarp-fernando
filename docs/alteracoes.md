P13 [Médio] API sempre responde HTTP 200, mesmo em erro
Status: corrigido

Contexto original:
- Os endpoints distinguiam sucesso e falha principalmente pelo campo `success` no JSON, sem refletir corretamente o status HTTP.

Risco:
- Dificulta uso de `response.ok`, observabilidade, testes automatizados e tratamento correto de erro por clientes HTTP.

Situação atual:
- As rotas ativas já usam códigos semânticos de resposta.
- O helper de API foi centralizado para permitir resposta JSON com status explícito.
- Endpoints antigos mantidos por compatibilidade respondem `410 Gone` quando foram substituídos.

Correção aplicada:
- Centralização de `json_response($payload, $status)`.
- Uso consistente de `400`, `401`, `403`, `405`, `409`, `419`, `500` e outros conforme o cenário.

Arquivos impactados:
- `public/api/_bootstrap.php`
- `public/api/salvar_medicamento.php`
- `public/api/buscar_medicamentos.php`
- `public/api/validar_unicidade.php`
- `auth.php`
- `api/salvar_medicamento.php`
- `api/buscar_medicamentos.php`
- `api/validar_unicidade.php`

---

P14 [Baixo] Supressão de erros com @ e caminhos relativos ao diretório de trabalho
Status: corrigido

Contexto original:
- Havia uso de supressão com `@` e inclusões relativas do tipo `require_once '../db.php'`, dependentes do diretório de trabalho do processo.

Risco:
- Diagnóstico mais difícil em falhas de permissão ou caminho.
- Fragilidade fora do fluxo do Apache, como CLI, testes e outras configurações de execução.

Situação atual:
- Os usos antigos de `@file_get_contents` apontados no relatório não existem mais no código atual.
- Ainda restavam resíduos reais: includes relativos em endpoints legados e supressão em criação de diretório/ajuste de ownership.

Correção aplicada:
- Substituição de includes relativos por caminhos baseados em `__DIR__`.
- Remoção da supressão com `@mkdir`, com erro explícito ao falhar.
- Remoção da supressão em `chown`/`chgrp`, com registro em log quando o ajuste não é possível.

Arquivos impactados:
- `api/auditoria.php`
- `api/setup_chaves.php`
- `src/Database.php`
- `bootstrap.php`

Conclusão geral

- P13: corrigido.
- P14: corrigido.

Estado final:
- Os pontos P13 e P14 estão encerrados para a arquitetura atual do projeto.