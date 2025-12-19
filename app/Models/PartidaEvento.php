<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaEvento extends Model
{
    protected $table = 'partida_eventos';

    protected $fillable = [
        'partida_id',
        'tipo',
        'user_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
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
