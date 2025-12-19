<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partida;
use App\Models\PartidaOpcaoHorario;
use App\Services\PartidaSchedulerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PartidaScheduleController extends Controller
{
    public function __construct(private readonly PartidaSchedulerService $scheduler)
    {
    }

    public function opcoes(Request $request, Partida $partida): JsonResponse
    {
        $this->assertParticipante($request->user()->id, $partida);

        $partida->loadMissing('liga');
        $tz = $partida->liga->timezone ?? 'UTC';

        $opcoes = PartidaOpcaoHorario::query()
            ->where('partida_id', $partida->id)
            ->orderBy('datetime')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'datetime_utc' => $row->datetime?->toIso8601String(),
                'datetime_local' => $row->datetime?->setTimezone($tz)->toIso8601String(),
            ]);

        $sugestoes = $this->scheduler->candidateSlots($partida, 5)
            ->map(fn (Carbon $slot) => [
                'datetime_utc' => $slot->toIso8601String(),
                'datetime_local' => $slot->copy()->setTimezone($tz)->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'partida_id' => $partida->id,
            'estado' => $partida->estado,
            'timezone' => $tz,
            'opcoes' => $opcoes,
            'sugestoes_alteracao' => $sugestoes,
        ]);
    }

    public function confirmar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);

        $data = $request->validate([
            'datetimes' => ['required', 'array', 'min:1'],
            'datetimes.*' => ['required', 'date'],
        ]);

        $partida->loadMissing('liga');
        $tz = $partida->liga->timezone ?? 'UTC';
        $parsed = collect($data['datetimes'])
            ->map(fn ($dt) => Carbon::parse($dt, $tz)->setTimezone('UTC'))
            ->unique(fn (Carbon $dt) => $dt->toIso8601String())
            ->values();

        $allowed = PartidaOpcaoHorario::query()
            ->where('partida_id', $partida->id)
            ->whereIn('datetime', $parsed)
            ->pluck('datetime')
            ->map(fn ($dt) => $dt->toIso8601String());

        if ($allowed->isEmpty()) {
            throw ValidationException::withMessages([
                'datetimes' => ['Nenhum dos horários está entre as opções disponíveis.'],
            ]);
        }

        $filtered = $parsed->filter(
            fn (Carbon $dt) => $allowed->contains($dt->toIso8601String())
        );

        $this->scheduler->confirmHorarios($partida, $user, $filtered);
        $partida->refresh()->loadMissing('liga');
        $tz = $partida->liga->timezone ?? 'UTC';

        return response()->json([
            'message' => 'Horários confirmados.',
            'estado' => $partida->estado,
            'scheduled_at' => $partida->scheduled_at?->timezone($tz)->toIso8601String(),
        ]);
    }

    private function assertParticipante(int $userId, Partida $partida): void
    {
        $partida->loadMissing(['mandante', 'visitante']);
        $mandanteUserId = $partida->mandante->user_id;
        $visitanteUserId = $partida->visitante->user_id;

        if (! in_array($userId, [$mandanteUserId, $visitanteUserId], true)) {
            abort(403, 'Apenas participantes podem acessar esta partida.');
        }
    }
}
