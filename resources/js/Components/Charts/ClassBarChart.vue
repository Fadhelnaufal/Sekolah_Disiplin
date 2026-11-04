<script setup lang="ts">
import { ref, computed } from 'vue';
import type { ChartData, ChartOptions } from 'chart.js';
import BaseChart from './BaseChart.vue';

export interface ClassRecapItem {
    id?: number;
    name: string;
    students_count: number;
    total_violation_count: number;
    total_violation_points: number;
    total_achievement_count: number;
    total_achievement_points: number;
    total_net_points: number;
    average_net_points: number;
}

const props = withDefaults(
    defineProps<{
        classData: ClassRecapItem[];
        title?: string;
        subtitle?: string;
        height?: string;
        loading?: boolean;
        error?: string | null;
        maxItems?: number;
    }>(),
    {
        title: 'Rekapitulasi Disiplin per Kelas',
        subtitle: 'Perbandingan akumulasi pelanggaran dan prestasi antar kelas',
        height: '320px',
        loading: false,
        error: null,
        maxItems: 10,
    }
);

// Toggle between count of events and net points
const metricMode = ref<'events' | 'points'>('events');

const sortedClasses = computed(() => {
    const list = [...props.classData];
    if (metricMode.value === 'events') {
        list.sort((a, b) => (b.total_violation_count || 0) - (a.total_violation_count || 0));
    } else {
        list.sort((a, b) => (b.total_net_points || 0) - (a.total_net_points || 0));
    }
    return list.slice(0, props.maxItems);
});

const labels = computed(() => sortedClasses.value.map((c) => c.name));

const chartData = computed<ChartData<'bar'>>(() => {
    if (metricMode.value === 'events') {
        return {
            labels: labels.value,
            datasets: [
                {
                    label: 'Jumlah Pelanggaran (Kasus)',
                    data: sortedClasses.value.map((c) => c.total_violation_count || 0),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: '#EF4444',
                    borderWidth: 1.5,
                    borderRadius: 4,
                },
                {
                    label: 'Jumlah Prestasi (Kasus)',
                    data: sortedClasses.value.map((c) => c.total_achievement_count || 0),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10B981',
                    borderWidth: 1.5,
                    borderRadius: 4,
                },
            ],
        };
    }

    return {
        labels: labels.value,
        datasets: [
            {
                label: 'Total Poin Bersih Kelas',
                data: sortedClasses.value.map((c) => c.total_net_points || 0),
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: '#6366F1',
                borderWidth: 1.5,
                borderRadius: 4,
            },
            {
                label: 'Rata-rata Poin per Siswa',
                data: sortedClasses.value.map((c) => c.average_net_points || 0),
                backgroundColor: 'rgba(245, 158, 11, 0.8)',
                borderColor: '#F59E0B',
                borderWidth: 1.5,
                borderRadius: 4,
            },
        ],
    };
});

const chartOptions = computed<ChartOptions<'bar'>>(() => {
    return {
        indexAxis: 'y', // Horizontal Bar Chart
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    padding: 12,
                    font: { size: 11, weight: 500 },
                },
            },
            tooltip: {
                callbacks: {
                    afterBody: (context) => {
                        const idx = context[0]?.dataIndex ?? -1;
                        if (idx >= 0 && sortedClasses.value[idx]) {
                            const c = sortedClasses.value[idx];
                            return [
                                `Siswa Aktif: ${c.students_count} siswa`,
                                `Rata-rata Poin: ${c.average_net_points} poin/siswa`,
                            ];
                        }
                        return [];
                    },
                },
            },
        },
        scales: {
            x: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: metricMode.value === 'events' ? 'Jumlah Kejadian' : 'Nilai Poin',
                    font: { size: 11, weight: 500 },
                    color: '#6B7280',
                },
            },
            y: {
                ticks: {
                    font: { size: 11, weight: 600 },
                    color: '#374151',
                },
            },
        },
    };
});

const tableColumns = [
    { key: 'name', label: 'Kelas' },
    { key: 'students_count', label: 'Siswa Aktif', align: 'right' as const },
    { key: 'violations', label: 'Pelanggaran', align: 'right' as const },
    { key: 'achievements', label: 'Prestasi', align: 'right' as const },
    { key: 'net_points', label: 'Total Poin Bersih', align: 'right' as const },
    { key: 'avg_points', label: 'Rata-rata/Siswa', align: 'right' as const },
];

const tableRows = computed(() => {
    return sortedClasses.value.map((c) => ({
        name: c.name,
        students_count: `${c.students_count} siswa`,
        violations: `${c.total_violation_count} (${c.total_violation_points} poin)`,
        achievements: `${c.total_achievement_count} (${c.total_achievement_points} poin)`,
        net_points: `${c.total_net_points} poin`,
        avg_points: `${c.average_net_points} poin`,
    }));
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
        empty-message="Belum ada data rekapitulasi kelas untuk semester ini."
    >
        <template #actions>
            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-xs">
                <button
                    type="button"
                    @click="metricMode = 'events'"
                    class="px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                    :class="metricMode === 'events' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                >
                    Jumlah Kejadian
                </button>
                <button
                    type="button"
                    @click="metricMode = 'points'"
                    class="px-2.5 py-1 rounded-md font-medium transition cursor-pointer"
                    :class="metricMode === 'points' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'"
                >
                    Poin Bersih
                </button>
            </div>
        </template>
    </BaseChart>
</template>
