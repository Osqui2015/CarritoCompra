<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => trim((string) $request->input('status', 'all')),
        ];

        $now = now();

        $coupons = Coupon::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where('code', 'like', "%{$filters['search']}%");
            })
            ->when($filters['status'] === 'active', function ($query) use ($now): void {
                $query
                    ->where('is_active', true)
                    ->where(fn($innerQuery) => $innerQuery->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn($innerQuery) => $innerQuery->whereNull('expires_at')->orWhere('expires_at', '>=', $now));
            })
            ->when($filters['status'] === 'inactive', fn($query) => $query->where('is_active', false))
            ->when($filters['status'] === 'expired', fn($query) => $query->whereNotNull('expires_at')->where('expires_at', '<', $now))
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/Coupons/Index', [
            'filters' => $filters,
            'coupons' => $coupons->map(fn(Coupon $coupon): array => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => round((float) $coupon->value, 2),
                'starts_at' => $coupon->starts_at?->format('Y-m-d\TH:i'),
                'expires_at' => $coupon->expires_at?->format('Y-m-d\TH:i'),
                'is_active' => $coupon->is_active,
                'usage_limit' => $coupon->usage_limit,
                'times_used' => $coupon->times_used,
                'is_valid_now' => $coupon->isCurrentlyValid(),
                'updated_at' => $coupon->updated_at?->format('d/m/Y H:i'),
            ])->values()->all(),
        ]);
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Coupon::query()->create([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'],
            'usage_limit' => $validated['usage_limit'] ?? null,
            'times_used' => 0,
        ]);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Cupon creado correctamente.');
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validated();

        $coupon->fill([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'],
            'usage_limit' => $validated['usage_limit'] ?? null,
        ]);
        $coupon->save();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Cupon actualizado correctamente.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Cupon eliminado.');
    }
}
