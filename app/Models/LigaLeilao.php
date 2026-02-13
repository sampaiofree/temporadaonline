<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaLeilao extends Model
{
    protected $table = 'liga_leiloes';

    protected $fillable = [
        'confederacao_id',
        'inicio',
        'fim',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fim' => 'date',
    ];

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class, 'confederacao_id');
    }
}
