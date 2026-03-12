<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Cart::query()
            ->with(['items.product.media', 'coupon', 'user'])
            ->where('status', 'confirmed')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders->map(fn(Cart $cart): array => [
                'id' => $cart->id,
                'code' => $cart->code,
                'customer_name' => $cart->customer_name,
                'customer_email' => $cart->customer_email,
                'customer_phone' => $cart->customer_phone,
                'shipping_address' => $cart->shipping_address,
                'notes' => $cart->notes,
                'status' => $cart->status,
                'subtotal' => round((float) $cart->subtotal, 2),
                'discount_amount' => round((float) $cart->discount_amount, 2),
                'total' => round((float) $cart->total, 2),
                'coupon_code' => $cart->coupon?->code,
                'confirmed_at' => $cart->confirmed_at?->format('d/m/Y H:i'),
                'created_at' => $cart->created_at?->format('d/m/Y H:i'),
                'items' => $cart->items->map(fn(CartItem $item): array => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => round((float) $item->unit_price, 2),
                    'line_total' => round((float) $item->line_total, 2),
                    'image_url' => $item->product?->image_url,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }
}
