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
        'partidas_sem_levar_gol' => 'Partidas sem levar gol',
        'vitorias_por_3_gols_ou_mais_de_diferenca' => 'Vitorias por 3 ou mais gols de diferenca',
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
