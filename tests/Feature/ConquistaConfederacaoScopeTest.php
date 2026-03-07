<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Conquista;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeAjusteSalarial;
use App\Models\Partida;
use App\Models\PartidaAvaliacao;
use App\Models\PartidaDesempenho;
use App\Models\PartidaEvento;
use App\Models\Patrocinio;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\ConquistaProgressService;
use App\Http\Middleware\EnsureLegacyFirstAccessCompleted;
use App\Http\Middleware\EnsureRosterLimitDuringMarketClosed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConquistaConfederacaoScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_service_counts_metrics_with_confederacao_scope(): void
    {
        $suffix = Str::lower(Str::random(6));

        $contextA = $this->createConfederacaoContext("A-{$suffix}");
        $contextB = $this->createConfederacaoContext("B-{$suffix}");

        $ligaA = $this->createLiga($contextA['confederacao'], "Liga A {$suffix}");
        $ligaB = $this->createLiga($contextB['confederacao'], "Liga B {$suffix}");

        $user = User::factory()->create();
        $opponentA = User::factory()->create();
        $opponentB = User::factory()->create();

        $clubeA = $this->createClub($ligaA, $contextA['confederacao'], $user, 'Meu Clube A');
        $adversarioA = $this->createClub($ligaA, $contextA['confederacao'], $opponentA, 'Adversario A');

        $clubeB = $this->createClub($ligaB, $contextB['confederacao'], $user, 'Meu Clube B');
        $adversarioB = $this->createClub($ligaB, $contextB['confederacao'], $opponentB, 'Adversario B');

        $partidaA1 = Partida::create([
            'liga_id' => $ligaA->id,
            'mandante_id' => $clubeA->id,
            'visitante_id' => $adversarioA->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 3,
            'placar_visitante' => 1,
            'placar_registrado_por' => $user->id,
            'placar_registrado_em' => now(),
        ]);

        $partidaA2 = Partida::create([
            'liga_id' => $ligaA->id,
            'mandante_id' => $adversarioA->id,
            'visitante_id' => $clubeA->id,
            'estado' => 'wo',
            'placar_mandante' => 2,
            'placar_visitante' => 0,
            'placar_registrado_por' => $user->id,
            'placar_registrado_em' => now(),
        ]);

        $partidaB1 = Partida::create([
            'liga_id' => $ligaB->id,
            'mandante_id' => $clubeB->id,
            'visitante_id' => $adversarioB->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 4,
            'placar_visitante' => 0,
            'placar_registrado_por' => $user->id,
            'placar_registrado_em' => now(),
        ]);

        PartidaEvento::create([
            'partida_id' => $partidaA1->id,
            'tipo' => 'confirmacao_horario',
            'user_id' => $user->id,
            'payload' => null,
        ]);
        PartidaEvento::create([
            'partida_id' => $partidaA1->id,
            'tipo' => 'confirmacao_horario',
            'user_id' => $user->id,
            'payload' => ['repeat' => true],
        ]);
        PartidaEvento::create([
            'partida_id' => $partidaA2->id,
            'tipo' => 'confirmacao_horario',
            'user_id' => $user->id,
            'payload' => null,
        ]);
        PartidaEvento::create([
            'partida_id' => $partidaB1->id,
            'tipo' => 'confirmacao_horario',
            'user_id' => $user->id,
            'payload' => null,
        ]);

        $playerA1 = $this->createElencoPlayer($contextA['jogo']->id, "Jogador A1 {$suffix}");
        $playerA2 = $this->createElencoPlayer($contextA['jogo']->id, "Jogador A2 {$suffix}");
        $playerA3 = $this->createElencoPlayer($contextA['jogo']->id, "Jogador A3 {$suffix}");
        $playerB1 = $this->createElencoPlayer($contextB['jogo']->id, "Jogador B1 {$suffix}");

        PartidaDesempenho::create([
            'partida_id' => $partidaA1->id,
            'liga_clube_id' => $clubeA->id,
            'elencopadrao_id' => $playerA1->id,
            'nota' => 8.5,
            'gols' => 3,
            'assistencias' => 1,
        ]);
        PartidaDesempenho::create([
            'partida_id' => $partidaA1->id,
            'liga_clube_id' => $clubeA->id,
            'elencopadrao_id' => $playerA2->id,
            'nota' => 7.0,
            'gols' => 1,
            'assistencias' => 0,
        ]);
        PartidaDesempenho::create([
            'partida_id' => $partidaA2->id,
            'liga_clube_id' => $clubeA->id,
            'elencopadrao_id' => $playerA3->id,
            'nota' => 6.0,
            'gols' => 0,
            'assistencias' => 2,
        ]);
        PartidaDesempenho::create([
            'partida_id' => $partidaB1->id,
            'liga_clube_id' => $clubeB->id,
            'elencopadrao_id' => $playerB1->id,
            'nota' => 9.0,
            'gols' => 4,
            'assistencias' => 0,
        ]);

        PartidaAvaliacao::create([
            'partida_id' => $partidaA1->id,
            'avaliador_user_id' => $opponentA->id,
            'avaliado_user_id' => $user->id,
            'nota' => 4,
        ]);

        PartidaAvaliacao::create([
            'partida_id' => $partidaA2->id,
            'avaliador_user_id' => $user->id,
            'avaliado_user_id' => $opponentA->id,
            'nota' => 5,
        ]);

        PartidaAvaliacao::create([
            'partida_id' => $partidaB1->id,
            'avaliador_user_id' => $opponentB->id,
            'avaliado_user_id' => $user->id,
            'nota' => 2,
        ]);

        PartidaAvaliacao::create([
            'partida_id' => $partidaB1->id,
            'avaliador_user_id' => $user->id,
            'avaliado_user_id' => $opponentB->id,
            'nota' => 5,
        ]);

        LigaClubeAjusteSalarial::create([
            'user_id' => $user->id,
            'confederacao_id' => $contextA['confederacao']->id,
            'liga_id' => $ligaA->id,
            'liga_clube_id' => $clubeA->id,
            'liga_clube_elenco_id' => 10,
            'wage_anterior' => 100000,
            'wage_novo' => 120000,
        ]);
        LigaClubeAjusteSalarial::create([
            'user_id' => $user->id,
            'confederacao_id' => $contextA['confederacao']->id,
            'liga_id' => $ligaA->id,
            'liga_clube_id' => $clubeA->id,
            'liga_clube_elenco_id' => 11,
            'wage_anterior' => 120000,
            'wage_novo' => 130000,
        ]);
        LigaClubeAjusteSalarial::create([
            'user_id' => $user->id,
            'confederacao_id' => $contextB['confederacao']->id,
            'liga_id' => $ligaB->id,
            'liga_clube_id' => $clubeB->id,
            'liga_clube_elenco_id' => 12,
            'wage_anterior' => 90000,
            'wage_novo' => 100000,
        ]);

        /** @var ConquistaProgressService $service */
        $service = app(ConquistaProgressService::class);
        $progress = $service->progressForConfederacao($user->id, $contextA['confederacao']->id);

        $this->assertSame(4, (int) $progress['gols']);
        $this->assertSame(3, (int) $progress['assistencias']);
        $this->assertSame(2, (int) $progress['quantidade_jogos']);
        $this->assertSame(1, (int) $progress['n_vitorias']);
        $this->assertSame(3, (int) $progress['n_gols_sofridos']);
        $this->assertSame(1, (int) $progress['n_hat_trick']);
        $this->assertSame(50, (int) $progress['skill_rating']);
        $this->assertSame(4.0, (float) $progress['score']);
        $this->assertSame(2, (int) $progress['agendar_partidas']);
        $this->assertSame(2, (int) $progress['enviar_sumula']);
        $this->assertSame(1, (int) $progress['avaliacoes']);
        $this->assertSame(2, (int) $progress['ajuste_salarial']);
    }

    public function test_claims_are_unique_by_user_and_confederacao(): void
    {
        $this->withoutMiddleware([
            EnsureRosterLimitDuringMarketClosed::class,
            EnsureLegacyFirstAccessCompleted::class,
        ]);

        $suffix = Str::lower(Str::random(6));
        $context = $this->createConfederacaoContext("C-{$suffix}");

        $liga1 = $this->createLiga($context['confederacao'], "Liga 1 {$suffix}");
        $liga2 = $this->createLiga($context['confederacao'], "Liga 2 {$suffix}");

        $user = User::factory()->create();
        $club1 = $this->createClub($liga1, $context['confederacao'], $user, 'Meu Clube 1');
        $club2 = $this->createClub($liga2, $context['confederacao'], $user, 'Meu Clube 2');

        $conquista = Conquista::create([
            'nome' => "Conquista {$suffix}",
            'descricao' => 'Teste',
            'imagem' => 'conquistas/teste.png',
            'tipo' => 'score',
            'quantidade' => 1,
            'fans' => 100,
        ]);

        $this->actingAs($user)
            ->post(route('minha_liga.conquistas.claim', ['conquista' => $conquista->id]), [
                'liga_id' => $liga1->id,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('minha_liga.conquistas.claim', ['conquista' => $conquista->id]), [
                'liga_id' => $liga2->id,
            ])
            ->assertStatus(409);

        $this->assertSame(
            1,
            (int) \DB::table('liga_clube_conquistas')
                ->where('user_id', $user->id)
                ->where('confederacao_id', $context['confederacao']->id)
                ->where('conquista_id', $conquista->id)
                ->count(),
        );

        $patrocinio = Patrocinio::create([
            'nome' => "Patrocinio {$suffix}",
            'descricao' => 'Teste',
            'imagem' => 'patrocinios/teste.png',
            'valor' => 500000,
            'fans' => 50,
        ]);

        $this->actingAs($user)
            ->post(route('minha_liga.patrocinio.claim', ['patrocinio' => $patrocinio->id]), [
                'liga_id' => $liga1->id,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('minha_liga.patrocinio.claim', ['patrocinio' => $patrocinio->id]), [
                'liga_id' => $liga2->id,
            ])
            ->assertStatus(409);

        $this->assertSame(
            1,
            (int) \DB::table('liga_clube_patrocinios')
                ->where('user_id', $user->id)
                ->where('confederacao_id', $context['confederacao']->id)
                ->where('patrocinio_id', $patrocinio->id)
                ->count(),
        );

        $this->assertDatabaseHas('liga_clube_patrocinios', [
            'user_id' => $user->id,
            'confederacao_id' => $context['confederacao']->id,
            'liga_id' => $liga1->id,
            'liga_clube_id' => $club1->id,
        ]);

        $this->assertDatabaseMissing('liga_clube_patrocinios', [
            'user_id' => $user->id,
            'confederacao_id' => $context['confederacao']->id,
            'liga_id' => $liga2->id,
            'liga_clube_id' => $club2->id,
        ]);
    }

    /**
     * @return array{confederacao:Confederacao,jogo:Jogo,geracao:Geracao,plataforma:Plataforma}
     */
    private function createConfederacaoContext(string $label): array
    {
        $plataforma = Plataforma::create([
            'nome' => "Plataforma {$label}",
            'slug' => 'plat-'.Str::slug($label),
        ]);

        $jogo = Jogo::create([
            'nome' => "Jogo {$label}",
            'slug' => 'jogo-'.Str::slug($label),
        ]);

        $geracao = Geracao::create([
            'nome' => "Geracao {$label}",
            'slug' => 'geracao-'.Str::slug($label),
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Confederacao {$label}",
            'descricao' => 'Teste',
            'timezone' => 'America/Sao_Paulo',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        return [
            'confederacao' => $confederacao,
            'jogo' => $jogo,
            'geracao' => $geracao,
            'plataforma' => $plataforma,
        ];
    }

    private function createLiga(Confederacao $confederacao, string $nome): Liga
    {
        return Liga::create([
            'nome' => $nome,
            'descricao' => 'Liga teste',
            'regras' => 'Regras teste',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 20,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 10000000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $confederacao->jogo_id,
            'geracao_id' => $confederacao->geracao_id,
            'plataforma_id' => $confederacao->plataforma_id,
        ]);
    }

    private function createClub(Liga $liga, Confederacao $confederacao, User $user, string $nome): LigaClube
    {
        $user->ligas()->syncWithoutDetaching([$liga->id]);

        return LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => $nome,
        ]);
    }

    private function createElencoPlayer(int $jogoId, string $name): Elencopadrao
    {
        return Elencopadrao::create([
            'jogo_id' => $jogoId,
            'short_name' => $name,
            'long_name' => $name,
            'player_positions' => 'ST',
            'overall' => 75,
        ]);
    }
}
