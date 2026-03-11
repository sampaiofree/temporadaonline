<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLegacyFirstAccessCompleted;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_user_can_request_account_deletion_from_legacy_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/legacy/profile/request-account-deletion');

        $response
            ->assertCreated()
            ->assertJson([
                'status' => 'pending',
                'pending' => true,
            ]);

        $this->assertDatabaseHas('account_deletion_requests', [
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_create_duplicate_pending_account_deletion_request(): void
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
                'status' => 'pending',
                'pending' => true,
            ]);

        $this->assertSame(
            1,
            AccountDeletionRequest::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->count()
        );
    }

    public function test_user_can_cancel_pending_account_deletion_request(): void
    {
        $user = User::factory()->create();
        $request = AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/legacy/profile/cancel-account-deletion');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'cancelled',
                'pending' => false,
            ]);

        $request->refresh();
        $this->assertSame('cancelled', $request->status);
        $this->assertNotNull($request->processed_at);

        $this->assertDatabaseMissing('account_deletion_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_cancel_returns_ok_when_there_is_no_pending_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/legacy/profile/cancel-account-deletion');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'none',
                'pending' => false,
            ]);

        $this->assertDatabaseMissing('account_deletion_requests', [
            'user_id' => $user->id,
        ]);
    }
}
