<?php

namespace Database\Seeders;

use App\Models\EscudoClube;
use App\Models\LigaEscudo;
use App\Models\Pais;
use Illuminate\Database\Seeder;

class EscudoClubeSeeder extends Seeder
{
    public function run(): void
    {
        $ligasComEscudos = [
            'brasil' => [
                'liga_nome' => 'Brasileirão',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=BRASILEIRAO',
                'clubes' => [
                    ['nome' => 'Flamengo', 'imagem' => 'https://placehold.co/256x256/png?text=FLA'],
                    ['nome' => 'Palmeiras', 'imagem' => 'https://placehold.co/256x256/png?text=PAL'],
                    ['nome' => 'Corinthians', 'imagem' => 'https://placehold.co/256x256/png?text=COR'],
                    ['nome' => 'Cruzeiro', 'imagem' => 'https://placehold.co/256x256/png?text=CRU'],
                ],
            ],
            'argentina' => [
                'liga_nome' => 'Liga Argentina',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=ARG',
                'clubes' => [
                    ['nome' => 'River Plate', 'imagem' => 'https://placehold.co/256x256/png?text=RIV'],
                    ['nome' => 'Boca Juniors', 'imagem' => 'https://placehold.co/256x256/png?text=BOC'],
                ],
            ],
            'espanha' => [
                'liga_nome' => 'La Liga',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=LALIGA',
                'clubes' => [
                    ['nome' => 'Real Madrid', 'imagem' => 'https://placehold.co/256x256/png?text=RMA'],
                    ['nome' => 'Barcelona', 'imagem' => 'https://placehold.co/256x256/png?text=BAR'],
                ],
            ],
            'inglaterra' => [
                'liga_nome' => 'Premier League',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=EPL',
                'clubes' => [
                    ['nome' => 'Manchester City', 'imagem' => 'https://placehold.co/256x256/png?text=MCI'],
                    ['nome' => 'Liverpool', 'imagem' => 'https://placehold.co/256x256/png?text=LIV'],
                ],
            ],
            'franca' => [
                'liga_nome' => 'Ligue 1',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=L1',
                'clubes' => [
                    ['nome' => 'PSG', 'imagem' => 'https://placehold.co/256x256/png?text=PSG'],
                ],
            ],
            'alemanha' => [
                'liga_nome' => 'Bundesliga',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=BUNDES',
                'clubes' => [
                    ['nome' => 'Bayern München', 'imagem' => 'https://placehold.co/256x256/png?text=BAY'],
                ],
            ],
            'italia' => [
                'liga_nome' => 'Serie A',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=SERIE+A',
                'clubes' => [
                    ['nome' => 'Inter de Milão', 'imagem' => 'https://placehold.co/256x256/png?text=INT'],
                    ['nome' => 'Juventus', 'imagem' => 'https://placehold.co/256x256/png?text=JUV'],
                ],
            ],
            'portugal' => [
                'liga_nome' => 'Liga Portugal',
                'liga_imagem' => 'https://placehold.co/256x256/png?text=PT',
                'clubes' => [
                    ['nome' => 'Benfica', 'imagem' => 'https://placehold.co/256x256/png?text=BEN'],
                    ['nome' => 'Porto', 'imagem' => 'https://placehold.co/256x256/png?text=POR'],
                ],
            ],
        ];

        foreach ($ligasComEscudos as $paisSlug => $data) {
            $pais = Pais::query()->where('slug', $paisSlug)->first();

            if (! $pais) {
                continue;
            }

            $ligaEscudo = LigaEscudo::query()->updateOrCreate(
                [
                    'pais_id' => $pais->id,
                    'liga_nome' => $data['liga_nome'],
                ],
                [
                    'liga_imagem' => $data['liga_imagem'],
                ],
            );

            foreach ($data['clubes'] as $clube) {
                EscudoClube::query()->updateOrCreate(
                    [
                        'liga_id' => $ligaEscudo->id,
                        'clube_nome' => $clube['nome'],
                    ],
                    [
                        'pais_id' => $pais->id,
                        'clube_imagem' => $clube['imagem'],
                    ],
                );
            }
        }
    }
}

