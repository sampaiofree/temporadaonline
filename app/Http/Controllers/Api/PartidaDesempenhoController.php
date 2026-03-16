<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaPeriodo;
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
    public function __construct(
        private readonly PartidaDesempenhoAiService $aiService,
    ) {
    }

    public function form(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipant($user->id, $partida);
        $this->assertAllowedState($partida);
        if ($response = $this->ensureMatchReportUnlocked($partida)) {
            return $response;
        }

        $partida->loadMissing(['mandante', 'visitante']);
        $mandanteRoster = $this->loadRoster($partida->mandante);
        $visitanteRoster = $this->loadRoster($partida->visitante);

        return response()->json([
            'mandante' => [
                'entries' => $mandanteRoster['entries'],
            ],
            'visitante' => [
                'entries' => $visitanteRoster['entries'],
            ],
            'placar' => [
                'mandante' => $this->normalizeScore($partida->placar_mandante, 0),
                'visitante' => $this->normalizeScore($partida->placar_visitante, 0),
            ],
        ]);
    }

    public function confirm(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipant($user->id, $partida);
        $this->assertAllowedState($partida);
        if ($response = $this->ensureMatchReportUnlocked($partida)) {
            return $response;
        }

        $partida->loadMissing(['mandante', 'visitante']);
        $mandanteRoster = $this->loadRoster($partida->mandante);
        $visitanteRoster = $this->loadRoster($partida->visitante);

        $mandanteEntries = $this->sanitizeSubmittedEntries(
            $request->input('mandante'),
            $mandanteRoster['map'],
        );
        $visitanteEntries = $this->sanitizeSubmittedEntries(
            $request->input('visitante'),
            $visitanteRoster['map'],
        );

        $placarMandante = $this->normalizeScore(
            $request->input('placar_mandante'),
            $this->sumGoals($mandanteEntries),
        );
        $placarVisitante = $this->normalizeScore(
            $request->input('placar_visitante'),
            $this->sumGoals($visitanteEntries),
        );

        DB::transaction(function () use ($partida, $user, $mandanteEntries, $visitanteEntries, $placarMandante, $placarVisitante): void {
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
                $this->normalizeEntries($mandanteEntries, $partida->id, $partida->mandante_id),
                $this->normalizeEntries($visitanteEntries, $partida->id, $partida->visitante_id),
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

    public function preview(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipant($user->id, $partida);
        $this->assertAllowedState($partida);
        if ($response = $this->ensureMatchReportUnlocked($partida)) {
            return $response;
        }

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
                'analysis_failed' => true,
                'warning' => 'Não foi possível analisar as imagens. Você pode preencher a súmula manualmente.',
                'mandante' => [
                    'entries' => [],
                    'unknown_players' => [],
                ],
                'visitante' => [
                    'entries' => [],
                    'unknown_players' => [],
                ],
                'placar' => [
                    'mandante' => 0,
                    'visitante' => 0,
                ],
            ]);
        }

        $mandanteEntries = $this->mapEntries($analysis['mandante']['entries'], $mandanteRoster['map']);
        $visitanteEntries = $this->mapEntries($analysis['visitante']['entries'], $visitanteRoster['map']);

        $placarMandante = (int) ($analysis['mandante']['placar_total'] ?? $this->sumGoals($mandanteEntries));
        $placarVisitante = (int) ($analysis['visitante']['placar_total'] ?? $this->sumGoals($visitanteEntries));

        return response()->json([
            'analysis_failed' => false,
            'warning' => null,
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

    private function assertParticipant(int $userId, Partida $partida): void
    {
        $partida->loadMissing(['mandante', 'visitante']);
        $mandanteUserId = $partida->mandante?->user_id;
        $visitanteUserId = $partida->visitante?->user_id;

        if (! $mandanteUserId || ! $visitanteUserId) {
            abort(403, 'Partida sem participantes válidos.');
        }

        if (! in_array($userId, [$mandanteUserId, $visitanteUserId], true)) {
            abort(403, 'Somente participantes podem finalizar esta partida.');
        }
    }

    private function assertAllowedState(Partida $partida): void
    {
        if (in_array($partida->estado, ['placar_registrado', 'placar_confirmado', 'finalizada', 'wo'], true)) {
            abort(403, 'Esta partida já foi registrada.');
        }

        if (! in_array($partida->estado, ['confirmada', 'agendada'], true)) {
            abort(403, 'Partida não está disponível para finalização.');
        }
    }

    private function ensureMatchReportUnlocked(Partida $partida): ?JsonResponse
    {
        $partida->loadMissing('liga');
        $liga = $partida->liga;

        if (! $liga) {
            return response()->json([
                'message' => 'Liga da partida não encontrada.',
            ], 404);
        }

        if (! LigaPeriodo::activeRangeForLiga($liga)) {
            return null;
        }

        return response()->json([
            'message' => 'Mercado aberto. O envio de súmulas fica bloqueado até o fechamento da janela.',
        ], 423);
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
        $formEntries = [];

        foreach ($entries as $entry) {
            $player = $entry->elencopadrao;
            if (! $player) {
                continue;
            }

            $display = $player->short_name ?? $player->long_name ?? "Jogador {$player->id}";
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
                'short_name' => $player->short_name,
                'long_name' => $player->long_name,
            ];
            $formEntries[] = [
                'elencopadrao_id' => $player->id,
                'nome' => $display,
                'short_name' => $player->short_name,
                'long_name' => $player->long_name,
                'nota' => '',
                'gols' => 0,
                'assistencias' => 0,
            ];
        }

        return [
            'payload' => $payload,
            'map' => $map,
            'entries' => $formEntries,
        ];
    }

    private function sanitizeSubmittedEntries(mixed $entries, array $rosterMap): array
    {
        $normalized = [];
        $items = is_array($entries) ? $entries : [];

        foreach ($items as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $playerId = (int) ($entry['elencopadrao_id'] ?? 0);
            if (! $playerId || ! isset($rosterMap[$playerId])) {
                continue;
            }

            $normalized[$playerId] = [
                'elencopadrao_id' => $playerId,
                'nota' => $this->normalizeNota($entry['nota'] ?? null),
                'gols' => $this->normalizeNonNegativeInt($entry['gols'] ?? 0),
                'assistencias' => $this->normalizeNonNegativeInt($entry['assistencias'] ?? 0),
            ];
        }

        return array_values(array_filter($normalized, static fn (array $entry): bool => $entry['nota'] !== null));
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

    private function normalizeNota(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        $normalized = is_string($value)
            ? str_replace(',', '.', trim($value))
            : $value;

        if (! is_numeric($normalized)) {
            return null;
        }

        $nota = (float) $normalized;

        if ($nota < 0 || $nota > 10) {
            return null;
        }

        return $nota;
    }

    private function normalizeNonNegativeInt(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        return max(0, (int) $value);
    }

    private function normalizeScore(mixed $value, int $fallback = 0): int
    {
        if (! is_numeric($value)) {
            return max(0, $fallback);
        }

        return max(0, (int) $value);
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
