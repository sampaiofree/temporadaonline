<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Partida extends Model
{
    public const COMPETITION_LEAGUE = 'liga';
    public const COMPETITION_CUP = 'copa';

    private static ?bool $competitionSchemaReadyCache = null;

    protected $fillable = [
        'liga_id',
        'mandante_id',
        'visitante_id',
        'competition_type',
        'scheduled_at',
        'estado',
        'alteracoes_usadas',
        'forced_by_system',
        'sem_slot_disponivel',
        'wo_para_user_id',
        'wo_motivo',
        'placar_mandante',
        'placar_visitante',
        'placar_registrado_por',
        'placar_registrado_em',
        'checkin_mandante_at',
        'checkin_visitante_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'forced_by_system' => 'boolean',
        'sem_slot_disponivel' => 'boolean',
        'checkin_mandante_at' => 'datetime',
        'checkin_visitante_at' => 'datetime',
        'placar_registrado_em' => 'datetime',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'mandante_id');
    }

    public function visitante(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'visitante_id');
    }

    public function woParaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wo_para_user_id');
    }

    public function placarRegistradoPorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placar_registrado_por');
    }

    public function alteracoes(): HasMany
    {
        return $this->hasMany(PartidaAlteracao::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(PartidaEvento::class);
    }

    public function desempenhos(): HasMany
    {
        return $this->hasMany(PartidaDesempenho::class);
    }

    public function reclamacoes(): HasMany
    {
        return $this->hasMany(ReclamacaoPartida::class);
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(PartidaAvaliacao::class);
    }

    public function cupMeta(): HasOne
    {
        return $this->hasOne(LigaCopaPartida::class, 'partida_id');
    }

    public static function competitionSchemaReady(): bool
    {
        if (self::$competitionSchemaReadyCache !== null) {
            return self::$competitionSchemaReadyCache;
        }

        self::$competitionSchemaReadyCache = Schema::hasTable('partidas')
            && Schema::hasColumn('partidas', 'competition_type');

        return self::$competitionSchemaReadyCache;
    }

    public function scopeLeagueCompetition($query)
    {
        if (! self::competitionSchemaReady()) {
            return $query;
        }

        return $query->where('competition_type', self::COMPETITION_LEAGUE);
    }

    public function scopeCupCompetition($query)
    {
        if (! self::competitionSchemaReady()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('competition_type', self::COMPETITION_CUP);
    }

    public function isCupCompetition(): bool
    {
        return (string) $this->competition_type === self::COMPETITION_CUP;
    }

    public function isLeagueCompetition(): bool
    {
        return (string) ($this->competition_type ?: self::COMPETITION_LEAGUE) === self::COMPETITION_LEAGUE;
    }
}
