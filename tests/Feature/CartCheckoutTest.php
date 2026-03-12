<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
  use RefreshDatabase;

  public function test_a_cart_can_be_stored_from_the_storefront(): void
  {
    $this->seed(CatalogSeeder::class);

    $product = Product::query()->firstOrFail();

    $response = $this->post(route('carts.store'), [
      'customer_name' => 'Maria Gomez',
      'customer_email' => 'maria@example.com',
      'customer_phone' => '3001234567',
      'shipping_address' => 'Calle 10 #20-30',
      'notes' => 'Entregar despues de las 3pm',
      'items' => [
        [
          'product_id' => $product->id,
          'quantity' => 2,
        ],
      ],
    ]);

    $response->assertRedirect(route('storefront'));

    $this->assertDatabaseHas('carts', [
      'customer_email' => 'maria@example.com',
      'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('cart_items', [
      'product_id' => $product->id,
      'quantity' => 2,
    ]);
  }

  public function test_the_pdf_endpoint_returns_a_document_for_a_saved_cart(): void
  {
    $this->seed(CatalogSeeder::class);

    $product = Product::query()->firstOrFail();

    $this->post(route('carts.store'), [
      'customer_name' => 'Maria Gomez',
      'customer_email' => 'maria@example.com',
      'customer_phone' => '3001234567',
      'shipping_address' => 'Calle 10 #20-30',
      'notes' => 'Entregar despues de las 3pm',
      'items' => [
        [
          'product_id' => $product->id,
          'quantity' => 1,
        ],
      ],
    ]);

    $cart = Cart::query()->firstOrFail();

    $response = $this->get(route('carts.pdf', $cart));

    $response->assertOk();
    $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
  }

  public function test_a_valid_coupon_is_applied_to_the_checkout_total(): void
  {
    $this->seed(CatalogSeeder::class);

    $product = Product::query()->firstOrFail();
    $coupon = Coupon::factory()->create([
      'code' => 'DESC10',
      'type' => 'percentage',
      'value' => 10,
      'starts_at' => now()->subDay(),
      'expires_at' => now()->addDay(),
      'is_active' => true,
    ]);

    $this->post(route('carts.store'), [
      'customer_name' => 'Maria Gomez',
      'customer_email' => 'maria@example.com',
      'customer_phone' => '3001234567',
      'shipping_address' => 'Calle 10 #20-30',
      'coupon_code' => $coupon->code,
      'items' => [
        [
          'product_id' => $product->id,
          'quantity' => 1,
        ],
      ],
    ])->assertRedirect(route('storefront'));

    $cart = Cart::query()->firstOrFail();

    $expectedSubtotal = round((float) $product->price, 2);
    $expectedDiscount = round($expectedSubtotal * 0.10, 2);
    $expectedTotal = round($expectedSubtotal - $expectedDiscount, 2);

    $this->assertSame($expectedSubtotal, round((float) $cart->subtotal, 2));
    $this->assertSame($expectedDiscount, round((float) $cart->discount_amount, 2));
    $this->assertSame($expectedTotal, round((float) $cart->total, 2));
    $this->assertSame($coupon->id, $cart->coupon_id);
  }

  public function test_a_category_catalog_pdf_can_be_downloaded(): void
  {
    $this->seed(CatalogSeeder::class);

    $category = Category::query()->firstOrFail();

    $response = $this->get(route('categories.catalog.pdf', $category));

    $response->assertOk();
    $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
  }

  public function test_a_product_detail_page_can_be_rendered(): void
  {
    $this->seed(CatalogSeeder::class);

    $product = Product::query()->firstOrFail();

    $response = $this->get(route('products.show', ['product' => $product->slug]));

    $response->assertOk();
    $response->assertSee($product->name);
  }

  public function test_a_category_page_shows_products_and_pdf_action(): void
  {
    $this->seed(CatalogSeeder::class);

    $category = Category::query()->firstOrFail();

    $response = $this->get(route('categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(
      fn(Assert $page) => $page
        ->component('Categories/Show')
        ->where('category.slug', $category->slug)
        ->where('category.name', $category->name)
        ->has('category.catalog_pdf_url')
        ->has('products')
    );
  }

  public function test_a_product_can_appear_in_multiple_categories(): void
  {
    $this->seed(CatalogSeeder::class);

    $primaryCategory = Category::query()->firstOrFail();
    $secondaryCategory = Category::query()
      ->where('id', '!=', $primaryCategory->id)
      ->firstOrFail();

    $product = Product::query()
      ->where('category_id', $primaryCategory->id)
      ->firstOrFail();

    $product->categories()->sync([$primaryCategory->id, $secondaryCategory->id]);

    $response = $this->get(route('categories.show', ['category' => $secondaryCategory->slug]));

    $response->assertOk();
    $response->assertSee($product->name);
  }

  public function test_storefront_navigation_includes_secondary_categories_under_primary_group(): void
  {
    $this->seed(CatalogSeeder::class);

    $primaryCategory = Category::query()->firstOrFail();
    $secondaryCategory = Category::query()
      ->where('id', '!=', $primaryCategory->id)
      ->firstOrFail();

    $product = Product::query()
      ->where('category_id', $primaryCategory->id)
      ->firstOrFail();

    $product->categories()->sync([$primaryCategory->id, $secondaryCategory->id]);

    $response = $this->get(route('storefront'));

    $response->assertOk();
    $response->assertInertia(
      fn(Assert $page) => $page
        ->component('Storefront')
        ->has('nav_groups')
    );

    $inertiaPage = $response->viewData('page');
    $navGroups = collect(data_get($inertiaPage, 'props.nav_groups', []));
    $primaryGroup = $navGroups->firstWhere('label', $primaryCategory->name);

    $this->assertNotNull($primaryGroup);

    $groupItems = collect($primaryGroup['items'] ?? []);

    $this->assertTrue(
      $groupItems->contains(fn(array $item): bool => ($item['label'] ?? null) === $secondaryCategory->name),
      'La categoria secundaria no aparece dentro del grupo principal en la navegacion.',
    );
  }

  public function test_product_search_requires_at_least_three_letters(): void
  {
    $this->seed(CatalogSeeder::class);

    $response = $this->getJson(route('products.search', ['q' => 'te']));

    $response
      ->assertOk()
      ->assertJson([
        'results' => [],
      ]);
  }

  public function test_product_search_returns_matching_products(): void
  {
    $this->seed(CatalogSeeder::class);

    $product = Product::query()
      ->where('name', 'like', '%Teclado%')
      ->firstOrFail();

    $response = $this->getJson(route('products.search', ['q' => 'tec']));

    $response->assertOk();

    $results = collect($response->json('results', []));

    $this->assertTrue(
      $results->contains(fn(array $item): bool => (int) ($item['id'] ?? 0) === $product->id),
      'La busqueda no devolvio el producto esperado.',
    );
  }

  public function test_storefront_exposes_all_active_products_for_client_pagination(): void
  {
    $this->seed(CatalogSeeder::class);

    Product::factory()->count(15)->create([
      'is_active' => true,
      'stock' => 25,
      'stock_reference' => 25,
    ]);

    $expectedActiveCount = Product::query()
      ->where('is_active', true)
      ->where('stock', '>', 0)
      ->count();

    $response = $this->get(route('storefront'));

    $response->assertOk();

    $inertiaPage = $response->viewData('page');
    $products = collect(data_get($inertiaPage, 'props.featured_products', []));

    $this->assertCount($expectedActiveCount, $products);

    $firstProduct = $products->first();

    $this->assertNotNull($firstProduct);
    $this->assertArrayHasKey('is_featured', $firstProduct);
    $this->assertArrayHasKey('created_at', $firstProduct);
    $this->assertArrayHasKey('updated_at', $firstProduct);
  }
}
