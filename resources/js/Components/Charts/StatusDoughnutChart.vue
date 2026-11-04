<script setup lang="ts">
import { computed } from 'vue';
import { Chart, type ChartData, type ChartOptions } from 'chart.js';
import BaseChart from './BaseChart.vue';

export interface FollowUpStatusItem {
    status: string;
    label: string;
    count: number;
}

const props = withDefaults(
    defineProps<{
        statusData: FollowUpStatusItem[];
        title?: string;
        subtitle?: string;
        height?: string;
        loading?: boolean;
        error?: string | null;
    }>(),
    {
        title: 'Status Tindak Lanjut Siswa',
        subtitle: 'Distribusi status penanganan pembinaan dan arahan',
        height: '280px',
        loading: false,
        error: null,
    }
);

const statusColorMap: Record<string, { bg: string; border: string }> = {
    pending: { bg: '#F59E0B', border: '#D97706' }, // Amber
    in_progress: { bg: '#3B82F6', border: '#2563EB' }, // Blue
    completed: { bg: '#10B981', border: '#059669' }, // Emerald
    cancelled: { bg: '#9CA3AF', border: '#6B7280' }, // Gray
};

const defaultColor = { bg: '#8B5CF6', border: '#7C3AED' };

const totalCount = computed(() => {
    return props.statusData.reduce((acc, curr) => acc + (curr.count || 0), 0);
});

const chartData = computed<ChartData<'doughnut'>>(() => {
    const labels = props.statusData.map((s) => s.label);
    const dataValues = props.statusData.map((s) => s.count || 0);
    const bgColors = props.statusData.map((s) => statusColorMap[s.status]?.bg || defaultColor.bg);
    const borderColors = props.statusData.map((s) => statusColorMap[s.status]?.border || defaultColor.border);

    return {
        labels,
        datasets: [
            {
                data: dataValues,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 2,
                hoverOffset: 6,
            },
        ],
    };
});

const chartOptions = computed<ChartOptions<'doughnut'>>(() => {
    return {
        cutout: '70%',
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    padding: 14,
                    font: { size: 11, weight: 500 },
                    generateLabels: (chart) => {
                        const original = Chart.overrides.doughnut.plugins.legend.labels.generateLabels(chart);
                        return original.map((item, idx) => {
                            const count = props.statusData[idx]?.count ?? 0;
                            const pct = totalCount.value > 0 ? ((count / totalCount.value) * 100).toFixed(0) : '0';
                            item.text = `${item.text} (${count} • ${pct}%)`;
                            return item;
                        });
                    },
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const val = Number(context.raw) || 0;
                        const pct = totalCount.value > 0 ? ((val / totalCount.value) * 100).toFixed(1) : '0';
                        return `${context.label}: ${val} tindakan (${pct}%)`;
                    },
                },
            },
        },
    };
});

const tableColumns = [
    { key: 'label', label: 'Status Tindak Lanjut' },
    { key: 'count', label: 'Jumlah', align: 'right' as const },
    { key: 'percentage', label: 'Persentase', align: 'right' as const },
];

const tableRows = computed(() => {
    return props.statusData.map((item) => {
        const count = item.count || 0;
        const pct = totalCount.value > 0 ? ((count / totalCount.value) * 100).toFixed(1) : '0';
        return {
            label: item.label,
            count: `${count} kasus`,
            percentage: `${pct}%`,
        };
    });
});
</script>

<template>
    <BaseChart
        type="doughnut"
        :data="chartData"
        :options="chartOptions"
        :title="title"
        :subtitle="subtitle"
        :height="height"
        :loading="loading"
        :error="error"
        :table-columns="tableColumns"
        :table-rows="tableRows"
        empty-message="Belum ada data status tindak lanjut pada periode ini."
    >
        <template #footer>
            <div class="flex items-center justify-between text-xs text-gray-500 pt-1">
                <span>Total Tindak Lanjut Terdaftar:</span>
                <span class="font-bold text-gray-900">{{ totalCount }} tindakan</span>
            </div>
        </template>
    </BaseChart>
</template>
