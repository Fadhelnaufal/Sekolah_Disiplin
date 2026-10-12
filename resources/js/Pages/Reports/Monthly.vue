<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    students: Array<any>;
    academic_years: Array<any>;
    classes: Array<any>;
    current_semester: any;
    filters: any;
}>();

const filterForm = ref({
    semester_id: props.filters.semester_id || props.current_semester.id,
    class_id: props.filters.class_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const applyFilter = () => {
    router.get('/reports/monthly', filterForm.value, { preserveState: true });
};

const exportExcel = () => {
    const url = new URL('/reports/monthly', window.location.origin);
    Object.keys(filterForm.value).forEach(key => url.searchParams.append(key, filterForm.value[key]));
    url.searchParams.append('export', 'excel');
    window.location.href = url.toString();
};

const exportPdf = () => {
    const url = new URL('/reports/monthly', window.location.origin);
    Object.keys(filterForm.value).forEach(key => url.searchParams.append(key, filterForm.value[key]));
    url.searchParams.append('export', 'pdf');
    window.open(url.toString(), '_blank');
};
</script>

<template>
    <Head title="Laporan Bulanan - Disiplin Pro" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan Bulanan Siswa</h2>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kelas</label>
                    <select v-model="filterForm.class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                        <option value="">Semua Kelas</option>
                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mulai Tanggal</label>
                    <input type="date" v-model="filterForm.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                    <input type="date" v-model="filterForm.end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIS / Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kejadian</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-red-500 uppercase tracking-wider">Poin Mins</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-green-500 uppercase tracking-wider">Prestasi</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-800 uppercase tracking-wider">Net Poin</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ student.name }}</div>
                                    <div class="text-sm text-gray-500">{{ student.nis }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ student.class_room ? student.class_room.name : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ student.violation_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-red-600">{{ student.violation_points }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-green-600">{{ student.achievement_points }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-900">{{ student.net_points }}</td>
                            </tr>
                            <tr v-if="students.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    Tidak ada data yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
