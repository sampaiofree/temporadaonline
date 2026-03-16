# Dominio: partidas

Documento funcional do dominio de partidas com base no codigo atual em 2026-03-16.

## Escopo

O dominio cobre:
- geracao de confrontos
- agendamento por disponibilidade
- check-in
- registro e confirmacao de placar
- W.O. por desistencia
- reclamacao
- avaliacao do adversario
- sumula/desempenho por jogador
- disparo de folha/premiacao financeira associada a resultado

## Entidades centrais

- `Partida`
- `PartidaDesempenho`
- `PartidaEvento`
- `PartidaAlteracao`
- `PartidaAvaliacao`
- `ReclamacaoPartida`
- `PartidaFolhaPagamento`

## Services principais

- `PartidaSchedulerService`
- `PartidaStateService`
- `PartidaPayrollService`
- `PartidaDesempenhoAiService`

## Estados da partida

Mapa atual em `PartidaStateService`:
- `confirmacao_necessaria`
- `agendada`
- `confirmada`
- `placar_registrado`
- `placar_confirmado`
- `em_reclamacao`
- `finalizada`
- `wo`
- `cancelada`

Transicoes relevantes:
- `confirmacao_necessaria` -> `agendada|confirmada|cancelada|wo`
- `agendada` -> `confirmada|placar_registrado|cancelada|wo`
- `confirmada` -> `placar_registrado|finalizada|wo|cancelada`
- `placar_registrado` -> `placar_confirmado|em_reclamacao`
- `em_reclamacao` -> `placar_confirmado`

Efeito colateral importante:
- quando a transicao muda para `placar_confirmado` ou `wo`, a folha/pagamento da partida pode ser cobrada automaticamente

## Geracao de partidas

Responsavel:
- `PartidaSchedulerService::generateMatchesForNewClub()`

Regra atual:
- quando um clube entra na liga, o service garante turno e returno contra todos os outros clubes
- para cada adversario, existe uma partida como mandante e outra como visitante
- a partida nasce em `confirmacao_necessaria`

## Agendamento

Entradas principais:
- `GET /api/partidas/{partida}/slots`
- `POST /api/partidas/{partida}/agendar`

Regras:
- apenas participantes podem agendar
- estados aceitos: `confirmacao_necessaria`, `confirmada`, `agendada`
- a disponibilidade usada e a do adversario do usuario que esta marcando
- o horizonte de busca atual e `30` dias
- slots sao gerados em blocos de `30` minutos
- conflitos consideram qualquer partida `agendada` ou `confirmada` no mesmo bloco
- ao agendar uma partida em `confirmacao_necessaria`, o estado vai para `confirmada`

Timezone:
- a exibicao e validacao usam `Liga::resolveTimezone()`
- o horario persistido e UTC

## Check-in

Entrada:
- `POST /api/partidas/{partida}/checkin`

Regras:
- permitido apenas em `confirmada`
- exige `scheduled_at`
- janela valida: de 30 minutos antes ate 15 minutos depois do horario
- grava `checkin_mandante_at` ou `checkin_visitante_at`

## Registro de placar

Entrada:
- `POST /api/partidas/{partida}/registrar-placar`

Regras:
- apenas participante
- permitido apenas em `confirmada`
- exige `placar_mandante` e `placar_visitante` inteiros >= 0
- ao registrar:
  - estado vira `placar_registrado`
  - salva usuario registrante e timestamp
  - gera evento `placar_registrado`

## Confirmacao de placar

Entrada:
- `POST /api/partidas/{partida}/confirmar-placar`

Regras:
- apenas o adversario do registrante pode confirmar
- permitido apenas em `placar_registrado`
- antes de confirmar, o usuario precisa ter avaliado o adversario
- ao confirmar:
  - estado vira `placar_confirmado`
  - a folha/premiacao da partida pode ser processada automaticamente

## Reclamacao

Entrada:
- `POST /api/partidas/{partida}/reclamacoes`

Regras:
- apenas o adversario do registrante pode contestar
- permitido apenas em `placar_registrado`
- exige `motivo` e `descricao`
- ao reclamar:
  - cria `ReclamacaoPartida`
  - estado vira `em_reclamacao`
  - gera evento `placar_reclamacao`

## Avaliacao do adversario

Entrada:
- `POST /api/partidas/{partida}/avaliacoes`

Regras:
- apenas participante
- estados aceitos: `placar_registrado`, `placar_confirmado`, `em_reclamacao`, `finalizada`
- cada usuario pode avaliar uma vez por partida
- nota obrigatoria de 1 a 5

## W.O. por desistencia

Entrada:
- `POST /api/partidas/{partida}/desistir`

Regra atual:
- permitido apenas em `confirmada`
- exige `scheduled_at`
- permitido ate antes do horario da partida
- no horario da partida ou depois, a API bloqueia

Efeito:
- estado vira `wo`
- placar vira `3x0` para o adversario
- preenche `wo_para_user_id` e `wo_motivo`
- gera evento `wo_declarado`
- dispara o fluxo financeiro associado a W.O.

Superficies onde a acao aparece:
- Legacy `match-center`
- Legacy `schedule-matches`
- tela nao-legacy de partidas

## Sumula / desempenho dos jogadores

Entradas:
- `GET /api/partidas/{partida}/desempenho/form`
- `POST /api/partidas/{partida}/desempenho/preview`
- `POST /api/partidas/{partida}/desempenho/confirm`

### Disponibilidade da sumula

Regras:
- apenas participantes
- estados aceitos para abrir/finalizar: `confirmada` e `agendada`
- se a partida ja estiver em `placar_registrado`, `placar_confirmado`, `finalizada` ou `wo`, o fluxo e bloqueado
- se o mercado estiver aberto (`LigaPeriodo` ativo), o envio de sumula fica bloqueado

Esse bloqueio vale para:
- web moderna
- `legacy?view=match-center`
- `legacy?view=report-match`

### Formulario base

O endpoint `form` retorna os elencos completos de mandante e visitante, ja em formato editavel.

Cada linha do formulario nasce com:
- `elencopadrao_id`
- `nome`
- `short_name`
- `long_name`
- `nota` vazia
- `gols = 0`
- `assistencias = 0`

### Preview por imagem

O endpoint `preview` usa `PartidaDesempenhoAiService` para tentar reconhecer a sumula a partir de duas imagens.

Regra atual importante:
- falha de IA nao quebra mais o fluxo
- em caso de erro operacional, a resposta vem com `analysis_failed = true` e mensagem de aviso
- o usuario pode seguir preenchendo manualmente

### Confirmacao final da sumula

O endpoint `confirm` hoje e tolerante.

Regras:
- aceita o formulario completo dos dois lados
- ignora linhas fora do elenco permitido
- deduplica por `elencopadrao_id`
- linha sem `nota` e considerada inativa e nao e salva
- `gols` e `assistencias` invalidos viram `0`
- o placar manual continua soberano, mesmo que nao bata com a soma dos gols
- ao confirmar:
  - apaga `PartidaDesempenho` antigo da partida
  - recria as linhas validas
  - preenche placar na partida
  - estado vira `placar_registrado`

## Match center e legacy

A shell legacy e hoje a superficie mais rica para partidas:
- `legacy?view=match-center`
- `legacy?view=schedule-matches`
- `legacy?view=report-match`
- `legacy?view=confirm-match`
- `legacy?view=evaluate-match`

Capacidades ja conectadas:
- carregar confrontos do usuario
- agendar e reagendar
- finalizar partida por sumula
- confirmar resultado
- avaliar adversario
- desistir e aplicar W.O.
- bloquear sumula quando o mercado estiver aberto

## Regras de consistencia

- todas as acoes sensiveis validam se o usuario e participante da partida
- transicoes de estado passam por `PartidaStateService`
- eventos relevantes sao gravados em `PartidaEvento`
- o financeiro nao depende da UI; ele e disparado pela transicao de estado

## Decisoes atuais importantes

- W.O. por desistencia nao exige mais antecedencia minima de 60 minutos; basta ser antes de `scheduled_at`
- a sumula agora abre com os dois elencos completos, sem depender da IA
- falhas de IA nao interrompem o fluxo manual
- mercado aberto bloqueia envio de sumula, mas nao bloqueia o restante da leitura das partidas
