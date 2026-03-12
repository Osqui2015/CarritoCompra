<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class CategoryShowController extends Controller
{
  public function __invoke(Category $category): Response
  {
    $products = Product::query()
      ->with(['category', 'categories', 'media'])
      ->where('is_active', true)
      ->where('stock', '>', 0)
      ->where(function ($query) use ($category): void {
        $query
          ->where('category_id', $category->id)
          ->orWhereHas('categories', function ($categoryQuery) use ($category): void {
            $categoryQuery->where('categories.id', $category->id);
          });
      })
      ->orderByDesc('updated_at')
      ->get();

    return Inertia::render('Categories/Show', [
      'category' => [
        'id' => $category->id,
        'name' => $category->name,
        'slug' => $category->slug,
        'description' => $category->description,
        'accent_color' => $category->accent_color,
        'catalog_pdf_url' => route('categories.catalog.pdf', $category),
      ],
      'products' => $products->map(function (Product $product): array {
        $categoryLinks = $this->resolveCategoryLinks($product);

        return [
          'id' => $product->id,
          'name' => $product->name,
          'slug' => $product->slug,
          'hero_tag' => $product->hero_tag,
          'description' => $product->description,
          'price' => round((float) $product->price, 2),
          'stock' => (int) $product->stock,
          'stock_reference' => max((int) ($product->stock_reference ?? 0), (int) $product->stock, 1),
          'image_url' => $product->image_url,
          'category_names' => collect($categoryLinks)->pluck('name')->values()->all(),
          'category_links' => $categoryLinks,
        ];
      })->values()->all(),
      'breadcrumbs' => [
        'Inicio',
        $category->name,
      ],
    ]);
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
      ->map(fn(Category $category): array => [
        'name' => $category->name,
        'href' => route('categories.show', $category),
      ])
      ->values()
      ->all();
  }
}
