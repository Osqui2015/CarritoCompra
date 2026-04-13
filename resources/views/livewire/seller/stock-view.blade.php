<div class="space-y-4">
    <div>
        <input type="text" wire:model.live="search" placeholder="Buscar producto..."
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">
                        <button wire:click="changeSortBy('name')" class="hover:text-slate-900">
                            Producto
                            @if ($sortBy === 'name')
                                {{ $sortOrder === 'asc' ? '↑' : '↓' }}
                            @endif
                        </button>
                    </th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">
                        <button wire:click="changeSortBy('stock')" class="hover:text-slate-900">
                            Stock
                            @if ($sortBy === 'stock')
                                {{ $sortOrder === 'asc' ? '↑' : '↓' }}
                            @endif
                        </button>
                    </th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">
                        <button wire:click="changeSortBy('price')" class="hover:text-slate-900">
                            Precio
                            @if ($sortBy === 'price')
                                {{ $sortOrder === 'asc' ? '↑' : '↓' }}
                            @endif
                        </button>
                    </th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="border-b border-slate-200 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $product->stock }}</td>
                        <td class="px-4 py-3 text-slate-700">${{ number_format($product->price, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($product->stock <= $threshold)
                                <span
                                    class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                    Stock bajo
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                                    Disponible
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-slate-500">No hay productos disponibles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">
        {{ $products->links() }}
    </div>
</div>
