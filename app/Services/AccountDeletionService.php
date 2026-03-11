<?php

namespace App\Services;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountDeletionService
{
    public function process(User $user, ?AccountDeletionRequest $deletionRequest = null, string $source = 'legacy_profile'): AccountDeletionRequest
    {
        return DB::transaction(function () use ($user, $deletionRequest, $source): AccountDeletionRequest {
            /** @var User $lockedUser */
            $lockedUser = User::query()
                ->with('profile')
                ->lockForUpdate()
                ->findOrFail($user->id);

            $request = $this->resolveDeletionRequest($lockedUser, $deletionRequest);

            $processedAt = now();
            $originalEmail = (string) $lockedUser->email;

            $this->anonymizeUser($lockedUser, $processedAt);
            $this->anonymizeProfile($lockedUser);
            $this->clearRelatedPersonalData((int) $lockedUser->id, $originalEmail);

            $request->forceFill([
                'status' => 'processed',
                'processed_at' => $processedAt,
                'notes' => $this->buildProcessedNotes($request->notes, $source, $processedAt),
            ])->save();

            return $request->fresh() ?? $request;
        });
    }

    private function resolveDeletionRequest(User $user, ?AccountDeletionRequest $deletionRequest): AccountDeletionRequest
    {
        if ($deletionRequest && (int) $deletionRequest->user_id === (int) $user->id) {
            $resolved = AccountDeletionRequest::query()
                ->lockForUpdate()
                ->find($deletionRequest->id);

            if ($resolved) {
                return $resolved;
            }
        }

        $resolved = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($resolved) {
            return $resolved;
        }

        $resolved = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'processed')
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($resolved) {
            return $resolved;
        }

        return AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'email' => (string) $user->email,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    private function anonymizeUser(User $user, \DateTimeInterface $processedAt): void
    {
        $emailAlias = $this->buildAnonymizedEmail((int) $user->id, $processedAt);

        $user->forceFill([
            'name' => sprintf('Conta excluida #%d', (int) $user->id),
            'email' => $emailAlias,
            'password' => Hash::make(Str::random(64)),
            'remember_token' => Str::random(32),
            'email_verified_at' => null,
            'email_verification_code_hash' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_code_sent_at' => null,
            'email_verification_code_attempts' => 0,
            'password_reset_code_hash' => null,
            'password_reset_code_expires_at' => null,
            'password_reset_code_sent_at' => null,
            'password_reset_code_verified_at' => null,
            'password_reset_code_attempts' => 0,
            'is_admin' => false,
        ])->save();
    }

    private function anonymizeProfile(User $user): void
    {
        $profile = $user->profile;
        if (! $profile) {
            return;
        }

        $profile->forceFill([
            'nickname' => null,
            'avatar' => null,
            'whatsapp' => null,
            'regiao' => 'Nao informado',
            'idioma' => 'Nao informado',
            'plataforma_id' => null,
            'jogo_id' => null,
            'geracao_id' => null,
            'regiao_id' => null,
            'idioma_id' => null,
        ])->save();
    }

    private function clearRelatedPersonalData(int $userId, string $originalEmail): void
    {
        if (Schema::hasTable('user_disponibilidades')) {
            DB::table('user_disponibilidades')->where('user_id', $userId)->delete();
        }

        if (Schema::hasTable('player_favorites')) {
            DB::table('player_favorites')->where('user_id', $userId)->delete();
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }

        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $originalEmail)->delete();
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $userId)
                ->delete();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $userId)
                ->delete();
        }
    }

    private function buildProcessedNotes(?string $existingNotes, string $source, \DateTimeInterface $processedAt): string
    {
        $prefix = trim((string) $existingNotes);
        $entry = sprintf(
            '[%s] Processada automaticamente (%s): conta anonimizada e acessos revogados.',
            $processedAt->format(\DateTimeInterface::ATOM),
            trim($source) !== '' ? trim($source) : 'unknown_source',
        );

        if ($prefix === '') {
            return $entry;
        }

        return $prefix.PHP_EOL.$entry;
    }

    private function buildAnonymizedEmail(int $userId, \DateTimeInterface $processedAt): string
    {
        $timestamp = $processedAt->format('YmdHis');

        return sprintf('deleted-user-%d-%s@deleted.legaxi.invalid', $userId, $timestamp);
    }
}
