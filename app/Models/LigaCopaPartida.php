<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaCopaPartida extends Model
{
    protected $table = 'liga_copa_partidas';

    protected $fillable = [
        'partida_id',
        'fase_id',
        'grupo_id',
        'key_slot',
        'perna',
    ];

    protected $casts = [
        'perna' => 'integer',
    ];

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function fase(): BelongsTo
    {
        return $this->belongsTo(LigaCopaFase::class, 'fase_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(LigaCopaGrupo::class, 'grupo_id');
    }
}
