# NAVIGATION SPEC — APP LIGAS (MVP)

Este documento define **como o usuário navega no sistema**, com foco em **mobile-first** e comportamento de **aplicativo**. Ele é a fonte única de verdade para frontend e backend.

---

## 🎯 PRINCÍPIO CENTRAL

O sistema funciona sempre em **um de dois estados**:

1. **Fora de uma liga** (modo global)
2. **Dentro de uma liga ativa** (modo liga)

> Mercado, Clube, Partidas e Classificação **só existem dentro de uma liga ativa**.

---

## 🧭 ESTADOS DE NAVEGAÇÃO

### 🔹 ESTADO 1 — MODO GLOBAL (fora da liga)

**Quando acontece:**

* Usuário acabou de entrar no app
* Usuário saiu de uma liga
* Usuário ainda não escolheu uma liga

**Rotas típicas:**

* `/home`
* `/ligas`
* `/perfil`

**Barra inferior (GlobalNavbar):**

```
[ Home ] [ Ligas ] [ Perfil ]
```

**Regras:**

* Não existe acesso a Mercado, Partidas ou Clube
* Usuário deve escolher uma liga para avançar
* Ligas são criadas apenas por administradores

---

### 🔸 ESTADO 2 — MODO LIGA (liga ativa)

**Quando acontece:**

* Usuário entrou em uma liga
* Uma liga foi resolvida pelo backend (`ResolvesLiga`)

**Rotas típicas:**

* `/liga/dashboard`
* `/liga/mercado`
* `/liga/partidas`
* `/liga/classificacao`
* `/liga/clubes/{meu_clube}`

**Barra inferior (LigaNavbar):**

```
[ ⬅ Ligas ] [ Mercado ] [ Partidas ] [ Tabela ] [ Meu Clube ]
```

**Significado dos itens:**

* **⬅ Ligas** → Sai da liga ativa e volta para `/ligas`
* **Mercado** → Mercado da liga ativa
* **Partidas** → Partidas da liga ativa
* **Tabela** → Classificação da liga ativa
* **Meu Clube** → Hub do clube do usuário naquela liga

---

## 🔙 SAIR DA LIGA (AÇÃO CRÍTICA)

* A ação **⬅ Ligas**:

  * encerra o contexto da liga ativa
  * redireciona para `/ligas`
  * troca automaticamente a navbar para modo global

**Regras:**

* Não há confirmação
* Não há perda de dados
* Apenas troca de contexto

---

## 🏟️ CONTEXTO VISUAL NO TOPO

Enquanto estiver no **modo liga**, o topo da tela deve exibir:

```
Nome da Liga • Nome do Clube
```

Objetivos:

* Reforçar contexto
* Evitar confusão
* Sensação de app de jogo

---

## 🛡️ REGRA GLOBAL DE SEGURANÇA

* Todas as rotas `/liga/*`:

  * passam pelo trait `ResolvesLiga`
  * validam se o usuário pertence à liga

* Se o usuário **não tiver clube na liga**:

  * acesso é bloqueado
  * usuário é redirecionado para criação de clube

---

## 🧩 MEU CLUBE (HUB DO CLUBE)

**Rota:** `/liga/clubes/{meu_clube}`

**Função:** centralizar tudo que é do clube.

**Conteúdo:**

* Perfil do clube (read-only)
* Botões:

  * Meu Elenco
  * Financeiro
  * Editar nome do clube

---

## 🚫 O QUE É PROIBIDO (ANTI-BRECHAS)

* Mostrar Mercado fora de uma liga
* Mostrar Clube sem liga ativa
* Misturar lista de ligas com navegação da liga
* Ter duas barras fixas
* Depender de URL para inferir contexto (usar backend)

---

## 🧠 IMPLEMENTAÇÃO TÉCNICA (RESUMO)

### Backend

* `ResolvesLiga` define:

  * liga ativa
  * clube do usuário
  * modo de navegação (`global` | `liga`)

### Frontend

* Backend injeta:

```js
window.__APP_CONTEXT__ = {
  mode: 'global' | 'liga',
  liga: {...} | null,
  clube: {...} | null
}
```

* Navbar renderiza conforme `mode`

---

## ✅ CHECKLIST DE QUALIDADE (MVP)

* [ ] Usuário nunca fica preso em uma tela
* [ ] Navegação funciona com uma mão
* [ ] Sempre fica claro em qual liga o usuário está
* [ ] Não existem rotas mortas
* [ ] UX se comporta como aplicativo, não site

---

## 📌 FRASE-GUIA DO PROJETO

> **Primeiro o usuário escolhe a liga.**
> **Depois, tudo acontece dentro dela.**
