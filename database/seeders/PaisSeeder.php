<?php

namespace Database\Seeders;

use App\Models\Pais;
use Illuminate\Database\Seeder;

class PaisSeeder extends Seeder
{
    public function run(): void
    {
        $paises = [
            ['nome' => 'Brasil', 'slug' => 'brasil', 'imagem' => 'https://placehold.co/128x128/png?text=BR'],
            ['nome' => 'Argentina', 'slug' => 'argentina', 'imagem' => 'https://placehold.co/128x128/png?text=AR'],
            ['nome' => 'Espanha', 'slug' => 'espanha', 'imagem' => 'https://placehold.co/128x128/png?text=ES'],
            ['nome' => 'Inglaterra', 'slug' => 'inglaterra', 'imagem' => 'https://placehold.co/128x128/png?text=EN'],
            ['nome' => 'França', 'slug' => 'franca', 'imagem' => 'https://placehold.co/128x128/png?text=FR'],
            ['nome' => 'Alemanha', 'slug' => 'alemanha', 'imagem' => 'https://placehold.co/128x128/png?text=DE'],
            ['nome' => 'Itália', 'slug' => 'italia', 'imagem' => 'https://placehold.co/128x128/png?text=IT'],
            ['nome' => 'Portugal', 'slug' => 'portugal', 'imagem' => 'https://placehold.co/128x128/png?text=PT'],
        ];

        foreach ($paises as $pais) {
            Pais::query()->updateOrCreate(
                ['slug' => $pais['slug']],
                [
                    'nome' => $pais['nome'],
                    'imagem' => $pais['imagem'],
                    'ativo' => true,
                ],
            );
        }
    }
}

