<script setup lang="ts">
import axios from "axios";
import StoreTopBar from "@/Components/StoreTopBar.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { PageProps } from "@/types";

interface StoredCartItem {
    product_id: number;
    quantity: number;
    name: string;
    price: number;
    image_url: string | null;
    category_name: string | null;
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

const CART_STORAGE_KEY = "carrito:items:v1";

const page = usePage<PageProps>();
const items = ref<StoredCartItem[]>([]);
const appliedCoupon = ref<AppliedCoupon | null>(null);
const couponCodeInput = ref("");
const couponError = ref<string | null>(null);
const couponLoading = ref(false);
const clientErrors = ref<Record<string, string>>({});

const siteName = computed(
    () => page.props.branding?.site_name || "TUS TECNOLOGIAS",
);

const form = useForm<CheckoutForm>({
    customer_name: page.props.auth.user?.name ?? "",
    customer_email: page.props.auth.user?.email ?? "",
    customer_phone: page.props.auth.user?.phone ?? "",
    shipping_address: page.props.auth.user?.shipping_address ?? "",
    notes: "",
    coupon_code: "",
    items: [],
});

const subtotalCents = computed(() =>
    items.value.reduce(
        (sum, item) => sum + Math.round(item.price * 100) * item.quantity,
        0,
    ),
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

const totalItems = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0),
);

function formatMoney(value: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)}`;
}

function formatMoneyFromCents(value: number): string {
    return formatMoney(value / 100);
}

function loadCart(): void {
    if (typeof window === "undefined") {
        return;
    }

    try {
        const raw = window.localStorage.getItem(CART_STORAGE_KEY);
        if (!raw) {
            items.value = [];
            return;
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            items.value = [];
            return;
        }

        items.value = parsed
            .map((item: any) => ({
                product_id: Number(item.product_id),
                quantity: Math.max(0, Number(item.quantity) || 0),
                name: String(item.name || "Producto"),
                price: Number(item.price) || 0,
                image_url:
                    typeof item.image_url === "string" ? item.image_url : null,
                category_name:
                    typeof item.category_name === "string"
                        ? item.category_name
                        : null,
            }))
            .filter((item) => item.product_id > 0 && item.quantity > 0);
    } catch {
        items.value = [];
    }
}

function persistCart(): void {
    if (typeof window === "undefined") {
        return;
    }

    window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items.value));
}

function removeItem(productId: number): void {
    items.value = items.value.filter((item) => item.product_id !== productId);
    persistCart();

    if (!items.value.length) {
        clearCoupon();
    }
}

function setQuantity(productId: number, nextQty: number): void {
    const normalized = Math.max(0, Math.floor(nextQty));

    if (normalized <= 0) {
        removeItem(productId);
        return;
    }

    items.value = items.value.map((item) =>
        item.product_id === productId
            ? { ...item, quantity: normalized }
            : item,
    );

    persistCart();
}

function clearCart(): void {
    items.value = [];
    persistCart();
    clearCoupon();
}

function clearCoupon(): void {
    appliedCoupon.value = null;
    couponCodeInput.value = "";
    couponError.value = null;
    form.coupon_code = "";
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

    if (!items.value.length) {
        nextErrors.items = "Debes agregar al menos un producto al carrito.";
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
        items: items.value.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
        })),
    })).post(route("carts.store"), {
        preserveScroll: true,
        onSuccess: () => {
            clearCart();
            form.reset("notes", "coupon_code", "items");
            form.clearErrors();
            clientErrors.value = {};
        },
    });
}

function handleStorage(event: StorageEvent): void {
    if (event.key === CART_STORAGE_KEY) {
        loadCart();
    }
}

onMounted(() => {
    loadCart();

    window.addEventListener("storage", handleStorage);
});

onBeforeUnmount(() => {
    window.removeEventListener("storage", handleStorage);
});
</script>

<template>
    <Head :title="`Carrito · ${siteName}`" />

    <div class="min-h-screen bg-slate-100 pt-6 text-slate-900 sm:pt-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <StoreTopBar />

            <main class="mx-auto max-w-6xl py-8">
                <section
                    class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_360px]"
                >
                    <article
                        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-xl font-semibold text-slate-950">
                                Productos cargados
                            </h2>
                            <button
                                v-if="items.length"
                                type="button"
                                class="rounded-full border border-rose-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-rose-700"
                                @click="clearCart"
                            >
                                Vaciar carrito
                            </button>
                        </div>

                        <div class="mt-5 space-y-3">
                            <article
                                v-for="item in items"
                                :key="item.product_id"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-16 w-16 overflow-hidden rounded-xl border border-slate-200 bg-white"
                                        >
                                            <img
                                                v-if="item.image_url"
                                                :src="item.image_url"
                                                :alt="item.name"
                                                class="h-full w-full object-cover object-center"
                                            />
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-slate-900"
                                            >
                                                {{ item.name }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{
                                                    item.category_name ||
                                                    "Catalogo"
                                                }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{
                                                    formatMoney(item.price)
                                                }}
                                                por unidad
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input
                                            :value="item.quantity"
                                            type="number"
                                            min="1"
                                            class="h-10 w-16 rounded-xl border border-slate-300 text-center text-sm"
                                            @input="
                                                setQuantity(
                                                    item.product_id,
                                                    Number(
                                                        (
                                                            $event.target as HTMLInputElement
                                                        ).value,
                                                    ),
                                                )
                                            "
                                        />
                                        <button
                                            type="button"
                                            class="rounded-full border border-rose-300 px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-rose-700"
                                            @click="removeItem(item.product_id)"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <article
                                v-if="!items.length"
                                class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500"
                            >
                                No hay productos en el carrito.
                            </article>
                        </div>
                    </article>

                    <aside
                        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    >
                        <h2 class="text-xl font-semibold text-slate-950">
                            Checkout
                        </h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Revisa tu pedido y confirma la compra desde aqui.
                        </p>

                        <div
                            class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                for="coupon_code"
                            >
                                Codigo de descuento
                            </label>
                            <div class="flex gap-2">
                                <input
                                    id="coupon_code"
                                    v-model="couponCodeInput"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm uppercase"
                                    placeholder="BIENVENIDA10"
                                />
                                <button
                                    type="button"
                                    class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white disabled:opacity-50"
                                    :disabled="couponLoading"
                                    @click="applyCoupon"
                                >
                                    {{ couponLoading ? "..." : "Aplicar" }}
                                </button>
                            </div>
                            <p
                                v-if="couponError"
                                class="mt-2 text-xs text-rose-600"
                            >
                                {{ couponError }}
                            </p>
                            <p
                                v-if="form.errors.coupon_code"
                                class="mt-2 text-xs text-rose-600"
                            >
                                {{ form.errors.coupon_code }}
                            </p>
                            <div
                                v-if="appliedCoupon"
                                class="mt-3 flex items-center justify-between rounded-xl bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-800"
                            >
                                <span
                                    >Cupon aplicado:
                                    {{ appliedCoupon.code }}</span
                                >
                                <button
                                    type="button"
                                    class="underline"
                                    @click="clearCoupon"
                                >
                                    Quitar
                                </button>
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-2xl bg-slate-950 p-5 text-white"
                        >
                            <div
                                class="flex items-center justify-between text-sm text-slate-300"
                            >
                                <span>Total de items</span>
                                <span>{{ totalItems }}</span>
                            </div>
                            <div
                                class="mt-2 flex items-center justify-between text-sm text-slate-300"
                            >
                                <span>Subtotal</span>
                                <span>{{
                                    formatMoneyFromCents(subtotalCents)
                                }}</span>
                            </div>
                            <div
                                class="mt-2 flex items-center justify-between text-sm text-slate-300"
                            >
                                <span>Descuento</span>
                                <span
                                    >-{{
                                        formatMoneyFromCents(discountCents)
                                    }}</span
                                >
                            </div>
                            <div
                                class="mt-3 flex items-center justify-between border-t border-slate-700 pt-3 text-xl font-semibold"
                            >
                                <span>Total</span>
                                <span>{{
                                    formatMoneyFromCents(totalCents)
                                }}</span>
                            </div>
                        </div>

                        <form
                            class="mt-5 space-y-4"
                            @submit.prevent="submitOrder"
                        >
                            <div>
                                <label
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                    for="customer_name"
                                >
                                    Nombre
                                </label>
                                <input
                                    id="customer_name"
                                    v-model="form.customer_name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                />
                                <p
                                    v-if="
                                        clientErrors.customer_name ||
                                        form.errors.customer_name
                                    "
                                    class="mt-1 text-xs text-rose-600"
                                >
                                    {{
                                        clientErrors.customer_name ||
                                        form.errors.customer_name
                                    }}
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                        for="customer_email"
                                    >
                                        Correo
                                    </label>
                                    <input
                                        id="customer_email"
                                        v-model="form.customer_email"
                                        type="email"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                    />
                                    <p
                                        v-if="
                                            clientErrors.customer_email ||
                                            form.errors.customer_email
                                        "
                                        class="mt-1 text-xs text-rose-600"
                                    >
                                        {{
                                            clientErrors.customer_email ||
                                            form.errors.customer_email
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                        for="customer_phone"
                                    >
                                        Telefono
                                    </label>
                                    <input
                                        id="customer_phone"
                                        v-model="form.customer_phone"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                    for="shipping_address"
                                >
                                    Direccion
                                </label>
                                <input
                                    id="shipping_address"
                                    v-model="form.shipping_address"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                />
                                <p
                                    v-if="
                                        clientErrors.shipping_address ||
                                        form.errors.shipping_address
                                    "
                                    class="mt-1 text-xs text-rose-600"
                                >
                                    {{
                                        clientErrors.shipping_address ||
                                        form.errors.shipping_address
                                    }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                    for="notes"
                                >
                                    Notas
                                </label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                    placeholder="Indicaciones de entrega, horario, referencias..."
                                ></textarea>
                            </div>

                            <p
                                v-if="clientErrors.items || form.errors.items"
                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
                            >
                                {{ clientErrors.items || form.errors.items }}
                            </p>

                            <button
                                type="submit"
                                class="w-full rounded-full bg-orange-500 px-5 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-orange-600 disabled:opacity-60"
                                :disabled="form.processing || !items.length"
                            >
                                {{
                                    form.processing
                                        ? "Confirmando pedido..."
                                        : "Finalizar compra"
                                }}
                            </button>
                        </form>

                        <Link
                            :href="route('storefront')"
                            class="mt-3 block w-full rounded-full border border-slate-300 px-4 py-3 text-center text-sm font-semibold uppercase tracking-[0.08em] text-slate-700"
                        >
                            Seguir comprando
                        </Link>
                    </aside>
                </section>
            </main>
        </div>
    </div>
</template>
