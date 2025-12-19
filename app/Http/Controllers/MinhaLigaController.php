<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaTransferencia;
use App\Services\LeagueFinanceService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\ResolvesLiga;

class MinhaLigaController extends Controller
{
    use ResolvesLiga;
    public function show(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $request->user()->clubesLiga()->where('liga_id', $liga->id)->first();

        return view('minha_liga', [
            'appContext' => $this->makeAppContext($liga, $userClub, 'clube'),
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
            'imagem' => $liga->imagem,
            'tipo' => $liga->tipo,
            'status' => $liga->status,
            'jogo' => $liga->jogo?->nome,
            'geracao' => $liga->geracao?->nome,
            'plataforma' => $liga->plataforma?->nome,
        ]]);
    }

    public function financeiro(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $request->user()->clubesLiga()->where('liga_id', $liga->id)->first();

        $saldo = null;
        $salarioPorRodada = 0;
        $rodadasRestantes = null;
        $movimentos = [];

        if ($userClub) {
            $walletSaldo = LigaClubeFinanceiro::query()
                ->where('liga_id', $liga->id)
                ->where('clube_id', $userClub->id)
                ->value('saldo');

            $saldo = $walletSaldo !== null ? (int) $walletSaldo : (int) ($liga->saldo_inicial ?? 0);

            $salarioPorRodada = (int) LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $userClub->id)
                ->where('ativo', true)
                ->sum('wage_eur');

            $rodadasRestantes = $salarioPorRodada > 0
                ? (int) floor($saldo / $salarioPorRodada)
                : null;

            $movimentos = LigaTransferencia::query()
                ->where('liga_id', $liga->id)
                ->where(function ($query) use ($userClub): void {
                    $query->where('clube_origem_id', $userClub->id)
                        ->orWhere('clube_destino_id', $userClub->id);
                })
                ->latest()
                ->limit(5)
                ->get([
                    'id',
                    'tipo',
                    'valor',
                    'observacao',
                    'created_at',
                    'clube_origem_id',
                    'clube_destino_id',
                ])
                ->toArray();
        }

        return view('minha_liga_financeiro', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'imagem' => $liga->imagem,
                'jogo' => $liga->jogo?->nome,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
            ] : null,
            'financeiro' => [
                'saldo' => $saldo,
                'salarioPorRodada' => $salarioPorRodada,
                'rodadasRestantes' => $rodadasRestantes,
                'movimentos' => $movimentos,
            ],
            'appContext' => $this->makeAppContext($liga, $userClub, 'clube'),
        ]);
    }

    public function meuElenco(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $request->user()->clubesLiga()->where('liga_id', $liga->id)->first();

        $entries = [];
        $salaryPerRound = 0;

        if ($userClub) {
            $elenco = LigaClubeElenco::with('elencopadrao')
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $userClub->id)
                ->get();

            $salaryPerRound = (int) $elenco->sum('wage_eur');

            $entries = $elenco->map(function (LigaClubeElenco $entry) {
                $player = $entry->elencopadrao;

                return [
                    'id' => $entry->id,
                    'ativo' => (bool) $entry->ativo,
                    'value_eur' => (int) ($entry->value_eur ?? 0),
                    'wage_eur' => (int) ($entry->wage_eur ?? 0),
                    'snapshot_value_eur' => (int) ($player?->value_eur ?? 0),
                    'snapshot_wage_eur' => (int) ($player?->wage_eur ?? 0),
                    'elencopadrao' => [
                        'id' => $player?->id,
                        'short_name' => $player?->short_name,
                        'long_name' => $player?->long_name,
                        'player_positions' => $player?->player_positions,
                        'overall' => $player?->overall,
                        'player_face_url' => $player?->player_face_url,
                        'value_eur' => $player?->value_eur,
                        'wage_eur' => $player?->wage_eur,
                    ],
                ];
            })->values()->all();
        }

        return view('minha_liga_meu_elenco', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'imagem' => $liga->imagem,
                'jogo' => $liga->jogo?->nome,
                'max_jogadores_por_clube' => $liga->max_jogadores_por_clube,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
            ] : null,
            'elenco' => [
                'players' => $entries,
                'player_count' => count($entries),
                'max_players' => (int) ($liga->max_jogadores_por_clube ?? 18),
                'salary_per_round' => $salaryPerRound,
            ],
            'appContext' => $this->makeAppContext($liga, $userClub, 'clube'),
        ]);
    }

    public function storeClube(Request $request): JsonResponse
    {
        $liga = $this->resolveUserLiga($request);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
        ]);

        $clube = LigaClube::updateOrCreate(
            [
                'liga_id' => $liga->id,
                'user_id' => $request->user()->id,
            ],
            [
                'nome' => $validated['nome'],
            ],
        );

        $wallet = app(LeagueFinanceService::class)->initClubWallet($liga->id, $clube->id);

        return response()->json([
            'message' => $clube->wasRecentlyCreated
                ? 'Clube criado com sucesso.'
                : 'Nome do clube foi alterado com sucesso.',
            'clube' => $clube,
            'financeiro' => [
                'saldo' => (int) $wallet->saldo,
            ],
        ], 201);
    }

    public function addPlayerToClub(Request $request): JsonResponse
    {
        $liga = $this->resolveUserLiga($request);
        $user = $request->user();
        $club = $user->clubesLiga()->where('liga_id', $liga->id)->first();

        if (! $club) {
            return response()->json([
                'message' => 'Você precisa criar um clube para esta liga antes de adicionar jogadores.',
            ], 422);
        }

        $validated = $request->validate([
            'elencopadrao_id' => ['required', 'integer', 'exists:elencopadrao,id'],
        ]);

        try {
            $entry = app(TransferService::class)->buyPlayer(
                ligaId: (int) $liga->id,
                compradorClubeId: (int) $club->id,
                elencopadraoId: (int) $validated['elencopadrao_id'],
            );

            return response()->json([
                'message' => 'Jogador adicionado ao elenco.',
                'elencopadrao_id' => $entry->elencopadrao_id,
            ], 201);
        } catch (\DomainException $exception) {
            $message = $exception->getMessage();
            $status = str_contains($message, 'já faz parte') ? 409 : 422;

            return response()->json(['message' => $message], $status);
        }
    }

}
