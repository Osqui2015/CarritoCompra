<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductShowController extends Controller
{
  public function __invoke(Product $product): Response
  {
    abort_if(! $product->is_active, 404);

    $product->loadMissing(['category', 'categories', 'media']);

    $relatedProducts = Product::query()
      ->with(['category', 'categories', 'media'])
      ->where('is_active', true)
      ->where('stock', '>', 0)
      ->where('id', '!=', $product->id)
      ->when($product->category_id, fn($query) => $query->where('category_id', $product->category_id))
      ->orderByDesc('updated_at')
      ->limit(4)
      ->get();

    if ($relatedProducts->count() < 4) {
      $missing = 4 - $relatedProducts->count();

      $fallbackProducts = Product::query()
        ->with(['category', 'categories', 'media'])
        ->where('is_active', true)
        ->where('stock', '>', 0)
        ->where('id', '!=', $product->id)
        ->whereNotIn('id', $relatedProducts->pluck('id'))
        ->orderByDesc('updated_at')
        ->limit($missing)
        ->get();

      $relatedProducts = $relatedProducts->concat($fallbackProducts);
    }

    return Inertia::render('Products/Show', [
      'product' => $this->toProductPayload($product),
      'related_products' => $relatedProducts
        ->map(function (Product $related): array {
          $categoryLinks = $this->resolveCategoryLinks($related);
          $categoryNames = collect($categoryLinks)->pluck('name')->values()->all();

          return [
            'id' => $related->id,
            'name' => $related->name,
            'slug' => $related->slug,
            'hero_tag' => $related->hero_tag,
            'description' => $related->description,
            'price' => round((float) $related->price, 2),
            'stock' => (int) $related->stock,
            'stock_reference' => max((int) ($related->stock_reference ?? 0), (int) $related->stock, 1),
            'image_url' => $related->image_url,
            'category_name' => $categoryNames[0] ?? null,
            'category_names' => $categoryNames,
            'category_links' => $categoryLinks,
          ];
        })
        ->values()
        ->all(),
      'breadcrumbs' => array_values(array_filter([
        'Inicio',
        $product->category?->name,
        $product->name,
      ])),
    ]);
  }

  /**
   * @return array<string, mixed>
   */
  private function toProductPayload(Product $product): array
  {
    $categoryLinks = $this->resolveCategoryLinks($product);
    $categoryNames = collect($categoryLinks)->pluck('name')->values()->all();

    return [
      'id' => $product->id,
      'name' => $product->name,
      'slug' => $product->slug,
      'hero_tag' => $product->hero_tag,
      'description' => $product->description,
      'price' => round((float) $product->price, 2),
      'stock' => (int) $product->stock,
      'stock_reference' => max((int) ($product->stock_reference ?? 0), (int) $product->stock, 1),
      'category_name' => $categoryNames[0] ?? null,
      'category_names' => $categoryNames,
      'category_links' => $categoryLinks,
      'image_url' => $product->image_url,
    ];
  }

  /**
   * @return array<int, array{name: string, href: string}>
   */
  private function resolveCategoryLinks(Product $product): array
  {
    $categories = $product->categories;

    if ($product->category !== null && ! $categories->contains('id', $product->category->id)) {
      $categories = $categories->prepend($product->category);
    }

    return $categories
      ->unique('id')
      ->map(fn($category): array => [
        'name' => $category->name,
        'href' => route('categories.show', $category),
      ])
      ->values()
      ->all();
  }
}
