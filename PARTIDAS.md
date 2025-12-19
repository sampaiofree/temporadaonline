# MCO — Geração e Agendamento de Partidas

Este documento define **toda a lógica oficial** de geração, agendamento e gestão de partidas no MCO.
O objetivo é ser **automático, justo, escalável** e com **mínimas brechas de abuso**.

---

## 1. Princípios

* Não existe botão de "start da liga"
* As partidas **nascem automaticamente**
* Turno e returno obrigatórios
* Cada liga pode ter **regras próprias de dias e horários**
* O sistema deve funcionar **sem admin no dia a dia**

---

## 2. Configurações da Liga

Definidas **na criação da liga** (editáveis depois pelo admin).

### 2.1 Campos obrigatórios

* `dias_permitidos` (array)

  * Ex: [seg, ter, qua, qui, sex, sab, dom]

* `horarios_permitidos` (array de faixas)

  * Ex: [18:00–23:00]

* `antecedencia_minima_alteracao_horas`

  * Padrão: **10 horas**

* `max_alteracoes_horario`

  * Padrão: **1**

* `prazo_confirmacao_horas`

  * Padrão: **48 horas**

⚠️ Alterações nessas regras **não afetam partidas já finalizadas**.

---

## 3. Disponibilidade do Usuário (Perfil)

Cada usuário define no perfil:

* Dias da semana disponíveis
* Horários disponíveis

Essas informações são usadas para:

* Agendamento automático
* Validação de W.O
* Proteção contra horários abusivos

---

## 4. Geração Automática de Partidas

### 4.1 Quando acontece

* Sempre que um **novo clube entra na liga**

### 4.2 O que é gerado

Para o novo clube:

* 1 jogo como **mandante** contra cada clube existente
* 1 jogo como **visitante** contra cada clube existente

⚠️ Jogos antigos **não são recalculados**.

---

## 5. Agendamento Automático

Para cada partida criada:

### 5.1 Tentativa automática

O sistema cruza:

* Disponibilidade do mandante
* Disponibilidade do visitante
* Regras da liga

Se existir ao menos **1 horário válido**:

* O sistema escolhe o **mais próximo no futuro**
* A partida já nasce como **Agendada**

---

## 6. Partidas sem Cruzamento de Horário

### 6.1 Estado especial

Se **não houver cruzamento automático**, a partida nasce como:

**Status:** `confirmacao_necessaria`

---

### 6.2 Lista de opções

O sistema gera uma lista de **datas e horários válidos**, sempre respeitando:

* Regras da liga
* Disponibilidade individual

Exemplo:

* Ter 20h
* Qua 22h
* Sex 21h

---

### 6.3 Confirmação

* Mandante e visitante selecionam os horários que aceitam
* Quando ambos escolhem o mesmo horário:

  * Partida passa para **Confirmada**

---

### 6.4 Falta de confirmação

Se ninguém confirmar dentro do prazo da liga:

* O sistema **força um horário**
* Sempre dentro da disponibilidade declarada
* Sem W.O automático por isso

---

## 7. Alteração de Horário

### 7.1 Quem pode alterar

* Apenas o **mandante**

### 7.2 Regras

* Máx. de alterações: conforme liga (padrão 1)
* Antecedência mínima: conforme liga (padrão 10h)

### 7.3 Validações

O novo horário deve:

* Estar dentro das regras da liga
* Estar dentro da disponibilidade do visitante

Se não atender → alteração bloqueada

---

## 8. W.O (Walkover)

### 8.1 Quando pode acontecer

W.O só é válido se:

* Horário estava **confirmado**
* Horário estava dentro da disponibilidade do usuário
* Usuário não compareceu

---

### 8.2 Proteções

* Não existe W.O por horário imposto fora da disponibilidade
* Não existe W.O em partidas apenas "sugeridas"

---

## 9. Estados de uma Partida

Uma partida pode estar em um dos seguintes estados:

1. `agendada`
2. `confirmacao_necessaria`
3. `confirmada`
4. `em_andamento`
5. `finalizada`
6. `wo`
7. `cancelada`

---

## 10. Resumo Operacional

* Partidas são geradas automaticamente
* Horários respeitam liga + usuários
* Mandante tem poder limitado
* Sistema evita abuso e dependência de chat
* Escala para ligas grandes sem intervenção humana
* **Conflito de Agenda**: o sistema nunca agenda, sugere ou aceita um horário em que qualquer um dos clubes já possua outra partida ativa (`agendada`, `confirmada`, `em_andamento`). Cada partida ocupa uma janela fixa de 120 minutos. Partidas sem `scheduled_at` não entram no cálculo.

---

## 11. Observação Final

Este modelo foi desenhado para:

* Proteger usuários corretos
* Reduzir conflitos
* Evitar admins apagando incêndio
* Manter a liga sempre ativa
