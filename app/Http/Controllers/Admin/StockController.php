<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $settings = StoreSetting::current();

        $products = Product::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('stock')
            ->orderBy('name')
            ->get();

        $criticalProducts = $products
            ->filter(fn(Product $product): bool => $product->stock <= $settings->low_stock_threshold)
            ->values();

        $recentMovements = StockMovement::query()
            ->with(['product:id,name', 'user:id,name'])
            ->latest('id')
            ->limit(100)
            ->get();

        return Inertia::render('Admin/Stock/Index', [
            'filters' => [
                'search' => $search,
            ],
            'threshold' => $settings->low_stock_threshold,
            'critical_products' => $criticalProducts->map(fn(Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'stock_reference' => max((int) ($product->stock_reference ?? 0), (int) $product->stock, 1),
                'price' => round((float) $product->price, 2),
                'image_url' => $product->image_url,
            ])->values()->all(),
            'all_products' => $products->map(fn(Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'stock_reference' => max((int) ($product->stock_reference ?? 0), (int) $product->stock, 1),
            ])->values()->all(),
            'movements' => $recentMovements->map(fn(StockMovement $movement): array => [
                'id' => $movement->id,
                'product_name' => $movement->product?->name,
                'user_name' => $movement->user?->name,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'previous_stock' => $movement->previous_stock,
                'new_stock' => $movement->new_stock,
                'reference' => $movement->reference,
                'note' => $movement->note,
                'created_at' => $movement->created_at?->format('d/m/Y H:i'),
            ])->values()->all(),
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'new_stock' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail((int) $validated['product_id']);
        $previousStock = (int) $product->stock;
        $newStock = (int) $validated['new_stock'];

        if ($previousStock === $newStock) {
            return back()->with('success', 'No se detectaron cambios de stock.');
        }

        $product->fill([
            'stock' => $newStock,
            'stock_reference' => max((int) ($product->stock_reference ?? 0), $newStock, 1),
        ])->save();

        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'type' => $newStock > $previousStock ? 'restock' : 'manual_adjustment',
            'quantity' => abs($newStock - $previousStock),
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference' => 'admin-stock-adjustment',
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('success', "Stock actualizado para {$product->name}.");
    }

    public function updateThreshold(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $settings = StoreSetting::current();
        $settings->fill([
            'low_stock_threshold' => (int) $validated['low_stock_threshold'],
        ])->save();

        return back()->with('success', 'Umbral de stock critico actualizado.');
    }
}
