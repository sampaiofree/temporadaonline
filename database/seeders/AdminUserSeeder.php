<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'sampaio.free@gmail.com'],
            [
                'name' => 'Sampaio Free',
                'email' => 'sampaio.free@gmail.com',
                'password' => Hash::make('admin123'),
            ],
        );

        if (! $user->is_admin) {
            $user->forceFill(['is_admin' => true])->save();
        }

        Profile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nickname' => 'sampaio.free',
                'whatsapp' => '559999999999',
                'regiao' => 'Brasil',
                'idioma' => 'Português do Brasil',
                'reputacao_score' => 99,
                'nivel' => 0,
            ],
        );
    }
}
