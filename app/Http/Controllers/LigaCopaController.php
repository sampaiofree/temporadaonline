<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Services\LigaCopaService;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaCopaController extends Controller
{
    use ResolvesLiga;

    public function __construct(
        private readonly LigaCopaService $ligaCopaService,
    ) {
    }

    public function index(Request $request): View|Response
    {
        $liga = $this->resolveUserLiga($request);
        $clube = $this->resolveUserClub($request);

        if (! $this->ligaCopaService->schemaReady()) {
            return response('Copa da Liga indisponivel durante atualizacao.', 503);
        }

        return view('liga_copa', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => $clube ? [
                'id' => $clube->id,
                'nome' => $clube->nome,
            ] : null,
            'copa' => $this->ligaCopaService->buildPayload($liga, $clube),
            'appContext' => $this->makeAppContext($liga, $clube, 'liga'),
        ]);
    }
}
