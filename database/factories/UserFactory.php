<?php

namespace Database\Factories;

use App\Models\User;
use App\UserRole;
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
            'name' => fake('pl_PL')->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
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

    /**
     * Konto czekające na aktywację przez klub.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    public function boss(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Boss,
        ]);
    }

    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Editor,
        ]);
    }

    /**
     * Indicate that the user is a trainer.
     */
    public function trainer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Trainer,
        ]);
    }

    /**
     * Indicate that the user is a parent.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Parent,
        ]);
    }

    /**
     * Indicate that the user is a player.
     */
    public function player(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Player,
        ]);
    }

    /**
     * Indicate that the user is a fan.
     */
    public function fan(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Fan,
        ]);
    }
}
