<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartRequest;
use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function store(StoreCartRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $requestedItems = collect($validated['items'])
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

        $couponCode = $validated['coupon_code'] ?? null;
        $userId = $request->user()?->id;

        $cart = DB::transaction(function () use ($validated, $requestedItems, $couponCode, $userId): Cart {
            $products = Product::query()
                ->whereIn('id', $requestedItems->pluck('product_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $requestedItems->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Uno de los productos seleccionados ya no esta disponible.',
                ]);
            }

            $lineItems = $requestedItems->map(function (array $item) use ($products): array {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $quantity = $item['quantity'];

                if ($quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        'items' => "La cantidad solicitada para {$product->name} supera el stock disponible.",
                    ]);
                }

                $unitPriceCents = (int) round((float) $product->price * 100);
                $lineTotalCents = $unitPriceCents * $quantity;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => round($unitPriceCents / 100, 2),
                    'line_total' => round($lineTotalCents / 100, 2),
                    'line_total_cents' => $lineTotalCents,
                ];
            });

            $subtotalCents = (int) $lineItems->sum('line_total_cents');

            $coupon = null;
            $discountCents = 0;

            if ($couponCode !== null) {
                $coupon = Coupon::query()
                    ->where('code', $couponCode)
                    ->lockForUpdate()
                    ->first();

                if (! $coupon || ! $coupon->isCurrentlyValid()) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'El codigo de descuento no es valido o ya no esta vigente.',
                    ]);
                }

                $discountCents = $coupon->calculateDiscountCents($subtotalCents);

                if ($discountCents <= 0) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'El codigo no aplica para el subtotal actual.',
                    ]);
                }
            }

            $totalCents = max(0, $subtotalCents - $discountCents);

            $cart = Cart::query()->create([
                'user_id' => $userId,
                'code' => $this->generateCartCode(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'coupon_id' => $coupon?->id,
                'subtotal' => round($subtotalCents / 100, 2),
                'discount_amount' => round($discountCents / 100, 2),
                'total' => round($totalCents / 100, 2),
            ]);

            $cart->items()->createMany(
                $lineItems->map(fn(array $item): array => [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ])->all(),
            );

            foreach ($lineItems as $lineItem) {
                /** @var Product $product */
                $product = $products->get($lineItem['product_id']);
                $previousStock = (int) $product->stock;
                $newStock = max(0, $previousStock - $lineItem['quantity']);

                $product->fill([
                    'stock' => $newStock,
                ])->save();

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'sale',
                    'quantity' => (int) $lineItem['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reference' => $cart->code,
                    'note' => 'Descuento por venta confirmada.',
                ]);
            }

            if ($coupon) {
                $coupon->increment('times_used');
            }

            if ($userId !== null) {
                AbandonedCart::query()
                    ->where('user_id', $userId)
                    ->whereIn('status', ['open', 'reminded'])
                    ->where('item_count', '>', 0)
                    ->update([
                        'status' => 'recovered',
                        'recovered_at' => now(),
                    ]);
            }

            return $cart;
        });

        return redirect()
            ->route('storefront')
            ->with('success', "Pedido {$cart->code} generado correctamente.")
            ->with('cart', [
                'code' => $cart->code,
                'pdf_url' => route('carts.pdf', $cart),
                'subtotal' => round((float) $cart->subtotal, 2),
                'discount_amount' => round((float) $cart->discount_amount, 2),
                'total' => round((float) $cart->total, 2),
                'coupon_code' => $cart->coupon?->code,
            ]);
    }

    private function generateCartCode(): string
    {
        do {
            $code = 'CAR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Cart::query()->where('code', $code)->exists());

        return $code;
    }
}
