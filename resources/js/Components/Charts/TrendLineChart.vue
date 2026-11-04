<script setup lang="ts">
import { ref, computed } from 'vue';
import type { ChartData, ChartOptions } from 'chart.js';
import BaseChart from './BaseChart.vue';

export interface MonthlyTrendItem {
    month: string;
    label: string;
    count?: number;
    violation_count: number;
    achievement_count: number;
    violation_points: number;
    achievement_points: number;
    net_points: number;
}

const props = withDefaults(
    defineProps<{
        trendData: MonthlyTrendItem[];
        title?: string;
        subtitle?: string;
        height?: string;
        loading?: boolean;
        error?: string | null;
        defaultType?: 'bar' | 'line';
    }>(),
    {
        title: 'Tren Pelanggaran & Prestasi',
        subtitle: 'Perbandingan dinamika kedisiplinan dan capaian siswa per bulan',
        height: '300px',
        loading: false,
        error: null,
        defaultType: 'bar',
    }
);

// Default to 'bar' chart model as requested
const chartType = ref<'bar' | 'line'>(props.defaultType);

// Toggle between count of events and points
const metricMode = ref<'count' | 'points'>('count');

const labels = computed(() => props.trendData.map((d) => d.label || d.month));

const chartData = computed<ChartData<'bar' | 'line'>>(() => {
    const isBar = chartType.value === 'bar';

    if (metricMode.value === 'count') {
        return {
            labels: labels.value,
            datasets: [
                {
                    type: isBar ? 'bar' : 'line',
                    label: 'Kejadian Pelanggaran (Kasus)',
                    data: props.trendData.map((d) => d.violation_count || 0),
                    borderColor: '#E11D48', // Rose 600
                    backgroundColor: isBar ? 'rgba(225, 29, 72, 0.82)' : 'rgba(225, 29, 72, 0.08)',
                    fill: !isBar,
                    tension: isBar ? 0 : 0.35,
                    borderWidth: 1.5,
                    borderRadius: isBar ? 4 : 0,
                    maxBarThickness: 32,
                    pointBackgroundColor: '#E11D48',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: isBar ? 0 : 4,
                    pointHoverRadius: 6,
                },
                {
                    type: isBar ? 'bar' : 'line',
                    label: 'Kejadian Prestasi (Kasus)',
                    data: props.trendData.map((d) => d.achievement_count || 0),
                    borderColor: '#059669', // Emerald 600
                    backgroundColor: isBar ? 'rgba(5, 150, 105, 0.82)' : 'rgba(5, 150, 105, 0.08)',
                    fill: !isBar,
                    tension: isBar ? 0 : 0.35,
                    borderWidth: 1.5,
                    borderRadius: isBar ? 4 : 0,
                    maxBarThickness: 32,
                    pointBackgroundColor: '#059669',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: isBar ? 0 : 4,
                    pointHoverRadius: 6,
                },
            ],
        };
    }

    return {
        labels: labels.value,
        datasets: [
            {
                type: isBar ? 'bar' : 'line',
                label: 'Poin Pelanggaran',
                data: props.trendData.map((d) => d.violation_points || 0),
                borderColor: '#E11D48',
                backgroundColor: isBar ? 'rgba(225, 29, 72, 0.82)' : 'rgba(225, 29, 72, 0.08)',
                fill: !isBar,
                tension: isBar ? 0 : 0.35,
                borderWidth: 1.5,
                borderRadius: isBar ? 4 : 0,
                maxBarThickness: 28,
                pointBackgroundColor: '#E11D48',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: isBar ? 0 : 4,
                pointHoverRadius: 6,
            },
            {
                type: isBar ? 'bar' : 'line',
                label: 'Poin Pengurang Prestasi',
                data: props.trendData.map((d) => d.achievement_points || 0),
                borderColor: '#059669',
                backgroundColor: isBar ? 'rgba(5, 150, 105, 0.82)' : 'rgba(5, 150, 105, 0.08)',
                fill: !isBar,
                tension: isBar ? 0 : 0.35,
                borderWidth: 1.5,
                borderRadius: isBar ? 4 : 0,
                maxBarThickness: 28,
                pointBackgroundColor: '#059669',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: isBar ? 0 : 4,
                pointHoverRadius: 6,
            },
            {
                type: isBar ? 'bar' : 'line',
                label: 'Poin Bersih',
                data: props.trendData.map((d) => d.net_points || 0),
                borderColor: '#6366F1', // Indigo 500
                backgroundColor: isBar ? 'rgba(99, 102, 241, 0.82)' : 'transparent',
                borderDash: isBar ? [] : [5, 5],
                tension: isBar ? 0 : 0.35,
                borderWidth: 1.5,
                borderRadius: isBar ? 4 : 0,
                maxBarThickness: 28,
                pointBackgroundColor: '#6366F1',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: isBar ? 0 : 4,
                pointHoverRadius: 6,
            },
        ],
    };
});

const chartOptions = computed<ChartOptions<'bar' | 'line'>>(() => {
    return {
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    padding: 14,
                    font: { size: 11, weight: 500 },
                    color: '#4B5563',
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const val = context.parsed.y ?? 0;
                        const label = context.dataset.label || '';
                        if (metricMode.value === 'count') {
                            return `${label}: ${val} kejadian`;
                        }
                        return `${label}: ${val} poin`;
                    },
                },
            },
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: metricMode.value === 'count' ? 'Jumlah Kejadian' : 'Total Poin',
                    font: {
                        size: 11,
                        weight: 500,
                    },
                    color: '#6B7280',
                },
                beginAtZero: true,
            },
            x: {
                title: {
                    display: true,
                    text: 'Bulan (WIB)',
                    font: {
                        size: 11,
                        weight: 500,
                    },
                    color: '#6B7280',
                },
            },
        },
    };
});

// Accessible table representation
const tableColumns = [
    { key: 'label', label: 'Bulan' },
    { key: 'violation_count', label: 'Kasus Pelanggaran', align: 'right' as const },
    { key: 'achievement_count', label: 'Kasus Prestasi', align: 'right' as const },
    { key: 'violation_points', label: 'Poin Pelanggaran', align: 'right' as const },
    { key: 'achievement_points', label: 'Poin Prestasi', align: 'right' as const },
    { key: 'net_points', label: 'Poin Bersih', align: 'right' as const },
];

const tableRows = computed(() => {
    return props.trendData.map((item) => ({
        label: item.label || item.month,
        violation_count: `${item.violation_count || 0} kasus`,
        achievement_count: `${item.achievement_count || 0} kasus`,
        violation_points: `${item.violation_points || 0} poin`,
        achievement_points: `${item.achievement_points || 0} poin`,
        net_points: `${item.net_points || 0} poin`,
    }));
});
</script>

<template>
    <BaseChart
        :type="chartType"
        :data="chartData"
        :options="chartOptions"
        :title="title"
        :subtitle="subtitle"
        :height="height"
        :loading="loading"
        :error="error"
        :table-columns="tableColumns"
        :table-rows="tableRows"
        empty-message="Belum ada catatan pelanggaran atau prestasi pada periode yang dipilih."
    >
        <template #actions>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Chart Model Toggle: Bar vs Line (Default: Bar) -->
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-xs">
                    <button
                        type="button"
                        @click="chartType = 'bar'"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                        :class="chartType === 'bar' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                        title="Tampilan Grafik Batang (Bar Chart)"
                    >
                        <i class="pi pi-chart-bar text-[10px]"></i>
                        <span>Batang</span>
                    </button>
                    <button
                        type="button"
                        @click="chartType = 'line'"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                        :class="chartType === 'line' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                        title="Tampilan Grafik Garis (Line Chart)"
                    >
                        <i class="pi pi-chart-line text-[10px]"></i>
                        <span>Garis</span>
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
