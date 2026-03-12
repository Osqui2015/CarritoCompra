<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CouponValidationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:60'],
            'subtotal_cents' => ['required', 'integer', 'min:0'],
        ]);

        $couponCode = Str::upper(trim($validated['coupon_code']));

        $coupon = Coupon::query()->where('code', $couponCode)->first();

        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'El codigo no es valido o no esta vigente.',
            ], 422);
        }

        $discountCents = $coupon->calculateDiscountCents((int) $validated['subtotal_cents']);

        if ($discountCents <= 0) {
            return response()->json([
                'valid' => false,
                'message' => 'El codigo no aplica para el subtotal actual.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'coupon' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => round((float) $coupon->value, 2),
            ],
            'discount_cents' => $discountCents,
            'total_cents' => max(0, (int) $validated['subtotal_cents'] - $discountCents),
        ]);
    }
}
