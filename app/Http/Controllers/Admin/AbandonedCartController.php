<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Coupon;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AbandonedCartController extends Controller
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->input('status', 'open'));
        if (! in_array($status, ['open', 'reminded', 'recovered', 'all'], true)) {
            $status = 'open';
        }

        $items = AbandonedCart::query()
            ->with(['user:id,name,email,phone', 'reminderCoupon:id,code,expires_at'])
            ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->where('item_count', '>', 0)
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $settings = StoreSetting::current();

        return Inertia::render('Admin/AbandonedCarts/Index', [
            'filters' => [
                'status' => $status,
            ],
            'settings' => [
                'sales_whatsapp' => $settings->sales_whatsapp,
            ],
            'items' => $items->map(function (AbandonedCart $cart): array {
                $coupon = $cart->reminderCoupon;
                $message = $coupon
                    ? "Hola {$cart->user?->name}, dejamos reservado tu carrito. Usa el cupon {$coupon->code} en tu proxima compra."
                    : "Hola {$cart->user?->name}, tu carrito sigue esperandote con stock disponible.";

                return [
                    'id' => $cart->id,
                    'status' => $cart->status,
                    'item_count' => $cart->item_count,
                    'subtotal' => round((float) $cart->subtotal, 2),
                    'last_activity_at' => $cart->last_activity_at?->format('d/m/Y H:i'),
                    'reminder_sent_at' => $cart->reminder_sent_at?->format('d/m/Y H:i'),
                    'recovered_at' => $cart->recovered_at?->format('d/m/Y H:i'),
                    'coupon_code' => $coupon?->code,
                    'coupon_expires_at' => $coupon?->expires_at?->format('d/m/Y H:i'),
                    'user' => [
                        'id' => $cart->user?->id,
                        'name' => $cart->user?->name,
                        'email' => $cart->user?->email,
                        'phone' => $cart->user?->phone,
                    ],
                    'items_snapshot' => $cart->items_snapshot ?? [],
                    'whatsapp_url' => $this->buildWhatsAppUrl($cart->user?->phone, $message),
                ];
            })->values()->all(),
        ]);
    }

    public function remind(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'discount_percent' => ['nullable', 'numeric', 'min:1', 'max:90'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        if ($abandonedCart->user?->phone === null || trim($abandonedCart->user->phone) === '') {
            return back()->with('error', 'El cliente no tiene telefono cargado para WhatsApp.');
        }

        $discountPercent = (float) ($validated['discount_percent'] ?? 10);
        $expiresInDays = (int) ($validated['expires_in_days'] ?? 5);

        $coupon = $abandonedCart->reminderCoupon;
        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            $coupon = Coupon::query()->create([
                'code' => $this->generateUniqueCouponCode(),
                'type' => 'percentage',
                'value' => $discountPercent,
                'starts_at' => now(),
                'expires_at' => now()->addDays($expiresInDays),
                'is_active' => true,
                'usage_limit' => 1,
                'times_used' => 0,
            ]);
        }

        $abandonedCart->fill([
            'status' => 'reminded',
            'reminder_coupon_id' => $coupon->id,
            'reminder_sent_at' => now(),
        ])->save();

        return back()->with(
            'success',
            "Recordatorio preparado. Cupon {$coupon->code} ({$coupon->value}% hasta {$coupon->expires_at?->format('d/m')}).",
        );
    }

    public function markRecovered(AbandonedCart $abandonedCart): RedirectResponse
    {
        $abandonedCart->fill([
            'status' => 'recovered',
            'recovered_at' => now(),
        ])->save();

        return back()->with('success', 'Carrito marcado como recuperado.');
    }

    public function markCleared(AbandonedCart $abandonedCart): RedirectResponse
    {
        $abandonedCart->fill([
            'status' => 'cleared',
            'item_count' => 0,
            'subtotal' => 0,
            'items_snapshot' => null,
        ])->save();

        return back()->with('success', 'Carrito abandonado marcado como limpiado.');
    }

    private function generateUniqueCouponCode(): string
    {
        do {
            $code = 'RECUPERA-' . Str::upper(Str::random(6));
        } while (Coupon::query()->where('code', $code)->exists());

        return $code;
    }

    private function buildWhatsAppUrl(?string $phone, string $message): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $cleanPhone = preg_replace('/\D+/', '', $phone) ?: '';
        if ($cleanPhone === '') {
            return null;
        }

        return 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($message);
    }
}
