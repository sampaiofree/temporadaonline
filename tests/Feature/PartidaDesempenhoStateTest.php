<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaPeriodo;
use App\Models\Partida;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\PartidaDesempenhoAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class PartidaDesempenhoStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_allows_agendada_state(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('agendada');

        $response = $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/confirm", [
                'mandante' => [],
                'visitante' => [],
                'placar_mandante' => 2,
                'placar_visitante' => 1,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'estado' => 'placar_registrado',
                'placar_mandante' => 2,
                'placar_visitante' => 1,
            ]);

        $this->assertDatabaseHas('partidas', [
            'id' => $partida->id,
            'estado' => 'placar_registrado',
            'placar_mandante' => 2,
            'placar_visitante' => 1,
            'placar_registrado_por' => $mandanteUser->id,
        ]);
    }

    public function test_confirm_rejects_finalized_state(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('finalizada');

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/confirm", [
                'mandante' => [],
                'visitante' => [],
                'placar_mandante' => 1,
                'placar_visitante' => 0,
            ])
            ->assertForbidden();
    }

    public function test_confirm_rejects_when_market_is_open(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('confirmada');

        LigaPeriodo::query()->create([
            'confederacao_id' => $partida->liga->confederacao_id,
            'inicio' => now()->subHour()->format('Y-m-d H:i:s'),
            'fim' => now()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/confirm", [
                'mandante' => [],
                'visitante' => [],
                'placar_mandante' => 1,
                'placar_visitante' => 0,
            ])
            ->assertStatus(423)
            ->assertJson([
                'message' => 'Mercado aberto. O envio de súmulas fica bloqueado até o fechamento da janela.',
            ]);

        $this->assertDatabaseHas('partidas', [
            'id' => $partida->id,
            'estado' => 'confirmada',
            'placar_registrado_por' => null,
        ]);
    }

    public function test_preview_rejects_when_market_is_open(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('confirmada');

        LigaPeriodo::query()->create([
            'confederacao_id' => $partida->liga->confederacao_id,
            'inicio' => now()->subHour()->format('Y-m-d H:i:s'),
            'fim' => now()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/preview")
            ->assertStatus(423)
            ->assertJson([
                'message' => 'Mercado aberto. O envio de súmulas fica bloqueado até o fechamento da janela.',
            ]);
    }

    public function test_form_returns_full_rosters(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('confirmada');
        $roster = $this->seedRoster($partida);

        $this
            ->actingAs($mandanteUser)
            ->getJson("/api/partidas/{$partida->id}/desempenho/form")
            ->assertOk()
            ->assertJsonCount(2, 'mandante.entries')
            ->assertJsonCount(2, 'visitante.entries')
            ->assertJsonPath('mandante.entries.0.elencopadrao_id', $roster['mandante'][0]->id)
            ->assertJsonPath('mandante.entries.0.nota', '')
            ->assertJsonPath('mandante.entries.0.gols', 0)
            ->assertJsonPath('visitante.entries.1.elencopadrao_id', $roster['visitante'][1]->id);
    }

    public function test_preview_returns_soft_failure_when_ai_breaks(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('confirmada');
        $this->seedRoster($partida);

        $this->mock(PartidaDesempenhoAiService::class, function ($mock): void {
            $mock->shouldReceive('analyzeMatch')
                ->once()
                ->andThrow(new RuntimeException('Falha na IA'));
        });

        $this
            ->actingAs($mandanteUser)
            ->post("/api/partidas/{$partida->id}/desempenho/preview", [
                'mandante_imagem' => UploadedFile::fake()->image('mandante.jpg'),
                'visitante_imagem' => UploadedFile::fake()->image('visitante.jpg'),
            ])
            ->assertOk()
            ->assertJson([
                'analysis_failed' => true,
                'warning' => 'Não foi possível analisar as imagens. Você pode preencher a súmula manualmente.',
            ])
            ->assertJsonCount(0, 'mandante.entries')
            ->assertJsonCount(0, 'visitante.entries');
    }

    public function test_confirm_sanitizes_payload_and_keeps_manual_score(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('confirmada');
        $roster = $this->seedRoster($partida);

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/confirm", [
                'mandante' => [
                    [
                        'elencopadrao_id' => $roster['mandante'][0]->id,
                        'nota' => '',
                        'gols' => 2,
                        'assistencias' => 1,
                    ],
                    [
                        'elencopadrao_id' => $roster['mandante'][0]->id,
                        'nota' => 7.5,
                        'gols' => 1,
                        'assistencias' => 0,
                    ],
                    [
                        'elencopadrao_id' => $roster['mandante'][1]->id,
                        'nota' => 'abc',
                        'gols' => 1,
                        'assistencias' => 1,
                    ],
                    [
                        'elencopadrao_id' => 999999,
                        'nota' => 9.0,
                        'gols' => 4,
                        'assistencias' => 2,
                    ],
                ],
                'visitante' => [
                    [
                        'elencopadrao_id' => $roster['visitante'][0]->id,
                        'nota' => 6.2,
                        'gols' => '2x',
                        'assistencias' => -3,
                    ],
                    [
                        'elencopadrao_id' => $roster['visitante'][1]->id,
                        'nota' => null,
                        'gols' => 1,
                        'assistencias' => 1,
                    ],
                ],
                'placar_mandante' => 5,
                'placar_visitante' => 4,
            ])
            ->assertOk()
            ->assertJson([
                'estado' => 'placar_registrado',
                'placar_mandante' => 5,
                'placar_visitante' => 4,
            ]);

        $this->assertDatabaseHas('partida_desempenhos', [
            'partida_id' => $partida->id,
            'liga_clube_id' => $partida->mandante_id,
            'elencopadrao_id' => $roster['mandante'][0]->id,
            'nota' => 7.5,
            'gols' => 1,
            'assistencias' => 0,
        ]);

        $this->assertDatabaseHas('partida_desempenhos', [
            'partida_id' => $partida->id,
            'liga_clube_id' => $partida->visitante_id,
            'elencopadrao_id' => $roster['visitante'][0]->id,
            'nota' => 6.2,
            'gols' => 0,
            'assistencias' => 0,
        ]);

        $this->assertDatabaseMissing('partida_desempenhos', [
            'partida_id' => $partida->id,
            'elencopadrao_id' => $roster['mandante'][1]->id,
        ]);

        $this->assertSame(2, \App\Models\PartidaDesempenho::query()->where('partida_id', $partida->id)->count());
    }

    public function test_confirm_uses_goal_sum_when_manual_score_is_invalid(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('confirmada');
        $roster = $this->seedRoster($partida);

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/confirm", [
                'mandante' => [
                    [
                        'elencopadrao_id' => $roster['mandante'][0]->id,
                        'nota' => 8.0,
                        'gols' => 2,
                        'assistencias' => 1,
                    ],
                ],
                'visitante' => [
                    [
                        'elencopadrao_id' => $roster['visitante'][0]->id,
                        'nota' => 7.0,
                        'gols' => 1,
                        'assistencias' => 0,
                    ],
                ],
                'placar_mandante' => 'invalido',
                'placar_visitante' => null,
            ])
            ->assertOk()
            ->assertJson([
                'placar_mandante' => 2,
                'placar_visitante' => 1,
            ]);
    }

    /**
     * @return array{0: Partida, 1: User, 2: User}
     */
    private function createPartida(string $estado): array
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

        $liga = Liga::query()->create([
            'nome' => "Liga {$suffix}",
            'descricao' => 'Liga de teste',
            'regras' => 'Regras de teste',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'confederacao_id' => $confederacao->id,
        ]);

        $mandanteUser = User::factory()->create();
        $visitanteUser = User::factory()->create();

        $mandante = LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $mandanteUser->id,
            'nome' => 'Mandante FC',
        ]);

        $visitante = LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $visitanteUser->id,
            'nome' => 'Visitante FC',
        ]);

        $partida = Partida::query()->create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => $estado,
        ]);

        return [$partida, $mandanteUser, $visitanteUser];
    }

    /**
     * @return array{mandante: array<int, Elencopadrao>, visitante: array<int, Elencopadrao>}
     */
    private function seedRoster(Partida $partida): array
    {
        $partida->loadMissing(['liga', 'mandante', 'visitante']);

        $mandantePlayers = [
            $this->createPlayer($partida->liga->jogo, 'mandante-a', 'Mandante A'),
            $this->createPlayer($partida->liga->jogo, 'mandante-b', 'Mandante B'),
        ];
        $visitantePlayers = [
            $this->createPlayer($partida->liga->jogo, 'visitante-a', 'Visitante A'),
            $this->createPlayer($partida->liga->jogo, 'visitante-b', 'Visitante B'),
        ];

        foreach ($mandantePlayers as $player) {
            LigaClubeElenco::query()->create([
                'confederacao_id' => $partida->liga->confederacao_id,
                'liga_id' => $partida->liga_id,
                'liga_clube_id' => $partida->mandante_id,
                'elencopadrao_id' => $player->id,
                'value_eur' => 1000,
                'wage_eur' => 100,
                'ativo' => true,
            ]);
        }

        foreach ($visitantePlayers as $player) {
            LigaClubeElenco::query()->create([
                'confederacao_id' => $partida->liga->confederacao_id,
                'liga_id' => $partida->liga_id,
                'liga_clube_id' => $partida->visitante_id,
                'elencopadrao_id' => $player->id,
                'value_eur' => 1000,
                'wage_eur' => 100,
                'ativo' => true,
            ]);
        }

        return [
            'mandante' => $mandantePlayers,
            'visitante' => $visitantePlayers,
        ];
    }

    private function createPlayer(Jogo $jogo, string $playerId, string $name): Elencopadrao
    {
        return Elencopadrao::query()->create([
            'jogo_id' => $jogo->id,
            'player_id' => $playerId,
            'short_name' => $name,
            'long_name' => "{$name} Completo",
            'player_positions' => 'ST',
            'overall' => 75,
            'value_eur' => 1000,
            'wage_eur' => 100,
        ]);
    }
}
