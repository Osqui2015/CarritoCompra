<div class="bg-white rounded-lg shadow p-6 max-w-xl mx-auto mt-8">
    <h2 class="text-xl font-bold mb-4">Información del Negocio</h2>
    @if (session()->has('success'))
        <div class="mb-4 p-2 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif
    <form wire:submit.prevent="save" class="space-y-4">
        <div>
            <label class="block font-medium">Nombre del negocio</label>
            <input type="text" wire:model.defer="store_name" class="w-full border rounded p-2" maxlength="255">
        </div>
        <div>
            <label class="block font-medium">Correo electrónico</label>
            <input type="email" wire:model.defer="store_email" class="w-full border rounded p-2" maxlength="255">
        </div>
        <div>
            <label class="block font-medium">Teléfono</label>
            <input type="text" wire:model.defer="store_phone" class="w-full border rounded p-2" maxlength="30">
        </div>
        <div>
            <label class="block font-medium">WhatsApp</label>
            <input type="text" wire:model.defer="store_whatsapp" class="w-full border rounded p-2" maxlength="30">
        </div>
        <div>
            <label class="block font-medium">Dirección</label>
            <input type="text" wire:model.defer="store_address" class="w-full border rounded p-2" maxlength="255">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
    </form>
</div>
