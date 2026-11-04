<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    class_aggregates: Array<any>;
    academic_years: Array<any>;
    current_semester: any;
    filters: any;
}>();

const page = usePage();
const isHomeroomRoute = computed(() => page.url.startsWith('/reports/homerooms'));

const filterForm = ref({
    semester_id: props.filters.semester_id || props.current_semester?.id || '',
});

const applyFilter = () => {
    const routePath = isHomeroomRoute.value ? '/reports/homerooms' : '/reports/classes';
    router.get(routePath, filterForm.value, { preserveState: true });
};

const exportExcel = () => {
    const routePath = isHomeroomRoute.value ? '/reports/homerooms' : '/reports/classes';
    const url = new URL(routePath, window.location.origin);
    Object.keys(filterForm.value).forEach(key => url.searchParams.append(key, filterForm.value[key]));
    url.searchParams.append('export', 'excel');
    window.location.href = url.toString();
};

const exportPdf = () => {
    const routePath = isHomeroomRoute.value ? '/reports/homerooms' : '/reports/classes';
    const url = new URL(routePath, window.location.origin);
    Object.keys(filterForm.value).forEach(key => url.searchParams.append(key, filterForm.value[key]));
    url.searchParams.append('export', 'pdf');
    window.open(url.toString(), '_blank');
};
</script>

<template>
    <Head :title="isHomeroomRoute ? 'Laporan Wali Kelas - Sekolah Disiplin' : 'Laporan Kelas - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">
                        {{ isHomeroomRoute ? 'Laporan Rekapitulasi Wali Kelas' : 'Laporan Rekapitulasi Kelas' }}
                    </h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">Ringkasan agregat kedisiplinan dan prestasi siswa per rombongan belajar.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="exportPdf"
                        class="px-4 py-2 rounded-full border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 text-xs font-semibold transition-all flex items-center gap-1.5"
                    >
                        <i class="pi pi-file-pdf text-xs"></i>
                        Export PDF
                    </button>
                    <button
                        @click="exportExcel"
                        class="px-4 py-2 rounded-full border border-green-200 bg-green-50 text-green-700 hover:bg-green-100 text-xs font-semibold transition-all flex items-center gap-1.5"
                    >
                        <i class="pi pi-file-excel text-xs"></i>
                        Export Excel
                    </button>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                <!-- Filters Toolbar -->
                <div class="flex flex-wrap items-end gap-3 p-4 bg-gray-50/70 rounded-2xl border border-gray-100">
                    <div class="w-full sm:w-64">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tahun / Semester</label>
                        <select v-model="filterForm.semester_id" class="w-full text-xs rounded-xl bg-white border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]">
                            <template v-for="ay in academic_years" :key="ay.id">
                                <option v-for="sem in ay.semesters" :key="sem.id" :value="sem.id">
                                    {{ ay.name }} - {{ sem.name }}
                                </option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <button
                            @click="applyFilter"
                            class="px-5 py-2 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm"
                        >
                            Terapkan Filter
                        </button>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3 text-left">Nama Kelas</th>
                                <th class="pb-3 text-center">Total Siswa Aktif</th>
                                <th class="pb-3 text-center">Total Pelanggaran</th>
                                <th class="pb-3 text-center">Total Prestasi</th>
                                <th class="pb-3 text-center">Rata-rata Net Poin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="cls in class_aggregates" :key="cls.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4">
                                    <div class="font-bold text-gray-900">{{ cls.name }}</div>
                                </td>
                                <td class="py-4 text-center text-xs font-semibold text-gray-700">
                                    {{ cls.students_count }} siswa
                                </td>
                                <td class="py-4 text-center">
                                    <div class="text-xs font-bold text-red-600">{{ cls.total_violation_points }} pt</div>
                                    <div class="text-[11px] text-gray-500">({{ cls.total_violation_count }} kejadian)</div>
                                </td>
                                <td class="py-4 text-center">
                                    <div class="text-xs font-bold text-green-600">{{ cls.total_achievement_points }} pt</div>
                                    <div class="text-[11px] text-gray-500">({{ cls.total_achievement_count }} kejadian)</div>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800">
                                        {{ cls.average_net_points }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="class_aggregates.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-500 text-xs">
                                    Tidak ada data kelas yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
