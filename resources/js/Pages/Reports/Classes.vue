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
    semester_id: props.filters.semester_id || props.current_semester.id,
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
    <Head :title="isHomeroomRoute ? 'Laporan Wali Kelas - Disiplin Pro' : 'Laporan Kelas - Disiplin Pro'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isHomeroomRoute ? 'Laporan Rekapitulasi Wali Kelas' : 'Laporan Rekapitulasi Kelas' }}
            </h2>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <!-- Filter Section -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6 border border-gray-100 flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tahun/Semester</label>
                    <select v-model="filterForm.semester_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                        <template v-for="ay in academic_years" :key="ay.id">
                            <option v-for="sem in ay.semesters" :key="sem.id" :value="sem.id">
                                {{ ay.name }} - {{ sem.name }}
                            </option>
                        </template>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilter" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 transition">Filter</button>
                </div>
                <div class="ml-auto flex gap-2">
                    <button @click="exportPdf" class="border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-md text-sm transition">Export PDF</button>
                    <button @click="exportExcel" class="border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 px-4 py-2 rounded-md text-sm transition">Export Excel</button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kelas</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Siswa Aktif</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-red-500 uppercase tracking-wider">Total Pelanggaran</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-green-500 uppercase tracking-wider">Total Prestasi</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-800 uppercase tracking-wider">Rata-rata Net Poin</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="cls in class_aggregates" :key="cls.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ cls.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ cls.students_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-semibold text-red-600">{{ cls.total_violation_points }} Poin</div>
                                    <div class="text-xs text-gray-400">({{ cls.total_violation_count }} kejadian)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-semibold text-green-600">{{ cls.total_achievement_points }} Poin</div>
                                    <div class="text-xs text-gray-400">({{ cls.total_achievement_count }} kejadian)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-900">{{ cls.average_net_points }}</td>
                            </tr>
                            <tr v-if="class_aggregates.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
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
