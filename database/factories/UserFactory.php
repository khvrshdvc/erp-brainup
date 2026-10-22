<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Branch;
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
     * Define the model's default state (an active student).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+998 9# ### ## ##'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Student,
            'status' => UserStatus::Active,
            'birth_date' => fake()->dateTimeBetween('-30 years', '-10 years')->format('Y-m-d'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Student]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Teacher,
            'birth_date' => fake()->dateTimeBetween('-50 years', '-23 years')->format('Y-m-d'),
        ]);
    }

    public function branchAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::BranchAdmin,
            'birth_date' => fake()->dateTimeBetween('-50 years', '-25 years')->format('Y-m-d'),
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::SuperAdmin,
            'branch_id' => null,
        ]);
    }

    public function inBranch(Branch|int $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branch instanceof Branch ? $branch->id : $branch,
        ]);
    }
}
