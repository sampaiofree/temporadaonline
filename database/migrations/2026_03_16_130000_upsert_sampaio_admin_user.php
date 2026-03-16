<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $email = 'sampaio.free@gmail.com';
        $now = now();

        $user = DB::table('users')
            ->where('email', $email)
            ->first();

        if ($user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'is_admin' => true,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('users')->insert([
            'name' => 'Sampaio Free',
            'email' => $email,
            'email_verified_at' => $now,
            'password' => Hash::make('admin123'),
            'remember_token' => Str::random(10),
            'is_admin' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('email', 'sampaio.free@gmail.com')
            ->update([
                'is_admin' => false,
                'updated_at' => now(),
            ]);
    }
};
