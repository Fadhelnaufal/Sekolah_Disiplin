<script setup lang="ts">
withDefaults(
    defineProps<{
        modelValue?: string;
        searchPlaceholder?: string;
        searchWidth?: string;
        totalRecords?: number;
        filteredRecords?: number;
        recordLabel?: string;
        hasActiveFilters?: boolean;
    }>(),
    {
        modelValue: '',
        searchPlaceholder: 'Cari data...',
        searchWidth: 'w-full sm:w-64 md:w-72',
        totalRecords: undefined,
        filteredRecords: undefined,
        recordLabel: 'data',
        hasActiveFilters: false,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'reset'): void;
}>();

const onInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};

const clearSearch = () => {
    emit('update:modelValue', '');
};
</script>

<template>
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-5">
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Global Search Input (Standard 36px Height, Rounded Pill) -->
            <div class="relative" :class="searchWidth">
                <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input
                    :value="modelValue"
                    @input="onInput"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="w-full pl-9 pr-8 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                />
                <button
                    v-if="modelValue"
                    type="button"
                    @click="clearSearch"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer transition-colors"
                    title="Bersihkan pencarian"
                >
                    <i class="pi pi-times text-[10px]"></i>
                </button>
            </div>

            <!-- Custom Filters Slot (Dropdowns, Toggles, etc.) -->
            <slot name="filters" />

            <!-- Reset Filter Button -->
            <button
                v-if="hasActiveFilters"
                type="button"
                @click="emit('reset')"
                class="text-xs text-gray-500 hover:text-gray-900 underline px-2 py-1 cursor-pointer transition-colors"
            >
                Reset Filter
            </button>
        </div>

        <!-- Right Side: Counter and Extra Actions -->
        <div class="flex items-center gap-3 justify-between sm:justify-end shrink-0">
            <slot name="actions" />

            <!-- Records Counter -->
            <div v-if="totalRecords !== undefined || filteredRecords !== undefined" class="text-xs font-medium text-gray-400 whitespace-nowrap">
                <template v-if="filteredRecords !== undefined && totalRecords !== undefined && filteredRecords !== totalRecords">
                    Menampilkan <span class="text-gray-900 font-bold">{{ filteredRecords }}</span> dari {{ totalRecords }} {{ recordLabel }}
                </template>
                <template v-else-if="filteredRecords !== undefined">
                    Menampilkan <span class="text-gray-900 font-bold">{{ filteredRecords }}</span> {{ recordLabel }}
                </template>
                <template v-else-if="totalRecords !== undefined">
                    Total: <span class="text-gray-900 font-bold">{{ totalRecords }}</span> {{ recordLabel }}
                </template>
            </div>
        </div>
    </div>
</template>
