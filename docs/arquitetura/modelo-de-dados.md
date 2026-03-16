# Modelo de dados

Resumo arquitetural das entidades Eloquent mais relevantes do projeto, baseado em `app/Models/*.php` em 2026-03-16.

## 1. Catalogo global

### `Jogo`
Catalogo da versao do jogo (ex.: FC26). Serve de ancora para `Elencopadrao`, `Liga` e `Profile`.

### `Geracao`
Catalogo de geracao/plataforma logica usada por liga e perfil.

### `Plataforma`
Catalogo de plataforma do usuario e da competicao.

### `Pais`, `Idioma`, `Regiao`, `Playstyle`
Catalogos auxiliares usados por perfil, assets e enriquecimento do jogador.

### `AppAsset`
Centraliza assets globais do app, consumidos por `window.__APP_ASSETS__`.

## 2. Estrutura competitiva

### `Confederacao`
Escopo superior da competicao.

Campos importantes:
- `nome`
- `timezone`
- `ganho_vitoria_partida`
- `ganho_empate_partida`
- `ganho_derrota_partida`
- `jogo_id`
- `geracao_id`
- `plataforma_id`

Relacoes principais:
- `ligas()`
- `temporadas()`
- `periodos()`
- `leiloes()`
- `roubosMulta()`

Observacao importante: as janelas operacionais ficam na confederacao, nao na liga.

### `Liga`
Entidade principal da competicao jogavel.

Campos importantes:
- `nome`, `descricao`, `regras`
- `tipo`, `status`
- `max_times`
- `max_jogadores_por_clube`
- `saldo_inicial`
- `multa_multiplicador`
- `cobranca_salario`
- `venda_min_percent`
- `bloquear_compra_saldo_negativo`
- `confederacao_id`, `jogo_id`, `geracao_id`, `plataforma_id`

Relacoes principais:
- `confederacao()`
- `jogo()`
- `geracao()`
- `plataforma()`
- `users()` via `liga_jogador`
- `clubes()`
- `transferencias()`
- `periodos()` por `confederacao_id`
- `leiloes()` por `confederacao_id`

### `LigaJogador`
Pivot usuario x liga. Controla participacao do user na competicao.

## 3. Clube e elenco

### `LigaClube`
Representa o clube do usuario dentro de uma liga.

Campos importantes:
- `liga_id`
- `confederacao_id`
- `user_id`
- `nome`
- `escudo_clube_id`
- `esquema_tatico_imagem`
- `esquema_tatico_layout`

Relacoes principais:
- `liga()`
- `confederacao()`
- `user()`
- `escudo()`
- `clubeElencos()`
- `conquistas()`
- `financeiro()`

### `Elencopadrao`
Catalogo mestre de jogadores por jogo.

Campos de identidade:
- `jogo_id`
- `player_id`
- `short_name`
- `long_name`
- `player_positions`

Campos de mercado e atributos:
- `overall`, `potential`
- `value_eur`, `wage_eur`
- atributos tecnicos, fisicos e goleiro
- imagens como `player_face_url`

Regra atual importante:
- a chave logica usada pelo projeto e `(jogo_id, player_id)`

Relacoes principais:
- `jogo()`
- `ligaClubeElencos()`
- `ligaTransferencias()`

### `LigaClubeElenco`
Posse do jogador dentro da competicao.

Campos importantes:
- `confederacao_id`
- `liga_id`
- `liga_clube_id`
- `elencopadrao_id`
- `value_eur`
- `wage_eur`
- `ativo`

Ponto arquitetural importante:
- `value_eur` e `wage_eur` aqui podem divergir do catalogo mestre e representam o valor do jogador naquele clube/liga.

### `PlayerFavorite`
Radar/favoritos do usuario por liga ou confederacao.

Campos:
- `user_id`
- `liga_id`
- `confederacao_id`
- `elencopadrao_id`

## 4. Mercado, transferencias e negociacao

### `LigaTransferencia`
Historico consolidado de movimentacao de jogadores.

Campos centrais:
- `liga_id`
- `confederacao_id`
- `liga_origem_id`
- `liga_destino_id`
- `elencopadrao_id`
- `clube_origem_id`
- `clube_destino_id`
- `tipo`
- `valor`
- `observacao`

Tipos usados pelo projeto:
- `jogador_livre`
- `venda`
- `multa`
- `troca`

### `LigaProposta`
Proposta entre clubes com valor e opcionalmente jogadores oferecidos.

Campos centrais:
- `confederacao_id`
- `liga_origem_id`
- `liga_destino_id`
- `elencopadrao_id`
- `clube_origem_id`
- `clube_destino_id`
- `valor`
- `oferta_elencopadrao_ids`
- `status`

### Leilao
Entidades principais:
- `LigaLeilao`
- `LigaLeilaoItem`
- `LigaLeilaoLance`

Uso atual:
- `LigaLeilao` define janelas por confederacao
- os itens/lances sustentam a operacao de leilao do mercado

## 5. Janelas operacionais da confederacao

### `LigaPeriodo`
Janela de transferencias por data/hora.

Campos:
- `confederacao_id`
- `inicio`
- `fim`

### `LigaLeilao`
Janela de leilao por data.

Campos:
- `confederacao_id`
- `inicio`
- `fim`

### `LigaRouboMulta`
Janela de roubo por multa por data/hora.

Campos:
- `confederacao_id`
- `inicio`
- `fim`

Ponto importante:
- `MarketWindowService` resolve estado atual e proxima abertura dessas janelas para web e Legacy.

## 6. Financeiro

### `LigaClubeFinanceiro`
Carteira atual do clube.

Campos:
- `liga_id`
- `clube_id`
- `saldo`

### `LigaClubeFinanceiroMovimento`
Ledger financeiro oficial.

Campos:
- `liga_id`
- `clube_id`
- `operacao`
- `descricao`
- `valor`
- `saldo_antes`
- `saldo_depois`
- `metadata`

Operacoes padrao:
- `credit`
- `debit`
- `snapshot_abertura`

Outras entidades financeiras relacionadas:
- `LigaClubePatrocinio`
- `LigaFolhaPagamento`
- `PartidaFolhaPagamento`
- `LigaClubeVendaMercado`
- `LigaClubeAjusteSalarial`

## 7. Partidas

### `Partida`
Entidade central de confronto entre clubes.

Campos importantes:
- `liga_id`
- `mandante_id`
- `visitante_id`
- `scheduled_at`
- `estado`
- `alteracoes_usadas`
- `forced_by_system`
- `sem_slot_disponivel`
- `wo_para_user_id`
- `wo_motivo`
- `placar_mandante`
- `placar_visitante`
- `placar_registrado_por`
- `placar_registrado_em`
- `checkin_mandante_at`
- `checkin_visitante_at`

Relacoes principais:
- `liga()`
- `mandante()`
- `visitante()`
- `woParaUser()`
- `placarRegistradoPorUser()`
- `alteracoes()`
- `eventos()`
- `desempenhos()`
- `reclamacoes()`
- `avaliacoes()`

Estados usados pelo fluxo:
- `confirmacao_necessaria`
- `agendada`
- `confirmada`
- `placar_registrado`
- `placar_confirmado`
- `wo`

### `PartidaDesempenho`
Linha individual da sumula por jogador.

Campos:
- `partida_id`
- `liga_clube_id`
- `elencopadrao_id`
- `nota`
- `gols`
- `assistencias`

Outras entidades da area:
- `PartidaAlteracao`
- `PartidaEvento`
- `PartidaAvaliacao`
- `ReclamacaoPartida`
- `PartidaFolhaPagamento`

## 8. Usuario e perfil

### `User`
Autenticacao principal.

Campos relevantes:
- `name`
- `email`
- `password`
- `is_admin`

Relacoes:
- `profile()`
- `ligas()`
- `clubesLiga()`
- `disponibilidades()`

### `Profile`
Metadados do jogador/gamer.

Uso principal:
- nickname
- plataforma/jogo/geracao
- whatsapp
- idioma/regiao
- reputacao e nivel

### `UserDisponibilidade`
Faixas de disponibilidade do usuario para agendamento de partidas.

## 9. Conteudo, progressao e assets de clube

### Conquistas e premiacoes
Entidades:
- `Conquista`
- `ConquistaImagem`
- `LigaClubeConquista`
- `Premiacao`
- `PremiacaoImagem`

### Patrocinio
Entidades:
- `Patrocinio`
- `PatrocinioImagem`
- `LigaClubePatrocinio`

### Escudos
Entidades:
- `EscudoClube`
- `LigaEscudo`

### Torcida
Entidade principal de apoio:
- `ClubeTamanho`

Hoje o total de torcida visivel no produto tambem depende de conquistas resgatadas pelo usuario/clube.

## 10. Leitura pratica do dominio

Se precisar entender o projeto rapido, a ordem mais util e:

1. `Liga`
2. `Confederacao`
3. `LigaClube`
4. `LigaClubeElenco`
5. `Elencopadrao`
6. `LigaTransferencia`
7. `Partida`
8. `PartidaDesempenho`
9. `LigaClubeFinanceiro` + `LigaClubeFinanceiroMovimento`
10. `LigaPeriodo` + `LigaLeilao` + `LigaRouboMulta`
