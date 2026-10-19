<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // roles.id no es autoincremental: insertGetId() devuelve 0 en MySQL, así que
        // se inserta con id explícito y luego se lee
        $rolId = \DB::table('roles')->where('nombre', 'cliente')->value('id');
        if (! $rolId) {
            \DB::table('roles')->insertOrIgnore(['id' => 3, 'nombre' => 'cliente']);
            $rolId = \DB::table('roles')->where('nombre', 'cliente')->value('id') ?? 3;
        }

        return [
            'rol_id' => $rolId,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'activo' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
