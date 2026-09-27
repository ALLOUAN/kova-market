<?php

namespace Database\Factories;

use App\Enums\Role;
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
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Back-office staff member with the given role, two-factor authentication already set up.
     */
    public function staff(Role $role): static
    {
        return $this->withAppAuthentication()->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }

    /**
     * Indicate that the user has set up an authenticator app for the back-office.
     */
    public function withAppAuthentication(): static
    {
        return $this->state(fn (array $attributes) => [
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
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
