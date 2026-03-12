<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { computed } from "vue";

interface OrderItem {
    id: number;
    product_name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    image_url: string | null;
}

interface OrderRow {
    id: number;
    code: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string | null;
    shipping_address: string;
    notes: string | null;
    status: string;
    subtotal: number;
    discount_amount: number;
    total: number;
    coupon_code: string | null;
    confirmed_at: string | null;
    created_at: string | null;
    items: OrderItem[];
}

const props = defineProps<{
    orders: OrderRow[];
}>();

const totalRevenue = computed(() =>
    props.orders.reduce((sum, order) => sum + order.total, 0),
);

const totalDiscount = computed(() =>
    props.orders.reduce((sum, order) => sum + order.discount_amount, 0),
);
</script>

<template>
    <Head title="Admin · Pedidos" />

    <AdminLayout>
        <section class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                    >
                        Pedidos confirmados
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">
                        {{ orders.length }}
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                    >
                        Facturacion total
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">
                        ${{ totalRevenue.toFixed(2) }}
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                    >
                        Descuento otorgado
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">
                        ${{ totalDiscount.toFixed(2) }}
                    </p>
                </article>
            </div>

            <article
                v-for="order in orders"
                :key="order.id"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div
                    class="flex flex-col gap-5 border-b border-slate-200 pb-5 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500"
                        >
                            Pedido
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                            {{ order.code }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ order.created_at }}
                        </p>
                    </div>

                    <div
                        class="grid gap-3 text-sm text-slate-700 sm:grid-cols-2"
                    >
                        <div>
                            <p class="font-semibold text-slate-900">
                                {{ order.customer_name }}
                            </p>
                            <p>{{ order.customer_email }}</p>
                            <p>{{ order.customer_phone || "Sin telefono" }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Entrega</p>
                            <p>{{ order.shipping_address }}</p>
                            <p v-if="order.notes">Nota: {{ order.notes }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div class="space-y-3">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-white"
                                >
                                    <img
                                        v-if="item.image_url"
                                        :src="item.image_url"
                                        alt="Producto"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-lg font-semibold text-slate-400"
                                    >
                                        {{ item.product_name.slice(0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ item.product_name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ item.quantity }} x ${{
                                            item.unit_price.toFixed(2)
                                        }}
                                    </p>
                                </div>
                            </div>
                            <p class="font-semibold text-slate-900">
                                ${{ item.line_total.toFixed(2) }}
                            </p>
                        </div>
                    </div>

                    <aside
                        class="rounded-2xl border border-slate-200 bg-slate-950 p-4 text-white"
                    >
                        <p
                            class="text-xs uppercase tracking-[0.2em] text-slate-300"
                        >
                            Resumen
                        </p>
                        <div class="mt-4 space-y-2 text-sm">
                            <div
                                class="flex items-center justify-between text-slate-300"
                            >
                                <span>Subtotal</span>
                                <span>${{ order.subtotal.toFixed(2) }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between text-slate-300"
                            >
                                <span>Descuento</span>
                                <span
                                    >- ${{
                                        order.discount_amount.toFixed(2)
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between text-slate-300"
                                v-if="order.coupon_code"
                            >
                                <span>Cupon</span>
                                <span>{{ order.coupon_code }}</span>
                            </div>
                        </div>
                        <div
                            class="mt-4 flex items-center justify-between border-t border-slate-700 pt-3 text-lg font-semibold"
                        >
                            <span>Total</span>
                            <span>${{ order.total.toFixed(2) }}</span>
                        </div>
                    </aside>
                </div>
            </article>

            <article
                v-if="!orders.length"
                class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500"
            >
                No hay pedidos confirmados aun.
            </article>
        </section>
    </AdminLayout>
</template>
