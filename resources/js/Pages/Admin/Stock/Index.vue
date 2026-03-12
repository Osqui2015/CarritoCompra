<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { reactive } from "vue";

interface ProductRow {
    id: number;
    name: string;
    stock: number;
    stock_reference: number;
    price?: number;
    image_url?: string | null;
}

interface MovementRow {
    id: number;
    product_name: string | null;
    user_name: string | null;
    type: string;
    quantity: number;
    previous_stock: number;
    new_stock: number;
    reference: string | null;
    note: string | null;
    created_at: string | null;
}

const props = defineProps<{
    filters: {
        search: string;
    };
    threshold: number;
    critical_products: ProductRow[];
    all_products: ProductRow[];
    movements: MovementRow[];
}>();

const filterState = reactive({
    search: props.filters.search ?? "",
});

const thresholdForm = useForm({
    low_stock_threshold: props.threshold,
});

const adjustForm = useForm({
    product_id: "",
    new_stock: 0,
    note: "",
});

function applyFilter(): void {
    router.get(
        route("admin.stock.index"),
        {
            search: filterState.search || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function updateThreshold(): void {
    thresholdForm.post(route("admin.stock.threshold"), {
        preserveScroll: true,
    });
}

function submitAdjustment(): void {
    adjustForm.post(route("admin.stock.adjust"), {
        preserveScroll: true,
        onSuccess: () => {
            adjustForm.reset("new_stock", "note");
        },
    });
}

function useSuggestedStock(product: ProductRow): void {
    adjustForm.product_id = String(product.id);
    adjustForm.new_stock = product.stock;
}

function stockStateLabel(stock: number, stockReference: number): string {
    if (stock <= 0) {
        return "Sin stock";
    }

    const ratio = stock / Math.max(stockReference, 1);

    if (ratio < 0.3) {
        return "Bajo";
    }

    if (ratio < 0.7) {
        return "Medio";
    }

    return "Alto";
}

function formatMoney(value: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)}`;
}
</script>

<template>
    <Head title="Admin · Stock" />

    <AdminLayout>
        <section class="space-y-6">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500"
                    >
                        Inventario
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold text-slate-950">
                        Control de stock
                    </h2>
                </div>

                <form
                    class="flex items-center gap-2"
                    @submit.prevent="applyFilter"
                >
                    <input
                        v-model="filterState.search"
                        type="text"
                        placeholder="Buscar producto"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    />
                    <button
                        type="submit"
                        class="rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white"
                    >
                        Buscar
                    </button>
                </form>
            </div>

            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]"
            >
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        Productos criticos
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Productos con stock menor o igual a
                        {{ threshold }} unidades.
                    </p>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="product in critical_products"
                            :key="product.id"
                            class="flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-12 w-12 overflow-hidden rounded-lg border border-rose-200 bg-white"
                                >
                                    <img
                                        v-if="product.image_url"
                                        :src="product.image_url"
                                        :alt="product.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-rose-900"
                                    >
                                        {{ product.name }}
                                    </p>
                                    <p class="text-xs text-rose-700">
                                        Stock: {{ product.stock }} ·
                                        {{
                                            stockStateLabel(
                                                product.stock,
                                                product.stock_reference,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="rounded-full border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-700"
                                @click="useSuggestedStock(product)"
                            >
                                Ajustar
                            </button>
                        </div>
                        <p
                            v-if="!critical_products.length"
                            class="text-sm text-slate-500"
                        >
                            No hay productos criticos.
                        </p>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        Ajuste rapido
                    </h3>
                    <form
                        class="mt-4 space-y-3"
                        @submit.prevent="submitAdjustment"
                    >
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                >Producto</label
                            >
                            <select
                                v-model="adjustForm.product_id"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                required
                            >
                                <option value="">Seleccionar</option>
                                <option
                                    v-for="product in all_products"
                                    :key="product.id"
                                    :value="String(product.id)"
                                >
                                    {{ product.name }} (stock:
                                    {{ product.stock }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                >Nuevo stock</label
                            >
                            <input
                                v-model.number="adjustForm.new_stock"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                required
                            />
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                >Nota</label
                            >
                            <input
                                v-model="adjustForm.note"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="Motivo del ajuste"
                            />
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white"
                            :disabled="adjustForm.processing"
                        >
                            {{
                                adjustForm.processing
                                    ? "Guardando..."
                                    : "Guardar ajuste"
                            }}
                        </button>
                    </form>

                    <form
                        class="mt-6 border-t border-slate-200 pt-4"
                        @submit.prevent="updateThreshold"
                    >
                        <h4 class="text-sm font-semibold text-slate-900">
                            Umbral critico global
                        </h4>
                        <div class="mt-3 flex items-center gap-2">
                            <input
                                v-model.number="
                                    thresholdForm.low_stock_threshold
                                "
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                            <button
                                type="submit"
                                class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700"
                                :disabled="thresholdForm.processing"
                            >
                                Actualizar
                            </button>
                        </div>
                    </form>
                </article>
            </div>

            <article
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-lg font-semibold text-slate-950">
                    Historial de movimientos
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    Auditoria de entradas y salidas de inventario.
                </p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead
                            class="bg-slate-100 text-xs uppercase tracking-[0.08em] text-slate-600"
                        >
                            <tr>
                                <th class="px-3 py-2">Fecha</th>
                                <th class="px-3 py-2">Producto</th>
                                <th class="px-3 py-2">Tipo</th>
                                <th class="px-3 py-2">Cantidad</th>
                                <th class="px-3 py-2">Antes</th>
                                <th class="px-3 py-2">Despues</th>
                                <th class="px-3 py-2">Usuario</th>
                                <th class="px-3 py-2">Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="movement in movements"
                                :key="movement.id"
                                class="border-t border-slate-200"
                            >
                                <td class="px-3 py-2 text-slate-600">
                                    {{ movement.created_at }}
                                </td>
                                <td
                                    class="px-3 py-2 font-medium text-slate-900"
                                >
                                    {{ movement.product_name }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ movement.type }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ movement.quantity }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ movement.previous_stock }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ movement.new_stock }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ movement.user_name || "Sistema" }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ movement.reference || "-" }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-if="!movements.length"
                        class="mt-4 text-sm text-slate-500"
                    >
                        No hay movimientos registrados.
                    </p>
                </div>
            </article>
        </section>
    </AdminLayout>
</template>
