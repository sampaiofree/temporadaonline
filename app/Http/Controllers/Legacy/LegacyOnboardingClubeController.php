<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Concerns\ChecksProfileCompletion;
use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\EscudoClube;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaEscudo;
use App\Models\Pais;
use App\Models\User;
use App\Services\LeagueFinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LegacyOnboardingClubeController extends Controller
{
    use ResolvesLiga;
    use ChecksProfileCompletion;

    public function show(Request $request): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $ligaId = $request->query('liga_id');

        if (! $ligaId) {
            return view('legacy.onboarding_clube_select', [
                'selectorData' => $this->buildSelectorData($user),
            ]);
        }

        return view('legacy.onboarding_clube', $this->buildClubEditorData($request));
    }

    public function selectLiga(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'confederacao_id' => ['required', 'integer', 'exists:confederacoes,id'],
            'liga_id' => ['required', 'integer', 'exists:ligas,id'],
        ]);

        $liga = Liga::query()
            ->where('id', $validated['liga_id'])
            ->where('confederacao_id', $validated['confederacao_id'])
            ->with(['jogo', 'geracao'])
            ->firstOrFail();

        if (! $this->hasCompleteProfile($user->profile)) {
            return response()->json([
                'message' => 'Complete seu primeiro acesso antes de escolher uma liga.',
                'redirect' => route('legacy.primeiro_acesso'),
            ], 422);
        }

        if ((int) $user->profile?->jogo_id !== (int) $liga->jogo_id || (int) $user->profile?->geracao_id !== (int) $liga->geracao_id) {
            return response()->json([
                'message' => 'Seu perfil está incompatível com esta liga (jogo/geração).',
            ], 422);
        }

        $user->ligas()->syncWithoutDetaching([$liga->id]);

        return response()->json([
            'message' => 'Liga selecionada com sucesso.',
            'redirect' => route('legacy.onboarding_clube', ['liga_id' => $liga->id]),
        ]);
    }

    public function storeClube(Request $request): JsonResponse
    {
        $liga = $this->resolveUserLiga($request);
        $existingClub = $request->user()->clubesLiga()->where('liga_id', $liga->id)->first();

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'escudo_id' => ['nullable', 'integer', 'exists:escudos_clubes,id'],
        ]);

        $result = DB::transaction(function () use ($validated, $existingClub, $liga, $request) {
            $escudoId = $validated['escudo_id'] ?? null;
            if ($escudoId) {
                $escudoInUse = LigaClube::query()
                    ->where('escudo_clube_id', $escudoId)
                    ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $liga->confederacao_id))
                    ->when($existingClub, fn ($query) => $query->where('id', '<>', $existingClub->id))
                    ->exists();

                if ($escudoInUse) {
                    return [
                        'error' => 'Este escudo já está em uso por outro clube nesta confederação.',
                    ];
                }
            }

            $escudo = $escudoId ? EscudoClube::query()->find($escudoId) : null;

            $clube = LigaClube::updateOrCreate(
                [
                    'liga_id' => $liga->id,
                    'user_id' => $request->user()->id,
                ],
                [
                    'nome' => trim($validated['nome']),
                    'escudo_clube_id' => $escudo?->id,
                    'confederacao_id' => $liga->confederacao_id,
                ],
            );

            $wallet = app(LeagueFinanceService::class)->initClubWallet($liga->id, $clube->id);

            $initialAdded = 0;
            if ($clube->wasRecentlyCreated) {
                $initialAdded = $this->seedInitialRoster($liga, $clube);
            }

            return [
                'clube' => $clube,
                'wallet' => $wallet,
                'initialAdded' => $initialAdded,
            ];
        });

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], 422);
        }

        $clube = $result['clube'];
        $wallet = $result['wallet'];
        $initialAdded = (int) ($result['initialAdded'] ?? 0);

        return response()->json([
            'message' => $clube->wasRecentlyCreated
                ? 'Clube criado com sucesso.'
                : 'Nome do clube foi alterado com sucesso.',
            'clube' => $clube,
            'financeiro' => [
                'saldo' => (int) $wallet->saldo,
            ],
            'initial_roster_added' => $initialAdded > 0,
            'initial_roster_message' => $initialAdded > 0 ? "{$initialAdded} jogadores iniciais adicionados automaticamente." : null,
            'initial_roster_count' => $initialAdded > 0 ? $initialAdded : null,
            'initial_roster_cta' => route('legacy.index'),
        ], 201);
    }

    private function buildSelectorData(User $user): array
    {
        $registeredLeagueIds = $user->ligas()->pluck('ligas.id')->map(fn ($id) => (int) $id)->all();

        $confederacoes = Confederacao::query()
            ->with(['ligas' => function ($query) {
                $query->with(['jogo:id,nome', 'geracao:id,nome', 'plataforma:id,nome'])
                    ->orderBy('nome');
            }])
            ->orderBy('nome')
            ->get(['id', 'nome', 'descricao', 'imagem'])
            ->map(function (Confederacao $confederacao) use ($registeredLeagueIds) {
                return [
                    'id' => $confederacao->id,
                    'nome' => $confederacao->nome,
                    'descricao' => $confederacao->descricao,
                    'imagem' => $confederacao->imagem,
                    'ligas' => $confederacao->ligas->map(function (Liga $liga) use ($registeredLeagueIds) {
                        return [
                            'id' => $liga->id,
                            'nome' => $liga->nome,
                            'status' => $liga->status,
                            'tipo' => $liga->tipo,
                            'jogo' => $liga->jogo?->nome,
                            'geracao' => $liga->geracao?->nome,
                            'plataforma' => $liga->plataforma?->nome,
                            'registered' => in_array((int) $liga->id, $registeredLeagueIds, true),
                        ];
                    })->values()->all(),
                ];
            })
            ->filter(fn (array $confederacao) => count($confederacao['ligas']) > 0)
            ->values()
            ->all();

        return [
            'confederacoes' => $confederacoes,
            'endpoints' => [
                'select_liga_url' => route('legacy.onboarding_clube.select_liga'),
                'onboarding_base_url' => route('legacy.onboarding_clube'),
                'cancel_url' => route('legacy.index'),
            ],
        ];
    }

    private function buildClubEditorData(Request $request): array
    {
        $liga = $this->resolveUserLiga($request);
        $liga->loadMissing('confederacao');

        $userClub = $request->user()->clubesLiga()
            ->where('liga_id', $liga->id)
            ->with('escudo')
            ->first();

        $elencoCount = null;
        $saldo = null;

        $filters = [
            'search' => $request->query('search', ''),
            'escudo_pais_id' => $request->query('escudo_pais_id', ''),
            'escudo_liga_id' => $request->query('escudo_liga_id', ''),
            'only_available' => $request->boolean('only_available'),
        ];

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

        $usedEscudos = LigaClube::query()
            ->whereNotNull('escudo_clube_id')
            ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $liga->confederacao_id))
            ->pluck('escudo_clube_id')
            ->values();

        $selectedEscudoId = $userClub?->escudo_clube_id;
        $usedEscudosForFilter = $usedEscudos->when(
            $selectedEscudoId,
            fn ($escudos) => $escudos->reject(fn ($id) => (int) $id === (int) $selectedEscudoId),
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

        return [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'imagem' => $liga->imagem,
                'jogo' => $liga->jogo?->nome,
                'geracao' => $liga->geracao?->nome,
                'plataforma' => $liga->plataforma?->nome,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'confederacao_nome' => $liga->confederacao?->nome,
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
                'elenco_count' => $elencoCount ?? 0,
                'saldo' => $saldo,
            ] : null,
            'escudos' => $escudos,
            'paises' => $paises,
            'ligasEscudos' => $ligasEscudos,
            'usedEscudos' => $usedEscudos,
            'filters' => $filters,
            'routes' => [
                'onboarding_base_path' => '/legacy/onboarding-clube',
                'store_clube_url' => route('legacy.onboarding_clube.store'),
                'home_url' => route('legacy.index'),
                'meu_clube_url' => route('legacy.index'),
                'meu_elenco_url' => route('legacy.index'),
                'step_mode' => 'club_only',
                'select_universe_url' => route('legacy.onboarding_clube'),
                'show_navbar' => false,
            ],
        ];
    }

    private function seedInitialRoster(Liga $liga, LigaClube $clube): int
    {
        $slots = [
            'GK' => 1,
            'RB' => 1,
            'LB' => 1,
            'CB' => 3,
            'CDM' => 2,
            'CM' => 2,
            'CAM' => 2,
            'ST' => 2,
            'LW' => 1,
            'RW' => 1,
            'LM' => 1,
            'RM' => 1,
        ];

        [$scopeColumn, $scopeValue] = $this->resolveRosterScope($liga);

        $selected = [];
        $added = 0;

        foreach ($slots as $position => $quantity) {
            for ($i = 0; $i < $quantity; $i++) {
                $player = $this->findAvailablePlayer($liga, $position, $selected, $scopeColumn, $scopeValue, true)
                    ?? $this->findAvailablePlayer($liga, $position, $selected, $scopeColumn, $scopeValue, false)
                    ?? $this->findAnyAvailablePlayer($liga, $selected, $scopeColumn, $scopeValue);

                if (! $player) {
                    continue;
                }

                LigaClubeElenco::create([
                    'confederacao_id' => $liga->confederacao_id,
                    'liga_id' => $liga->id,
                    'liga_clube_id' => $clube->id,
                    'elencopadrao_id' => $player->id,
                    'value_eur' => $player->value_eur,
                    'wage_eur' => $player->wage_eur,
                    'ativo' => true,
                ]);

                $selected[] = $player->id;
                $added++;
            }
        }

        return $added;
    }

    private function resolveRosterScope(Liga $liga): array
    {
        if ($liga->confederacao_id) {
            return ['confederacao_id', $liga->confederacao_id];
        }

        return ['liga_id', $liga->id];
    }

    private function findAvailablePlayer(
        Liga $liga,
        string $position,
        array $excludedIds,
        string $scopeColumn,
        int $scopeValue,
        bool $preferUnder80,
    ): ?Elencopadrao {
        $query = $this->availablePlayersBaseQuery($liga, $excludedIds, $scopeColumn, $scopeValue);

        if (DB::connection()->getDriverName() === 'pgsql') {
            $query->where('player_positions', 'ILIKE', '%'.$position.'%');
        } else {
            $query->whereRaw('LOWER(player_positions) LIKE ?', ['%'.Str::lower($position).'%']);
        }

        if ($preferUnder80) {
            $query->where('overall', '<', 80)->orderByRaw('RANDOM()');
        } else {
            $query->orderBy('overall')->orderBy('id');
        }

        return $query->first();
    }

    private function findAnyAvailablePlayer(Liga $liga, array $excludedIds, string $scopeColumn, int $scopeValue): ?Elencopadrao
    {
        return $this->availablePlayersBaseQuery($liga, $excludedIds, $scopeColumn, $scopeValue)
            ->orderBy('overall')
            ->orderBy('id')
            ->first();
    }

    private function availablePlayersBaseQuery(Liga $liga, array $excludedIds, string $scopeColumn, int $scopeValue)
    {
        return Elencopadrao::query()
            ->select(['id', 'value_eur', 'wage_eur'])
            ->where('jogo_id', $liga->jogo_id)
            ->when($excludedIds, fn ($query) => $query->whereNotIn('id', $excludedIds))
            ->whereNotExists(function ($query) use ($scopeColumn, $scopeValue) {
                $query->select(DB::raw(1))
                    ->from('liga_clube_elencos as lce')
                    ->whereColumn('lce.elencopadrao_id', 'elencopadrao.id')
                    ->where($scopeColumn, $scopeValue);
            });
    }
}
