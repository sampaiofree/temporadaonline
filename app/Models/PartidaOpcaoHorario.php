<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaOpcaoHorario extends Model
{
    protected $table = 'partida_opcoes_horario';

    protected $fillable = [
        'partida_id',
        'datetime',
    ];

    protected $casts = [
        'datetime' => 'datetime',
    ];

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }
}
