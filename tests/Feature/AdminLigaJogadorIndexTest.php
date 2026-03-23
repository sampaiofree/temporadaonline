<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaJogador;
use App\Models\Plataforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLigaJogadorIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_liga_jogador_index_displays_club_column_for_users_with_and_without_club(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $liga = $this->createLiga();
        $userWithClub = User::factory()->create();
        $userWithoutClub = User::factory()->create();

        LigaJogador::query()->create([
            'liga_id' => $liga->id,
            'user_id' => $userWithClub->id,
        ]);

        LigaJogador::query()->create([
            'liga_id' => $liga->id,
            'user_id' => $userWithoutClub->id,
        ]);

        LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $userWithClub->id,
            'nome' => 'Clube Admin Teste',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ligas-usuarios.index'));

        $response->assertOk();
        $response->assertSeeText('Clube');
        $response->assertSeeText('Clube Admin Teste');
        $response->assertSeeText('Sem clube');
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
