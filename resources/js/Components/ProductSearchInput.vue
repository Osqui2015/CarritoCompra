<script setup lang="ts">
import axios from "axios";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

interface SearchResult {
    id: number;
    name: string;
    slug: string;
    price: number;
    image_url: string | null;
    category_names: string[];
    href: string;
}

const query = ref("");
const results = ref<SearchResult[]>([]);
const loading = ref(false);
const isOpen = ref(false);
const containerRef = ref<HTMLElement | null>(null);
let debounceTimer: number | null = null;
let latestRequestId = 0;

const normalizedQuery = computed(() => query.value.trim());
const canSearch = computed(() => normalizedQuery.value.length >= 3);

function formatMoney(value: number): string {
    return `$${new Intl.NumberFormat("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)}`;
}

function closeDropdown(): void {
    isOpen.value = false;
}

function openDropdown(): void {
    if (normalizedQuery.value.length > 0 || results.value.length > 0) {
        isOpen.value = true;
    }
}

async function searchProducts(searchTerm: string): Promise<void> {
    const requestId = ++latestRequestId;
    loading.value = true;

    try {
        const response = await axios.get(route("products.search"), {
            params: { q: searchTerm },
        });

        if (requestId !== latestRequestId) {
            return;
        }

        results.value = Array.isArray(response.data?.results)
            ? response.data.results
            : [];
        isOpen.value = true;
    } catch {
        if (requestId !== latestRequestId) {
            return;
        }

        results.value = [];
        isOpen.value = true;
    } finally {
        if (requestId === latestRequestId) {
            loading.value = false;
        }
    }
}

function scheduleSearch(): void {
    if (debounceTimer !== null) {
        window.clearTimeout(debounceTimer);
    }

    if (!canSearch.value) {
        latestRequestId++;
        loading.value = false;
        results.value = [];
        isOpen.value = normalizedQuery.value.length > 0;
        return;
    }

    debounceTimer = window.setTimeout(() => {
        searchProducts(normalizedQuery.value);
    }, 250);
}

function handleDocumentClick(event: MouseEvent): void {
    const target = event.target as Node;

    if (containerRef.value?.contains(target)) {
        return;
    }

    closeDropdown();
}

watch(
    () => query.value,
    () => {
        scheduleSearch();
    },
);

onMounted(() => {
    document.addEventListener("click", handleDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleDocumentClick);

    if (debounceTimer !== null) {
        window.clearTimeout(debounceTimer);
    }
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <input
            v-model="query"
            type="text"
            class="w-full rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-700 outline-none focus:border-orange-400"
            placeholder="Buscar productos, marcas y mas..."
            @focus="openDropdown"
            @keydown.esc.prevent="closeDropdown"
        />

        <div
            v-if="isOpen"
            class="absolute left-0 right-0 top-full z-[90] mt-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
        >
            <p
                v-if="!canSearch"
                class="px-2 py-2 text-xs font-semibold text-slate-500"
            >
                Escribe al menos 3 letras para buscar.
            </p>

            <p
                v-else-if="loading"
                class="px-2 py-2 text-xs font-semibold text-slate-500"
            >
                Buscando...
            </p>

            <p
                v-else-if="!results.length"
                class="px-2 py-2 text-xs font-semibold text-slate-500"
            >
                No encontramos coincidencias.
            </p>

            <a
                v-for="result in results"
                v-else
                :key="result.id"
                :href="result.href"
                class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-100"
                @click="closeDropdown"
            >
                <div
                    class="h-10 w-10 overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                >
                    <img
                        v-if="result.image_url"
                        :src="result.image_url"
                        :alt="result.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"
                    >
                        no image
                    </div>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">
                        {{ result.name }}
                    </p>
                    <p class="truncate text-[11px] text-slate-500">
                        {{
                            result.category_names.length
                                ? result.category_names.join(" / ")
                                : "Catalogo"
                        }}
                    </p>
                </div>

                <span class="text-xs font-semibold text-orange-600">
                    {{ formatMoney(result.price) }}
                </span>
            </a>
        </div>
    </div>
</template>
