<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jogadores') && ! Schema::hasTable('profiles')) {
            Schema::rename('jogadores', 'profiles');
        }

        if (! Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('profiles', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id');
            }
        });

        $hasNome = Schema::hasColumn('profiles', 'nome');
        $hasEmail = Schema::hasColumn('profiles', 'email');
        $hasPassword = Schema::hasColumn('profiles', 'password');
        $hasEmailVerifiedAt = Schema::hasColumn('profiles', 'email_verified_at');

        if ($hasEmail) {
            $profiles = DB::table('profiles')
                ->select([
                    'id',
                    $hasNome ? 'nome' : DB::raw("'' as nome"),
                    'email',
                    $hasPassword ? 'password' : DB::raw('NULL as password'),
                    $hasEmailVerifiedAt ? 'email_verified_at' : DB::raw('NULL as email_verified_at'),
                ])
                ->whereNull('user_id')
                ->get();

            foreach ($profiles as $profile) {
                if (! $profile->email) {
                    continue;
                }

                $existingUser = DB::table('users')->where('email', $profile->email)->first();
                $userId = $existingUser?->id;

                if (! $userId) {
                    $userId = DB::table('users')->insertGetId([
                        'name' => $profile->nome ?: 'Usuário',
                        'email' => $profile->email,
                        'email_verified_at' => $profile->email_verified_at,
                        'password' => $profile->password ?: bcrypt(Str::random(32)),
                        'remember_token' => Str::random(10),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('profiles')
                    ->where('id', $profile->id)
                    ->update(['user_id' => $userId]);
            }
        }

        Schema::table('profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('profiles', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unique('user_id');
            }
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            if ($hasEmail) {
                DB::statement('DROP INDEX IF EXISTS jogadores_email_unique;');
                DB::statement('DROP INDEX IF EXISTS profiles_email_unique;');
            }
        }

        Schema::table('profiles', function (Blueprint $table) use ($hasNome, $hasEmail, $hasEmailVerifiedAt, $hasPassword): void {
            $columnsToDrop = [];

            if ($hasNome) {
                $columnsToDrop[] = 'nome';
            }

            if ($hasEmail) {
                $columnsToDrop[] = 'email';
            }

            if ($hasEmailVerifiedAt) {
                $columnsToDrop[] = 'email_verified_at';
            }

            if ($hasPassword) {
                $columnsToDrop[] = 'password';
            }

            if (Schema::hasColumn('profiles', 'remember_token')) {
                $columnsToDrop[] = 'remember_token';
            }

            if (count($columnsToDrop) > 0) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('profiles', 'user_id')) {
                $table->dropUnique(['user_id']);
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        if (! Schema::hasTable('jogadores')) {
            Schema::rename('profiles', 'jogadores');
        }
    }
};
