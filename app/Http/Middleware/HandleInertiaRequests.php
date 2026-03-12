<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn() => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'shipping_address' => $request->user()->shipping_address,
                    'is_admin' => $request->user()->is_admin,
                    'email_verified_at' => $request->user()->email_verified_at,
                ] : null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'cart' => fn() => $request->session()->get('cart'),
            ],
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'store_nav_groups' => fn() => $this->resolveStoreNavGroups(),
        ];
    }

    /**
     * @return array<int, array{label: string, items: array<int, array{label: string, href: string}>}>
     */
    private function resolveStoreNavGroups(): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('categories')) {
            return [];
        }

        $catalogProducts = Product::query()
            ->with([
                'category:id,name,slug',
                'categories:id,name,slug',
            ])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->get(['id', 'category_id']);

        $navGroups = $catalogProducts
            ->filter(fn(Product $product): bool => $product->category !== null)
            ->groupBy('category_id')
            ->map(function (Collection $productsByPrimary): ?array {
                /** @var Product|null $firstProduct */
                $firstProduct = $productsByPrimary->first();
                $primaryCategory = $firstProduct?->category;

                if (! $primaryCategory) {
                    return null;
                }

                $secondaryCategories = $productsByPrimary
                    ->flatMap(function (Product $product) use ($primaryCategory) {
                        return $product->categories
                            ->filter(fn(Category $category): bool => $category->id !== $primaryCategory->id);
                    })
                    ->unique('id')
                    ->sortBy('name')
                    ->values();

                $items = collect([
                    [
                        'label' => "Todos en {$primaryCategory->name}",
                        'href' => route('categories.show', $primaryCategory),
                    ],
                ])->concat(
                    $secondaryCategories->map(fn(Category $category): array => [
                        'label' => $category->name,
                        'href' => route('categories.show', $category),
                    ]),
                )
                    ->unique('href')
                    ->values();

                return [
                    'label' => $primaryCategory->name,
                    'items' => $items->all(),
                ];
            })
            ->filter()
            ->sortBy('label')
            ->values();

        if ($navGroups->isNotEmpty()) {
            return $navGroups->all();
        }

        return Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn(Category $category): array => [
                'label' => $category->name,
                'items' => [
                    [
                        'label' => "Todos en {$category->name}",
                        'href' => route('categories.show', $category),
                    ],
                ],
            ])
            ->values()
            ->all();
    }
}
