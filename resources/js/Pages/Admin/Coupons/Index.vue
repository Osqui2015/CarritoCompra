<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { reactive, ref } from "vue";

interface CouponItem {
    id: number;
    code: string;
    type: "percentage" | "fixed";
    value: number;
    starts_at: string | null;
    expires_at: string | null;
    is_active: boolean;
    usage_limit: number | null;
    times_used: number;
    is_valid_now: boolean;
    updated_at: string | null;
}

interface Filters {
    search: string;
    status: string;
}

const props = defineProps<{
    coupons: CouponItem[];
    filters: Filters;
}>();

const filterForm = reactive({
    search: props.filters.search ?? "",
    status: props.filters.status ?? "all",
});

const editingId = ref<number | null>(null);

const form = useForm({
    code: "",
    type: "percentage",
    value: "",
    starts_at: "",
    expires_at: "",
    usage_limit: "",
    is_active: true,
});

function applyFilters(): void {
    router.get(
        route("admin.coupons.index"),
        {
            search: filterForm.search || undefined,
            status: filterForm.status !== "all" ? filterForm.status : undefined,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
}

function resetFilters(): void {
    filterForm.search = "";
    filterForm.status = "all";
    applyFilters();
}

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.type = "percentage";
    form.is_active = true;
}

function editCoupon(coupon: CouponItem): void {
    editingId.value = coupon.id;
    form.code = coupon.code;
    form.type = coupon.type;
    form.value = coupon.value.toFixed(2);
    form.starts_at = coupon.starts_at ?? "";
    form.expires_at = coupon.expires_at ?? "";
    form.usage_limit = coupon.usage_limit ? String(coupon.usage_limit) : "";
    form.is_active = coupon.is_active;
}

function submit(): void {
    const payload = {
        code: form.code,
        type: form.type,
        value: form.value,
        starts_at: form.starts_at || null,
        expires_at: form.expires_at || null,
        usage_limit: form.usage_limit !== "" ? Number(form.usage_limit) : null,
        is_active: form.is_active,
        _method: editingId.value ? "put" : "post",
    };

    const url = editingId.value
        ? route("admin.coupons.update", editingId.value)
        : route("admin.coupons.store");

    form.transform(() => payload).post(url, {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
}

function destroyCoupon(couponId: number): void {
    if (!window.confirm("Eliminar este cupon?")) {
        return;
    }

    router.delete(route("admin.coupons.destroy", couponId), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Admin · Cupones" />

    <AdminLayout>
        <section class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
            <article
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2 class="text-xl font-semibold text-slate-950">
                    {{ editingId ? "Editar cupon" : "Nuevo cupon" }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Gestiona descuentos por porcentaje o monto fijo.
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="code"
                            >Codigo</label
                        >
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase"
                            placeholder="BIENVENIDA10"
                            required
                        />
                        <p
                            v-if="form.errors.code"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                for="type"
                                >Tipo</label
                            >
                            <select
                                id="type"
                                v-model="form.type"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            >
                                <option value="percentage">Porcentaje</option>
                                <option value="fixed">Monto fijo</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                for="value"
                                >Valor</label
                            >
                            <input
                                id="value"
                                v-model="form.value"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                required
                            />
                            <p
                                v-if="form.errors.value"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ form.errors.value }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                for="starts_at"
                                >Inicia</label
                            >
                            <input
                                id="starts_at"
                                v-model="form.starts_at"
                                type="datetime-local"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                for="expires_at"
                                >Expira</label
                            >
                            <input
                                id="expires_at"
                                v-model="form.expires_at"
                                type="datetime-local"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="usage_limit"
                            >Limite de usos (opcional)</label
                        >
                        <input
                            id="usage_limit"
                            v-model="form.usage_limit"
                            type="number"
                            min="1"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors.usage_limit"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.usage_limit }}
                        </p>
                    </div>

                    <label
                        class="inline-flex items-center gap-2 text-sm text-slate-700"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300"
                        />
                        Activo
                    </label>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            class="rounded-full bg-slate-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? "Guardando..."
                                    : editingId
                                      ? "Actualizar"
                                      : "Crear"
                            }}
                        </button>
                        <button
                            v-if="editingId"
                            type="button"
                            class="rounded-full border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700"
                            @click="resetForm"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </article>

            <article
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <h2 class="text-xl font-semibold text-slate-950">
                            Cupones
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ coupons.length }} resultados
                        </p>
                    </div>

                    <form
                        class="grid gap-3 sm:grid-cols-2"
                        @submit.prevent="applyFilters"
                    >
                        <input
                            v-model="filterForm.search"
                            type="text"
                            placeholder="Buscar por codigo"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        />
                        <select
                            v-model="filterForm.status"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="all">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                            <option value="expired">Expirados</option>
                        </select>
                        <div class="sm:col-span-2 flex gap-2">
                            <button
                                type="submit"
                                class="rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Filtrar
                            </button>
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-700"
                                @click="resetFilters"
                            >
                                Limpiar
                            </button>
                        </div>
                    </form>
                </div>

                <div
                    class="mt-6 overflow-hidden rounded-2xl border border-slate-200"
                >
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs uppercase tracking-[0.2em] text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Codigo</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Valor</th>
                                <th class="px-4 py-3">Vigencia</th>
                                <th class="px-4 py-3">Usos</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-for="coupon in coupons" :key="coupon.id">
                                <td
                                    class="px-4 py-3 font-semibold text-slate-900"
                                >
                                    {{ coupon.code }}
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        coupon.type === "percentage"
                                            ? "Porcentaje"
                                            : "Monto fijo"
                                    }}
                                </td>
                                <td
                                    class="px-4 py-3 font-semibold text-slate-900"
                                >
                                    {{
                                        coupon.type === "percentage"
                                            ? `${coupon.value}%`
                                            : `$${coupon.value.toFixed(2)}`
                                    }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <p>
                                        Inicio:
                                        {{ coupon.starts_at || "Sin fecha" }}
                                    </p>
                                    <p>
                                        Fin:
                                        {{ coupon.expires_at || "Sin fecha" }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <p>{{ coupon.times_used }} usados</p>
                                    <p v-if="coupon.usage_limit">
                                        Limite: {{ coupon.usage_limit }}
                                    </p>
                                    <p v-else>Sin limite</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="
                                            coupon.is_valid_now
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-slate-200 text-slate-700'
                                        "
                                    >
                                        {{
                                            coupon.is_valid_now
                                                ? "Vigente"
                                                : "No vigente"
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700"
                                            @click="editCoupon(coupon)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-full border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-700"
                                            @click="destroyCoupon(coupon.id)"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!coupons.length">
                                <td
                                    colspan="7"
                                    class="px-4 py-6 text-center text-slate-500"
                                >
                                    No hay cupones para mostrar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </AdminLayout>
</template>
