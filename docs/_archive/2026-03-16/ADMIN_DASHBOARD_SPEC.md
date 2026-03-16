# ADMIN DASHBOARD — MCO (MASTER CAREER ONLINE)

## 1. VISÃO GERAL

Este documento define o **ambiente administrativo** do MCO.

* Ambiente **totalmente isolado** do jogador/gamer
* Uso exclusivo de **Laravel Blade + Controllers**
* Foco em **Desktop**
* Login **separado** do usuário comum
* Apenas **2 perfis**: `admin` e `gamer`

---

## 2. PRINCÍPIOS

* Nada de React no admin
* Nada de delete físico (apenas desativar/encerrar)
* Tudo que o admin faz gera **log**
* Admin tem **controle total** do sistema

---

## 3. ESTRUTURA DE ROTAS

```
/admin/login
/admin/dashboard
/admin/jogos
/admin/geracoes
/admin/plataformas
/admin/elencos
/admin/ligas
/admin/ligas/{liga}/clubes
/admin/ligas/{liga}/financeiro
/admin/usuarios
```

---

## 4. ESTRUTURA DE PASTAS

```
app/Http/Controllers/Admin/
  AuthController.php
  DashboardController.php
  JogoController.php
  GeracaoController.php
  PlataformaController.php
  ElencoPadraoController.php
  LigaController.php
  ClubeController.php
  FinanceiroController.php
  UsuarioController.php

resources/views/admin/
  auth/
  dashboard/
  jogos/
  geracoes/
  plataformas/
  elencos/
  ligas/
  clubes/
  financeiro/
  usuarios/
  layout.blade.php
```

---

## 5. MÓDULOS ADMINISTRATIVOS

### 5.1 Jogos

Admin pode:

* Criar jogo (EAFC, PES, etc)
* Definir ano
* Ativar / desativar

---

### 5.2 Geração

* Cadastro simples (Nova / Antiga)
* **Sem relacionamento direto com Jogo**
* Usada apenas na criação da Liga

---

### 5.3 Plataforma

* Cadastro (PS5, Xbox, PC, etc)
* Ativar / desativar

---

### 5.4 Elenco Padrão

Admin pode:

* Importar elenco via **CSV**
* Editar jogador
* Corrigir overall
* Definir posição

Regras:

* Preview antes de salvar
* Validação básica

---

### 5.5 Ligas

Admin pode:

* Criar liga
* Editar dados
* Definir jogo, geração e plataforma
* Ativar / desativar
* Encerrar liga

---

### 5.6 Clubes da Liga

Admin pode:

* Ver clubes por liga
* Editar nome e escudo
* Remover clube da liga
* Corrigir saldo financeiro

---

### 5.7 Elenco do Clube

Admin pode:

* Remover jogador bugado
* Ajustar dados manualmente

---

### 5.8 Financeiro

(Admin: `LigaClubeFinanceiro`, `LigaFolhaPagamento`, `LigaTransferencia`)

Admin pode:

* Auditar
* Corrigir erros
* Editar saldo manualmente

Regra obrigatória:

* Campo **motivo**
* Geração de log

---

### 5.9 Usuários

Admin pode:

* Criar usuário
* Editar dados
* Suspender
* Banir
* Ver histórico completo

---

## 6. RESETAR LIGA (RESET DE TEMPORADA)

Função administrativa crítica.

Resetar liga significa:

* Zerar tabela
* Zerar pontos
* Manter clubes
* Manter elencos
* Manter histórico salvo

Uso:

* Bug grave
* Testes
* Temporada cancelada

Regras:

* Confirmação dupla
* Log obrigatório

---

## 7. LOGS DO ADMIN

Tabela sugerida: `admin_logs`

Campos:

* admin_id
* ação
* entidade
* entidade_id
* descrição
* created_at

Nada no admin acontece sem log.

---

## 8. DASHBOARD INICIAL (HOME)

Exibir:

* Total de usuários
* Ligas ativas
* Partidas pendentes
* Alertas críticos
* Últimas ações administrativas

---

## 9. REGRAS DE OURO

* Admin **nunca apaga dados**
* Toda edição gera log
* Financeiro sempre exige motivo
* Sistema deve ser auditável

