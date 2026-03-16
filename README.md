# Temporada Online

Aplicacao web para gestao de ligas de futebol com foco em mercado de jogadores, partidas PvP, financeiro, conquistas e uma experiencia mobile-first no modo Legacy.

## Stack principal

- PHP 8.2+
- Laravel 12
- React 18 + Vite
- Tailwind CSS 4
- Blade para views tradicionais e admin
- Inertia apenas em partes pontuais da stack padrao

## Areas da aplicacao

### 1. Legacy

Interface principal do produto em `legacy/`, renderizada por `resources/js/legacy/index.tsx` com payload inicial em `window.__LEGACY_CONFIG__`.

Fluxos principais:
- hub-global
- mercado
- my-club
- squad
- match-center
- report-match
- finance
- inbox
- onboarding de clube

### 2. Views autenticadas da liga

Conjunto de telas Blade + React especificas por rota, por exemplo:
- `liga/mercado`
- `liga/partidas`
- `liga/partidas/{partida}/finalizar`
- `liga/classificacao`
- `liga/elenco`
- `liga/clubes/{clube}`
- `minha_liga/*`

Essas telas usam bootstrap via `window.__LIGA__`, `window.__CLUBE__` e payloads especificos por view.

### 3. Admin

Area separada por `auth + admin`, baseada em controllers Blade. Cobre cadastro de catalogos, confederacoes, ligas, clubes, partidas, assets, usuarios e importacao do elenco padrao.

## Dominios principais

- Catalogos: jogos, geracoes, plataformas, paises, playstyles, assets
- Estrutura competitiva: confederacoes, ligas, clubes
- Mercado: compra, venda, multa, proposta, leilao e favoritos
- Janelas da confederacao: transferencias, leilao e roubo por multa
- Partidas: agendamento, check-in, W.O., registro de placar, sumula e avaliacao
- Financeiro: carteira do clube, ledger, patrocinio, folha e historico de transferencias
- Progressao: conquistas, premiacoes, patrocinio e torcida

## Regras estruturais importantes

- `elencopadrao` usa chave logica por `(jogo_id, player_id)`.
- Posse do jogador dentro da competicao fica em `liga_clube_elencos`.
- Janelas de mercado, leilao e multa sao definidas por `confederacao_id`, nao por liga individual.
- O Legacy e hoje a superficie mais completa do produto para o usuario final.

## Setup rapido

### Dependencias

```bash
composer install
npm install
```

### Ambiente

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Se quiser usar o script padrao do projeto:

```bash
composer run setup
```

## Desenvolvimento

### Backend + Vite

```bash
php artisan serve
npm run dev
```

Ou com o script combinado do projeto:

```bash
composer run dev
```

## Testes e build

```bash
php artisan test
npm run build
```

## Documentacao

A documentacao atual foi reorganizada em `docs/`.

- indice: `docs/INDEX.md`
- rotas: `docs/integracao/rotas.md`
- payloads: `docs/integracao/payloads.md`
- modelo de dados: `docs/arquitetura/modelo-de-dados.md`

Os arquivos antigos da raiz foram movidos para `docs/_archive/2026-03-16/` como referencia historica.
