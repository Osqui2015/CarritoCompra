<script setup lang="ts">
import axios from "axios";
import ProductSearchInput from "@/Components/ProductSearchInput.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from "vue";
import type { PageProps } from "@/types";

interface CategoryHighlight {
    id: number;
    name: string;
    slug: string;
    accent_color: string;
    description: string | null;
    product_count: number;
    icon: string;
    catalog_pdf_url: string;
}

interface FeaturedProduct {
    id: number;
    name: string;
    slug: string;
    hero_tag: string | null;
    is_featured: boolean;
    description: string | null;
    price: number;
    stock: number;
    stock_reference: number;
    category_name: string | null;
    category_links: Array<{
        name: string;
        href: string;
    }>;
    image_url: string | null;
    created_at: string | null;
    updated_at: string | null;
}

interface PromotionCard {
    title: string;
    subtitle: string;
    cta: string;
}

interface HeroBanner {
    id: number;
    title: string;
    subtitle: string | null;
    link_url: string | null;
    image_url: string | null;
}

interface SideBanner {
    id: number;
    title: string;
    subtitle: string | null;
    link_url: string | null;
    image_url: string | null;
}

interface NavGroupItem {
    label: string;
    href: string;
}

interface NavGroup {
    label: string;
    items: NavGroupItem[];
}

interface CheckoutDefaults {
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    shipping_address: string;
}

interface AppearanceSettings {
    hero_banner_title: string | null;
    hero_banner_subtitle: string | null;
    hero_banner_image_url: string | null;
    hero_banner_link_url: string | null;
    store_address: string | null;
    business_hours: string | null;
    sales_whatsapp: string | null;
}

interface AppliedCoupon {
    code: string;
    type: "percentage" | "fixed";
    value: number;
}

interface CheckoutForm {
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    shipping_address: string;
    notes: string;
    coupon_code: string;
    items: Array<{
        product_id: number;
        quantity: number;
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
const PRODUCTS_PER_PAGE = 12;
const NEW_PRODUCT_WINDOW_DAYS = 30;

type ProductFilterMode = "all" | "new" | "featured";
type ProductSortMode = "newest" | "name_asc" | "price_desc" | "price_asc";

const props = defineProps<{
    categories: CategoryHighlight[];
    featured_products: FeaturedProduct[];
    hero_banners: HeroBanner[];
    side_banners: SideBanner[];
    nav_groups: NavGroup[];
    promotions: PromotionCard[];
    checkout_defaults: CheckoutDefaults;
    appearance: AppearanceSettings;
    abandoned_cart_sync_enabled: boolean;
}>();

const page = usePage<PageProps>();
const quantities = ref<Record<number, number>>({});
const appliedCoupon = ref<AppliedCoupon | null>(null);
const couponCodeInput = ref("");
const couponError = ref<string | null>(null);
const couponLoading = ref(false);
const clientErrors = ref<Record<string, string>>({});
const syncStatus = ref<"idle" | "syncing">("idle");
const heroBannerIndex = ref(0);
const isCartDropdownOpen = ref(false);
const addToCartMessage = ref<string | null>(null);
const favoriteIds = ref<number[]>([]);
const isLoginModalOpen = ref(false);
const isMobileMenuOpen = ref(false);
const loginLoading = ref(false);
const loginErrors = ref<Record<string, string>>({});
const selectedProduct = ref<FeaturedProduct | null>(null);
const modalQuantity = ref(1);
const modalMessage = ref<string | null>(null);
const cartMenuRef = ref<HTMLElement | null>(null);
const activeProductFilter = ref<ProductFilterMode>("all");
const activeProductSort = ref<ProductSortMode>("newest");
const currentProductsPage = ref(1);
const loginForm = reactive({
    email: "",
    password: "",
    remember: false,
});
let abandonedCartSyncTimer: number | null = null;
let heroBannerInterval: number | null = null;
let addMessageTimeout: number | null = null;
let modalMessageTimeout: number | null = null;

const form = useForm<CheckoutForm>({
    customer_name: props.checkout_defaults.customer_name ?? "",
    customer_email: props.checkout_defaults.customer_email ?? "",
    customer_phone: props.checkout_defaults.customer_phone ?? "",
    shipping_address: props.checkout_defaults.shipping_address ?? "",
    notes: "",
    coupon_code: "",
    items: [],
});

const flash = computed(() => page.props.flash);

const allCatalogProducts = computed<FeaturedProduct[]>(
    () => props.featured_products,
);

function resolveProductTimestamp(product: FeaturedProduct): number {
    const candidate = product.created_at ?? product.updated_at;

    if (!candidate) {
        return 0;
    }

    const parsed = Date.parse(candidate);

    return Number.isNaN(parsed) ? 0 : parsed;
}

function isProductNew(product: FeaturedProduct): boolean {
    const timestamp = resolveProductTimestamp(product);

    if (timestamp <= 0) {
        return false;
    }

    const ageInMs = Date.now() - timestamp;

    return ageInMs <= NEW_PRODUCT_WINDOW_DAYS * 24 * 60 * 60 * 1000;
}

const filteredProducts = computed<FeaturedProduct[]>(() => {
    if (activeProductFilter.value === "featured") {
        return allCatalogProducts.value.filter(
            (product) => product.is_featured,
        );
    }

    if (activeProductFilter.value === "new") {
        return allCatalogProducts.value.filter((product) =>
            isProductNew(product),
        );
    }

    return allCatalogProducts.value;
});

const sortedProducts = computed<FeaturedProduct[]>(() => {
    const products = [...filteredProducts.value];

    if (activeProductSort.value === "name_asc") {
        return products.sort((left, right) =>
            left.name.localeCompare(right.name, "es", { sensitivity: "base" }),
        );
    }

    if (activeProductSort.value === "price_asc") {
        return products.sort((left, right) => left.price - right.price);
    }

    if (activeProductSort.value === "price_desc") {
        return products.sort((left, right) => right.price - left.price);
    }

    return products.sort(
        (left, right) =>
            resolveProductTimestamp(right) - resolveProductTimestamp(left),
    );
});

const totalProductPages = computed(() =>
    Math.max(1, Math.ceil(sortedProducts.value.length / PRODUCTS_PER_PAGE)),
);

const paginatedProducts = computed<FeaturedProduct[]>(() => {
    const start = (currentProductsPage.value - 1) * PRODUCTS_PER_PAGE;

    return sortedProducts.value.slice(start, start + PRODUCTS_PER_PAGE);
});

const selectedItems = computed(() =>
    allCatalogProducts.value
        .map((product) => ({
            product,
            quantity: quantities.value[product.id] ?? 0,
        }))
        .filter(({ quantity }) => quantity > 0),
);

const storageCartItems = computed<StoredCartItem[]>(() =>
    selectedItems.value.map(({ product, quantity }) => ({
        product_id: product.id,
        quantity,
        name: product.name,
        price: product.price,
        image_url: product.image_url,
        category_name: product.category_name,
    })),
);

const totalItems = computed(() =>
    selectedItems.value.reduce((carry, item) => carry + item.quantity, 0),
);

const subtotalCents = computed(() =>
    selectedItems.value.reduce((carry, item) => {
        const unitPriceCents = Math.round(item.product.price * 100);

        return carry + unitPriceCents * item.quantity;
    }, 0),
);

const discountCents = computed(() => {
    if (!appliedCoupon.value) {
        return 0;
    }

    if (appliedCoupon.value.type === "percentage") {
        return Math.min(
            subtotalCents.value,
            Math.floor(subtotalCents.value * (appliedCoupon.value.value / 100)),
        );
    }

    return Math.min(
        subtotalCents.value,
        Math.round(appliedCoupon.value.value * 100),
    );
});

const totalCents = computed(() =>
    Math.max(0, subtotalCents.value - discountCents.value),
);

const siteName = computed(
    () => page.props.branding?.site_name || "TUS TECNOLOGIAS",
);

const siteLogoUrl = computed(() => page.props.branding?.site_logo || null);

const salesWhatsappUrl = computed(() => {
    const phone = props.appearance.sales_whatsapp?.replace(/\D+/g, "") ?? "";

    return phone ? `https://wa.me/${phone}` : null;
});

const currentHeroBanner = computed(() => {
    if (!props.hero_banners.length) {
        return null;
    }

    return props.hero_banners[heroBannerIndex.value] ?? props.hero_banners[0];
});

const resolvedSideBanners = computed<SideBanner[]>(() => {
    if (props.side_banners.length) {
        return props.side_banners;
    }

    return props.promotions.slice(0, 2).map((promotion, index) => ({
        id: index + 1,
        title: promotion.title,
        subtitle: promotion.subtitle,
        link_url: null,
        image_url: null,
    }));
});

function formatMoney(amount: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount)}`;
}

function formatMoneyFromCents(amountCents: number): string {
    return formatMoney(amountCents / 100);
}

function stockRatio(product: FeaturedProduct): number {
    const reference = Math.max(product.stock_reference, 1);

    return product.stock / reference;
}

function stockBadgeClass(product: FeaturedProduct): string {
    if (product.stock <= 0) {
        return "bg-rose-100 text-rose-700";
    }

    const ratio = stockRatio(product);

    if (ratio < 0.3) {
        return "bg-amber-100 text-amber-700";
    }

    if (ratio < 0.7) {
        return "bg-sky-100 text-sky-700";
    }

    return "bg-emerald-100 text-emerald-700";
}

function stockLabel(product: FeaturedProduct): string {
    if (product.stock <= 0) {
        return "Sin stock";
    }

    const ratio = stockRatio(product);

    if (ratio < 0.3) {
        return `Stock bajo (${product.stock})`;
    }

    if (ratio < 0.7) {
        return `Stock medio (${product.stock})`;
    }

    return `Stock alto (${product.stock})`;
}

function setProductFilter(filter: ProductFilterMode): void {
    activeProductFilter.value = filter;
}

function goToProductsPage(nextPage: number): void {
    const boundedPage = Math.min(
        Math.max(1, nextPage),
        totalProductPages.value,
    );

    currentProductsPage.value = boundedPage;
}

function goToPreviousProductsPage(): void {
    goToProductsPage(currentProductsPage.value - 1);
}

function goToNextProductsPage(): void {
    goToProductsPage(currentProductsPage.value + 1);
}

function setQuantity(
    product: FeaturedProduct,
    rawValue: string | number,
): void {
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

    if (subtotalCents.value === 0) {
        clearCoupon();
    }
}

function addToCart(product: FeaturedProduct, rawAmount = 1): void {
    if (product.stock <= 0) {
        return;
    }

    const amount = Math.max(1, Math.floor(rawAmount || 1));

    setQuantity(product, (quantities.value[product.id] ?? 0) + amount);

    addToCartMessage.value = `Se agrego ${product.name} al carrito.`;
    isCartDropdownOpen.value = true;

    if (addMessageTimeout !== null) {
        window.clearTimeout(addMessageTimeout);
    }

    addMessageTimeout = window.setTimeout(() => {
        addToCartMessage.value = null;
    }, 2500);
}

function increment(product: FeaturedProduct): void {
    addToCart(product);
}

function decrement(product: FeaturedProduct): void {
    setQuantity(product, (quantities.value[product.id] ?? 0) - 1);
}

function clearCoupon(): void {
    appliedCoupon.value = null;
    couponCodeInput.value = "";
    couponError.value = null;
    form.coupon_code = "";
}

function toggleCartDropdown(): void {
    isCartDropdownOpen.value = !isCartDropdownOpen.value;
}

function removeFromCart(product: FeaturedProduct): void {
    setQuantity(product, 0);
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

function syncOverlayState(): void {
    document.body.style.overflow =
        selectedProduct.value || isLoginModalOpen.value ? "hidden" : "";
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

            return;
        }

        loginErrors.value = {
            general: "No se pudo iniciar sesion. Intenta nuevamente.",
        };
    } finally {
        loginLoading.value = false;
    }
}

function openProductModal(product: FeaturedProduct): void {
    selectedProduct.value = product;
    modalQuantity.value = product.stock > 0 ? 1 : 0;
    modalMessage.value = null;
    syncOverlayState();
}

function closeProductModal(): void {
    selectedProduct.value = null;
    modalQuantity.value = 1;
    modalMessage.value = null;
    syncOverlayState();
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
    if (event.key !== "Escape") {
        return;
    }

    if (selectedProduct.value) {
        closeProductModal();
        return;
    }

    if (isLoginModalOpen.value) {
        closeLoginModal();
        return;
    }

    if (isMobileMenuOpen.value) {
        isMobileMenuOpen.value = false;
    }
}

function persistCartStorage(): void {
    if (typeof window === "undefined") {
        return;
    }

    window.localStorage.setItem(
        CART_STORAGE_KEY,
        JSON.stringify(storageCartItems.value),
    );
}

function hydrateCartFromStorage(): void {
    if (typeof window === "undefined") {
        return;
    }

    try {
        const raw = window.localStorage.getItem(CART_STORAGE_KEY);
        if (!raw) {
            return;
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return;
        }

        const productMap = new Map(
            allCatalogProducts.value.map((product) => [product.id, product]),
        );

        const nextQuantities: Record<number, number> = {};

        parsed.forEach((item: any) => {
            const productId = Number(item.product_id);
            const quantity = Number(item.quantity);

            if (!productMap.has(productId)) {
                return;
            }

            const product = productMap.get(productId)!;
            const normalizedQty = Math.max(
                0,
                Math.min(product.stock, Math.floor(quantity || 0)),
            );

            if (normalizedQty > 0) {
                nextQuantities[productId] = normalizedQty;
            }
        });

        quantities.value = nextQuantities;
    } catch {
        // Ignore malformed storage payloads.
    }
}

function handleDocumentClick(event: MouseEvent): void {
    const target = event.target as Node;
    const menu = cartMenuRef.value;

    if (menu && !menu.contains(target)) {
        isCartDropdownOpen.value = false;
    }
}

function handleStorage(event: StorageEvent): void {
    if (event.key === CART_STORAGE_KEY) {
        hydrateCartFromStorage();
    }

    if (event.key === FAVORITES_STORAGE_KEY) {
        loadFavorites();
    }
}

async function syncAbandonedCart(): Promise<void> {
    if (!props.abandoned_cart_sync_enabled) {
        return;
    }

    syncStatus.value = "syncing";

    try {
        await axios.post(route("abandoned-carts.sync"), {
            items: selectedItems.value.map(({ product, quantity }) => ({
                product_id: product.id,
                quantity,
            })),
        });
    } catch {
        // Silent by design: storefront must keep working even if sync fails.
    } finally {
        syncStatus.value = "idle";
    }
}

function scheduleAbandonedCartSync(): void {
    if (!props.abandoned_cart_sync_enabled) {
        return;
    }

    if (abandonedCartSyncTimer !== null) {
        window.clearTimeout(abandonedCartSyncTimer);
    }

    abandonedCartSyncTimer = window.setTimeout(() => {
        syncAbandonedCart();
    }, 700);
}

function rotateHeroBanner(): void {
    if (props.hero_banners.length <= 1) {
        return;
    }

    heroBannerIndex.value =
        (heroBannerIndex.value + 1) % props.hero_banners.length;
}

async function applyCoupon(): Promise<void> {
    couponError.value = null;

    if (!couponCodeInput.value.trim()) {
        couponError.value = "Ingresa un codigo de descuento.";
        return;
    }

    if (subtotalCents.value <= 0) {
        couponError.value = "Agrega productos antes de aplicar un codigo.";
        return;
    }

    couponLoading.value = true;

    try {
        const response = await axios.post(route("coupons.validate"), {
            coupon_code: couponCodeInput.value,
            subtotal_cents: subtotalCents.value,
        });

        appliedCoupon.value = {
            code: response.data.coupon.code,
            type: response.data.coupon.type,
            value: Number(response.data.coupon.value),
        };
        form.coupon_code = response.data.coupon.code;
        couponCodeInput.value = response.data.coupon.code;
    } catch (error: any) {
        appliedCoupon.value = null;
        form.coupon_code = "";
        couponError.value =
            error?.response?.data?.message ||
            "No se pudo validar el codigo en este momento.";
    } finally {
        couponLoading.value = false;
    }
}

function validateBeforeSubmit(): boolean {
    const nextErrors: Record<string, string> = {};

    if (!form.customer_name.trim()) {
        nextErrors.customer_name = "Ingresa tu nombre.";
    }

    if (!form.customer_email.trim()) {
        nextErrors.customer_email = "Ingresa tu correo.";
    } else if (!/^\S+@\S+\.\S+$/.test(form.customer_email)) {
        nextErrors.customer_email = "Ingresa un correo valido.";
    }

    if (!form.shipping_address.trim()) {
        nextErrors.shipping_address = "Ingresa la direccion de envio.";
    }

    if (!selectedItems.value.length) {
        nextErrors.items = "Debes agregar al menos un producto al carrito.";
    }

    const overflowProduct = selectedItems.value.find(
        ({ product, quantity }) => quantity > product.stock,
    );

    if (overflowProduct) {
        nextErrors.items = `Solo quedan ${overflowProduct.product.stock} unidades de ${overflowProduct.product.name}.`;
    }

    clientErrors.value = nextErrors;

    return Object.keys(nextErrors).length === 0;
}

function submitOrder(): void {
    if (!validateBeforeSubmit()) {
        return;
    }

    form.transform((data) => ({
        ...data,
        coupon_code: appliedCoupon.value?.code ?? "",
        items: selectedItems.value.map(({ product, quantity }) => ({
            product_id: product.id,
            quantity,
        })),
    })).post(route("carts.store"), {
        preserveScroll: true,
        onSuccess: () => {
            quantities.value = {};
            form.reset("items", "notes", "coupon_code");
            form.clearErrors();
            clientErrors.value = {};
            clearCoupon();
        },
    });
}

watch(
    () =>
        selectedItems.value.map(({ product, quantity }) => ({
            product_id: product.id,
            quantity,
        })),
    () => {
        scheduleAbandonedCartSync();
    },
    { deep: true },
);

watch(
    () => storageCartItems.value,
    () => {
        persistCartStorage();
    },
    { deep: true },
);

watch(
    () => props.hero_banners.length,
    (nextLength) => {
        if (nextLength === 0 || heroBannerIndex.value >= nextLength) {
            heroBannerIndex.value = 0;
        }
    },
);

watch([activeProductFilter, activeProductSort], () => {
    currentProductsPage.value = 1;
});

watch(totalProductPages, (nextTotalPages) => {
    if (currentProductsPage.value > nextTotalPages) {
        currentProductsPage.value = nextTotalPages;
    }
});

onMounted(() => {
    hydrateCartFromStorage();
    loadFavorites();

    if (props.hero_banners.length > 1) {
        heroBannerInterval = window.setInterval(() => {
            rotateHeroBanner();
        }, 5000);
    }

    document.addEventListener("click", handleDocumentClick);
    window.addEventListener("storage", handleStorage);
    window.addEventListener("keydown", handleEscape);
    window.addEventListener("resize", handleResize);

    handleResize();
});

onBeforeUnmount(() => {
    if (abandonedCartSyncTimer !== null) {
        window.clearTimeout(abandonedCartSyncTimer);
    }

    if (heroBannerInterval !== null) {
        window.clearInterval(heroBannerInterval);
    }

    if (addMessageTimeout !== null) {
        window.clearTimeout(addMessageTimeout);
    }

    if (modalMessageTimeout !== null) {
        window.clearTimeout(modalMessageTimeout);
    }

    document.removeEventListener("click", handleDocumentClick);
    window.removeEventListener("storage", handleStorage);
    window.removeEventListener("keydown", handleEscape);
    window.removeEventListener("resize", handleResize);
    document.body.style.overflow = "";
});
</script>

<template>
    <Head title="Tienda Mayorista" />

    <div class="min-h-screen pb-16 pt-6 text-slate-900 sm:pt-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header
                class="sticky top-4 z-50 rounded-3xl border border-slate-200 bg-white/90 px-5 py-4 shadow-sm backdrop-blur sm:px-7"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
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
                    </div>

                    <div
                        class="hidden flex-1 items-center justify-end gap-3 sm:flex"
                    >
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
                                $page.props.auth.user &&
                                $page.props.auth.user.is_admin
                            "
                            :href="route('admin.dashboard')"
                            class="rounded-full border border-orange-300 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-50"
                        >
                            Admin
                        </Link>

                        <div ref="cartMenuRef" class="relative">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                @click="toggleCartDropdown"
                            >
                                <span>Carrito</span>
                                <span
                                    class="rounded-full bg-white/15 px-2 py-0.5 text-xs"
                                >
                                    {{ totalItems }}
                                </span>
                            </button>

                            <div
                                v-if="isCartDropdownOpen"
                                class="absolute right-0 mt-3 w-[22rem] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl"
                            >
                                <p
                                    v-if="addToCartMessage"
                                    class="rounded-xl bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-800"
                                >
                                    {{ addToCartMessage }}
                                </p>

                                <p
                                    v-if="abandoned_cart_sync_enabled"
                                    class="mt-2 text-[11px] text-slate-400"
                                >
                                    {{
                                        syncStatus === "syncing"
                                            ? "Guardando carrito..."
                                            : "Carrito sincronizado"
                                    }}
                                </p>

                                <div
                                    class="mt-3 max-h-72 space-y-2 overflow-y-auto"
                                >
                                    <div
                                        v-if="!selectedItems.length"
                                        class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-500"
                                    >
                                        Tu carrito esta vacio.
                                    </div>

                                    <article
                                        v-for="item in selectedItems"
                                        :key="item.product.id"
                                        class="rounded-xl border border-slate-200 bg-slate-50 p-3"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-12 w-12 overflow-hidden rounded-lg border border-slate-200 bg-white"
                                            >
                                                <img
                                                    v-if="
                                                        item.product.image_url
                                                    "
                                                    :src="
                                                        item.product.image_url
                                                    "
                                                    :alt="item.product.name"
                                                    class="h-full w-full object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="h-full w-full bg-gradient-to-br from-slate-100 via-slate-200 to-slate-100"
                                                ></div>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="truncate text-sm font-semibold text-slate-900"
                                                >
                                                    {{ item.product.name }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        formatMoney(
                                                            item.product.price,
                                                        )
                                                    }}
                                                    c/u
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="mt-3 flex items-center justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <button
                                                    type="button"
                                                    class="h-7 w-7 rounded-full border border-slate-300 text-sm font-semibold text-slate-700"
                                                    @click="
                                                        decrement(item.product)
                                                    "
                                                >
                                                    -
                                                </button>
                                                <span
                                                    class="w-8 text-center text-xs font-semibold text-slate-700"
                                                >
                                                    {{ item.quantity }}
                                                </span>
                                                <button
                                                    type="button"
                                                    class="h-7 w-7 rounded-full border border-slate-300 bg-slate-900 text-sm font-semibold text-white"
                                                    @click="
                                                        increment(item.product)
                                                    "
                                                >
                                                    +
                                                </button>
                                            </div>

                                            <div
                                                class="flex items-center gap-3 text-xs"
                                            >
                                                <span
                                                    class="font-semibold text-slate-900"
                                                >
                                                    {{
                                                        formatMoney(
                                                            item.product.price *
                                                                item.quantity,
                                                        )
                                                    }}
                                                </span>
                                                <button
                                                    type="button"
                                                    class="font-semibold text-rose-600"
                                                    @click="
                                                        removeFromCart(
                                                            item.product,
                                                        )
                                                    "
                                                >
                                                    Quitar
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                </div>

                                <div
                                    class="mt-4 rounded-xl bg-slate-950 px-3 py-3 text-sm text-white"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span>Subtotal</span>
                                        <span>{{
                                            formatMoneyFromCents(subtotalCents)
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-2 flex items-center justify-between text-slate-300"
                                    >
                                        <span>Descuento</span>
                                        <span
                                            >-{{
                                                formatMoneyFromCents(
                                                    discountCents,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="mt-2 flex items-center justify-between border-t border-slate-700 pt-2 text-base font-semibold"
                                    >
                                        <span>Total</span>
                                        <span>{{
                                            formatMoneyFromCents(totalCents)
                                        }}</span>
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <a
                                        :href="route('cart.view')"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="rounded-xl border border-slate-300 px-3 py-2 text-center text-xs font-semibold uppercase tracking-[0.06em] text-slate-700 transition hover:border-slate-900"
                                        @click="isCartDropdownOpen = false"
                                    >
                                        Ver carrito
                                    </a>
                                    <a
                                        :href="route('cart.view')"
                                        class="rounded-xl bg-orange-500 px-3 py-2 text-center text-xs font-semibold uppercase tracking-[0.06em] text-white transition hover:bg-orange-600"
                                        @click="isCartDropdownOpen = false"
                                    >
                                        Finalizar compra
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:hidden">
                        <a
                            :href="route('cart.view')"
                            class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-3 py-2 text-xs font-semibold text-white"
                        >
                            <span>Carrito</span>
                            <span
                                class="rounded-full bg-white/15 px-2 py-0.5 text-[11px]"
                            >
                                {{ totalItems }}
                            </span>
                        </a>

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
                                $page.props.auth.user &&
                                $page.props.auth.user.is_admin
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
                            v-for="group in nav_groups"
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
                            v-for="group in nav_groups"
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
                            <h3
                                class="mt-1 text-2xl font-semibold text-slate-950"
                            >
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

                    <form
                        class="mt-5 space-y-4"
                        @submit.prevent="submitLoginModal"
                    >
                        <div>
                            <label
                                for="login-email"
                                class="mb-1 block text-sm font-semibold text-slate-700"
                            >
                                Correo
                            </label>
                            <input
                                id="login-email"
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
                                for="login-password"
                                class="mb-1 block text-sm font-semibold text-slate-700"
                            >
                                Contrasena
                            </label>
                            <input
                                id="login-password"
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
                                {{
                                    loginLoading ? "Ingresando..." : "Ingresar"
                                }}
                            </button>
                        </div>
                    </form>
                </article>
            </div>

            <div
                v-if="flash.success"
                class="mt-6 rounded-3xl border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-800 shadow-sm"
            >
                <p class="font-semibold">{{ flash.success }}</p>
                <p v-if="flash.cart" class="mt-1">
                    Total final: {{ formatMoney(Number(flash.cart.total)) }}
                    <span v-if="flash.cart.coupon_code">
                        con cupon {{ flash.cart.coupon_code }}
                    </span>
                </p>
                <a
                    v-if="flash.cart"
                    :href="flash.cart.pdf_url"
                    target="_blank"
                    rel="noreferrer"
                    class="mt-3 inline-flex rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white"
                >
                    Ver PDF del pedido
                </a>
            </div>

            <section
                class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_360px]"
            >
                <article
                    class="animate-rise relative overflow-hidden rounded-3xl border border-cyan-200 bg-gradient-to-br from-cyan-200 via-sky-100 to-blue-100 p-7 shadow-[0_25px_60px_rgba(2,132,199,0.22)]"
                >
                    <img
                        v-if="
                            currentHeroBanner?.image_url ||
                            appearance.hero_banner_image_url
                        "
                        :src="
                            currentHeroBanner?.image_url ||
                            appearance.hero_banner_image_url ||
                            undefined
                        "
                        :alt="
                            currentHeroBanner?.title ||
                            appearance.hero_banner_title ||
                            siteName
                        "
                        class="absolute inset-0 h-full w-full object-cover object-center opacity-35"
                    />
                    <div class="absolute inset-0 bg-white/55"></div>
                    <div class="relative z-10">
                        <p
                            class="inline-flex rounded-full bg-orange-500 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white"
                        >
                            Temporada 2026
                        </p>
                        <h2
                            class="mt-5 max-w-xl text-4xl font-semibold leading-tight text-slate-950 sm:text-5xl"
                        >
                            {{
                                currentHeroBanner?.title ||
                                appearance.hero_banner_title ||
                                "Vuelta al Cole"
                            }}
                        </h2>
                        <p
                            class="mt-4 max-w-xl text-base leading-7 text-slate-700"
                        >
                            {{
                                currentHeroBanner?.subtitle ||
                                appearance.hero_banner_subtitle ||
                                "Todo lo que necesitas para abastecer tu negocio: tecnologia, oficina, hogar y libreria con precios mayoristas."
                            }}
                        </p>
                        <a
                            :href="
                                currentHeroBanner?.link_url ||
                                appearance.hero_banner_link_url ||
                                '#productos-destacados'
                            "
                            class="mt-7 inline-flex rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-orange-600"
                        >
                            {{ promotions[0]?.cta || "Ver ofertas" }}
                        </a>
                        <div
                            v-if="hero_banners.length > 1"
                            class="mt-6 flex items-center gap-2"
                        >
                            <button
                                v-for="(banner, index) in hero_banners"
                                :key="banner.id"
                                type="button"
                                class="h-2.5 w-8 rounded-full transition"
                                :class="
                                    index === heroBannerIndex
                                        ? 'bg-slate-950'
                                        : 'bg-slate-400/50'
                                "
                                @click="heroBannerIndex = index"
                            ></button>
                        </div>
                    </div>
                </article>

                <div class="space-y-4">
                    <article
                        v-for="(banner, index) in resolvedSideBanners"
                        :key="banner.id"
                        class="animate-rise relative overflow-hidden rounded-3xl border p-5 text-white shadow-lg"
                        :class="
                            banner.image_url
                                ? 'border-slate-200 bg-slate-950'
                                : index === 0
                                  ? 'border-slate-200 bg-slate-950'
                                  : 'border-orange-300 bg-orange-500'
                        "
                    >
                        <img
                            v-if="banner.image_url"
                            :src="banner.image_url"
                            :alt="banner.title"
                            class="absolute inset-0 h-full w-full object-cover object-center opacity-45"
                        />
                        <div
                            class="absolute inset-0"
                            :class="
                                index === 0
                                    ? 'bg-slate-950/60'
                                    : 'bg-orange-900/45'
                            "
                        ></div>
                        <div class="relative z-10">
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.2em]"
                                :class="
                                    index === 0
                                        ? 'text-orange-300'
                                        : 'text-orange-50'
                                "
                            >
                                {{
                                    index === 0
                                        ? "Banner lateral"
                                        : "Promocion destacada"
                                }}
                            </p>
                            <h3
                                class="mt-2 text-2xl font-semibold leading-tight"
                            >
                                {{ banner.title }}
                            </h3>
                            <p
                                class="mt-3 text-sm leading-6"
                                :class="
                                    index === 0
                                        ? 'text-slate-200'
                                        : 'text-orange-50/90'
                                "
                            >
                                {{
                                    banner.subtitle ||
                                    "Configurable desde el panel de apariencia."
                                }}
                            </p>
                            <a
                                v-if="banner.link_url"
                                :href="banner.link_url"
                                class="mt-4 inline-flex text-sm font-semibold underline underline-offset-4"
                            >
                                Explorar ahora
                            </a>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-10">
                <div>
                    <div
                        id="productos-destacados"
                        class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500"
                            >
                                Productos destacados
                            </p>
                            <h2
                                class="mt-2 text-3xl font-semibold text-slate-950"
                            >
                                Top para tu stock
                            </h2>
                        </div>

                        <div
                            class="flex flex-col items-start gap-3 lg:items-end"
                        >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] transition"
                                    :class="
                                        activeProductFilter === 'new'
                                            ? 'border-slate-950 bg-slate-950 text-white'
                                            : 'border-slate-300 bg-white text-slate-700 hover:border-slate-900'
                                    "
                                    @click="setProductFilter('new')"
                                >
                                    Nuevos
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] transition"
                                    :class="
                                        activeProductFilter === 'featured'
                                            ? 'border-slate-950 bg-slate-950 text-white'
                                            : 'border-slate-300 bg-white text-slate-700 hover:border-slate-900'
                                    "
                                    @click="setProductFilter('featured')"
                                >
                                    Destacados
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] transition"
                                    :class="
                                        activeProductFilter === 'all'
                                            ? 'border-slate-950 bg-slate-950 text-white'
                                            : 'border-slate-300 bg-white text-slate-700 hover:border-slate-900'
                                    "
                                    @click="setProductFilter('all')"
                                >
                                    Mostrar todos
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <label
                                    for="product-sort"
                                    class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"
                                >
                                    Ordenar
                                </label>
                                <select
                                    id="product-sort"
                                    v-model="activeProductSort"
                                    class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700"
                                >
                                    <option value="newest">Mas nuevos</option>
                                    <option value="name_asc">
                                        Alfabetico (A-Z)
                                    </option>
                                    <option value="price_desc">
                                        Precio: mayor a menor
                                    </option>
                                    <option value="price_asc">
                                        Precio: menor a mayor
                                    </option>
                                </select>
                            </div>

                            <p class="text-sm text-slate-500">
                                {{ sortedProducts.length }} productos activos
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <article
                            v-if="!paginatedProducts.length"
                            class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500 md:col-span-2 lg:col-span-3 xl:col-span-4"
                        >
                            No hay productos para este filtro.
                        </article>

                        <article
                            v-for="product in paginatedProducts"
                            :key="product.id"
                            class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                        >
                            <button
                                type="button"
                                class="h-44 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
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

                            <div class="mt-4">
                                <div>
                                    <div
                                        class="flex min-h-[22px] flex-wrap items-center gap-1"
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
                                            v-if="
                                                !product.category_links.length
                                            "
                                            class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500"
                                        >
                                            Catalogo
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-1 block h-12 text-left text-base font-semibold leading-tight text-slate-950 transition hover:text-orange-600"
                                        @click="openProductModal(product)"
                                    >
                                        {{ product.name }}
                                    </button>
                                </div>
                            </div>

                            <p
                                class="mt-3 h-12 overflow-hidden text-sm leading-6 text-slate-600"
                            >
                                {{
                                    product.description ||
                                    product.hero_tag ||
                                    "Producto para venta mayorista."
                                }}
                            </p>

                            <div
                                class="mt-3 flex items-center justify-between gap-3"
                            >
                                <p
                                    class="text-3xl font-semibold text-orange-500"
                                >
                                    {{ formatMoney(product.price) }}
                                </p>
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
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="sortedProducts.length"
                        class="mt-6 flex flex-wrap items-center justify-between gap-3"
                    >
                        <p class="text-sm text-slate-500">
                            Mostrando {{ paginatedProducts.length }} de
                            {{ sortedProducts.length }} productos · Pagina
                            {{ currentProductsPage }} de {{ totalProductPages }}
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 transition hover:border-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="currentProductsPage <= 1"
                                @click="goToPreviousProductsPage"
                            >
                                Anterior
                            </button>
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 transition hover:border-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="
                                    currentProductsPage >= totalProductPages
                                "
                                @click="goToNextProductsPage"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
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
                                    selectedProduct.category_name || "Catalogo"
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

                            <div class="mt-6 border-t border-slate-200 pt-5">
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

                            <div class="mt-7 flex flex-wrap items-center gap-2">
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
                                            ($event.target as HTMLInputElement)
                                                .value,
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

            <section class="mt-10 grid gap-4 lg:grid-cols-3">
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500"
                    >
                        Contacto
                    </p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">
                        {{
                            appearance.store_address ||
                            "Direccion comercial pendiente"
                        }}
                    </p>
                </article>

                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500"
                    >
                        Horario
                    </p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">
                        {{ appearance.business_hours || "Horario a confirmar" }}
                    </p>
                </article>

                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500"
                    >
                        WhatsApp ventas
                    </p>
                    <a
                        v-if="salesWhatsappUrl"
                        :href="salesWhatsappUrl"
                        target="_blank"
                        rel="noreferrer"
                        class="mt-3 inline-flex rounded-full border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                    >
                        Hablar con ventas
                    </a>
                    <p v-else class="mt-3 text-lg font-semibold text-slate-950">
                        No configurado
                    </p>
                </article>
            </section>

            <footer
                class="mt-10 rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-4">
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
                            <p class="text-sm font-semibold text-slate-950">
                                {{ siteName }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Banners, branding y promociones administrables.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-3 text-sm text-slate-600"
                    >
                        <span v-if="appearance.store_name">
                            <strong>{{ appearance.store_name }}</strong>
                        </span>
                        <span v-if="appearance.store_address">
                            {{ appearance.store_address }}
                        </span>
                        <span v-if="appearance.business_hours">
                            {{ appearance.business_hours }}
                        </span>
                        <span v-if="appearance.store_phone">
                            <a
                                :href="`tel:${appearance.store_phone}`"
                                class="underline hover:text-slate-900"
                                >Tel: {{ appearance.store_phone }}</a
                            >
                        </span>
                        <span v-if="appearance.store_email">
                            <a
                                :href="`mailto:${appearance.store_email}`"
                                class="underline hover:text-slate-900"
                                >{{ appearance.store_email }}</a
                            >
                        </span>
                        <span v-if="appearance.store_whatsapp">
                            <a
                                :href="`https://wa.me/${appearance.store_whatsapp.replace(/\D+/g, '')}`"
                                target="_blank"
                                rel="noreferrer"
                                class="rounded-full border border-emerald-300 px-4 py-2 font-semibold text-emerald-700 transition hover:bg-emerald-50"
                            >
                                WhatsApp
                            </a>
                        </span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.animate-rise {
    animation: rise 0.5s ease both;
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(16px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
