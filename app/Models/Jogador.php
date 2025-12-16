<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Jogador extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Explicit table name to avoid default "jogadors" pluralization.
     */
    protected $table = 'jogadores';

    protected $fillable = [
        'nome',
        'email',
        'nickname',
        'avatar',
        'whatsapp',
        'plataforma',
        'geracao',
        'jogo',
        'regiao',
        'idioma',
        'reputacao_score',
        'nivel',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'reputacao_score' => 'integer',
        'nivel' => 'integer',
    ];
}
