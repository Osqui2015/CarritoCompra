<script setup lang="ts">
import StoreTopBar from "@/Components/StoreTopBar.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { PageProps } from "@/types";

interface ProductDetail {
    id: number;
    name: string;
    slug: string;
    hero_tag: string | null;
    description: string | null;
    price: number;
    stock: number;
    stock_reference: number;
    category_name: string | null;
    category_names: string[];
    image_url: string | null;
}

interface RelatedProduct {
    id: number;
    name: string;
    slug: string;
    hero_tag: string | null;
    description: string | null;
    price: number;
    stock: number;
    stock_reference: number;
    image_url: string | null;
    category_name: string | null;
    category_names: string[];
    category_links: Array<{
        name: string;
        href: string;
    }>;
}

interface StoredCartItem {
    product_id: number;
    quantity: number;
    name: string;
    price: number;
    image_url: string | null;
    category_name: string | null;
}

const CART_STORAGE_KEY = "carrito:items:v1";
const FAVORITES_STORAGE_KEY = "carrito:favoritos:v1";

const props = defineProps<{
    product: ProductDetail;
    related_products: RelatedProduct[];
    breadcrumbs: string[];
}>();

const page = usePage<PageProps>();
const quantity = ref(1);
const cartItems = ref<StoredCartItem[]>([]);
const favoriteIds = ref<number[]>([]);
const relatedQuantities = ref<Record<number, number>>({});
const addMessage = ref<string | null>(null);
const selectedRelatedProduct = ref<RelatedProduct | null>(null);
const relatedModalQuantity = ref(1);
const relatedModalMessage = ref<string | null>(null);
let addMessageTimeout: number | null = null;
let relatedModalMessageTimeout: number | null = null;

const siteName = computed(
    () => page.props.branding?.site_name || "TUS TECNOLOGIAS",
);

const isFavorite = computed(() => favoriteIds.value.includes(props.product.id));

const productDescription = computed(
    () =>
        props.product.description ||
        props.product.hero_tag ||
        "Producto de alta rotacion para ventas mayoristas.",
);

const stockRatio = computed(() => {
    const reference = Math.max(props.product.stock_reference, 1);

    return props.product.stock / reference;
});

const stockBadgeClass = computed(() => {
    if (props.product.stock <= 0) {
        return "bg-rose-100 text-rose-700";
    }

    if (stockRatio.value < 0.3) {
        return "bg-amber-100 text-amber-700";
    }

    if (stockRatio.value < 0.7) {
        return "bg-yellow-100 text-yellow-800";
    }

    return "bg-emerald-100 text-emerald-700";
});

const stockLabel = computed(() => {
    if (props.product.stock <= 0) {
        return "Sin stock";
    }

    if (stockRatio.value < 0.3) {
        return "Stock bajo";
    }

    if (stockRatio.value < 0.7) {
        return "Stock medio";
    }

    return "Stock alto";
});

function stockRatioByProduct(product: RelatedProduct): number {
    const reference = Math.max(product.stock_reference, 1);

    return product.stock / reference;
}

function stockBadgeClassByProduct(product: RelatedProduct): string {
    if (product.stock <= 0) {
        return "bg-rose-100 text-rose-700";
    }

    const ratio = stockRatioByProduct(product);

    if (ratio < 0.3) {
        return "bg-amber-100 text-amber-700";
    }

    if (ratio < 0.7) {
        return "bg-yellow-100 text-yellow-800";
    }

    return "bg-emerald-100 text-emerald-700";
}

function stockLabelByProduct(product: RelatedProduct): string {
    if (product.stock <= 0) {
        return "Sin stock";
    }

    const ratio = stockRatioByProduct(product);

    if (ratio < 0.3) {
        return "Stock bajo";
    }

    if (ratio < 0.7) {
        return "Stock medio";
    }

    return "Stock alto";
}

function formatMoney(value: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)}`;
}

function normalizeCartItem(item: any): StoredCartItem | null {
    const productId = Number(item.product_id);
    const normalizedQty = Math.max(0, Number(item.quantity) || 0);

    if (productId <= 0 || normalizedQty <= 0) {
        return null;
    }

    return {
        product_id: productId,
        quantity: normalizedQty,
        name: String(item.name || "Producto"),
        price: Number(item.price) || 0,
        image_url: typeof item.image_url === "string" ? item.image_url : null,
        category_name:
            typeof item.category_name === "string" ? item.category_name : null,
    };
}

function loadCart(): void {
    if (typeof window === "undefined") {
        return;
    }

    try {
        const raw = window.localStorage.getItem(CART_STORAGE_KEY);
        if (!raw) {
            cartItems.value = [];
            return;
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            cartItems.value = [];
            return;
        }

        cartItems.value = parsed
            .map((item: any) => normalizeCartItem(item))
            .filter((item): item is StoredCartItem => item !== null);
    } catch {
        cartItems.value = [];
    }
}

function persistCart(): void {
    if (typeof window === "undefined") {
        return;
    }

    window.localStorage.setItem(
        CART_STORAGE_KEY,
        JSON.stringify(cartItems.value),
    );
}

function loadFavorites(): void {
    if (typeof window === "undefined") {
        return;
    }

    try {
        const raw = window.localStorage.getItem(FAVORITES_STORAGE_KEY);
        if (!raw) {
            favoriteIds.value = [];
            return;
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            favoriteIds.value = [];
            return;
        }

        favoriteIds.value = Array.from(
            new Set(
                parsed
                    .map((value: any) => Number(value))
                    .filter(
                        (value: number) => Number.isInteger(value) && value > 0,
                    ),
            ),
        );
    } catch {
        favoriteIds.value = [];
    }
}

function persistFavorites(): void {
    if (typeof window === "undefined") {
        return;
    }

    window.localStorage.setItem(
        FAVORITES_STORAGE_KEY,
        JSON.stringify(favoriteIds.value),
    );
}

function isFavoriteId(productId: number): boolean {
    return favoriteIds.value.includes(productId);
}

function toggleFavoriteById(productId: number): void {
    if (isFavoriteId(productId)) {
        favoriteIds.value = favoriteIds.value.filter((id) => id !== productId);
    } else {
        favoriteIds.value = [...favoriteIds.value, productId];
    }

    persistFavorites();
}

function setQuantity(nextValue: number): void {
    const minQuantity = props.product.stock > 0 ? 1 : 0;

    quantity.value = Math.max(
        minQuantity,
        Math.min(props.product.stock, Math.floor(nextValue || 1)),
    );
}

function incrementQuantity(): void {
    setQuantity(quantity.value + 1);
}

function decrementQuantity(): void {
    setQuantity(quantity.value - 1);
}

function addCurrentProductToCart(): void {
    if (props.product.stock <= 0) {
        return;
    }

    const itemIndex = cartItems.value.findIndex(
        (item) => item.product_id === props.product.id,
    );

    if (itemIndex >= 0) {
        const existing = cartItems.value[itemIndex];
        const nextQuantity = Math.min(
            props.product.stock,
            existing.quantity + quantity.value,
        );

        const nextCart = [...cartItems.value];
        nextCart[itemIndex] = {
            ...existing,
            quantity: nextQuantity,
        };

        cartItems.value = nextCart;
    } else {
        cartItems.value = [
            ...cartItems.value,
            {
                product_id: props.product.id,
                quantity: quantity.value,
                name: props.product.name,
                price: props.product.price,
                image_url: props.product.image_url,
                category_name: props.product.category_name,
            },
        ];
    }

    persistCart();

    addMessage.value = `${props.product.name} agregado al carrito.`;

    if (addMessageTimeout !== null) {
        window.clearTimeout(addMessageTimeout);
    }

    addMessageTimeout = window.setTimeout(() => {
        addMessage.value = null;
    }, 2500);
}

function toggleFavorite(): void {
    toggleFavoriteById(props.product.id);
}

function addRelatedProductToCart(product: RelatedProduct, rawAmount = 1): void {
    if (product.stock <= 0) {
        return;
    }

    const amount = Math.max(1, Math.floor(rawAmount || 1));
    const itemIndex = cartItems.value.findIndex(
        (item) => item.product_id === product.id,
    );

    if (itemIndex >= 0) {
        const existing = cartItems.value[itemIndex];
        const nextQuantity = Math.min(
            product.stock,
            existing.quantity + amount,
        );

        const nextCart = [...cartItems.value];
        nextCart[itemIndex] = {
            ...existing,
            quantity: nextQuantity,
        };

        cartItems.value = nextCart;
    } else {
        cartItems.value = [
            ...cartItems.value,
            {
                product_id: product.id,
                quantity: Math.min(product.stock, amount),
                name: product.name,
                price: product.price,
                image_url: product.image_url,
                category_name:
                    product.category_names[0] ?? product.category_name,
            },
        ];
    }

    persistCart();
}

function setRelatedCardQuantity(
    product: RelatedProduct,
    rawValue: string | number,
): void {
    const nextValue = Number.isFinite(Number(rawValue))
        ? Math.max(0, Math.min(product.stock, Math.floor(Number(rawValue))))
        : 0;

    const nextQuantities = { ...relatedQuantities.value };

    if (nextValue === 0) {
        delete nextQuantities[product.id];
    } else {
        nextQuantities[product.id] = nextValue;
    }

    relatedQuantities.value = nextQuantities;
}

function incrementRelatedCard(product: RelatedProduct): void {
    setRelatedCardQuantity(
        product,
        (relatedQuantities.value[product.id] ?? 0) + 1,
    );
}

function decrementRelatedCard(product: RelatedProduct): void {
    setRelatedCardQuantity(
        product,
        (relatedQuantities.value[product.id] ?? 0) - 1,
    );
}

function openRelatedModal(product: RelatedProduct): void {
    selectedRelatedProduct.value = product;
    relatedModalQuantity.value = product.stock > 0 ? 1 : 0;
    relatedModalMessage.value = null;
    document.body.style.overflow = "hidden";
}

function closeRelatedModal(): void {
    selectedRelatedProduct.value = null;
    relatedModalQuantity.value = 1;
    relatedModalMessage.value = null;
    document.body.style.overflow = "";
}

function setRelatedModalQuantity(rawValue: string | number): void {
    if (!selectedRelatedProduct.value) {
        return;
    }

    const minQuantity = selectedRelatedProduct.value.stock > 0 ? 1 : 0;
    const parsed = Math.floor(Number(rawValue) || 0);

    relatedModalQuantity.value = Math.max(
        minQuantity,
        Math.min(selectedRelatedProduct.value.stock, parsed),
    );
}

function incrementRelatedModalQuantity(): void {
    setRelatedModalQuantity(relatedModalQuantity.value + 1);
}

function decrementRelatedModalQuantity(): void {
    setRelatedModalQuantity(relatedModalQuantity.value - 1);
}

function addSelectedRelatedProductToCart(): void {
    if (!selectedRelatedProduct.value) {
        return;
    }

    addRelatedProductToCart(
        selectedRelatedProduct.value,
        relatedModalQuantity.value,
    );
    relatedModalMessage.value = `${selectedRelatedProduct.value.name} agregado al carrito.`;

    if (relatedModalMessageTimeout !== null) {
        window.clearTimeout(relatedModalMessageTimeout);
    }

    relatedModalMessageTimeout = window.setTimeout(() => {
        relatedModalMessage.value = null;
    }, 2500);
}

function toggleSelectedRelatedFavorite(): void {
    if (!selectedRelatedProduct.value) {
        return;
    }

    toggleFavoriteById(selectedRelatedProduct.value.id);
}

function handleEscape(event: KeyboardEvent): void {
    if (event.key === "Escape" && selectedRelatedProduct.value) {
        closeRelatedModal();
    }
}

function handleStorage(event: StorageEvent): void {
    if (event.key === CART_STORAGE_KEY) {
        loadCart();
    }

    if (event.key === FAVORITES_STORAGE_KEY) {
        loadFavorites();
    }
}

onMounted(() => {
    loadCart();
    loadFavorites();

    window.addEventListener("storage", handleStorage);
    window.addEventListener("keydown", handleEscape);
});

onBeforeUnmount(() => {
    window.removeEventListener("storage", handleStorage);
    window.removeEventListener("keydown", handleEscape);

    if (addMessageTimeout !== null) {
        window.clearTimeout(addMessageTimeout);
    }

    if (relatedModalMessageTimeout !== null) {
        window.clearTimeout(relatedModalMessageTimeout);
    }

    document.body.style.overflow = "";
});
</script>

<template>
    <Head :title="`${product.name} · ${siteName}`" />

    <div class="min-h-screen bg-slate-100 pb-12 pt-6 text-slate-900 sm:pt-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <StoreTopBar />

            <main class="pt-6">
                <nav class="text-sm text-slate-500">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li
                            v-for="(crumb, index) in breadcrumbs"
                            :key="`${crumb}-${index}`"
                        >
                            <span v-if="index > 0" class="mr-2 text-slate-300"
                                >/</span
                            >
                            <span
                                :class="
                                    index === breadcrumbs.length - 1
                                        ? 'font-semibold text-slate-900'
                                        : ''
                                "
                            >
                                {{ crumb }}
                            </span>
                        </li>
                    </ol>
                </nav>

                <section
                    class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]"
                >
                    <article
                        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"
                    >
                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100"
                        >
                            <img
                                v-if="product.image_url"
                                :src="product.image_url"
                                :alt="product.name"
                                class="h-full max-h-[620px] w-full object-contain"
                            />
                            <div
                                v-else
                                class="aspect-square w-full bg-gradient-to-br from-slate-100 via-slate-200 to-slate-100"
                            ></div>
                        </div>
                        <p
                            class="mt-4 text-xs uppercase tracking-[0.2em] text-slate-500"
                        >
                            {{
                                product.category_names.length
                                    ? product.category_names.join(" / ")
                                    : product.category_name || "Catalogo"
                            }}
                        </p>
                    </article>

                    <article
                        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    >
                        <p
                            class="inline-flex rounded-full bg-gradient-to-r from-orange-500 to-amber-400 px-4 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white"
                        >
                            Garantia DAZ
                        </p>

                        <h1
                            class="mt-4 text-3xl font-semibold leading-tight text-slate-950 sm:text-4xl"
                        >
                            {{ product.name }}
                        </h1>

                        <p
                            class="mt-2 text-sm uppercase tracking-[0.14em] text-slate-500"
                        >
                            {{
                                product.category_names.length
                                    ? product.category_names.join(" / ")
                                    : product.category_name || "Catalogo"
                            }}
                        </p>

                        <p class="mt-4 text-5xl font-semibold text-orange-600">
                            {{ formatMoney(product.price) }}
                        </p>

                        <span
                            class="mt-4 inline-flex rounded-full px-4 py-1 text-sm font-semibold"
                            :class="stockBadgeClass"
                        >
                            {{ stockLabel }}
                        </span>

                        <div class="mt-6 border-t border-slate-200 pt-5">
                            <h2
                                class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500"
                            >
                                Descripcion
                            </h2>
                            <p class="mt-3 text-base leading-7 text-slate-700">
                                {{ productDescription }}
                            </p>
                        </div>

                        <div class="mt-7 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="h-11 w-11 rounded-full border border-slate-300 text-lg font-semibold text-slate-700"
                                @click="decrementQuantity"
                            >
                                -
                            </button>
                            <input
                                :value="quantity"
                                type="number"
                                :min="product.stock > 0 ? 1 : 0"
                                :max="product.stock"
                                inputmode="numeric"
                                class="h-11 w-20 rounded-full border border-slate-300 text-center text-sm font-semibold"
                                @input="
                                    setQuantity(
                                        Number(
                                            ($event.target as HTMLInputElement)
                                                .value,
                                        ),
                                    )
                                "
                            />
                            <button
                                type="button"
                                class="h-11 w-11 rounded-full border border-slate-300 bg-slate-950 text-lg font-semibold text-white"
                                @click="incrementQuantity"
                            >
                                +
                            </button>
                            <button
                                type="button"
                                class="rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                                :disabled="product.stock <= 0"
                                @click="addCurrentProductToCart"
                            >
                                Agregar al carrito
                            </button>
                            <button
                                type="button"
                                class="rounded-full border px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] transition"
                                :class="
                                    isFavorite
                                        ? 'border-rose-300 bg-rose-50 text-rose-700'
                                        : 'border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900'
                                "
                                @click="toggleFavorite"
                            >
                                {{ isFavorite ? "En favoritos" : "Favorito" }}
                            </button>
                        </div>

                        <p
                            v-if="addMessage"
                            class="mt-4 rounded-xl bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-800"
                        >
                            {{ addMessage }}
                        </p>
                    </article>
                </section>

                <section v-if="related_products.length" class="mt-10">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-2xl font-semibold text-slate-950">
                            Tambien te puede interesar
                        </h2>
                        <Link
                            :href="route('storefront')"
                            class="text-sm font-semibold text-orange-600 transition hover:text-orange-700"
                        >
                            Ver todo
                        </Link>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <article
                            v-for="related in related_products"
                            :key="related.id"
                            class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                        >
                            <button
                                type="button"
                                class="h-44 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                                @click="openRelatedModal(related)"
                            >
                                <img
                                    v-if="related.image_url"
                                    :src="related.image_url"
                                    :alt="related.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 via-slate-200 to-slate-100 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400"
                                >
                                    no image
                                </div>
                            </button>
                            <div
                                class="mt-3 flex min-h-[22px] flex-wrap items-center gap-1"
                            >
                                <a
                                    v-for="category in related.category_links"
                                    :key="`${related.id}-cat-${category.href}`"
                                    :href="category.href"
                                    class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-600 transition hover:border-slate-900 hover:text-slate-900"
                                >
                                    {{ category.name }}
                                </a>
                                <span
                                    v-if="!related.category_links.length"
                                    class="text-xs uppercase tracking-[0.14em] text-slate-500"
                                >
                                    {{ related.category_name || "Catalogo" }}
                                </span>
                            </div>
                            <button
                                type="button"
                                class="mt-1 h-12 text-left text-base font-semibold leading-tight text-slate-900 transition hover:text-orange-600"
                                @click="openRelatedModal(related)"
                            >
                                {{ related.name }}
                            </button>

                            <p
                                class="mt-2 h-12 overflow-hidden text-sm leading-6 text-slate-600"
                            >
                                {{
                                    related.description ||
                                    related.hero_tag ||
                                    "Producto disponible para venta mayorista."
                                }}
                            </p>

                            <div
                                class="mt-3 flex items-center justify-between gap-3"
                            >
                                <p
                                    class="text-2xl font-semibold text-orange-600"
                                >
                                    {{ formatMoney(related.price) }}
                                </p>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="stockBadgeClassByProduct(related)"
                                >
                                    {{ stockLabelByProduct(related) }}
                                </span>
                            </div>

                            <div class="mt-auto pt-4">
                                <div
                                    class="grid grid-cols-[2.5rem_minmax(0,1fr)_2.5rem] items-center gap-2"
                                >
                                    <button
                                        type="button"
                                        class="h-10 rounded-xl border border-slate-300 bg-white text-lg font-semibold text-slate-700 transition hover:border-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="
                                            (relatedQuantities[related.id] ??
                                                0) <= 0
                                        "
                                        @click="decrementRelatedCard(related)"
                                    >
                                        -
                                    </button>
                                    <input
                                        :value="
                                            relatedQuantities[related.id] ?? 0
                                        "
                                        type="number"
                                        inputmode="numeric"
                                        min="0"
                                        :max="related.stock"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-300 text-center text-sm font-semibold text-slate-900"
                                        @input="
                                            setRelatedCardQuantity(
                                                related,
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            )
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="h-10 rounded-xl border border-slate-900 bg-slate-950 text-lg font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="
                                            related.stock <= 0 ||
                                            (relatedQuantities[related.id] ??
                                                0) >= related.stock
                                        "
                                        @click="incrementRelatedCard(related)"
                                    >
                                        +
                                    </button>
                                </div>

                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                        @click="openRelatedModal(related)"
                                    >
                                        Ver
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl bg-orange-500 px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                                        :disabled="related.stock <= 0"
                                        @click="
                                            addRelatedProductToCart(
                                                related,
                                                relatedQuantities[related.id] ??
                                                    1,
                                            )
                                        "
                                    >
                                        Anadir al carrito
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    class="mt-2 w-full rounded-xl border px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] transition"
                                    :class="
                                        isFavoriteId(related.id)
                                            ? 'border-rose-300 bg-rose-50 text-rose-700'
                                            : 'border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900'
                                    "
                                    @click="toggleFavoriteById(related.id)"
                                >
                                    {{
                                        isFavoriteId(related.id)
                                            ? "En favoritos"
                                            : "Favorito"
                                    }}
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

                <div
                    v-if="selectedRelatedProduct"
                    class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/65 p-4"
                    @click="closeRelatedModal"
                >
                    <article
                        class="relative max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl sm:p-6"
                        @click.stop
                    >
                        <button
                            type="button"
                            class="absolute right-4 top-4 h-10 w-10 rounded-full border border-slate-300 text-lg font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            @click="closeRelatedModal"
                        >
                            x
                        </button>

                        <div
                            class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]"
                        >
                            <div
                                class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100"
                            >
                                <img
                                    v-if="selectedRelatedProduct.image_url"
                                    :src="selectedRelatedProduct.image_url"
                                    :alt="selectedRelatedProduct.name"
                                    class="h-full max-h-[620px] w-full object-contain"
                                />
                                <div
                                    v-else
                                    class="aspect-square w-full bg-gradient-to-br from-slate-100 via-slate-200 to-slate-100"
                                ></div>
                            </div>

                            <div class="pt-2">
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500"
                                >
                                    {{
                                        selectedRelatedProduct.category_names
                                            .length
                                            ? selectedRelatedProduct.category_names.join(
                                                  " / ",
                                              )
                                            : selectedRelatedProduct.category_name ||
                                              "Catalogo"
                                    }}
                                </p>

                                <h3
                                    class="mt-2 text-3xl font-semibold leading-tight text-slate-950"
                                >
                                    {{ selectedRelatedProduct.name }}
                                </h3>

                                <p
                                    class="mt-4 text-5xl font-semibold text-orange-600"
                                >
                                    {{
                                        formatMoney(
                                            selectedRelatedProduct.price,
                                        )
                                    }}
                                </p>

                                <span
                                    class="mt-4 inline-flex rounded-full px-4 py-1 text-sm font-semibold"
                                    :class="
                                        stockBadgeClassByProduct(
                                            selectedRelatedProduct,
                                        )
                                    "
                                >
                                    {{
                                        stockLabelByProduct(
                                            selectedRelatedProduct,
                                        )
                                    }}
                                </span>

                                <div
                                    class="mt-6 border-t border-slate-200 pt-5"
                                >
                                    <h4
                                        class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500"
                                    >
                                        Descripcion
                                    </h4>
                                    <p
                                        class="mt-3 text-base leading-7 text-slate-700"
                                    >
                                        {{
                                            selectedRelatedProduct.description ||
                                            selectedRelatedProduct.hero_tag ||
                                            "Producto disponible para venta mayorista."
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="mt-7 flex flex-wrap items-center gap-2"
                                >
                                    <button
                                        type="button"
                                        class="h-11 w-11 rounded-full border border-slate-300 text-lg font-semibold text-slate-700"
                                        @click="decrementRelatedModalQuantity"
                                    >
                                        -
                                    </button>
                                    <input
                                        :value="relatedModalQuantity"
                                        type="number"
                                        :min="
                                            selectedRelatedProduct.stock > 0
                                                ? 1
                                                : 0
                                        "
                                        :max="selectedRelatedProduct.stock"
                                        inputmode="numeric"
                                        class="h-11 w-20 rounded-full border border-slate-300 text-center text-sm font-semibold"
                                        @input="
                                            setRelatedModalQuantity(
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            )
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="h-11 w-11 rounded-full border border-slate-300 bg-slate-950 text-lg font-semibold text-white"
                                        @click="incrementRelatedModalQuantity"
                                    >
                                        +
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                                        :disabled="
                                            selectedRelatedProduct.stock <= 0
                                        "
                                        @click="addSelectedRelatedProductToCart"
                                    >
                                        Agregar al carrito
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full border px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] transition"
                                        :class="
                                            isFavoriteId(
                                                selectedRelatedProduct.id,
                                            )
                                                ? 'border-rose-300 bg-rose-50 text-rose-700'
                                                : 'border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900'
                                        "
                                        @click="toggleSelectedRelatedFavorite"
                                    >
                                        {{
                                            isFavoriteId(
                                                selectedRelatedProduct.id,
                                            )
                                                ? "En favoritos"
                                                : "Favorito"
                                        }}
                                    </button>
                                </div>

                                <p
                                    v-if="relatedModalMessage"
                                    class="mt-4 rounded-xl bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-800"
                                >
                                    {{ relatedModalMessage }}
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            </main>
        </div>
    </div>
</template>
