<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\User;
use App\Models\UserDisponibilidade;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartidaSchedulerService
{
    private const BLOCK_MINUTES = 30;
    private const SCHEDULING_HORIZON_DAYS = 30;

    /**
     * Gera turno e returno para um clube recém-criado.
     */
    public function generateMatchesForNewClub(LigaClube $novoClube): void
    {
        $this->ensureMatchesForClub($novoClube, true);
    }

    public function ensureMatchesForClub(LigaClube $clube, bool $generatedByNewClub = false, string $competitionType = Partida::COMPETITION_LEAGUE): void
    {
        $liga = $clube->liga()->firstOrFail();

        $outrosClubes = LigaClube::query()
            ->where('liga_id', $liga->id)
            ->where('id', '<>', $clube->id)
            ->get();

        foreach ($outrosClubes as $oponente) {
            $this->createAndSchedulePartida($liga, $clube, $oponente, $generatedByNewClub, $competitionType);
            $this->createAndSchedulePartida($liga, $oponente, $clube, $generatedByNewClub, $competitionType);
        }
    }

    /**
     * Cria uma partida (sem agendamento automático).
     */
    public function createAndSchedulePartida(
        Liga $liga,
        LigaClube $mandante,
        LigaClube $visitante,
        bool $generatedByNewClub = false,
        string $competitionType = Partida::COMPETITION_LEAGUE,
    ): Partida
    {
        $existing = $this->findExistingMatch($liga, $mandante, $visitante, $competitionType);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($liga, $mandante, $visitante, $generatedByNewClub, $competitionType): Partida {
            $payload = [
                'liga_id' => $liga->id,
                'mandante_id' => $mandante->id,
                'visitante_id' => $visitante->id,
                'estado' => 'confirmacao_necessaria',
            ];

            if (Partida::competitionSchemaReady()) {
                $payload['competition_type'] = $competitionType;
            }

            $partida = Partida::create($payload);

            return $partida;
        });
    }

    private function findExistingMatch(Liga $liga, LigaClube $mandante, LigaClube $visitante, string $competitionType): ?Partida
    {
        $query = Partida::query()
            ->where('liga_id', $liga->id)
            ->where('mandante_id', $mandante->id)
            ->where('visitante_id', $visitante->id);

        if (Partida::competitionSchemaReady()) {
            $query->where('competition_type', $competitionType);
        }

        return $query->first();
    }

    /**
     * Calcula slots para o visitante (UTC) usando a disponibilidade do mandante.
     */
    public function availableVisitorSlots(Partida $partida): Collection
    {
        return $this->availableSlotsForRole($partida, 'mandante');
    }

    /**
     * Calcula slots para o usuario logado (UTC), usando disponibilidade do adversario.
     */
    public function availableOpponentSlots(Partida $partida, int $requesterUserId): Collection
    {
        $partida->loadMissing(['mandante.user', 'visitante.user']);
        $mandanteUserId = $partida->mandante?->user_id;
        $visitanteUserId = $partida->visitante?->user_id;

        $availabilityRole = $requesterUserId === $mandanteUserId ? 'visitante' : 'mandante';

        return $this->availableSlotsForRole($partida, $availabilityRole);
    }

    private function availableSlotsForRole(Partida $partida, string $availabilityRole): Collection
    {
        $partida->loadMissing(['liga', 'mandante.user', 'visitante.user']);
        $liga = $partida->liga;
        $tz = $liga->resolveTimezone();
        $nowLocal = Carbon::now($tz);
        $windowStart = $nowLocal->copy()->startOfDay();
        $windowEnd = $windowStart->copy()->addDays(self::SCHEDULING_HORIZON_DAYS);

        $availabilityUser = $availabilityRole === 'visitante'
            ? $partida->visitante?->user
            : $partida->mandante?->user;

        $availabilityRanges = $this->groupDisponibilidades($availabilityUser);
        if (empty($availabilityRanges)) {
            return collect();
        }

        $slots = collect();

        for ($date = $windowStart->copy(); $date->lte($windowEnd); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            $dayRanges = $availabilityRanges[$dayOfWeek] ?? [];

            if (empty($dayRanges)) {
                continue;
            }

            foreach ($dayRanges as $range) {
                $rangeSlots = $this->buildSlotsForRange($date, $range, $tz);

                foreach ($rangeSlots as $slotLocal) {
                    if ($slotLocal->lessThanOrEqualTo($nowLocal)) {
                        continue;
                    }

                    $slotUtc = $slotLocal->copy()->setTimezone('UTC');

                    if (
                        $this->hasScheduleConflict($partida->mandante_id, $slotUtc) ||
                        $this->hasScheduleConflict($partida->visitante_id, $slotUtc)
                    ) {
                        continue;
                    }

                    $slots->push($slotUtc);
                }
            }
        }

        return $slots
            ->unique(fn (Carbon $slot) => $slot->toIso8601String())
            ->sortBy(fn (Carbon $slot) => $slot->timestamp)
            ->values();
    }

    private function buildSlotsForRange(Carbon $date, array $range, string $tz): array
    {
        $startRaw = $range['start'] ?? null;
        $endRaw = $range['end'] ?? null;

        if (! $startRaw || ! $endRaw) {
            return [];
        }

        $start = Carbon::parse($date->toDateString().' '.$startRaw, $tz);
        $end = Carbon::parse($date->toDateString().' '.$endRaw, $tz);

        if ($end->lessThanOrEqualTo($start)) {
            return [];
        }

        $start = $this->roundUpToSlot($start);
        $end = $this->roundDownToSlot($end);
        $lastStart = $end->copy()->subMinutes(self::BLOCK_MINUTES);

        if ($lastStart->lt($start)) {
            return [];
        }

        $slots = [];
        for ($cursor = $start->copy(); $cursor->lte($lastStart); $cursor->addMinutes(self::BLOCK_MINUTES)) {
            $slots[] = $cursor->copy();
        }

        return $slots;
    }

    private function roundUpToSlot(Carbon $time): Carbon
    {
        $minute = $time->minute;
        $remainder = $minute % self::BLOCK_MINUTES;

        if ($remainder === 0) {
            return $time->copy()->second(0);
        }

        return $time->copy()->addMinutes(self::BLOCK_MINUTES - $remainder)->second(0);
    }

    private function roundDownToSlot(Carbon $time): Carbon
    {
        $minute = $time->minute;
        $remainder = $minute % self::BLOCK_MINUTES;

        return $time->copy()->subMinutes($remainder)->second(0);
    }

    /**
     * Mantido por compatibilidade: o agendamento nao depende mais de periodo de liga.
     */
    public function isWithinLigaPeriod(Liga $liga, Carbon $dateLocal): bool
    {
        return true;
    }

    /**
     * Verifica se um clube já tem partida ocupando a janela de BLOCK_MINUTES no horário candidato.
     */
    public function hasScheduleConflict(int $clubeId, Carbon $candidateStart): bool
    {
        $candidateEnd = $candidateStart->copy()->addMinutes(self::BLOCK_MINUTES);
        $windowStart = $candidateStart->copy()->subMinutes(self::BLOCK_MINUTES);

        return Partida::query()
            ->whereNotNull('scheduled_at')
            ->whereIn('estado', ['agendada', 'confirmada'])
            ->where(function ($q) use ($clubeId): void {
                $q->where('mandante_id', $clubeId)
                    ->orWhere('visitante_id', $clubeId);
            })
            ->where(function ($q) use ($candidateEnd, $windowStart): void {
                // As partidas ocupam blocos fixos de 30 minutos.
                // Dois blocos colidem quando o inicio existente cai dentro da janela
                // (candidateStart - 30min, candidateStart + 30min).
                $q->where('scheduled_at', '<', $candidateEnd->toDateTimeString())
                    ->where('scheduled_at', '>', $windowStart->toDateTimeString());
            })
            ->exists();
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

}
