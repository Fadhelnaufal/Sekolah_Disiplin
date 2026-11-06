<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "../../Layouts/AuthenticatedLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";

interface StudentRecap {
    id: number;
    nis: string;
    name: string;
    class_room?: { id: number; name: string };
    violation_count: number;
    violation_points: number;
    achievement_count: number;
    achievement_points: number;
    net_points: number;
    discipline_status?: string;
    discipline_status_label?: string;
}

const props = defineProps<{
    students: Array<StudentRecap>;
    summary?: {
        total_students: number;
        total_violations: number;
        total_violation_points: number;
        total_achievements: number;
        total_achievement_points: number;
        total_net_points: number;
        need_guidance_count: number;
        safe_count: number;
        warning_count: number;
        critical_count: number;
    };
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
        status?: string;
    };
    school?: { name: string };
}>();

// State filters
const semesterId = ref(
    props.filters.semester_id ? String(props.filters.semester_id) : (props.current_semester ? String(props.current_semester.id) : "")
);
const classId = ref(props.filters.class_id ? String(props.filters.class_id) : "");
const startDate = ref(props.filters.start_date || "");
const endDate = ref(props.filters.end_date || "");
const search = ref(props.filters.search || "");
const minPoints = ref(props.filters.min_points ? String(props.filters.min_points) : "");
const status = ref(props.filters.status ? String(props.filters.status) : "");
const showAdvancedDate = ref(Boolean(props.filters.start_date || props.filters.end_date));

// Client-side pagination setting
const rowsPerPage = ref(15);

// Quick Detail Modal
const selectedStudent = ref<StudentRecap | null>(null);
const isDetailModalOpen = ref(false);

const openDetailModal = (student: StudentRecap) => {
    selectedStudent.value = student;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedStudent.value = null;
};

function debounce(fn: Function, delay: number) {
    let timeoutId: ReturnType<typeof setTimeout>;
    return (...args: any[]) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
}

const applyFilters = () => {
    router.get(
        "/discipline-recap",
        {
            semester_id: semesterId.value || undefined,
            class_id: classId.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
            search: search.value || undefined,
            min_points: minPoints.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        }
    );
};

const debouncedSearch = debounce(() => {
    applyFilters();
}, 400);

watch(search, () => {
    debouncedSearch();
});

const onFilterChange = () => {
    applyFilters();
};

const resetFilters = () => {
    semesterId.value = props.current_semester ? String(props.current_semester.id) : "";
    classId.value = "";
    startDate.value = "";
    endDate.value = "";
    search.value = "";
    minPoints.value = "";
    status.value = "";
    showAdvancedDate.value = false;
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return Boolean(
        classId.value ||
        startDate.value ||
        endDate.value ||
        search.value ||
        minPoints.value ||
        status.value ||
        (props.current_semester && semesterId.value !== String(props.current_semester.id))
    );
});

// Client search fallback if user wants instant reactivity
const filteredStudents = computed(() => {
    return props.students;
});

const exportExcel = () => {
    const params = new URLSearchParams({
        semester_id: String(semesterId.value || ""),
        class_id: String(classId.value || ""),
        start_date: String(startDate.value || ""),
        end_date: String(endDate.value || ""),
        search: String(search.value || ""),
        min_points: String(minPoints.value || ""),
        status: String(status.value || ""),
        export: "excel",
    });
    window.open(`/discipline-recap?${params.toString()}`, "_blank");
};

const exportPdf = () => {
    const params = new URLSearchParams({
        semester_id: String(semesterId.value || ""),
        class_id: String(classId.value || ""),
        start_date: String(startDate.value || ""),
        end_date: String(endDate.value || ""),
        search: String(search.value || ""),
        min_points: String(minPoints.value || ""),
        status: String(status.value || ""),
        export: "pdf",
    });
    window.open(`/discipline-recap?${params.toString()}`, "_blank");
};

const getStatusBadge = (student: StudentRecap) => {
    const net = student.net_points ?? 0;
    if (net >= 50) {
        return {
            label: "Kritis",
            class: "bg-red-50 text-red-700 border border-red-200",
            dot: "bg-red-500",
            desc: "Panggilan Orang Tua / Sanksi Berat",
        };
    }
    if (net >= 25) {
        return {
            label: "Waspada",
            class: "bg-orange-50 text-orange-700 border border-orange-200",
            dot: "bg-orange-500",
            desc: "Surat Peringatan ke Orang Tua",
        };
    }
    if (net > 0) {
        return {
            label: "Perhatian",
            class: "bg-amber-50 text-amber-700 border border-amber-200",
            dot: "bg-amber-500",
            desc: "Pembinaan Wali Kelas & BK",
        };
    }
    return {
        label: "Aman",
        class: "bg-emerald-50 text-emerald-700 border border-emerald-200",
        dot: "bg-emerald-500",
        desc: "Catatan Disiplin Baik",
    };
};
</script>

<template>
    <Head title="Rekapitulasi Disiplin - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <!-- Breadcrumb Header Slot -->
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/discipline-events" class="text-gray-500 hover:text-gray-900 transition-colors">
                    Catatan Kedisiplinan
                </Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Rekapitulasi Disiplin</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Module Switcher Tabs: Kedisiplinan -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-3">
                <Link
                    href="/discipline-events"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                >
                    <i class="pi pi-list text-[11px]"></i>
                    <span>Jurnal Catatan Kejadian</span>
                </Link>
                <Link
                    href="/discipline-recap"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 bg-[#001AFF] text-white shadow-xs"
                >
                    <i class="pi pi-chart-pie text-[11px]"></i>
                    <span>Rekapitulasi Poin</span>
                </Link>
            </div>

            <!-- Page Header (Matching Master Data pattern) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">
                            Rekapitulasi Disiplin
                        </h1>
                        <span
                            v-if="current_semester"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-[#001AFF] border border-indigo-100"
                        >
                            <i class="pi pi-calendar mr-1.5 text-[10px]"></i>
                            Semester {{ current_semester.name }} ({{ current_semester.academic_year?.name || 'Aktif' }})
                        </span>
                    </div>
                    <p class="text-gray-500 text-[15px] mt-0.5">
                        Rekapitulasi komprehensif akumulasi poin pelanggaran, prestasi, dan evaluasi status kedisiplinan siswa.
                    </p>
                </div>

                <!-- Export Actions -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <button
                        @click="exportExcel"
                        class="px-4 py-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-xs font-semibold text-gray-700 transition-all flex items-center gap-2 shadow-xs cursor-pointer"
                        title="Ekspor data rekapitulasi ke format Excel"
                    >
                        <i class="pi pi-file-excel text-emerald-600 text-xs"></i>
                        <span>Ekspor Excel</span>
                    </button>
                    <button
                        @click="exportPdf"
                        class="px-4 py-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-xs font-semibold text-gray-700 transition-all flex items-center gap-2 shadow-xs cursor-pointer"
                        title="Unduh laporan rekapitulasi format cetak PDF"
                    >
                        <i class="pi pi-file-pdf text-rose-600 text-xs"></i>
                        <span>Unduh PDF</span>
                    </button>
                </div>
            </div>

            <!-- Summary Statistics Cards (4 Columns) -->
            <div v-if="summary" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Siswa -->
                <div class="bg-white rounded-[24px] p-5 sm:p-6 shadow-[0_2px_20px_#00000006] border border-gray-100/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">
                            Total Siswa Terdata
                        </span>
                        <div class="text-2xl font-bold text-gray-900 mt-1">
                            {{ summary.total_students }}
                            <span class="text-xs font-normal text-gray-500">siswa</span>
                        </div>
                        <span class="text-[11px] text-emerald-600 font-medium mt-1 inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            {{ summary.safe_count }} siswa berstatus aman
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-[#6366F1] flex items-center justify-center shrink-0">
                        <i class="pi pi-users text-lg"></i>
                    </div>
                </div>

                <!-- Card 2: Pelanggaran -->
                <div class="bg-white rounded-[24px] p-5 sm:p-6 shadow-[0_2px_20px_#00000006] border border-gray-100/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">
                            Total Pelanggaran
                        </span>
                        <div class="text-2xl font-bold text-rose-600 mt-1">
                            {{ summary.total_violations }}
                            <span class="text-xs font-normal text-gray-500">kejadian</span>
                        </div>
                        <span class="text-[11px] text-gray-500 font-medium mt-1 block">
                            Akumulasi: <strong class="text-gray-900">{{ summary.total_violation_points }}</strong> poin
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <i class="pi pi-exclamation-triangle text-lg"></i>
                    </div>
                </div>

                <!-- Card 3: Prestasi -->
                <div class="bg-white rounded-[24px] p-5 sm:p-6 shadow-[0_2px_20px_#00000006] border border-gray-100/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">
                            Total Prestasi
                        </span>
                        <div class="text-2xl font-bold text-emerald-600 mt-1">
                            {{ summary.total_achievements }}
                            <span class="text-xs font-normal text-gray-500">pencapaian</span>
                        </div>
                        <span class="text-[11px] text-gray-500 font-medium mt-1 block">
                            Apresiasi: <strong class="text-gray-900">{{ summary.total_achievement_points }}</strong> poin deduksi
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <i class="pi pi-star-fill text-lg"></i>
                    </div>
                </div>

                <!-- Card 4: Perlu Pembinaan -->
                <div class="bg-white rounded-[24px] p-5 sm:p-6 shadow-[0_2px_20px_#00000006] border border-gray-100/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">
                            Perlu Pembinaan
                        </span>
                        <div class="text-2xl font-bold text-amber-600 mt-1">
                            {{ summary.need_guidance_count }}
                            <span class="text-xs font-normal text-gray-500">siswa</span>
                        </div>
                        <span class="text-[11px] text-red-600 font-medium mt-1 inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            {{ summary.critical_count }} siswa status kritis (&ge; 50 poin)
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="pi pi-shield text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Main DataTable Card (Matching Master Data) -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                <!-- Filter Toolbar -->
                <div class="space-y-3 border-b border-gray-100 pb-5">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Global Search Input -->
                            <div class="relative w-full sm:w-64 md:w-72">
                                <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari nama atau NIS siswa..."
                                    class="w-full pl-9 pr-8 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                />
                                <button
                                    v-if="search"
                                    @click="search = ''; onFilterChange();"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    title="Bersihkan pencarian"
                                >
                                    <i class="pi pi-times text-[10px]"></i>
                                </button>
                            </div>

                            <!-- Semester Filter -->
                            <div class="w-full sm:w-48">
                                <select
                                    v-model="semesterId"
                                    @change="onFilterChange"
                                    class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                                >
                                    <option v-for="sem in semesters" :key="sem.id" :value="String(sem.id)">
                                        {{ sem.name }} ({{ sem.academic_year?.name || 'Tahun Ajaran' }})
                                    </option>
                                </select>
                            </div>

                            <!-- Class Filter -->
                            <div class="w-full sm:w-44">
                                <select
                                    v-model="classId"
                                    @change="onFilterChange"
                                    class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                                >
                                    <option value="">Semua Kelas</option>
                                    <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                                        {{ c.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="w-full sm:w-40">
                                <select
                                    v-model="status"
                                    @change="onFilterChange"
                                    class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="SAFE">Aman (0 Poin)</option>
                                    <option value="ATTENTION">Perhatian (1-24 Poin)</option>
                                    <option value="WARNING">Waspada (25-49 Poin)</option>
                                    <option value="CRITICAL">Kritis (&ge; 50 Poin)</option>
                                </select>
                            </div>

                            <!-- Min Points Filter -->
                            <div class="w-full sm:w-36">
                                <input
                                    v-model="minPoints"
                                    @change="onFilterChange"
                                    type="number"
                                    min="0"
                                    placeholder="Min. Poin..."
                                    class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                />
                            </div>

                            <!-- Advanced Date Toggle -->
                            <button
                                type="button"
                                @click="showAdvancedDate = !showAdvancedDate"
                                class="px-3 py-2 rounded-full border text-xs font-medium transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="showAdvancedDate || startDate || endDate ? 'border-indigo-200 bg-indigo-50/70 text-[#001AFF]' : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100'"
                            >
                                <i class="pi pi-calendar text-[11px]"></i>
                                <span>Periode Tanggal</span>
                            </button>

                            <!-- Reset Button -->
                            <button
                                v-if="hasActiveFilters"
                                @click="resetFilters"
                                class="text-xs text-gray-500 hover:text-gray-900 underline px-2 py-1 cursor-pointer transition-colors"
                            >
                                Reset Filter
                            </button>
                        </div>

                        <!-- Data Counter -->
                        <div class="text-xs font-medium text-gray-400 whitespace-nowrap">
                            Menampilkan <span class="text-gray-900 font-bold">{{ filteredStudents.length }}</span> data siswa
                        </div>
                    </div>

                    <!-- Collapsible Date Range Bar -->
                    <div
                        v-if="showAdvancedDate"
                        class="p-4 rounded-2xl bg-gray-50/80 border border-gray-100 flex flex-wrap items-center gap-4 text-xs"
                    >
                        <div class="flex items-center gap-2">
                            <label class="font-medium text-gray-600">Dari Tanggal:</label>
                            <input
                                v-model="startDate"
                                @change="onFilterChange"
                                type="date"
                                class="px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-gray-800 text-xs focus:ring-2 focus:ring-[#6366F1] focus:outline-none"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="font-medium text-gray-600">Sampai Tanggal:</label>
                            <input
                                v-model="endDate"
                                @change="onFilterChange"
                                type="date"
                                class="px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-gray-800 text-xs focus:ring-2 focus:ring-[#6366F1] focus:outline-none"
                            />
                        </div>
                        <button
                            v-if="startDate || endDate"
                            @click="startDate = ''; endDate = ''; onFilterChange();"
                            class="text-xs text-rose-600 hover:text-rose-800 cursor-pointer"
                        >
                            Hapus Rentang Tanggal
                        </button>
                    </div>
                </div>

                <!-- PrimeVue DataTable Component -->
                <DataTable
                    :value="filteredStudents"
                    :paginator="filteredStudents.length > 0"
                    :rows="rowsPerPage"
                    :rowsPerPageOptions="[10, 15, 25, 50]"
                    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} data siswa"
                    responsiveLayout="stack"
                    breakpoint="768px"
                    class="p-datatable-sm"
                    removableSort
                    emptyMessage="Tidak ada data siswa yang sesuai dengan filter pencarian."
                >
                    <!-- Column 1: Nomor Urut -->
                    <Column header="No" class="w-14 text-center py-3.5 pl-2 text-xs font-mono text-gray-400">
                        <template #body="slotProps">
                            {{ slotProps.index + 1 }}
                        </template>
                    </Column>

                    <!-- Column 2: Siswa & NIS -->
                    <Column field="name" header="Siswa & NIS" sortable class="py-3.5 min-w-[200px]">
                        <template #body="slotProps">
                            <div class="flex flex-col">
                                <Link
                                    :href="`/students/${slotProps.data.id}`"
                                    class="text-xs font-bold text-gray-900 hover:text-[#001AFF] transition-colors leading-tight"
                                >
                                    {{ slotProps.data.name }}
                                </Link>
                                <span class="font-mono text-[11px] text-gray-400 mt-0.5">
                                    NIS: {{ slotProps.data.nis }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Column 3: Kelas -->
                    <Column field="class_room.name" header="Kelas" sortable class="py-3.5 w-32">
                        <template #body="slotProps">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ slotProps.data.class_room?.name || 'Belum Ada Kelas' }}
                            </span>
                        </template>
                    </Column>

                    <!-- Column 4: Pelanggaran -->
                    <Column field="violation_points" header="Pelanggaran" sortable class="py-3.5 text-center w-36">
                        <template #body="slotProps">
                            <div class="inline-flex flex-col items-center">
                                <span class="text-xs font-bold text-rose-600">
                                    {{ slotProps.data.violation_points }} Poin
                                </span>
                                <span class="text-[10px] text-gray-400">
                                    {{ slotProps.data.violation_count }}x kejadian
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Column 5: Prestasi -->
                    <Column field="achievement_points" header="Prestasi" sortable class="py-3.5 text-center w-36">
                        <template #body="slotProps">
                            <div class="inline-flex flex-col items-center">
                                <span class="text-xs font-bold text-emerald-600">
                                    {{ Math.abs(slotProps.data.achievement_points) }} Poin
                                </span>
                                <span class="text-[10px] text-gray-400">
                                    {{ slotProps.data.achievement_count }}x pencapaian
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Column 6: Poin Bersih -->
                    <Column field="net_points" header="Poin Akhir" sortable class="py-3.5 text-center w-32">
                        <template #body="slotProps">
                            <span
                                class="inline-flex px-3 py-1 rounded-full text-xs font-bold font-mono"
                                :class="slotProps.data.net_points > 0 ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-gray-100 text-gray-700'"
                            >
                                {{ slotProps.data.net_points }}
                            </span>
                        </template>
                    </Column>

                    <!-- Column 7: Status Disiplin -->
                    <Column field="discipline_status" header="Status Disiplin" sortable class="py-3.5 text-center w-40">
                        <template #body="slotProps">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" :class="getStatusBadge(slotProps.data).class">
                                <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(slotProps.data).dot"></span>
                                <span>{{ getStatusBadge(slotProps.data).label }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Column 8: Aksi -->
                    <Column header="Aksi" class="py-3.5 text-center w-36 pr-2">
                        <template #body="slotProps">
                            <div class="inline-flex items-center gap-1.5 justify-center">
                                <!-- Quick Detail Modal Button -->
                                <button
                                    @click="openDetailModal(slotProps.data)"
                                    type="button"
                                    title="Lihat Ringkasan Siswa"
                                    class="w-7 h-7 rounded-full bg-gray-100 hover:bg-indigo-50 text-gray-600 hover:text-[#001AFF] flex items-center justify-center transition-colors cursor-pointer"
                                >
                                    <i class="pi pi-eye text-[11px]"></i>
                                </button>

                                <!-- Cetak Kartu Disiplin -->
                                <Link
                                    :href="`/students/${slotProps.data.id}/print-card`"
                                    title="Cetak Kartu Disiplin"
                                    class="w-7 h-7 rounded-full bg-gray-100 hover:bg-emerald-50 text-gray-600 hover:text-emerald-700 flex items-center justify-center transition-colors"
                                >
                                    <i class="pi pi-print text-[11px]"></i>
                                </Link>

                                <!-- Buka Profil Siswa -->
                                <Link
                                    :href="`/students/${slotProps.data.id}`"
                                    title="Buka Profil & Riwayat Lengkap"
                                    class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 flex items-center justify-center transition-colors"
                                >
                                    <i class="pi pi-arrow-right text-[11px]"></i>
                                </Link>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Quick Detail Modal (PrimeVue Dialog) -->
        <Dialog
            v-model:visible="isDetailModalOpen"
            modal
            :dismissableMask="true"
            header="Ringkasan Rekapitulasi Siswa"
            :style="{ width: '560px' }"
            :breakpoints="{ '960px': '75vw', '640px': '92vw' }"
        >
            <div v-if="selectedStudent" class="space-y-5 pt-2 text-xs">
                <!-- Student Header Banner -->
                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ selectedStudent.name }}</h3>
                        <div class="flex items-center gap-3 text-gray-500 mt-1 font-mono text-[11px]">
                            <span>NIS: {{ selectedStudent.nis }}</span>
                            <span>&bull;</span>
                            <span>Kelas: {{ selectedStudent.class_room?.name || 'Belum Ada Kelas' }}</span>
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full font-semibold" :class="getStatusBadge(selectedStudent).class">
                        <span class="w-2 h-2 rounded-full" :class="getStatusBadge(selectedStudent).dot"></span>
                        <span>{{ getStatusBadge(selectedStudent).label }}</span>
                    </div>
                </div>

                <!-- Aggregation Metrics Grid -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-xl bg-rose-50/60 border border-rose-100 text-center">
                        <span class="text-[10px] font-semibold text-rose-700 uppercase tracking-wider block">Pelanggaran</span>
                        <div class="text-lg font-bold text-rose-600 mt-0.5">{{ selectedStudent.violation_points }} Poin</div>
                        <span class="text-[10px] text-gray-500">{{ selectedStudent.violation_count }} kejadian</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100 text-center">
                        <span class="text-[10px] font-semibold text-emerald-700 uppercase tracking-wider block">Prestasi</span>
                        <div class="text-lg font-bold text-emerald-600 mt-0.5">{{ Math.abs(selectedStudent.achievement_points) }} Poin</div>
                        <span class="text-[10px] text-gray-500">{{ selectedStudent.achievement_count }} prestasi</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-gray-100 border border-gray-200 text-center">
                        <span class="text-[10px] font-semibold text-gray-700 uppercase tracking-wider block">Poin Akhir</span>
                        <div class="text-lg font-bold text-gray-900 mt-0.5">{{ selectedStudent.net_points }}</div>
                        <span class="text-[10px] text-gray-500">Poin Bersih</span>
                    </div>
                </div>

                <!-- Recommended Action Info -->
                <div class="p-3.5 rounded-xl bg-indigo-50/60 border border-indigo-100 flex items-start gap-3">
                    <i class="pi pi-info-circle text-[#001AFF] text-sm mt-0.5"></i>
                    <div>
                        <div class="font-bold text-gray-900">Rekomendasi Tindak Lanjut:</div>
                        <p class="text-gray-600 mt-0.5 leading-relaxed">
                            {{ getStatusBadge(selectedStudent).desc }}.
                            Perhitungan poin bersih merupakan akumulasi poin pelanggaran dikurangi deduksi poin prestasi yang sah pada semester ini.
                        </p>
                    </div>
                </div>

                <!-- Dialog Actions -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                    <button
                        @click="closeDetailModal"
                        type="button"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                    <Link
                        :href="`/students/${selectedStudent.id}/print-card`"
                        class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-xs font-semibold text-gray-700 transition-colors flex items-center gap-1.5"
                    >
                        <i class="pi pi-print text-xs"></i>
                        <span>Cetak Kartu</span>
                    </Link>
                    <Link
                        :href="`/students/${selectedStudent.id}`"
                        class="px-4 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs"
                    >
                        <span>Lihat Profil Lengkap</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                </div>
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>
