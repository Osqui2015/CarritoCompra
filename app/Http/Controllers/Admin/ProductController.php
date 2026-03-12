<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'status' => trim((string) $request->input('status', 'all')),
        ];

        $products = Product::query()
            ->with(['category', 'categories', 'media'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($innerQuery) use ($filters): void {
                    $innerQuery
                        ->where('name', 'like', "%{$filters['search']}%")
                        ->orWhere('slug', 'like', "%{$filters['search']}%");
                });
            })
            ->when($filters['category_id'] !== null, function ($query) use ($filters): void {
                $query->where(function ($innerQuery) use ($filters): void {
                    $innerQuery
                        ->where('category_id', $filters['category_id'])
                        ->orWhereHas('categories', function ($categoryQuery) use ($filters): void {
                            $categoryQuery->where('categories.id', $filters['category_id']);
                        });
                });
            })
            ->when($filters['status'] === 'active', fn($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn($query) => $query->where('is_active', false))
            ->orderByDesc('id')
            ->get();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Products/Index', [
            'filters' => $filters,
            'categories' => $categories->map(fn(Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values()->all(),
            'products' => $products->map(function (Product $product): array {
                $categoryIds = $product->categories->pluck('id');

                if ($product->category_id !== null && ! $categoryIds->contains($product->category_id)) {
                    $categoryIds = $categoryIds->prepend($product->category_id);
                }

                $secondaryCategoryIds = $categoryIds
                    ->filter(fn(int $categoryId): bool => $categoryId !== (int) $product->category_id)
                    ->values();

                $categoryNames = $product->categories->pluck('name');

                if ($product->category !== null && ! $categoryNames->contains($product->category->name)) {
                    $categoryNames = $categoryNames->prepend($product->category->name);
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'hero_tag' => $product->hero_tag,
                    'description' => $product->description,
                    'price' => round((float) $product->price, 2),
                    'stock' => $product->stock,
                    'stock_reference' => max((int) ($product->stock_reference ?? 0), (int) $product->stock, 1),
                    'is_active' => $product->is_active,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category?->name,
                    'category_ids' => $secondaryCategoryIds->all(),
                    'category_names' => $categoryNames->values()->all(),
                    'image_url' => $product->image_url,
                    'updated_at' => $product->updated_at?->format('d/m/Y H:i'),
                ];
            })->values()->all(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $categoryId = $this->resolveCategoryId($validated);
        $slug = $validated['slug'] ?? $this->generateUniqueSlug($validated['name']);
        $stock = (int) $validated['stock'];

        $product = Product::query()->create([
            'category_id' => $categoryId,
            'name' => $validated['name'],
            'slug' => $slug,
            'hero_tag' => $validated['hero_tag'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $stock,
            'stock_reference' => max(1, $stock),
            'is_active' => $validated['is_active'],
        ]);

        $product->categories()->sync($this->resolveCategoryIds($categoryId, $validated));

        if ($stock > 0) {
            StockMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $request->user()?->id,
                'type' => 'initial_stock',
                'quantity' => $stock,
                'previous_stock' => 0,
                'new_stock' => $stock,
                'reference' => 'admin-product-create',
                'note' => 'Stock inicial del producto.',
            ]);
        }

        if ($request->hasFile('image')) {
            $product->addMediaFromRequest('image')->toMediaCollection('catalog');
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $categoryId = $this->resolveCategoryId($validated);
        $slug = $validated['slug'] ?? $this->generateUniqueSlug($validated['name'], $product->id);
        $nextStock = (int) $validated['stock'];
        $previousStock = (int) $product->stock;
        $currentReference = max((int) ($product->stock_reference ?? 0), 1);

        $product->fill([
            'category_id' => $categoryId,
            'name' => $validated['name'],
            'slug' => $slug,
            'hero_tag' => $validated['hero_tag'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $nextStock,
            'stock_reference' => max($currentReference, $nextStock, 1),
            'is_active' => $validated['is_active'],
        ]);
        $product->save();

        $product->categories()->sync($this->resolveCategoryIds($categoryId, $validated));

        if ($nextStock !== $previousStock) {
            StockMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $request->user()?->id,
                'type' => $nextStock > $previousStock ? 'restock' : 'manual_adjustment',
                'quantity' => abs($nextStock - $previousStock),
                'previous_stock' => $previousStock,
                'new_stock' => $nextStock,
                'reference' => 'admin-product-update',
                'note' => 'Ajuste de stock desde edicion de producto.',
            ]);
        }

        if (! empty($validated['remove_image'])) {
            $product->clearMediaCollection('catalog');
        }

        if ($request->hasFile('image')) {
            $product->clearMediaCollection('catalog');
            $product->addMediaFromRequest('image')->toMediaCollection('catalog');
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto eliminado del catalogo.');
    }

    public function storeSecondaryCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $categoryName = trim((string) $validated['name']);

        if ($categoryName === '') {
            return response()->json([
                'message' => 'El nombre de la categoria secundaria es obligatorio.',
                'errors' => [
                    'name' => ['El nombre de la categoria secundaria es obligatorio.'],
                ],
            ], 422);
        }

        $existingCategory = Category::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])
            ->first();

        if ($existingCategory) {
            return response()->json([
                'created' => false,
                'category' => [
                    'id' => $existingCategory->id,
                    'name' => $existingCategory->name,
                ],
            ]);
        }

        $category = $this->resolveOrCreateCategory($categoryName);

        return response()->json([
            'created' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveCategoryId(array $validated): int
    {
        if (! empty($validated['category_id'])) {
            return (int) $validated['category_id'];
        }

        $categoryName = (string) ($validated['category_name'] ?? 'General');

        return $this->resolveOrCreateCategory($categoryName)->id;
    }

    private function resolveOrCreateCategory(string $categoryName): Category
    {
        $categoryName = trim($categoryName);

        if ($categoryName === '') {
            $categoryName = 'General';
        }

        $existingCategory = Category::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])
            ->first();

        if ($existingCategory) {
            return $existingCategory;
        }

        $baseSlug = Str::slug($categoryName);

        if ($baseSlug === '') {
            $baseSlug = 'categoria';
        }
        $slug = $baseSlug;
        $counter = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return Category::query()->create([
            'name' => $categoryName,
            'slug' => $slug,
            'description' => 'Categoria creada desde el panel de productos.',
            'accent_color' => '#1f7a8c',
            'sort_order' => (Category::query()->max('sort_order') ?? 0) + 1,
        ]);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'producto';
        }
        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::query()
            ->when($ignoreProductId !== null, fn($query) => $query->where('id', '!=', $ignoreProductId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int>
     */
    private function resolveCategoryIds(int $primaryCategoryId, array $validated): array
    {
        $categoryIds = collect($validated['category_ids'] ?? [])
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->reject(fn(int $id): bool => $id === $primaryCategoryId)
            ->values();

        $secondaryCategoryName = (string) ($validated['secondary_category_name'] ?? '');

        if ($secondaryCategoryName !== '') {
            $secondaryCategoryId = $this->resolveOrCreateCategory($secondaryCategoryName)->id;

            if ($secondaryCategoryId !== $primaryCategoryId) {
                $categoryIds->push($secondaryCategoryId);
            }
        }

        return $categoryIds
            ->push($primaryCategoryId)
            ->unique()
            ->values()
            ->all();
    }
}
