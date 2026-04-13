<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SellerTestSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    User::query()->updateOrCreate(
      ['email' => 'vendedor.test@carrito.local'],
      [
        'name' => 'Vendedor Test',
        'phone' => '3005550002',
        'shipping_address' => 'Bodega Vendedor 456',
        'is_admin' => true,
        'email_verified_at' => now(),
        'password' => Hash::make('VendedorTest123'),
      ],
    );
  }
}
