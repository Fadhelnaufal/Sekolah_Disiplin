<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    student: {
        id: number;
        nis: string;
        name: string;
        parent_name: string | null;
        parent_phone: string | null;
        status: string;
        class_room: { id: number; name: string } | null;
    };
    semesters: Array<{ id: number; name: string; status: string }>;
    selectedSemesterId: number | null;
    summary: {
        violation_points: number;
        achievement_points: number;
        net_points: number;
        risk_status: string;
    };
    events: {
        data: Array<{
            id: number;
            event_date: string;
            event_type: string;
            points_effect: number;
            description: string | null;
            follow_up: string | null;
            violation: { name: string; code: string } | null;
            achievement: { name: string; code: string } | null;
            recorded_by: { name: string } | null;
            semester: { name: string } | null;
        }>;
        links: any[];
    };
    counselingRecords?: Array<{
        id: number;
        date_scheduled: string | null;
        date_completed: string | null;
        category: string;
        status: string;
        confidentiality_level: string;
        reason: string | null;
        summary: string | null;
        follow_up_action: string | null;
        counselor_name: string;
        counselor_id: number | null;
        semester_name: string | null;
    }> | null;
    filters: {
        semester_id?: string | number;
        event_type?: string;
    };
}>();

const semesterId = ref(props.selectedSemesterId || '');
const eventType = ref(props.filters.event_type || '');
const activeProfileTab = ref<'discipline' | 'counseling'>('discipline');

const page = usePage();
const userRoles = computed<string[]>(() => (page.props.auth as any)?.roles || []);

const canManageStudents = computed(() => {
    return userRoles.value.includes('Super Admin') || userRoles.value.includes('Tata Tertib');
});

const canRecordDiscipline = computed(() => {
    return userRoles.value.includes('Super Admin') || userRoles.value.includes('Tata Tertib') || userRoles.value.includes('Teacher') || userRoles.value.includes('BK');
});

const canCounsel = computed(() => {
    return userRoles.value.includes('Super Admin') || userRoles.value.includes('BK');
});

const isBkUser = computed(() => {
    return userRoles.value.includes('BK') || userRoles.value.includes('Super Admin');
});

watch([semesterId, eventType], ([newSemester, newType]) => {
    router.get(`/students/${props.student.id}`, { 
        semester_id: newSemester,
        event_type: newType
    }, { preserveState: true, replace: true });
});

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }).format(d);
};

const getStatusBadgeClass = (status: string) => {
    switch(status) {
        case 'NORMAL': return 'bg-green-50 text-green-700';
        case 'ATTENTION': return 'bg-yellow-50 text-yellow-700';
        case 'RAPID_INCREASE': return 'bg-orange-50 text-orange-700';
        case 'HIGH_THRESHOLD': return 'bg-red-50 text-red-700';
        default: return 'bg-gray-50 text-gray-700';
    }
};

const getStatusLabel = (status: string) => {
    switch(status) {
        case 'NORMAL': return 'Aman';
        case 'ATTENTION': return 'Perhatian';
        case 'RAPID_INCREASE': return 'Waspada';
        case 'HIGH_THRESHOLD': return 'Kritis';
        default: return 'Normal';
    }
};

const getCounselingStatusBadge = (status: string) => {
    switch ((status || '').toLowerCase()) {
        case 'completed': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'in_progress': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'scheduled': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'cancelled': return 'bg-gray-100 text-gray-500 border-gray-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
};

const getCounselingStatusLabel = (status: string) => {
    switch ((status || '').toLowerCase()) {
        case 'completed': return 'Selesai';
        case 'in_progress': return 'Sedang Berjalan';
        case 'scheduled': return 'Terjadwal';
        case 'cancelled': return 'Dibatalkan';
        default: return status || 'Tercatat';
    }
};

const getConfidentialityBadge = (level: string) => {
    switch ((level || '').toLowerCase()) {
        case 'strict': return { label: 'Sangat Rahasia', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'confidential': return { label: 'Rahasia', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        default: return { label: 'Umum', class: 'bg-gray-50 text-gray-600 border-gray-200' };
    }
};

const canEditCounseling = (recordCounselorId: number | null) => {
    const authUser = (page.props.auth as any)?.user;
    if (!authUser) return false;
    if (userRoles.value.includes('Super Admin')) return true;
    if (userRoles.value.includes('BK') && authUser.id === recordCounselorId) return true;
    return false;
};
</script>

<template>
    <Head :title="`${student.name} - Detail Siswa`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/students" class="text-gray-500 hover:text-gray-900 transition-colors">Data Siswa</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ student.name }}</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Area -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">{{ student.name }}</h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">Profil Siswa & Riwayat Kedisiplinan</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a 
                        :href="`/students/${student.id}/print-card?semester_id=${selectedSemesterId || ''}`" 
                        target="_blank"
                        class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-xs flex items-center gap-1.5"
                    >
                        <i class="pi pi-print text-xs"></i>
                        <span>Cetak Kartu</span>
                    </a>
                    <!-- For BK: Jadwalkan Konseling Siswa & Panggil Ortu -->
                    <Link
                        v-if="canCounsel"
                        :href="`/counseling-records/create?student_id=${student.id}`"
                        class="px-5 py-2 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                    >
                        <i class="pi pi-calendar-plus text-xs"></i>
                        <span>Jadwalkan Konseling</span>
                    </Link>

                    <Link
                        v-if="isBkUser"
                        :href="`/letters/create?student_id=${student.id}`"
                        class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-xs flex items-center gap-1.5"
                    >
                        <i class="pi pi-envelope text-xs text-rose-600"></i>
                        <span>Surat Panggilan</span>
                    </Link>

                    <!-- For Teacher & Tata Tertib: Catat Kejadian -->
                    <Link 
                        v-if="canRecordDiscipline"
                        :href="`/discipline-events/create?student_id=${student.id}`" 
                        class="px-5 py-2 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Catat Kejadian</span>
                    </Link>

                    <!-- For Admin & Tata Tertib: Edit Data Pokok Siswa -->
                    <Link 
                        v-if="canManageStudents"
                        :href="`/students/${student.id}/edit`" 
                        class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-xs flex items-center gap-1.5"
                    >
                        <i class="pi pi-pencil text-xs"></i>
                        <span>Edit Data</span>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Identity & Summary -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Identity Card -->
                    <div class="bg-white rounded-[24px] p-6 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-4">
                        <div class="border-b border-gray-100 pb-3">
                            <h3 class="font-bold text-gray-900 text-base">Informasi Identitas</h3>
                        </div>
                        <div class="space-y-3.5 text-sm">
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">NIS</p>
                                <p class="mt-0.5 text-gray-900 font-medium">{{ student.nis }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Kelas Saat Ini</p>
                                <p class="mt-0.5 text-gray-900 font-medium">{{ student.class_room ? student.class_room.name : 'Belum Ditetapkan' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Nama Orang Tua / Wali</p>
                                <p class="mt-0.5 text-gray-900 font-medium">{{ student.parent_name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Kontak Darurat (Wali)</p>
                                <p class="mt-0.5 text-gray-900 font-medium">{{ student.parent_phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status Siswa</p>
                                <span class="mt-1 inline-flex px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider rounded-md"
                                    :class="(student.status.toLowerCase() === 'active' || student.status.toLowerCase() === 'aktif') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                    {{ student.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Discipline Summary Card -->
                    <div class="bg-white rounded-[24px] p-6 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <h3 class="font-bold text-gray-900 text-base">Ringkasan Poin</h3>
                            <span class="text-xs text-gray-500 font-medium">Semester Aktif</span>
                        </div>
                        <div class="py-2 flex flex-col items-center">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Net Point</p>
                            <p class="text-4xl font-bold text-gray-900 mb-2">{{ summary.net_points }}</p>
                            <span class="px-3 py-1 text-[11px] font-bold uppercase tracking-wider rounded-md"
                                :class="getStatusBadgeClass(summary.risk_status)">
                                Status: {{ getStatusLabel(summary.risk_status) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="p-3.5 rounded-2xl bg-rose-50/60 border border-rose-100/60 text-center">
                                <p class="text-[11px] font-bold text-rose-600 uppercase tracking-wider mb-0.5">Pelanggaran</p>
                                <p class="text-lg font-bold text-rose-700">+{{ summary.violation_points }}</p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-100/60 text-center">
                                <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider mb-0.5">Prestasi</p>
                                <p class="text-lg font-bold text-emerald-700">-{{ summary.achievement_points }}</p>
                            </div>
                        </div>

                        <!-- Ringkasan Konseling BK (Khusus Guru BK, Kepala Sekolah, dan Super Admin) -->
                        <div v-if="counselingRecords !== null" class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                            <span class="text-gray-500 font-medium flex items-center gap-1.5">
                                <i class="pi pi-comments text-[#6366F1]"></i>
                                Total Pembinaan BK
                            </span>
                            <span class="font-bold text-[#6366F1] bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-100">
                                {{ counselingRecords?.length || 0 }} Sesi
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Timeline / History -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-5">
                        
                        <!-- Navigation Tabs (Discipline vs Counseling) -->
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-4 overflow-x-auto [scrollbar-width:none]">
                            <button
                                @click="activeProfileTab = 'discipline'"
                                class="px-5 py-2 rounded-full text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                                :class="activeProfileTab === 'discipline' ? 'bg-[#6366F1] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >
                                <i class="pi pi-history text-xs"></i>
                                Riwayat Kedisiplinan & Prestasi ({{ events.data.length }})
                            </button>

                            <button
                                v-if="counselingRecords !== null"
                                @click="activeProfileTab = 'counseling'"
                                class="px-5 py-2 rounded-full text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                                :class="activeProfileTab === 'counseling' ? 'bg-[#6366F1] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >
                                <i class="pi pi-comments text-xs"></i>
                                Riwayat Pembinaan BK ({{ counselingRecords?.length || 0 }})
                            </button>
                        </div>

                        <!-- TAB 1: RIWAYAT KEDISIPLINAN -->
                        <div v-if="activeProfileTab === 'discipline'" class="space-y-5">
                            <!-- Filter Bar -->
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100 pb-4">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-base">Riwayat Kedisiplinan</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Daftar kejadian pelanggaran dan prestasi yang tercatat.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                    <select 
                                        v-model="semesterId"
                                        class="text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                    >
                                        <option value="">Semua Semester</option>
                                        <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
                                            {{ sem.name }} ({{ sem.status === 'Closed' ? 'Selesai' : 'Aktif' }})
                                        </option>
                                    </select>
                                    <select 
                                        v-model="eventType"
                                        class="text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                    >
                                        <option value="">Semua Jenis</option>
                                        <option value="violation">Pelanggaran</option>
                                        <option value="achievement">Prestasi</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="events.data.length === 0" class="py-16 text-center text-gray-500 text-xs">
                                <i class="pi pi-info-circle text-2xl text-gray-400 mb-2 block"></i>
                                Belum ada riwayat kedisiplinan atau prestasi yang tercatat pada filter ini.
                            </div>

                            <!-- Timeline List -->
                            <div v-else class="flow-root">
                                <ul role="list" class="divide-y divide-gray-50">
                                    <li v-for="event in events.data" :key="event.id" class="py-4 hover:bg-gray-50/60 px-3 rounded-2xl transition-colors">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-3">
                                                <div 
                                                    class="w-9 h-9 rounded-2xl flex items-center justify-center shrink-0 text-sm mt-0.5"
                                                    :class="event.event_type === 'violation' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'"
                                                >
                                                    <i :class="event.event_type === 'violation' ? 'pi pi-exclamation-triangle' : 'pi pi-star'"></i>
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-bold text-gray-900">
                                                        {{ event.event_type === 'violation' ? (event.violation?.name || 'Pelanggaran') : (event.achievement?.name || 'Prestasi') }}
                                                    </h4>
                                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-[11px] text-gray-500">
                                                        <span>{{ formatDate(event.event_date) }}</span>
                                                        <span>•</span>
                                                        <span>Dicatat: {{ event.recorded_by?.name || 'Sistem' }}</span>
                                                        <span v-if="event.semester">•</span>
                                                        <span v-if="event.semester">{{ event.semester.name }}</span>
                                                    </div>
                                                    <p v-if="event.description" class="mt-2 text-xs text-gray-600 bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                                        {{ event.description }}
                                                    </p>
                                                    <div v-if="event.follow_up" class="mt-2 text-xs text-amber-700 bg-amber-50/70 p-2.5 rounded-xl border border-amber-100">
                                                        <span class="block text-[10px] font-bold uppercase tracking-wider mb-0.5">Tindak Lanjut:</span>
                                                        <p>{{ event.follow_up }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span 
                                                    class="text-xs font-bold inline-flex px-2.5 py-1 rounded-full"
                                                    :class="event.event_type === 'violation' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'"
                                                >
                                                    {{ event.event_type === 'violation' ? `+${event.points_effect}` : `-${event.points_effect}` }} Poin
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Pagination -->
                            <div v-if="events.links.length > 3" class="pt-4 border-t border-gray-100 flex items-center justify-center gap-1">
                                <Link 
                                    v-for="(link, index) in events.links" 
                                    :key="index"
                                    :href="link.url || '#'"
                                    class="min-w-[32px] h-8 flex items-center justify-center px-2 text-[13px] font-medium rounded-lg transition-colors"
                                    :class="[
                                        link.active ? 'bg-[#6366F1] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100',
                                        !link.url && 'opacity-40 cursor-not-allowed hover:bg-transparent'
                                    ]"
                                    v-html="link.label"
                                    preserve-scroll
                                />
                            </div>
                        </div>

                        <!-- TAB 2: RIWAYAT PEMBINAAN BK -->
                        <div v-else-if="activeProfileTab === 'counseling' && counselingRecords !== null" class="space-y-5">
                            <!-- Section Header -->
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100 pb-4">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-base">Riwayat Pembinaan BK</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Rekam jejak konseling, bimbingan karakter, dan hasil tindak lanjut.</p>
                                </div>
                                <div v-if="canCounsel">
                                    <Link
                                        :href="`/counseling-records/create?student_id=${student.id}`"
                                        class="px-4 py-2 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                                    >
                                        <i class="pi pi-plus text-xs"></i>
                                        <span>Sesi Konseling Baru</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="counselingRecords.length === 0" class="py-16 text-center space-y-3">
                                <div class="w-12 h-12 rounded-full bg-indigo-50 text-[#6366F1] flex items-center justify-center mx-auto text-xl">
                                    <i class="pi pi-comments"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">Belum Ada Riwayat Konseling BK</h4>
                                    <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                                        Siswa ini belum memiliki catatan sesi bimbingan atau konseling karakter.
                                    </p>
                                </div>
                                <div v-if="canCounsel" class="pt-2">
                                    <Link
                                        :href="`/counseling-records/create?student_id=${student.id}`"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm"
                                    >
                                        <i class="pi pi-plus text-xs"></i>
                                        <span>Jadwalkan Konseling Pertama</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Counseling Records List -->
                            <div v-else class="space-y-4">
                                <div 
                                    v-for="record in counselingRecords" 
                                    :key="record.id"
                                    class="p-5 rounded-2xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:shadow-sm transition-all space-y-3"
                                >
                                    <!-- Header Row -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-[#6366F1] border border-indigo-100">
                                                {{ record.category }}
                                            </span>
                                            <span 
                                                class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                                :class="getCounselingStatusBadge(record.status)"
                                            >
                                                {{ getCounselingStatusLabel(record.status) }}
                                            </span>
                                            <span 
                                                v-if="record.confidentiality_level && record.confidentiality_level !== 'normal'"
                                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                                :class="getConfidentialityBadge(record.confidentiality_level).class"
                                            >
                                                <i class="pi pi-lock text-[9px] mr-1"></i>
                                                {{ getConfidentialityBadge(record.confidentiality_level).label }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <i class="pi pi-calendar text-[11px]"></i>
                                            <span>Jadwal: {{ record.date_scheduled ? formatDate(record.date_scheduled) : '-' }}</span>
                                            <span v-if="record.date_completed" class="text-emerald-600 font-medium">
                                                (Selesai: {{ formatDate(record.date_completed) }})
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Meta Info -->
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                        <span class="flex items-center gap-1.5">
                                            <i class="pi pi-user text-gray-400"></i>
                                            <span>Konselor: <strong class="text-gray-800">{{ record.counselor_name }}</strong></span>
                                        </span>
                                        <span v-if="record.semester_name" class="flex items-center gap-1.5">
                                            <i class="pi pi-bookmark text-gray-400"></i>
                                            <span>Semester: {{ record.semester_name }}</span>
                                        </span>
                                    </div>

                                    <!-- Content Details -->
                                    <div class="space-y-2 pt-1 text-xs">
                                        <div v-if="record.reason">
                                            <span class="font-bold text-gray-700 block mb-0.5">Alasan / Pokok Masalah:</span>
                                            <p class="text-gray-600 bg-white p-3 rounded-xl border border-gray-100 leading-relaxed whitespace-pre-wrap">
                                                {{ record.reason }}
                                            </p>
                                        </div>
                                        <div v-if="record.summary">
                                            <span class="font-bold text-gray-700 block mb-0.5">Ringkasan Hasil Konseling:</span>
                                            <p class="text-gray-600 bg-white p-3 rounded-xl border border-gray-100 leading-relaxed whitespace-pre-wrap">
                                                {{ record.summary }}
                                            </p>
                                        </div>
                                        <div v-if="record.follow_up_action" class="bg-amber-50/70 p-3 rounded-xl border border-amber-100">
                                            <span class="font-bold text-amber-800 block mb-0.5 text-[11px] uppercase tracking-wider">Rencana Tindak Lanjut:</span>
                                            <p class="text-amber-900 leading-relaxed whitespace-pre-wrap">
                                                {{ record.follow_up_action }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Actions Row -->
                                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                                        <Link 
                                            :href="`/counseling-records/${record.id}`"
                                            class="px-3.5 py-1.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-2xs flex items-center gap-1"
                                        >
                                            <i class="pi pi-eye text-[11px]"></i>
                                            <span>Lihat Detail</span>
                                        </Link>
                                        <Link 
                                            v-if="canEditCounseling(record.counselor_id)"
                                            :href="`/counseling-records/${record.id}/edit`"
                                            class="px-3.5 py-1.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-xs flex items-center gap-1"
                                        >
                                            <i class="pi pi-pencil text-[11px]"></i>
                                            <span>Catat / Edit Hasil</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
