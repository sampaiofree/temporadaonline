<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaEscudo;
use App\Models\LigaPeriodo;
use App\Models\LigaTransferencia;
use App\Models\PartidaFolhaPagamento;
use App\Models\EscudoClube;
use App\Models\Pais;
use App\Services\LeagueFinanceService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\ResolvesLiga;
use Illuminate\Support\Str;

class MinhaLigaController extends Controller
{
    use ResolvesLiga;
    public function show(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $liga->load(['periodos' => fn ($query) => $query->orderBy('inicio')]);
        $userClub = $request->user()->clubesLiga()
            ->where('liga_id', $liga->id)
            ->with(['escudo', 'financeiro'])
            ->first();
        $escudos = EscudoClube::orderBy('clube_nome')->get(['id', 'clube_nome', 'clube_imagem']);
        $usedEscudos = LigaClube::query()
            ->whereNotNull('escudo_clube_id')
            ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $liga->confederacao_id))
            ->when($userClub, fn ($query) => $query->where('id', '<>', $userClub->id))
            ->pluck('escudo_clube_id')
            ->values();
        $elencoCount = null;
        $saldo = null;

        if ($userClub) {
            $elencoCount = LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $userClub->id)
                ->count();

            $walletSaldo = LigaClubeFinanceiro::query()
                ->where('liga_id', $liga->id)
                ->where('clube_id', $userClub->id)
                ->value('saldo');

            $saldo = $walletSaldo !== null ? (int) $walletSaldo : (int) ($liga->saldo_inicial ?? 0);
        }

        $periodos = $liga->periodos
            ->map(function (LigaPeriodo $periodo) {
                return [
                    'codigo' => $periodo->id,
                    'inicio' => $periodo->inicio?->toDateString(),
                    'fim' => $periodo->fim?->toDateString(),
                    'inicio_label' => $periodo->inicio?->format('d/m/Y'),
                    'fim_label' => $periodo->fim?->format('d/m/Y'),
                ];
            })
            ->values()
            ->all();

        $periodoAtual = LigaPeriodo::activeRangeForLiga($liga);

        return view('minha_liga', [
            'appContext' => $this->makeAppContext($liga, $userClub, 'clube'),
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'imagem' => $liga->imagem,
                'descricao' => $liga->descricao,
                'regras' => $liga->regras,
                'whatsapp_grupo_link' => $liga->whatsapp_grupo_link,
                'tipo' => $liga->tipo,
                'status' => $liga->status,
                'jogo' => $liga->jogo?->nome,
                'geracao' => $liga->geracao?->nome,
                'plataforma' => $liga->plataforma?->nome,
                'periodos' => $periodos,
                'periodo_atual' => $periodoAtual,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
                'escudo_id' => $userClub->escudo_clube_id,
                'escudo_url' => $userClub->escudo?->clube_imagem
                    ? '/storage/'.$userClub->escudo->clube_imagem
                    : null,
                'elenco_count' => $elencoCount ?? 0,
                'saldo' => $saldo,
            ] : null,
            'escudos' => $escudos,
            'usedEscudos' => $usedEscudos,
        ]);
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

            $movimentosTransferencias = LigaTransferencia::query()
                ->where(function ($query) use ($userClub): void {
                    $query->where('clube_origem_id', $userClub->id)
                        ->orWhere('clube_destino_id', $userClub->id);
                })
                ->with(['elencopadrao:id,short_name,long_name'])
                ->latest()
                ->limit(10)
                ->get([
                    'id',
                    'tipo',
                    'valor',
                    'observacao',
                    'created_at',
                    'clube_origem_id',
                    'clube_destino_id',
                    'elencopadrao_id',
                ])
                ->map(function (LigaTransferencia $movimento) {
                    $player = $movimento->elencopadrao;
                    $jogadorNome = $player?->short_name ?: $player?->long_name;

                    return [
                        'id' => $movimento->id,
                        'tipo' => $movimento->tipo,
                        'valor' => $movimento->valor,
                        'observacao' => $movimento->observacao,
                        'created_at' => $movimento->created_at,
                        'clube_origem_id' => $movimento->clube_origem_id,
                        'clube_destino_id' => $movimento->clube_destino_id,
                        'elencopadrao_id' => $movimento->elencopadrao_id,
                        'jogador_nome' => $jogadorNome,
                    ];
                })
                ->values()
                ->all();

            $movimentosPartida = PartidaFolhaPagamento::query()
                ->where('liga_id', $liga->id)
                ->where('clube_id', $userClub->id)
                ->latest()
                ->limit(10)
                ->get(['id', 'partida_id', 'total_wage', 'multa_wo', 'created_at'])
                ->flatMap(function (PartidaFolhaPagamento $pagamento) use ($userClub) {
                    $items = [];

                    $items[] = [
                        'id' => 'partida-salario-'.$pagamento->id,
                        'tipo' => 'salario_partida',
                        'valor' => (int) $pagamento->total_wage,
                        'observacao' => "Salário da partida #{$pagamento->partida_id}",
                        'created_at' => $pagamento->created_at,
                        'clube_origem_id' => $userClub->id,
                        'clube_destino_id' => null,
                    ];

                    if ((int) $pagamento->multa_wo > 0) {
                        $items[] = [
                            'id' => 'partida-multa-'.$pagamento->id,
                            'tipo' => 'multa_wo',
                            'valor' => (int) $pagamento->multa_wo,
                            'observacao' => "Multa W.O. partida #{$pagamento->partida_id}",
                            'created_at' => $pagamento->created_at,
                            'clube_origem_id' => $userClub->id,
                            'clube_destino_id' => null,
                        ];
                    }

                    return $items;
                })
                ->values()
                ->all();

            $movimentos = collect(array_merge($movimentosTransferencias, $movimentosPartida))
                ->sortByDesc('created_at')
                ->take(5)
                ->values()
                ->all();
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

    public function clube(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $request->user()->clubesLiga()
            ->where('liga_id', $liga->id)
            ->with('escudo')
            ->first();

        $filters = [
            'search' => $request->query('search', ''),
            'escudo_pais_id' => $request->query('escudo_pais_id', ''),
            'escudo_liga_id' => $request->query('escudo_liga_id', ''),
            'only_available' => $request->boolean('only_available'),
        ];

        $usedEscudos = LigaClube::query()
            ->whereNotNull('escudo_clube_id')
            ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $liga->confederacao_id))
            ->pluck('escudo_clube_id')
            ->values();

        $selectedEscudoId = $userClub?->escudo_clube_id;
        $usedEscudosForFilter = $usedEscudos->when(
            $selectedEscudoId,
            fn ($escudos) => $escudos->reject(
                fn ($id) => (int) $id === (int) $selectedEscudoId,
            ),
        );

        $escudosQuery = EscudoClube::query()
            ->select(['id', 'clube_nome', 'clube_imagem', 'pais_id', 'liga_id'])
            ->orderBy('clube_nome');

        $search = trim((string) $filters['search']);
        if ($search !== '') {
            $term = Str::lower($search);
            $escudosQuery->whereRaw('LOWER(clube_nome) LIKE ?', ['%'.$term.'%']);
        }

        if ($filters['escudo_pais_id']) {
            $escudosQuery->where('pais_id', (int) $filters['escudo_pais_id']);
        }

        if ($filters['escudo_liga_id']) {
            $escudosQuery->where('liga_id', (int) $filters['escudo_liga_id']);
        }

        if ($filters['only_available'] && $usedEscudosForFilter->isNotEmpty()) {
            $escudosQuery->whereNotIn('id', $usedEscudosForFilter);
        }

        $escudos = $escudosQuery
            ->paginate(48)
            ->appends($request->query());

        $paises = Pais::orderBy('nome')->get(['id', 'nome']);
        $ligasEscudos = LigaEscudo::orderBy('liga_nome')->get(['id', 'liga_nome']);

        return view('minha_liga_clube', [
            'appContext' => $this->makeAppContext($liga, $userClub, 'clube'),
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'imagem' => $liga->imagem,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
                'escudo_id' => $userClub->escudo_clube_id,
                'escudo' => $userClub->escudo
                    ? [
                        'id' => $userClub->escudo->id,
                        'clube_nome' => $userClub->escudo->clube_nome,
                        'clube_imagem' => $userClub->escudo->clube_imagem,
                    ]
                    : null,
            ] : null,
            'escudos' => $escudos,
            'paises' => $paises,
            'ligasEscudos' => $ligasEscudos,
            'usedEscudos' => $usedEscudos,
            'filters' => $filters,
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
                'esquema_tatico_imagem_url' => $this->resolveStorageUrl($userClub->esquema_tatico_imagem),
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

    public function esquemaTatico(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $request->user()->clubesLiga()->where('liga_id', $liga->id)->first();

        $entries = [];

        if ($userClub) {
            $elenco = LigaClubeElenco::with('elencopadrao')
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $userClub->id)
                ->where('ativo', true)
                ->get();

            $entries = $elenco->map(function (LigaClubeElenco $entry) {
                $player = $entry->elencopadrao;

                return [
                    'id' => $entry->id,
                    'short_name' => $player?->short_name,
                    'long_name' => $player?->long_name,
                    'player_positions' => $player?->player_positions,
                    'overall' => $player?->overall,
                    'player_face_url' => $player?->player_face_url,
                ];
            })->values()->all();
        }

        return view('minha_liga_esquema_tatico', [
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
            'esquema' => [
                'players' => $entries,
                'layout' => $userClub?->esquema_tatico_layout,
                'image_url' => $this->resolveStorageUrl($userClub?->esquema_tatico_imagem),
            ],
            'appContext' => $this->makeAppContext($liga, $userClub, 'clube'),
        ]);
    }

    public function salvarEsquemaTatico(Request $request): JsonResponse
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $request->user()->clubesLiga()->where('liga_id', $liga->id)->first();

        if (! $userClub) {
            return response()->json([
                'message' => 'Clube não encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'layout' => ['required', 'string'],
            'imagem' => ['required', 'image', 'max:4096'],
        ]);

        $layout = json_decode($validated['layout'], true);
        if (! is_array($layout)) {
            return response()->json([
                'message' => 'Layout inválido.',
            ], 422);
        }

        $rawPlayers = $layout['players'] ?? [];
        if (! is_array($rawPlayers)) {
            return response()->json([
                'message' => 'Layout inválido.',
            ], 422);
        }

        $validIds = LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $userClub->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validIdsMap = array_flip($validIds);
        $normalizedPlayers = [];

        foreach ($rawPlayers as $player) {
            if (! is_array($player)) {
                continue;
            }

            $id = (int) ($player['id'] ?? 0);
            if (! $id || ! isset($validIdsMap[$id])) {
                continue;
            }

            $x = $player['x'] ?? null;
            $y = $player['y'] ?? null;
            if (! is_numeric($x) || ! is_numeric($y)) {
                continue;
            }

            $x = max(0, min(1, (float) $x));
            $y = max(0, min(1, (float) $y));

            $normalizedPlayers[] = [
                'id' => $id,
                'x' => round($x, 4),
                'y' => round($y, 4),
            ];
        }

        if (empty($normalizedPlayers)) {
            return response()->json([
                'message' => 'Nenhum jogador válido foi encontrado no esquema.',
            ], 422);
        }

        $file = $request->file('imagem');
        $directory = 'esquemas/'.$liga->id.'/'.$userClub->id;
        $filename = 'esquema-'.now()->format('YmdHis').'-'.Str::random(6).'.png';
        $path = $file->storeAs($directory, $filename, 'public');
        if (! $path) {
            return response()->json([
                'message' => 'Não foi possível salvar a imagem do esquema.',
            ], 500);
        }

        if ($userClub->esquema_tatico_imagem) {
            Storage::disk('public')->delete($userClub->esquema_tatico_imagem);
        }

        $userClub->update([
            'esquema_tatico_layout' => [
                'players' => $normalizedPlayers,
            ],
            'esquema_tatico_imagem' => $path,
        ]);

        return response()->json([
            'message' => 'Esquema tático salvo com sucesso.',
            'image_url' => $this->resolveStorageUrl($path),
        ]);
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

        return Storage::disk('public')->url($path);
    }

    public function storeClube(Request $request): JsonResponse
    {
        $liga = $this->resolveUserLiga($request);
        $existingClub = $request->user()->clubesLiga()->where('liga_id', $liga->id)->first();

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'escudo_id' => ['nullable', 'integer', 'exists:escudos_clubes,id'],
        ]);

        $escudoId = $validated['escudo_id'] ?? null;
        if ($escudoId) {
            $escudoInUse = LigaClube::query()
                ->where('escudo_clube_id', $escudoId)
                ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $liga->confederacao_id))
                ->when($existingClub, fn ($query) => $query->where('id', '<>', $existingClub->id))
                ->exists();

            if ($escudoInUse) {
                return response()->json([
                    'message' => 'Este escudo já está em uso por outro clube nesta confederação.',
                ], 422);
            }
        }

        $escudo = $escudoId
            ? EscudoClube::query()->find($escudoId)
            : null;

        $clube = LigaClube::updateOrCreate(
            [
                'liga_id' => $liga->id,
                'user_id' => $request->user()->id,
            ],
            [
                'nome' => trim($validated['nome']),
                'escudo_clube_id' => $escudo?->id,
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
