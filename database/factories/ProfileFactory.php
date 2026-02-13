<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nickname' => Str::slug(fake()->unique()->userName()),
            'avatar' => null,
            'whatsapp' => fake()->optional()->numerify('55#########'),
            'regiao' => 'Brasil',
            'idioma' => 'Português do Brasil',
            'reputacao_score' => 99,
            'nivel' => 0,
            'plataforma_id' => null,
            'jogo_id' => null,
            'geracao_id' => null,
            'regiao_id' => null,
            'idioma_id' => null,
        ];
    }
}
