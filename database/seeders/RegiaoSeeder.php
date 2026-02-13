<?php

namespace Database\Seeders;

use App\Models\Regiao;
use Illuminate\Database\Seeder;

class RegiaoSeeder extends Seeder
{
    public function run(): void
    {
        $regioes = [
            ['nome' => 'Brasil', 'slug' => 'brasil'],
            ['nome' => 'América do Sul', 'slug' => 'america-do-sul'],
            ['nome' => 'América do Norte', 'slug' => 'america-do-norte'],
            ['nome' => 'Europa', 'slug' => 'europa'],
            ['nome' => 'Ásia', 'slug' => 'asia'],
            ['nome' => 'África', 'slug' => 'africa'],
            ['nome' => 'Oceania', 'slug' => 'oceania'],
        ];

        foreach ($regioes as $regiao) {
            Regiao::query()->updateOrCreate(
                ['slug' => $regiao['slug']],
                ['nome' => $regiao['nome']],
            );
        }
    }
}
