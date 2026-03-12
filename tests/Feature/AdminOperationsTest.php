<?php

namespace Tests\Feature;

use App\Livewire\Admin\AppearanceManager;
use App\Models\AbandonedCart;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
  use RefreshDatabase;

  public function test_admin_can_open_the_new_management_pages(): void
  {
    $admin = User::factory()->create([
      'is_admin' => true,
    ]);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.abandoned-carts.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.stock.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.appearance.index'))->assertOk();
  }

  public function test_reminding_an_abandoned_cart_creates_a_single_use_coupon(): void
  {
    $admin = User::factory()->create([
      'is_admin' => true,
    ]);

    $customer = User::factory()->create([
      'phone' => '3001234567',
    ]);

    $abandonedCart = AbandonedCart::query()->create([
      'user_id' => $customer->id,
      'status' => 'open',
      'item_count' => 2,
      'subtotal' => 1200,
      'items_snapshot' => [
        ['product_id' => 1, 'name' => 'Teclado', 'quantity' => 2, 'price' => 600],
      ],
      'last_activity_at' => now(),
    ]);

    $this->actingAs($admin)
      ->post(route('admin.abandoned-carts.remind', $abandonedCart), [
        'discount_percent' => 15,
        'expires_in_days' => 7,
      ])
      ->assertRedirect();

    $abandonedCart->refresh();

    $this->assertSame('reminded', $abandonedCart->status);
    $this->assertNotNull($abandonedCart->reminder_coupon_id);
    $this->assertNotNull($abandonedCart->reminder_sent_at);

    $coupon = Coupon::query()->findOrFail($abandonedCart->reminder_coupon_id);

    $this->assertSame('percentage', $coupon->type);
    $this->assertSame('15.00', $coupon->value);
    $this->assertSame(1, $coupon->usage_limit);
  }

  public function test_stock_adjustment_updates_product_and_creates_a_movement(): void
  {
    $admin = User::factory()->create([
      'is_admin' => true,
    ]);

    $product = Product::factory()->create([
      'stock' => 3,
      'stock_reference' => 3,
    ]);

    $this->actingAs($admin)
      ->post(route('admin.stock.adjust'), [
        'product_id' => $product->id,
        'new_stock' => 10,
        'note' => 'Reposicion semanal',
      ])
      ->assertRedirect();

    $product->refresh();

    $this->assertSame(10, $product->stock);
    $this->assertDatabaseHas('stock_movements', [
      'product_id' => $product->id,
      'user_id' => $admin->id,
      'type' => 'restock',
      'quantity' => 7,
      'previous_stock' => 3,
      'new_stock' => 10,
    ]);
  }

  public function test_checkout_marks_abandoned_cart_as_recovered_and_logs_sale_movement(): void
  {
    $customer = User::factory()->create();

    $product = Product::factory()->create([
      'stock' => 8,
      'stock_reference' => 8,
      'price' => 100,
    ]);

    $abandonedCart = AbandonedCart::query()->create([
      'user_id' => $customer->id,
      'status' => 'open',
      'item_count' => 1,
      'subtotal' => 100,
      'items_snapshot' => [
        ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 100],
      ],
      'last_activity_at' => now(),
    ]);

    $this->actingAs($customer)
      ->post(route('carts.store'), [
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => $customer->phone,
        'shipping_address' => $customer->shipping_address,
        'items' => [
          [
            'product_id' => $product->id,
            'quantity' => 2,
          ],
        ],
      ])
      ->assertRedirect(route('storefront'));

    $abandonedCart->refresh();

    $this->assertSame('recovered', $abandonedCart->status);
    $this->assertNotNull($abandonedCart->recovered_at);

    $movement = StockMovement::query()->where('product_id', $product->id)->first();

    $this->assertNotNull($movement);
    $this->assertSame('sale', $movement->type);
    $this->assertSame(2, $movement->quantity);
    $this->assertSame(8, $movement->previous_stock);
    $this->assertSame(6, $movement->new_stock);
  }

  public function test_admin_can_save_branding_and_banners_from_livewire(): void
  {
    $admin = User::factory()->create([
      'is_admin' => true,
    ]);

    Livewire::actingAs($admin)
      ->test(AppearanceManager::class)
      ->set('siteName', 'Marca Demo')
      ->set('salesWhatsapp', '5491112345678')
      ->set('storeAddress', 'Calle 55 #10-20')
      ->set('businessHours', 'Lun a Vie 9 a 18 hs')
      ->set('siteLogo', UploadedFile::fake()->image('logo.png', 400, 200))
      ->set('siteFavicon', UploadedFile::fake()->image('favicon.png', 256, 256))
      ->call('saveBranding')
      ->assertHasNoErrors();

    $this->assertSame('Marca Demo', Setting::value('site_name'));
    $this->assertNotNull(Setting::value('site_logo'));
    $this->assertNotNull(Setting::value('site_favicon'));

    Livewire::actingAs($admin)
      ->test(AppearanceManager::class)
      ->set('bannerTitle', 'Promo Marzo')
      ->set('bannerSubtitle', 'Hasta 30% off en tecnologia')
      ->set('bannerType', Banner::TYPE_MAIN_LARGE)
      ->set('bannerSortOrder', 1)
      ->set('bannerImage', UploadedFile::fake()->image('banner.jpg', 1200, 600))
      ->call('saveBanner')
      ->assertHasNoErrors();

    $this->assertDatabaseHas('banners', [
      'title' => 'Promo Marzo',
      'type' => Banner::TYPE_MAIN_LARGE,
      'is_active' => true,
    ]);
  }

  public function test_admin_can_create_product_with_checked_and_new_secondary_category(): void
  {
    $admin = User::factory()->create([
      'is_admin' => true,
    ]);

    $primaryCategory = Category::factory()->create([
      'name' => 'Oficina',
      'slug' => 'oficina',
    ]);

    $existingSecondaryCategory = Category::factory()->create([
      'name' => 'Tecnologia',
      'slug' => 'tecnologia',
    ]);

    $this->actingAs($admin)
      ->post(route('admin.products.store'), [
        'name' => 'Silla ergonomica',
        'slug' => 'silla-ergonomica',
        'price' => 199.90,
        'stock' => 12,
        'is_active' => true,
        'category_id' => $primaryCategory->id,
        'category_ids' => [$existingSecondaryCategory->id],
        'secondary_category_name' => 'Estudio',
      ])
      ->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('slug', 'silla-ergonomica')->firstOrFail();

    $this->assertSame($primaryCategory->id, $product->category_id);

    $newSecondaryCategory = Category::query()
      ->whereRaw('LOWER(name) = ?', ['estudio'])
      ->first();

    $this->assertNotNull($newSecondaryCategory);

    $relatedCategoryIds = $product->categories()->pluck('categories.id')->all();

    $this->assertContains($primaryCategory->id, $relatedCategoryIds);
    $this->assertContains($existingSecondaryCategory->id, $relatedCategoryIds);
    $this->assertContains($newSecondaryCategory->id, $relatedCategoryIds);
  }

  public function test_admin_can_quick_load_a_secondary_category(): void
  {
    $admin = User::factory()->create([
      'is_admin' => true,
    ]);

    $this->actingAs($admin)
      ->postJson(route('admin.products.secondary-categories.store'), [
        'name' => 'Gaming',
      ])
      ->assertCreated()
      ->assertJsonPath('created', true)
      ->assertJsonPath('category.name', 'Gaming');

    $this->assertDatabaseHas('categories', [
      'slug' => 'gaming',
    ]);
  }

  public function test_quick_secondary_category_endpoint_reuses_existing_category(): void
  {
    $admin = User::factory()->create([
      'is_admin' => true,
    ]);

    $existingCategory = Category::factory()->create([
      'name' => 'Estudio',
      'slug' => 'estudio',
    ]);

    $this->actingAs($admin)
      ->postJson(route('admin.products.secondary-categories.store'), [
        'name' => 'estudio',
      ])
      ->assertOk()
      ->assertJsonPath('created', false)
      ->assertJsonPath('category.id', $existingCategory->id)
      ->assertJsonPath('category.name', $existingCategory->name);
  }
}
