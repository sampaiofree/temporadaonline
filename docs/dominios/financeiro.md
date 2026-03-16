# Dominio: financeiro

Documento funcional do dominio financeiro com base no codigo atual em 2026-03-16.

## Escopo

O dominio financeiro cobre:
- carteira do clube
- ledger financeiro
- credito/debito por eventos do sistema
- salario agregado do elenco
- patrocinio
- ganhos por partida
- impacto financeiro de mercado, multa, troca, proposta e leilao
- extrato resumido e extrato completo no Legacy

## Entidades centrais

- `LigaClubeFinanceiro`
- `LigaClubeFinanceiroMovimento`
- `LigaClubePatrocinio`
- `PartidaFolhaPagamento`
- `LigaFolhaPagamento`
- `LigaClubeVendaMercado`
- `LigaClubeAjusteSalarial`

## Fonte de verdade

A fonte de verdade do saldo atual e:
- `LigaClubeFinanceiro.saldo`

A fonte de verdade do historico financeiro e:
- `LigaClubeFinanceiroMovimento`

Responsavel por gravar as mutacoes:
- `LeagueFinanceService`

## Inicializacao da carteira

`LeagueFinanceService::initClubWallet()`:
- cria a carteira do clube com `saldo_inicial` da liga
- grava um movimento `snapshot_abertura`
- esse snapshot e o ponto inicial de conciliacao do ledger

## Operacoes basicas

### Credito

Metodo:
- `LeagueFinanceService::credit()`

Comportamento:
- trava a carteira
- soma o valor
- grava `LigaClubeFinanceiroMovimento` com `operacao = credit`
- registra `saldo_antes`, `saldo_depois` e `metadata`

### Debito

Metodo:
- `LeagueFinanceService::debit()`

Comportamento:
- trava a carteira
- impede saldo negativo por padrao
- grava `LigaClubeFinanceiroMovimento` com `operacao = debit`
- registra `saldo_antes`, `saldo_depois` e `metadata`

## Eventos financeiros padronizados

`LeagueFinanceService` hoje reconhece estes `event_key`:
- `market_buy_free`
- `market_sell_release`
- `transfer_buy`
- `transfer_sell`
- `release_clause_paid`
- `release_clause_received`
- `trade_adjustment_paid`
- `trade_adjustment_received`
- `proposal_paid`
- `proposal_received`
- `auction_bid`
- `auction_refund_outbid`
- `auction_refund_cancelled`
- `match_reward`
- `sponsorship_claim`
- `round_payroll_legacy`

Esses eventos sao usados para gerar descricoes legiveis no extrato.

## Entradas e saidas de dinheiro por dominio

### Mercado

- compra de jogador livre: debito
- venda entre clubes: debito do comprador, credito do vendedor
- multa: debito do comprador, credito do clube de origem
- troca com ajuste: debito de um lado, credito do outro
- proposta aceita com dinheiro: debito do comprador, credito do vendedor
- leilao:
  - lance: debito do lider atual
  - overbid: reembolso do lider anterior
  - cancelamento do item: reembolso do lider
- devolucao ao mercado:
  - credito liquido de 80%
  - taxa padrao de 20%

### Patrocinio

- resgate de patrocinio gera credito
- o evento entra no ledger como `sponsorship_claim`

### Partidas

- ganhos por partida entram por `PartidaFolhaPagamento`
- a descricao no ledger usa `match_reward`
- W.O. e placar confirmado podem disparar a cobranca/premiacao automaticamente via fluxo da partida

## Reserva salarial

O projeto nao olha apenas o saldo atual. Operacoes de mercado e reajuste de salario passam por `SalaryReserveGuardService`.

Ideia central:
- o clube nao pode assumir um jogador ou ajuste de salario se a reserva salarial futura estourar o caixa conforme a regra atual do sistema

Essa validacao e aplicada em:
- compra de jogador livre
- multa
- troca
- proposta aceita
- lance em leilao
- reajuste de salario no elenco
- finalizacao de leilao

## Salario do elenco

A soma atual do custo do elenco vem de:
- `LigaClubeElenco.wage_eur` dos jogadores ativos do clube

Superficies que usam esse valor:
- `minha_liga/financeiro`
- `legacy/finance-data`
- validacoes de reserva salarial

Observacao importante:
- reajustar `wage_eur` nao mexe no saldo na hora
- o reajuste apenas altera a reserva futura e grava `LigaClubeAjusteSalarial`

## Extrato financeiro no Legacy

Endpoints:
- `GET /legacy/finance-data`
- `GET /legacy/finance-statement-data`

### `finance-data`
Traz resumo para a home do financeiro:
- `saldo`
- `salarioPorRodada`
- `rodadasRestantes`
- 3 ultimos movimentos do ledger
- patrocinio resgatado
- ganhos recentes por partida
- `ledger_activated_at`

### `finance-statement-data`
Traz extrato paginado completo:
- `items`
- `pagination`
- `ledger_activated_at`

## Financeiro na web tradicional

Tela:
- `GET /minha_liga/financeiro`

Entrega:
- saldo atual
- salario agregado do elenco
- rodadas restantes estimadas
- historico recente de transferencias
- historico recente de partida
- patrocinio resgatado

## Patrocinio e torcida

Entrada principal:
- `MinhaLigaController::claimPatrocinio()`

Regras:
- o usuario precisa ter clube na liga
- a elegibilidade depende do total de torcida/fas
- hoje o total de fas vem principalmente da soma de conquistas resgatadas naquela confederacao
- quando resgata:
  - credita `patrocinio.valor`
  - grava `LigaClubePatrocinio.claimed_at`
  - retorna movimento sintetico para a UI

Observacao:
- `claimConquista()` nao credita dinheiro; ela afeta progressao e torcida

## Folha por rodada

Endpoint existente:
- `POST /api/ligas/{liga}/rodadas/{rodada}/cobrar-salarios`

Estado atual:
- retorna `410`
- mensagem: cobranca por rodada desativada

Interpretacao atual:
- o modelo financeiro de cobranca manual por rodada foi descontinuado
- a cobranca/premiacao relevante hoje esta mais ligada ao fluxo automatico das partidas

## Auditorias auxiliares

### `LigaClubeVendaMercado`
Guarda auditoria de devolucao de jogador ao mercado:
- valor base
- credito liquido
- taxa aplicada

### `LigaClubeAjusteSalarial`
Guarda auditoria de reajuste salarial no elenco.

### `PartidaFolhaPagamento`
Guarda eventos financeiros associados a partidas.

## Decisoes atuais importantes

- o ledger e a fonte oficial para extrato e conciliacao
- toda mutacao relevante de saldo deve passar por `LeagueFinanceService`
- a UI Legacy mostra apenas resumo; o extrato completo fica no endpoint dedicado
- a venda de jogador ao mercado credita pelo valor atual do elenco, nao pelo valor original do catalogo
