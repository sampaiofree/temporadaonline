<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigaClubeAjusteSalarial extends Model
{
    protected $table = 'liga_clube_ajustes_salariais';

    protected $fillable = [
        'user_id',
        'confederacao_id',
        'liga_id',
        'liga_clube_id',
        'liga_clube_elenco_id',
        'wage_anterior',
        'wage_novo',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'confederacao_id' => 'integer',
        'liga_id' => 'integer',
        'liga_clube_id' => 'integer',
        'liga_clube_elenco_id' => 'integer',
        'wage_anterior' => 'integer',
        'wage_novo' => 'integer',
    ];
}
