<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import TrendLineChart, { type MonthlyTrendItem } from '../../Components/Charts/TrendLineChart.vue';
import CategoryBarChart, { type CategorySummaryItem, type DetailedViolationItem } from '../../Components/Charts/CategoryBarChart.vue';
import ClassBarChart, { type ClassRecapItem } from '../../Components/Charts/ClassBarChart.vue';
import StatusDoughnutChart, { type FollowUpStatusItem } from '../../Components/Charts/StatusDoughnutChart.vue';

const props = defineProps<{
    monthly_trend: MonthlyTrendItem[];
    category_distribution: DetailedViolationItem[];
    category_summary?: CategorySummaryItem[];
    class_recap?: ClassRecapItem[];
    follow_up_distribution?: FollowUpStatusItem[];
    kpi_summary?: {
        total_violations: number;
        total_achievements: number;
        total_violation_points: number;
        total_achievement_points: number;
        total_net_points: number;
        total_follow_ups: number;
    };
    academic_years: Array<any>;
    classes?: Array<{ id: number; name: string }>;
    current_semester: any;
    filters: {
        semester_id?: number | string;
        class_id?: number | string;
        start_date?: string;
        end_date?: string;
    };
}>();

const filterForm = ref({
    semester_id: props.filters.semester_id || props.current_semester?.id || '',
    class_id: props.filters.class_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const isFiltering = ref(false);

const applyFilter = () => {
    isFiltering.value = true;
    router.get(
        '/reports/analytics',
        {
            semester_id: filterForm.value.semester_id,
            class_id: filterForm.value.class_id || undefined,
            start_date: filterForm.value.start_date || undefined,
            end_date: filterForm.value.end_date || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        }
    );
};

const resetFilter = () => {
    filterForm.value.class_id = '';
    filterForm.value.start_date = '';
    filterForm.value.end_date = '';
    applyFilter();
};
</script>

<template>
    <Head title="Analitik Kedisiplinan - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col">
                <h1 class="text-2xl font-bold text-gray-900 leading-tight tracking-tight">Analitik Kedisiplinan</h1>
                <span class="text-sm text-gray-500 mt-0.5">
                    Semester {{ current_semester?.name }} ({{ current_semester?.academic_year?.name || 'Tahun Ajaran' }})
                </span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
             <!-- KPI Cards Aligned With Active Filter -->
            <div v-if="kpi_summary" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="pi pi-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 leading-tight">
                            {{ kpi_summary.total_violations }}
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider ml-0.5">kasus</span>
                        </div>
                        <div class="text-xs font-medium text-gray-500 mt-1">
                            Total Pelanggaran ({{ kpi_summary.total_violation_points }} Poin)
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="pi pi-star text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 leading-tight">
                            {{ kpi_summary.total_achievements }}
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider ml-0.5">kasus</span>
                        </div>
                        <div class="text-xs font-medium text-gray-500 mt-1">
                            Total Prestasi ({{ kpi_summary.total_achievement_points }} Poin)
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="pi pi-shield text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 leading-tight">
                            {{ kpi_summary.total_net_points }}
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider ml-0.5">poin</span>
                        </div>
                        <div class="text-xs font-medium text-gray-500 mt-1">Akumulasi Poin Bersih</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="pi pi-check-circle text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 leading-tight">
                            {{ kpi_summary.total_follow_ups }}
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider ml-0.5">tindakan</span>
                        </div>
                        <div class="text-xs font-medium text-gray-500 mt-1">Total Tindak Lanjut</div>
                    </div>
                </div>
            </div>
            <!-- Filter Section Card -->
            <div class="bg-white p-5 md:p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 flex-1">
                        <!-- Filter Semester -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tahun / Semester</label>
                            <div class="relative">
                                <select
                                    v-model="filterForm.semester_id"
                                    class="w-full h-10 px-4 pr-10 appearance-none text-sm bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer transition-colors"
                                >
                                    <template v-for="ay in academic_years" :key="ay.id">
                                        <option v-for="sem in ay.semesters" :key="sem.id" :value="sem.id">
                                            {{ ay.name }} - {{ sem.name }}
                                        </option>
                                    </template>
                                </select>
                                <i class="pi pi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Filter Kelas -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kelas</label>
                            <div class="relative">
                                <select
                                    v-model="filterForm.class_id"
                                    class="w-full h-10 px-4 pr-10 appearance-none text-sm bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer transition-colors"
                                >
                                    <option value="">Semua Kelas</option>
                                    <option v-for="c in classes" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                                <i class="pi pi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Filter Tanggal Mulai -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Dari Tanggal</label>
                            <input
                                type="date"
                                v-model="filterForm.start_date"
                                class="w-full h-10 px-4 text-sm bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-colors"
                            />
                        </div>

                        <!-- Filter Tanggal Selesai -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Sampai Tanggal</label>
                            <input
                                type="date"
                                v-model="filterForm.end_date"
                                class="w-full h-10 px-4 text-sm bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Filter Action Buttons -->
                    <div class="flex items-center gap-3 self-end shrink-0 w-full lg:w-auto mt-2 lg:mt-0">
                        <button
                            type="button"
                            @click="resetFilter"
                            class="flex-1 lg:flex-none h-10 px-5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-all cursor-pointer"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            @click="applyFilter"
                            :disabled="isFiltering"
                            class="flex-1 lg:flex-none h-10 inline-flex items-center justify-center gap-2 px-5 rounded-lg bg-[#6366F1] hover:bg-[#4F46E5] text-white text-sm font-semibold shadow-sm transition-all cursor-pointer disabled:opacity-50"
                        >
                            <i v-if="isFiltering" class="pi pi-spin pi-spinner text-sm"></i>
                            <i v-else class="pi pi-filter text-sm"></i>
                            <span>Terapkan Filter</span>
                        </button>
                    </div>
                </div>
            </div>

           

            <!-- Row 1: Line Chart (Tren) & Bar Chart (Kategori Pelanggaran) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Line Chart: Tren Pelanggaran & Prestasi -->
                <TrendLineChart
                    :trend-data="monthly_trend"
                    :loading="isFiltering"
                    height="320px"
                    class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100"
                />

                <!-- 2. Bar Chart: Distribusi Kategori Pelanggaran -->
                <CategoryBarChart
                    :categories="category_summary || []"
                    :detailed-violations="category_distribution || []"
                    :loading="isFiltering"
                    height="320px"
                    class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100"
                />
            </div>

            <!-- Row 2: Horizontal Bar Chart (Rekap Kelas) & Doughnut Chart (Status Tindak Lanjut) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 3. Horizontal Bar Chart: Rekap Per Kelas -->
                <ClassBarChart
                    :class-data="class_recap || []"
                    :loading="isFiltering"
                    height="320px"
                    class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100"
                />

                <!-- 4. Doughnut Chart: Status Tindak Lanjut -->
                <StatusDoughnutChart
                    :status-data="follow_up_distribution || []"
                    :loading="isFiltering"
                    height="320px"
                    class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>