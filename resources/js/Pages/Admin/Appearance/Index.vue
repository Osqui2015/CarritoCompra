<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";

interface CategoryOption {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    settings: {
        low_stock_threshold: number;
        sales_whatsapp: string | null;
        store_address: string | null;
        business_hours: string | null;
        hero_banner_title: string | null;
        hero_banner_subtitle: string | null;
        hero_banner_link_type: "url" | "category";
        hero_banner_link_value: string | null;
        hero_banner_url: string | null;
    };
    categories: CategoryOption[];
}>();

const form = useForm({
    low_stock_threshold: props.settings.low_stock_threshold,
    sales_whatsapp: props.settings.sales_whatsapp || "",
    store_address: props.settings.store_address || "",
    business_hours: props.settings.business_hours || "",
    hero_banner_title: props.settings.hero_banner_title || "",
    hero_banner_subtitle: props.settings.hero_banner_subtitle || "",
    hero_banner_link_type: props.settings.hero_banner_link_type || "url",
    hero_banner_link_value: props.settings.hero_banner_link_value || "/",
    hero_banner_image: null as File | null,
    remove_hero_banner_image: false,
});

function onBannerFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.hero_banner_image = target.files?.[0] ?? null;
    if (form.hero_banner_image) {
        form.remove_hero_banner_image = false;
    }
}

function submit(): void {
    form.post(route("admin.appearance.update"), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Admin · Apariencia" />

    <AdminLayout>
        <section
            class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]"
        >
            <article
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2 class="text-2xl font-semibold text-slate-950">
                    CMS de apariencia
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Edita banner principal, enlaces y datos de contacto visibles
                    en tienda.
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            >Titulo del banner</label
                        >
                        <input
                            v-model="form.hero_banner_title"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            required
                        />
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            >Subtitulo del banner</label
                        >
                        <textarea
                            v-model="form.hero_banner_subtitle"
                            rows="3"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        ></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                >Tipo de enlace</label
                            >
                            <select
                                v-model="form.hero_banner_link_type"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            >
                                <option value="url">URL</option>
                                <option value="category">Categoria</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                >Destino</label
                            >
                            <input
                                v-if="form.hero_banner_link_type === 'url'"
                                v-model="form.hero_banner_link_value"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="/ofertas o https://..."
                                required
                            />
                            <select
                                v-else
                                v-model="form.hero_banner_link_value"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                required
                            >
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.slug"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            >Imagen de banner</label
                        >
                        <input
                            type="file"
                            accept="image/*"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            @change="onBannerFileChange"
                        />
                        <label
                            class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600"
                        >
                            <input
                                v-model="form.remove_hero_banner_image"
                                type="checkbox"
                                class="rounded border-slate-300"
                            />
                            Quitar imagen actual
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                >WhatsApp ventas</label
                            >
                            <input
                                v-model="form.sales_whatsapp"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="54911..."
                            />
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                >Umbral stock critico</label
                            >
                            <input
                                v-model.number="form.low_stock_threshold"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            >Direccion comercial</label
                        >
                        <input
                            v-model="form.store_address"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            >Horario</label
                        >
                        <input
                            v-model="form.business_hours"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Lun a Vie 9:00 a 18:00"
                        />
                    </div>

                    <button
                        type="submit"
                        class="rounded-full bg-slate-950 px-5 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing ? "Guardando..." : "Guardar cambios"
                        }}
                    </button>
                </form>
            </article>

            <article
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-lg font-semibold text-slate-950">
                    Vista previa de banner
                </h3>
                <div
                    class="mt-4 overflow-hidden rounded-2xl border border-slate-200"
                >
                    <img
                        v-if="
                            settings.hero_banner_url &&
                            !form.remove_hero_banner_image
                        "
                        :src="settings.hero_banner_url"
                        alt="Banner"
                        class="h-52 w-full object-cover"
                    />
                    <div
                        v-else
                        class="h-52 bg-gradient-to-br from-cyan-200 via-sky-100 to-orange-100"
                    ></div>
                </div>

                <div class="mt-4">
                    <p
                        class="text-xs uppercase tracking-[0.18em] text-slate-500"
                    >
                        Titulo
                    </p>
                    <h4 class="mt-1 text-2xl font-semibold text-slate-950">
                        {{ form.hero_banner_title || "Sin titulo" }}
                    </h4>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ form.hero_banner_subtitle || "Sin subtitulo" }}
                    </p>
                </div>

                <div
                    class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"
                >
                    <p>
                        <span class="font-semibold">Destino:</span>
                        {{ form.hero_banner_link_value }}
                    </p>
                    <p class="mt-1">
                        <span class="font-semibold">WhatsApp:</span>
                        {{ form.sales_whatsapp || "No definido" }}
                    </p>
                    <p class="mt-1">
                        <span class="font-semibold">Direccion:</span>
                        {{ form.store_address || "No definida" }}
                    </p>
                    <p class="mt-1">
                        <span class="font-semibold">Horario:</span>
                        {{ form.business_hours || "No definido" }}
                    </p>
                </div>
            </article>
        </section>
    </AdminLayout>
</template>
