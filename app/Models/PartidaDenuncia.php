<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartidaDenuncia extends Model
{
    protected $fillable = [
        'partida_id',
        'user_id',
        'motivo',
        'descricao',
    ];
}
