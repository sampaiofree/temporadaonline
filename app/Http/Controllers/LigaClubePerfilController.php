<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaClubeConquista;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaClubePerfilController extends Controller
{
    use ResolvesLiga;

    public function show(Request $request, LigaClube $clube): View
    {
        if (! $request->query('liga_id') && ! $request->input('liga_id') && $clube->liga_id) {
            $request->merge(['liga_id' => $clube->liga_id]);
        }

        $liga = $this->resolveUserLiga($request);
        $userClub = $request->user()?->clubesLiga()->where('liga_id', $liga->id)->first();
        $nav = $userClub && (int) $userClub->id === (int) $clube->id ? 'clube' : 'tabela';

        if ((int) $clube->liga_id !== (int) $liga->id) {
            abort(404);
        }

        $clube->load([
            'user.profile',
            'user.profile.plataformaRegistro',
            'escudo',
            'clubeElencos.elencopadrao',
        ]);

        $valorElenco = (int) $clube->clubeElencos->sum(fn ($entry) => (int) ($entry->value_eur ?? 0));
        $saldo = (int) (LigaClubeFinanceiro::query()
            ->where('liga_id', $liga->id)
            ->where('clube_id', $clube->id)
            ->value('saldo') ?? 0);
        $clubValue = $valorElenco + $saldo;
        $fansTotal = $this->sumClaimedConquistaFans($liga, $clube);

        $achievements = LigaClubeConquista::query()
            ->with('conquista')
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $clube->id)
            ->whereNotNull('claimed_at')
            ->orderByDesc('claimed_at')
            ->get()
            ->map(function (LigaClubeConquista $registro) {
                $conquista = $registro->conquista;
                return [
                    'id' => $registro->id,
                    'nome' => $conquista?->nome,
                    'image_url' => $this->resolveStorageUrl($conquista?->imagem),
                ];
            })
            ->filter(fn ($item) => $item['image_url'])
            ->values()
            ->all();

        $players = $clube->clubeElencos->map(function ($entry) {
            $player = $entry->elencopadrao;

            return [
                'id' => $player?->id,
                'short_name' => $player?->short_name,
                'long_name' => $player?->long_name,
                'player_positions' => $player?->player_positions,
                'overall' => $player?->overall,
                'player_face_url' => $player?->player_face_url,
            ];
        })->filter(fn ($player) => $player['id'])->values();

        $owner = $clube->user;
        $profile = $owner?->profile;
        $plataformaImagem = $profile?->plataformaRegistro?->imagem;

        return view('liga_clube_perfil', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
            ],
            'clube' => [
                'id' => $clube->id,
                'nome' => $clube->nome,
                'nickname' => $profile?->nickname,
                'dono' => $owner?->name,
                'plataforma' => $profile?->plataforma_nome,
                'geracao' => $profile?->geracao_nome,
                'plataforma_imagem' => $this->resolveStorageUrl($plataformaImagem),
                'escudo_url' => $this->resolveEscudoUrl($clube->escudo?->clube_imagem),
                'esquema_tatico_imagem_url' => $this->resolveStorageUrl($clube->esquema_tatico_imagem),
                'players' => $players,
                'valor_elenco' => $valorElenco,
                'saldo' => $saldo,
                'club_value' => $clubValue,
                'achievement_images' => $achievements,
                'fans_total' => $fansTotal,
            ],
            'appContext' => $this->makeAppContext($liga, $userClub, $nav),
        ]);
    }

    private function resolveEscudoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        return \Storage::disk('public')->url($path);
    }

    private function resolveStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        return \Storage::disk('public')->url($path);
    }

    private function sumClaimedConquistaFans(Liga $liga, LigaClube $clube): int
    {
        return (int) LigaClubeConquista::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $clube->id)
            ->whereNotNull('claimed_at')
            ->join('conquistas', 'conquistas.id', 'liga_clube_conquistas.conquista_id')
            ->sum('conquistas.fans');
    }
}
