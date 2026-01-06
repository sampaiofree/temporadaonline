<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaFolhaPagamento extends Model
{
    protected $table = 'partida_folha_pagamento';

    protected $fillable = [
        'liga_id',
        'partida_id',
        'clube_id',
        'total_wage',
        'multa_wo',
    ];

    protected $casts = [
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
