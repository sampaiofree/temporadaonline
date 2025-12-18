# Componentes reutilizáveis (estado atual)

## Modal
- Arquivo: `resources/js/components/Modal.jsx`
- Stack: `@headlessui/react` (`Dialog`, `Transition`).
- Props: `show` (bool), `maxWidth` (`sm|md|lg|xl|2xl`, padrão `2xl`), `closeable` (bool), `onClose`.
- Estilo: tailwind padrão (fundo branco, sombra, bordas arredondadas). Não segue o tema “MCO” dourado.
- Não existe Drawer hoje.

## Dropdown
- Arquivo: `resources/js/components/Dropdown.jsx`
- Stack: `@headlessui/react` (`Transition`) + `@inertiajs/react` para links.
- API: `<Dropdown><Dropdown.Trigger>...` e `<Dropdown.Content align width contentClasses>...` e `<Dropdown.Link>`.
- Estilo: tailwind padrão.

## Botões (stack “Breeze”/tailwind)
- `PrimaryButton.jsx`: bg cinza escuro, texto branco, uppercase, rounded.
- `SecondaryButton.jsx`: bg branco, borda cinza, texto cinza.
- `DangerButton.jsx`: bg vermelho, texto branco.
- São estilizados via tailwind utilitário, não usam o tema dourado de `base.css`.

## Botões de tabela (tema MCO)
- Para botões dentro das tabelas do marketplace e do elenco, use a classe `.table-action-badge` definida em `resources/css/app_publico/base.css`. Ela replica o visual dos badges (`.mercado-pos-badge`) e garante espaçamento/hover consistentes.
- Variantes: `.primary` (para ações positivas como comprar), `.outline` (para ações secundárias como multa) e `.neutral` (para estados desabilitados/indicadores `Já no clube`). Evite usar `.btn-primary`/`.btn-outline` nesses contextos.

## Inputs básicos
- `TextInput.jsx`: input com focus indigo; suporta `isFocused`.
- `Checkbox.jsx`, `InputLabel.jsx`, `InputError.jsx` estão presentes (não listados acima mas seguem o padrão breeze).

## Navegação pública (tema MCO)
- `app_publico/Navbar.jsx`: navbar fixa inferior com contexto de liga (usa `window.__APP_CONTEXT__`), ícones inline.
- `app_publico/DashboardButton.jsx`: botão estilizado (`dashboard-btn` em `base.css`) com ícone SVG customizável.
- Estilo definido em `resources/css/app_publico/base.css` (tema dourado/black).

## Alert (novo)
- Arquivo: `resources/js/components/app_publico/Alert.jsx`
- Variantes: `info`, `success`, `warning`, `danger` (cores e fundos no `base.css`).
- Props: `variant` (default `info`), `title` (string), `description` ou `children` (conteúdo), `onClose` (callback opcional para mostrar botão “×”), `floating` (alinha o alerta no centro da tela).
- Exemplo:
  ```jsx
  <Alert variant="warning" title="Saldo insuficiente" floating onClose={() => setFeedback('')}>
    Saldo atual: €99.075.000. Necessário: €100.000.000.
  </Alert>
  ```

## Helpers de layout
- Uma coleção de classes em `resources/css/app_publico/base.css` serve como padrão para tabelas e drawers densos:
  * `.mercado-filters`, `.mercado-search`, `.mercado-search-input`, `.mercado-filters-button` (barra superior com busca e botão “Filtros”).
  * `.mercado-drawer`, `.mercado-drawer-backdrop`, `.mercado-drawer-grid`, `.mercado-drawer-actions` (drawer bottom-up usado para filtros no Mercado e Meu Elenco).
  * `.mercado-table`, `.mercado-table-scroll`, `.mercado-player-cell`, `.mercado-avatar-sm`, `.mercado-ovr-badge`, `.mercado-pos-badge` (tabela única com scroll horizontal, badges e hover).
  * `.alert` e variantes (`.alert-info`, `.alert-success`, etc.) aplicam o overlay de feedback quando necessário.

## Outros
- `ApplicationLogo.jsx`, `NavLink.jsx`, `ResponsiveNavLink.jsx` — utilitários da stack breeze/inertia.

## O que **não** existe pronto
- Drawer: não há componente; precisaríamos implementar.
- Badge/Chip: não há componente React; o tema usa classes como `.filter-pill`, mas não há wrapper.
- Tabela padrão: nenhuma abstração de tabela; telas usam `<table>` direto com classes locais.
- Botão primário/secundário no tema MCO: existem classes globais `.btn-primary` e `.btn-outline` em `resources/css/app_publico/base.css`, mas não há componentes React encapsulando essas variantes.
