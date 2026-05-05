<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserHardDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            /** @var User $lockedUser */
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $this->deleteDirectRows((int) $lockedUser->id, (string) $lockedUser->email);

            $lockedUser->delete();
        });
    }

    private function deleteDirectRows(int $userId, string $email): void
    {
        foreach ([
            'liga_clube_conquistas',
            'liga_clube_patrocinios',
            'liga_clube_ajustes_salariais',
            'liga_clube_vendas_mercado',
            'sessions',
        ] as $table) {
            $this->deleteByUserId($table, $userId);
        }

        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
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

    private function deleteByUserId(string $table, int $userId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
            return;
        }

        DB::table($table)->where('user_id', $userId)->delete();
    }
}
