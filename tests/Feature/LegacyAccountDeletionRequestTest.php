<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLegacyFirstAccessCompleted;
use App\Models\AccountDeletionRequest;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserDisponibilidade;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyAccountDeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
            EnsureLegacyFirstAccessCompleted::class,
        ]);
    }

    public function test_user_request_processes_account_deletion_and_anonymizes_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Jogador Teste',
            'email' => 'jogador.teste@example.com',
        ]);

        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'gamer_tag',
            'whatsapp' => '5511999999999',
            'regiao' => 'Brasil',
            'idioma' => 'Portugues do Brasil',
        ]);

        UserDisponibilidade::query()->create([
            'user_id' => $user->id,
            'dia_semana' => 2,
            'hora_inicio' => '19:00:00',
            'hora_fim' => '20:00:00',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'jogador.teste@example.com',
            'token' => 'dummy-token',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/legacy/profile/request-account-deletion');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'processed',
                'pending' => false,
            ]);

        $this->assertDatabaseHas('account_deletion_requests', [
            'user_id' => $user->id,
            'email' => 'jogador.teste@example.com',
            'status' => 'processed',
        ]);

        $this->assertDatabaseCount('account_deletion_requests', 1);
        $this->assertDatabaseMissing('user_disponibilidades', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'jogador.teste@example.com',
        ]);

        $reloadedUser = User::query()->findOrFail($user->id);
        $this->assertNotSame('jogador.teste@example.com', $reloadedUser->email);
        $this->assertStringStartsWith('deleted-user-'.$user->id.'-', $reloadedUser->email);
        $this->assertSame('Conta excluida #'.$user->id, $reloadedUser->name);

        $this->assertGuest();
    }

    public function test_existing_pending_request_is_processed_without_creating_duplicate_rows(): void
    {
        $user = User::factory()->create();

        AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/legacy/profile/request-account-deletion');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'processed',
                'pending' => false,
            ]);

        $this->assertSame(
            1,
            AccountDeletionRequest::query()
                ->where('user_id', $user->id)
                ->count()
        );

        $this->assertDatabaseHas('account_deletion_requests', [
            'user_id' => $user->id,
            'status' => 'processed',
        ]);
    }
}
