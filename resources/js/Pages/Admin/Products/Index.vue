<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import axios from "axios";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";

interface CategoryOption {
    id: number;
    name: string;
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
    is_active: boolean;
    category_id: number;
    category_name: string | null;
    category_ids: number[];
    category_names: string[];
    image_url: string | null;
    updated_at: string | null;
}

interface Filters {
    search: string;
    category_id: number | null;
    status: string;
}

const props = defineProps<{
    products: ProductItem[];
    categories: CategoryOption[];
    filters: Filters;
}>();

const categoryOptions = ref<CategoryOption[]>([]);

function hydrateCategoryOptions(categories: CategoryOption[]): void {
    categoryOptions.value = [...categories];
}

hydrateCategoryOptions(props.categories);

watch(
    () => props.categories,
    (nextCategories) => {
        hydrateCategoryOptions(nextCategories);
    },
);

const filterForm = reactive({
    search: props.filters.search ?? "",
    category_id: props.filters.category_id
        ? String(props.filters.category_id)
        : "",
    status: props.filters.status ?? "all",
});

const editingId = ref<number | null>(null);
const editingImageUrl = ref<string | null>(null);
const editingStockReference = ref<number>(1);
const secondaryCategoryStatus = reactive({
    loading: false,
    message: "",
    error: "",
});

const shouldShowSecondaryLoadButton = computed(
    () =>
        secondaryCategoryStatus.loading ||
        form.secondary_category_name.trim().length > 0,
);

const form = useForm({
    name: "",
    slug: "",
    hero_tag: "",
    description: "",
    price: "",
    stock: 0,
    is_active: true,
    category_id: "",
    category_name: "",
    category_ids: [] as string[],
    secondary_category_name: "",
    image: null as File | null,
    remove_image: false,
});

watch(
    () => form.category_id,
    (nextPrimaryId) => {
        if (!nextPrimaryId) {
            return;
        }

        form.category_ids = form.category_ids.filter(
            (id) => id !== nextPrimaryId,
        );
    },
);

watch(
    () => form.secondary_category_name,
    () => {
        secondaryCategoryStatus.message = "";
        secondaryCategoryStatus.error = "";
    },
);

function upsertCategoryOption(category: CategoryOption): void {
    const nextCategories = [...categoryOptions.value];
    const existingIndex = nextCategories.findIndex(
        (item) => item.id === category.id,
    );

    if (existingIndex >= 0) {
        nextCategories[existingIndex] = category;
    } else {
        nextCategories.push(category);
    }

    categoryOptions.value = nextCategories;
}

async function loadSecondaryCategory(): Promise<void> {
    const rawName = form.secondary_category_name.trim();

    secondaryCategoryStatus.message = "";
    secondaryCategoryStatus.error = "";

    if (!rawName) {
        secondaryCategoryStatus.error =
            "Escribe una categoria secundaria para cargar.";
        return;
    }

    secondaryCategoryStatus.loading = true;

    try {
        const response = await axios.post(
            route("admin.products.secondary-categories.store"),
            {
                name: rawName,
            },
        );

        const responseCategory = response.data?.category;
        const nextCategory: CategoryOption = {
            id: Number(responseCategory?.id),
            name: String(responseCategory?.name ?? rawName),
        };

        if (!Number.isInteger(nextCategory.id) || nextCategory.id <= 0) {
            secondaryCategoryStatus.error =
                "No se pudo cargar la categoria secundaria.";
            return;
        }

        upsertCategoryOption(nextCategory);

        const secondaryId = String(nextCategory.id);

        if (
            secondaryId !== form.category_id &&
            !form.category_ids.includes(secondaryId)
        ) {
            form.category_ids = [...form.category_ids, secondaryId];
        }

        if (secondaryId === form.category_id) {
            secondaryCategoryStatus.message =
                "La categoria cargada coincide con la principal, por eso no se marco como secundaria.";
        } else if (response.data?.created === false) {
            secondaryCategoryStatus.message =
                "Categoria existente cargada y marcada como secundaria.";
        } else {
            secondaryCategoryStatus.message =
                "Categoria secundaria creada y marcada correctamente.";
        }

        form.secondary_category_name = "";
    } catch (error: any) {
        const responseErrors = error?.response?.data?.errors ?? {};

        secondaryCategoryStatus.error = Array.isArray(responseErrors.name)
            ? responseErrors.name[0]
            : "No se pudo cargar la categoria secundaria. Intenta nuevamente.";
    } finally {
        secondaryCategoryStatus.loading = false;
    }
}

function applyFilters(): void {
    router.get(
        route("admin.products.index"),
        {
            search: filterForm.search || undefined,
            category_id: filterForm.category_id || undefined,
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
    filterForm.category_id = "";
    filterForm.status = "all";
    applyFilters();
}

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.image = target.files?.[0] ?? null;
}

function resetForm(): void {
    editingId.value = null;
    editingImageUrl.value = null;
    editingStockReference.value = 1;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.stock = 0;
    form.price = "";
    form.category_ids = [];
    form.secondary_category_name = "";
    secondaryCategoryStatus.loading = false;
    secondaryCategoryStatus.message = "";
    secondaryCategoryStatus.error = "";
}

function editProduct(product: ProductItem): void {
    editingId.value = product.id;
    editingImageUrl.value = product.image_url;
    editingStockReference.value = Math.max(product.stock_reference, 1);
    form.name = product.name;
    form.slug = product.slug;
    form.hero_tag = product.hero_tag ?? "";
    form.description = product.description ?? "";
    form.price = product.price.toFixed(2);
    form.stock = product.stock;
    form.is_active = product.is_active;
    form.category_id = String(product.category_id);
    form.category_name = "";
    form.category_ids = product.category_ids.map((id) => String(id));
    form.secondary_category_name = "";
    form.image = null;
    form.remove_image = false;
    secondaryCategoryStatus.message = "";
    secondaryCategoryStatus.error = "";
}

function submit(): void {
    const payload = {
        name: form.name,
        slug: form.slug,
        hero_tag: form.hero_tag,
        description: form.description,
        price: form.price,
        stock: form.stock,
        is_active: form.is_active,
        category_id: form.category_id !== "" ? Number(form.category_id) : null,
        category_name: form.category_name || null,
        category_ids: form.category_ids
            .map((id) => Number(id))
            .filter((id) => Number.isInteger(id) && id > 0),
        secondary_category_name: form.secondary_category_name || null,
        image: form.image,
        remove_image: form.remove_image,
        _method: editingId.value ? "put" : "post",
    };

    const url = editingId.value
        ? route("admin.products.update", editingId.value)
        : route("admin.products.store");

    form.transform(() => payload).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
}

function destroyProduct(productId: number): void {
    if (!window.confirm("Eliminar este producto del catalogo?")) {
        return;
    }

    router.delete(route("admin.products.destroy", productId), {
        preserveScroll: true,
    });
}

function stockLevelLabelByReference(
    stock: number,
    stockReference: number,
): string {
    if (stock <= 0) {
        return "Sin stock";
    }

    const ratio = stock / Math.max(stockReference, 1);

    if (ratio < 0.3) {
        return "Stock bajo";
    }

    if (ratio < 0.7) {
        return "Stock medio";
    }

    return "Stock alto";
}

function stockLevelClassByReference(
    stock: number,
    stockReference: number,
): string {
    if (stock <= 0) {
        return "bg-rose-100 text-rose-700";
    }

    const ratio = stock / Math.max(stockReference, 1);

    if (ratio < 0.3) {
        return "bg-amber-100 text-amber-700";
    }

    if (ratio < 0.7) {
        return "bg-sky-100 text-sky-700";
    }

    return "bg-emerald-100 text-emerald-700";
}

function secondaryCategoryNames(product: ProductItem): string {
    const secondary = product.category_names.filter(
        (name) => name !== product.category_name,
    );

    return secondary.length ? secondary.join(", ") : "Sin secundarias";
}
</script>

<template>
    <Head title="Admin · Productos" />

    <AdminLayout>
        <section class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
            <article
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2 class="text-xl font-semibold text-slate-950">
                    {{ editingId ? "Editar producto" : "Nuevo producto" }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Carga imagen cuadrada (se recorta automaticamente a 500x500
                    con Spatie Media Library).
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="name"
                            >Nombre</label
                        >
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            required
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="slug"
                            >Slug (opcional)</label
                        >
                        <input
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            placeholder="se-genera-automaticamente"
                        />
                        <p
                            v-if="form.errors.slug"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.slug }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="category_id"
                            >Categoria principal</label
                        >
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                        >
                            <option value="">Seleccionar</option>
                            <option
                                v-for="category in categoryOptions"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="category_name"
                            >Crear categoria principal al vuelo</label
                        >
                        <input
                            id="category_name"
                            v-model="form.category_name"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            placeholder="Solo si no existe"
                        />
                        <p
                            v-if="form.errors.category_id"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.category_id }}
                        </p>
                        <p
                            v-if="form.errors.category_name"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.category_name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="category_ids"
                            >Categorias secundarias</label
                        >
                        <div
                            id="category_ids"
                            class="max-h-40 space-y-2 overflow-y-auto rounded-xl border border-slate-300 bg-white p-3"
                        >
                            <label
                                v-for="category in categoryOptions"
                                :key="`extra-${category.id}`"
                                class="flex items-center gap-3 rounded-lg px-2 py-1 transition"
                                :class="
                                    String(category.id) === form.category_id
                                        ? 'cursor-not-allowed bg-slate-100 text-slate-400'
                                        : 'cursor-pointer hover:bg-slate-50'
                                "
                            >
                                <input
                                    v-model="form.category_ids"
                                    type="checkbox"
                                    :value="String(category.id)"
                                    :disabled="
                                        String(category.id) === form.category_id
                                    "
                                    class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                />
                                <span class="text-sm">{{ category.name }}</span>
                            </label>
                            <p
                                v-if="!categoryOptions.length"
                                class="text-sm text-slate-500"
                            >
                                No hay categorias creadas.
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Marca una o varias subcategorias relacionadas.
                        </p>
                        <p
                            v-if="form.errors.category_ids"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.category_ids }}
                        </p>
                        <p
                            v-if="form.errors['category_ids.0']"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors["category_ids.0"] }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="secondary_category_name"
                            >Crear categoria secundaria al vuelo</label
                        >
                        <div class="flex items-center gap-2">
                            <input
                                id="secondary_category_name"
                                v-model="form.secondary_category_name"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                placeholder="Ej: Gaming"
                                @keydown.enter.prevent="loadSecondaryCategory"
                            />
                            <button
                                v-if="shouldShowSecondaryLoadButton"
                                type="button"
                                class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-900 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="
                                    secondaryCategoryStatus.loading ||
                                    !form.secondary_category_name.trim()
                                "
                                @click="loadSecondaryCategory"
                            >
                                {{
                                    secondaryCategoryStatus.loading
                                        ? "Cargando..."
                                        : "Cargar"
                                }}
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Si no existe, se crea y se marca automaticamente.
                        </p>
                        <p
                            v-if="secondaryCategoryStatus.message"
                            class="mt-1 text-xs text-emerald-600"
                        >
                            {{ secondaryCategoryStatus.message }}
                        </p>
                        <p
                            v-if="secondaryCategoryStatus.error"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ secondaryCategoryStatus.error }}
                        </p>
                        <p
                            v-if="form.errors.secondary_category_name"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.secondary_category_name }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                for="price"
                                >Precio</label
                            >
                            <input
                                id="price"
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                required
                            />
                            <p
                                v-if="form.errors.price"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ form.errors.price }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                                for="stock"
                                >Cantidad en stock</label
                            >
                            <input
                                id="stock"
                                v-model.number="form.stock"
                                type="number"
                                min="0"
                                step="1"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                                required
                            />
                            <p class="mt-1 text-xs text-slate-500">
                                Nivel visible para cliente:
                                <span class="font-semibold">{{
                                    stockLevelLabelByReference(
                                        form.stock,
                                        editingId
                                            ? Math.max(editingStockReference, 1)
                                            : Math.max(form.stock, 1),
                                    )
                                }}</span>
                            </p>
                            <p
                                v-if="form.errors.stock"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ form.errors.stock }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="hero_tag"
                            >Etiqueta destacada</label
                        >
                        <input
                            id="hero_tag"
                            v-model="form.hero_tag"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            placeholder="Ej: Producto estrella"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="description"
                            >Descripcion</label
                        >
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-700"
                            for="image"
                            >Imagen</label
                        >
                        <input
                            id="image"
                            type="file"
                            accept="image/*"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            @change="onFileChange"
                        />
                        <p
                            v-if="form.errors.image"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.image }}
                        </p>
                        <div
                            v-if="editingImageUrl"
                            class="mt-3 overflow-hidden rounded-xl border border-slate-200"
                        >
                            <img
                                :src="editingImageUrl"
                                alt="Preview"
                                class="aspect-square w-24 object-cover"
                            />
                        </div>
                        <label
                            v-if="editingId"
                            class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600"
                        >
                            <input
                                v-model="form.remove_image"
                                type="checkbox"
                                class="rounded border-slate-300"
                            />
                            Quitar imagen actual
                        </label>
                    </div>

                    <label
                        class="inline-flex items-center gap-2 text-sm text-slate-700"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300"
                        />
                        Producto activo
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
                            Cancelar edicion
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
                            Catalogo de productos
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ products.length }} resultados
                        </p>
                    </div>

                    <form
                        class="grid gap-3 sm:grid-cols-3"
                        @submit.prevent="applyFilters"
                    >
                        <input
                            v-model="filterForm.search"
                            type="text"
                            placeholder="Buscar por nombre o slug"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        />
                        <select
                            v-model="filterForm.category_id"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="">Todas</option>
                            <option
                                v-for="category in categoryOptions"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <select
                            v-model="filterForm.status"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="all">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                        <div class="sm:col-span-3 flex gap-2">
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
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3">Precio</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-for="product in products" :key="product.id">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                                        >
                                            <img
                                                v-if="product.image_url"
                                                :src="product.image_url"
                                                alt="Imagen"
                                                class="h-full w-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-lg font-semibold text-slate-400"
                                            >
                                                {{ product.name.slice(0, 1) }}
                                            </div>
                                        </div>
                                        <div>
                                            <p
                                                class="font-semibold text-slate-900"
                                            >
                                                {{ product.name }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ product.slug }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="font-semibold text-slate-900"
                                        >
                                            Principal:
                                            {{
                                                product.category_name ||
                                                "Sin categoria"
                                            }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            Secundarias:
                                            {{
                                                secondaryCategoryNames(product)
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 font-semibold text-slate-900"
                                >
                                    ${{ product.price.toFixed(2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="font-semibold text-slate-900"
                                        >
                                            {{ product.stock }} /
                                            {{ product.stock_reference }} u.
                                        </span>
                                        <span
                                            class="inline-flex w-fit rounded-full px-2 py-1 text-[11px] font-semibold"
                                            :class="
                                                stockLevelClassByReference(
                                                    product.stock,
                                                    product.stock_reference,
                                                )
                                            "
                                        >
                                            {{
                                                stockLevelLabelByReference(
                                                    product.stock,
                                                    product.stock_reference,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="
                                            product.is_active
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-rose-100 text-rose-700'
                                        "
                                    >
                                        {{
                                            product.is_active
                                                ? "Activo"
                                                : "Inactivo"
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700"
                                            @click="editProduct(product)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-full border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-700"
                                            @click="destroyProduct(product.id)"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!products.length">
                                <td
                                    colspan="6"
                                    class="px-4 py-6 text-center text-slate-500"
                                >
                                    No hay productos para mostrar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </AdminLayout>
</template>
