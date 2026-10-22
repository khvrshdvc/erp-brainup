<?php

namespace Database\Factories;

use App\Models\ShopItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopItem>
 */
class ShopItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(50, 500),
            'stock' => 10,
            'is_active' => true,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => ['stock' => null]);
    }
}
