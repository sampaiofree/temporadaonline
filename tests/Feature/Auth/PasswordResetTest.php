<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_code_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.reset', ['email' => $user->email], false));

        Notification::assertSentTo($user, ResetPasswordCodeNotification::class);
    }

    public function test_password_reset_code_can_be_confirmed(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordCodeNotification::class, function (ResetPasswordCodeNotification $notification) use ($user) {
            $response = $this->post('/reset-password/code', [
                'email' => $user->email,
                'code' => $notification->code(),
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('password.reset', ['email' => $user->email], false));

            return true;
        });
    }

    public function test_password_can_be_reset_after_code_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordCodeNotification::class, function (ResetPasswordCodeNotification $notification) use ($user) {
            $this->post('/reset-password/code', [
                'email' => $user->email,
                'code' => $notification->code(),
            ])->assertSessionHasNoErrors();

            $response = $this->post('/reset-password', [
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('legacy.login'));

            return true;
        });

        $this->assertTrue(Hash::check('NewPassword123!', (string) $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_without_code_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
