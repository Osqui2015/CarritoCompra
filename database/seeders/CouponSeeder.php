<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::query()->updateOrCreate(
            ['code' => 'BIENVENIDA10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
                'usage_limit' => 1000,
                'times_used' => 0,
            ],
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'MAYORISTA5000'],
            [
                'type' => 'fixed',
                'value' => 5000,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addMonths(1),
                'is_active' => true,
                'usage_limit' => null,
                'times_used' => 0,
            ],
        );
    }
}
