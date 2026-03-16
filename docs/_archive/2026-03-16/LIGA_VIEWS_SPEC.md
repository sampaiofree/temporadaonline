# LIGA – ESPECIFICAÇÃO DE VIEWS (MVP)

Este documento descreve **todas as views da Liga**, seus objetivos, regras de acesso e responsabilidades. Serve como referência única para frontend (React) e backend.

---

## 🔐 REGRA GLOBAL DE ACESSO

* Ao entrar em uma liga, o usuário sempre cai na **Dashboard da Liga**.
* Se o usuário **não criou clube**, todas as outras views ficam bloqueadas.
* O sistema redireciona automaticamente para **Criar Clube**.

---

## 1️⃣ Dashboard da Liga

**View:** `LigaDashboard`

### Objetivo

Visão geral rápida da liga e ponto central de navegação.

### Conteúdo

* Nome da liga
* Nome do clube do usuário
* Próxima partida do clube
* Posição atual na classificação

### Ações

* Acessar Mercado
* Acessar Meu Elenco
* Acessar Partidas
* Acessar Classificação

---

## 2️⃣ Criar / Editar Clube

**View:** `ClubeForm`

### Objetivo

Criar ou editar o clube do usuário dentro da liga.

### Regras

* Clube é obrigatório para acessar qualquer outra funcionalidade.
* O clube possui **apenas nome** (escudo e cores ficam para versões futuras).
* O nome pode ser alterado quantas vezes quiser.

### Campos

* Nome do clube (input simples)

---

## 3️⃣ Mercado

**View:** `MercadoJogadores`

### Objetivo

Exibir **todos os jogadores da Confederação**, permitindo compra, venda, troca ou multa.

### Listagem de Jogadores

Cada jogador deve exibir:

* Nome
* Posição
* Overall
* Valor (`value_eur`)
* Clube atual:

  * Livre
  * Pertence ao Clube X

### Ações

* Comprar jogador livre
* Roubar jogador via multa (cláusula)

### Regras Importantes

* Limite de elenco: **18 jogadores por clube**
* Compra bloqueada se ultrapassar o limite
* Multa:

  * Valor = `value_eur * ligas.multa_multiplicador`
  * Clube atual não pode recusar
* Compra/roubo entra **imediatamente** no elenco

---

## 4️⃣ Meu Elenco

**View:** `MeuElenco`

### Objetivo

Gerenciar os jogadores do próprio clube.

### Informações exibidas

* Contador: `X / 18 jogadores`
* Custo total de salários por rodada

### Card do Jogador

* Nome
* Posição
* Overall
* Salário por rodada (`wage_eur`)
* Status:

  * Ativo
  * Inativo

### Ações disponíveis

* Vender jogador
* Trocar jogador

---

## 5️⃣ Troca de Jogadores

**Componente:** `TrocaJogadorModal`

### Tipos de troca

1. Jogador ↔ Jogador
2. Jogador ↔ Jogador + dinheiro

### Regras

* Não pode ultrapassar 18 jogadores após a troca
* Ajuste financeiro deve ser validado
* Operação registrada em `liga_transferencias`

---

## 6️⃣ Partidas

**View:** `PartidasLiga`

### Objetivo

Visualizar partidas do clube e da liga.

### Abas / Filtros

* Minhas Partidas
* Todas as Partidas da Liga

### Cada partida exibe

* Clubes
* Rodada
* Status:

  * Agendada
  * Em andamento
  * Finalizada

---

## 7️⃣ Detalhe da Partida

**View:** `PartidaDetalhe`

### Objetivo

Executar ações relacionadas à partida.

### Ações

* Confirmar presença (check-in)
* Enviar resultado
* Abrir chat da partida

### Resultados

* Apenas placar
* Somente leitura (por enquanto)

---

## 8️⃣ Classificação / Ranking

**View:** `ClassificacaoLiga`

### Objetivo

Exibir a tabela de classificação da liga.

### Colunas

* Posição
* Clube
* Pontos
* Vitórias

### Critérios

1. Pontos
2. Número de vitórias

### Ação

* Clique no clube abre o **Perfil do Clube**

---

## 9️⃣ Perfil do Clube

**View:** `ClubePerfil`

### Objetivo

Exibir informações públicas de um clube.

### Conteúdo

* Nome do clube
* Dono
* Elenco
* Estatísticas básicas (futuro)

---

## 💰 Regras Financeiras (Resumo)

* Salário cobrado **por rodada**
* Total = soma de `wage_eur` dos jogadores ativos
* Débito automático na virada da rodada
* Saldo negativo pode bloquear compras

---

## 📦 Lista Final de Views (MVP)

```
LigaDashboard
ClubeForm
MercadoJogadores
MeuElenco
TrocaJogadorModal
PartidasLiga
PartidaDetalhe
ClassificacaoLiga
ClubePerfil
```

---

## ✅ Observações Finais

* Arquitetura compatível com `LIGA_FINANCEIRO_SPEC.md`
* Compatível com `Design System – Flat Neon Sport`
* Foco total em **mobile**
* MVP pronto para evolução sem refatoração grande
