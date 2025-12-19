<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partida extends Model
{
    protected $fillable = [
        'liga_id',
        'mandante_id',
        'visitante_id',
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

    public function opcoes(): HasMany
    {
        return $this->hasMany(PartidaOpcaoHorario::class);
    }

    public function confirmacoes(): HasMany
    {
        return $this->hasMany(PartidaConfirmacao::class);
    }

    public function alteracoes(): HasMany
    {
        return $this->hasMany(PartidaAlteracao::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(PartidaEvento::class);
    }

    public function reclamacoes(): HasMany
    {
        return $this->hasMany(ReclamacaoPartida::class);
    }
}
