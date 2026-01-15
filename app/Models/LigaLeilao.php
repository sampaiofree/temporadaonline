<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaLeilao extends Model
{
    protected $table = 'liga_leiloes';

    protected $fillable = [
        'liga_id',
        'inicio',
        'fim',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fim' => 'date',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }
}
