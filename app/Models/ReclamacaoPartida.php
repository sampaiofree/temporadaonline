<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamacaoPartida extends Model
{
    protected $table = 'reclamacoes_partida';

    protected $fillable = [
        'partida_id',
        'user_id',
        'motivo',
        'descricao',
        'imagem',
        'status',
    ];

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
