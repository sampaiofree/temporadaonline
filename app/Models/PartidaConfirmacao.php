<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaConfirmacao extends Model
{
    protected $table = 'partida_confirmacoes';

    protected $fillable = [
        'partida_id',
        'user_id',
        'datetime',
    ];

    protected $casts = [
        'datetime' => 'datetime',
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
