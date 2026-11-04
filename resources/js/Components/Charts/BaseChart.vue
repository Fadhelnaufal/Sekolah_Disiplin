<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed, shallowRef } from 'vue';
import {
    Chart,
    registerables,
    type ChartType,
    type ChartData,
    type ChartOptions,
} from 'chart.js';

Chart.register(...registerables);

interface TableColumn {
    key: string;
    label: string;
    align?: 'left' | 'center' | 'right';
}

const props = withDefaults(
    defineProps<{
        type: ChartType;
        data: ChartData;
        options?: ChartOptions;
        height?: string;
        title?: string;
        subtitle?: string;
        loading?: boolean;
        error?: string | null;
        empty?: boolean;
        emptyMessage?: string;
        showTableToggle?: boolean;
        tableColumns?: TableColumn[];
        tableRows?: Array<Record<string, any>>;
    }>(),
    {
        height: '280px',
        loading: false,
        error: null,
        empty: false,
        emptyMessage: 'Tidak ada data untuk periode ini.',
        showTableToggle: true,
        options: () => ({}),
        tableColumns: () => [],
        tableRows: () => [],
    }
);

const canvasRef = ref<HTMLCanvasElement | null>(null);
const chartInstance = shallowRef<Chart | null>(null);
const viewMode = ref<'chart' | 'table'>('chart');

const isActuallyEmpty = computed(() => {
    if (props.empty) return true;
    if (!props.data || !props.data.datasets || props.data.datasets.length === 0) return true;
    return props.data.datasets.every((ds) => {
        if (!ds.data || ds.data.length === 0) return true;
        return (ds.data as number[]).every((val) => val === 0 || val === null || val === undefined);
    });
});

const defaultOptions = computed<ChartOptions>(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    padding: 16,
                    font: {
                        family: 'system-ui, -apple-system, sans-serif',
                        size: 12,
                        weight: 500,
                    },
                    color: '#4B5563',
                },
            },
            tooltip: {
                backgroundColor: '#1E1B4B',
                titleColor: '#FFFFFF',
                bodyColor: '#E0E7FF',
                borderColor: '#4338CA',
                borderWidth: 1,
                padding: 10,
                cornerRadius: 8,
                titleFont: {
                    weight: 600,
                    size: 12,
                },
                bodyFont: {
                    size: 12,
                },
            },
        },
        scales: props.type === 'doughnut' || props.type === 'pie' ? undefined : {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    color: '#6B7280',
                    font: {
                        size: 11,
                    },
                },
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#F3F4F6',
                },
                ticks: {
                    color: '#6B7280',
                    font: {
                        size: 11,
                    },
                    precision: 0,
                },
            },
        },
        ...props.options,
    };
});

const initChart = () => {
    if (!canvasRef.value) return;

    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }

    if (props.loading || props.error || isActuallyEmpty.value) {
        return;
    }

    try {
        const ctx = canvasRef.value.getContext('2d');
        if (!ctx) return;

        chartInstance.value = new Chart(ctx, {
            type: props.type,
            data: JSON.parse(JSON.stringify(props.data)),
            options: defaultOptions.value,
        });
    } catch (e) {
        console.error('Error creating Chart.js instance:', e);
    }
};

onMounted(() => {
    initChart();
});

onBeforeUnmount(() => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
});

watch(
    () => [props.data, props.options, props.type, isActuallyEmpty.value, props.loading],
    () => {
        if (props.loading || isActuallyEmpty.value || props.error) {
            if (chartInstance.value) {
                chartInstance.value.destroy();
                chartInstance.value = null;
            }
            return;
        }

        if (!chartInstance.value) {
            initChart();
        } else {
            chartInstance.value.data = JSON.parse(JSON.stringify(props.data));
            chartInstance.value.options = defaultOptions.value;
            chartInstance.value.update();
        }
    },
    { deep: true }
);

watch(
    () => viewMode.value,
    (mode) => {
        if (mode === 'chart') {
            setTimeout(() => {
                initChart();
            }, 50);
        }
    }
);
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] flex flex-col justify-between">
        <!-- Chart Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
            <div>
                <h3 v-if="title" class="text-sm sm:text-base font-bold text-gray-900 tracking-tight">
                    {{ title }}
                </h3>
                <p v-if="subtitle" class="text-xs text-gray-500 mt-0.5">
                    {{ subtitle }}
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-center">
                <slot name="actions" />

                <!-- Table / Chart Toggle Button (Accessibility & Textual alternative) -->
                <button
                    v-if="showTableToggle && tableColumns.length > 0 && tableRows.length > 0"
                    type="button"
                    @click="viewMode = viewMode === 'chart' ? 'table' : 'chart'"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-medium transition cursor-pointer"
                    :title="viewMode === 'chart' ? 'Tampilkan Tabel Ringkas' : 'Tampilkan Grafik'"
                >
                    <i :class="viewMode === 'chart' ? 'pi pi-table' : 'pi pi-chart-line'" class="text-[11px]"></i>
                    <span>{{ viewMode === 'chart' ? 'Tabel' : 'Grafik' }}</span>
                </button>
            </div>
        </div>

        <!-- Chart Body Container -->
        <div class="relative w-full" :style="{ minHeight: height }">
            <!-- Loading State -->
            <div
                v-if="loading"
                class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 backdrop-blur-xs z-10"
            >
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-600 border-t-transparent mb-2"></div>
                <span class="text-xs text-gray-500 font-medium">Memuat data grafik...</span>
            </div>

            <!-- Error State -->
            <div
                v-else-if="error"
                class="absolute inset-0 flex flex-col items-center justify-center p-4 text-center z-10"
            >
                <div class="w-10 h-10 rounded-full bg-red-50 text-red-500 flex items-center justify-center mb-2">
                    <i class="pi pi-exclamation-circle text-lg"></i>
                </div>
                <h4 class="text-xs font-bold text-gray-800">Gagal Memuat Grafik</h4>
                <p class="text-xs text-gray-500 max-w-xs mt-1">{{ error }}</p>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="isActuallyEmpty"
                class="absolute inset-0 flex flex-col items-center justify-center p-4 text-center z-10"
            >
                <div class="w-10 h-10 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center mb-2">
                    <i class="pi pi-inbox text-lg"></i>
                </div>
                <h4 class="text-xs font-semibold text-gray-700">Tidak Ada Data</h4>
                <p class="text-xs text-gray-400 max-w-xs mt-0.5">{{ emptyMessage }}</p>
            </div>

            <!-- Table View Mode (Accessible Textual Alternative) -->
            <div
                v-if="viewMode === 'table' && !loading && !error && !isActuallyEmpty"
                class="w-full overflow-x-auto"
                :style="{ maxHeight: height }"
            >
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase font-semibold sticky top-0 border-b border-gray-200">
                        <tr>
                            <th
                                v-for="col in tableColumns"
                                :key="col.key"
                                class="py-2.5 px-3"
                                :class="{
                                    'text-right': col.align === 'right',
                                    'text-center': col.align === 'center',
                                    'text-left': !col.align || col.align === 'left'
                                }"
                            >
                                {{ col.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="(row, idx) in tableRows"
                            :key="idx"
                            class="hover:bg-gray-50/70 transition-colors"
                        >
                            <td
                                v-for="col in tableColumns"
                                :key="col.key"
                                class="py-2 px-3 text-gray-800"
                                :class="{
                                    'text-right': col.align === 'right',
                                    'text-center': col.align === 'center',
                                    'text-left': !col.align || col.align === 'left'
                                }"
                            >
                                {{ row[col.key] ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Canvas View Mode -->
            <div
                v-show="viewMode === 'chart' && !loading && !error && !isActuallyEmpty"
                class="relative w-full h-full"
                :style="{ height: height }"
            >
                <canvas ref="canvasRef"></canvas>
            </div>
        </div>

        <!-- Optional Footer Slot -->
        <div v-if="$slots.footer" class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
            <slot name="footer" />
        </div>
    </div>
</template>
