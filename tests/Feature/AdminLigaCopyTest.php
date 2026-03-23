<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Plataforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLigaCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_page_explains_cup_format_before_club_entry(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.ligas.create'));

        $response->assertOk();
        $response->assertSeeText('Defina o formato da liga antes da entrada de clubes.');
        $response->assertSeeText('os formatos permitidos sao 8, 16, 32 ou 64 clubes', false);
        $response->assertSeeText('Formato da liga (maximo de clubes)');
        $response->assertSeeText('Voce ainda pode alterar esse formato antes da entrada de clubes.');
    }

    public function test_admin_edit_page_without_clubs_explains_format_can_still_change(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $liga = $this->createLiga();

        $response = $this->actingAs($admin)->get(route('admin.ligas.edit', $liga));

        $response->assertOk();
        $response->assertSeeText('Os campos permitidos ainda podem ser ajustados.');
        $response->assertSeeText('Defina o formato final da liga antes da entrada de clubes.');
        $response->assertSeeText('Voce ainda pode alterar esse formato antes da entrada de clubes.');
    }

    public function test_admin_edit_page_with_clubs_explains_locked_format(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $liga = $this->createLiga();
        $user = User::factory()->create();

        LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $user->id,
            'nome' => 'Clube Teste',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ligas.edit', $liga));

        $response->assertOk();
        $response->assertSeeText('A liga ja iniciou sua estrutura competitiva.');
        $response->assertSeeText('O formato ficou bloqueado para preservar os grupos e o chaveamento da Copa.');
        $response->assertSeeText('Formato bloqueado: a liga ja recebeu clubes.');
        $response->assertSeeText('o campo permanece apenas para consulta.', false);
        $response->assertSee('disabled', false);
    }

    private function createLiga(): Liga
    {
        $suffix = uniqid('', true);

        $jogo = Jogo::query()->create([
            'nome' => "Jogo {$suffix}",
            'slug' => "jogo-{$suffix}",
        ]);

        $geracao = Geracao::query()->create([
            'nome' => "Geracao {$suffix}",
            'slug' => "geracao-{$suffix}",
        ]);

        $plataforma = Plataforma::query()->create([
            'nome' => "Plataforma {$suffix}",
            'slug' => "plataforma-{$suffix}",
        ]);

        $confederacao = Confederacao::query()->create([
            'nome' => "Conf {$suffix}",
            'timezone' => 'UTC',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        return Liga::query()->create([
            'nome' => "Liga {$suffix}",
            'descricao' => 'Descricao',
            'regras' => 'Regras',
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 16,
            'saldo_inicial' => 0,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);
    }
}
