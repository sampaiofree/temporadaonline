<?php

namespace Database\Factories;

use App\Models\Jogador;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Jogador>
 */
class JogadorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Jogador::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'nickname' => Str::slug(fake()->unique()->userName()),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'whatsapp' => fake()->optional()->numerify('55#########'),
            'plataforma' => null,
            'geracao' => null,
            'jogo' => null,
            'regiao' => 'Brasil',
            'idioma' => 'Português do Brasil',
            'reputacao_score' => 99,
            'nivel' => 0,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
