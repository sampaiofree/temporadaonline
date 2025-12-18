<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Liga;
use App\Models\LigaClube;
use Illuminate\Http\Request;

trait ResolvesLiga
{
    protected function resolveUserLiga(Request $request): Liga
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $ligaId = $request->query('liga_id') ?? $request->input('liga_id');
        if (! $ligaId) {
            abort(400, 'Liga não informada.');
        }

        $liga = $user->ligas()
            ->where('ligas.id', $ligaId)
            ->with(['jogo', 'geracao', 'plataforma'])
            ->first();

        if (! $liga) {
            abort(404, 'Liga não encontrada.');
        }

        return $liga;
    }

    protected function resolveUserClub(Request $request): ?LigaClube
    {
        $liga = $this->resolveUserLiga($request);
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return $user->clubesLiga()->where('liga_id', $liga->id)->first();
    }

    protected function makeAppContext(?Liga $liga = null, ?LigaClube $clube = null): array
    {
        $mode = $liga && $clube ? 'liga' : 'global';

        return [
            'mode' => $mode,
            'liga' => $liga ? ['id' => $liga->id, 'nome' => $liga->nome] : null,
            'clube' => $clube ? ['id' => $clube->id, 'nome' => $clube->nome] : null,
        ];
    }
}
