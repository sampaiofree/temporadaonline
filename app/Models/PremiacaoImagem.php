<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiacaoImagem extends Model
{
    protected $table = 'premiacao_imagem';

    protected $fillable = [
        'nome',
        'url',
    ];
}
