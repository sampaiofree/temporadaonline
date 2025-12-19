<?php

namespace App\Services;

use App\Models\Partida;
use Carbon\Carbon;

class PartidaPlacarService
{
    private const CONFIRMATION_WINDOW_MINUTES = 5;

    public function __construct(private readonly PartidaStateService $state)
    {
    }

    public function maybeAutoConfirm(Partida $partida): bool
    {
        if ($partida->estado !== 'placar_registrado' || ! $partida->placar_registrado_em) {
            return false;
        }

        $now = Carbon::now('UTC');
        $deadline = $partida->placar_registrado_em->copy()->addMinutes(self::CONFIRMATION_WINDOW_MINUTES);

        if ($now->lessThan($deadline)) {
            return false;
        }

        $this->state->transitionTo(
            $partida,
            'placar_confirmado',
            [],
            'placar_confirmado',
            null,
            ['auto' => true],
        );

        return true;
    }

    public function isConfirmationWindowExpired(Partida $partida): bool
    {
        if (! $partida->placar_registrado_em) {
            return false;
        }

        $deadline = $partida->placar_registrado_em->copy()->addMinutes(self::CONFIRMATION_WINDOW_MINUTES);

        return Carbon::now('UTC')->greaterThanOrEqualTo($deadline);
    }
}
