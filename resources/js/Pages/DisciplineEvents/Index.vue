<script setup lang="ts">
import Swal from 'sweetalert2';
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import { buildDisciplineEventWhatsAppMessage, buildWhatsAppUrl } from '../../utils';

const props = defineProps<{
    events: {
        data: Array<{
            id: number;
            event_date: string;
            event_type: string;
            points_effect: number;
            description?: string;
            follow_up?: string;
            attachment_path?: string;
            attachment_url?: string;
            original_recorder_name?: string;
            student: {
                id: number;
                name: string;
                nis: string;
                parent_name?: string;
                parent_phone?: string;
                class_room?: { id: number; name: string };
            } | null;
            violation: { id: number; name: string; code: string; category?: string } | null;
            achievement: { id: number; name: string; code: string; level?: string } | null;
            recorded_by: { id: number; name: string } | null;
            semester: { id: number; name: string; status?: string } | null;
        }>;
        links: any[];
        total?: number;
        from?: number;
        to?: number;
        current_page?: number;
        last_page?: number;
        per_page?: number;
    };
    classRooms?: Array<{
        id: number;
        name: string;
    }>;
    semesters?: Array<{
        id: number;
        name: string;
        status?: string;
    }>;
    filters: {
        search?: string;
        event_type?: string;
        class_id?: string | number;
        semester_id?: string | number;
        per_page?: string | number;
    };
}>();

const search = ref(props.filters.search || '');
const eventType = ref(props.filters.event_type || '');
const classId = ref(props.filters.class_id ? String(props.filters.class_id) : '');
const semesterId = ref(props.filters.semester_id ? String(props.filters.semester_id) : '');
const perPage = ref(props.filters.per_page ? String(props.filters.per_page) : '15');

const page = usePage();
const userRoles = computed<string[]>(() => (page.props.auth as any)?.roles || []);
const canDelete = computed(() => userRoles.value.includes('Super Admin') || userRoles.value.includes('Tata Tertib'));
const canCreate = computed(() => userRoles.value.some((r) => ['Super Admin', 'Tata Tertib', 'Teacher', 'BK'].includes(r)));

// Modal Detail State
const selectedEvent = ref<any | null>(null);
const isDetailModalOpen = ref(false);

const openDetailModal = (event: any) => {
    selectedEvent.value = event;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedEvent.value = null;
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
        '/discipline-events',
        {
            search: search.value || undefined,
            event_type: eventType.value || undefined,
            class_id: classId.value || undefined,
            semester_id: semesterId.value || undefined,
            per_page: perPage.value !== '15' ? perPage.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

watch(
    [search, eventType, classId, semesterId, perPage],
    debounce(() => {
        applyFilters();
    }, 300)
);

const resetFilters = () => {
    search.value = '';
    eventType.value = '';
    classId.value = '';
    semesterId.value = '';
    perPage.value = '15';
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return !!(search.value || eventType.value || classId.value || semesterId.value || perPage.value !== '15');
});

const deleteEvent = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Tindakan',
        text: 'Apakah Anda yakin ingin menghapus catatan kedisiplinan ini? Saldo poin siswa akan otomatis disesuaikan kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Catatan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/discipline-events/${id}`, {
                onSuccess: () => {
                    if (isDetailModalOpen.value) {
                        closeDetailModal();
                    }
                },
            });
        }
    });
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }).format(d);
};

const previewAttachment = (url: string) => {
    Swal.fire({
        title: 'Lampiran Bukti Foto',
        imageUrl: url,
        imageAlt: 'Bukti Foto Kejadian',
        imageWidth: 480,
        imageHeight: 'auto',
        showCloseButton: true,
        confirmButtonColor: '#001AFF',
        confirmButtonText: 'Tutup',
    });
};

const sendWhatsAppEvent = (item: any) => {
    const student = item.student;
    const phone = student?.parent_phone;
    const schoolName = (usePage().props.school as any)?.name || 'Sekolah';
    const eventName = item.event_type === 'violation' ? (item.violation?.name || 'Pelanggaran') : (item.achievement?.name || 'Prestasi');

    const message = buildDisciplineEventWhatsAppMessage({
        schoolName,
        studentName: student?.name || 'Siswa',
        studentNis: student?.nis,
        className: student?.class_room?.name,
        eventType: item.event_type,
        eventName,
        eventDate: formatDate(item.event_date),
        points: item.points_effect,
        description: item.description,
        recorderName: item.original_recorder_name || item.recorded_by?.name,
    });

    if (!phone) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(message);
        }
        Swal.fire({
            title: 'Nomor WA Belum Terdaftar',
            text: 'Nomor WhatsApp orang tua belum terdaftar di profil siswa. Draf pesan resmi telah disalin ke clipboard Anda.',
            icon: 'info',
            confirmButtonColor: '#001AFF',
            confirmButtonText: 'Tutup',
        });
        window.open(buildWhatsAppUrl('', message), '_blank');
        return;
    }

    window.open(buildWhatsAppUrl(phone, message), '_blank');
};
</script>

<template>
    <Head title="Kejadian Disiplin - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <!-- Breadcrumb Header Slot -->
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <span class="text-gray-900 font-semibold">Kejadian Disiplin</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            
            <!-- Module Switcher Tabs: Kedisiplinan -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-3">
                <Link
                    href="/discipline-events"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 bg-[#001AFF] text-white shadow-xs"
                >
                    <i class="pi pi-list text-[11px]"></i>
                    <span>Jurnal Catatan Kejadian</span>
                </Link>
                <Link
                    href="/discipline-recap"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                >
                    <i class="pi pi-chart-pie text-[11px]"></i>
                    <span>Rekapitulasi Poin</span>
                </Link>
            </div>

            <!-- Page Header (Matching Master Data pattern) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Kejadian Disiplin</h1>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-[#001AFF] border border-indigo-100">
                            Buku Jurnal Aktif
                        </span>
                    </div>
                    <p class="text-gray-500 text-[15px] mt-0.5">
                        Rekam jejak real-time pelanggaran tata tertib dan perolehan prestasi siswa secara komprehensif.
                    </p>
                </div>
                <div v-if="canCreate" class="flex items-center gap-2.5">
                    <Link
                        href="/discipline-events/create"
                        class="px-5 py-2.5 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Input Kejadian Baru</span>
                    </Link>
                </div>
            </div>

            <!-- Main DataTable Card (Matching Master Data) -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                
                <!-- Filter Toolbar -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-5">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Global Search Input -->
                        <div class="relative w-full sm:w-72">
                            <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama atau NIS siswa..."
                                class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                            />
                        </div>

                        <!-- Filter: Jenis Kejadian -->
                        <div class="w-full sm:w-44">
                            <select
                                v-model="eventType"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Semua Jenis Kejadian</option>
                                <option value="violation">Pelanggaran Saja</option>
                                <option value="achievement">Prestasi Saja</option>
                            </select>
                        </div>

                        <!-- Filter: Kelas / Rombel -->
                        <div v-if="classRooms && classRooms.length > 0" class="w-full sm:w-40">
                            <select
                                v-model="classId"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Semua Kelas</option>
                                <option v-for="cls in classRooms" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter: Semester -->
                        <div v-if="semesters && semesters.length > 0" class="w-full sm:w-40">
                            <select
                                v-model="semesterId"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Semua Semester</option>
                                <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
                                    {{ sem.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="resetFilters"
                            class="text-xs text-gray-500 hover:text-gray-900 underline px-2 py-1 cursor-pointer transition-colors"
                        >
                            Reset Filter
                        </button>
                    </div>

                    <!-- Right Info Counter -->
                    <div class="text-xs font-medium text-gray-400 shrink-0">
                        Menampilkan <span class="text-gray-900 font-bold">{{ events.from || (events.data.length > 0 ? 1 : 0) }}</span> sampai <span class="text-gray-900 font-bold">{{ events.to || events.data.length }}</span> dari <span class="text-gray-900 font-bold">{{ events.total || events.data.length }}</span> kejadian
                    </div>
                </div>

                <!-- PrimeVue DataTable Component -->
                <DataTable
                    :value="events.data"
                    responsiveLayout="stack"
                    breakpoint="768px"
                    class="p-datatable-sm"
                    removableSort
                    emptyMessage="Tidak ada catatan kedisiplinan yang sesuai dengan filter pencarian."
                    :pt="{
                        table: { class: 'w-full text-left border-collapse' },
                        thead: { class: 'bg-gray-50/70 border-b border-gray-200' },
                        headerRow: { class: 'border-b border-gray-200' },
                        row: { class: 'border-b border-gray-100 hover:bg-gray-50/80 transition-colors' }
                    }"
                >
                    <!-- Kolom 1: Tanggal -->
                    <Column field="event_date" header="Tanggal" sortable class="py-3.5 px-4 text-xs font-medium text-gray-700 w-32">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 text-xs">{{ formatDate(data.event_date) }}</span>
                                <span v-if="data.semester" class="text-[10px] text-gray-400 mt-0.5">{{ data.semester.name }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Kolom 2: Siswa & Kelas -->
                    <Column field="student.name" header="Siswa" sortable class="py-3.5 px-4 font-semibold text-gray-900 min-w-[200px]">
                        <template #body="{ data }">
                            <div v-if="data.student" class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100">
                                    {{ (data.student.name || 'S').charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <Link
                                        :href="`/students/${data.student.id}`"
                                        class="font-bold text-gray-900 hover:text-[#001AFF] transition-colors text-sm truncate block"
                                    >
                                        {{ data.student.name }}
                                    </Link>
                                    <div class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1.5">
                                        <span class="font-mono text-gray-500">{{ data.student.nis }}</span>
                                        <span>&bull;</span>
                                        <span class="inline-flex px-1.5 py-0.2 text-[10px] rounded bg-gray-100 text-gray-700 font-medium">
                                            {{ data.student.class_room?.name || 'Tanpa Kelas' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <span v-else class="text-gray-400 text-xs italic">Data Siswa Dihapus</span>
                        </template>
                    </Column>

                    <!-- Kolom 3: Jenis Kejadian -->
                    <Column field="event_type" header="Jenis" sortable class="py-3.5 px-4 w-32 text-center">
                        <template #body="{ data }">
                            <div class="text-center">
                                <span
                                    class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider border shadow-xs"
                                    :class="data.event_type === 'violation' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100'"
                                >
                                    {{ data.event_type === 'violation' ? 'Pelanggaran' : 'Prestasi' }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Kolom 4: Pelanggaran / Prestasi & Keterangan -->
                    <Column header="Kejadian & Deskripsi" class="py-3.5 px-4 min-w-[240px]">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1 items-start">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-gray-900 text-xs">
                                        {{ data.event_type === 'violation' ? data.violation?.name : data.achievement?.name }}
                                    </span>
                                    <span
                                        v-if="(data.event_type === 'violation' ? data.violation?.code : data.achievement?.code)"
                                        class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-semibold"
                                    >
                                        {{ data.event_type === 'violation' ? data.violation?.code : data.achievement?.code }}
                                    </span>
                                </div>

                                <p v-if="data.description" class="text-xs text-gray-600 line-clamp-1 mt-0.5">
                                    {{ data.description }}
                                </p>

                                <div class="flex items-center gap-2 mt-1">
                                    <!-- Foto Bukti Button -->
                                    <button
                                        v-if="data.attachment_url"
                                        type="button"
                                        @click="previewAttachment(data.attachment_url)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors cursor-pointer border border-indigo-100"
                                        title="Klik untuk melihat foto barang sitaan / dokumentasi"
                                    >
                                        <i class="pi pi-image text-[10px]"></i>
                                        <span>Bukti Foto</span>
                                    </button>

                                    <!-- Kategori Badge -->
                                    <span
                                        v-if="data.violation?.category"
                                        class="text-[10px] text-gray-500 font-medium"
                                    >
                                        Kategori: {{ data.violation.category }}
                                    </span>
                                    <span
                                        v-else-if="data.achievement?.level"
                                        class="text-[10px] text-gray-500 font-medium"
                                    >
                                        Tingkat: {{ data.achievement.level }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Kolom 5: Poin Effect -->
                    <Column field="points_effect" header="Poin" sortable class="py-3.5 px-4 text-center w-28">
                        <template #body="{ data }">
                            <div class="text-center">
                                <span
                                    class="inline-flex px-2.5 py-0.5 rounded text-xs font-bold shadow-xs"
                                    :class="data.event_type === 'violation' ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'"
                                >
                                    {{ data.event_type === 'violation' ? '+' : '-' }}{{ data.points_effect }} Poin
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Kolom 6: Petugas Pencatat -->
                    <Column field="recorded_by.name" header="Petugas" class="py-3.5 px-4 text-xs text-gray-600 w-36">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-900 text-xs">
                                    {{ data.original_recorder_name || data.recorded_by?.name || 'Petugas' }}
                                </span>
                                <span v-if="data.original_recorder_name && data.recorded_by?.name && data.original_recorder_name !== data.recorded_by?.name" class="text-[10px] text-gray-400">
                                    via {{ data.recorded_by.name }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Kolom 7: Aksi -->
                    <Column header="Aksi" class="py-3.5 px-4 text-center w-36">
                        <template #body="{ data }">
                            <div class="inline-flex items-center gap-1.5 justify-center w-full">
                                <!-- Detail Button -->
                                <button
                                    type="button"
                                    @click="openDetailModal(data)"
                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors cursor-pointer"
                                    title="Lihat Detail Kejadian Lengkap"
                                >
                                    <i class="pi pi-eye text-xs"></i>
                                </button>

                                <!-- WhatsApp Button -->
                                <button
                                    type="button"
                                    @click="sendWhatsAppEvent(data)"
                                    class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 flex items-center justify-center transition-colors cursor-pointer border border-emerald-100"
                                    title="Kirim Pemberitahuan WhatsApp ke Orang Tua"
                                >
                                    <i class="pi pi-whatsapp text-xs"></i>
                                </button>

                                <!-- Delete Button -->
                                <button
                                    v-if="canDelete"
                                    type="button"
                                    @click="deleteEvent(data.id)"
                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors cursor-pointer border border-red-100"
                                    title="Hapus Catatan Kejadian"
                                >
                                    <i class="pi pi-trash text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination Footer (Matching Master Data) -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-gray-500 border-t border-gray-100 pt-5">
                    <div class="flex items-center gap-3">
                        <span>
                            Menampilkan <span class="font-bold text-gray-900">{{ events.from || (events.data.length > 0 ? 1 : 0) }}</span> sampai <span class="font-bold text-gray-900">{{ events.to || events.data.length }}</span> dari <span class="font-bold text-gray-900">{{ events.total || events.data.length }}</span> data kejadian.
                        </span>

                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-1.5 text-xs text-gray-600 pl-2 border-l border-gray-200">
                            <span>Baris per halaman:</span>
                            <select
                                v-model="perPage"
                                class="bg-gray-50 border border-gray-200 rounded-md text-xs font-semibold py-1 px-2 text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <!-- Inertia Pagination Links -->
                    <div v-if="events.links && events.links.length > 3" class="flex flex-wrap items-center justify-center gap-1">
                        <Link
                            v-for="(link, index) in events.links"
                            :key="index"
                            :href="link.url || '#'"
                            class="min-w-[32px] h-8 flex items-center justify-center px-2.5 text-xs font-semibold rounded-lg transition-all"
                            :class="[
                                link.active
                                    ? 'bg-[#001AFF] text-white shadow-xs'
                                    : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-40 cursor-not-allowed hover:bg-transparent'
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                    </div>
                </div>

            </div>

            <!-- Modal Detail Kejadian Lengkap -->
            <Dialog
                v-model:visible="isDetailModalOpen"
                modal
                header="Rincian Catatan Kedisiplinan"
                :style="{ width: '560px' }"
                :pt="{
                    root: { class: 'rounded-2xl border border-gray-100 shadow-xl' },
                    header: { class: 'border-b border-gray-100 pb-4' },
                    content: { class: 'p-6 space-y-5' }
                }"
            >
                <div v-if="selectedEvent" class="space-y-5">
                    <!-- Header Event Summary -->
                    <div class="flex items-start justify-between gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shrink-0 shadow-xs"
                                :class="selectedEvent.event_type === 'violation' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                            >
                                <i :class="selectedEvent.event_type === 'violation' ? 'pi pi-exclamation-triangle' : 'pi pi-star'"></i>
                            </div>
                            <div>
                                <span
                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mb-1"
                                    :class="selectedEvent.event_type === 'violation' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800'"
                                >
                                    {{ selectedEvent.event_type === 'violation' ? 'Pelanggaran Disiplin' : 'Pencapaian Prestasi' }}
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 leading-snug">
                                    {{ selectedEvent.event_type === 'violation' ? selectedEvent.violation?.name : selectedEvent.achievement?.name }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Tanggal: <strong>{{ formatDate(selectedEvent.event_date) }}</strong>
                                    <span v-if="selectedEvent.semester"> &bull; {{ selectedEvent.semester.name }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span
                                class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold shadow-xs"
                                :class="selectedEvent.event_type === 'violation' ? 'bg-red-600 text-white' : 'bg-emerald-600 text-white'"
                            >
                                {{ selectedEvent.event_type === 'violation' ? '+' : '-' }}{{ selectedEvent.points_effect }} Poin
                            </span>
                        </div>
                    </div>

                    <!-- Student Info Details -->
                    <div class="bg-white rounded-xl p-4 border border-gray-100 space-y-3 text-xs">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">Nama Siswa</span>
                            <span class="font-bold text-gray-900">{{ selectedEvent.student?.name || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">NIS</span>
                            <span class="font-mono text-gray-700 font-semibold">{{ selectedEvent.student?.nis || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">Kelas / Rombel</span>
                            <span class="font-semibold text-gray-900">{{ selectedEvent.student?.class_room?.name || 'Tanpa Kelas' }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">Orang Tua / Wali</span>
                            <span class="font-semibold text-gray-900">{{ selectedEvent.student?.parent_name || 'Belum Dicantumkan' }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">No. Kontak WhatsApp</span>
                            <span class="font-mono text-emerald-600 font-semibold flex items-center gap-1">
                                <i v-if="selectedEvent.student?.parent_phone" class="pi pi-whatsapp"></i>
                                <span>{{ selectedEvent.student?.parent_phone || 'Tidak Ada Nomor' }}</span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Dicatat Oleh</span>
                            <span class="font-semibold text-gray-800">
                                {{ selectedEvent.original_recorder_name || selectedEvent.recorded_by?.name || 'Petugas' }}
                            </span>
                        </div>
                    </div>

                    <!-- Keterangan Kejadian -->
                    <div v-if="selectedEvent.description" class="p-3.5 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-700 space-y-1">
                        <span class="font-bold text-gray-900 block text-[11px] uppercase tracking-wider">Keterangan / Kronologi:</span>
                        <p class="leading-relaxed">{{ selectedEvent.description }}</p>
                    </div>

                    <!-- Tindak Lanjut bila ada -->
                    <div v-if="selectedEvent.follow_up" class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900 space-y-1">
                        <span class="font-bold text-amber-950 block text-[11px] uppercase tracking-wider">Tindak Lanjut yang Dilakukan:</span>
                        <p class="leading-relaxed">{{ selectedEvent.follow_up }}</p>
                    </div>

                    <!-- Lampiran Bukti Foto -->
                    <div v-if="selectedEvent.attachment_url" class="space-y-2">
                        <span class="font-bold text-gray-900 block text-xs">Lampiran Bukti Foto:</span>
                        <div class="rounded-xl overflow-hidden border border-gray-200 bg-black/5 max-h-64 flex items-center justify-center cursor-pointer group" @click="previewAttachment(selectedEvent.attachment_url)">
                            <img
                                :src="selectedEvent.attachment_url"
                                alt="Foto Bukti Kejadian"
                                class="object-cover w-full h-48 group-hover:scale-102 transition-transform"
                            />
                        </div>
                    </div>

                    <!-- Dialog Footer Actions -->
                    <div class="pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                        <button
                            v-if="canDelete"
                            type="button"
                            @click="deleteEvent(selectedEvent.id)"
                            class="px-4 py-2 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors border border-red-100 flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-trash text-xs"></i>
                            <span>Hapus Catatan</span>
                        </button>
                        <div class="flex items-center gap-2 ml-auto">
                            <button
                                type="button"
                                @click="sendWhatsAppEvent(selectedEvent)"
                                class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors shadow-xs flex items-center gap-1.5 cursor-pointer"
                            >
                                <i class="pi pi-whatsapp text-xs"></i>
                                <span>Kirim Notifikasi WA</span>
                            </button>
                            <button
                                type="button"
                                @click="closeDetailModal"
                                class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </Dialog>

        </div>
    </AuthenticatedLayout>
</template>
