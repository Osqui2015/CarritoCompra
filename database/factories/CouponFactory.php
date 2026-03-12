<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->bothify('PROMO##??')),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'value' => fake()->randomElement([5, 10, 15, 20, 1000, 2500]),
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_limit' => null,
            'times_used' => 0,
        ];
    }
}
