<?php

namespace Tests\Feature;

use App\Livewire\Profile\Preferences;
use App\Livewire\Profile\UpdatePassword;
use App\Livewire\Profile\UpdateProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountLivewireTest extends TestCase
{
  use RefreshDatabase;

  public function test_account_page_is_displayed(): void
  {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/account');

    $response->assertOk();
  }

  public function test_profile_information_can_be_updated_from_livewire_component(): void
  {
    $user = User::factory()->create();

    Livewire::actingAs($user)
      ->test(UpdateProfileInformation::class)
      ->set('name', 'Nuevo Nombre')
      ->set('email', 'nuevo@example.com')
      ->set('phone', '111222333')
      ->set('shipping_address', 'Calle Falsa 123')
      ->call('save')
      ->assertHasNoErrors();

    $user->refresh();

    $this->assertSame('Nuevo Nombre', $user->name);
    $this->assertSame('nuevo@example.com', $user->email);
    $this->assertSame('111222333', $user->phone);
    $this->assertSame('Calle Falsa 123', $user->shipping_address);
  }

  public function test_password_can_be_updated_from_livewire_component(): void
  {
    $user = User::factory()->create();

    Livewire::actingAs($user)
      ->test(UpdatePassword::class)
      ->set('current_password', 'password')
      ->set('password', 'NewSecurePassword123')
      ->set('password_confirmation', 'NewSecurePassword123')
      ->call('save')
      ->assertHasNoErrors();

    $this->assertTrue(password_verify('NewSecurePassword123', $user->refresh()->password));
  }

  public function test_preferences_can_be_updated_from_livewire_component(): void
  {
    $user = User::factory()->create();

    Livewire::actingAs($user)
      ->test(Preferences::class)
      ->set('email_notifications', false)
      ->set('language', 'en')
      ->call('save')
      ->assertHasNoErrors();

    $this->assertEquals([
      'email_notifications' => false,
      'language' => 'en',
    ], $user->refresh()->preferences);
  }
}
