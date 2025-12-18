<?php

namespace Database\Seeders;

use App\Models\Liga;
use App\Models\Plataforma;
use App\Models\Jogo;
use App\Models\Geracao;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $platforms = [
            ['nome' => 'PlayStation 5', 'slug' => 'playstation-5'],
            ['nome' => 'PC', 'slug' => 'pc'],
            ['nome' => 'XBOX', 'slug' => 'xbox'],
        ];

        foreach ($platforms as $platform) {
            Plataforma::firstOrCreate(['slug' => $platform['slug']], $platform);
        }

        $games = [
            ['nome' => 'FC25', 'slug' => 'fc25'],
            ['nome' => 'FC26', 'slug' => 'fc26'],
            ['nome' => 'PES25', 'slug' => 'pes25'],
            ['nome' => 'PES26', 'slug' => 'pes26'],
        ];

        foreach ($games as $game) {
            Jogo::firstOrCreate(['slug' => $game['slug']], $game);
        }

        $geracoes = [
            ['nome' => 'Nova', 'slug' => 'nova'],
            ['nome' => 'Antiga', 'slug' => 'antiga'],
        ];

        foreach ($geracoes as $geracao) {
            Geracao::firstOrCreate(['slug' => $geracao['slug']], $geracao);
        }

        $this->call([
            LigaSeeder::class,
            AdminUserSeeder::class,
            DemoLigaUsersSeeder::class,
        ]);

        $user = User::where('email', 'sampaio.free@gmail.com')->with('profile')->first();
        $liga = Liga::firstWhere('nome', 'Liga Demo MCO');

        if ($user && $liga) {
            $user->ligas()->syncWithoutDetaching([$liga->id]);
        }

        $plataforma = Plataforma::firstWhere('slug', 'playstation-5');
        $jogo = Jogo::firstWhere('slug', 'fc26');
        $geracao = Geracao::firstWhere('slug', 'nova');

        if ($user && $user->profile) {
            $user->profile->fill([
                'plataforma_id' => $plataforma?->id,
                'plataforma' => $plataforma?->nome,
                'jogo_id' => $jogo?->id,
                'jogo' => $jogo?->nome,
                'geracao_id' => $geracao?->id,
                'geracao' => $geracao?->nome,
            ]);
            $user->profile->save();
        }
    }
}
