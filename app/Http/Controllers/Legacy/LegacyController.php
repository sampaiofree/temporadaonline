<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegacyController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $confederacoes = $user
            ? $user->ligas()
                ->with(['confederacao:id,nome'])
                ->get(['ligas.id', 'ligas.confederacao_id'])
                ->map(fn (Liga $liga) => $liga->confederacao)
                ->filter()
                ->unique('id')
                ->sortBy('nome')
                ->values()
                ->map(fn ($confederacao) => [
                    'id' => (string) $confederacao->id,
                    'name' => (string) $confederacao->nome,
                ])
                ->all()
            : [];

        return view('legacy.index', [
            'legacyConfig' => [
                'profileSettingsUrl' => route('legacy.profile.settings'),
                'profileUpdateUrl' => route('legacy.profile.update'),
                'profileDisponibilidadesSyncUrl' => route('legacy.profile.disponibilidades.sync'),
                'logoutUrl' => route('legacy.logout'),
                'userId' => $request->user()?->id,
                'confederacoes' => $confederacoes,
                'onboardingClubeUrl' => route('legacy.onboarding_clube'),
            ],
        ]);
    }
}
