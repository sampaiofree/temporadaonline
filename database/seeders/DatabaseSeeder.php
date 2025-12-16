<?php

namespace Database\Seeders;

use App\Models\Jogador;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Jogador::factory()->create([
            'nome' => 'Operador Demo',
            'nickname' => 'operador_demo',
            'email' => 'demo@example.com',
            'whatsapp' => '559999999999',
            'plataforma' => 'PlayStation',
            'geracao' => 'Gen 5',
            'jogo' => 'MCO FIFA',
            'reputacao_score' => 99,
            'nivel' => 0,
            'remember_token' => Str::random(10),
        ]);
    }
}
