<div class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto mt-8">
    <h2 class="text-2xl font-bold mb-6">Información del Negocio</h2>
    @if (session()->has('success'))
        <div class="mb-4 p-2 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                <input type="text" wire:model.defer="store_whatsapp" class="w-full border rounded p-2"
                    maxlength="30">
            </div>
            <div class="md:col-span-2">
                <label class="block font-medium">Dirección</label>
                <input type="text" wire:model.defer="store_address" class="w-full border rounded p-2"
                    maxlength="255">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium">Facebook</label>
                <input type="text" wire:model.defer="facebook_url" class="w-full border rounded p-2" maxlength="255"
                    placeholder="https://facebook.com/tuempresa">
            </div>
            <div>
                <label class="block font-medium">Instagram</label>
                <input type="text" wire:model.defer="instagram_url" class="w-full border rounded p-2" maxlength="255"
                    placeholder="https://instagram.com/tuempresa">
            </div>
            <div>
                <label class="block font-medium">TikTok</label>
                <input type="text" wire:model.defer="tiktok_url" class="w-full border rounded p-2" maxlength="255"
                    placeholder="https://tiktok.com/@tuempresa">
            </div>
            <div>
                <label class="block font-medium">YouTube</label>
                <input type="text" wire:model.defer="youtube_url" class="w-full border rounded p-2" maxlength="255"
                    placeholder="https://youtube.com/tuempresa">
            </div>
        </div>

        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-2">Banner principal</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium">Título</label>
                    <input type="text" wire:model.defer="hero_banner_title" class="w-full border rounded p-2"
                        maxlength="255">
                </div>
                <div>
                    <label class="block font-medium">Subtítulo</label>
                    <input type="text" wire:model.defer="hero_banner_subtitle" class="w-full border rounded p-2"
                        maxlength="255">
                </div>
                <div>
                    <label class="block font-medium">Tipo de enlace</label>
                    <select wire:model.defer="hero_banner_link_type" class="w-full border rounded p-2">
                        <option value="url">URL</option>
                        <option value="category">Categoría</option>
                        <option value="product">Producto</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium">Destino</label>
                    <input type="text" wire:model.defer="hero_banner_link_value" class="w-full border rounded p-2"
                        maxlength="255" placeholder="/ofertas o https://...">
                </div>
                <div class="md:col-span-2">
                    <label class="block font-medium">Imagen del banner</label>
                    <input type="file" wire:model="hero_banner_image" accept="image/*"
                        class="w-full border rounded p-2">
                    @if (!empty($bannerUrl))
                        <div class="mt-2">
                            <img src="{{ $bannerUrl }}" alt="Banner actual"
                                class="h-32 rounded shadow border object-contain">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8">
            <button type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">Guardar todo</button>
        </div>
    </form>
</div>
