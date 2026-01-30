<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConquistaImagem extends Model
{
    protected $table = 'conquista_imagem';

    protected $fillable = [
        'nome',
        'url',
    ];
}
