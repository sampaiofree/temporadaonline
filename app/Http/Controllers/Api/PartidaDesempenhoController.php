<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\Partida;
use App\Models\PartidaDesempenho;
use App\Services\PartidaDesempenhoAiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartidaDesempenhoController extends Controller
{
    public function __construct(private readonly PartidaDesempenhoAiService $aiService)
    {
    }

    public function preview(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertVisitor($user->id, $partida);
        $this->assertAllowedState($partida);

        $data = $request->validate([
            'mandante_imagem' => ['required', 'image', 'max:6144'],
            'visitante_imagem' => ['required', 'image', 'max:6144'],
        ]);

        $partida->loadMissing(['mandante', 'visitante']);
        $mandanteRoster = $this->loadRoster($partida->mandante);
        $visitanteRoster = $this->loadRoster($partida->visitante);

        try {
            $analysis = $this->aiService->analyzeMatch(
                $data['mandante_imagem'],
                $data['visitante_imagem'],
                $mandanteRoster['payload'],
                $visitanteRoster['payload'],
            );
        } catch (\Throwable $exception) {
            Log::warning('Erro ao analisar desempenho', [
                'partida_id' => $partida->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Não foi possível analisar as imagens. Tente novamente.',
            ], 422);
        }

        $mandanteEntries = $this->mapEntries($analysis['mandante']['entries'], $mandanteRoster['map']);
        $visitanteEntries = $this->mapEntries($analysis['visitante']['entries'], $visitanteRoster['map']);

        $placarMandante = $this->sumGoals($mandanteEntries);
        $placarVisitante = $this->sumGoals($visitanteEntries);

        return response()->json([
            'mandante' => [
                'entries' => $mandanteEntries,
                'unknown_players' => $analysis['mandante']['unknown_players'],
            ],
            'visitante' => [
                'entries' => $visitanteEntries,
                'unknown_players' => $analysis['visitante']['unknown_players'],
            ],
            'placar' => [
                'mandante' => $placarMandante,
                'visitante' => $placarVisitante,
            ],
        ]);
    }

    public function confirm(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertVisitor($user->id, $partida);
        $this->assertAllowedState($partida);

        $data = $request->validate([
            'mandante' => ['required', 'array', 'min:1'],
            'mandante.*.elencopadrao_id' => ['required', 'integer'],
            'mandante.*.nota' => ['required', 'numeric', 'min:0', 'max:10'],
            'mandante.*.gols' => ['required', 'integer', 'min:0'],
            'mandante.*.assistencias' => ['required', 'integer', 'min:0'],
            'visitante' => ['required', 'array', 'min:1'],
            'visitante.*.elencopadrao_id' => ['required', 'integer'],
            'visitante.*.nota' => ['required', 'numeric', 'min:0', 'max:10'],
            'visitante.*.gols' => ['required', 'integer', 'min:0'],
            'visitante.*.assistencias' => ['required', 'integer', 'min:0'],
        ]);

        $partida->loadMissing(['mandante', 'visitante']);
        $mandanteRosterIds = $this->loadRosterIds($partida->mandante);
        $visitanteRosterIds = $this->loadRosterIds($partida->visitante);

        $this->assertRosterMembership($data['mandante'], $mandanteRosterIds);
        $this->assertRosterMembership($data['visitante'], $visitanteRosterIds);
        $this->assertUniquePlayers($data['mandante'], $data['visitante']);

        $placarMandante = $this->sumGoals($data['mandante']);
        $placarVisitante = $this->sumGoals($data['visitante']);

        DB::transaction(function () use ($partida, $user, $data, $placarMandante, $placarVisitante): void {
            $partida->fill([
                'placar_mandante' => $placarMandante,
                'placar_visitante' => $placarVisitante,
                'placar_registrado_por' => $user->id,
                'placar_registrado_em' => Carbon::now('UTC'),
                'estado' => 'placar_registrado',
            ]);
            $partida->save();

            PartidaDesempenho::query()
                ->where('partida_id', $partida->id)
                ->delete();

            $entries = array_merge(
                $this->normalizeEntries($data['mandante'], $partida->id, $partida->mandante_id),
                $this->normalizeEntries($data['visitante'], $partida->id, $partida->visitante_id),
            );

            foreach ($entries as $entry) {
                PartidaDesempenho::create($entry);
            }
        });

        return response()->json([
            'message' => 'Desempenho registrado.',
            'placar_mandante' => $placarMandante,
            'placar_visitante' => $placarVisitante,
            'estado' => $partida->estado,
        ]);
    }

    private function assertVisitor(int $userId, Partida $partida): void
    {
        $partida->loadMissing(['visitante']);
        $visitanteUserId = $partida->visitante?->user_id;

        if (! $visitanteUserId || $visitanteUserId !== $userId) {
            abort(403, 'Somente o visitante pode finalizar esta partida.');
        }
    }

    private function assertAllowedState(Partida $partida): void
    {
        if (in_array($partida->estado, ['placar_registrado', 'placar_confirmado', 'finalizada', 'wo'], true)) {
            abort(403, 'Esta partida já foi registrada.');
        }

        if (! in_array($partida->estado, ['confirmada', 'em_andamento'], true)) {
            abort(403, 'Partida não está disponível para finalização.');
        }
    }

    private function loadRoster(LigaClube $clube): array
    {
        $entries = LigaClubeElenco::query()
            ->where('liga_clube_id', $clube->id)
            ->where('ativo', true)
            ->with(['elencopadrao:id,short_name,long_name'])
            ->get();

        $payload = [];
        $map = [];

        foreach ($entries as $entry) {
            $player = $entry->elencopadrao;
            if (! $player) {
                continue;
            }

            $display = $player->short_name ?? $player->long_name ?? '';
            $item = [
                'id' => $player->id,
                'display_name' => $display,
                'aliases' => array_values(array_filter(array_unique([
                    $player->short_name,
                    $player->long_name,
                ]))),
            ];

            $payload[] = $item;
            $map[$player->id] = [
                'elencopadrao_id' => $player->id,
                'nome' => $display,
            ];
        }

        return [
            'payload' => $payload,
            'map' => $map,
        ];
    }

    private function loadRosterIds(LigaClube $clube): array
    {
        return LigaClubeElenco::query()
            ->where('liga_clube_id', $clube->id)
            ->where('ativo', true)
            ->pluck('elencopadrao_id')
            ->all();
    }

    private function mapEntries(array $entries, array $rosterMap): array
    {
        $mapped = [];

        foreach ($entries as $entry) {
            $playerId = (int) ($entry['player_id'] ?? 0);
            if (! $playerId || ! isset($rosterMap[$playerId])) {
                continue;
            }

            $mapped[] = [
                'elencopadrao_id' => $playerId,
                'nome' => $rosterMap[$playerId]['nome'],
                'nota' => (float) ($entry['nota'] ?? 0),
                'gols' => (int) ($entry['gols'] ?? 0),
                'assistencias' => (int) ($entry['assistencias'] ?? 0),
                'confidence' => $entry['confidence'] ?? null,
                'name_in_image' => $entry['name_in_image'] ?? null,
            ];
        }

        return $mapped;
    }

    private function sumGoals(array $entries): int
    {
        return array_reduce($entries, function (int $carry, array $entry): int {
            return $carry + (int) ($entry['gols'] ?? 0);
        }, 0);
    }

    private function assertRosterMembership(array $entries, array $allowedIds): void
    {
        $allowed = array_flip($allowedIds);

        foreach ($entries as $entry) {
            $playerId = (int) ($entry['elencopadrao_id'] ?? 0);
            if (! $playerId || ! isset($allowed[$playerId])) {
                abort(422, 'Jogador fora do elenco do clube.');
            }
        }
    }

    private function assertUniquePlayers(array $mandante, array $visitante): void
    {
        $ids = array_merge(
            array_column($mandante, 'elencopadrao_id'),
            array_column($visitante, 'elencopadrao_id'),
        );

        $counts = array_count_values(array_map('intval', $ids));

        foreach ($counts as $count) {
            if ($count > 1) {
                abort(422, 'Jogador duplicado na lista de desempenho.');
            }
        }
    }

    private function normalizeEntries(array $entries, int $partidaId, int $clubeId): array
    {
        $normalized = [];

        foreach ($entries as $entry) {
            $normalized[] = [
                'partida_id' => $partidaId,
                'liga_clube_id' => $clubeId,
                'elencopadrao_id' => (int) $entry['elencopadrao_id'],
                'nota' => (float) $entry['nota'],
                'gols' => (int) $entry['gols'],
                'assistencias' => (int) $entry['assistencias'],
            ];
        }

        return $normalized;
    }
}
