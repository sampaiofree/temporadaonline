<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaAlteracao extends Model
{
    protected $table = 'partida_alteracoes';

    protected $fillable = [
        'partida_id',
        'user_id',
        'old_datetime',
        'new_datetime',
    ];

    protected $casts = [
        'old_datetime' => 'datetime',
        'new_datetime' => 'datetime',
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
