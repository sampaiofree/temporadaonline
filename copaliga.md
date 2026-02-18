# Planejamento Copa da Liga (`compaliga.md`)

## 1) Resumo
Este documento define o planejamento funcional e tecnico do modo `Copa` dentro da Liga.
A Copa roda em paralelo ao campeonato de pontos corridos, reaproveitando ao maximo a logica de partidas ja existente.

## 2) Decisoes ja confirmadas
1. A tabela `ligas` tera o campo `copa` (boolean), com default `true`.
2. Se `copa=true`, a Copa ja vale desde a criacao da liga.
3. O clube que entra na liga entra automaticamente na Copa.
4. Grupos da Copa terao sempre 4 clubes.
5. Sorteio de grupos sera aleatorio, preenchendo um grupo por vez conforme clubes entram.
6. Fase de grupos tera jogos de ida e volta entre todos do grupo.
7. Pontuacao e logica de partida seguem as mesmas regras atuais.
8. Criterios de desempate de grupo (ordem): saldo de gols, gols pro, confronto direto.
9. Mata-mata tambem sera ida e volta, inclusive final.
10. Avanco para mata-mata so ocorre quando TODOS os grupos finalizarem.
11. Se clube sair da competicao no meio, aplicar WO automatico.
12. Rotas da Copa ficam separadas com prefixo `liga/copa/...`.
13. Sem cron: progresso automatico baseado em eventos do sistema.

## 3) Itens em aberto (para decidir depois)
1. Chaveamento do mata-mata (confirmar modelo final):
- opcao A: fixo por cruzamento de grupos (recomendado para MVP)
- opcao B: sorteio total apos grupos
- opcao C: hibrido (parte fixa + sorteio)
2. Legacy (como expor Copa na interface legacy):
- opcao A: nao exibir no primeiro ciclo
- opcao B: exibir apenas leitura (tabela/chave)
- opcao C: fluxo completo (agenda, resultado, classificacao)
3. Premiacao da Copa:
- definir regras via conquistas/trofeus em tarefa dedicada

## 4) Observacao critica de regra (importante)
Com grupos de 4 e classificacao de 2 por grupo:
- `classificados = total_clubes / 2`
- Para mata-mata puro sem fase preliminar/bye, `classificados` precisa ser potencia de 2.

Exemplos:
- 8 clubes -> 4 classificados (ok)
- 16 clubes -> 8 classificados (ok)
- 24 clubes -> 12 classificados (nao fecha chave pura)

### Recomendacao simples para MVP
Restringir `max_times` a valores que permitam chave pura: `8, 16, 32, 64...`.
Se manter apenas "multiplo de 8", sera necessario modelar fase preliminar para casos como 24.

## 5) Principio de reuso (meta principal)
Reaproveitar o que ja existe:
1. Cadastro/estado de partida (`partidas`, `PartidaStateService`, `PartidaActionsController`).
2. Agendamento e disponibilidade (`PartidaSchedulerService`).
3. Regras de pontuacao de resultados (vitoria/empate/derrota) ja usadas na classificacao atual.
4. Fluxo de criacao de partidas ao entrar novo clube (ja usado no legado).

## 6) Proposta tecnica recomendada (MVP)

### 6.1 Campos/tabelas
1. `ligas.copa` (bool, default true).
2. Metadados de Copa separados para evitar acoplamento na liga normal:
- `liga_copa_grupos`
- `liga_copa_grupo_clubes`
- `liga_copa_fases`
- `liga_copa_partidas` (metadata da partida da copa, apontando para `partidas.id`)

### 6.2 Por que manter metadata separada
1. Evita poluir regras atuais da liga de pontos corridos.
2. Permite distinguir claramente partida de Liga x partida de Copa sem quebrar telas atuais.
3. Facilita evolucao futura da Copa sem refatorar toda a estrutura de `partidas`.

## 7) Regras de negocio detalhadas

### 7.1 Entrada de clube na liga
Quando clube entrar:
1. Se `liga.copa=false`: nao faz nada para Copa.
2. Se `liga.copa=true`:
- alocar clube no primeiro grupo com vaga (<4)
- se nao houver grupo com vaga, criar novo grupo
- se o grupo atingir 4 clubes, gerar partidas da fase de grupos (ida e volta)

### 7.2 Fase de grupos
1. Cada grupo tem 4 clubes.
2. Cada clube enfrenta os outros 3 em ida e volta.
3. Total por grupo: 12 partidas.
4. Classificacao por grupo:
- pontos
- saldo de gols
- gols pro
- confronto direto

### 7.3 Encerramento de grupos e inicio do mata-mata
1. A cada partida de Copa finalizada (estado `placar_confirmado` ou `wo`), recalcular tabela do grupo.
2. Verificar se todos os grupos da liga terminaram.
3. Somente quando todos terminarem:
- classificar top 2 de cada grupo
- montar chave do mata-mata conforme chaveamento escolhido
- gerar partidas ida/volta da primeira fase eliminatoria

### 7.4 Mata-mata
1. Ida e volta em todas as fases, incluindo final.
2. Vencedor ja vem da sumula (nao criar regra adicional de prorrogação/penalti no sistema).
3. Ao fim de cada fase:
- gerar automaticamente a fase seguinte
- repetir ate campeao

### 7.5 WO automatico por saida do clube
Quando clube sair da liga/confederacao durante Copa:
1. partidas futuras do clube -> WO automatico para adversario
2. partidas ja finalizadas nao mudam
3. registrar evento/auditoria da aplicacao do WO

## 8) Automacao sem cron (event-driven)
A automacao da Copa deve acontecer por gatilhos ja existentes:
1. Evento "clube entrou": alocacao em grupo + possivel geracao de jogos do grupo.
2. Evento "partida finalizada": recalculo de grupo + possivel avanco de fase.
3. Evento "clube saiu": WO automatico nas partidas pendentes.

### 8.1 Idempotencia obrigatoria
Para evitar duplicacao:
1. usar transacao
2. lock por liga/fase
3. checagem de existencia antes de criar grupo, confronto ou fase

## 9) Rotas previstas (`liga/copa/...`)
Sugestao de endpoints (web/API), sem implementacao agora:
1. `GET liga/copa` -> visao geral da copa da liga
2. `GET liga/copa/grupos` -> grupos + classificacao
3. `GET liga/copa/chave` -> arvore do mata-mata
4. `GET liga/copa/partidas` -> partidas da copa do usuario
5. `POST liga/copa/reprocessar` (admin/sistema interno) -> reprocessa estado da copa com seguranca

## 10) Impacto em componentes existentes
1. Entradas de clube:
- reaproveitar ponto onde ja se gera partida de liga para tambem acionar orquestracao da Copa.
2. Finalizacao de partida:
- reaproveitar `PartidaStateService` como ponto de gatilho para orquestracao da Copa.
3. Classificacao:
- reaproveitar logica de agregacao atual (adaptando para escopo de grupo/fase da Copa).

## 11) Plano de implementacao por etapas

### Etapa 1 - Fundacao de dados
1. Campo `ligas.copa`.
2. Estruturas de grupos/fases/partidas da Copa.
3. Indices e constraints para idempotencia.

### Etapa 2 - Alocacao e geracao de grupos
1. Alocar clube em grupo automaticamente.
2. Gerar 12 partidas quando grupo fechar 4 clubes.
3. Garantir que nao duplica confrontos.

### Etapa 3 - Classificacao de grupo
1. Recalculo por evento de partida finalizada.
2. Aplicar criterios de desempate definidos.
3. Expor payload de tabela por grupo.

### Etapa 4 - Mata-mata automatico
1. Detectar fim global da fase de grupos.
2. Classificar top 2.
3. Gerar primeira fase eliminatoria.
4. Avancar fases automaticamente ate final/campeao.

### Etapa 5 - Rotas e telas
1. `liga/copa/...` para grupos, chave e partidas.
2. Manter separacao da tela de pontos corridos.
3. Deixar legado como decisao posterior.

### Etapa 6 - Regras de excecao
1. WO automatico quando clube sair.
2. Reprocessamento seguro de copa (sem cron).
3. Logs e auditoria de mudancas de fase.

## 12) Casos de teste (alto nivel)
1. Clube entra em liga com `copa=true` -> entra em grupo automaticamente.
2. Grupo fecha 4 clubes -> cria 12 partidas exatamente uma vez.
3. Finalizar partidas do grupo -> classifica corretamente com desempate definido.
4. Todos grupos finalizados -> mata-mata criado automaticamente.
5. Finalizar ida/volta da fase -> proxima fase criada sem duplicacao.
6. Saida de clube no meio -> WO automatico nas partidas pendentes.
7. Liga com `copa=false` -> nenhum artefato de copa gerado.

## 13) Criterios de aceite do MVP
1. Copa funciona de ponta a ponta sem acao manual de admin.
2. Nenhuma duplicacao de grupo/fase/partida em reentradas de fluxo.
3. Pontos corridos continuam funcionando sem regressao.
4. Copa isolada em rotas `liga/copa/...`.

## 14) Riscos e mitigacoes
1. Risco: condicao de corrida ao criar fases/partidas.
- Mitigacao: transacao + lock + constraints unicas.
2. Risco: mistura de partidas de Liga e Copa em consultas antigas.
- Mitigacao: metadata de Copa separada e queries explicitas por contexto.
3. Risco: regra de tamanho da liga nao fechar chave eliminatoria pura.
- Mitigacao: restringir tamanhos validos no MVP (8/16/32/64...) ou implementar fase preliminar.

## 15) Proximos passos
1. Confirmar regra final de tamanho valido de liga para Copa (recomendado: 8/16/32/64...).
2. Confirmar chaveamento final do mata-mata.
3. Confirmar estrategia do legacy para o primeiro ciclo.
4. Confirmar desenho de premiacao por conquistas/trofeus.
