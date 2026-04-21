<div class="space-y-4">
    <div class="flex gap-2">
        <button wire:click="$set('filterStatus', 'all')" @class([
            'px-3 py-2 rounded-md text-sm font-medium',
            'bg-slate-900 text-white' => $filterStatus === 'all',
            'bg-slate-200 text-slate-700 hover:bg-slate-300' => $filterStatus !== 'all',
        ])>
            Todos
        </button>
        <button wire:click="$set('filterStatus', 'pending')" @class([
            'px-3 py-2 rounded-md text-sm font-medium',
            'bg-slate-900 text-white' => $filterStatus === 'pending',
            'bg-slate-200 text-slate-700 hover:bg-slate-300' =>
                $filterStatus !== 'pending',
        ])>
            Pendientes
        </button>
        <button wire:click="$set('filterStatus', 'confirmed')" @class([
            'px-3 py-2 rounded-md text-sm font-medium',
            'bg-slate-900 text-white' => $filterStatus === 'confirmed',
            'bg-slate-200 text-slate-700 hover:bg-slate-300' =>
                $filterStatus !== 'confirmed',
        ])>
            Confirmados
        </button>
        <button wire:click="$set('filterStatus', 'delivered')" @class([
            'px-3 py-2 rounded-md text-sm font-medium',
            'bg-slate-900 text-white' => $filterStatus === 'delivered',
            'bg-slate-200 text-slate-700 hover:bg-slate-300' =>
                $filterStatus !== 'delivered',
        ])>
            Entregados
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">Código</th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">Cliente</th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">Total</th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">Estado</th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">Fecha</th>
                    <th class="border-b border-slate-200 px-4 py-2 text-left font-semibold text-slate-700">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b border-slate-200 hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-sm font-medium text-slate-900">{{ $order->code }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $order->customer_name }}</td>
                        <td class="px-4 py-3 text-slate-700 font-semibold">${{ number_format($order->total, 2) }}</td>
                        <td class="px-4 py-3">
                            @switch($order->status)
                                @case('submitted')
                                    <span
                                        class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700">
                                        Pendiente
                                    </span>
                                @break

                                @case('confirmed')
                                    <span
                                        class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                                        Confirmado
                                    </span>
                                @break

                                @case('delivered')
                                    <span
                                        class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                                        Entregado
                                    </span>
                                @break

                                @default
                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                        {{ ucfirst($order->status) }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $order->confirmed_at?->format('d/m/Y H:i') ?? $order->created_at?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('seller.orders.show', $order) }}"
                                class="rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900">
                                Ver
                            </a>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No hay pedidos en este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-center">
            {{ $orders->links() }}
        </div>
    </div>


