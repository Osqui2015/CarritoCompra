<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbandonedCartSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['synced' => false], 401);
        }

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $requestedItems = collect($validated['items'] ?? [])
            ->map(fn(array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->groupBy('product_id')
            ->map(fn($items, $productId): array => [
                'product_id' => (int) $productId,
                'quantity' => $items->sum('quantity'),
            ])
            ->values();

        $currentAbandonedCart = AbandonedCart::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'reminded'])
            ->latest('id')
            ->first();

        if ($requestedItems->isEmpty()) {
            if ($currentAbandonedCart) {
                $currentAbandonedCart->update([
                    'status' => 'cleared',
                    'item_count' => 0,
                    'subtotal' => 0,
                    'items_snapshot' => [],
                    'last_activity_at' => now(),
                ]);
            }

            return response()->json(['synced' => true, 'empty' => true]);
        }

        $products = Product::query()
            ->whereIn('id', $requestedItems->pluck('product_id'))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $snapshotItems = $requestedItems
            ->map(function (array $item) use ($products): ?array {
                /** @var Product|null $product */
                $product = $products->get($item['product_id']);

                if (! $product) {
                    return null;
                }

                $maxAvailable = (int) $product->stock;
                if ($maxAvailable <= 0) {
                    return null;
                }

                $quantity = max(1, min((int) $item['quantity'], $maxAvailable));
                $unitPrice = round((float) $product->price, 2);

                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                    'line_total' => round($unitPrice * $quantity, 2),
                    'image_url' => $product->image_url,
                ];
            })
            ->filter()
            ->values();

        if ($snapshotItems->isEmpty()) {
            return response()->json(['synced' => false], 422);
        }

        $itemCount = (int) $snapshotItems->sum('quantity');
        $subtotal = round((float) $snapshotItems->sum('line_total'), 2);

        $abandonedCart = $currentAbandonedCart
            ? tap($currentAbandonedCart)->update([
                'status' => 'open',
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'items_snapshot' => $snapshotItems->all(),
                'last_activity_at' => now(),
            ])
            : AbandonedCart::query()->create([
                'user_id' => $user->id,
                'status' => 'open',
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'items_snapshot' => $snapshotItems->all(),
                'last_activity_at' => now(),
            ]);

        return response()->json([
            'synced' => true,
            'abandoned_cart_id' => $abandonedCart->id,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
        ]);
    }
}
