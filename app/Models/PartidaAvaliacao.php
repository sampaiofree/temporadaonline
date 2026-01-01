<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaAvaliacao extends Model
{
    protected $table = 'partida_avaliacoes';

    protected $fillable = [
        'partida_id',
        'avaliador_user_id',
        'avaliado_user_id',
        'nota',
    ];

    protected $casts = [
        'nota' => 'integer',
    ];

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function avaliador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avaliador_user_id');
    }

    public function avaliado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avaliado_user_id');
    }
}
