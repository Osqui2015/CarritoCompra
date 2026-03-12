<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $rangeDays = (int) $request->input('range', 30);
        if (! in_array($rangeDays, [7, 30], true)) {
            $rangeDays = 30;
        }

        $settings = StoreSetting::current();

        $monthStart = now()->startOfMonth();
        $monthEnd = now();

        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $monthSales = (float) Cart::query()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$monthStart, $monthEnd])
            ->sum('total');

        $previousMonthSales = (float) Cart::query()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total');

        $monthOrdersCount = Cart::query()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$monthStart, $monthEnd])
            ->count();

        $avgTicket = $monthOrdersCount > 0
            ? round($monthSales / $monthOrdersCount, 2)
            : 0;

        if ($previousMonthSales > 0) {
            $salesGrowthPct = round((($monthSales - $previousMonthSales) / $previousMonthSales) * 100, 2);
        } elseif ($monthSales > 0) {
            $salesGrowthPct = 100;
        } else {
            $salesGrowthPct = 0;
        }

        $couponUsage = Cart::query()
            ->join('coupons', 'carts.coupon_id', '=', 'coupons.id')
            ->where('carts.status', 'confirmed')
            ->whereBetween('carts.confirmed_at', [$monthStart, $monthEnd])
            ->selectRaw('coupons.code as code, COUNT(*) as uses')
            ->groupBy('coupons.code')
            ->orderByDesc('uses')
            ->get();

        $topCoupon = $couponUsage->first();
        $couponOrdersCount = (int) $couponUsage->sum('uses');
        $couponConversionRate = $monthOrdersCount > 0
            ? round(($couponOrdersCount / $monthOrdersCount) * 100, 2)
            : 0;

        $chartStart = now()->subDays($rangeDays - 1)->startOfDay();
        $rawSalesByDate = Cart::query()
            ->where('status', 'confirmed')
            ->where('confirmed_at', '>=', $chartStart)
            ->selectRaw('DATE(confirmed_at) as sales_day, SUM(total) as sales_total')
            ->groupBy('sales_day')
            ->orderBy('sales_day')
            ->get()
            ->keyBy('sales_day');

        $chartLabels = [];
        $chartTotals = [];

        for ($day = $chartStart->copy(); $day->lte(now()->endOfDay()); $day->addDay()) {
            $key = $day->format('Y-m-d');
            $chartLabels[] = $day->format('d/m');
            $chartTotals[] = round((float) ($rawSalesByDate->get($key)->sales_total ?? 0), 2);
        }

        $topProducts = Product::query()
            ->leftJoin('cart_items', 'products.id', '=', 'cart_items.product_id')
            ->leftJoin('carts', function ($join): void {
                $join->on('cart_items.cart_id', '=', 'carts.id')
                    ->where('carts.status', 'confirmed');
            })
            ->select('products.id', 'products.name')
            ->selectRaw('COALESCE(SUM(CASE WHEN carts.id IS NOT NULL THEN cart_items.quantity ELSE 0 END), 0) as sold_qty')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold_qty')
            ->limit(5)
            ->get();

        $sinceDays = now()->subDays(60);
        $productsWithoutMovement = Product::query()
            ->where('is_active', true)
            ->whereDoesntHave('cartItems.cart', function ($query) use ($sinceDays): void {
                $query->where('status', 'confirmed')
                    ->where('confirmed_at', '>=', $sinceDays);
            })
            ->orderBy('name')
            ->limit(12)
            ->get();

        $criticalProducts = Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', $settings->low_stock_threshold)
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(12)
            ->get();

        $recentMovements = StockMovement::query()
            ->with(['product:id,name', 'user:id,name'])
            ->latest('id')
            ->limit(12)
            ->get();

        $abandonedOpenCount = AbandonedCart::query()
            ->whereIn('status', ['open', 'reminded'])
            ->where('item_count', '>', 0)
            ->count();

        return Inertia::render('Admin/Dashboard/Index', [
            'range' => $rangeDays,
            'summary' => [
                'month_sales' => round($monthSales, 2),
                'previous_month_sales' => round($previousMonthSales, 2),
                'sales_growth_pct' => $salesGrowthPct,
                'avg_ticket' => $avgTicket,
                'month_orders_count' => $monthOrdersCount,
                'coupon_conversion_rate' => $couponConversionRate,
                'top_coupon' => $topCoupon ? [
                    'code' => $topCoupon->code,
                    'uses' => (int) $topCoupon->uses,
                ] : null,
                'abandoned_open_count' => $abandonedOpenCount,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'totals' => $chartTotals,
            ],
            'top_products' => $topProducts->map(fn($row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'sold_qty' => (int) $row->sold_qty,
            ])->values()->all(),
            'stale_products' => $productsWithoutMovement->map(fn(Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'stock_reference' => $product->stock_reference,
                'price' => round((float) $product->price, 2),
            ])->values()->all(),
            'critical_products' => $criticalProducts->map(fn(Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'stock_reference' => $product->stock_reference,
            ])->values()->all(),
            'recent_movements' => $recentMovements->map(fn(StockMovement $movement): array => [
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
            'low_stock_threshold' => $settings->low_stock_threshold,
        ]);
    }

    public function exportSales(Request $request): BinaryFileResponse
    {
        $days = (int) $request->input('days', 30);
        if (! in_array($days, [7, 30, 60, 90], true)) {
            $days = 30;
        }

        return Excel::download(
            new SalesReportExport($days),
            'reporte-ventas-' . $days . 'd-' . now()->format('Ymd_His') . '.xlsx',
        );
    }

    public function latestOrderNotification(): JsonResponse
    {
        $latestOrder = Cart::query()
            ->where('status', 'confirmed')
            ->orderByDesc('confirmed_at')
            ->first();

        if (! $latestOrder) {
            return response()->json(['latest' => null]);
        }

        return response()->json([
            'latest' => [
                'id' => $latestOrder->id,
                'code' => $latestOrder->code,
                'total' => round((float) $latestOrder->total, 2),
                'confirmed_at' => $latestOrder->confirmed_at?->toIso8601String(),
            ],
        ]);
    }
}
