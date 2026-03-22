<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\PartidaDesempenho;
use App\Models\Plataforma;
use App\Models\Temporada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacySeasonStatsDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_season_stats_aggregates_club_history_across_confederacao_and_uses_official_score_for_goals(): void
    {
        ['confederacao' => $confederacao, 'jogo' => $jogo] = $this->createConfederacaoContext();
        $ligaA = $this->createLiga($confederacao, $jogo, 'Liga A');
        $ligaB = $this->createLiga($confederacao, $jogo, 'Liga B');

        $user = User::factory()->create();
        $opponentAUser = User::factory()->create();
        $opponentBUser = User::factory()->create();

        $user->ligas()->attach([$ligaA->id, $ligaB->id]);
        $opponentAUser->ligas()->attach([$ligaA->id]);
        $opponentBUser->ligas()->attach([$ligaB->id]);

        $clubeA = LigaClube::create([
            'liga_id' => $ligaA->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => 'Meu Clube A',
        ]);

        $clubeB = LigaClube::create([
            'liga_id' => $ligaB->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => 'Meu Clube B',
        ]);

        $opponentA = LigaClube::create([
            'liga_id' => $ligaA->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $opponentAUser->id,
            'nome' => 'Adversario A',
        ]);

        $opponentB = LigaClube::create([
            'liga_id' => $ligaB->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $opponentBUser->id,
            'nome' => 'Adversario B',
        ]);

        Temporada::create([
            'confederacao_id' => $confederacao->id,
            'name' => 'Temporada Unica',
            'descricao' => 'Temporada de teste',
            'data_inicio' => now()->subDays(15)->toDateString(),
            'data_fim' => now()->addDays(15)->toDateString(),
        ]);

        $matchA = Partida::create([
            'liga_id' => $ligaA->id,
            'mandante_id' => $clubeA->id,
            'visitante_id' => $opponentA->id,
            'estado' => 'placar_registrado',
            'placar_mandante' => 2,
            'placar_visitante' => 1,
            'placar_registrado_por' => $user->id,
            'placar_registrado_em' => now()->subDays(1),
        ]);

        Partida::create([
            'liga_id' => $ligaB->id,
            'mandante_id' => $opponentB->id,
            'visitante_id' => $clubeB->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 1,
            'placar_visitante' => 2,
            'placar_registrado_por' => $user->id,
            'placar_registrado_em' => now(),
        ]);

        $athlete = $this->createElenco($jogo);
        PartidaDesempenho::create([
            'partida_id' => $matchA->id,
            'liga_clube_id' => $clubeA->id,
            'elencopadrao_id' => $athlete->id,
            'nota' => 7.5,
            'gols' => 1,
            'assistencias' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('legacy.season_stats.data', [
            'confederacao_id' => $confederacao->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.matches_played', 2);
        $response->assertJsonPath('summary.wins', 2);
        $response->assertJsonPath('summary.goals_for', 4);
        $response->assertJsonPath('summary.goals', 4);
        $response->assertJsonPath('summary.assists', 1);
        $response->assertJsonPath('history.0.league', 'MULTIPLAS LIGAS');
        $response->assertJsonPath('history.0.pos', null);
    }

    public function test_season_history_keeps_position_when_only_one_liga_is_present_in_period(): void
    {
        ['confederacao' => $confederacao, 'jogo' => $jogo] = $this->createConfederacaoContext();
        $liga = $this->createLiga($confederacao, $jogo, 'Liga Unica');

        $user = User::factory()->create();
        $opponentUser = User::factory()->create();

        $user->ligas()->attach([$liga->id]);
        $opponentUser->ligas()->attach([$liga->id]);

        $meuClube = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => 'Meu Clube',
        ]);

        $adversario = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $opponentUser->id,
            'nome' => 'Clube Rival',
        ]);

        Temporada::create([
            'confederacao_id' => $confederacao->id,
            'name' => 'Temporada Liga Unica',
            'descricao' => 'Temporada com uma liga',
            'data_inicio' => now()->subDays(10)->toDateString(),
            'data_fim' => now()->addDays(10)->toDateString(),
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $meuClube->id,
            'visitante_id' => $adversario->id,
            'estado' => 'placar_registrado',
            'placar_mandante' => 3,
            'placar_visitante' => 0,
            'placar_registrado_por' => $user->id,
            'placar_registrado_em' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('legacy.season_stats.data', [
            'confederacao_id' => $confederacao->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('history.0.league', 'Liga Unica');
        $response->assertJsonPath('history.0.pos', 1);
        $response->assertJsonPath('history.0.trophy', 'CAMPEAO');
    }

    /**
     * @return array{confederacao:Confederacao,jogo:Jogo}
     */
    private function createConfederacaoContext(): array
    {
        $suffix = Str::lower(Str::random(6));

        $plataforma = Plataforma::create([
            'nome' => "Plataforma {$suffix}",
            'slug' => "plat-{$suffix}",
        ]);

        $jogo = Jogo::create([
            'nome' => "Jogo {$suffix}",
            'slug' => "jogo-{$suffix}",
        ]);

        $geracao = Geracao::create([
            'nome' => "Geracao {$suffix}",
            'slug' => "geracao-{$suffix}",
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Confederacao {$suffix}",
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'timezone' => 'America/Sao_Paulo',
            'ganho_vitoria_partida' => 750000,
            'ganho_empate_partida' => 300000,
            'ganho_derrota_partida' => 50000,
        ]);

        return [
            'confederacao' => $confederacao,
            'jogo' => $jogo,
        ];
    }

    private function createLiga(Confederacao $confederacao, Jogo $jogo, string $nome): Liga
    {
        $suffix = Str::lower(Str::random(5));

        return Liga::create([
            'nome' => $nome,
            'descricao' => "Descricao {$suffix}",
            'regras' => "Regras {$suffix}",
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 16,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 1000000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $confederacao->geracao_id,
            'plataforma_id' => $confederacao->plataforma_id,
        ]);
    }

    private function createElenco(Jogo $jogo): Elencopadrao
    {
        $suffix = Str::lower(Str::random(8));

        return Elencopadrao::create([
            'jogo_id' => $jogo->id,
            'player_id' => "player-{$suffix}",
            'short_name' => "P{$suffix}",
            'long_name' => "Player {$suffix}",
            'player_positions' => 'CM',
            'overall' => 80,
            'value_eur' => 0,
            'wage_eur' => 0,
        ]);
    }
}
