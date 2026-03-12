<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CategoryCatalogPdfController extends Controller
{
  public function show(Category $category): Response
  {
    $items = Product::query()
      ->with('media')
      ->where('is_active', true)
      ->where(function ($query) use ($category): void {
        $query
          ->where('category_id', $category->id)
          ->orWhereHas('categories', function ($categoryQuery) use ($category): void {
            $categoryQuery->where('categories.id', $category->id);
          });
      })
      ->orderBy('name')
      ->get()
      ->map(function (Product $product): array {
        $imagePath = $product->getFirstMediaPath('catalog', 'catalog-square');

        if ($imagePath === '') {
          $imagePath = $product->getFirstMediaPath('catalog');
        }

        $imageBase64 = null;
        $mimeType = 'image/png';

        if ($imagePath !== '' && File::exists($imagePath)) {
          $imageBase64 = base64_encode((string) File::get($imagePath));
          $mimeType = File::mimeType($imagePath) ?: 'image/png';
        }

        return [
          'name' => $product->name,
          'price' => round((float) $product->price, 2),
          'image_base64' => $imageBase64,
          'image_mime' => $mimeType,
        ];
      })
      ->values();

    return Pdf::loadView('pdfs.category-catalog', [
      'category' => $category,
      'items' => $items,
    ])->setPaper('a4')->stream("catalogo-{$category->slug}.pdf");
  }
}
