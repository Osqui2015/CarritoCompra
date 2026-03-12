<div class="space-y-8">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">Branding global</h2>
            <p class="mt-1 text-sm text-slate-500">
                El logo se refleja en navbar, layouts y correos HTML. El favicon se publica en toda la app.
            </p>

            <form class="mt-6 space-y-5" wire:submit="saveBranding">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nombre de la marca</label>
                    <input wire:model.live="siteName" type="text"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('siteName')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Logo</label>
                        <p class="mb-2 text-xs text-slate-500">Sugerido: 320x120 px. Se recorta automaticamente.</p>
                        <input wire:model.live="siteLogo" type="file" accept="image/*"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" />
                        @error('siteLogo')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                            <input wire:model.live="removeSiteLogo" type="checkbox" class="rounded border-slate-300" />
                            Quitar logo actual
                        </label>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Favicon</label>
                        <p class="mb-2 text-xs text-slate-500">Sugerido: 256x256 px. Minimo: 64x64 px.</p>
                        <input wire:model.live="siteFavicon" type="file" accept="image/*"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" />
                        @error('siteFavicon')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                            <input wire:model.live="removeSiteFavicon" type="checkbox"
                                class="rounded border-slate-300" />
                            Quitar favicon actual
                        </label>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">WhatsApp ventas</label>
                        <input wire:model.live="salesWhatsapp" type="text"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            placeholder="54911..." />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Direccion comercial</label>
                        <input wire:model.live="storeAddress" type="text"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Horario</label>
                    <input wire:model.live="businessHours" type="text"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Lun a Vie 9:00 a 18:00" />
                </div>

                <button type="submit" class="rounded-full bg-slate-950 px-5 py-2 text-sm font-semibold text-white">
                    Guardar branding
                </button>
            </form>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Previsualizacion</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Logo</p>
                    <div
                        class="mt-2 flex h-24 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50">
                        @if ($siteLogo)
                            <img src="{{ $siteLogo->temporaryUrl() }}" alt="Preview logo"
                                class="h-full w-full object-contain object-center" />
                        @elseif ($currentLogoUrl && !$removeSiteLogo)
                            <img src="{{ $currentLogoUrl }}" alt="Logo actual"
                                class="h-full w-full object-contain object-center" />
                        @else
                            <span class="text-sm text-slate-400">Sin logo configurado</span>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Favicon</p>
                    <div
                        class="mt-2 flex h-24 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50">
                        @if ($siteFavicon)
                            <img src="{{ $siteFavicon->temporaryUrl() }}" alt="Preview favicon"
                                class="h-16 w-16 rounded-xl object-cover object-center" />
                        @elseif ($currentFaviconUrl && !$removeSiteFavicon)
                            <img src="{{ $currentFaviconUrl }}" alt="Favicon actual"
                                class="h-16 w-16 rounded-xl object-cover object-center" />
                        @else
                            <span class="text-sm text-slate-400">Sin favicon configurado</span>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <p><span class="font-semibold">Marca:</span> {{ $siteName }}</p>
                    <p class="mt-1"><span class="font-semibold">WhatsApp:</span> {{ $salesWhatsapp ?: 'No definido' }}
                    </p>
                    <p class="mt-1"><span class="font-semibold">Direccion:</span>
                        {{ $storeAddress ?: 'No definida' }}</p>
                    <p class="mt-1"><span class="font-semibold">Horario:</span> {{ $businessHours ?: 'No definido' }}
                    </p>
                </div>
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-950">Banners de portada</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Carrusel principal: {{ $mainBannerHint['label'] }}. Laterales: {{ $sideBannerHint['label'] }}.
                    </p>
                </div>
                @if ($editingBannerId)
                    <button wire:click="resetBannerForm" type="button"
                        class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700">
                        Nuevo banner
                    </button>
                @endif
            </div>

            <form class="mt-6 space-y-4" wire:submit="saveBanner">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Titulo</label>
                    <input wire:model.live="bannerTitle" type="text"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('bannerTitle')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subtitulo</label>
                    <textarea wire:model.live="bannerSubtitle" rows="3"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                    @error('bannerSubtitle')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tipo</label>
                        <select wire:model.live="bannerType"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="main_large">Banner grande principal</option>
                            <option value="side_small">Banner lateral chico</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Orden</label>
                        <input wire:model.live="bannerSortOrder" type="number" min="1"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">URL destino</label>
                    <input wire:model.live="bannerLinkUrl" type="text"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        placeholder="/ofertas o https://..." />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Activo desde</label>
                        <input wire:model.live="bannerActiveFrom" type="datetime-local"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Activo hasta</label>
                        <input wire:model.live="bannerActiveTo" type="datetime-local"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Imagen</label>
                    <p class="mb-2 text-xs text-slate-500">
                        @if ($bannerType === 'main_large')
                            Sugerido: {{ $mainBannerHint['label'] }}. Minimo validado: 1000x500 px.
                        @else
                            Sugerido: {{ $sideBannerHint['label'] }}. Minimo validado: 500x250 px.
                        @endif
                    </p>
                    <input wire:model.live="bannerImage" type="file" accept="image/*"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" />
                    @error('bannerImage')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model.live="bannerIsActive" type="checkbox" class="rounded border-slate-300" />
                    Publicar banner
                </label>

                <button type="submit" class="rounded-full bg-slate-950 px-5 py-2 text-sm font-semibold text-white">
                    {{ $editingBannerId ? 'Actualizar banner' : 'Crear banner' }}
                </button>
            </form>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Preview del banner</h3>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                @if ($bannerImage)
                    <img src="{{ $bannerImage->temporaryUrl() }}" alt="Preview banner"
                        class="h-64 w-full object-cover object-center" />
                @elseif ($editingBannerId)
                    @php($editingBanner = $banners->firstWhere('id', $editingBannerId))
                    @if ($editingBanner)
                        <img src="{{ $editingBanner->image_url }}" alt="Banner actual"
                            class="h-64 w-full object-cover object-center" />
                    @else
                        <div class="flex h-64 items-center justify-center text-sm text-slate-400">Sin imagen
                            seleccionada</div>
                    @endif
                @else
                    <div
                        class="flex h-64 items-center justify-center bg-gradient-to-br from-cyan-200 via-sky-100 to-orange-100 text-sm text-slate-500">
                        Carga una imagen para ver la previsualizacion.
                    </div>
                @endif
            </div>
            <p class="mt-3 text-sm font-semibold text-slate-900">{{ $bannerTitle ?: 'Titulo del banner' }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $bannerSubtitle ?: 'Subtitulo opcional del banner.' }}</p>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-950">Banners cargados</h2>
                <p class="mt-1 text-sm text-slate-500">El carrusel principal toma solo los activos de tipo main_large.
                    Los laterales muestran hasta 2 side_small activos.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @forelse ($banners as $banner)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}"
                        class="h-48 w-full object-cover object-center" />
                    <div class="space-y-2 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $banner->title }}</p>
                                <p class="text-xs text-slate-500">{{ $banner->subtitle }}</p>
                            </div>
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold {{ $banner->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $banner->type }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">
                            Orden {{ $banner->sort_order }}
                            @if ($banner->active_from || $banner->active_to)
                                · Ventana: {{ $banner->active_from?->format('d/m H:i') ?: 'ahora' }} a
                                {{ $banner->active_to?->format('d/m H:i') ?: 'sin cierre' }}
                            @endif
                        </p>
                        <div class="flex gap-2 pt-2">
                            <button wire:click="editBanner({{ $banner->id }})" type="button"
                                class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700">
                                Editar
                            </button>
                            <button wire:click="deleteBanner({{ $banner->id }})" type="button"
                                class="rounded-full border border-rose-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-rose-700">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
                    Todavia no hay banners cargados.
                </div>
            @endforelse
        </div>
    </section>
</div>
