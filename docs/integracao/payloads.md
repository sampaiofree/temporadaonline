# Payloads de bootstrap do frontend

Este documento cobre os payloads injetados via Blade em `window.*` e usados como bootstrap inicial pelas telas React e pela shell Legacy.

## Payloads globais compartilhados

### `window.__APP_CONTEXT__`

Injetado por `resources/views/components/app_context.blade.php`.

Shape:

```json
{
  "mode": "liga|global",
  "liga": { "id": 1, "nome": "Liga X" },
  "clube": { "id": 10, "nome": "Clube X" },
  "nav": "mercado"
}
```

Uso:
- navbar publica/app
- destaque de secao ativa
- contexto da liga atual

### `window.__APP_ASSETS__`

Injetado por `resources/views/components/app_assets.blade.php`.

Campos atuais:
- `favicon_url`
- `logo_padrao_url`
- `logo_dark_url`
- `imagem_campo_url`
- `background_app_url`
- `card_completo_url`
- `card_reduzido_url`
- `img_jogador_url`

## Payloads por view web

### Dashboard inicial

View: `resources/views/dashboard.blade.php`

```js
window.__CHECKLIST__
```

Payload usado pela dashboard inicial do fluxo padrao.

### Lista de ligas

View: `resources/views/ligas.blade.php`

```js
window.__ALL_LIGAS__
window.__MY_LIGAS__
window.__REQUIRE_PROFILE_COMPLETION__
window.__PROFILE_URL__
window.__PROFILE_HORARIOS_URL__
```

Uso:
- listagem publica/autenticada de ligas
- marcacao das ligas do usuario
- bloqueio por perfil incompleto
- links auxiliares para completar perfil e horarios

### Minha liga - hub e onboarding

Views:
- `resources/views/minha_liga.blade.php`
- `resources/views/minha_liga_onboarding_clube.blade.php`
- `resources/views/minha_liga_clube.blade.php`

Payloads:

```js
window.__LIGA__
window.__CLUBE__
window.__ESCUDOS__
window.__USED_ESCUDOS__
window.__CLUBE_ONBOARDING__
window.__CLUBE_EDITOR__
```

Uso:
- criacao/edicao de clube
- escolha de escudo
- bloqueio de escudos em uso na mesma confederacao

### Mercado da liga

View: `resources/views/liga_mercado.blade.php`

```js
window.__LIGA__
window.__CLUBE__
window.__MERCADO__
```

`__MERCADO__` contem, no minimo:
- `players`
- `closed`
- `period`
- `mode` (`open|closed|auction`)
- `auction_period`
- `bid_increment_options`
- `bid_duration_seconds`
- `radar_ids`
- `propostas_recebidas_count`
- opcionalmente `blocked_reason` e `blocked_message`

Cada player tende a trazer:
- `elencopadrao_id`
- `short_name`
- `long_name`
- `player_positions`
- `overall`
- `value_eur`
- `wage_eur`
- `club_status`
- `club_name`
- `liga_nome`
- `club_id`
- `is_free_agent`
- `can_buy`
- `can_multa`
- `entry_value_eur`
- `multa_value_eur`
- `player_face_url`
- `auction` quando o modo atual e leilao

### Partidas da liga

View: `resources/views/liga_partidas.blade.php`

```js
window.__LIGA__
window.__CLUBE__
window.__PARTIDAS__
```

`__PARTIDAS__` entrega a lista da liga e flags como bloqueio de envio de sumula quando o mercado esta aberto.

### Finalizacao de partida

View: `resources/views/liga_partida_finalizar.blade.php`

```js
window.__LIGA__
window.__CLUBE__
window.__PARTIDA__
```

`__PARTIDA__` carrega a partida alvo e metadados necessarios para o fluxo de sumula/finalizacao.

### Elenco da liga

View: `resources/views/liga_elenco.blade.php`

```js
window.__LIGA__
window.__ELENCO_LIGA__
```

`__ELENCO_LIGA__` e um ranking agregado por desempenho historico dos jogadores na liga.

### Meu elenco

View: `resources/views/minha_liga_meu_elenco.blade.php`

```js
window.__LIGA__
window.__CLUBE__
window.__MEU_ELENCO__
```

### Esquema tatico

View: `resources/views/minha_liga_esquema_tatico.blade.php`

```js
window.__LIGA__
window.__CLUBE__
window.__ESQUEMA_TATICO__
```

### Financeiro do clube

View: `resources/views/minha_liga_financeiro.blade.php`

```js
window.__LIGA__
window.__CLUBE__
window.__FINANCEIRO__
```

`__FINANCEIRO__` mistura resumo de carteira, custos, patrocinio, ganhos por partida e historico de movimentos usado pela tela web.

### Conquistas e patrocinio do clube

Views:
- `resources/views/minha_liga_conquistas.blade.php`
- `resources/views/minha_liga_patrocinio.blade.php`

```js
window.__CONQUISTAS__
window.__PATROCINIOS__
```

### Classificacao

View: `resources/views/liga_classificacao.blade.php`

```js
window.__LIGA__
window.__CLASSIFICACAO__
```

### Perfil publico de clube

View: `resources/views/liga_clube_perfil.blade.php`

```js
window.__LIGA__
window.__CLUBE_PERFIL__
```

Campos comuns em `__CLUBE_PERFIL__`:
- identificacao do clube
- dono/nickname
- plataforma e geracao
- escudo e imagem tatica
- `players`
- `valor_elenco`
- `saldo`
- `club_value`
- `achievement_images`
- `fans_total`

### Perfil do usuario

View: `resources/views/perfil.blade.php`

```js
window.__PLAYER__
window.__PLATAFORMAS__
window.__JOGOS__
window.__GERACOES__
```

Observacao: o controller tambem carrega regioes e idiomas, mas hoje esses arrays nao sao bootstrapados nessa Blade especifica.

## Payload do Legacy

### `window.__LEGACY_CONFIG__`

View: `resources/views/legacy/index.blade.php`

`__LEGACY_CONFIG__` nao carrega os dados de dominio diretamente; ele entrega os endpoints que a shell legacy usa para hydrate por tela.

Chaves atuais:
- `profileSettingsUrl`
- `profileUpdateUrl`
- `profileDisponibilidadesSyncUrl`
- `profileAccountDeletionRequestUrl`
- `profileAccountDeletionCancelUrl`
- `logoutUrl`
- `userId`
- `confederacoes`
- `onboardingClubeUrl`
- `marketDataUrl`
- `myClubDataUrl`
- `squadDataUrl`
- `matchCenterDataUrl`
- `leaderboardDataUrl`
- `leagueTableDataUrl`
- `achievementsDataUrl`
- `patrociniosDataUrl`
- `seasonStatsDataUrl`
- `transferHistoryDataUrl`
- `nextEventsDataUrl`
- `financeDataUrl`
- `financeStatementDataUrl`
- `inboxDataUrl`
- `publicClubProfileDataUrl`
- `esquemaTaticoDataUrl`
- `esquemaTaticoSaveUrl`

### `window.__LEGACY_FIRST_ACCESS__`

View: `resources/views/legacy/primeiro_acesso.blade.php`

Bootstrap exclusivo do fluxo de primeiro acesso.

### `window.__LEGACY_ONBOARDING_SELECTOR__`

View: `resources/views/legacy/onboarding_clube_select.blade.php`

Bootstrap da etapa de selecao de liga/confederacao no onboarding legacy.

### `window.__CLUBE_ONBOARDING__`

Views:
- `resources/views/legacy/onboarding_clube.blade.php`
- `resources/views/minha_liga_onboarding_clube.blade.php`

Bootstrap do fluxo de criacao do clube, com ligas, escudos e dados auxiliares.

## Regras praticas

- Payload de bootstrap deve carregar apenas o necessario para a tela abrir.
- No Legacy, quase tudo e lazy-loaded por endpoint, nao por HTML inicial.
- Os contratos mais sensiveis hoje sao `__MERCADO__`, `__PARTIDAS__`, `__PARTIDA__` e `__LEGACY_CONFIG__`.
- Se um controller passar dados para a view mas a Blade nao os expuser em `window.*`, o frontend nao os recebe.
