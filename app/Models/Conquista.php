<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conquista extends Model
{
    public const TIPOS = [
        'gols' => 'Gols',
        'assistencias' => 'Assistencias',
        'quantidade_jogos' => 'Quantidade de jogos',
        'skill_rating' => 'Skill rating',
        'score' => 'Score',
        'n_gols_sofridos' => 'Gols sofridos',
        'n_vitorias' => 'Vitorias',
        'n_hat_trick' => 'Hat-trick',
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
