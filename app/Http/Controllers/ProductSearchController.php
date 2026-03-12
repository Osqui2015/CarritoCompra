<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
  public function __invoke(Request $request): JsonResponse
  {
    $query = trim((string) $request->query('q', ''));

    if (mb_strlen($query) < 3) {
      return response()->json([
        'results' => [],
      ]);
    }

    $products = Product::query()
      ->with(['category', 'categories', 'media'])
      ->where('is_active', true)
      ->where('stock', '>', 0)
      ->where(function ($productQuery) use ($query): void {
        $productQuery
          ->where('name', 'like', "%{$query}%")
          ->orWhere('slug', 'like', "%{$query}%")
          ->orWhere('hero_tag', 'like', "%{$query}%")
          ->orWhere('description', 'like', "%{$query}%");
      })
      ->orderByDesc('updated_at')
      ->limit(8)
      ->get();

    return response()->json([
      'results' => $products->map(function (Product $product): array {
        $categoryNames = $product->categories->pluck('name');

        if ($product->category !== null && ! $categoryNames->contains($product->category->name)) {
          $categoryNames = $categoryNames->prepend($product->category->name);
        }

        return [
          'id' => $product->id,
          'name' => $product->name,
          'slug' => $product->slug,
          'price' => round((float) $product->price, 2),
          'image_url' => $product->image_url,
          'category_names' => $categoryNames->values()->all(),
          'href' => route('products.show', $product),
        ];
      })->values()->all(),
    ]);
  }
}
