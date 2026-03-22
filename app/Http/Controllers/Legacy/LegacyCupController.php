<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\User;
use App\Services\LigaCopaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegacyCupController extends Controller
{
    public function __construct(
        private readonly LigaCopaService $ligaCopaService,
    ) {
    }

    public function data(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveActiveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga ativa encontrada para esta confederacao.',
                'liga' => null,
                'clube' => null,
                'cup' => [
                    'summary' => [],
                    'groups' => [],
                    'bracket' => [
                        'current_phase_type' => null,
                        'current_phase_label' => null,
                        'phases' => [],
                        'champion' => null,
                    ],
                    'matches' => [],
                ],
            ], 404);
        }

        if (! $this->ligaCopaService->schemaReady()) {
            return response()->json([
                'message' => 'Copa da Liga indisponivel durante atualizacao.',
            ], 503);
        }

        $clube = $user->clubesLiga()
            ->with('escudo:id,clube_imagem')
            ->where('liga_id', $liga->id)
            ->first();

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'status' => $liga->status,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => $clube ? [
                'id' => $clube->id,
                'nome' => $clube->nome,
                'escudo_url' => $this->resolveEscudoUrl($clube->escudo?->clube_imagem),
            ] : null,
            'cup' => $this->ligaCopaService->buildPayload($liga, $clube),
            'onboarding_url' => route('legacy.onboarding_clube', [
                'stage' => 'confederacao',
                'confederacao_id' => $liga->confederacao_id,
            ]),
        ]);
    }

    private function resolveActiveMarketLiga(User $user, ?int $confederacaoId): ?Liga
    {
        $query = $user->ligas()
            ->with(['jogo:id,nome', 'confederacao:id,nome,jogo_id,timezone'])
            ->where('ligas.status', 'ativa')
            ->orderByDesc('ligas.id');

        if ($confederacaoId) {
            $query->where('ligas.confederacao_id', $confederacaoId);
        }

        return $query->first([
            'ligas.id',
            'ligas.nome',
            'ligas.status',
            'ligas.saldo_inicial',
            'ligas.multa_multiplicador',
            'ligas.jogo_id',
            'ligas.confederacao_id',
            'ligas.timezone',
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

        return Storage::disk('public')->url($path);
    }
}
