<form wire:submit="save" class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Preferencias</h2>
        <p class="mt-1 text-sm text-slate-600">Personaliza idioma y notificaciones.</p>
    </div>

    @if (session()->has('preferences_status'))
        <div class="rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ session('preferences_status') }}
        </div>
    @endif

    <div class="flex items-center gap-3">
        <input id="email_notifications" type="checkbox" wire:model.defer="email_notifications"
            class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
        <label for="email_notifications" class="text-sm text-slate-700">Recibir notificaciones por email</label>
    </div>
    @error('email_notifications')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    <div>
        <label class="block text-sm font-medium text-slate-700">Idioma</label>
        <select wire:model.defer="language"
            class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="es">Espanol</option>
            <option value="en">English</option>
        </select>
        @error('language')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
        Guardar preferencias
    </button>
</form>
