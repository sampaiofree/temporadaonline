<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaProposta;
use App\Services\MarketWindowService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LigaPropostaController extends Controller
{
    public function __construct(private readonly MarketWindowService $marketWindowService)
    {
    }

    public function index(Request $request, Liga $liga, LigaClube $clube): JsonResponse
    {
        $this->ensureClubAccess($request, $liga, $clube);

        $baseQuery = LigaProposta::query()
            ->with(['elencopadrao', 'clubeOrigem', 'clubeDestino', 'ligaOrigem', 'ligaDestino'])
            ->where('status', 'aberta')
            ->orderByDesc('created_at');

        $recebidas = (clone $baseQuery)
            ->where('clube_origem_id', $clube->id)
            ->get();

        $enviadas = (clone $baseQuery)
            ->where('clube_destino_id', $clube->id)
            ->get();

        $offerIds = $recebidas
            ->concat($enviadas)
            ->flatMap(fn (LigaProposta $proposta) => $proposta->oferta_elencopadrao_ids ?? [])
            ->unique()
            ->values();

        $offerPlayers = $offerIds->isNotEmpty()
            ? Elencopadrao::query()
                ->whereIn('id', $offerIds->all())
                ->get(['id', 'short_name', 'long_name', 'overall', 'player_positions', 'player_face_url'])
                ->keyBy('id')
            : collect();

        $payload = [
            'recebidas' => $recebidas->map(fn (LigaProposta $proposta) => $this->mapProposta($proposta, $offerPlayers))
                ->values(),
            'enviadas' => $enviadas->map(fn (LigaProposta $proposta) => $this->mapProposta($proposta, $offerPlayers))
                ->values(),
        ];

        return response()->json($payload);
    }

    public function store(Request $request, Liga $liga, LigaClube $clube): JsonResponse
    {
        $this->ensureClubAccess($request, $liga, $clube);
        if ($blocked = $this->ensureProposalAllowed($liga)) {
            return $blocked;
        }

        $data = $request->validate([
            'elencopadrao_id' => ['required', 'integer', 'exists:elencopadrao,id'],
            'valor' => ['nullable', 'integer', 'min:0'],
            'oferta_elencopadrao_ids' => ['array'],
            'oferta_elencopadrao_ids.*' => ['integer', 'distinct', 'exists:elencopadrao,id'],
        ]);

        $valor = (int) ($data['valor'] ?? 0);
        $offerIds = array_values(array_unique(array_map('intval', $data['oferta_elencopadrao_ids'] ?? [])));

        if ($valor <= 0 && empty($offerIds)) {
            return response()->json([
                'message' => 'Informe valor ou jogadores para enviar a proposta.',
            ], 422);
        }

        if (in_array((int) $data['elencopadrao_id'], $offerIds, true)) {
            return response()->json([
                'message' => 'O jogador alvo nao pode estar entre os jogadores oferecidos.',
            ], 422);
        }

        $entryQuery = LigaClubeElenco::query()
            ->where('elencopadrao_id', (int) $data['elencopadrao_id']);

        if ($liga->confederacao_id) {
            $entryQuery->where('confederacao_id', $liga->confederacao_id);
        } else {
            $entryQuery->where('liga_id', $liga->id);
        }

        $entry = $entryQuery->first();
        if (! $entry || ! $entry->ativo) {
            return response()->json([
                'message' => 'Jogador nao esta vinculado a nenhum clube.',
            ], 422);
        }

        if ((int) $entry->liga_clube_id === (int) $clube->id) {
            return response()->json([
                'message' => 'Voce ja possui este jogador.',
            ], 422);
        }

        $clubeOrigem = LigaClube::query()->with('liga')->find($entry->liga_clube_id);
        if (! $clubeOrigem) {
            return response()->json([
                'message' => 'Clube de origem nao encontrado.',
            ], 422);
        }

        if ($liga->confederacao_id && (int) $clubeOrigem->liga?->confederacao_id !== (int) $liga->confederacao_id) {
            return response()->json([
                'message' => 'Jogador nao pertence a esta confederacao.',
            ], 422);
        }

        if ($offerIds) {
            $offerQuery = LigaClubeElenco::query()
                ->where('liga_clube_id', $clube->id)
                ->whereIn('elencopadrao_id', $offerIds)
                ->where('ativo', true);

            if ($liga->confederacao_id) {
                $offerQuery->where('confederacao_id', $liga->confederacao_id);
            } else {
                $offerQuery->where('liga_id', $liga->id);
            }

            $offerCount = $offerQuery->count();
            if ($offerCount !== count($offerIds)) {
                return response()->json([
                    'message' => 'Um ou mais jogadores oferecidos nao pertencem ao seu clube.',
                ], 422);
            }
        }

        $proposta = LigaProposta::create([
            'confederacao_id' => $liga->confederacao_id,
            'liga_origem_id' => $entry->liga_id,
            'liga_destino_id' => $liga->id,
            'elencopadrao_id' => $entry->elencopadrao_id,
            'clube_origem_id' => $entry->liga_clube_id,
            'clube_destino_id' => $clube->id,
            'valor' => $valor,
            'oferta_elencopadrao_ids' => $offerIds,
            'status' => 'aberta',
        ]);

        return response()->json([
            'message' => 'Proposta enviada com sucesso.',
            'proposta_id' => $proposta->id,
        ], 201);
    }

    public function accept(Request $request, Liga $liga, LigaClube $clube, LigaProposta $proposta, TransferService $transferService): JsonResponse
    {
        $this->ensureClubAccess($request, $liga, $clube);
        if ($blocked = $this->ensureProposalAllowed($liga)) {
            return $blocked;
        }

        if ((int) $proposta->clube_origem_id !== (int) $clube->id || (int) $proposta->liga_origem_id !== (int) $liga->id) {
            abort(403);
        }

        try {
            $transferService->acceptProposal($proposta);

            return response()->json([
                'message' => 'Proposta aceita com sucesso.',
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request, Liga $liga, LigaClube $clube, LigaProposta $proposta): JsonResponse
    {
        $this->ensureClubAccess($request, $liga, $clube);
        if ($blocked = $this->ensureProposalAllowed($liga)) {
            return $blocked;
        }

        if ((int) $proposta->clube_origem_id !== (int) $clube->id || (int) $proposta->liga_origem_id !== (int) $liga->id) {
            abort(403);
        }

        if ($proposta->status !== 'aberta') {
            return response()->json([
                'message' => 'Proposta nao esta mais disponivel.',
            ], 422);
        }

        $proposta->update(['status' => 'rejeitada']);

        return response()->json([
            'message' => 'Proposta rejeitada.',
        ]);
    }

    public function cancel(Request $request, Liga $liga, LigaClube $clube, LigaProposta $proposta): JsonResponse
    {
        $this->ensureClubAccess($request, $liga, $clube);
        if ($blocked = $this->ensureProposalAllowed($liga)) {
            return $blocked;
        }

        if ((int) $proposta->clube_destino_id !== (int) $clube->id || (int) $proposta->liga_destino_id !== (int) $liga->id) {
            abort(403);
        }

        if ($proposta->status !== 'aberta') {
            return response()->json([
                'message' => 'Proposta nao esta mais disponivel.',
            ], 422);
        }

        $proposta->update(['status' => 'cancelada']);

        return response()->json([
            'message' => 'Proposta cancelada.',
        ]);
    }

    private function ensureClubAccess(Request $request, Liga $liga, LigaClube $clube): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ((int) $clube->user_id !== (int) $user->id) {
            abort(403);
        }

        if ((int) $clube->liga_id !== (int) $liga->id) {
            abort(403);
        }

        if (! $user->ligas()->where('ligas.id', $liga->id)->exists()) {
            abort(403);
        }
    }

    private function mapProposta(LigaProposta $proposta, $offerPlayers): array
    {
        $target = $proposta->elencopadrao;
        $offerIds = $proposta->oferta_elencopadrao_ids ?? [];

        $offer = collect($offerIds)->map(function ($id) use ($offerPlayers) {
            $player = $offerPlayers->get($id);
            if (! $player) {
                return ['id' => (int) $id];
            }

            return [
                'id' => $player->id,
                'short_name' => $player->short_name,
                'long_name' => $player->long_name,
                'overall' => $player->overall,
                'player_positions' => $player->player_positions,
                'player_face_url' => $player->player_face_url,
            ];
        })->values();

        return [
            'id' => $proposta->id,
            'status' => $proposta->status,
            'valor' => (int) $proposta->valor,
            'created_at' => $proposta->created_at,
            'elencopadrao' => $target ? [
                'id' => $target->id,
                'short_name' => $target->short_name,
                'long_name' => $target->long_name,
                'overall' => $target->overall,
                'player_positions' => $target->player_positions,
                'player_face_url' => $target->player_face_url,
            ] : null,
            'clube_origem' => $proposta->clubeOrigem ? [
                'id' => $proposta->clubeOrigem->id,
                'nome' => $proposta->clubeOrigem->nome,
                'liga_nome' => $proposta->ligaOrigem?->nome,
            ] : null,
            'clube_destino' => $proposta->clubeDestino ? [
                'id' => $proposta->clubeDestino->id,
                'nome' => $proposta->clubeDestino->nome,
                'liga_nome' => $proposta->ligaDestino?->nome,
            ] : null,
            'oferta_jogadores' => $offer,
        ];
    }

    private function ensureProposalAllowed(Liga $liga): ?JsonResponse
    {
        if ($this->marketWindowService->isAuctionActive($liga)) {
            return response()->json([
                'message' => 'Mercado em modo leilao. Propostas entre clubes estao bloqueadas.',
            ], 423);
        }

        return null;
    }
}
