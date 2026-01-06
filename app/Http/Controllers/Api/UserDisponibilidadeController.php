<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDisponibilidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserDisponibilidadeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fim']);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $this->validateDisponibilidadeBatch($request);
        $created = [];
        $skipped = [];

        foreach ($data['dias_semana'] as $diaSemana) {
            $payload = [
                'dia_semana' => $diaSemana,
                'hora_inicio' => $data['hora_inicio'],
                'hora_fim' => $data['hora_fim'],
            ];

            try {
                $this->assertNoOverlap($user->id, $payload);
                $created[] = UserDisponibilidade::create(array_merge($payload, ['user_id' => $user->id]));
            } catch (ValidationException $exception) {
                $skipped[] = [
                    'dia_semana' => $diaSemana,
                    'message' => $exception->errors()['hora_inicio'][0] ?? 'Horário indisponível.',
                ];
            }
        }

        if (count($created) === 0) {
            throw ValidationException::withMessages([
                'dia_semana' => ['Horário se sobrepõe ou encosta em outra janela existente.'],
            ]);
        }

        $response = [
            'created' => $created,
            'skipped' => $skipped,
        ];

        if (count($created) === 1 && count($data['dias_semana']) === 1) {
            $response['row'] = $created[0];
        }

        return response()->json($response, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $row = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $data = $this->validateDisponibilidade($request);
        $this->assertNoOverlap($user->id, $data, $row->id);

        $row->fill($data);
        $row->save();

        return response()->json($row);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $row = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $row->delete();

        return response()->json(['deleted' => true]);
    }

    private function validateDisponibilidade(Request $request): array
    {
        $data = $request->validate([
            'dia_semana' => ['required', 'integer', 'min:0', 'max:6'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
        ]);

        if ($data['hora_inicio'] >= $data['hora_fim']) {
            throw ValidationException::withMessages([
                'hora_inicio' => ['Hora início deve ser menor que hora fim.'],
            ]);
        }

        return $data;
    }

    private function validateDisponibilidadeBatch(Request $request): array
    {
        $data = $request->validate([
            'dia_semana' => ['nullable', 'integer', 'min:0', 'max:6'],
            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['integer', 'min:0', 'max:6'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
        ]);

        if ($data['hora_inicio'] >= $data['hora_fim']) {
            throw ValidationException::withMessages([
                'hora_inicio' => ['Hora início deve ser menor que hora fim.'],
            ]);
        }

        $days = [];

        if (array_key_exists('dias_semana', $data) && is_array($data['dias_semana'])) {
            $days = $data['dias_semana'];
        }

        if (array_key_exists('dia_semana', $data) && $data['dia_semana'] !== null) {
            $days[] = $data['dia_semana'];
        }

        $days = array_values(array_unique(array_map('intval', $days)));

        if (count($days) === 0) {
            throw ValidationException::withMessages([
                'dia_semana' => ['Selecione pelo menos um dia.'],
            ]);
        }

        return [
            'dias_semana' => $days,
            'hora_inicio' => $data['hora_inicio'],
            'hora_fim' => $data['hora_fim'],
        ];
    }

    private function assertNoOverlap(int $userId, array $data, ?int $ignoreId = null): void
    {
        $query = UserDisponibilidade::query()
            ->where('user_id', $userId)
            ->where('dia_semana', $data['dia_semana'])
            ->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))
            ->where(function ($q) use ($data) {
                // overlap inclusive of touching edges (encosta ou sobrepõe)
                $q->where('hora_inicio', '<=', $data['hora_fim'])
                    ->where('hora_fim', '>=', $data['hora_inicio']);
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'hora_inicio' => ['Horário se sobrepõe ou encosta em outra janela existente.'],
            ]);
        }
    }
}
