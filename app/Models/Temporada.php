<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Temporada extends Model
{
    protected $table = 'temporadas';

    protected $fillable = [
        'confederacao_id',
        'name',
        'descricao',
        'data_inicio',
        'data_fim',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class);
    }
}
