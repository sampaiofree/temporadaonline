# Rotas do projeto

Baseado em `php artisan route:list --except-vendor` executado em 2026-03-16.

## Visao geral

O projeto hoje mistura 4 superficies principais:

1. web autenticada tradicional
2. API autenticada usada por React e Legacy
3. Legacy em `/legacy`
4. admin em `/admin`

Total observado no ambiente local: 264 rotas.

## Arquivos de rota

- `routes/web.php`
- `routes/api.php`
- `routes/legacy.php`
- `routes/auth.php`

## Entrada principal

- `GET /` (`home`)
  - autenticado: redireciona para `legacy.index`
  - visitante: renderiza `home`

Rotas institucionais simples:
- `GET /suporte` (`support`)
- `GET /politica-privacidade` (`privacy-policy`)

## Autenticacao e conta

Stack padrao Laravel/Breeze com verificacao de email e reset por token/codigo.

Rotas principais:
- `GET /login` / `POST /login`
- `POST /logout`
- `GET /register` / `POST /register`
- `GET /forgot-password` / `POST /forgot-password`
- `GET /reset-password/{token}` / `POST /reset-password`
- `POST /reset-password/code`
- `GET /verify-email`
- `GET /verify-email/{id}/{hash}`
- `POST /verify-email/code`
- `POST /email/verification-notification`
- `GET /confirm-password` / `POST /confirm-password`
- `PUT /password`

## Web autenticada

Middleware dominante:
- `auth`
- `verified`
- `roster.limit`
- `legacy.first_access`

### Navegacao e perfil
- `GET /dashboard` -> redireciona para `legacy.index`
- `GET /ligas` (`ligas`)
- `POST /ligas/{liga}/entrar` (`ligas.join`)
- `GET /perfil` / `PUT /perfil` / `DELETE /perfil`
- aliases em `/profile`

### Minha liga
- `GET /minha_liga`
- `GET /minha_liga/clube`
- `GET /minha_liga/onboarding-clube`
- `POST /minha_liga/clubes`
- `POST /minha_liga/clube/elenco`
- `GET /minha_liga/meu-elenco`
- `GET /minha_liga/esquema-tatico`
- `POST /minha_liga/esquema-tatico`
- `GET /minha_liga/financeiro`
- `GET /minha_liga/clube/conquistas`
- `POST /minha_liga/clube/conquistas/{conquista}/claim`
- `GET /minha_liga/clube/patrocinio`
- `POST /minha_liga/clube/patrocinio/{patrocinio}/claim`
- rota legado antiga `GET /minha_liga/elenco` que redireciona para `liga.mercado`

### Liga
- `GET /liga/mercado`
- `GET /liga/mercado/propostas`
- `GET /liga/partidas`
- `GET /liga/partidas/{partida}/finalizar`
- `GET /liga/classificacao`
- `GET /liga/elenco`
- `GET /liga/clubes/{clube}`

### Ajustes do elenco do clube
- `PATCH /elenco/{elenco}/valor`
- `POST /elenco/{elenco}/vender-mercado`
- `POST /elenco/{elenco}/listar-mercado`

### Disponibilidades
- `GET /me/disponibilidades`

## API autenticada (`routes/api.php`)

Middleware:
- `web`
- `auth`

### Mercado e transferencias
- `POST /api/ligas/{liga}/clubes/{clube}/comprar`
- `POST /api/ligas/{liga}/clubes/{clube}/vender`
- `POST /api/ligas/{liga}/clubes/{clube}/multa`
- `POST /api/ligas/{liga}/clubes/{clube}/trocar`
- `POST /api/ligas/{liga}/clubes/{clube}/leiloes/lances`

### Propostas
- `GET /api/ligas/{liga}/clubes/{clube}/propostas`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas/{proposta}/aceitar`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas/{proposta}/rejeitar`
- `POST /api/ligas/{liga}/clubes/{clube}/propostas/{proposta}/cancelar`

### Favoritos e catalogo
- `GET /api/elencopadrao/{player}`
- `GET /api/ligas/{liga}/favoritos`
- `POST /api/ligas/{liga}/favoritos`

### Financeiro e pendencias
- `POST /api/ligas/{liga}/rodadas/{rodada}/cobrar-salarios`
- `GET /api/me/pendencias`

### Disponibilidade do usuario
- `GET /api/me/disponibilidades`
- `POST /api/me/disponibilidades`
- `PUT /api/me/disponibilidades/{id}`
- `DELETE /api/me/disponibilidades/{id}`

### Partidas
- `GET /api/partidas/{partida}/slots`
- `POST /api/partidas/{partida}/agendar`
- `POST /api/partidas/{partida}/checkin`
- `POST /api/partidas/{partida}/registrar-placar`
- `POST /api/partidas/{partida}/confirmar-placar`
- `POST /api/partidas/{partida}/reclamacoes`
- `POST /api/partidas/{partida}/avaliacoes`
- `POST /api/partidas/{partida}/desistir`
- `GET /api/partidas/{partida}/desempenho/form`
- `POST /api/partidas/{partida}/desempenho/preview`
- `POST /api/partidas/{partida}/desempenho/confirm`

## Legacy (`routes/legacy.php`)

O Legacy e a shell principal da experiencia autenticada. Entra por:
- `GET /legacy` (`legacy.index`)

### Auth e onboarding
- `GET /legacy/login`
- `POST /legacy/login`
- `POST /legacy/logout`
- `GET /legacy/primeiro-acesso`
- `PUT /legacy/primeiro-acesso/profile`
- `PUT /legacy/primeiro-acesso/disponibilidades`
- `GET /legacy/onboarding-clube`
- `POST /legacy/onboarding-clube/select-liga`
- `POST /legacy/onboarding-clube/clubes`

### Endpoints de dados do app legacy
- `GET /legacy/market-data`
- `GET /legacy/my-club-data`
- `GET /legacy/squad-data`
- `GET /legacy/match-center-data`
- `GET /legacy/leaderboard-data`
- `GET /legacy/league-table-data`
- `GET /legacy/achievements-data`
- `POST /legacy/achievements/{conquista}/claim`
- `GET /legacy/patrocinios-data`
- `POST /legacy/patrocinios/{patrocinio}/claim`
- `GET /legacy/season-stats-data`
- `GET /legacy/transfer-history-data`
- `GET /legacy/next-events-data`
- `GET /legacy/finance-data`
- `GET /legacy/finance-statement-data`
- `GET /legacy/inbox-data`
- `GET /legacy/public-club-profile-data`
- `GET /legacy/esquema-tatico-data`
- `POST /legacy/esquema-tatico`
- `GET /legacy/profile/settings`
- `PUT /legacy/profile`
- `PUT /legacy/profile/disponibilidades`
- `POST /legacy/profile/request-account-deletion`
- `POST /legacy/profile/cancel-account-deletion`

## Admin (`/admin`)

Middleware:
- `auth`
- `admin`

O admin e Blade-first e cobre catalogos, conteudo, ligas, operacao e moderacao.

### Modulos principais
- dashboard e logs
- confederacoes
- ligas
- geracoes, jogos, plataformas, paises
- playstyles
- conquistas, patrocinio e premiacoes
- idioma/regiao
- clubes e ligas-usuarios
- temporada
- app-assets
- whatsapp
- partidas e reclamacoes
- ligas-escudos e escudos-clubes
- users e horarios
- clube-tamanho
- elenco-padrao

### Destaques de rotas especificas
- `PATCH /admin/ligas/{liga}/finalizar`
- `POST /admin/conquistas/upload-massa`
- `POST /admin/patrocinios/upload-massa`
- `POST /admin/premiacoes/upload-massa`
- `POST /admin/whatsapp/instance`
- `POST /admin/whatsapp/{connection}/connect`
- `GET /admin/elenco-padrao`
- `POST /admin/elenco-padrao/importar`
- `GET /admin/elenco-padrao/jogadores`

## Observacoes uteis

- O admin concentra a maior quantidade de CRUDs do sistema.
- `legacy.index` e hoje a rota de destino padrao para usuario autenticado comum.
- Ha coexistencia entre views Blade especificas por pagina e a shell Legacy em React/TSX.
- Para atualizar este documento, rode novamente `php artisan route:list --except-vendor`.
