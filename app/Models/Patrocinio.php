<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patrocinio extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'imagem',
        'valor',
        'fans',
    ];

    protected $casts = [
        'valor' => 'integer',
        'fans' => 'integer',
    ];
}
