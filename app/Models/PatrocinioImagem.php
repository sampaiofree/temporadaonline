<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrocinioImagem extends Model
{
    protected $table = 'patrocinio_imagem';

    protected $fillable = [
        'nome',
        'url',
    ];
}
