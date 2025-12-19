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

        $data = $this->validateDisponibilidade($request);
        $this->assertNoOverlap($user->id, $data);

        $row = UserDisponibilidade::create(array_merge($data, ['user_id' => $user->id]));

        return response()->json($row, 201);
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
