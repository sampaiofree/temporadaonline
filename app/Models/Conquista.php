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
        'agendar_partidas' => 'Agendar partidas',
        'enviar_sumula' => 'Enviar sumula',
        'avaliacoes' => 'Avaliacoes',
        'ajuste_salarial' => 'Ajuste salarial',
        'venda_mercado' => 'Venda de jogadores no mercado',
        'compra_mercado' => 'Compra de jogadores no mercado',
        'negociacoes_enviadas' => 'Negociacoes enviadas',
        'negociacoes_recebidas' => 'Negociacoes recebidas',
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
