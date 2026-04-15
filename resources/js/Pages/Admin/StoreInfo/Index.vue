<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";

const props = defineProps<{
    settings: {
        store_name: string | null;
        store_email: string | null;
        store_phone: string | null;
        store_whatsapp: string | null;
        store_address: string | null;
        facebook_url: string | null;
        instagram_url: string | null;
        tiktok_url: string | null;
        youtube_url: string | null;
    };
}>();

const form = useForm({
    store_name: props.settings.store_name || "",
    store_email: props.settings.store_email || "",
    store_phone: props.settings.store_phone || "",
    store_whatsapp: props.settings.store_whatsapp || "",
    store_address: props.settings.store_address || "",
    facebook_url: props.settings.facebook_url || "",
    instagram_url: props.settings.instagram_url || "",
    tiktok_url: props.settings.tiktok_url || "",
    youtube_url: props.settings.youtube_url || "",
});

function submit(): void {
    form.put(route("admin.store-info.update"), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Admin · Info negocio" />

    <AdminLayout>
        <section class="space-y-6">
            <div class="flex flex-col gap-2">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                    Gestión comercial
                </p>
                <h2 class="text-3xl font-semibold text-slate-950">
                    Información del negocio
                </h2>
                <p class="max-w-2xl text-sm text-slate-500">
                    Actualiza los datos visibles de contacto y redes sociales desde una página completa, no desde un modal.
                </p>
            </div>

            <div
                v-if="$page.props.flash.success"
                class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ $page.props.flash.success }}
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-950">Datos principales</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Estos campos se usan para comunicar tu negocio en el sitio y en los formularios de contacto.
                    </p>

                    <form class="mt-6 space-y-5" @submit.prevent="submit">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Nombre del negocio</label>
                                <input
                                    v-model="form.store_name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    maxlength="255"
                                />
                                <p v-if="form.errors.store_name" class="mt-1 text-xs text-rose-600">{{ form.errors.store_name }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                                <input
                                    v-model="form.store_email"
                                    type="email"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    maxlength="255"
                                />
                                <p v-if="form.errors.store_email" class="mt-1 text-xs text-rose-600">{{ form.errors.store_email }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Teléfono</label>
                                <input
                                    v-model="form.store_phone"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    maxlength="30"
                                />
                                <p v-if="form.errors.store_phone" class="mt-1 text-xs text-rose-600">{{ form.errors.store_phone }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">WhatsApp</label>
                                <input
                                    v-model="form.store_whatsapp"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    maxlength="30"
                                />
                                <p v-if="form.errors.store_whatsapp" class="mt-1 text-xs text-rose-600">{{ form.errors.store_whatsapp }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Dirección</label>
                            <input
                                v-model="form.store_address"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                maxlength="255"
                            />
                            <p v-if="form.errors.store_address" class="mt-1 text-xs text-rose-600">{{ form.errors.store_address }}</p>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button
                                type="submit"
                                class="rounded-full bg-slate-950 px-5 py-2 text-sm font-semibold text-white disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                            </button>
                            <p class="text-xs text-slate-500">Los cambios se guardan en la configuración global del negocio.</p>
                        </div>
                    </form>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-950">Redes sociales</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Completa los enlaces que quieres mostrar en la tienda y el pie de página.
                    </p>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Facebook</label>
                            <input
                                v-model="form.facebook_url"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="https://facebook.com/tuempresa"
                                maxlength="255"
                            />
                            <p v-if="form.errors.facebook_url" class="mt-1 text-xs text-rose-600">{{ form.errors.facebook_url }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Instagram</label>
                            <input
                                v-model="form.instagram_url"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="https://instagram.com/tuempresa"
                                maxlength="255"
                            />
                            <p v-if="form.errors.instagram_url" class="mt-1 text-xs text-rose-600">{{ form.errors.instagram_url }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">TikTok</label>
                            <input
                                v-model="form.tiktok_url"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="https://tiktok.com/@tuempresa"
                                maxlength="255"
                            />
                            <p v-if="form.errors.tiktok_url" class="mt-1 text-xs text-rose-600">{{ form.errors.tiktok_url }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">YouTube</label>
                            <input
                                v-model="form.youtube_url"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="https://youtube.com/tuempresa"
                                maxlength="255"
                            />
                            <p v-if="form.errors.youtube_url" class="mt-1 text-xs text-rose-600">{{ form.errors.youtube_url }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            Si después quieres editar banners o apariencia visual, usa la sección <span class="font-semibold text-slate-900">Apariencia</span>.
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </AdminLayout>
</template>