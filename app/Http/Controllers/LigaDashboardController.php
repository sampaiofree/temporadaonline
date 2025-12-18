<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\LigaClube;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LigaDashboardController extends Controller
{
    use ResolvesLiga;

    public function show(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $clube = $this->resolveUserClub($request);
        $hasClub = $clube !== null;

        $totalClubs = (int) LigaClube::query()->where('liga_id', $liga->id)->count();
        if ($totalClubs === 0) {
            $totalClubs = 1;
        }

        $position = null;
        $points = null;
        if ($clube) {
            $position = (($clube->id % $totalClubs) + 1);
            $points = max(0, 30 - ($position * 2));
        }

        $nextMatch = $hasClub
            ? [
                'round' => 1,
                'opponent' => 'Time Demo FC',
                'status' => 'Agendada',
                'date' => Carbon::now()->addDays(3)->toIso8601String(),
            ]
            : null;

        return view('liga_dashboard', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'descricao' => $liga->descricao,
                'imagem' => $liga->imagem,
                'jogo' => $liga->jogo?->nome,
                'tipo' => $liga->tipo,
            ],
            'clube' => $clube ? [
                'id' => $clube->id,
                'nome' => $clube->nome,
            ] : null,
            'hasClub' => $hasClub,
            'nextMatch' => $nextMatch,
            'classification' => [
                'position' => $position,
                'total' => $totalClubs,
                'points' => $points,
            ],
            'actions' => [
                [
                    'label' => 'Mercado',
                    'description' => 'Veja todos os jogadores disponíveis e negocie contratos.',
                    'href' => route('liga.mercado', ['liga_id' => $liga->id]),
                ],
                [
                    'label' => 'Meu elenco',
                    'description' => 'Gerencie seu time com filtros e status atualizados.',
                    'href' => route('minha_liga.meu_elenco', ['liga_id' => $liga->id]),
                ],
                [
                    'label' => 'Financeiro',
                    'description' => 'Saldo, salario por rodada e movimentos recentes.',
                    'href' => route('minha_liga.financeiro', ['liga_id' => $liga->id]),
                ],
                [
                    'label' => 'Partidas',
                    'description' => 'Em breve: cadastre partidas e acompanhe o torneio.',
                    'href' => route('liga.partidas', ['liga_id' => $liga->id]),
                ],
                [
                    'label' => 'Classificação',
                    'description' => 'Em breve: ranking completo da liga.',
                    'href' => null,
                ],
            ],
            'appContext' => $this->makeAppContext($liga, $clube),
        ]);
    }
}
