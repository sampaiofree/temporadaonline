<?php

namespace Tests\Feature;

use App\Models\Jogo;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserEditTest extends TestCase
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

    public function test_admin_can_see_jogo_field_on_user_edit_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();
        $jogo = Jogo::query()->create([
            'nome' => 'FIFA 26',
            'slug' => 'fifa-26',
        ]);

        Profile::factory()->create([
            'user_id' => $target->id,
            'jogo_id' => $jogo->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.edit', $target));

        $response
            ->assertOk()
            ->assertSee('Jogo')
            ->assertSee('FIFA 26');
    }

    public function test_admin_can_update_user_jogo(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create([
            'name' => 'Jogador Atual',
            'email' => 'jogador@example.com',
        ]);
        $jogo = Jogo::query()->create([
            'nome' => 'FIFA 26',
            'slug' => 'fifa-26',
        ]);

        Profile::factory()->create([
            'user_id' => $target->id,
            'jogo_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'Jogador Atual',
                'email' => 'jogador@example.com',
                'password' => null,
                'nickname' => null,
                'whatsapp' => null,
                'plataforma_id' => null,
                'jogo_id' => $jogo->id,
                'regiao_id' => null,
                'idioma_id' => null,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('profiles', [
            'user_id' => $target->id,
            'jogo_id' => $jogo->id,
        ]);
    }
}
