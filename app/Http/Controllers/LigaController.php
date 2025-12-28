<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksProfileCompletion;
use App\Models\Liga;
use App\Models\LigaPeriodo;
use App\Models\Profile;
use App\Models\UserDisponibilidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LigaController extends Controller
{
    use ChecksProfileCompletion;

    public function index(): View
    {
        $user = Auth::user();
        $userId = $user?->id;
        $ligas = Liga::with(['jogo', 'geracao', 'plataforma', 'users', 'periodos'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Liga $liga) => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'descricao' => $liga->descricao,
                'regras' => $liga->regras,
                'imagem' => $liga->imagem,
                'tipo' => $liga->tipo,
                'status' => $liga->status,
                'max_times' => $liga->max_times,
                'jogo' => $liga->jogo?->nome,
                'geracao' => $liga->geracao?->nome,
                'plataforma' => $liga->plataforma?->nome,
                'registered' => $userId ? $liga->users->contains($userId) : false,
                'created_at' => $liga->created_at?->toIso8601String(),
                'periodo' => LigaPeriodo::activeRangeForLiga($liga),
                'periodos' => $liga->periodos
                    ->sortBy('inicio')
                    ->map(fn (LigaPeriodo $periodo) => [
                        'codigo' => $periodo->id,
                        'inicio_label' => $periodo->inicio?->format('d/m/Y'),
                        'fim_label' => $periodo->fim?->format('d/m/Y'),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        $myLigas = array_values(array_filter($ligas, fn (array $liga) => $liga['registered']));

        $profileComplete = $this->hasCompleteProfile($user?->profile ?? null);
        $hasAvailability = $userId
            ? UserDisponibilidade::query()->where('user_id', $userId)->exists()
            : false;
        $requireProfileCompletion = ! ($profileComplete && $hasAvailability);

        return view('ligas', [
            'ligas' => $ligas,
            'myLigas' => $myLigas,
            'appContext' => [
                'mode' => 'global',
                'liga' => null,
                'clube' => null,
                'nav' => 'ligas',
            ],
            'requireProfileCompletion' => $requireProfileCompletion,
            'profileUrl' => route('perfil'),
            'profileHorariosUrl' => route('perfil').'#horarios',
        ]);
    }

    public function join(Request $request, Liga $liga): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $profile = $user->profile;
        if (! $this->hasCompleteProfile($profile)) {
            return response()->json([
                'message' => 'Complete seu perfil antes de entrar em uma liga.',
            ], 422);
        }

        if (! $profile->jogo_id || ! $profile->geracao_id || ! $profile->plataforma_id) {
            $liga->loadMissing(['jogo', 'geracao', 'plataforma']);
        }

        $mismatches = $this->profileLigaMismatches($profile, $liga);
        if ($mismatches !== []) {
            return response()->json([
                'message' => 'Perfil incompativel com a liga. Atualize seu perfil para corresponder a: '
                    . implode(', ', $mismatches)
                    . '.',
            ], 422);
        }

        $user->ligas()->syncWithoutDetaching([$liga->id]);

        return response()->json([
            'redirect' => route('minha_liga', ['liga_id' => $liga->id]),
        ]);
    }

    private function profileLigaMismatches(Profile $profile, Liga $liga): array
    {
        $mismatches = [];

        if (! $this->matchesProfileAttribute(
            $profile->jogo_id,
            $liga->jogo_id,
            $profile->jogo,
            $liga->jogo?->nome,
        )) {
            $mismatches[] = 'jogo';
        }

        if (! $this->matchesProfileAttribute(
            $profile->geracao_id,
            $liga->geracao_id,
            $profile->geracao,
            $liga->geracao?->nome,
        )) {
            $mismatches[] = 'geracao';
        }

        if (! $this->matchesProfileAttribute(
            $profile->plataforma_id,
            $liga->plataforma_id,
            $profile->plataforma,
            $liga->plataforma?->nome,
        )) {
            $mismatches[] = 'plataforma';
        }

        return $mismatches;
    }

    private function matchesProfileAttribute(
        ?int $profileId,
        ?int $ligaId,
        ?string $profileValue,
        ?string $ligaValue,
    ): bool {
        if ($profileId && $ligaId) {
            return $profileId === $ligaId;
        }

        if (filled($profileValue) && filled($ligaValue)) {
            return $profileValue === $ligaValue;
        }

        return false;
    }
}
