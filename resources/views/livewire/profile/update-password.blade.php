<form wire:submit="save" class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Seguridad</h2>
        <p class="mt-1 text-sm text-slate-600">Cambia tu contrasena cuando lo necesites.</p>
    </div>

    @if (session()->has('password_status'))
        <div class="rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ session('password_status') }}
        </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-slate-700">Contrasena actual</label>
        <input type="password" wire:model.defer="current_password"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        @error('current_password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Nueva contrasena</label>
        <input type="password" wire:model.defer="password"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Confirmar nueva contrasena</label>
        <input type="password" wire:model.defer="password_confirmation"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
    </div>

    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
        Actualizar contrasena
    </button>
</form>
