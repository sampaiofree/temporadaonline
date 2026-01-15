<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premiacao extends Model
{
    protected $table = 'premiacoes';

    protected $fillable = [
        'posicao',
        'imagem',
        'premiacao',
    ];

    protected $casts = [
        'posicao' => 'integer',
        'premiacao' => 'integer',
    ];
}
