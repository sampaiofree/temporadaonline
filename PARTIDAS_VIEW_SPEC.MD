# MCO — Liga / Partidas (UI & UX)

Este documento descreve **o layout, comportamento e fluxos de interação** da tela:

`/liga/partidas?liga_id={id}`

Objetivo: manter **tudo em uma única página**, usando **modais** para ações, com foco **mobile first**, clareza de estados e mínimo atrito.

---

## 1. Princípios de Design

* Uma página única (sem rota de detalhe por partida)
* Cards informativos + ações via **modais**
* Uma ação principal por estado
* Mobile first (modais em tela cheia no mobile)
* Nada de lógica complexa no front (backend decide tudo)

---

## 2. Estrutura da Página

### 2.1 Hero

* Título: **Agenda da Liga**
* Subtítulo: nome do clube do usuário

---

### 2.2 Tabs

* **Minhas partidas** (default)
* **Todas as partidas**

Estilo:

* Pill tabs
* Estado local (sem query string)

---

### 2.3 Lista de Partidas

Cada partida é exibida em um **card sólido**.

#### Conteúdo do Card

1. Clubes (mandante vs visitante)
2. Badge de papel do usuário:

   * "Você é mandante"
   * "Você é visitante"
3. Data / Horário
4. Badge de estado
5. Chips informativos (opcional)
6. Rodapé de ações (condicional)

---

## 3. Estados da Partida e Ações

| Estado                 | Ação Principal               | Observação               |
| ---------------------- | ---------------------------- | ------------------------ |
| confirmacao_necessaria | Confirmar horário            | Modal de seleção         |
| agendada               | —                            | Apenas informativo       |
| confirmada             | Check-in                     | Ativo próximo ao horário |
| em_andamento           | Registrar placar · Denunciar | Dois botões              |
| finalizada             | Ver placar                   | Somente leitura          |
| wo                     | Ver motivo                   | Histórico                |
| cancelada              | —                            | Nenhuma ação             |

Regra:

* No máximo **2 botões por card**

---

## 4. Modais

Todos os modais:

* Overlay escuro
* Fechar por botão ou swipe (mobile)
* Botão primário bem destacado

---

### 4.1 Modal — Confirmar Horário

Aparece quando estado = `confirmacao_necessaria`.

Conteúdo:

* Lista de horários disponíveis (checkbox)
* Texto explicativo curto

Exemplo:

```
[ ] Ter 20:00
[ ] Qua 22:00
[ ] Sex 21:00

[ Confirmar horários ]
```

Comportamento:

* Usuário pode marcar vários
* Confirmação envia seleção para o backend

---

### 4.2 Modal — Alterar Horário (Mandante)

Visível apenas se:

* Usuário é mandante
* Estado = `agendada` ou `confirmada`

Conteúdo:

* Seleção de novo horário permitido
* Aviso: alteração pode exigir nova confirmação do visitante

Ações:

* Confirmar alteração
* Cancelar

---

### 4.3 Modal — Check-in

Visível quando:

* Estado = `confirmada`
* Próximo ao horário do jogo

Conteúdo:

* Texto: "Confirme sua presença"
* Botão único: **Check-in**

---

### 4.4 Modal — Registrar Placar

Visível quando:

* Estado = `em_andamento`

Conteúdo:

```
Mandante: [ 2 ]
Visitante: [ 1 ]

[ Enviar resultado ]
```

Regra:

* Input manual (MVP)
* Sem upload de imagem nesta fase

---

### 4.5 Modal — Denúncia

Visível quando:

* Estado = `em_andamento`

Conteúdo:

* Select motivo:

  * Conduta antidesportiva
  * Escalação irregular
  * Outro
* Campo texto opcional

Ação:

* Enviar denúncia

---

### 4.6 Modal — Ver Placar

Visível quando:

* Estado = `finalizada`

Conteúdo:

* Placar final
* Data da partida
* Informações básicas

---

### 4.7 Modal — Ver W.O

Visível quando:

* Estado = `wo`

Conteúdo:

* Quem sofreu W.O
* Motivo
* Data

---

## 5. Chips Informativos no Card

Exibidos abaixo do conteúdo principal:

* "Horário forçado pelo sistema"
* "Sem slot futuro disponível"
* "Aguardando confirmação"

Estilo:

* Pequenos
* Cores neutras ou warning

---

## 6. Hierarquia Visual do Card

Prioridade visual:

1. Clubes
2. Data / Horário
3. Estado
4. Chips
5. Botões

---

## 7. Responsividade

### Desktop

* Cards em coluna única
* Modais centrais

### Mobile

* Cards full-width
* Modais em tela cheia
* Botões grandes

---

## 8. Regras Importantes

* Nenhuma ação muda estado diretamente no front
* Todas as ações passam pelo backend
* Partidas já confirmadas não mudam com edição de disponibilidade

---

## 9. Escopo do MVP

Incluído:

* Confirmação de horário
* Alteração de horário
* Check-in
* Placar manual
* Denúncia simples

Fora do escopo agora:

* Upload de imagem
* OCR
* Chat por partida
* Histórico avançado

---

## 10. Objetivo Final

* Reduzir cliques
* Evitar confusão
* Proteger contra abuso
* Manter experiência rápida e clara

Este documento é a **referência oficial** para implementar o layout e UX da tela de partidas da liga.
