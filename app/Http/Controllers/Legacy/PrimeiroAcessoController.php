<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Concerns\ChecksProfileCompletion;
use App\Http\Controllers\Controller;
use App\Models\Geracao;
use App\Models\Idioma;
use App\Models\Jogo;
use App\Models\Plataforma;
use App\Models\Profile;
use App\Models\Regiao;
use App\Models\User;
use App\Models\UserDisponibilidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrimeiroAcessoController extends Controller
{
    use ChecksProfileCompletion;

    public function show(Request $request): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! $this->requiresFirstAccess($user)) {
            return redirect()->route('legacy.index');
        }

        $profile = $user->profile;
        $status = $this->buildStatus($user);

        $payload = [
            'profile' => $this->buildProfilePayload($user, $profile),
            'options' => [
                'regioes' => Regiao::query()->orderBy('nome')->get(['id', 'nome']),
                'idiomas' => Idioma::query()->orderBy('nome')->get(['id', 'nome']),
                'plataformas' => Plataforma::query()->orderBy('nome')->get(['id', 'nome']),
                'geracoes' => Geracao::query()->orderBy('nome')->get(['id', 'nome']),
                'jogos' => Jogo::query()->orderBy('nome')->get(['id', 'nome']),
            ],
            'disponibilidades' => UserDisponibilidade::query()
                ->where('user_id', $user->id)
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fim']),
            'endpoints' => [
                'update_profile_url' => route('legacy.primeiro_acesso.profile.update'),
                'sync_disponibilidades_url' => route('legacy.primeiro_acesso.disponibilidades.sync'),
                'finish_url' => route('legacy.index'),
            ],
            'status' => $status,
        ];

        return view('legacy.primeiro_acesso', [
            'firstAccessData' => $payload,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($request->has('whatsapp')) {
            $request->merge([
                'whatsapp' => preg_replace('/\D+/', '', (string) $request->input('whatsapp')),
            ]);
        }

        $profileId = $user->profile?->id;
        $payload = $request->validate([
            'regiao_id' => ['sometimes', 'required', 'integer', 'exists:regioes,id'],
            'idioma_id' => ['sometimes', 'required', 'integer', 'exists:idiomas,id'],
            'nickname' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('profiles', 'nickname')->ignore($profileId),
            ],
            'whatsapp' => ['sometimes', 'required', 'digits_between:10,15'],
            'plataforma_id' => ['sometimes', 'required', 'integer', 'exists:plataformas,id'],
            'geracao_id' => ['sometimes', 'required', 'integer', 'exists:geracoes,id'],
            'jogo_id' => ['sometimes', 'required', 'integer', 'exists:jogos,id'],
        ]);

        if ($payload === []) {
            throw ValidationException::withMessages([
                'payload' => ['Envie ao menos um campo para atualizar.'],
            ]);
        }

        $profile = $user->profile;
        if (! $profile) {
            $profile = new Profile(['user_id' => $user->id]);
        }

        $profile->fill(Arr::only($payload, [
            'regiao_id',
            'idioma_id',
            'nickname',
            'whatsapp',
            'plataforma_id',
            'geracao_id',
            'jogo_id',
        ]));

        if (array_key_exists('regiao_id', $payload)) {
            $profile->regiao = Regiao::query()->find($payload['regiao_id'])?->nome;
        }

        if (array_key_exists('idioma_id', $payload)) {
            $profile->idioma = Idioma::query()->find($payload['idioma_id'])?->nome;
        }

        $profile->save();
        $user->unsetRelation('profile');
        $user->load('profile');

        return response()->json([
            'message' => 'Etapa salva com sucesso.',
            'profile' => $this->buildProfilePayload($user, $user->profile),
            'status' => $this->buildStatus($user),
        ]);
    }

    public function syncDisponibilidades(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $data = $request->validate([
            'entries' => ['required', 'array', 'min:1'],
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
            'message' => 'Horários salvos com sucesso.',
            'status' => $this->buildStatus($user),
        ]);
    }

    private function buildProfilePayload(User $user, ?Profile $profile): array
    {
        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'regiao_id' => $profile?->regiao_id,
            'idioma_id' => $profile?->idioma_id,
            'nickname' => $profile?->nickname,
            'whatsapp' => $profile?->whatsapp,
            'plataforma_id' => $profile?->plataforma_id,
            'geracao_id' => $profile?->geracao_id,
            'jogo_id' => $profile?->jogo_id,
        ];
    }

    private function buildStatus(User $user): array
    {
        $profile = $user->profile;
        $hasAvailability = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->exists();

        $steps = [
            'regiao_idioma' => filled($profile?->regiao_id) && filled($profile?->idioma_id),
            'nickname' => filled($profile?->nickname),
            'whatsapp' => filled($profile?->whatsapp),
            'plataforma_geracao' => filled($profile?->plataforma_id) && filled($profile?->geracao_id),
            'jogo' => filled($profile?->jogo_id),
            'disponibilidade' => $hasAvailability,
        ];

        return [
            'steps' => $steps,
            'is_complete' => collect($steps)->every(fn (bool $done): bool => $done),
        ];
    }

    private function requiresFirstAccess(User $user): bool
    {
        $hasAvailability = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->exists();

        return ! ($this->hasCompleteProfile($user->profile) && $hasAvailability);
    }
}
