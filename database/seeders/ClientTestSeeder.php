<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientTestSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    User::query()->updateOrCreate(
      ['email' => 'cliente.test@carrito.local'],
      [
        'name' => 'Cliente Test',
        'phone' => '3005550001',
        'shipping_address' => 'Calle Cliente 123',
        'is_admin' => false,
        'email_verified_at' => now(),
        'password' => Hash::make('ClienteTest123'),
      ],
    );
  }
}
