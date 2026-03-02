<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDisponibilidade;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserDeletionTest extends TestCase
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

    public function test_admin_cannot_delete_own_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Você não pode excluir o seu próprio usuário.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_another_admin_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $anotherAdmin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $anotherAdmin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Usuários administradores não podem ser excluídos.');
        $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id]);
    }

    public function test_admin_cannot_delete_user_with_related_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_admin' => false]);

        UserDisponibilidade::query()->create([
            'user_id' => $target->id,
            'dia_semana' => 1,
            'hora_inicio' => '10:00:00',
            'hora_fim' => '11:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Usuário possui histórico relacionado. Remova os vínculos manualmente antes de excluir.');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_admin_can_delete_regular_user_without_history_and_preserve_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', [
                'user' => $target,
                'q' => 'busca',
            ]));

        $response->assertRedirect(route('admin.users.index', ['q' => 'busca']));
        $response->assertSessionHas('success', 'Usuário excluído com sucesso.');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}

