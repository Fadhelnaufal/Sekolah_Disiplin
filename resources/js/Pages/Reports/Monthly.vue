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
    semester_id: props.filters.semester_id || props.current_semester?.id || '',
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
    <Head title="Laporan Bulanan - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Laporan Bulanan Siswa</h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">Rekapitulasi poin kedisiplinan dan capaian prestasi siswa berdasarkan periode.</p>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 p-4 bg-gray-50/70 rounded-2xl border border-gray-100 items-end">
                    <div>
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
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Kelas</label>
                        <select v-model="filterForm.class_id" class="w-full text-xs rounded-xl bg-white border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]">
                            <option value="">Semua Kelas</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Mulai Tanggal</label>
                        <input type="date" v-model="filterForm.start_date" class="w-full text-xs rounded-xl bg-white border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" v-model="filterForm.end_date" class="w-full text-xs rounded-xl bg-white border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]">
                    </div>
                    <div>
                        <button
                            @click="applyFilter"
                            class="w-full px-4 py-2 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm"
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
                                <th class="pb-3 text-left">NIS / Siswa</th>
                                <th class="pb-3 text-left">Kelas</th>
                                <th class="pb-3 text-center">Kejadian</th>
                                <th class="pb-3 text-center">Poin Pelanggaran</th>
                                <th class="pb-3 text-center">Poin Prestasi</th>
                                <th class="pb-3 text-center">Net Poin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4">
                                    <div class="font-bold text-gray-900">{{ student.name }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ student.nis }}</div>
                                </td>
                                <td class="py-4 text-xs font-semibold text-gray-700">
                                    {{ student.class_room ? student.class_room.name : '-' }}
                                </td>
                                <td class="py-4 text-center text-xs font-medium text-gray-600">{{ student.violation_count }}</td>
                                <td class="py-4 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-600">
                                        {{ student.violation_points }}
                                    </span>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-600">
                                        {{ student.achievement_points }}
                                    </span>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold" :class="student.net_points > 0 ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-700'">
                                        {{ student.net_points }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="students.length === 0">
                                <td colspan="6" class="py-12 text-center text-gray-500 text-xs">
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
