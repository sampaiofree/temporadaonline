# Modelo de dados (Eloquent)

Fonte: `app/Models/*.php`

## Hierarquia (relacoes principais)

Observacao: hierarquia baseada em relacoes Eloquent (belongsTo/hasOne/hasMany/belongsToMany). Alguns pais aparecem apenas pelo lado belongsTo do filho. As relacoes abaixo refletem o que esta declarado nos modelos.

Plataforma
- Liga
- Profile

Jogo
- Liga
- Elencopadrao
- Profile

Geracao
- Liga
- Profile

Confederacao
- Liga
- LigaClube

Liga
- LigaClube
  - LigaClubeElenco
  - LigaClubeConquista
  - LigaClubePatrocinio
  - LigaClubeFinanceiro
  - LigaFolhaPagamento
  - Partida (mandante/visitante)
  - PartidaDesempenho
  - PartidaFolhaPagamento
  - LigaProposta (clube_origem/clube_destino)
  - LigaTransferencia (clube_origem/clube_destino)
- LigaTransferencia
- LigaPeriodo
- LigaLeilao
- LigaJogador (pivot User <-> Liga)
- Partida
- PartidaFolhaPagamento
- LigaFolhaPagamento

Partida
- PartidaAlteracao
- PartidaEvento
- PartidaDesempenho
- PartidaAvaliacao
- PartidaDenuncia
- ReclamacaoPartida
- PartidaFolhaPagamento

User
- Profile
- LigaClube
- UserDisponibilidade
- LigaJogador (pivot User <-> Liga)
- PartidaAlteracao
- PartidaEvento
- PartidaAvaliacao (avaliador/avaliado)
- PartidaDenuncia
- ReclamacaoPartida
- PlayerFavorite

Pais
- LigaEscudo
- EscudoClube

Elencopadrao
- LigaClubeElenco
- LigaTransferencia
- LigaProposta
- PartidaDesempenho
- PlayerFavorite

Catalogos e assets (sem relacoes diretas nos modelos)
- AppAsset
- ConquistaImagem
- PatrocinioImagem
- PremiacaoImagem
- Conquista
- Patrocinio
- Premiacao
- Playstyle
- WhatsappConnection
- Temporada

## Modelos e relacoes

### AppAsset
- Relacoes: nenhuma

### Confederacao
- belongsTo: Jogo (jogo), Geracao (geracao), Plataforma (plataforma)
- hasMany: Liga (ligas), Temporada (temporadas)

### Conquista
- Relacoes: nenhuma

### ConquistaImagem
- Relacoes: nenhuma

### Elencopadrao
- belongsTo: Jogo (jogo)
- hasMany: LigaClubeElenco (ligaClubeElencos), LigaTransferencia (ligaTransferencias)

### EscudoClube
- belongsTo: Pais (pais), LigaEscudo (liga)

### Geracao
- hasMany: Profile (profiles), Liga (ligas)

### Jogo
- hasMany: Profile (profiles), Liga (ligas), Elencopadrao (elencoPadrao)

### Liga
- belongsTo: Jogo (jogo), Geracao (geracao), Plataforma (plataforma), Confederacao (confederacao)
- belongsToMany: User (users) via `liga_jogador`
- hasMany: LigaClube (clubes), LigaTransferencia (transferencias), LigaPeriodo (periodos), LigaLeilao (leiloes)

### Temporada
- belongsTo: Confederacao (confederacao)

### LigaClube
- belongsTo: Liga (liga), Confederacao (confederacao), User (user), EscudoClube (escudo)
- hasMany: LigaClubeElenco (clubeElencos), LigaClubeConquista (conquistas)
- hasOne: LigaClubeFinanceiro (financeiro)

### LigaClubeConquista
- belongsTo: Liga (liga), LigaClube (clube), Conquista (conquista)

### LigaClubeElenco
- belongsTo: Liga (liga), Confederacao (confederacao), LigaClube (ligaClube), Elencopadrao (elencopadrao)

### LigaClubeFinanceiro
- belongsTo: Liga (liga), LigaClube (clube)

### LigaClubePatrocinio
- belongsTo: Liga (liga), LigaClube (clube), Patrocinio (patrocinio)

### LigaEscudo
- belongsTo: Pais (pais)

### LigaFolhaPagamento
- belongsTo: Liga (liga), LigaClube (clube)

### LigaJogador
- belongsTo: Liga (liga), User (user)

### LigaLeilao
- belongsTo: Liga (liga)

### LigaPeriodo
- belongsTo: Liga (liga)

### LigaProposta
- belongsTo: Liga (liga_origem, liga_destino), Confederacao (confederacao), LigaClube (clube_origem, clube_destino), Elencopadrao (elencopadrao)

### LigaTransferencia
- belongsTo: Liga (liga, liga_origem, liga_destino), Confederacao (confederacao), Elencopadrao (elencopadrao), LigaClube (clube_origem, clube_destino)

### Pais
- Relacoes: nenhuma

### Partida
- belongsTo: Liga (liga), LigaClube (mandante), LigaClube (visitante)
- hasMany: PartidaAlteracao (alteracoes), PartidaEvento (eventos), PartidaDesempenho (desempenhos), ReclamacaoPartida (reclamacoes), PartidaAvaliacao (avaliacoes), PartidaDenuncia (denuncias)

### PartidaAlteracao
- belongsTo: Partida (partida), User (user)

### PartidaAvaliacao
- belongsTo: Partida (partida), User (avaliador), User (avaliado)

### PartidaDenuncia
- belongsTo: Partida (partida), User (user)

### PartidaDesempenho
- belongsTo: Partida (partida), LigaClube (ligaClube), Elencopadrao (elencopadrao)

### PartidaEvento
- belongsTo: Partida (partida), User (user)

### PartidaFolhaPagamento
- belongsTo: Liga (liga), Partida (partida), LigaClube (clube)

### Patrocinio
- Relacoes: nenhuma

### PatrocinioImagem
- Relacoes: nenhuma

### Plataforma
- hasMany: Profile (profiles), Liga (ligas)

### PlayerFavorite
- belongsTo: User (user), Liga (liga), Confederacao (confederacao), Elencopadrao (player)

### Playstyle
- Relacoes: nenhuma

### Premiacao
- Relacoes: nenhuma

### PremiacaoImagem
- Relacoes: nenhuma

### Profile
- belongsTo: User (user), Plataforma (plataformaRegistro), Jogo (jogoRegistro), Geracao (geracaoRegistro)

### ReclamacaoPartida
- belongsTo: Partida (partida), User (user)

### User
- hasOne: Profile (profile)
- belongsToMany: Liga (ligas) via `liga_jogador`
- hasMany: LigaClube (clubesLiga), UserDisponibilidade (disponibilidades)

### UserDisponibilidade
- belongsTo: User (user)

### WhatsappConnection
- Relacoes: nenhuma
