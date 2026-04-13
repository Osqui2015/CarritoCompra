<form wire:submit="deleteAccount" class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-red-700">Eliminar cuenta</h2>
        <p class="mt-1 text-sm text-slate-600">Esta accion no se puede deshacer. Se eliminaran tus datos personales.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Contrasena actual</label>
        <input type="password" wire:model.defer="password"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-red-400 focus:ring-red-400">
        @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
        Eliminar mi cuenta
    </button>
</form>
