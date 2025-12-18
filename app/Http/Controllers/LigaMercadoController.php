<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Elencopadrao;
use App\Models\LigaClubeElenco;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaMercadoController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $this->resolveUserClub($request);

        $elencos = LigaClubeElenco::with(['elencopadrao', 'ligaClube'])
            ->where('liga_id', $liga->id)
            ->get()
            ->keyBy('elencopadrao_id');

        $players = Elencopadrao::query()
            ->where('jogo_id', $liga->jogo_id)
            ->orderByDesc('overall')
            ->limit(48)
            ->get()
            ->map(function (Elencopadrao $player) use ($elencos, $userClub) {
                $entry = $elencos->get($player->id);
                $club = $entry?->ligaClube;

                $clubStatus = 'livre';
                $canBuy = ! $entry;
                $canMulta = false;

                if ($entry && $club) {
                    $clubStatus = $userClub && $club->id === $userClub->id ? 'meu' : 'outro';
                    $canBuy = $clubStatus === 'outro';
                    $canMulta = $clubStatus === 'outro';
                }

                return [
                    'elencopadrao_id' => $player->id,
                    'short_name' => $player->short_name,
                    'long_name' => $player->long_name,
                    'player_positions' => $player->player_positions,
                    'overall' => $player->overall,
                    'value_eur' => $player->value_eur,
                    'wage_eur' => $player->wage_eur,
                    'club_status' => $clubStatus,
                    'club_name' => $club?->nome,
                    'club_id' => $club?->id,
                    'is_free_agent' => $clubStatus === 'livre',
                    'can_buy' => $canBuy,
                    'can_multa' => $canMulta,
                    'player_face_url' => $player->player_face_url,
                ];
            })
            ->values()
            ->all();

        return view('liga_mercado', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'jogo' => $liga->jogo?->nome,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
            ] : null,
            'players' => $players,
            'appContext' => $this->makeAppContext($liga, $userClub, 'mercado'),
        ]);
    }
}
