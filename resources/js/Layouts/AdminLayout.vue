<script setup lang="ts">
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import axios from "axios";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { PageProps } from "@/types";

const page = usePage<PageProps>();
const flash = computed(() => page.props.flash);
const user = computed(() => page.props.auth.user);
const branding = computed(() => page.props.branding);
const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") ?? "";
const latestOrderCode = ref<string | null>(null);
const latestOrderTotal = ref<number | null>(null);
const notificationVisible = ref(false);
const isMobileNavOpen = ref(false);
let pollInterval: number | null = null;

function closeNotification(): void {
    notificationVisible.value = false;
}

function toggleMobileNav(): void {
    isMobileNavOpen.value = !isMobileNavOpen.value;
}

function closeMobileNav(): void {
    isMobileNavOpen.value = false;
}

function handleResize(): void {
    if (window.innerWidth >= 640) {
        isMobileNavOpen.value = false;
    }
}

async function checkLatestOrder(): Promise<void> {
    try {
        const response = await axios.get(route("admin.dashboard.latest-order"));
        const latest = response.data?.latest;

        if (!latest?.code) {
            return;
        }

        if (latestOrderCode.value === null) {
            latestOrderCode.value = latest.code;
            latestOrderTotal.value = Number(latest.total);
            return;
        }

        if (latest.code !== latestOrderCode.value) {
            latestOrderCode.value = latest.code;
            latestOrderTotal.value = Number(latest.total);
            notificationVisible.value = true;
        }
    } catch (error: any) {
        // If session expires/logs out, stop polling to avoid noisy 401 requests.
        if (error?.response?.status === 401 && pollInterval !== null) {
            window.clearInterval(pollInterval);
            pollInterval = null;
        }
    }
}

onMounted(() => {
    checkLatestOrder();
    pollInterval = window.setInterval(() => {
        checkLatestOrder();
    }, 25000);

    window.addEventListener("resize", handleResize);
    handleResize();
});

onBeforeUnmount(() => {
    if (pollInterval !== null) {
        window.clearInterval(pollInterval);
    }

    window.removeEventListener("resize", handleResize);
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="h-12 w-16 overflow-hidden rounded-xl bg-slate-50"
                    >
                        <ApplicationLogo
                            class="h-full w-full fill-current text-slate-700"
                        />
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500"
                        >
                            Panel de Administracion
                        </p>
                        <h1 class="mt-1 text-2xl font-semibold text-slate-950">
                            {{ branding.site_name || "TUS TECNOLOGIAS" }}
                        </h1>
                    </div>

                    <button
                        type="button"
                        class="ml-auto rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 sm:hidden"
                        @click="toggleMobileNav"
                    >
                        {{ isMobileNavOpen ? "Cerrar menu" : "Menu" }}
                    </button>
                </div>

                <div
                    class="flex flex-wrap items-center gap-3 text-sm"
                    :class="isMobileNavOpen ? 'flex' : 'hidden sm:flex'"
                    @click="closeMobileNav"
                >
                    <Link
                        :href="route('admin.dashboard')"
                        class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                        :class="{
                            'border-slate-950 bg-slate-950 text-white':
                                route().current('admin.dashboard*'),
                        }"
                    >
                        Analitica
                    </Link>
                    <Link
                        :href="route('admin.products.index')"
                        class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                        :class="{
                            'border-slate-950 bg-slate-950 text-white':
                                route().current('admin.products.*'),
                        }"
                    >
                        Productos
                    </Link>
                    <Link
                        :href="route('admin.coupons.index')"
                        class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                        :class="{
                            'border-slate-950 bg-slate-950 text-white':
                                route().current('admin.coupons.*'),
                        }"
                    >
                        Cupones
                    </Link>
                    <Link
                        :href="route('admin.orders.index')"
                        class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                        :class="{
                            'border-slate-950 bg-slate-950 text-white':
                                route().current('admin.orders.*'),
                        }"
                    >
                        Pedidos
                    </Link>
                    <Link
                        :href="route('admin.abandoned-carts.index')"
                        class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                        :class="{
                            'border-slate-950 bg-slate-950 text-white':
                                route().current('admin.abandoned-carts.*'),
                        }"
                    >
                        Abandonados
                    </Link>
                    <Link
                        :href="route('admin.stock.index')"
                        class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                        :class="{
                            'border-slate-950 bg-slate-950 text-white':
                                route().current('admin.stock.*'),
                        }"
                    >
                        Stock
                    </Link>
                    <a
                        :href="route('admin.appearance.index')"
                        class="rounded-full border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                    >
                        Apariencia
                    </a>
                    <Link
                        :href="route('storefront')"
                        class="rounded-full border border-emerald-600 px-4 py-2 font-semibold text-emerald-700 transition hover:bg-emerald-50"
                    >
                        Ver tienda
                    </Link>
                    <form :action="route('logout')" method="post" @click.stop>
                        <input type="hidden" name="_token" :value="csrfToken" />
                        <button
                            type="submit"
                            class="rounded-full border border-rose-300 px-4 py-2 font-semibold text-rose-700 transition hover:bg-rose-50"
                        >
                            Cerrar sesion
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div
                v-if="flash.success"
                class="mb-5 rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="mb-5 rounded-2xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
                {{ flash.error }}
            </div>

            <div
                class="mb-6 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-600"
            >
                Sesion:
                <span class="font-semibold text-slate-900">{{
                    user?.name
                }}</span>
                ({{ user?.email }})
            </div>

            <slot />
        </main>

        <div
            v-if="notificationVisible"
            class="fixed bottom-4 right-4 z-50 w-[320px] rounded-2xl border border-emerald-300 bg-white p-4 shadow-xl"
        >
            <p
                class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700"
            >
                Nuevo pedido confirmado
            </p>
            <p class="mt-2 text-sm text-slate-700">
                Pedido
                <span class="font-semibold text-slate-950">{{
                    latestOrderCode
                }}</span>
                por
                <span class="font-semibold text-slate-950"
                    >${{ latestOrderTotal?.toFixed(2) }}</span
                >
            </p>
            <div class="mt-3 flex justify-end gap-2">
                <Link
                    :href="route('admin.orders.index')"
                    class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white"
                    @click="closeNotification"
                >
                    Ver pedidos
                </Link>
                <button
                    type="button"
                    class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700"
                    @click="closeNotification"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>
