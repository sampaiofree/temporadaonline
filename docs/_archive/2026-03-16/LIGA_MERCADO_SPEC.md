# MCO — Mercado de Jogadores (Layout & UX)

## Objetivo

Definir **estrutura, comportamento e regras** da tela de Mercado de Jogadores, inspirada em FUTBIN/FIFA, mantendo o **design system do MCO** e foco em **mobile first**, **performance** e **imersão de jogo**.

---

## 1. Barra Superior (Busca + Filtros)

### Estrutura

Uma única barra fixa no topo contendo:

* 🔍 **Campo de busca** (com ícone de lupa, à esquerda)
* ⚙️ **Botão “Filtros”** (à direita)

```
[ 🔍 Buscar jogador...            ]   [ Filtros ]
```

### Busca

**Comportamento:**

* Busca **no backend**
* Usa **debounce de 300–400ms**
* Atualiza resultados conforme o usuário digita

**Campos pesquisados:**

* Nome curto do jogador
* Nome completo
* Clube
* Liga
* Nacionalidade

**UX:**

* Enquanto busca: mostrar texto discreto “Buscando…”
* Mantém lista atual até resposta do servidor

---

## 2. Filtros Avançados (Drawer)

### Abertura

* Ao clicar em **Filtros**, abre um **drawer de baixo para cima** (mobile first)

### Comportamento

* Filtros aplicam **em tempo real**
* Estado é mantido ao fechar
* Botão **“Limpar filtros”** disponível

### Filtros Disponíveis (usar TODOS que existirem no payload)

**Seleção múltipla:**

* Posição
* Liga
* Nacionalidade

**Seleção simples:**

* Clube

**Faixas (range):**

* Overall (OVR)
* Valor de mercado
* Salário

---

## 3. Estados do Jogador no Mercado (Regra de Negócio)

Estados possíveis:

1. **Livre**
2. **Meu clube**
3. **Outro clube (com multa)**
4. **À venda** (preço definido por outro usuário)

### Campo de controle sugerido

* `sale_price_eur`

  * `null` → não está à venda
  * valor numérico → jogador listado no mercado

---

## 4. Ações por Estado

| Estado do Jogador | Ação Disponível              |
| ----------------- | ---------------------------- |
| Livre             | **Comprar**                  |
| Outro clube       | **Roubar (multa)**           |
| À venda           | **Comprar** (preço definido) |
| Meu clube         | **Vender**                   |

> Jogador **à venda não pode ser roubado por multa**.

---

## 5. Fluxo de Venda (Meu Clube)

Ao clicar em **Vender**, abrir modal com duas opções:

### 1️⃣ Venda rápida

* Preço automático: `value_eur * 0.8`
* Venda imediata
* Dinheiro entra na hora

### 2️⃣ Colocar à venda

* Usuário define preço
* Valor mínimo: `value_eur * 0.7`
* Valor máximo: livre
* Jogador fica disponível no mercado

---

## 6. Estrutura da Tabela (Única — Desktop e Mobile)

### Conceito

* **Uma única tabela** para desktop e mobile
* Mobile usa **scroll horizontal**
* Tudo em **uma linha só**, sem cards

---

## 7. Colunas da Tabela (Ordem Final)

### 1️⃣ Jogador (coluna rica)

Contém:

* Foto do jogador com **moldura padrão MCO**
* Nome do jogador (destaque)
* Linha pequena abaixo:

  * Nacionalidade
  * Liga
  * Clube

---

### 2️⃣ Overall (OVR)

* Badge quadrado
* Número grande

**Cores:**

* ≥ 80 → Verde
* 60–79 → Laranja
* < 60 → Vermelho

---

### 3️⃣ Posição (POS)

* Badge pequeno
* Com borda

---

### 4️⃣ Valor de Mercado

* Valor **abreviado**:

  * `6M`, `850K`, `1.2M`

---

### 5️⃣ Salário

* Abreviado:

  * `120K`, `35K`

---

### 6️⃣ Stats do Jogador

Exibir:

* PAC
* SHO
* PAS
* DRI
* DEF
* PHY

Formato:

* Chips pequenos
* Texto: `PAC 85`

**Cores:**

* ≥ 80 → Verde
* 60–79 → Laranja
* < 60 → Vermelho

---

### 7️⃣ Ação

* Botão compacto
* Texto conforme estado:

  * Comprar
  * Roubar (multa)
  * Vender

---

## 8. Mobile — Prioridades de Exibição

### Mantém sempre visível:

* Jogador
* OVR
* Posição
* Stats
* Ação

### Pode ser ocultado primeiro se faltar espaço:

1. Clube
2. Salário
3. Valor

---

## 9. Diretrizes Visuais

* Estilo FIFA / FUTBIN
* Visual **compacto e denso**
* Tipografia pequena, mas legível
* Destaques visuais nos números (OVR + stats)
* Nada de cards grandes
* Aparência de **menu de jogo**, não sistema

---

## 10. Resumo Executivo

* Busca rápida no backend
* Filtros completos via drawer
* Mercado com estratégia real
* Tabela única, compacta
* UX gamer, não administrativo
* Escalável para milhares de jogadores

---

**Este documento define o padrão oficial do Mercado de Jogadores do MCO.**
