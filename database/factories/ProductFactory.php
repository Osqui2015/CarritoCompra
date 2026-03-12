<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $stock = fake()->numberBetween(1, 200);

        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => fake()->unique()->slug(),
            'hero_tag' => fake()->sentence(4),
            'description' => fake()->sentence(18),
            'price' => fake()->randomFloat(2, 10, 3000),
            'stock' => $stock,
            'stock_reference' => $stock,
            'is_active' => true,
        ];
    }
}
