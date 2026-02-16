<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubeTamanho extends Model
{
    protected $table = 'clube_tamanho';

    protected $fillable = [
        'nome',
        'descricao',
        'imagem',
        'n_fans',
    ];

    protected $casts = [
        'n_fans' => 'integer',
    ];
}
