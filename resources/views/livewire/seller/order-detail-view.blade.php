<div class="container mx-auto px-4 py-8">
    <div class="mb-4">
        <a href="{{ route('seller.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
            &larr; Volver al Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
        <div class="col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Checklist del Pedido</h2>

                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between rounded-md bg-slate-50 p-4">
                            <div>
                                <p class="font-medium text-slate-800">{{ $item->product->name }}</p>
                                <p class="text-sm text-slate-600">Cantidad: {{ $item->quantity }}</p>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" wire:model.live="checklist.{{ $item->id }}"
                                    id="item_{{ $item->id }}"
                                    class="h-5 w-5 rounded border-slate-300 text-slate-600 focus:ring-slate-500">
                                <label for="item_{{ $item->id }}"
                                    class="ml-2 text-sm text-slate-700">Encontrado</label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 text-right">
                    <button wire:click="confirmOrder" @disabled(!collect($checklist)->every(fn($checked) => $checked))
                        class="rounded-md bg-slate-800 px-4 py-2 font-medium text-white hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        Confirmar Pedido Armado
                    </button>
                </div>
            </div>
        </div>

        <div class="col-span-1">
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-slate-800">Detalles del Pedido</h3>
                <div class="space-y-3 text-sm">
                    <p class="flex justify-between">
                        <span class="font-medium text-slate-600">Código:</span>
                        <span class="font-mono text-slate-800">{{ $order->code }}</span>
                    </p>
                    <p class="flex justify-between">
                        <span class="font-medium text-slate-600">Cliente:</span>
                        <span class="text-slate-800">{{ $order->customer_name }}</span>
                    </p>
                    <p class="flex justify-between">
                        <span class="font-medium text-slate-600">Total:</span>
                        <span class="font-semibold text-slate-800">${{ number_format($order->total, 2) }}</span>
                    </p>
                    <p class="flex justify-between">
                        <span class="font-medium text-slate-600">Estado:</span>
                        <span>
                            @switch($order->status)
                                @case('submitted')
                                    <span
                                        class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Pendiente</span>
                                @break

                                @case('confirmed')
                                    <span
                                        class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">Confirmado</span>
                                @break

                                @case('delivered')
                                    <span
                                        class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Entregado</span>
                                @break

                                @default
                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800">{{ ucfirst($order->status) }}</span>
                            @endswitch
                        </span>
                    </p>
                    <p class="flex justify-between">
                        <span class="font-medium text-slate-600">Fecha:</span>
                        <span class="text-slate-800">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
