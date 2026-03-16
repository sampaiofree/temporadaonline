# LEGACY_FINANCE_EXTRATO_LEDGER.md

## Contexto

Antes desta implementação, o Legacy Finance mostrava "movimentações recentes" combinando fontes diferentes (`liga_transferencias`, `partida_folha_pagamento`, `liga_clube_patrocinios`), mas sem um livro-caixa único.

Isso causava três limitações:

1. Não era possível exibir um extrato bancário confiável com `saldo após` por linha.
2. Nem todo crédito/débito de saldo era recuperável pelas tabelas de domínio.
3. A explicação de "como chegou ao saldo atual" ficava incompleta.

## Decisão arquitetural

Foi adotado um **ledger financeiro dedicado**: `liga_clube_financeiro_movimentos`.

Princípios:

- Toda alteração de saldo em `LeagueFinanceService` gera um lançamento.
- O ledger é a fonte oficial para extrato no `legacy?view=finance`.
- Para carteiras antigas, foi criado `snapshot_abertura` na migração, sem reconstrução retroativa completa.

## Tabela de ledger

Tabela: `liga_clube_financeiro_movimentos`

Campos:

- `id`
- `liga_id` (FK `ligas.id`)
- `clube_id` (FK `liga_clubes.id`)
- `operacao` (`credit`, `debit`, `snapshot_abertura`)
- `descricao` (nullable)
- `valor` (bigint, inteiro em EUR)
- `saldo_antes` (bigint)
- `saldo_depois` (bigint)
- `metadata` (json nullable)
- `created_at`, `updated_at`

Índices:

- `liga_id + clube_id + created_at + id` (ordenação/consulta de extrato)
- `liga_id + clube_id + operacao` (filtros operacionais)

## Fluxo de gravação (service)

Arquivo principal: `app/Services/LeagueFinanceService.php`

- `initClubWallet(...)`
  - ao criar a carteira: grava `snapshot_abertura` com saldo inicial da liga.
- `credit(...)`
  - calcula `saldo_antes` e `saldo_depois`;
  - persiste carteira;
  - grava lançamento `credit`.
- `debit(...)`
  - valida saldo (quando aplicável);
  - calcula `saldo_antes` e `saldo_depois`;
  - persiste carteira;
  - grava lançamento `debit`.

Observação: tudo no mesmo contexto transacional para manter consistência do extrato.

## Estratégia de transição

Migração: `database/migrations/2026_03_09_000100_create_liga_clube_financeiro_movimentos_table.php`

- Cria a tabela e índices.
- Faz bootstrap para carteiras existentes (`liga_clube_financeiro`):
  - 1 lançamento por carteira com `operacao=snapshot_abertura`;
  - `valor=0`;
  - `saldo_antes=saldo_depois=saldo atual`.

Resultado esperado:

- clubes antigos passam a ter ponto inicial de conciliação;
- detalhamento completo passa a valer da data de ativação do ledger.

## Endpoints Legacy

### 1) Dados resumidos do Finance

`GET /legacy/finance-data`

Mudança:

- `financeiro.movimentos` agora vem do ledger e retorna no máximo 3 registros (mais recentes primeiro).
- resposta inclui:
  - `statement.ledger_activated_at` para aviso de transição.

### 2) Extrato completo paginado

`GET /legacy/finance-statement-data?confederacao_id={id}&page={n}&per_page={n}`

Resposta:

- `statement.items[]` com:
  - `id`
  - `operacao`
  - `descricao`
  - `valor`
  - `saldo_depois`
  - `created_at`
  - `metadata`
- `statement.pagination`:
  - `page`
  - `per_page`
  - `total`
  - `has_more`
- `statement.ledger_activated_at`

## UI Legacy Finance

Arquivo principal: `resources/js/legacy/index.tsx` (`FinanceView`)

Mudanças:

- seção **MOVIMENTAÇÕES RECENTES** mostra apenas 3 itens;
- botão **VER TODAS MOVIMENTAÇÕES** abre painel full-screen;
- cada linha mostra:
  - badge (`ENTRADA`, `SAÍDA`, `SALDO INICIAL`);
  - valor com sinal (`+/-`);
  - `SALDO APÓS`;
- formato monetário da movimentação/extrato:
  - `M$` com 2 casas decimais;
- aviso fixo no extrato:
  - "Detalhamento completo disponível a partir de ...".

## Contratos adicionados no config Legacy

`legacyConfig` ganhou:

- `financeStatementDataUrl`

Usado no frontend para paginação do extrato completo.

## Checklist de validação

Backend:

- `initClubWallet` cria snapshot inicial uma única vez por carteira.
- `credit/debit` registram `saldo_antes` e `saldo_depois` corretos.
- `finance-data` retorna no máximo 3 movimentos.
- `finance-statement-data` pagina corretamente.

Frontend:

- botão de extrato abre painel full-screen;
- lista mostra direção + saldo após;
- `carregar mais` funciona e mantém ordem decrescente;
- aviso de transição aparece no topo do extrato.

## Riscos conhecidos

1. Sem backfill completo das operações antigas: histórico detalhado pré-ledger não é reconstruído.
2. Qualquer alteração de saldo fora de `LeagueFinanceService` não entra no ledger.

## Troubleshooting rápido

- Extrato vazio para clube antigo:
  - verificar existência de `snapshot_abertura` em `liga_clube_financeiro_movimentos`.
- Saldo divergente:
  - validar se mutações de saldo estão passando por `LeagueFinanceService`.
- Paginação inconsistente:
  - validar ordenação por `created_at DESC, id DESC`.

