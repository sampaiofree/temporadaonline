<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\PartidaConfirmacao;
use App\Models\PartidaEvento;
use App\Models\PartidaOpcaoHorario;
use App\Models\User;
use App\Models\UserDisponibilidade;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartidaSchedulerService
{
    private const MAX_OPTIONS = 5;
    private const MAX_DAYS_LOOKAHEAD = 30;
    private const BLOCK_MINUTES = 30;

    public function __construct(private readonly PartidaStateService $state)
    {
    }

    /**
     * Gera turno e returno para um clube recém-criado e tenta agendar cada partida.
     */
    public function generateMatchesForNewClub(LigaClube $novoClube): void
    {
        $this->ensureMatchesForClub($novoClube, true);
    }

    public function ensureMatchesForClub(LigaClube $clube, bool $generatedByNewClub = false): void
    {
        $liga = $clube->liga()->firstOrFail();

        $outrosClubes = LigaClube::query()
            ->where('liga_id', $liga->id)
            ->where('id', '<>', $clube->id)
            ->get();

        foreach ($outrosClubes as $oponente) {
            $this->createAndSchedulePartida($liga, $clube, $oponente, $generatedByNewClub);
            $this->createAndSchedulePartida($liga, $oponente, $clube, $generatedByNewClub);
        }
    }

    /**
     * Cria uma partida e tenta agendar automaticamente.
     */
    public function createAndSchedulePartida(Liga $liga, LigaClube $mandante, LigaClube $visitante, bool $generatedByNewClub = false): Partida
    {
        $existing = $this->findExistingMatch($liga, $mandante, $visitante);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($liga, $mandante, $visitante, $generatedByNewClub): Partida {
            $partida = Partida::create([
                'liga_id' => $liga->id,
                'mandante_id' => $mandante->id,
                'visitante_id' => $visitante->id,
                'estado' => 'confirmacao_necessaria',
            ]);

            $this->attemptAutoSchedule($partida->fresh());

            $this->logEvent($partida, 'confirmacao_horario', null, [
                'auto_generated' => true,
                'generated_by_new_club' => $generatedByNewClub,
            ]);

            return $partida;
        });
    }

    private function findExistingMatch(Liga $liga, LigaClube $mandante, LigaClube $visitante): ?Partida
    {
        return Partida::query()
            ->where('liga_id', $liga->id)
            ->where('mandante_id', $mandante->id)
            ->where('visitante_id', $visitante->id)
            ->first();
    }

    /**
     * Tenta agendar automaticamente. Se não houver cruzamento, cria opções.
     */
    public function attemptAutoSchedule(Partida $partida): void
    {
        $partida->loadMissing(['liga', 'mandante.user', 'visitante.user']);
        $liga = $partida->liga;
        $slots = $this->candidateSlots($partida, self::MAX_OPTIONS);

        if ($slots->isNotEmpty()) {
            $slot = $slots->first();
            $this->state->transitionTo(
                $partida,
                'agendada',
                ['scheduled_at' => $slot],
                null
            );
            $this->persistOptions($partida, $slots);

            return;
        }

        if ($partida->estado !== 'confirmacao_necessaria') {
            $this->state->transitionTo($partida, 'confirmacao_necessaria');
        }

        $this->persistOptions($partida, $slots);
    }

    /**
     * Força horário após expirar prazo de confirmação.
     */
    public function forceSchedule(Partida $partida): void
    {
        $partida->loadMissing(['liga', 'mandante.user', 'visitante.user']);
        $slots = $this->candidateSlots($partida, 1);

        if ($slots->isEmpty()) {
            $partida->sem_slot_disponivel = true;
            $partida->save();

            $this->logEvent($partida, 'confirmacao_horario', null, [
                'forced' => false,
                'reason' => 'sem_slot_disponivel',
            ]);

            return;
        }

        $slot = $slots->first();
        $this->state->transitionTo(
            $partida,
            'confirmada',
            [
                'scheduled_at' => $slot,
                'forced_by_system' => true,
            ],
            'confirmacao_horario',
            null,
            [
                'forced' => true,
                'scheduled_at' => $slot?->toISOString(),
            ],
        );
    }

    /**
     * Usuário marca horário aceito; se houver match com oponente, confirma.
     */
    public function confirmHorarios(Partida $partida, User $user, Collection $datetimesUtc): void
    {
        $partida->loadMissing(['liga', 'mandante.user', 'visitante.user']);

        // Apenas partidas pendentes de confirmação permitem marcação
        $this->state->assertActionAllowed($partida, ['confirmacao_necessaria', 'agendada']);
        $tz = $partida->liga->timezone ?? 'UTC';

        // Limpa confirmações anteriores do usuário que não estejam na seleção atual
        PartidaConfirmacao::query()
            ->where('partida_id', $partida->id)
            ->where('user_id', $user->id)
            ->whereNotIn('datetime', $datetimesUtc)
            ->delete();

        $mandanteUserId = $partida->mandante->user_id;
        $visitanteUserId = $partida->visitante->user_id;
        $opponentId = $user->id === $mandanteUserId ? $visitanteUserId : $mandanteUserId;

        $matched = null;

        foreach ($datetimesUtc as $datetime) {
            PartidaConfirmacao::firstOrCreate([
                'partida_id' => $partida->id,
                'user_id' => $user->id,
                'datetime' => $datetime,
            ]);

            $existsOther = PartidaConfirmacao::query()
                ->where('partida_id', $partida->id)
                ->where('user_id', $opponentId)
                ->where('datetime', $datetime)
                ->exists();

            if ($existsOther) {
                $matched = $datetime;
                break;
            }
        }

        if ($matched) {
            // Revalida conflito antes de confirmar
            if (
                $this->hasScheduleConflict($partida->mandante_id, $matched) ||
                $this->hasScheduleConflict($partida->visitante_id, $matched)
            ) {
                throw ValidationException::withMessages([
                    'datetime' => ['Um dos clubes já possui partida nesse horário.'],
                ]);
            }

            $this->state->transitionTo(
                $partida,
                'confirmada',
                ['scheduled_at' => $matched],
                'confirmacao_horario',
                $user->id,
                [
                    'datetime' => $matched->copy()->setTimezone($tz)->toIso8601String(),
                    'match' => true,
                ],
            );
        } else {
            $this->logEvent($partida, 'confirmacao_horario', $user->id, [
                'datetimes' => $datetimesUtc->map(
                    fn (Carbon $dt) => $dt->copy()->setTimezone($tz)->toIso8601String()
                ),
                'match' => false,
            ]);
        }
    }

    private function persistOptions(Partida $partida, Collection $slots): void
    {
        foreach ($slots as $slot) {
            PartidaOpcaoHorario::firstOrCreate([
                'partida_id' => $partida->id,
                'datetime' => $slot,
            ]);
        }
    }

    /**
     * Calcula os slots válidos (UTC) respeitando liga + mandante + visitante.
     */
    /**
     * Slots candidatos para confirmação/alteração (UTC).
     */
    public function candidateSlots(Partida $partida, int $limit = self::MAX_OPTIONS): Collection
    {
        $liga = $partida->liga;
        $tz = $liga->timezone ?? 'UTC';
        $now = Carbon::now($tz);

        $allowedDays = $this->normalizeDays($liga->dias_permitidos ?? []);
        $ligaRanges = $this->normalizeRanges($liga->horarios_permitidos ?? []);

        $mandanteRanges = $this->groupDisponibilidades($partida->mandante->user);
        $visitanteRanges = $this->groupDisponibilidades($partida->visitante->user);

        $slots = collect();

        for ($i = 0; $i < self::MAX_DAYS_LOOKAHEAD && $slots->count() < $limit; $i++) {
            $day = $now->copy()->addDays($i);
            $dayOfWeek = $day->dayOfWeek;

            if (! in_array($dayOfWeek, $allowedDays, true)) {
                continue;
            }

            $mandanteDayRanges = $mandanteRanges[$dayOfWeek] ?? [];
            $visitanteDayRanges = $visitanteRanges[$dayOfWeek] ?? [];

            if (empty($mandanteDayRanges) || empty($visitanteDayRanges)) {
                continue;
            }

            foreach ($ligaRanges as $ligaRange) {
                foreach ($mandanteDayRanges as $mRange) {
                    foreach ($visitanteDayRanges as $vRange) {
                        $start = max($ligaRange['start'], $mRange['start'], $vRange['start']);
                        $end = min($ligaRange['end'], $mRange['end'], $vRange['end']);

                        if ($start >= $end) {
                            continue;
                        }

                        $candidate = $day->copy()->setTimeFromTimeString($start)->setTimezone('UTC');
                        if ($candidate->lessThanOrEqualTo(Carbon::now('UTC'))) {
                            continue;
                        }

                        if (
                            $this->hasScheduleConflict($partida->mandante_id, $candidate) ||
                            $this->hasScheduleConflict($partida->visitante_id, $candidate)
                        ) {
                            continue;
                        }

                        $slots->push($candidate);

                        if ($slots->count() >= $limit) {
                            break 3;
                        }
                    }
                }
            }
        }

        return $slots->sort()->values();
    }

    /**
     * Verifica se um clube já tem partida ocupando a janela de BLOCK_MINUTES no horário candidato.
     */
    public function hasScheduleConflict(int $clubeId, Carbon $candidateStart): bool
    {
        $candidateEnd = $candidateStart->copy()->addMinutes(self::BLOCK_MINUTES);

        return Partida::query()
            ->whereNotNull('scheduled_at')
            ->whereIn('estado', ['agendada', 'confirmada', 'em_andamento'])
            ->where(function ($q) use ($clubeId): void {
                $q->where('mandante_id', $clubeId)
                    ->orWhere('visitante_id', $clubeId);
            })
            ->where(function ($q) use ($candidateStart, $candidateEnd): void {
                $q->where('scheduled_at', '<', $candidateEnd)
                    ->whereRaw("scheduled_at + interval '120 minutes' > ?", [$candidateStart->toDateTimeString()]);
            })
            ->exists();
    }

    /**
     * Converte dias permitidos para array de inteiros (Carbon dayOfWeek).
     */
    public function normalizeDays(array|string $days): array
    {
        if (is_string($days)) {
            $decoded = json_decode($days, true);
            $days = is_array($decoded) ? $decoded : [];
        }

        $map = [
            'dom' => Carbon::SUNDAY,
            'domingo' => Carbon::SUNDAY,
            'seg' => Carbon::MONDAY,
            'segunda' => Carbon::MONDAY,
            'ter' => Carbon::TUESDAY,
            'terça' => Carbon::TUESDAY,
            'terca' => Carbon::TUESDAY,
            'qua' => Carbon::WEDNESDAY,
            'quarta' => Carbon::WEDNESDAY,
            'qui' => Carbon::THURSDAY,
            'quinta' => Carbon::THURSDAY,
            'sex' => Carbon::FRIDAY,
            'sexta' => Carbon::FRIDAY,
            'sab' => Carbon::SATURDAY,
            'sábado' => Carbon::SATURDAY,
        ];

        return collect($days)
            ->map(function ($day) use ($map) {
                if (is_numeric($day)) {
                    return (int) $day;
                }

                $key = mb_strtolower((string) $day);

                return $map[$key] ?? null;
            })
            ->filter(fn ($v) => $v !== null)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Normaliza faixas de horário para strings HH:MM.
     */
    public function normalizeRanges(array|string $ranges): array
    {
        if (is_string($ranges)) {
            $decoded = json_decode($ranges, true);
            $ranges = is_array($decoded) ? $decoded : [];
        }

        return collect($ranges)
            ->map(function ($range) {
                $start = $range['inicio'] ?? null;
                $end = $range['fim'] ?? null;
                if (! $start || ! $end) {
                    return null;
                }

                return [
                    'start' => substr($start, 0, 5),
                    'end' => substr($end, 0, 5),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Agrupa disponibilidades do usuário por dia da semana.
     */
    public function groupDisponibilidades(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $rows = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->get(['dia_semana', 'hora_inicio', 'hora_fim']);

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row->dia_semana] ??= [];
            $grouped[$row->dia_semana][] = [
                'start' => substr($row->hora_inicio, 0, 5),
                'end' => substr($row->hora_fim, 0, 5),
            ];
        }

        return $grouped;
    }

    private function logEvent(Partida $partida, string $tipo, ?int $userId = null, array $payload = []): void
    {
        PartidaEvento::create([
            'partida_id' => $partida->id,
            'tipo' => $tipo,
            'user_id' => $userId,
            'payload' => $payload,
        ]);
    }
}
