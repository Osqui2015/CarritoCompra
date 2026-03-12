<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Title,
    Tooltip,
} from "chart.js";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

Chart.register(
    CategoryScale,
    LinearScale,
    LineController,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

interface Summary {
    month_sales: number;
    previous_month_sales: number;
    sales_growth_pct: number;
    avg_ticket: number;
    month_orders_count: number;
    coupon_conversion_rate: number;
    top_coupon: {
        code: string;
        uses: number;
    } | null;
    abandoned_open_count: number;
}

interface ChartPayload {
    labels: string[];
    totals: number[];
}

interface ProductRow {
    id: number;
    name: string;
    sold_qty?: number;
    stock?: number;
    stock_reference?: number;
    price?: number;
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
    range: number;
    summary: Summary;
    chart: ChartPayload;
    top_products: ProductRow[];
    stale_products: ProductRow[];
    critical_products: ProductRow[];
    recent_movements: MovementRow[];
    low_stock_threshold: number;
}>();

const selectedRange = ref(String(props.range));
const chartCanvas = ref<HTMLCanvasElement | null>(null);
const chartInstance = ref<Chart<"line"> | null>(null);

const growthLabel = computed(() => {
    const value = props.summary.sales_growth_pct;

    if (value > 0) {
        return `+${value.toFixed(2)}% vs mes anterior`;
    }

    if (value < 0) {
        return `${value.toFixed(2)}% vs mes anterior`;
    }

    return "Sin cambios vs mes anterior";
});

const trendClass = computed(() => {
    if (props.summary.sales_growth_pct > 0) {
        return "text-emerald-700";
    }

    if (props.summary.sales_growth_pct < 0) {
        return "text-rose-700";
    }

    return "text-slate-600";
});

function formatMoney(value: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)}`;
}

function stockLevelLabel(stock: number, stockReference: number): string {
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

function updateRange(): void {
    router.get(
        route("admin.dashboard"),
        { range: selectedRange.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function createChart(): void {
    const canvas = chartCanvas.value;
    if (!canvas) {
        return;
    }

    chartInstance.value?.destroy();
    chartInstance.value = new Chart(canvas, {
        type: "line",
        data: {
            labels: props.chart.labels,
            datasets: [
                {
                    label: "Ventas",
                    data: props.chart.totals,
                    borderColor: "#f97316",
                    borderWidth: 3,
                    backgroundColor: "rgba(249, 115, 22, 0.18)",
                    fill: true,
                    tension: 0.28,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    ticks: {
                        callback(value) {
                            return `$${value}`;
                        },
                    },
                },
            },
        },
    });
}

watch(
    () => props.chart,
    () => {
        createChart();
    },
    { deep: true },
);

onMounted(() => {
    createChart();
});

onBeforeUnmount(() => {
    chartInstance.value?.destroy();
});
</script>

<template>
    <Head title="Admin · Analitica" />

    <AdminLayout>
        <section class="space-y-6">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500"
                    >
                        Inteligencia comercial
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold text-slate-950">
                        Dashboard de rendimiento
                    </h2>
                </div>

                <div class="flex items-center gap-2">
                    <label
                        class="text-sm font-semibold text-slate-700"
                        for="range"
                        >Rango</label
                    >
                    <select
                        id="range"
                        v-model="selectedRange"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        @change="updateRange"
                    >
                        <option value="7">Ultimos 7 dias</option>
                        <option value="30">Ultimos 30 dias</option>
                    </select>
                    <Link
                        :href="
                            route('admin.dashboard.export-sales', {
                                days: selectedRange,
                            })
                        "
                        class="rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white"
                    >
                        Exportar Excel
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                    >
                        Ventas del mes
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">
                        {{ formatMoney(summary.month_sales) }}
                    </p>
                    <p class="mt-2 text-xs font-semibold" :class="trendClass">
                        {{ growthLabel }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                    >
                        Ticket promedio
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">
                        {{ formatMoney(summary.avg_ticket) }}
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ summary.month_orders_count }} pedidos confirmados
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                    >
                        Conversion con cupon
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">
                        {{ summary.coupon_conversion_rate.toFixed(2) }}%
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        <span v-if="summary.top_coupon"
                            >Top: {{ summary.top_coupon.code }} ({{
                                summary.top_coupon.uses
                            }}
                            usos)</span
                        >
                        <span v-else>Sin cupones usados este mes</span>
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-amber-700"
                    >
                        Carritos abandonados
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-amber-900">
                        {{ summary.abandoned_open_count }}
                    </p>
                    <Link
                        :href="route('admin.abandoned-carts.index')"
                        class="mt-3 inline-flex text-xs font-semibold uppercase tracking-[0.08em] text-amber-800 underline"
                    >
                        Ver recuperacion
                    </Link>
                </article>
            </div>

            <div
                class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
            >
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        Tendencia de ventas
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Serie diaria del rango seleccionado.
                    </p>
                    <div class="mt-4 h-72">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        Productos estrella
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Top 5 por unidades vendidas.
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(row, index) in top_products"
                            :key="row.id"
                            class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                        >
                            <div>
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500"
                                >
                                    #{{ index + 1 }}
                                </p>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ row.name }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold text-emerald-700"
                                >{{ row.sold_qty }} uds</span
                            >
                        </div>
                        <p
                            v-if="!top_products.length"
                            class="text-sm text-slate-500"
                        >
                            No hay ventas registradas en este rango.
                        </p>
                    </div>
                </article>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        Productos hueso
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Activos sin ventas en 60 dias.
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="row in stale_products"
                            :key="row.id"
                            class="rounded-2xl border border-slate-200 px-4 py-3"
                        >
                            <p class="text-sm font-semibold text-slate-900">
                                {{ row.name }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Stock: {{ row.stock }} ({{
                                    stockLevelLabel(
                                        row.stock || 0,
                                        row.stock_reference || 1,
                                    )
                                }}) · Precio: {{ formatMoney(row.price || 0) }}
                            </p>
                        </div>
                        <p
                            v-if="!stale_products.length"
                            class="text-sm text-slate-500"
                        >
                            Sin productos hueso para mostrar.
                        </p>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        Stock critico
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Umbral actual: {{ low_stock_threshold }} unidades.
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="row in critical_products"
                            :key="row.id"
                            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3"
                        >
                            <p class="text-sm font-semibold text-rose-900">
                                {{ row.name }}
                            </p>
                            <p class="mt-1 text-xs text-rose-700">
                                Stock actual: {{ row.stock }}
                            </p>
                        </div>
                        <p
                            v-if="!critical_products.length"
                            class="text-sm text-slate-500"
                        >
                            No hay productos por debajo del umbral.
                        </p>
                    </div>
                </article>
            </div>

            <article
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-lg font-semibold text-slate-950">
                    Historial reciente de movimientos
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ultimos cambios de inventario.
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
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="movement in recent_movements"
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
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-if="!recent_movements.length"
                        class="mt-4 text-sm text-slate-500"
                    >
                        No hay movimientos registrados aun.
                    </p>
                </div>
            </article>
        </section>
    </AdminLayout>
</template>
