<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pais extends Model
{
    use SoftDeletes;

    protected $table = 'paises';

    protected $fillable = [
        'nome',
        'slug',
        'imagem',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];
}
