<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaClubeFinanceiro extends Model
{
    protected $table = 'liga_clube_financeiro';

    protected $fillable = [
        'liga_id',
        'clube_id',
        'saldo',
    ];

    protected $casts = [
        'saldo' => 'integer',
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

