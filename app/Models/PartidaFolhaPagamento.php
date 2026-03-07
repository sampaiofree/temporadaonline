<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaFolhaPagamento extends Model
{
    protected $table = 'partida_folha_pagamento';

    public const TYPE_LEGACY_SALARY_DEBIT = 'debito_salario_legacy';
    public const TYPE_MATCH_WIN_REWARD = 'ganho_partida_vitoria';
    public const TYPE_MATCH_DRAW_REWARD = 'ganho_partida_empate';
    public const TYPE_MATCH_LOSS_REWARD = 'ganho_partida_derrota';

    protected $fillable = [
        'liga_id',
        'partida_id',
        'clube_id',
        'tipo',
        'total_wage',
        'multa_wo',
    ];

    protected $casts = [
        'tipo' => 'string',
        'total_wage' => 'integer',
        'multa_wo' => 'integer',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function clube(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_id');
    }
}
