<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\EscudoClube;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaEscudo;
use App\Models\Pais;
use App\Models\Plataforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class MinhaLigaOnboardingClubeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function createLigaContext(array $ligaOverrides = []): array
    {
        $suffix = str_replace('.', '', uniqid('', true));

        $plataforma = Plataforma::create([
            'nome' => "PlayStation {$suffix}",
            'slug' => "ps-{$suffix}",
        ]);

        $jogo = Jogo::create([
            'nome' => "FC {$suffix}",
            'slug' => "fc-{$suffix}",
        ]);

        $geracao = Geracao::create([
            'nome' => "Nova {$suffix}",
            'slug' => "nova-{$suffix}",
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Conf {$suffix}",
            'descricao' => 'Confederação de teste',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::create(array_merge([
            'nome' => "Liga Onboarding {$suffix}",
            'descricao' => 'Liga para testes de onboarding.',
            'regras' => 'Regras de teste.',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 20,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 2_000_000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
        ], $ligaOverrides));

        return [$liga, $confederacao];
    }

    private function createEscudo(string $suffix = 'base'): EscudoClube
    {
        $pais = Pais::create([
            'nome' => "Brasil {$suffix}",
            'slug' => "br-{$suffix}",
            'ativo' => true,
        ]);

        $ligaEscudo = LigaEscudo::create([
            'pais_id' => $pais->id,
            'liga_nome' => "Liga Escudo {$suffix}",
            'liga_imagem' => "ligas-escudos/{$suffix}.png",
        ]);

        return EscudoClube::create([
            'pais_id' => $pais->id,
            'liga_id' => $ligaEscudo->id,
            'clube_nome' => "Escudo Clube {$suffix}",
            'clube_imagem' => "escudos-clubes/{$suffix}.png",
        ]);
    }

    public function test_onboarding_clube_page_is_displayed_for_user_in_league(): void
    {
        [$liga, $confederacao] = $this->createLigaContext();
        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        $response = $this
            ->actingAs($user)
            ->get('/minha_liga/onboarding-clube?liga_id='.$liga->id);

        $response->assertOk();
        $response->assertSee('window.__CLUBE_ONBOARDING__', false);
        $response->assertSee($liga->nome, false);
        $response->assertSee($confederacao->nome, false);
    }

    public function test_onboarding_clube_requires_liga_id(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/minha_liga/onboarding-clube');

        $response->assertStatus(400);
    }

    public function test_onboarding_clube_returns_404_when_user_is_not_in_league(): void
    {
        [$liga] = $this->createLigaContext();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/minha_liga/onboarding-clube?liga_id='.$liga->id);

        $response->assertStatus(404);
    }

    public function test_store_clube_creates_wallet_and_returns_initial_roster_payload(): void
    {
        [$liga] = $this->createLigaContext();
        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        Elencopadrao::create([
            'jogo_id' => $liga->jogo_id,
            'short_name' => 'GK TEST',
            'long_name' => 'Goalkeeper Test Seed',
            'player_positions' => 'GK',
            'value_eur' => 1000000,
            'wage_eur' => 5000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/minha_liga/clubes', [
                'liga_id' => $liga->id,
                'nome' => 'Clube Onboarding',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'clube' => ['id', 'liga_id', 'user_id', 'nome'],
            'financeiro' => ['saldo'],
            'initial_roster_added',
            'initial_roster_message',
            'initial_roster_count',
            'initial_roster_cta',
        ]);

        $clubeId = $response->json('clube.id');
        $this->assertNotNull($clubeId);

        $this->assertDatabaseHas('liga_clubes', [
            'id' => $clubeId,
            'liga_id' => $liga->id,
            'user_id' => $user->id,
            'nome' => 'Clube Onboarding',
        ]);

        $this->assertDatabaseHas('liga_clube_financeiro', [
            'liga_id' => $liga->id,
            'clube_id' => $clubeId,
            'saldo' => (int) $liga->saldo_inicial,
        ]);

        $wallet = LigaClubeFinanceiro::query()
            ->where('liga_id', $liga->id)
            ->where('clube_id', $clubeId)
            ->first();

        $this->assertNotNull($wallet);
        $this->assertTrue((bool) $response->json('initial_roster_added'));
        $this->assertGreaterThan(0, (int) $response->json('initial_roster_count'));
    }

    public function test_store_clube_rejects_escudo_already_used_in_same_confederacao(): void
    {
        [$liga] = $this->createLigaContext();
        $escudo = $this->createEscudo('confed');

        $owner = User::factory()->create();
        $owner->ligas()->attach($liga->id);

        LigaClube::create([
            'liga_id' => $liga->id,
            'user_id' => $owner->id,
            'nome' => 'Clube Dono',
            'escudo_clube_id' => $escudo->id,
        ]);

        $candidate = User::factory()->create();
        $candidate->ligas()->attach($liga->id);

        $response = $this
            ->actingAs($candidate)
            ->post('/minha_liga/clubes', [
                'liga_id' => $liga->id,
                'nome' => 'Clube Candidato',
                'escudo_id' => $escudo->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Este escudo já está em uso por outro clube nesta confederação.');
    }
}
