<?php

namespace App\Services;

use App\Models\Partida;
use App\Models\PartidaEvento;
use Illuminate\Validation\ValidationException;

class PartidaStateService
{
    /**
     * Mapa de transições permitidas.
     */
    private array $transitions = [
        'confirmacao_necessaria' => ['agendada', 'confirmada', 'cancelada', 'wo'],
        'agendada' => ['confirmada', 'em_andamento', 'cancelada', 'wo'],
        'confirmada' => ['em_andamento', 'finalizada', 'wo', 'cancelada'],
        'em_andamento' => ['placar_registrado', 'wo', 'cancelada'],
        'placar_registrado' => ['placar_confirmado', 'em_reclamacao'],
        'placar_confirmado' => [],
        'em_reclamacao' => ['placar_confirmado'],
        'finalizada' => [],
        'wo' => [],
        'cancelada' => [],
    ];

    /**
     * Efetua transição de estado com validação e efeitos colaterais opcionais.
     *
     * @param array $attributes Atributos adicionais a serem salvos (ex: scheduled_at)
     * @param string|null $eventType Tipo de evento opcional para registrar em partida_eventos
     * @param int|null $userId Usuário responsável pela transição (para log)
     * @param array $payload Payload opcional para evento
     *
     * @throws ValidationException
     */
    public function transitionTo(Partida $partida, string $targetState, array $attributes = [], ?string $eventType = null, ?int $userId = null, array $payload = []): Partida
    {
        $current = $partida->estado;

        if ($current !== $targetState && ! $this->canTransition($current, $targetState)) {
            throw ValidationException::withMessages([
                'estado' => ["Transição inválida: {$current} -> {$targetState}"],
            ]);
        }

        // Se estado já é o alvo, apenas atualiza atributos; caso contrário, transiciona
        $partida->fill(array_merge($attributes, $current === $targetState ? [] : ['estado' => $targetState]));
        $partida->save();

        if ($eventType) {
            PartidaEvento::create([
                'partida_id' => $partida->id,
                'tipo' => $eventType,
                'user_id' => $userId,
                'payload' => $payload,
            ]);
        }

        return $partida;
    }

    public function canTransition(string $current, string $target): bool
    {
        return in_array($target, $this->transitions[$current] ?? [], true);
    }

    /**
     * Garante que ação só acontece em determinados estados.
     */
    public function assertActionAllowed(Partida $partida, array $allowedStates): void
    {
        if (! in_array($partida->estado, $allowedStates, true)) {
            throw ValidationException::withMessages([
                'estado' => ["Ação não permitida no estado {$partida->estado}."],
            ]);
        }
    }
}
