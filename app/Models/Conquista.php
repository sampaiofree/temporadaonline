<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conquista extends Model
{
    public const TIPOS = [
        'gols' => 'Gols',
        'assistencias' => 'Assistencias',
        'quantidade_jogos' => 'Quantidade de jogos',
    ];

    protected $fillable = [
        'nome',
        'descricao',
        'imagem',
        'tipo',
        'quantidade',
        'fans',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'fans' => 'integer',
    ];
}
