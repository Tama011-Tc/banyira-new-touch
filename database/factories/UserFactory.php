<?php

namespace Database\Factories;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return ['name' => fake()->name(), 'email' => fake()->unique()->safeEmail(), 'email_verified_at' => now(), 'password' => static::$password ??= Hash::make('password'), 'remember_token' => Str::random(10)];
    }

    public function role(Role $role): static
    {
        return $this->afterMaking(fn ($u) => $u->forceFill(['role' => $role, 'status' => 'active']));
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($u) {
            if ($u->role === Role::Investor && ! $u->investorProfile) {
                \App\Models\InvestorProfile::create(['user_id' => $u->id]);
            }
        });
    }
}
