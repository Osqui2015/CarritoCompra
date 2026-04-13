<div>
    <h2 class="text-lg font-semibold text-slate-900">Foto de perfil</h2>
    <p class="mt-1 text-sm text-slate-600">Sube una imagen para personalizar tu cuenta.</p>

    @if (session()->has('avatar_status'))
        <div class="mt-4 rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ session('avatar_status') }}
        </div>
    @endif

    <div class="mt-4 flex items-center gap-4">
        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="Avatar" class="h-16 w-16 rounded-full object-cover">
        @else
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-200 text-slate-600">
                ?
            </div>
        @endif

        <form wire:submit="save" class="flex-1">
            <input type="file" wire:model="avatar" class="block w-full text-sm text-slate-700">
            @error('avatar')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit"
                class="mt-3 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Guardar foto
            </button>
        </form>
    </div>
</div>
