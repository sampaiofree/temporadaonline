# Dominio: mercado

Documento funcional do dominio de mercado com base no codigo atual em 2026-03-16.

## Escopo

O mercado cobre toda movimentacao de jogadores dentro do escopo competitivo atual:
- compra de jogador livre
- venda entre clubes
- multa (roubo por clausula)
- troca direta
- proposta entre clubes
- leilao
- favoritos/radar
- devolucao do jogador ao mercado
- reajuste de valor e salario do jogador no elenco do clube

## Entidades centrais

- `Elencopadrao`
- `LigaClubeElenco`
- `LigaTransferencia`
- `LigaProposta`
- `PlayerFavorite`
- `LigaLeilao`
- `LigaLeilaoItem`
- `LigaLeilaoLance`
- `LigaClubeVendaMercado`
- `LigaClubeAjusteSalarial`

## Services principais

- `TransferService`
- `AuctionService`
- `MarketWindowService`
- `ReleaseClauseValueService`
- `LeagueFinanceService`
- `SalaryReserveGuardService`

## Escopo de posse do jogador

A posse do jogador nao e controlada diretamente em `elencopadrao`.

A fonte de verdade da posse e `liga_clube_elencos`.

Regra importante:
- se a liga tem `confederacao_id`, a posse e as operacoes consideram a confederacao inteira
- se a liga nao tem confederacao, o escopo cai para a propria liga

Na pratica, isso significa que um jogador nao pode estar em dois clubes da mesma confederacao ao mesmo tempo.

## Janelas e modos de mercado

`MarketWindowService` resolve 3 janelas:
- `LigaPeriodo`: transferencias abertas por data/hora
- `LigaLeilao`: leilao aberto por data
- `LigaRouboMulta`: multa aberta por data/hora

Modos retornados:
- `open`
- `auction`
- `closed`

Regras operacionais:
- compra, venda, multa, troca e proposta tradicional exigem mercado aberto
- se o mercado estiver em modo `auction`, operacoes tradicionais ficam bloqueadas
- multa tambem depende de janela propria (`multa_period`)
- o service tambem expoe a proxima abertura futura de transferencias, leilao e multa

## Compra de jogador livre

Entrada principal:
- `POST /api/ligas/{liga}/clubes/{clube}/comprar`
- `TransferService::buyPlayer()`

Regras:
- nao aceita preco manual
- o jogador precisa pertencer ao `jogo` da liga/confederacao
- o jogador nao pode ja estar em outro clube do mesmo escopo
- respeita limite de elenco (`max_jogadores_por_clube`)
- respeita saldo e reserva salarial
- debita o clube comprador
- cria `LigaClubeElenco`
- registra `LigaTransferencia` com tipo `jogador_livre`

Preco usado:
- `value_eur` do catalogo `elencopadrao`

## Venda entre clubes

Entrada principal:
- `POST /api/ligas/{liga}/clubes/{clube}/vender`
- `TransferService::sellPlayer()`

Regras:
- exige mercado aberto
- o jogador precisa estar ativo e pertencer ao clube vendedor
- o preco precisa respeitar `venda_min_percent`
- respeita limite de elenco e reserva salarial do comprador
- debita comprador e credita vendedor
- move a linha de `liga_clube_elencos` para o comprador
- registra `LigaTransferencia` com tipo `venda`

## Multa / clausula de rescisao

Entrada principal:
- `POST /api/ligas/{liga}/clubes/{clube}/multa`
- `TransferService::payReleaseClause()`

Regras:
- exige mercado aberto
- o jogador precisa estar ativo em outro clube do mesmo escopo
- respeita limite de elenco e reserva salarial do comprador
- debita comprador e credita clube de origem
- move a posse do jogador
- registra `LigaTransferencia` com tipo `multa`

Calculo da multa:
- `ReleaseClauseValueService` usa o multiplicador da liga quando o valor atual do elenco e igual ao valor original do catalogo
- se o jogador foi reajustado no elenco, a multa passa a usar o valor do proprio elenco

## Troca direta

Entrada principal:
- `POST /api/ligas/{liga}/clubes/{clube}/trocar`
- `TransferService::swapPlayers()`

Regras:
- exige mercado aberto
- ambos os jogadores precisam estar ativos
- cada jogador precisa realmente pertencer ao clube informado
- pode haver `ajuste_valor`
  - positivo: clube A paga clube B
  - negativo: clube B paga clube A
- a troca respeita reserva salarial dos dois lados
- registra 2 linhas em `LigaTransferencia`, uma por jogador

## Propostas entre clubes

Entradas principais:
- `GET /api/ligas/{liga}/clubes/{clube}/propostas`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas/{proposta}/aceitar`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas/{proposta}/rejeitar`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas/{proposta}/cancelar`

Regras:
- propostas ficam bloqueadas em leilao e com mercado fechado
- uma proposta pode combinar dinheiro e jogadores oferecidos
- o jogador alvo nao pode estar entre os jogadores oferecidos
- os jogadores oferecidos precisam pertencer ao clube destino da proposta
- ao aceitar:
  - dinheiro e transferido via `LeagueFinanceService`
  - jogador alvo vai para o clube destino
  - jogadores ofertados voltam para o clube origem
  - a proposta vira `aceita`
  - propostas abertas concorrentes do mesmo jogador sao canceladas
  - o historico vai para `LigaTransferencia`

## Leilao

Entrada principal:
- `POST /api/ligas/{liga}/clubes/{clube}/leiloes/lances`
- `AuctionService::placeBid()`

Regras:
- disponivel apenas quando `MarketWindowService` marca `is_auction = true`
- vale apenas para jogadores livres
- o item do leilao existe por `confederacao_id + elencopadrao_id`
- o lance inicial e `80%` do valor de mercado (`floor(value_eur * 0.8)`)
- incrementos permitidos hoje: `100k`, `200k`, `300k`, `500k`, `1M`
- cada lance prorroga o item por `300` segundos
- o lider anterior recebe reembolso automatico quando toma overbid
- o clube nao pode liderar o proprio item e relancar no mesmo item
- na expiracao, `finalizeExpiredAuctions()` fecha o item e entrega o jogador ao lider
- se houver problema na finalizacao (clube indisponivel, elenco cheio, reserva salarial, jogador nao livre), o item e cancelado com reembolso

Historico:
- a aquisicao final do leilao entra em `LigaTransferencia` como `jogador_livre` com observacao de leilao

## Favoritos / radar

Entradas:
- `GET /api/ligas/{liga}/favoritos`
- `POST /api/ligas/{liga}/favoritos`

Regras:
- o favorito e salvo por `user_id + (confederacao_id ou liga_id) + elencopadrao_id`
- o controller valida se o jogador pertence ao jogo da liga antes de salvar

## Devolucao ao mercado

Entrada principal:
- `POST /elenco/{elenco}/vender-mercado`
- `TransferService::releaseToMarket()`

Regras:
- o usuario precisa ser dono da linha do elenco
- se o mercado estiver fechado e o clube ja estiver com 18 jogadores ativos, a venda fica bloqueada
- o valor base da operacao vem de `LigaClubeElenco.value_eur`
- fallback apenas se esse valor estiver nulo: `Elencopadrao.value_eur`
- taxa padrao de venda: `20%`
- credito liquido para o clube: `80%`
- a linha sai de `liga_clube_elencos`
- a operacao gera audit em `LigaClubeVendaMercado`

## Reajuste de valor e salario do jogador

Entrada principal:
- `PATCH /elenco/{elenco}/valor`

Regras:
- altera `value_eur` e opcionalmente `wage_eur` na linha de `liga_clube_elencos`
- respeita `SalaryReserveGuardService` quando houver mudanca de salario
- se o salario mudar, grava `LigaClubeAjusteSalarial`
- nao gera movimentacao financeira, porque nao ha entrada/saida de caixa nessa operacao

## Superficies atuais

### Web tradicional
- `GET /liga/mercado`
- `GET /liga/mercado/propostas`
- `GET /minha_liga/meu-elenco`
- `GET /minha_liga/financeiro`

### Legacy
- `legacy?view=market`
- `legacy?view=squad`
- `legacy?view=hub-global` (historico, proximos eventos, atalhos)

### Admin
- `admin/elenco-padrao`

## Historico e observabilidade

As trilhas principais de auditoria sao:
- `LigaTransferencia`
- `LigaClubeVendaMercado`
- `LigaLeilaoLance`
- `LigaClubeAjusteSalarial`
- ledger financeiro em `LigaClubeFinanceiroMovimento`

## Decisoes atuais importantes

- a venda para o mercado credita com base no valor atual do elenco, nao no valor do catalogo mestre
- multa tem janela propria e independe da abertura geral do mercado
- em modo leilao, compra/multa/proposta tradicional ficam bloqueadas
- o Legacy e hoje a superficie mais completa para exploracao do mercado
