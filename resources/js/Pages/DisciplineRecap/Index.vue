<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    students: Array<{
        id: number;
        nis: string;
        name: string;
        class_room?: { name: string };
        violation_count: number;
        violation_points: number;
        achievement_count: number;
        achievement_points: number;
        net_points: number;
    }>;
    classes: Array<{ id: number; name: string }>;
    semesters: Array<{ id: number; name: string; academic_year?: { name: string } }>;
    current_semester: { id: number; name: string; academic_year?: { name: string } } | null;
    filters: {
        semester_id?: string | number;
        class_id?: string | number;
        start_date?: string;
        end_date?: string;
        search?: string;
        min_points?: string | number;
    };
    school?: { name: string };
}>();

const semesterId = ref(props.filters.semester_id || (props.current_semester ? props.current_semester.id : ''));
const classId = ref(props.filters.class_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const search = ref(props.filters.search || '');
const minPoints = ref(props.filters.min_points || '');

const applyFilters = () => {
    router.get('/discipline-recap', {
        semester_id: semesterId.value || undefined,
        class_id: classId.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
        search: search.value || undefined,
        min_points: minPoints.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    semesterId.value = props.current_semester ? props.current_semester.id : '';
    classId.value = '';
    startDate.value = '';
    endDate.value = '';
    search.value = '';
    minPoints.value = '';
    applyFilters();
};

const exportExcel = () => {
    const params = new URLSearchParams({
        semester_id: String(semesterId.value || ''),
        class_id: String(classId.value || ''),
        start_date: String(startDate.value || ''),
        end_date: String(endDate.value || ''),
        search: String(search.value || ''),
        min_points: String(minPoints.value || ''),
        export: 'excel',
    });
    window.open(`/discipline-recap?${params.toString()}`, '_blank');
};

const exportPdf = () => {
    const params = new URLSearchParams({
        semester_id: String(semesterId.value || ''),
        class_id: String(classId.value || ''),
        start_date: String(startDate.value || ''),
        end_date: String(endDate.value || ''),
        search: String(search.value || ''),
        min_points: String(minPoints.value || ''),
        export: 'pdf',
    });
    window.open(`/discipline-recap?${params.toString()}`, '_blank');
};
</script>

<template>
    <Head title="Rekap Disiplin - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Rekap Disiplin</h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">
                        Rekapitulasi komprehensif poin pelanggaran, prestasi, dan poin bersih per siswa.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <button
                        @click="exportExcel"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-1.5 shadow-sm"
                    >
                        <i class="pi pi-file-excel text-green-600 text-[11px]"></i>
                        Ekspor Excel
                    </button>
                    <button
                        @click="exportPdf"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-1.5 shadow-sm"
                    >
                        <i class="pi pi-file-pdf text-red-600 text-[11px]"></i>
                        Unduh PDF
                    </button>
                </div>
            </div>

            <!-- Filters Bar Card -->
            <div class="bg-white rounded-[24px] p-6 shadow-[0_2px_20px_#00000006] space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <span class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="pi pi-filter text-[#6366F1]"></i>
                        Filter Rekapitulasi
                    </span>
                    <button
                        @click="resetFilters"
                        class="text-xs text-gray-400 hover:text-gray-700 transition-colors"
                    >
                        Reset Filter
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 pt-1">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Semester</label>
                        <select
                            v-model="semesterId"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                            <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
                                {{ sem.name }} ({{ sem.academic_year?.name }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Kelas</label>
                        <select
                            v-model="classId"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                            <option value="">Semua Kelas</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Dari Tanggal</label>
                        <input
                            v-model="startDate"
                            @change="applyFilters"
                            type="date"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Sampai Tanggal</label>
                        <input
                            v-model="endDate"
                            @change="applyFilters"
                            type="date"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Min. Poin Bersih</label>
                        <input
                            v-model="minPoints"
                            @change="applyFilters"
                            type="number"
                            min="0"
                            placeholder="Contoh: 20"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Cari Siswa / NIS</label>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Ketik & Enter..."
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Daftar Rekap Siswa</h2>
                        <span class="text-xs text-gray-400 mt-0.5 block">
                            Semester: {{ current_semester ? `${current_semester.name} (${current_semester.academic_year?.name})` : '-' }}
                        </span>
                    </div>
                    <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full">
                        {{ students.length }} siswa terdata
                    </span>
                </div>

                <div v-if="students.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="pb-3 pl-2">NIS</th>
                                <th class="pb-3">Nama Siswa</th>
                                <th class="pb-3">Kelas</th>
                                <th class="pb-3 text-center">Pelanggaran</th>
                                <th class="pb-3 text-center">Prestasi</th>
                                <th class="pb-3 text-center">Poin Bersih</th>
                                <th class="pb-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4 pl-2 font-mono text-xs text-gray-600">
                                    {{ student.nis }}
                                </td>
                                <td class="py-4 font-bold text-gray-900">
                                    <Link :href="`/students/${student.id}`" class="hover:text-[#6366F1] transition-colors">
                                        {{ student.name }}
                                    </Link>
                                </td>
                                <td class="py-4 text-xs font-medium text-gray-700">
                                    {{ student.class_room?.name || '-' }}
                                </td>
                                <td class="py-4 text-center">
                                    <span class="font-bold text-red-600">{{ student.violation_points }}</span>
                                    <span class="text-xs text-gray-400 ml-1">({{ student.violation_count }}x)</span>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="font-bold text-green-600">{{ Math.abs(student.achievement_points) }}</span>
                                    <span class="text-xs text-gray-400 ml-1">({{ student.achievement_count }}x)</span>
                                </td>
                                <td class="py-4 text-center">
                                    <span
                                        class="inline-flex px-3 py-1 rounded-full text-xs font-bold"
                                        :class="student.net_points > 0 ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-700'"
                                    >
                                        {{ student.net_points }}
                                    </span>
                                </td>
                                <td class="py-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            :href="`/students/${student.id}/print-card`"
                                            title="Cetak Kartu Disiplin"
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors"
                                        >
                                            <i class="pi pi-print text-[11px]"></i>
                                        </Link>
                                        <Link
                                            :href="`/students/${student.id}`"
                                            title="Lihat Detail Riwayat"
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors"
                                        >
                                            <i class="pi pi-arrow-right text-[11px]"></i>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-16 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 text-[#6366F1] flex items-center justify-center shadow-sm">
                        <i class="pi pi-chart-bar text-2xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Tidak ada data rekapitulasi</h3>
                    <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                        Tidak ditemukan siswa yang memenuhi kriteria filter atau belum ada pencatatan kejadian pada semester ini.
                    </p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
