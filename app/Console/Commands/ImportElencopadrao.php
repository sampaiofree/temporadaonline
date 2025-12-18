<?php

namespace App\Console\Commands;

use App\Models\Elencopadrao;
use App\Models\Jogo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use SplFileObject;

class ImportElencopadrao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elenco:import {file} {slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileName = $this->argument('file');
        $slug = $this->argument('slug');

        $path = storage_path("app/private/elenco/{$fileName}");

        if (! file_exists($path)) {
            $this->error("Arquivo não encontrado: {$path}");
            return 1;
        }

        $jogo = Jogo::where('slug', $slug)->first();

        if (! $jogo) {
            $this->error("Jogo não encontrado para slug {$slug}");
            return 1;
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(';');
        $headers = $file->fgetcsv();

        if (! $headers) {
            $this->error('Não foi possível ler o cabeçalho do CSV.');
            return 1;
        }

        $headers = array_map(fn ($item) => trim($item), $headers);

        $imported = 0;
        $skipped = 0;

        while (! $file->eof()) {
            $row = $file->fgetcsv();
            if (! $row || $row === [null]) {
                continue;
            }

            if (count($row) !== count($headers)) {
                $skipped++;
                continue;
            }

            $data = $this->mapRow($headers, $row);
            if (empty($data['long_name'])) {
                $skipped++;
                continue;
            }

            $data['jogo_id'] = $jogo->id;

            Elencopadrao::updateOrCreate(
                [
                    'jogo_id' => $jogo->id,
                    'long_name' => $data['long_name'],
                ],
                $data,
            );

            $imported++;
        }

        $this->info("Import completed: {$imported} records, {$skipped} skipped.");

        return 0;
    }

    private function mapRow(array $headers, array $row): array
    {
        $data = [];

        $intFields = [
            'overall',
            'potential',
            'value_eur',
            'wage_eur',
            'age',
            'height_cm',
            'weight_kg',
            'league_level',
            'club_jersey_number',
            'club_contract_valid_until_year',
            'nationality_id',
            'nation_team_id',
            'nation_jersey_number',
            'weak_foot',
            'skill_moves',
            'international_reputation',
            'pace',
            'shooting',
            'passing',
            'dribbling',
            'defending',
            'physic',
            'attacking_crossing',
            'attacking_finishing',
            'attacking_heading_accuracy',
            'attacking_short_passing',
            'attacking_volleys',
            'skill_dribbling',
            'skill_curve',
            'skill_fk_accuracy',
            'skill_long_passing',
            'skill_ball_control',
            'movement_acceleration',
            'movement_sprint_speed',
            'movement_agility',
            'movement_reactions',
            'movement_balance',
            'power_shot_power',
            'power_jumping',
            'power_stamina',
            'power_strength',
            'power_long_shots',
            'mentality_aggression',
            'mentality_interceptions',
            'mentality_positioning',
            'mentality_vision',
            'mentality_penalties',
            'mentality_composure',
            'defending_marking_awareness',
            'defending_standing_tackle',
            'defending_sliding_tackle',
            'goalkeeping_diving',
            'goalkeeping_handling',
            'goalkeeping_kicking',
            'goalkeeping_positioning',
            'goalkeeping_reflexes',
            'goalkeeping_speed',
        ];

        $intFields = array_merge($intFields, [
            'value_eur',
            'wage_eur',
            'release_clause_eur',
        ]);

        $dateFields = [
            'dob',
            'club_joined_date',
        ];

        $boolFields = [
            'real_face',
        ];

        foreach ($headers as $index => $header) {
            $value = trim($row[$index] ?? '');
            if ($value === '') {
                $data[$header] = null;
                continue;
            }

            if (in_array($header, $intFields, true)) {
                $data[$header] = (int) preg_replace('/[^0-9]/', '', $value);
                continue;
            }

            if (in_array($header, $dateFields, true)) {
                $data[$header] = $this->parseDate($value);
                continue;
            }

            if (in_array($header, $boolFields, true)) {
                $data[$header] = strcasecmp($value, 'yes') === 0;
                continue;
            }

            $data[$header] = $value;
        }

        return $data;
    }

    private function parseDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
