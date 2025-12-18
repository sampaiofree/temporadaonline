# Rotas do projeto (Laravel)

Fonte: `php artisan route:list` + revisao de `routes/web.php`, `routes/api.php` e `routes/auth.php` em ambiente local. Todas as rotas usam o middleware `web`; quando ha `auth` ou `guest` esta indicado.

## Publico e utilitarios
- `GET /` — view `dashboard` inicial (sem nome explicitado).
- `GET /up` — health check padrao do Laravel (retorna 200 se a aplicacao esta ativa).
- `GET /storage/{path}` (`storage.local`) — serve arquivos de `storage/app/public`.
- `GET /sanctum/csrf-cookie` (`sanctum.csrf-cookie`) — entrega cookie de CSRF para SPAs.

## Autenticacao (guest)
- `GET /login` (`login`) — `Auth\AuthenticatedSessionController@create`, formulario de login.
- `POST /login` (sem nome) — `Auth\AuthenticatedSessionController@store`, autentica o usuario.
- `GET /register` (`register`) — `Auth\RegisteredUserController@create`, formulario de cadastro.
- `POST /register` (sem nome) — `Auth\RegisteredUserController@store`, cria usuario.
- `GET /forgot-password` (`password.request`) — `Auth\PasswordResetLinkController@create`, pede e-mail para reset.
- `POST /forgot-password` (`password.email`) — `Auth\PasswordResetLinkController@store`, envia link de reset.
- `GET /reset-password/{token}` (`password.reset`) — `Auth\NewPasswordController@create`, exibe formulario com token.
- `POST /reset-password` (`password.store`) — `Auth\NewPasswordController@store`, define nova senha.

## Autenticacao (auth)
- `POST /logout` (`logout`) — `Auth\AuthenticatedSessionController@destroy`, encerra sessao.
- `GET /verify-email` (`verification.notice`) — `Auth\EmailVerificationPromptController`, solicita verificacao de e-mail.
- `GET /verify-email/{id}/{hash}` (`verification.verify`, `signed`, `throttle:6,1`) — `Auth\VerifyEmailController`, confirma e-mail.
- `POST /email/verification-notification` (`verification.send`, `throttle:6,1`) — `Auth\EmailVerificationNotificationController@store`, reenvia e-mail de verificacao.
- `GET /confirm-password` (`password.confirm`) — `Auth\ConfirmablePasswordController@show`, tela de confirmacao de senha.
- `POST /confirm-password` (sem nome) — `Auth\ConfirmablePasswordController@store`, valida a senha.
- `PUT /password` (`password.update`) — `Auth\PasswordController@update`, altera a senha logada.

## Admin
- `GET /admin/dashboard` (`admin.dashboard`) — `Admin\DashboardController@index`; painel com metricas ficticias. (Sem middleware especifico definido aqui.)

## Area autenticada (web.php, middleware `auth`)

### Navegacao geral
- `GET /dashboard` (`dashboard`) — view `dashboard`.
- `GET /ligas` (`ligas`) — `LigaController@index`, lista ligas e marcacao de inscricao do usuario.
- `POST /ligas/{liga}/entrar` (`ligas.join`) — `LigaController@join`, inscreve o usuario na liga e retorna redirect para `minha_liga`.

### Perfil
- `GET /perfil` (`perfil`) — `ProfileController@show`, exibe perfil do jogador.
- `PUT /perfil` (`perfil.update`) — `ProfileController@update`, atualiza nome/email e dados estendidos; responde JSON ou redirect.
- `DELETE /perfil` (`perfil.destroy`) — `ProfileController@destroy`, apaga conta.
- `GET /profile` (sem nome) — `ProfileController@show`, alias de leitura.
- `PATCH /profile` (sem nome) — `ProfileController@update`, alias de update.
- `DELETE /profile` (sem nome) — `ProfileController@destroy`, alias de delete.

### Minha liga / clube
- `GET /minha_liga` (`minha_liga`) — `MinhaLigaController@show`, resumo da liga do usuario.
- `POST /minha_liga/clubes` (`minha_liga.clubes`) — `MinhaLigaController@storeClube`, cria/atualiza clube do usuario e carteira financeira.
- `POST /minha_liga/clube/elenco` (`minha_liga.clube.elenco`) — `MinhaLigaController@addPlayerToClub`, compra jogador livre para o clube (usa `TransferService::buyPlayer`).
- `GET /minha_liga/meu-elenco` (`minha_liga.meu_elenco`) — `MinhaLigaController@meuElenco`, mostra elenco do clube e custo de salario por rodada.
- `GET /minha_liga/financeiro` (`minha_liga.financeiro`) — `MinhaLigaController@financeiro`, exibe saldo, salario/rodada e ultimas transferencias do clube.

### Liga – paginas
- `GET /liga/dashboard` (`liga.dashboard`) — `LigaDashboardController@show`, dashboard da liga com acoes e proxima partida simulada.
- `GET /liga/mercado` (`liga.mercado`) — `LigaMercadoController@index`, renderiza view do mercado com todos os jogadores do jogo da liga e marca status/clubes.
- `GET /liga/partidas` (`liga.partidas`) — `LigaPartidasController@index`, placeholder de partidas.
- `GET /liga/classificacao` (`liga.classificacao`) — `LigaClassificacaoController@index`, ranking simples dos clubes da liga.
- `GET /liga/clubes/{clube}` (`liga.clube.perfil`) — `LigaClubePerfilController@show`, perfil de um clube da liga e seu elenco.

### Mercado / elenco (JSON sob `auth`)
- `POST /elenco/{elenco}/listar-mercado` (`elenco.listarMercado`) — `ElencoController@listarMercado`; valida posse do usuario e registra preco enviado, respondendo mensagem e preco.
- `POST /elenco/{elenco}/vender-mercado` (`elenco.venderMercado`) — `ElencoController@venderMercado`; valida posse e devolve jogador ao mercado (resposta somente com mensagem).

## API (middleware `web`, `auth`)
- `POST /api/ligas/{liga}/clubes/{clube}/comprar` — `Api\LeagueTransferController@buy`; payload `{ elencopadrao_id }`; compra/adiciona jogador ao clube; retorna mensagem e `entry` criado.
- `POST /api/ligas/{liga}/clubes/{clube}/vender` — `Api\LeagueTransferController@sell`; payload `{ elencopadrao_id, price }`; vende jogador de outro clube pelo preco informado; retorna mensagem e `entry`.
- `POST /api/ligas/{liga}/clubes/{clube}/multa` — `Api\LeagueTransferController@payReleaseClause`; payload `{ elencopadrao_id }`; paga multa/rescisao e transfere jogador.
- `POST /api/ligas/{liga}/clubes/{clube}/trocar` — `Api\LeagueTransferController@swap`; payload `{ jogador_a_id, clube_b_id, jogador_b_id, ajuste_valor? }`; troca jogadores entre clubes com ajuste opcional.
- `POST /api/ligas/{liga}/rodadas/{rodada}/cobrar-salarios` — `Api\PayrollController@chargeRound`; valida `rodada` (min 1) e processa folha de pagamento, retornando mensagem e resultados.
