<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);
        $this->call(CouponSeeder::class);
        $this->call(AdminAndClientSeeder::class);
        $this->call(AppearanceDemoSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'phone' => '3001234567',
                'shipping_address' => 'Calle 10 #20-30',
                'is_admin' => true,
                'password' => Hash::make('password'),
            ],
        );
    }
}
