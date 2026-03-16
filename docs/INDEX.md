# Indice da documentacao

Documentacao reestruturada a partir do codigo atual em 2026-03-16.

## Documentos ativos

### Arquitetura
- `docs/arquitetura/modelo-de-dados.md` - entidades principais, agrupamento por dominio e relacoes centrais

### Dominios
- `docs/dominios/mercado.md` - compra, venda, multa, troca, proposta, leilao e favoritos
- `docs/dominios/partidas.md` - ciclo de vida da partida, agendamento, W.O., avaliacao e sumula
- `docs/dominios/financeiro.md` - carteira, ledger, patrocinio, ganhos e impacto financeiro das operacoes

### Integracao
- `docs/integracao/rotas.md` - mapa atualizado de rotas web, API, admin e legacy
- `docs/integracao/payloads.md` - contratos de bootstrap usados pelas views Blade e pela shell legacy

## Arquivo morto

Os `.md` antigos da raiz foram movidos para:

- `docs/_archive/2026-03-16/`

Eles permanecem apenas como referencia historica e nao devem ser tratados como fonte oficial do estado atual do projeto.

## Prioridade de reescrita daqui para frente

Os proximos documentos que valem ser recriados, agora ja dentro da nova arvore `docs/`, sao:

1. legacy por view
2. admin por modulo
3. roadmap da copa da liga

## Regra editorial

Para evitar nova divergencia:
- regras de negocio ficam por dominio
- payloads e rotas ficam separados
- comportamento especifico de tela fica por view apenas quando nao couber no dominio
- docs historicos nao voltam para a raiz do projeto
