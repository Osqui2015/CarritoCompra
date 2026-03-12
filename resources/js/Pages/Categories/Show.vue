<script setup lang="ts">
import StoreTopBar from "@/Components/StoreTopBar.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { PageProps } from "@/types";

interface CategoryPayload {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    accent_color: string;
    catalog_pdf_url: string;
}

interface ProductItem {
    id: number;
    name: string;
    slug: string;
    hero_tag: string | null;
    description: string | null;
    price: number;
    stock: number;
    stock_reference: number;
    image_url: string | null;
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
    category: CategoryPayload;
    products: ProductItem[];
    breadcrumbs: string[];
}>();

const page = usePage<PageProps>();
const cartItems = ref<StoredCartItem[]>([]);
const favoriteIds = ref<number[]>([]);
const quantities = ref<Record<number, number>>({});
const selectedProduct = ref<ProductItem | null>(null);
const modalQuantity = ref(1);
const modalMessage = ref<string | null>(null);
let modalMessageTimeout: number | null = null;

const siteName = computed(
    () => page.props.branding?.site_name || "TUS TECNOLOGIAS",
);

function formatMoney(value: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)}`;
}

function stockRatio(product: ProductItem): number {
    const reference = Math.max(product.stock_reference, 1);

    return product.stock / reference;
}

function stockBadgeClass(product: ProductItem): string {
    if (product.stock <= 0) {
        return "bg-rose-100 text-rose-700";
    }

    const ratio = stockRatio(product);

    if (ratio < 0.3) {
        return "bg-amber-100 text-amber-700";
    }

    if (ratio < 0.7) {
        return "bg-yellow-100 text-yellow-800";
    }

    return "bg-emerald-100 text-emerald-700";
}

function stockLabel(product: ProductItem): string {
    if (product.stock <= 0) {
        return "Sin stock";
    }

    const ratio = stockRatio(product);

    if (ratio < 0.3) {
        return "Stock bajo";
    }

    if (ratio < 0.7) {
        return "Stock medio";
    }

    return "Stock alto";
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

function addToCart(product: ProductItem, rawAmount = 1): void {
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
                category_name: product.category_names[0] ?? null,
            },
        ];
    }

    persistCart();
}

function setQuantity(product: ProductItem, rawValue: string | number): void {
    const nextValue = Number.isFinite(Number(rawValue))
        ? Math.max(0, Math.min(product.stock, Math.floor(Number(rawValue))))
        : 0;

    const nextQuantities = { ...quantities.value };

    if (nextValue === 0) {
        delete nextQuantities[product.id];
    } else {
        nextQuantities[product.id] = nextValue;
    }

    quantities.value = nextQuantities;
}

function increment(product: ProductItem): void {
    setQuantity(product, (quantities.value[product.id] ?? 0) + 1);
}

function decrement(product: ProductItem): void {
    setQuantity(product, (quantities.value[product.id] ?? 0) - 1);
}

function openProductModal(product: ProductItem): void {
    selectedProduct.value = product;
    modalQuantity.value = product.stock > 0 ? 1 : 0;
    modalMessage.value = null;
    document.body.style.overflow = "hidden";
}

function closeProductModal(): void {
    selectedProduct.value = null;
    modalQuantity.value = 1;
    modalMessage.value = null;
    document.body.style.overflow = "";
}

function setModalQuantity(rawValue: string | number): void {
    if (!selectedProduct.value) {
        return;
    }

    const minQuantity = selectedProduct.value.stock > 0 ? 1 : 0;
    const parsed = Math.floor(Number(rawValue) || 0);

    modalQuantity.value = Math.max(
        minQuantity,
        Math.min(selectedProduct.value.stock, parsed),
    );
}

function incrementModalQuantity(): void {
    setModalQuantity(modalQuantity.value + 1);
}

function decrementModalQuantity(): void {
    setModalQuantity(modalQuantity.value - 1);
}

function addSelectedProductToCart(): void {
    if (!selectedProduct.value) {
        return;
    }

    addToCart(selectedProduct.value, modalQuantity.value);
    modalMessage.value = `${selectedProduct.value.name} agregado al carrito.`;

    if (modalMessageTimeout !== null) {
        window.clearTimeout(modalMessageTimeout);
    }

    modalMessageTimeout = window.setTimeout(() => {
        modalMessage.value = null;
    }, 2500);
}

function toggleSelectedFavorite(): void {
    if (!selectedProduct.value) {
        return;
    }

    toggleFavorite(selectedProduct.value.id);
}

function handleEscape(event: KeyboardEvent): void {
    if (event.key === "Escape" && selectedProduct.value) {
        closeProductModal();
    }
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

function isFavorite(productId: number): boolean {
    return favoriteIds.value.includes(productId);
}

function toggleFavorite(productId: number): void {
    if (isFavorite(productId)) {
        favoriteIds.value = favoriteIds.value.filter((id) => id !== productId);
    } else {
        favoriteIds.value = [...favoriteIds.value, productId];
    }

    persistFavorites();
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

    if (modalMessageTimeout !== null) {
        window.clearTimeout(modalMessageTimeout);
    }

    document.body.style.overflow = "";
});
</script>

<template>
    <Head :title="`${category.name} · ${siteName}`" />

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
                    class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500"
                            >
                                Categoria
                            </p>
                            <h1
                                class="mt-2 text-3xl font-semibold text-slate-950"
                            >
                                {{ category.name }}
                            </h1>
                            <p
                                class="mt-3 max-w-2xl text-sm leading-6 text-slate-600"
                            >
                                {{
                                    category.description ||
                                    "Explora los productos disponibles en esta categoria."
                                }}
                            </p>
                        </div>

                        <a
                            :href="category.catalog_pdf_url"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex rounded-full border border-slate-300 bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                        >
                            Descargar catalogo PDF
                        </a>
                    </div>
                </section>

                <section class="mt-8">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-2xl font-semibold text-slate-950">
                            Productos
                        </h2>
                        <p class="text-sm text-slate-500">
                            {{ products.length }} disponibles
                        </p>
                    </div>

                    <div
                        v-if="products.length"
                        class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <article
                            v-for="product in products"
                            :key="product.id"
                            class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                        >
                            <button
                                type="button"
                                class="block h-44 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                                @click="openProductModal(product)"
                            >
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
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
                                    v-for="category in product.category_links"
                                    :key="`${product.id}-cat-${category.href}`"
                                    :href="category.href"
                                    class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-600 transition hover:border-slate-900 hover:text-slate-900"
                                >
                                    {{ category.name }}
                                </a>
                                <span
                                    v-if="!product.category_links.length"
                                    class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"
                                >
                                    Catalogo
                                </span>
                            </div>

                            <button
                                type="button"
                                class="mt-1 block h-12 text-left text-base font-semibold leading-tight text-slate-900 transition hover:text-orange-600"
                                @click="openProductModal(product)"
                            >
                                {{ product.name }}
                            </button>

                            <p
                                class="mt-2 h-12 overflow-hidden text-sm leading-6 text-slate-600"
                            >
                                {{
                                    product.description ||
                                    product.hero_tag ||
                                    "Producto disponible para venta mayorista."
                                }}
                            </p>

                            <div
                                class="mt-3 flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-2xl font-semibold text-orange-600"
                                >
                                    {{ formatMoney(product.price) }}
                                </span>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="stockBadgeClass(product)"
                                >
                                    {{ stockLabel(product) }}
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
                                            (quantities[product.id] ?? 0) <= 0
                                        "
                                        @click="decrement(product)"
                                    >
                                        -
                                    </button>
                                    <input
                                        :value="quantities[product.id] ?? 0"
                                        type="number"
                                        inputmode="numeric"
                                        min="0"
                                        :max="product.stock"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-300 text-center text-sm font-semibold text-slate-900"
                                        @input="
                                            setQuantity(
                                                product,
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
                                            product.stock <= 0 ||
                                            (quantities[product.id] ?? 0) >=
                                                product.stock
                                        "
                                        @click="increment(product)"
                                    >
                                        +
                                    </button>
                                </div>

                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                        @click="openProductModal(product)"
                                    >
                                        Ver
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl bg-orange-500 px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                                        :disabled="product.stock <= 0"
                                        @click="
                                            addToCart(
                                                product,
                                                quantities[product.id] ?? 1,
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
                                        isFavorite(product.id)
                                            ? 'border-rose-300 bg-rose-50 text-rose-700'
                                            : 'border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900'
                                    "
                                    @click="toggleFavorite(product.id)"
                                >
                                    {{
                                        isFavorite(product.id)
                                            ? "En favoritos"
                                            : "Favorito"
                                    }}
                                </button>
                            </div>
                        </article>
                    </div>

                    <article
                        v-else
                        class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500"
                    >
                        Esta categoria todavia no tiene productos activos.
                    </article>
                </section>

                <div
                    v-if="selectedProduct"
                    class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/65 p-4"
                    @click="closeProductModal"
                >
                    <article
                        class="relative max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl sm:p-6"
                        @click.stop
                    >
                        <button
                            type="button"
                            class="absolute right-4 top-4 h-10 w-10 rounded-full border border-slate-300 text-lg font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            @click="closeProductModal"
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
                                    v-if="selectedProduct.image_url"
                                    :src="selectedProduct.image_url"
                                    :alt="selectedProduct.name"
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
                                        selectedProduct.category_names.join(
                                            " / ",
                                        )
                                    }}
                                </p>

                                <h3
                                    class="mt-2 text-3xl font-semibold leading-tight text-slate-950"
                                >
                                    {{ selectedProduct.name }}
                                </h3>

                                <p
                                    class="mt-4 text-5xl font-semibold text-orange-600"
                                >
                                    {{ formatMoney(selectedProduct.price) }}
                                </p>

                                <span
                                    class="mt-4 inline-flex rounded-full px-4 py-1 text-sm font-semibold"
                                    :class="stockBadgeClass(selectedProduct)"
                                >
                                    {{ stockLabel(selectedProduct) }}
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
                                            selectedProduct.description ||
                                            selectedProduct.hero_tag ||
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
                                        @click="decrementModalQuantity"
                                    >
                                        -
                                    </button>
                                    <input
                                        :value="modalQuantity"
                                        type="number"
                                        :min="selectedProduct.stock > 0 ? 1 : 0"
                                        :max="selectedProduct.stock"
                                        inputmode="numeric"
                                        class="h-11 w-20 rounded-full border border-slate-300 text-center text-sm font-semibold"
                                        @input="
                                            setModalQuantity(
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            )
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="h-11 w-11 rounded-full border border-slate-300 bg-slate-950 text-lg font-semibold text-white"
                                        @click="incrementModalQuantity"
                                    >
                                        +
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                                        :disabled="selectedProduct.stock <= 0"
                                        @click="addSelectedProductToCart"
                                    >
                                        Agregar al carrito
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full border px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] transition"
                                        :class="
                                            isFavorite(selectedProduct.id)
                                                ? 'border-rose-300 bg-rose-50 text-rose-700'
                                                : 'border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900'
                                        "
                                        @click="toggleSelectedFavorite"
                                    >
                                        {{
                                            isFavorite(selectedProduct.id)
                                                ? "En favoritos"
                                                : "Favorito"
                                        }}
                                    </button>
                                </div>

                                <p
                                    v-if="modalMessage"
                                    class="mt-4 rounded-xl bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-800"
                                >
                                    {{ modalMessage }}
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            </main>
        </div>
    </div>
</template>
