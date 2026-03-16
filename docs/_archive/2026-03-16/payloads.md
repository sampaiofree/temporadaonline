# Payloads Front-end (React)

## window.__APP_CONTEXT__
```json
{
  "mode": "liga",
  "liga": { "id": 7, "nome": "Liga Demo" },
  "clube": { "id": 42, "nome": "Time XPTO" },
  "nav": "mercado"
}
```
- `mode`: `liga` quando há liga ativa; `global` caso contrário.
- `liga`: `{ id, nome }` ou `null`.
- `clube`: `{ id, nome }` ou `null`.
- `nav`: string opcional para forçar item ativo na navbar (`mercado`, `clube`, `tabela`, etc.).

## window.__LIGA__
```json
{
  "id": 7,
  "nome": "Liga Demo",
  "descricao": "Campeonato teste",
  "imagem": "https://cdn.mco.test/liga.png",
  "jogo": "FC 24",
  "geracao": "PS5",
  "plataforma": "PlayStation",
  "tipo": "aberta",
  "status": "ativa",
  "max_jogadores_por_clube": 18
}
```
- Sempre inclui `id` e `nome`.
- Demais campos aparecem conforme a view: `descricao`, `imagem`, `tipo`, `status`, `jogo`, `geracao`, `plataforma`, `max_jogadores_por_clube`.

## window.__CLUBE__
```json
{
  "id": 42,
  "nome": "Time XPTO"
}
```
- Presente quando o usuário já criou clube na liga; caso contrário `null`.
- Só traz `id` e `nome` (sem escudo/cores).

## window.__MERCADO__
```json
{
  "players": [
    {
      "elencopadrao_id": 101,
      "short_name": "J. Félix",
      "long_name": "João Félix Sequeira",
      "player_positions": "CF,ST",
      "overall": 85,
      "value_eur": 65000000,
      "wage_eur": 180000,
      "club_status": "outro",
      "club_name": "Clube Rival",
      "club_id": 12,
      "is_free_agent": false,
      "can_buy": true,
      "can_multa": true,
      "player_face_url": "https://cdn.sofifa.com/players/101/face.png"
    }
    ]
}
```
- `club_status`: `livre`, `meu`, `outro`.
- `can_buy`/`can_multa`: flags derivadas para a UI.
- Não há `sale_price_eur` nem stats detalhados no payload atual.
 - O backend entrega **todos os jogadores da Confederaçao**, ordenados por `overall`, garantindo que os dados sempre reflitam a liga completa.

## window.__MEU_ELENCO__
```json
{
  "players": [
    {
      "id": 555,              // id da linha em liga_clube_elenco
      "ativo": true,
      "value_eur": 32000000,
      "wage_eur": 95000,
      "elencopadrao": {
        "id": 101,
        "short_name": "J. Félix",
        "long_name": "João Félix Sequeira",
        "player_positions": "CF,ST",
        "overall": 85,
        "player_face_url": "https://cdn.sofifa.com/players/101/face.png"
      }
    }
  ],
  "player_count": 1,
  "max_players": 18,
  "salary_per_round": 95000
}
```
- `players` é array de entradas do elenco do clube.
- `ativo`: status de uso (boolean).

## window.__FINANCEIRO__
```json
{
  "saldo": 125000000,
  "salarioPorRodada": 450000,
  "rodadasRestantes": 277,
  "movimentos": [
    {
      "id": 1,
      "tipo": "compra",
      "valor": -35000000,
      "observacao": "Compra de atacante",
      "created_at": "2024-05-01T12:00:00Z",
      "clube_origem_id": 12,
      "clube_destino_id": 42
    }
  ]
}
```
- `rodadasRestantes` pode ser `null` se não houver gasto fixo.
- `movimentos` pode ser array vazio.

---

# Payloads API (React)

## POST /ligas/{liga}/entrar
Request: corpo vazio.  
Sucesso `200`:
```json
{ "redirect": "/minha_liga?liga_id=7" }
```
Erros: 403 se não autenticado; 404 se liga inexistente.

## POST /minha_liga/clubes
Request:
```json
{ "nome": "Time XPTO" }
```
Sucesso `201`:
```json
{
  "message": "Nome do clube foi alterado com sucesso.",
  "clube": { "id": 42, "nome": "Time XPTO" },
  "financeiro": { "saldo": 50000000 }
}
```
Erros: 422 em validação; 403 se não autenticado.

## POST /api/ligas/{liga}/clubes/{clube}/comprar
Request:
```json
{ "elencopadrao_id": 101 }
```
Sucesso `201`:
```json
{
  "message": "Jogador adicionado ao seu elenco.",
  "entry": { /* retorno do TransferService::buyPlayer */ }
}
```
Erros: `422` com `{ "message": "..." }` para regra de negócio; `409` se mensagem contiver “já faz parte”.

## POST /api/ligas/{liga}/clubes/{clube}/multa
Request:
```json
{ "elencopadrao_id": 101 }
```
Sucesso `200`:
```json
{
  "message": "Multa paga e jogador transferido com sucesso.",
  "entry": { /* retorno do TransferService::payReleaseClause */ }
}
```
Erros: `422` com `{ "message": "..." }` em violação de regra.

## POST /api/ligas/{liga}/clubes/{clube}/vender
Request:
```json
{ "elencopadrao_id": 101, "price": 25000000 }
```
Sucesso `200`:
```json
{
  "message": "Transferência concluída com sucesso.",
  "entry": { /* retorno do TransferService::sellPlayer */ }
}
```
Erros: `422` com `{ "message": "Este jogador está livre. Use a rota de compra de jogador livre." }` ou outra mensagem de domínio.

## POST /api/ligas/{liga}/clubes/{clube}/trocar
Request:
```json
{
  "jogador_a_id": 101,
  "clube_b_id": 12,
  "jogador_b_id": 202,
  "ajuste_valor": 0
}
```
Sucesso `200`:
```json
{
  "message": "Troca realizada com sucesso.",
  "entries": [ /* retorno do TransferService::swapPlayers */ ]
}
```
Erros: `422` com `{ "message": "..." }` em violação de regra.

## POST /elenco/{elenco}/listar-mercado
Request:
```json
{ "preco": 15000000 }
```
Sucesso `200`:
```json
{
  "message": "Jogador listado no mercado com sucesso.",
  "preco": 15000000
}
```
Erros: `422` em validação; `403` se o elenco não pertence ao usuário.

## POST /elenco/{elenco}/vender-mercado
Request: corpo vazio.  
Sucesso `200`:
```json
{ "message": "Jogador devolvido ao mercado com sucesso." }
```
Erros: `403` se o elenco não pertence ao usuário.

---

# APIs futuras (placeholders)
- `POST /api/ligas/{liga}/clubes/{clube}/vender-rapido` — **a implementar**; payload sugerido `{ elencopadrao_id }`; vender por `value_eur * 0.8`.
- `POST /api/ligas/{liga}/clubes/{clube}/colocar-a-venda` — **a implementar**; payload sugerido `{ elencopadrao_id, sale_price_eur }`.
- `POST /api/ligas/{liga}/clubes/{clube}/comprar-venda` — **a implementar**; payload sugerido `{ elencopadrao_id }` compra por `sale_price_eur`.
