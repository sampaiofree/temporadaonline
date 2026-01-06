<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Liga;
use App\Models\LigaClube;
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
            'escudo',
            'clubeElencos.elencopadrao',
        ]);

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
                'escudo_url' => $this->resolveEscudoUrl($clube->escudo?->clube_imagem),
                'esquema_tatico_imagem_url' => $this->resolveStorageUrl($clube->esquema_tatico_imagem),
                'players' => $players,
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
}
