<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Geracao;
use App\Models\Idioma;
use App\Models\Jogo;
use App\Models\Plataforma;
use App\Models\Profile;
use App\Models\Regiao;
use App\Models\UserDisponibilidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LegacyProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        $disponibilidades = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fim'])
            ->map(fn (UserDisponibilidade $item): array => [
                'id' => $item->id,
                'dia_semana' => $item->dia_semana,
                'hora_inicio' => $this->normalizeTimeToHi((string) $item->hora_inicio),
                'hora_fim' => $this->normalizeTimeToHi((string) $item->hora_fim),
            ])
            ->values();

        return response()->json([
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'nickname' => $profile?->nickname,
                'whatsapp' => $profile?->whatsapp,
                'regiao' => $profile?->regiao_nome,
                'idioma' => $profile?->idioma_nome,
                'regiao_id' => $profile?->regiao_id,
                'idioma_id' => $profile?->idioma_id,
                'plataforma_id' => $profile?->plataforma_id,
                'jogo_id' => $profile?->jogo_id,
                'geracao_id' => $profile?->geracao_id,
            ],
            'options' => [
                'regioes' => Regiao::query()->orderBy('nome')->get(['id', 'nome']),
                'idiomas' => Idioma::query()->orderBy('nome')->get(['id', 'nome']),
                'plataformas' => Plataforma::query()->orderBy('nome')->get(['id', 'nome']),
                'jogos' => Jogo::query()->orderBy('nome')->get(['id', 'nome']),
                'geracoes' => Geracao::query()->orderBy('nome')->get(['id', 'nome']),
            ],
            'disponibilidades' => $disponibilidades,
        ]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $user->fill([
            'name' => $payload['nome'] ?? $payload['name'] ?? $user->name,
            'email' => $payload['email'] ?? $user->email,
        ]);
        $user->save();

        $profile = $user->profile;
        if (! $profile) {
            $profile = new Profile(['user_id' => $user->id]);
        }

        $profile->fill([
            'nickname' => $payload['nickname'] ?? $profile->nickname,
            'plataforma_id' => $payload['plataforma_id'] ?? $profile->plataforma_id,
            'jogo_id' => $payload['jogo_id'] ?? $profile->jogo_id,
            'geracao_id' => $payload['geracao_id'] ?? $profile->geracao_id,
            'regiao_id' => $payload['regiao_id'] ?? $profile->regiao_id,
            'idioma_id' => $payload['idioma_id'] ?? $profile->idioma_id,
            'whatsapp' => $payload['whatsapp'] ?? $profile->whatsapp,
        ]);

        if (array_key_exists('regiao_id', $payload)) {
            $profile->regiao = $payload['regiao_id']
                ? Regiao::query()->find($payload['regiao_id'])?->nome
                : null;
        }

        if (array_key_exists('idioma_id', $payload)) {
            $profile->idioma = $payload['idioma_id']
                ? Idioma::query()->find($payload['idioma_id'])?->nome
                : null;
        }

        $profile->save();

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
        ]);
    }

    public function syncDisponibilidades(Request $request): JsonResponse
    {
        $user = $request->user();

        $normalizedEntries = collect($request->input('entries', []))
            ->map(function ($entry): array {
                $entry = is_array($entry) ? $entry : [];

                return [
                    'dia_semana' => $entry['dia_semana'] ?? null,
                    'hora_inicio' => $this->normalizeTimeToHi((string) ($entry['hora_inicio'] ?? '')),
                    'hora_fim' => $this->normalizeTimeToHi((string) ($entry['hora_fim'] ?? '')),
                ];
            })
            ->values();

        $request->merge([
            'entries' => $normalizedEntries->all(),
        ]);

        $data = $request->validate([
            'entries' => ['nullable', 'array'],
            'entries.*.dia_semana' => ['required', 'integer', 'min:0', 'max:6'],
            'entries.*.hora_inicio' => ['required', 'date_format:H:i'],
            'entries.*.hora_fim' => ['required', 'date_format:H:i'],
        ]);

        $entries = collect($data['entries'] ?? [])
            ->map(function (array $entry): array {
                if ($entry['hora_inicio'] >= $entry['hora_fim']) {
                    throw ValidationException::withMessages([
                        'entries' => ['Hora início deve ser menor que hora fim.'],
                    ]);
                }

                return [
                    'dia_semana' => (int) $entry['dia_semana'],
                    'hora_inicio' => $entry['hora_inicio'],
                    'hora_fim' => $entry['hora_fim'],
                ];
            })
            ->values();

        $entriesByDay = $entries->groupBy('dia_semana');
        foreach ($entriesByDay as $dayEntries) {
            $sorted = $dayEntries->sortBy('hora_inicio')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $previous = $sorted[$i - 1];
                $current = $sorted[$i];

                if ($current['hora_inicio'] <= $previous['hora_fim']) {
                    throw ValidationException::withMessages([
                        'entries' => ['Horários se sobrepõem ou encostam no mesmo dia.'],
                    ]);
                }
            }
        }

        DB::transaction(function () use ($entries, $user): void {
            UserDisponibilidade::query()->where('user_id', $user->id)->delete();

            foreach ($entries as $entry) {
                UserDisponibilidade::query()->create([
                    'user_id' => $user->id,
                    'dia_semana' => $entry['dia_semana'],
                    'hora_inicio' => $entry['hora_inicio'],
                    'hora_fim' => $entry['hora_fim'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Disponibilidades atualizadas com sucesso.',
            'count' => $entries->count(),
        ]);
    }

    private function normalizeTimeToHi(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return substr($value, 0, 5);
        }

        return $value;
    }
}
