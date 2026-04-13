<div class="min-h-screen bg-slate-100 py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h1 class="text-3xl font-bold text-slate-900">Panel del Vendedor</h1>
            <p class="mt-2 text-sm text-slate-600">Visualiza tu stock y pedidos pendientes/confirmados.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Stock de productos</h2>
                <livewire:seller.seller-stock-view />
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Pedidos</h2>
                <livewire:seller.seller-orders-view />
            </div>
        </div>
    </div>
</div>
