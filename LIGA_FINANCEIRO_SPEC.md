# LIGA_FINANCEIRO_SPEC.md

## Objetivo

Implementar sistema de **elenco único por liga**, com:

* limite de **18 jogadores por clube**
* **saldo inicial** por liga
* **compra / venda / troca / multa**
* **salário** (cobrança por rodada)
* histórico de transferências
* regras anti-brecha

---

## Entidades existentes

* `ligas`
* `liga_jogador` (pivot liga x player)
* `liga_clubes`
* `liga_clube_elenco` (posse do jogador dentro da liga)

---

## Novos campos em `ligas`

Adicionar:

* `max_jogadores_por_clube` (int, default 18)
* `saldo_inicial` (bigint, default 0)
* `multa_multiplicador` (decimal 4,2, default 2.00)
* `cobranca_salario` (enum: `rodada`, default `rodada`)
* `venda_min_percent` (int, default 100)  // 100 = 100% do value_eur (não vende abaixo)
* `bloquear_compra_saldo_negativo` (bool, default true)

---

## Tabelas novas

### 1) `liga_clube_financeiro`

1 registro por clube em cada liga.

Campos:

* `id`
* `liga_id` (fk)
* `clube_id` (fk)
* `saldo` (bigint)  // em EUR (inteiro)
* timestamps

Índices:

* unique `(liga_id, clube_id)`

---

### 2) `liga_transferencias`

Histórico de tudo.

Campos:

* `id`
* `liga_id`
* `jogador_id`
* `clube_origem_id` (nullable)
* `clube_destino_id`
* `tipo` enum: `compra`, `venda`, `troca`, `multa`, `jogador_livre`
* `valor` (bigint)
* `observacao` (nullable string)
* timestamps

Índices:

* index `(liga_id, jogador_id)`
* index `(liga_id, clube_destino_id)`
* index `(liga_id, clube_origem_id)`

---

### 3) `liga_folha_pagamento` (opcional, mas recomendado)

Pra registrar cobrança por rodada.

Campos:

* `id`
* `liga_id`
* `rodada` (int)
* `clube_id`
* `total_wage` (bigint)
* timestamps

Índices:

* unique `(liga_id, rodada, clube_id)`

---

## Ajustes em `liga_clube_elenco`

Adicionar campos:

* `value_eur` (bigint)  // snapshot do valor no momento que entrou no clube
* `wage_eur` (bigint)   // snapshot do salário
* `ativo` (bool default true)

Regras de banco (CRÍTICO):

* unique `(liga_id, jogador_id)`  // garante jogador único na liga

---

## Regras de Negócio

### Regra R1 — Limite de elenco

Um clube **não pode ter mais que** `ligas.max_jogadores_por_clube`.

Validação:

* antes de qualquer entrada de jogador:

  * `count(elenco_do_clube) < max`

Exceção:

* em troca “1 por 1” você pode permitir, desde que o total final não passe do limite.

---

### Regra R2 — Venda mínima

Preço mínimo = `value_eur * (venda_min_percent/100)`

Default: 100% (não vende abaixo do valor da planilha/snapshot).

---

### Regra R3 — Multa (cláusula)

Multa padrão:

* `multa = value_eur * ligas.multa_multiplicador`

Pagamento:

* comprador paga tudo
* clube atual recebe tudo
* clube atual **não pode recusar**

---

### Regra R4 — Salário

Cobrança por rodada:

* `total = sum(wage_eur dos jogadores ativos do clube)`
* debita do saldo

Regra de segurança:

* se saldo ficar negativo:

  * bloquear compra (se `bloquear_compra_saldo_negativo = true`)
  * (opcional) punição: perda de pontos / multa extra

---

### Regra R5 — Anti “jogador duplicado”

Garantir via índice único:

* `(liga_id, jogador_id)` em `liga_clube_elenco`

---

## Serviços (Laravel)

### Service 1: `LeagueFinanceService`

Responsável por:

* criar saldo inicial por clube ao entrar na liga
* debitar/creditar saldo com transação

Métodos:

* `initClubWallet(ligaId, clubeId)`
* `credit(ligaId, clubeId, amount, reason)`
* `debit(ligaId, clubeId, amount, reason)`
* `getSaldo(ligaId, clubeId)`

Regras:

* sempre usar `DB::transaction()`
* validar saldo suficiente antes de debitar (exceto salário, se você permitir negativo)

---

### Service 2: `TransferService`

Responsável por compra/venda/troca/multa.

Métodos:

* `buyPlayer(ligaId, compradorClubeId, jogadorId, priceOptional=null)`
* `sellPlayer(ligaId, vendedorClubeId, compradorClubeId, jogadorId, price)`
* `swapPlayers(ligaId, clubeA, jogadorA, clubeB, jogadorB, ajusteValor=0)`
* `payReleaseClause(ligaId, compradorClubeId, jogadorId)`

Validações obrigatórias (todas as ações):

1. jogador pertence à liga
2. jogador existe e está ativo
3. jogador está em algum clube (origem) ou está livre
4. limite de elenco do clube destino
5. saldo suficiente do comprador (se regra ativa)
6. update de posse do jogador deve ser 1 operação atômica (transaction)

Registro obrigatório:

* sempre inserir em `liga_transferencias`

---

### Service 3: `PayrollService`

Métodos:

* `chargeRound(ligaId, rodadaNumero)`

  * para cada clube:

    * calcula total_wage
    * debita saldo
    * grava em `liga_folha_pagamento`

Evitar duplicidade:

* se já existe folha da rodada para o clube, não cobrar de novo.

---

## Fluxos

### Fluxo F1 — Player entra na liga / cria clube

Ao criar `liga_clubes`:

1. criar/garantir `liga_clube_financeiro` com saldo = `ligas.saldo_inicial`
2. (se houver elenco inicial) inserir jogadores no `liga_clube_elenco` com snapshot value/wage

---

### Fluxo F2 — Compra de jogador livre

1. validar limite 18
2. preço = snapshot do jogador (ou `value_eur` original)
3. debitar saldo do comprador
4. inserir em `liga_clube_elenco` (liga, clube, jogador) com snapshot value/wage
5. registrar em `liga_transferencias` (`tipo=jogador_livre` ou `compra`)

---

### Fluxo F3 — Compra de outro clube

1. validar dono atual
2. validar preço mínimo (R2)
3. debitar comprador
4. creditar vendedor
5. mover posse (update `clube_id` em `liga_clube_elenco`)
6. registrar em `liga_transferencias` (`tipo=venda`)

---

### Fluxo F4 — Multa

1. dono atual é obrigatório
2. calcular multa = value_eur * multa_multiplicador
3. debitar comprador
4. creditar clube atual
5. mover posse
6. registrar em `liga_transferencias` (`tipo=multa`)

---

### Fluxo F5 — Salário por rodada

1. rodou a rodada X
2. payroll: soma wages
3. debita saldo
4. registra `liga_folha_pagamento`

---

## Controller / Actions (sugestão)

* `POST /ligas/{liga}/clubes/{clube}/comprar` (jogador_id)
* `POST /ligas/{liga}/clubes/{clube}/vender` (jogador_id, comprador_clube_id, price)
* `POST /ligas/{liga}/clubes/{clube}/multa` (jogador_id)
* `POST /ligas/{liga}/rodadas/{rodada}/cobrar-salarios`

Sempre:

* validar permissions (clube pertence ao usuário)
* usar FormRequest
* chamar Services

---

## Permissões (anti-brecha)

* usuário só pode operar o clube dele
* admin pode operar todos (se você quiser)

---

## Testes mínimos (Feature)

1. não permite comprar se já tem 18
2. não permite jogador duplicado na liga
3. compra debita saldo corretamente
4. venda credita vendedor corretamente
5. multa move jogador mesmo se vendedor “não quiser”
6. salário cobra só 1x por rodada

