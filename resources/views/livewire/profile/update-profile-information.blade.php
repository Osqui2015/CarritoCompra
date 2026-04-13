<form wire:submit="save" class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Informacion personal</h2>
        <p class="mt-1 text-sm text-slate-600">Actualiza tus datos de contacto y envio.</p>
    </div>

    @if (session()->has('profile_status'))
        <div class="rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ session('profile_status') }}
        </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-slate-700">Nombre</label>
        <input type="text" wire:model.defer="name"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" wire:model.defer="email"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Telefono</label>
        <input type="text" wire:model.defer="phone"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        @error('phone')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Direccion de envio</label>
        <input type="text" wire:model.defer="shipping_address"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        @error('shipping_address')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
        Guardar cambios
    </button>
</form>
