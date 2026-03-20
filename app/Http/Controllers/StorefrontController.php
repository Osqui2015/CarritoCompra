<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $categories = Category::query()
            ->withCount([
                'relatedProducts as related_products_count' => fn($query) => $query
                    ->where('is_active', true)
                    ->where('stock', '>', 0),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $catalogProducts = Product::query()
            ->with([
                'category:id,name,slug',
                'categories:id,name,slug',
            ])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->get(['id', 'category_id']);

        $featuredProducts = Product::query()
            ->with(['category', 'categories', 'media'])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $mainBanners = Banner::query()
            ->currentlyVisible()
            ->where('type', Banner::TYPE_MAIN_LARGE)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sideBanners = Banner::query()
            ->currentlyVisible()
            ->where('type', Banner::TYPE_SIDE_SMALL)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(2)
            ->get();

        $user = $request->user();
        $settings = StoreSetting::current();

        $heroLinkUrl = $settings->hero_banner_link_value;
        if ($settings->hero_banner_link_type === 'category' && $settings->hero_banner_link_value !== null) {
            $category = $categories->firstWhere('slug', $settings->hero_banner_link_value);
            if ($category) {
                $heroLinkUrl = route('categories.show', $category);
            }
        }

        $navGroups = $catalogProducts
            ->filter(fn(Product $product): bool => $product->category !== null)
            ->groupBy('category_id')
            ->map(function ($productsByPrimary): ?array {
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

        if ($navGroups->isEmpty()) {
            $navGroups = $categories->map(fn(Category $category): array => [
                'label' => $category->name,
                'items' => [
                    ['label' => "Todos en {$category->name}", 'href' => route('categories.show', $category)],
                ],
            ])->values();
        }

        return Inertia::render('Storefront', [
            'categories' => $categories->map(fn(Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'accent_color' => $category->accent_color,
                'description' => $category->description,
                'product_count' => $category->related_products_count,
                'icon' => strtoupper(substr($category->name, 0, 1)),
                'catalog_pdf_url' => route('categories.catalog.pdf', $category),
            ])->values()->all(),
            'featured_products' => $featuredProducts->map(fn(Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'hero_tag' => $product->hero_tag,
                'is_featured' => trim((string) ($product->hero_tag ?? '')) !== '',
                'description' => $product->description,
                'price' => round((float) $product->price, 2),
                'stock' => $product->stock,
                'stock_reference' => max((int) ($product->stock_reference ?? 0), (int) $product->stock, 1),
                'category_name' => $product->category?->name,
                'category_links' => $this->resolveCategoryLinks($product),
                'image_url' => $product->image_url,
                'created_at' => $product->created_at?->toIso8601String(),
                'updated_at' => $product->updated_at?->toIso8601String(),
            ])->values()->all(),
            'hero_banners' => $mainBanners->map(fn(Banner $banner): array => [
                'id' => $banner->id,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'link_url' => $banner->link_url,
                'image_url' => $banner->image_url,
            ])->values()->all(),
            'side_banners' => $sideBanners->map(fn(Banner $banner): array => [
                'id' => $banner->id,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'link_url' => $banner->link_url,
                'image_url' => $banner->image_url,
            ])->values()->all(),
            'nav_groups' => $navGroups->all(),
            'promotions' => [
                [
                    'title' => 'Vuelta al cole mayorista',
                    'subtitle' => 'Combos de tecnologia y oficina con ahorro por volumen.',
                    'cta' => 'Ver ofertas',
                ],
                [
                    'title' => 'Envios a todo el pais',
                    'subtitle' => 'Logistica segura con seguimiento en tiempo real.',
                    'cta' => 'Conocer cobertura',
                ],
            ],
            'checkout_defaults' => [
                'customer_name' => $user?->name ?? '',
                'customer_email' => $user?->email ?? '',
                'customer_phone' => $user?->phone ?? '',
                'shipping_address' => $user?->shipping_address ?? '',
            ],
            'appearance' => [
                'hero_banner_title' => $settings->hero_banner_title,
                'hero_banner_subtitle' => $settings->hero_banner_subtitle,
                'hero_banner_image_url' => $settings->hero_banner_url,
                'hero_banner_link_url' => $heroLinkUrl,
                'store_address' => $settings->store_address,
                'business_hours' => $settings->business_hours,
                'sales_whatsapp' => $settings->sales_whatsapp,
                'store_name' => $settings->store_name,
                'store_email' => $settings->store_email,
                'store_phone' => $settings->store_phone,
                'store_whatsapp' => $settings->store_whatsapp,
            ],
            'abandoned_cart_sync_enabled' => $user !== null,
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
