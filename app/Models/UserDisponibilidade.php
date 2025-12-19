<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDisponibilidade extends Model
{
    protected $table = 'user_disponibilidades';

    protected $fillable = [
        'user_id',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
