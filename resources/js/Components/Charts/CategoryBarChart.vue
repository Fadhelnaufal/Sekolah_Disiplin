<script setup lang="ts">
import { ref, computed } from 'vue';
import type { ChartData, ChartOptions } from 'chart.js';
import BaseChart from './BaseChart.vue';

export interface CategorySummaryItem {
    category: string;
    count: number;
    total_points: number;
}

export interface DetailedViolationItem {
    category: string;
    name: string;
    count: number;
    total_points: number;
}

const props = withDefaults(
    defineProps<{
        categories: CategorySummaryItem[];
        detailedViolations?: DetailedViolationItem[];
        title?: string;
        subtitle?: string;
        height?: string;
        loading?: boolean;
        error?: string | null;
    }>(),
    {
        title: 'Distribusi Kategori Pelanggaran',
        subtitle: 'Proporsi kejadian berdasarkan tingkat keparahan dan jenis tata tertib',
        height: '300px',
        loading: false,
        error: null,
        detailedViolations: () => [],
    }
);

const metricMode = ref<'count' | 'points'>('count');
const viewType = ref<'category' | 'specific'>('category');

const categoryColors: Record<string, { bg: string; border: string }> = {
    Ringan: { bg: 'rgba(245, 158, 11, 0.75)', border: '#F59E0B' }, // Amber
    Sedang: { bg: 'rgba(249, 115, 22, 0.75)', border: '#F97316' }, // Orange
    Berat: { bg: 'rgba(239, 68, 68, 0.8)', border: '#EF4444' }, // Red
    'Sangat Berat': { bg: 'rgba(159, 18, 57, 0.85)', border: '#9F1239' }, // Rose 900
    Lainnya: { bg: 'rgba(139, 92, 246, 0.75)', border: '#8B5CF6' }, // Violet
};

const defaultColor = { bg: 'rgba(99, 102, 241, 0.75)', border: '#6366F1' };

const displayItems = computed(() => {
    if (viewType.value === 'specific' && props.detailedViolations.length > 0) {
        return props.detailedViolations.slice(0, 8).map((d) => ({
            label: d.name,
            sub: d.category,
            count: Number(d.count || 0),
            points: Number(d.total_points || 0),
        }));
    }

    return props.categories.map((c) => ({
        label: c.category || 'Tidak Berkategori',
        sub: '',
        count: Number(c.count || 0),
        points: Number(c.total_points || 0),
    }));
});

const totalSum = computed(() => {
    return displayItems.value.reduce(
        (acc, item) => acc + (metricMode.value === 'count' ? item.count : item.points),
        0
    );
});

const chartData = computed<ChartData<'bar'>>(() => {
    const labels = displayItems.value.map((i) => i.label);
    const dataValues = displayItems.value.map((i) => (metricMode.value === 'count' ? i.count : i.points));

    const bgColors = displayItems.value.map((i) => {
        const catKey = viewType.value === 'specific' ? i.sub : i.label;
        return categoryColors[catKey]?.bg || defaultColor.bg;
    });

    const borderColors = displayItems.value.map((i) => {
        const catKey = viewType.value === 'specific' ? i.sub : i.label;
        return categoryColors[catKey]?.border || defaultColor.border;
    });

    return {
        labels,
        datasets: [
            {
                label: metricMode.value === 'count' ? 'Jumlah Kejadian' : 'Total Poin',
                data: dataValues,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 1.5,
                borderRadius: 6,
                maxBarThickness: 48,
            },
        ],
    };
});

const chartOptions = computed<ChartOptions<'bar'>>(() => {
    return {
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const val = context.parsed.y ?? 0;
                        const pct = totalSum.value > 0 ? ((val / totalSum.value) * 100).toFixed(1) : '0';
                        if (metricMode.value === 'count') {
                            return `${val} kejadian (${pct}% dari total)`;
                        }
                        return `${val} poin (${pct}% dari total)`;
                    },
                },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: metricMode.value === 'count' ? 'Jumlah Kejadian' : 'Total Poin',
                    font: { size: 11, weight: 500 },
                    color: '#6B7280',
                },
            },
            x: {
                ticks: {
                    callback: function (val, index) {
                        const label = this.getLabelForValue(val as number);
                        return label.length > 18 ? label.substring(0, 16) + '...' : label;
                    },
                    maxRotation: 25,
                    minRotation: 0,
                },
            },
        },
    };
});

const tableColumns = [
    { key: 'label', label: 'Kategori / Pelanggaran' },
    { key: 'count', label: 'Jumlah Kejadian', align: 'right' as const },
    { key: 'points', label: 'Total Poin', align: 'right' as const },
    { key: 'percentage', label: 'Persentase', align: 'right' as const },
];

const tableRows = computed(() => {
    return displayItems.value.map((item) => {
        const val = metricMode.value === 'count' ? item.count : item.points;
        const pct = totalSum.value > 0 ? ((val / totalSum.value) * 100).toFixed(1) : '0';
        return {
            label: item.sub ? `${item.label} (${item.sub})` : item.label,
            count: `${item.count} kejadian`,
            points: `${item.points} poin`,
            percentage: `${pct}%`,
        };
    });
});
</script>

<template>
    <BaseChart
        type="bar"
        :data="chartData"
        :options="chartOptions"
        :title="title"
        :subtitle="subtitle"
        :height="height"
        :loading="loading"
        :error="error"
        :table-columns="tableColumns"
        :table-rows="tableRows"
        empty-message="Belum ada data kategori pelanggaran pada filter ini."
    >
        <template #actions>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Grouping Toggle: Kategori vs Spesifik -->
                <div v-if="detailedViolations.length > 0" class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-xs">
                    <button
                        type="button"
                        @click="viewType = 'category'"
                        class="px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                        :class="viewType === 'category' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                    >
                        Tingkat
                    </button>
                    <button
                        type="button"
                        @click="viewType = 'specific'"
                        class="px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                        :class="viewType === 'specific' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                    >
                        Top Kasus
                    </button>
                </div>

                <!-- Metric Toggle: Kasus vs Poin -->
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-xs">
                    <button
                        type="button"
                        @click="metricMode = 'count'"
                        class="px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                        :class="metricMode === 'count' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                    >
                        Kasus
                    </button>
                    <button
                        type="button"
                        @click="metricMode = 'points'"
                        class="px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                        :class="metricMode === 'points' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                    >
                        Poin
                    </button>
                </div>
            </div>
        </template>
    </BaseChart>
</template>
