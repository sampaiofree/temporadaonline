<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
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

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $user->forceFill([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addMinutes(15),
            'email_verification_code_sent_at' => now(),
            'email_verification_code_attempts' => 0,
        ])->save();

        Event::fake();

        $response = $this->actingAs($user)->post('/verify-email/code', [
            'code' => '123456',
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('legacy.index', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_code(): void
    {
        $user = User::factory()->unverified()->create();
        $user->forceFill([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addMinutes(15),
            'email_verification_code_sent_at' => now(),
            'email_verification_code_attempts' => 0,
        ])->save();

        $response = $this->actingAs($user)->from('/verify-email')->post('/verify-email/code', [
            'code' => '999999',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, $user->fresh()->email_verification_code_attempts);
    }

    public function test_email_is_not_verified_with_expired_code(): void
    {
        $user = User::factory()->unverified()->create();
        $user->forceFill([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->subMinute(),
            'email_verification_code_sent_at' => now()->subMinutes(16),
            'email_verification_code_attempts' => 0,
        ])->save();

        $response = $this->actingAs($user)->from('/verify-email')->post('/verify-email/code', [
            'code' => '123456',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_code_resend_respects_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        $user->forceFill([
            'email_verification_code_sent_at' => now(),
        ])->save();

        $response = $this->actingAs($user)->from('/verify-email')->post('/email/verification-notification');

        $response->assertRedirect('/verify-email');
        $response->assertSessionHas('status');
    }
}
