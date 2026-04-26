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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'mobile' => fake()->phoneNumber(),
            'locked' => fake()->boolean(),
        ];
    }

    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->assignRole($role);
        });
    }

    public function admin(): static
    {
        return $this->withRole('admin');
    }

    public function manager(): static
    {
        return $this->withRole('manager');
    }

    public function user(): static
    {
        return $this->withRole('user');
    }
}
