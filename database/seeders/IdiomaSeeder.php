<?php

namespace Database\Seeders;

use App\Models\Idioma;
use Illuminate\Database\Seeder;

class IdiomaSeeder extends Seeder
{
    public function run(): void
    {
        $idiomas = [
            ['nome' => 'Português (Brasil)', 'slug' => 'pt-br'],
            ['nome' => 'Português (Portugal)', 'slug' => 'pt-pt'],
            ['nome' => 'Español', 'slug' => 'es'],
            ['nome' => 'English', 'slug' => 'en'],
            ['nome' => 'Français', 'slug' => 'fr'],
        ];

        foreach ($idiomas as $idioma) {
            Idioma::query()->updateOrCreate(
                ['slug' => $idioma['slug']],
                ['nome' => $idioma['nome']],
            );
        }
    }
}
