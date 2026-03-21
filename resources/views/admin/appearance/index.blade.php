<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin · Apariencia · {{ $branding['site_name'] ?? config('app.name', 'TUS TECNOLOGIAS') }}</title>
    @if (!empty($branding['site_favicon']))
        <link rel="icon" type="image/png" href="{{ $branding['site_favicon'] }}">
    @endif
    @vite(['resources/js/app.ts'])
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <img src="{{ asset('branding/logo.svg') }}" alt="Logo empresa"
                    class="h-12 w-auto rounded-xl object-contain object-center">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Panel de Administracion
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-950">
                        {{ $branding['site_name'] ?? 'TUS TECNOLOGIAS' }}</h1>
                </div>
            </div>

            <nav class="flex flex-wrap items-center gap-3 text-sm">
                <a href="{{ route('admin.dashboard') }}"
                    class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Analitica</a>
                <a href="{{ route('admin.products.index') }}"
                    class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Productos</a>
                <a href="{{ route('admin.coupons.index') }}"
                    class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Cupones</a>
                <a href="{{ route('admin.orders.index') }}"
                    class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Pedidos</a>
                <a href="{{ route('admin.abandoned-carts.index') }}"
                    class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Abandonados</a>
                <a href="{{ route('admin.stock.index') }}"
                    class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Stock</a>
                <span
                    class="rounded-full border border-slate-950 bg-slate-950 px-4 py-2 font-medium text-white">Apariencia</span>
                <a href="{{ route('storefront') }}"
                    class="rounded-full border border-emerald-600 px-4 py-2 font-semibold text-emerald-700 transition hover:bg-emerald-50">Ver
                    tienda</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="rounded-full border border-rose-300 px-4 py-2 font-semibold text-rose-700 transition hover:bg-rose-50">Cerrar
                        sesion</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <livewire:admin.store-info-manager />
        <div class="my-8"></div>
        <livewire:admin.appearance-manager />
    </main>

    @livewireScripts
</body>

</html>
