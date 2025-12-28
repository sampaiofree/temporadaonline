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

    /**
     * Gera turno e returno para um clube recém-criado.
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
     * Cria uma partida (sem agendamento automático).
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
     * Calcula slots para o visitante (UTC) usando periodos da liga e disponibilidade do mandante.
     */
    public function availableVisitorSlots(Partida $partida): Collection
    {
        $partida->loadMissing(['liga.periodos', 'mandante.user', 'visitante.user']);
        $liga = $partida->liga;
        $tz = $liga->timezone ?? 'UTC';
        $nowLocal = Carbon::now($tz);
        $periodos = ($liga->periodos ?? collect())->sortBy('inicio')->values();

        if ($periodos->isEmpty()) {
            return collect();
        }

        $mandanteRanges = $this->groupDisponibilidades($partida->mandante->user);
        if (empty($mandanteRanges)) {
            return collect();
        }

        $slots = collect();

        foreach ($periodos as $periodo) {
            $startDate = Carbon::parse($periodo->inicio, $tz)->startOfDay();
            $endDate = Carbon::parse($periodo->fim, $tz)->startOfDay();

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                if ($date->lt($nowLocal->copy()->startOfDay())) {
                    continue;
                }

                $dayOfWeek = $date->dayOfWeek;
                $dayRanges = $mandanteRanges[$dayOfWeek] ?? [];

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
     * Verifica se a data local esta dentro de algum periodo cadastrado da liga.
     */
    public function isWithinLigaPeriod(Liga $liga, Carbon $dateLocal): bool
    {
        $liga->loadMissing('periodos');
        $periodos = $liga->periodos ?? collect();
        if ($periodos->isEmpty()) {
            return true;
        }

        $date = $dateLocal->toDateString();

        return $periodos->contains(fn ($periodo) => $date >= $periodo->inicio->toDateString()
            && $date <= $periodo->fim->toDateString());
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
                    ->whereRaw(
                        "scheduled_at + interval '".self::BLOCK_MINUTES." minutes' > ?",
                        [$candidateStart->toDateTimeString()]
                    );
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
