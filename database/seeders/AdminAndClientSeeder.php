<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAndClientSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    User::query()->updateOrCreate(
      ['email' => 'admin@carrito.test'],
      [
        'name' => 'Administrador Prueba',
        'phone' => '3001111111',
        'shipping_address' => 'Oficina Central',
        'is_admin' => true,
        'email_verified_at' => now(),
        'password' => Hash::make('Admin12345'),
      ],
    );

    User::query()->updateOrCreate(
      ['email' => 'cliente@carrito.test'],
      [
        'name' => 'Cliente Prueba',
        'phone' => '3002222222',
        'shipping_address' => 'Direccion de Prueba 123',
        'is_admin' => false,
        'email_verified_at' => now(),
        'password' => Hash::make('Cliente12345'),
      ],
    );
  }
}
