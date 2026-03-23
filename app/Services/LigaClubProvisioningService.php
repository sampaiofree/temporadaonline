<?php

namespace App\Services;

use App\Models\Elencopadrao;
use App\Models\EscudoClube;
use App\Models\Idioma;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\Partida;
use App\Models\Profile;
use App\Models\Regiao;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LigaClubProvisioningService
{
    public const INITIAL_ROSTER_SIZE = 18;

    /**
     * @var array<string, int>
     */
    private const INITIAL_ROSTER_SLOTS = [
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

    public function __construct(
        private readonly LeagueFinanceService $leagueFinanceService,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array{clube:LigaClube,wallet:mixed,initialAdded:int,generatedMatches:int}
     */
    public function provision(Liga $liga, User $user, array $attributes): array
    {
        $clubName = trim((string) ($attributes['nome'] ?? ''));

        if ($clubName === '') {
            throw new DomainException('Nome do clube é obrigatório.');
        }

        $escudoId = isset($attributes['escudo_id']) && $attributes['escudo_id'] !== null
            ? (int) $attributes['escudo_id']
            : null;

        return DB::transaction(function () use ($liga, $user, $clubName, $escudoId, $attributes): array {
            $lockedLiga = Liga::query()->lockForUpdate()->findOrFail($liga->id);
            $lockedUser = User::query()->with('profile')->lockForUpdate()->findOrFail($user->id);
            $existingClub = LigaClube::query()
                ->where('liga_id', $lockedLiga->id)
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->first();

            $lockedUser->ligas()->syncWithoutDetaching([$lockedLiga->id]);
            $this->ensureCompatibleProfile($lockedLiga, $lockedUser, $attributes, $clubName);

            $escudo = $this->resolveEscudo($lockedLiga, $escudoId, $existingClub?->id);

            $clube = LigaClube::query()->updateOrCreate(
                [
                    'liga_id' => $lockedLiga->id,
                    'user_id' => $lockedUser->id,
                ],
                [
                    'nome' => $clubName,
                    'escudo_clube_id' => $escudo?->id,
                    'confederacao_id' => $lockedLiga->confederacao_id,
                ],
            );

            $wallet = $this->leagueFinanceService->initClubWallet($lockedLiga->id, $clube->id);

            $initialAdded = 0;
            $generatedMatches = 0;

            if ($clube->wasRecentlyCreated) {
                $initialAdded = $this->seedInitialRoster($lockedLiga, $clube);
                $generatedMatches = $this->countClubMatches($lockedLiga, $clube);
            }

            return [
                'clube' => $clube,
                'wallet' => $wallet,
                'initialAdded' => $initialAdded,
                'generatedMatches' => $generatedMatches,
            ];
        }, 3);
    }

    public function countAvailablePlayersForLiga(Liga $liga, array $excludedIds = []): int
    {
        [$scopeColumn, $scopeValue] = $this->resolveRosterScope($liga);

        return (int) $this->availablePlayersBaseQuery($liga, $excludedIds, $scopeColumn, $scopeValue)->count();
    }

    private function resolveEscudo(Liga $liga, ?int $escudoId, ?int $existingClubId): ?EscudoClube
    {
        if (! $escudoId) {
            return null;
        }

        $escudoInUse = LigaClube::query()
            ->where('escudo_clube_id', $escudoId)
            ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $liga->confederacao_id))
            ->when($existingClubId, fn ($query) => $query->where('id', '<>', $existingClubId))
            ->exists();

        if ($escudoInUse) {
            throw new DomainException('Este escudo já está em uso por outro clube nesta confederação.');
        }

        return EscudoClube::query()->findOrFail($escudoId);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function ensureCompatibleProfile(Liga $liga, User $user, array $attributes, string $clubName): void
    {
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        $desiredNickname = array_key_exists('nickname', $attributes)
            ? trim((string) $attributes['nickname'])
            : null;

        if ($desiredNickname !== null && $desiredNickname !== '') {
            $profile->nickname = $this->resolveUniqueNickname($desiredNickname, $profile->id);
        } elseif (! filled($profile->nickname)) {
            $profile->nickname = $this->resolveUniqueNickname(
                $this->buildProfileNicknameBase($user, $clubName),
                $profile->id,
            );
        }

        if (array_key_exists('whatsapp', $attributes)) {
            $profile->whatsapp = preg_replace('/\D+/', '', (string) ($attributes['whatsapp'] ?? '')) ?: $profile->whatsapp;
        }

        if (! filled($profile->whatsapp)) {
            $profile->whatsapp = $this->defaultWhatsappForUser($user);
        }

        if ($liga->plataforma_id) {
            $profile->plataforma_id = (int) $liga->plataforma_id;
        }

        if ($liga->jogo_id) {
            $profile->jogo_id = (int) $liga->jogo_id;
        }

        if ($liga->geracao_id) {
            $profile->geracao_id = (int) $liga->geracao_id;
        }

        $regiao = $profile->regiao_id
            ? Regiao::query()->find($profile->regiao_id)
            : $this->resolveDefaultRegiao();

        $idioma = $profile->idioma_id
            ? Idioma::query()->find($profile->idioma_id)
            : $this->resolveDefaultIdioma();

        if ($regiao) {
            $profile->regiao_id = (int) $regiao->id;
            $profile->regiao = $regiao->nome;
        }

        if ($idioma) {
            $profile->idioma_id = (int) $idioma->id;
            $profile->idioma = $idioma->nome;
        }

        $profile->save();

        $desiredUserName = trim((string) ($attributes['user_name'] ?? ''));
        if ($desiredUserName !== '' && $user->name !== $desiredUserName) {
            $user->forceFill(['name' => $desiredUserName])->save();
            return;
        }

        if (! filled($user->name)) {
            $user->forceFill(['name' => $profile->nickname ?: $clubName])->save();
        }
    }

    private function resolveUniqueNickname(string $base, ?int $ignoreProfileId = null): string
    {
        $normalizedBase = preg_replace('/\s+/', '', trim($base)) ?: 'Jogador';
        $normalizedBase = Str::limit($normalizedBase, 40, '');
        $candidate = $normalizedBase;
        $suffix = 1;

        while (
            Profile::query()
                ->when($ignoreProfileId, fn ($query) => $query->where('id', '<>', $ignoreProfileId))
                ->where('nickname', $candidate)
                ->exists()
        ) {
            $suffix++;
            $suffixLabel = str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
            $candidate = Str::limit($normalizedBase, max(1, 40 - strlen($suffixLabel)), '').$suffixLabel;
        }

        return $candidate;
    }

    private function buildProfileNicknameBase(User $user, string $clubName): string
    {
        $fromName = trim((string) $user->name);
        if ($fromName !== '') {
            return $fromName;
        }

        $emailPrefix = trim(Str::before((string) $user->email, '@'));
        if ($emailPrefix !== '') {
            return $emailPrefix;
        }

        return $clubName;
    }

    private function defaultWhatsappForUser(User $user): string
    {
        return '1199'.str_pad((string) ((int) $user->id % 10_000_000), 7, '0', STR_PAD_LEFT);
    }

    private function resolveDefaultRegiao(): ?Regiao
    {
        return Regiao::query()->orderBy('id')->first()
            ?? Regiao::query()->create([
                'nome' => 'Brasil',
                'slug' => 'brasil',
            ]);
    }

    private function resolveDefaultIdioma(): ?Idioma
    {
        return Idioma::query()->orderBy('id')->first()
            ?? Idioma::query()->create([
                'nome' => 'Português',
                'slug' => 'pt-br',
            ]);
    }

    private function countClubMatches(Liga $liga, LigaClube $clube): int
    {
        return (int) Partida::query()
            ->where('liga_id', $liga->id)
            ->where(function ($query) use ($clube): void {
                $query->where('mandante_id', $clube->id)
                    ->orWhere('visitante_id', $clube->id);
            })
            ->count();
    }

    private function seedInitialRoster(Liga $liga, LigaClube $clube): int
    {
        [$scopeColumn, $scopeValue] = $this->resolveRosterScope($liga);

        $selected = [];
        $added = 0;

        foreach (self::INITIAL_ROSTER_SLOTS as $position => $quantity) {
            for ($index = 0; $index < $quantity; $index++) {
                $player = $this->findAvailablePlayer($liga, $position, $selected, $scopeColumn, $scopeValue, true)
                    ?? $this->findAvailablePlayer($liga, $position, $selected, $scopeColumn, $scopeValue, false)
                    ?? $this->findAnyAvailablePlayer($liga, $selected, $scopeColumn, $scopeValue);

                if (! $player) {
                    continue;
                }

                LigaClubeElenco::query()->create([
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

    /**
     * @return array{0:string,1:int}
     */
    private function resolveRosterScope(Liga $liga): array
    {
        if ($liga->confederacao_id) {
            return ['confederacao_id', (int) $liga->confederacao_id];
        }

        return ['liga_id', (int) $liga->id];
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
            ->select(['id', 'value_eur', 'wage_eur', 'overall', 'player_positions'])
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
