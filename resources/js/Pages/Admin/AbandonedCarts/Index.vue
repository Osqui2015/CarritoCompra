<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";

interface AbandonedItem {
    id: number;
    status: "open" | "reminded" | "recovered" | "cleared";
    item_count: number;
    subtotal: number;
    last_activity_at: string | null;
    reminder_sent_at: string | null;
    recovered_at: string | null;
    coupon_code: string | null;
    coupon_expires_at: string | null;
    whatsapp_url: string | null;
    user: {
        id: number | null;
        name: string | null;
        email: string | null;
        phone: string | null;
    };
    items_snapshot: Array<{
        product_id: number;
        name: string;
        quantity: number;
        price: number;
        line_total: number;
        image_url: string | null;
    }>;
}

const props = defineProps<{
    filters: {
        status: string;
    };
    settings: {
        sales_whatsapp: string | null;
    };
    items: AbandonedItem[];
}>();

const filterState = reactive({
    status: props.filters.status || "open",
});

const remindForm = useForm({
    discount_percent: 10,
    expires_in_days: 5,
});

const activeReminderCartId = ref<number | null>(null);

const statusStats = computed(() => ({
    open: props.items.filter((item) => item.status === "open").length,
    reminded: props.items.filter((item) => item.status === "reminded").length,
    recovered: props.items.filter((item) => item.status === "recovered").length,
}));

function formatMoney(value: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)}`;
}

function applyFilters(): void {
    router.get(
        route("admin.abandoned-carts.index"),
        {
            status: filterState.status,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function remind(item: AbandonedItem): void {
    activeReminderCartId.value = item.id;
    remindForm.post(route("admin.abandoned-carts.remind", item.id), {
        preserveScroll: true,
        onFinish: () => {
            activeReminderCartId.value = null;
        },
    });
}

function markRecovered(item: AbandonedItem): void {
    router.patch(
        route("admin.abandoned-carts.recovered", item.id),
        {},
        { preserveScroll: true },
    );
}

function markCleared(item: AbandonedItem): void {
    if (!window.confirm("Marcar este carrito como limpiado?")) {
        return;
    }

    router.patch(
        route("admin.abandoned-carts.cleared", item.id),
        {},
        { preserveScroll: true },
    );
}

function statusClass(status: AbandonedItem["status"]): string {
    if (status === "open") {
        return "bg-amber-100 text-amber-800";
    }

    if (status === "reminded") {
        return "bg-sky-100 text-sky-800";
    }

    if (status === "recovered") {
        return "bg-emerald-100 text-emerald-800";
    }

    return "bg-slate-100 text-slate-700";
}
</script>

<template>
    <Head title="Admin · Carritos abandonados" />

    <AdminLayout>
        <section class="space-y-6">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500"
                    >
                        Recuperacion
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold text-slate-950">
                        Carritos abandonados
                    </h2>
                </div>

                <form
                    class="flex items-center gap-2"
                    @submit.prevent="applyFilters"
                >
                    <select
                        v-model="filterState.status"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="open">Abiertos</option>
                        <option value="reminded">Recordados</option>
                        <option value="recovered">Recuperados</option>
                        <option value="all">Todos</option>
                    </select>
                    <button
                        type="submit"
                        class="rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white"
                    >
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-amber-700"
                    >
                        Abiertos
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-amber-900">
                        {{ statusStats.open }}
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-sky-200 bg-sky-50 p-5"
                >
                    <p class="text-xs uppercase tracking-[0.2em] text-sky-700">
                        Con recordatorio
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-sky-900">
                        {{ statusStats.reminded }}
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-emerald-700"
                    >
                        Recuperados
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-900">
                        {{ statusStats.recovered }}
                    </p>
                </article>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-slate-900">
                    Configurar recordatorio
                </h3>
                <p class="mt-1 text-xs text-slate-500">
                    Cupon unico de recuperacion por carrito. WhatsApp ventas:
                    {{ settings.sales_whatsapp || "No configurado" }}.
                </p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:max-w-xl">
                    <label class="text-xs text-slate-600">
                        Descuento (%)
                        <input
                            v-model.number="remindForm.discount_percent"
                            type="number"
                            min="1"
                            max="90"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        />
                    </label>
                    <label class="text-xs text-slate-600">
                        Vigencia (dias)
                        <input
                            v-model.number="remindForm.expires_in_days"
                            type="number"
                            min="1"
                            max="30"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        />
                    </label>
                </div>
            </div>

            <article
                v-for="item in items"
                :key="item.id"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Carrito #{{ item.id }}
                            </p>
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="statusClass(item.status)"
                            >
                                {{ item.status }}
                            </span>
                        </div>
                        <h3 class="mt-2 text-lg font-semibold text-slate-950">
                            {{ item.user.name || "Usuario" }}
                        </h3>
                        <p class="text-sm text-slate-600">
                            {{ item.user.email }}
                        </p>
                        <p class="text-sm text-slate-600">
                            {{ item.user.phone || "Sin telefono" }}
                        </p>
                    </div>

                    <div class="text-sm text-slate-600">
                        <p>
                            Items:
                            <span class="font-semibold text-slate-900">{{
                                item.item_count
                            }}</span>
                        </p>
                        <p>
                            Subtotal:
                            <span class="font-semibold text-slate-900">{{
                                formatMoney(item.subtotal)
                            }}</span>
                        </p>
                        <p>
                            Ultima actividad: {{ item.last_activity_at || "-" }}
                        </p>
                        <p v-if="item.coupon_code">
                            Cupon:
                            <span class="font-semibold text-emerald-700">{{
                                item.coupon_code
                            }}</span>
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
                >
                    <div class="space-y-2">
                        <div
                            v-for="snapshot in item.items_snapshot"
                            :key="snapshot.product_id"
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 overflow-hidden rounded-lg border border-slate-200 bg-white"
                                >
                                    <img
                                        v-if="snapshot.image_url"
                                        :src="snapshot.image_url"
                                        :alt="snapshot.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ snapshot.name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ snapshot.quantity }} x
                                        {{ formatMoney(snapshot.price) }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ formatMoney(snapshot.line_total) }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <button
                            type="button"
                            class="w-full rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white disabled:opacity-50"
                            :disabled="activeReminderCartId === item.id"
                            @click="remind(item)"
                        >
                            {{
                                activeReminderCartId === item.id
                                    ? "Preparando..."
                                    : "Generar recordatorio"
                            }}
                        </button>

                        <a
                            v-if="item.whatsapp_url"
                            :href="item.whatsapp_url"
                            target="_blank"
                            rel="noreferrer"
                            class="block w-full rounded-full border border-emerald-300 px-4 py-2 text-center text-xs font-semibold uppercase tracking-[0.08em] text-emerald-700"
                        >
                            Abrir WhatsApp
                        </a>

                        <button
                            type="button"
                            class="w-full rounded-full border border-sky-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-sky-700"
                            @click="markRecovered(item)"
                        >
                            Marcar recuperado
                        </button>

                        <button
                            type="button"
                            class="w-full rounded-full border border-rose-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-rose-700"
                            @click="markCleared(item)"
                        >
                            Marcar limpiado
                        </button>
                    </div>
                </div>
            </article>

            <article
                v-if="!items.length"
                class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500"
            >
                No hay carritos en este estado.
            </article>
        </section>
    </AdminLayout>
</template>
