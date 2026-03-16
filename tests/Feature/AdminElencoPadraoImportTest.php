<?php

namespace Tests\Feature;

use App\Models\Elencopadrao;
use App\Models\Jogo;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminElencoPadraoImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);
    }

    public function test_admin_import_upserts_players_in_batches_and_normalizes_invalid_numeric_values(): void
    {
        $disk = config('filesystems.default', 'local');
        Storage::fake($disk);

        $admin = User::factory()->create(['is_admin' => true]);
        $jogo = Jogo::query()->create([
            'nome' => 'FC Import',
            'slug' => 'fc-import',
        ]);

        $existingPlayer = Elencopadrao::query()->create([
            'jogo_id' => $jogo->id,
            'player_id' => 'existing-1',
            'long_name' => 'Jogador Antigo',
            'short_name' => 'Antigo',
            'player_positions' => 'CM',
            'overall' => 70,
            'value_eur' => 50_000_000,
            'height_cm' => 175,
            'club_jersey_number' => 8,
        ]);

        $csv = implode("\n", [
            'player_id;long_name;player_positions;overall;value_eur;height_cm;club_jersey_number;club_joined_date',
            'existing-1;Jogador Atualizado;CM;72;115.5M;178.0;4.0;Jun 30, 2025',
            'new-1;Jogador Novo;ST;81;#VALOR!;190.0;9.0;2025-07-01',
            ';Sem Id;RW;77;1000000;170;7;2025-08-01',
        ]);

        $path = 'tmp/elenco-import-test.csv';
        Storage::disk($disk)->put($path, $csv);

        $mapping = [
            'player_id' => 'player_id',
            'long_name' => 'long_name',
            'player_positions' => 'player_positions',
            'overall' => 'overall',
            'value_eur' => 'value_eur',
            'height_cm' => 'height_cm',
            'club_jersey_number' => 'club_jersey_number',
            'club_joined_date' => 'club_joined_date',
        ];

        $response = $this
            ->actingAs($admin)
            ->withSession([
                'elenco_import' => [
                    'path' => $path,
                    'jogo_id' => $jogo->id,
                    'columns' => array_values(array_filter(explode(';', strtok($csv, "\n")))),
                    'mapping' => $mapping,
                    'match_strategy' => 'player_id',
                ],
            ])
            ->post(route('admin.elenco-padrao.importar'), [
                'step' => 'confirm',
                'mapping' => $mapping,
            ]);

        $response
            ->assertRedirect(route('admin.elenco-padrao.index'))
            ->assertSessionHas('success', 'Importacao concluida. Criados: 1 | Atualizados: 1 | Ignorados: 1.');

        $existingPlayer->refresh();

        $this->assertSame('Jogador Atualizado', $existingPlayer->long_name);
        $this->assertSame(72, $existingPlayer->overall);
        $this->assertSame(115_500_000, $existingPlayer->value_eur);
        $this->assertSame(178, $existingPlayer->height_cm);
        $this->assertSame(4, $existingPlayer->club_jersey_number);
        $this->assertSame('2025-06-30', optional($existingPlayer->club_joined_date)->toDateString());

        $newPlayer = Elencopadrao::query()
            ->where('jogo_id', $jogo->id)
            ->where('player_id', 'new-1')
            ->firstOrFail();

        $this->assertSame('Jogador Novo', $newPlayer->long_name);
        $this->assertSame(81, $newPlayer->overall);
        $this->assertNull($newPlayer->value_eur);
        $this->assertSame(190, $newPlayer->height_cm);
        $this->assertSame(9, $newPlayer->club_jersey_number);
        $this->assertSame('2025-07-01', optional($newPlayer->club_joined_date)->toDateString());

        $this->assertDatabaseCount('elencopadrao', 2);
    }
}
