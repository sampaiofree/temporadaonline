<?php

namespace Database\Seeders;

use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\Plataforma;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LigaSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $jogo = Jogo::firstWhere('slug', 'fc26');
        $geracao = Geracao::firstWhere('slug', 'nova');
        $plataforma = Plataforma::firstWhere('slug', 'playstation-5');

        if (!$jogo || !$geracao || !$plataforma) {
            return;
        }

        Liga::updateOrCreate([
            'nome' => 'Liga Demo MCO',
        ], [
            'descricao' => 'Uma liga de demonstração para mostrar o funcionamento do sistema.',
            'regras' => 'Respeito, fair play e resultados reportados em até 24 horas.',
            'imagem' => 'https://example.com/assets/ligas/ligamco.jpg',
            'saldo_inicial' => 100000000,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 20,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);
    }
}
