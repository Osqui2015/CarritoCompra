<script setup lang="ts">
import axios from "axios";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import ProductSearchInput from "@/Components/ProductSearchInput.vue";
import type { PageProps } from "@/types";

interface NavGroupItem {
    label: string;
    href: string;
}

interface NavGroup {
    label: string;
    items: NavGroupItem[];
}

const CART_STORAGE_KEY = "carrito:items:v1";

const props = defineProps<{
    navGroups?: NavGroup[];
}>();

const page = usePage<PageProps>();
const cartCount = ref(0);
const isLoginModalOpen = ref(false);
const isMobileMenuOpen = ref(false);
const loginLoading = ref(false);
const loginErrors = ref<Record<string, string>>({});
const loginForm = reactive({
    email: "",
    password: "",
    remember: false,
});
let cartCountInterval: number | null = null;

const siteName = computed(
    () => page.props.branding?.site_name || "TUS TECNOLOGIAS",
);
const siteLogoUrl = computed(() => page.props.branding?.site_logo || null);

const resolvedNavGroups = computed<NavGroup[]>(() => {
    if (Array.isArray(props.navGroups) && props.navGroups.length) {
        return props.navGroups;
    }

    const sharedGroups = (page.props as Record<string, any>).store_nav_groups;

    return Array.isArray(sharedGroups) ? sharedGroups : [];
});

function loadCartCount(): void {
    if (typeof window === "undefined") {
        return;
    }

    try {
        const raw = window.localStorage.getItem(CART_STORAGE_KEY);

        if (!raw) {
            cartCount.value = 0;
            return;
        }

        const parsed = JSON.parse(raw);

        if (!Array.isArray(parsed)) {
            cartCount.value = 0;
            return;
        }

        cartCount.value = parsed.reduce((sum: number, item: any) => {
            const quantity = Math.max(0, Number(item?.quantity) || 0);

            return sum + quantity;
        }, 0);
    } catch {
        cartCount.value = 0;
    }
}

function syncOverlayState(): void {
    document.body.style.overflow = isLoginModalOpen.value ? "hidden" : "";
}

function openLoginModal(): void {
    isMobileMenuOpen.value = false;
    isLoginModalOpen.value = true;
    loginErrors.value = {};
    syncOverlayState();
}

function closeLoginModal(): void {
    isLoginModalOpen.value = false;
    loginForm.password = "";
    loginForm.remember = false;
    loginErrors.value = {};
    syncOverlayState();
}

function toggleMobileMenu(): void {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

function handleResize(): void {
    if (window.innerWidth >= 640) {
        isMobileMenuOpen.value = false;
    }
}

async function submitLoginModal(): Promise<void> {
    loginErrors.value = {};
    loginLoading.value = true;

    try {
        await axios.post(route("login.modal"), {
            email: loginForm.email,
            password: loginForm.password,
            remember: loginForm.remember,
        });

        window.location.reload();
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const responseErrors = error.response?.data?.errors ?? {};

            loginErrors.value = {
                email: Array.isArray(responseErrors.email)
                    ? responseErrors.email[0]
                    : "",
                password: Array.isArray(responseErrors.password)
                    ? responseErrors.password[0]
                    : "",
            };
        } else {
            loginErrors.value = {
                general: "No se pudo iniciar sesion. Intenta nuevamente.",
            };
        }
    } finally {
        loginLoading.value = false;
    }
}

function handleStorage(event: StorageEvent): void {
    if (event.key === CART_STORAGE_KEY) {
        loadCartCount();
    }
}

function handleEscape(event: KeyboardEvent): void {
    if (event.key === "Escape" && isLoginModalOpen.value) {
        closeLoginModal();
        return;
    }

    if (event.key === "Escape" && isMobileMenuOpen.value) {
        isMobileMenuOpen.value = false;
    }
}

onMounted(() => {
    loadCartCount();

    window.addEventListener("storage", handleStorage);
    window.addEventListener("focus", loadCartCount);
    window.addEventListener("keydown", handleEscape);
    window.addEventListener("resize", handleResize);

    handleResize();

    cartCountInterval = window.setInterval(() => {
        loadCartCount();
    }, 1200);
});

onBeforeUnmount(() => {
    window.removeEventListener("storage", handleStorage);
    window.removeEventListener("focus", loadCartCount);
    window.removeEventListener("keydown", handleEscape);
    window.removeEventListener("resize", handleResize);

    if (cartCountInterval !== null) {
        window.clearInterval(cartCountInterval);
    }

    document.body.style.overflow = "";
});
</script>

<template>
    <header
        class="sticky top-4 z-50 rounded-3xl border border-slate-200 bg-white/90 px-5 py-4 shadow-sm backdrop-blur sm:px-7"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Link :href="route('storefront')" class="flex items-center gap-3">
                <div
                    v-if="siteLogoUrl"
                    class="h-12 w-16 overflow-hidden rounded-xl bg-slate-50"
                >
                    <img
                        :src="siteLogoUrl"
                        :alt="siteName"
                        class="h-full w-full object-contain object-center"
                    />
                </div>
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.26em] text-orange-500"
                    >
                        Mayorista
                    </p>
                    <h1 class="text-2xl font-semibold text-slate-950">
                        {{ siteName }}
                    </h1>
                </div>
            </Link>

            <div class="hidden flex-1 items-center justify-end gap-3 sm:flex">
                <div class="max-w-md flex-1">
                    <ProductSearchInput />
                </div>

                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('register')"
                    class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-950"
                >
                    Registrarse
                </Link>

                <button
                    v-if="!$page.props.auth.user"
                    type="button"
                    class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                    @click="openLoginModal"
                >
                    Ingresar
                </button>

                <Link
                    v-if="
                        $page.props.auth.user && $page.props.auth.user.is_admin
                    "
                    :href="route('admin.dashboard')"
                    class="rounded-full border border-orange-300 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-50"
                >
                    Admin
                </Link>

                <Link
                    :href="route('cart.view')"
                    class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    <span>Carrito</span>
                    <span class="rounded-full bg-white/15 px-2 py-0.5 text-xs">
                        {{ cartCount }}
                    </span>
                </Link>
            </div>

            <div class="flex items-center gap-2 sm:hidden">
                <Link
                    :href="route('cart.view')"
                    class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-3 py-2 text-xs font-semibold text-white"
                >
                    <span>Carrito</span>
                    <span
                        class="rounded-full bg-white/15 px-2 py-0.5 text-[11px]"
                    >
                        {{ cartCount }}
                    </span>
                </Link>

                <button
                    type="button"
                    class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                    @click="toggleMobileMenu"
                >
                    {{ isMobileMenuOpen ? "Cerrar" : "Menu" }}
                </button>
            </div>
        </div>

        <div
            v-if="isMobileMenuOpen"
            class="mt-3 space-y-3 border-t border-slate-200 pt-3 sm:hidden"
        >
            <ProductSearchInput />

            <div class="grid grid-cols-2 gap-2">
                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('register')"
                    class="rounded-full border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700"
                >
                    Registrarse
                </Link>

                <button
                    v-if="!$page.props.auth.user"
                    type="button"
                    class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                    @click="openLoginModal"
                >
                    Ingresar
                </button>

                <Link
                    v-if="
                        $page.props.auth.user && $page.props.auth.user.is_admin
                    "
                    :href="route('admin.dashboard')"
                    class="col-span-2 rounded-full border border-orange-300 px-4 py-2 text-center text-sm font-semibold text-orange-700"
                >
                    Admin
                </Link>
            </div>
        </div>

        <div class="mt-4 border-t border-slate-200 pt-3">
            <nav class="hidden flex-wrap items-center gap-2 lg:flex">
                <div
                    v-for="group in resolvedNavGroups"
                    :key="group.label"
                    class="group relative"
                >
                    <a
                        :href="group.items[0]?.href || '#'"
                        class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-900"
                    >
                        {{ group.label }}
                    </a>

                    <div
                        v-if="group.items.length > 1"
                        class="invisible pointer-events-none absolute left-0 top-full z-30 min-w-[240px] pt-2 opacity-0 transition group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100"
                    >
                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                        >
                            <a
                                v-for="item in group.items"
                                :key="`${group.label}-${item.href}`"
                                :href="item.href"
                                class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900"
                            >
                                {{ item.label }}
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <nav
                class="grid gap-2 lg:hidden"
                :class="isMobileMenuOpen ? '' : 'hidden sm:grid'"
            >
                <div
                    v-for="group in resolvedNavGroups"
                    :key="`${group.label}-mobile`"
                    class="rounded-2xl border border-slate-200 bg-white p-3"
                >
                    <a
                        :href="group.items[0]?.href || '#'"
                        class="text-sm font-semibold text-slate-900"
                    >
                        {{ group.label }}
                    </a>

                    <div
                        v-if="group.items.length > 1"
                        class="mt-2 flex flex-wrap gap-2"
                    >
                        <a
                            v-for="item in group.items"
                            :key="`${group.label}-mobile-${item.href}`"
                            :href="item.href"
                            class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700"
                        >
                            {{ item.label }}
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <div
        v-if="isLoginModalOpen"
        class="fixed inset-0 z-[85] flex items-center justify-center bg-slate-950/65 p-4"
        @click="closeLoginModal"
    >
        <article
            class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl"
            @click.stop
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-500"
                    >
                        Bienvenido
                    </p>
                    <h3 class="mt-1 text-2xl font-semibold text-slate-950">
                        Ingresar
                    </h3>
                </div>
                <button
                    type="button"
                    class="h-9 w-9 rounded-full border border-slate-300 text-lg font-semibold text-slate-700 transition hover:border-slate-900"
                    @click="closeLoginModal"
                >
                    x
                </button>
            </div>

            <form class="mt-5 space-y-4" @submit.prevent="submitLoginModal">
                <div>
                    <label
                        for="store-login-email"
                        class="mb-1 block text-sm font-semibold text-slate-700"
                    >
                        Correo
                    </label>
                    <input
                        id="store-login-email"
                        v-model="loginForm.email"
                        type="email"
                        autocomplete="email"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        required
                    />
                    <p
                        v-if="loginErrors.email"
                        class="mt-1 text-xs font-semibold text-rose-600"
                    >
                        {{ loginErrors.email }}
                    </p>
                </div>

                <div>
                    <label
                        for="store-login-password"
                        class="mb-1 block text-sm font-semibold text-slate-700"
                    >
                        Contrasena
                    </label>
                    <input
                        id="store-login-password"
                        v-model="loginForm.password"
                        type="password"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        required
                    />
                    <p
                        v-if="loginErrors.password"
                        class="mt-1 text-xs font-semibold text-rose-600"
                    >
                        {{ loginErrors.password }}
                    </p>
                </div>

                <label
                    class="inline-flex items-center gap-2 text-sm text-slate-600"
                >
                    <input
                        v-model="loginForm.remember"
                        type="checkbox"
                        class="rounded border-slate-300"
                    />
                    Recordarme
                </label>

                <p
                    v-if="loginErrors.general"
                    class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700"
                >
                    {{ loginErrors.general }}
                </p>

                <div class="flex items-center justify-between gap-3">
                    <a
                        :href="route('password.request')"
                        class="text-sm font-semibold text-slate-500 transition hover:text-slate-700"
                    >
                        Olvide mi contrasena
                    </a>
                    <button
                        type="submit"
                        class="rounded-full bg-slate-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                        :disabled="loginLoading"
                    >
                        {{ loginLoading ? "Ingresando..." : "Ingresar" }}
                    </button>
                </div>
            </form>
        </article>
    </div>
</template>
