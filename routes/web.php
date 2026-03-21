<?php

use App\Http\Controllers\AbandonedCartSyncController;
use App\Http\Controllers\Admin\AbandonedCartController as AdminAbandonedCartController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartPdfController;
use App\Http\Controllers\CategoryShowController;
use App\Http\Controllers\CategoryCatalogPdfController;
use App\Http\Controllers\CouponValidationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductShowController;
use App\Http\Controllers\ProductSearchController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', StorefrontController::class)->name('storefront');
Route::get('/categorias/{category:slug}', CategoryShowController::class)->name('categories.show');
Route::get('/productos/buscar', ProductSearchController::class)->name('products.search');
Route::get('/productos/{product:slug}', ProductShowController::class)->name('products.show');
Route::get('/carrito/ver', function () {
    return Inertia::render('Cart/View');
})->name('cart.view');
Route::post('/carrito', [CartController::class, 'store'])->name('carts.store');
Route::post('/carrito/validar-cupon', CouponValidationController::class)->name('coupons.validate');
Route::get('/pedidos/{cart}/pdf', [CartPdfController::class, 'show'])->name('carts.pdf');
Route::get('/categorias/{category:slug}/catalogo.pdf', [CategoryCatalogPdfController::class, 'show'])->name('categories.catalog.pdf');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/carrito/abandono/sync', AbandonedCartSyncController::class)->name('abandoned-carts.sync');
});

Route::middleware(['auth', 'admin'])->prefix('/admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/analitica')->name('home');

    Route::get('/analitica', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analitica/exportar-ventas', [AdminDashboardController::class, 'exportSales'])->name('dashboard.export-sales');
    Route::get('/notificaciones/ultimo-pedido', [AdminDashboardController::class, 'latestOrderNotification'])->name('dashboard.latest-order');

    Route::get('/productos', [AdminProductController::class, 'index'])->name('products.index');
    Route::post('/productos', [AdminProductController::class, 'store'])->name('products.store');
    Route::post('/productos/categorias-secundarias', [AdminProductController::class, 'storeSecondaryCategory'])->name('products.secondary-categories.store');
    Route::put('/productos/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/productos/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/cupones', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::post('/cupones', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::put('/cupones/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/cupones/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

    Route::get('/pedidos', [AdminOrderController::class, 'index'])->name('orders.index');

    Route::get('/carritos-abandonados', [AdminAbandonedCartController::class, 'index'])->name('abandoned-carts.index');
    Route::post('/carritos-abandonados/{abandonedCart}/recordatorio', [AdminAbandonedCartController::class, 'remind'])->name('abandoned-carts.remind');
    Route::patch('/carritos-abandonados/{abandonedCart}/recuperado', [AdminAbandonedCartController::class, 'markRecovered'])->name('abandoned-carts.recovered');
    Route::patch('/carritos-abandonados/{abandonedCart}/limpiado', [AdminAbandonedCartController::class, 'markCleared'])->name('abandoned-carts.cleared');

    Route::get('/stock', [AdminStockController::class, 'index'])->name('stock.index');
    Route::post('/stock/ajustar', [AdminStockController::class, 'adjust'])->name('stock.adjust');
    Route::post('/stock/umbral', [AdminStockController::class, 'updateThreshold'])->name('stock.threshold');

    // Nueva sección para banners y datos de contacto/redes sociales
    Route::view('/info-negocio', 'admin.store-info-manager')->name('store-info');

    // Ruta de apariencia eliminada para restringir edición de logo/icono/colores
});

require __DIR__ . '/auth.php';
