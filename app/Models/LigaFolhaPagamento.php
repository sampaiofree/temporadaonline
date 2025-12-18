<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaFolhaPagamento extends Model
{
    protected $table = 'liga_folha_pagamento';

    protected $fillable = [
        'liga_id',
        'rodada',
        'clube_id',
        'total_wage',
    ];

    protected $casts = [
        'rodada' => 'integer',
        'total_wage' => 'integer',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function clube(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_id');
    }
}

