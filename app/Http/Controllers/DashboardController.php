<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksProfileCompletion;
use App\Models\LigaClubeElenco;
use App\Models\Partida;
use App\Models\UserDisponibilidade;
use App\Services\LigaClassificacaoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ChecksProfileCompletion;

    public function __construct(private LigaClassificacaoService $classificationService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $profileComplete = $this->hasCompleteProfile($user->profile);
        $hasAvailability = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->exists();
        $inAnyLeague = $user->ligas()->exists();
        $hasActiveRoster = LigaClubeElenco::query()
            ->where('ativo', true)
            ->whereHas('ligaClube', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->exists();

        $clubIds = $user->clubesLiga()->pluck('id');
        $hasConfirmedMatch = false;

        if ($clubIds->isNotEmpty()) {
            $hasConfirmedMatch = Partida::query()
                ->where('estado', 'placar_confirmado')
                ->where(function ($query) use ($clubIds) {
                    $query->whereIn('mandante_id', $clubIds)
                        ->orWhereIn('visitante_id', $clubIds);
                })
                ->exists();
        }

        $hasPoints = $this->classificationService->userHasPoints($user);

        $primaryLigaId = $user->ligas()->value('ligas.id');
        $ligasFallback = route('ligas');

        $checklist = [
            [
                'id' => 'perfil',
                'title' => 'Completar perfil',
                'description' => 'Adicione região, idioma, plataforma, jogo, geração, nickname e WhatsApp no seu perfil.',
                'done' => $profileComplete,
                
            ],
            [
                'id' => 'horarios',
                'title' => 'Registrar horário de partida',
                'description' => 'Cadastre ao menos um horário disponível em sua agenda.',
                'done' => $hasAvailability,
                'actionLabel' => 'Registrar horário',
                'actionHref' => route('perfil').'#horarios',
            ],
            [
                'id' => 'liga',
                'title' => 'Entrar em uma liga',
                'description' => 'Escolha uma liga e confirme sua participação.',
                'done' => $inAnyLeague,
                'actionLabel' => 'Entrar em liga',
                'actionHref' => route('ligas'),
            ],
            [
                'id' => 'elenco',
                'title' => 'Ter jogador no elenco',
                'description' => 'Tenha pelo menos um atleta ativo em qualquer clube seu.',
                'done' => $hasActiveRoster,
                'actionLabel' => 'Ir ao mercado',
                'actionHref' => $primaryLigaId ? route('liga.mercado', ['liga_id' => $primaryLigaId]) : $ligasFallback,
            ],
            [
                'id' => 'partida',
                'title' => 'Concluir uma partida',
                'description' => 'Partidas com placar confirmado valem como experiência.',
                'done' => $hasConfirmedMatch,
                'actionLabel' => 'Ver partidas',
                'actionHref' => $primaryLigaId ? route('liga.partidas', ['liga_id' => $primaryLigaId]) : $ligasFallback,
            ],
            [
                'id' => 'pontos',
                'title' => 'Pontuar em uma liga',
                'description' => 'Conquiste pelo menos 1 ponto em qualquer classificação de liga.',
                'done' => $hasPoints,
                'actionLabel' => 'Ver tabela',
                'actionHref' => $primaryLigaId ? route('liga.classificacao', ['liga_id' => $primaryLigaId]) : $ligasFallback,
            ],
        ];

        $showChecklist = collect($checklist)->contains(function (array $item): bool {
            return ! $item['done'];
        });

        return view('dashboard', [
            'appContext' => [
                'mode' => 'global',
                'liga' => null,
                'clube' => null,
                'nav' => 'home',
            ],
            'checklist' => $checklist,
            'showChecklist' => $showChecklist,
        ]);
    }

}
