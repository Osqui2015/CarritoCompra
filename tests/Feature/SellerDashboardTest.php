<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerDashboardTest extends TestCase
{
  use RefreshDatabase;

  public function test_seller_can_access_dashboard(): void
  {
    $seller = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($seller)->get('/seller/dashboard');

    $response->assertOk();
  }

  public function test_customer_cannot_access_seller_dashboard(): void
  {
    $customer = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($customer)->get('/seller/dashboard');

    $response->assertForbidden();
  }

  public function test_unauthenticated_user_cannot_access_seller_dashboard(): void
  {
    $response = $this->get('/seller/dashboard');

    $response->assertRedirect('/login');
  }
}
