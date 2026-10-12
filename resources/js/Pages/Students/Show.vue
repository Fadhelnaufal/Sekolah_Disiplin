<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
    filters: {
        semester_id?: string | number;
        event_type?: string;
    };
}>();

const semesterId = ref(props.selectedSemesterId || '');
const eventType = ref(props.filters.event_type || '');

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
        case 'NORMAL': return 'bg-[#237A57]/10 text-[#237A57] border-[#237A57]/20';
        case 'ATTENTION': return 'bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20';
        case 'RAPID_INCREASE': return 'bg-[#EF4444]/10 text-[#EF4444] border-[#EF4444]/20';
        case 'HIGH_THRESHOLD': return 'bg-[#991B1B]/10 text-[#991B1B] border-[#991B1B]/20';
        default: return 'bg-[#697586]/10 text-[#697586] border-[#697586]/20';
    }
};

const getStatusLabel = (status: string) => {
    switch(status) {
        case 'NORMAL': return 'Aman';
        case 'ATTENTION': return 'Perlu Perhatian';
        case 'RAPID_INCREASE': return 'Peningkatan Cepat';
        case 'HIGH_THRESHOLD': return 'Batas Kritis';
        default: return 'Tidak Diketahui';
    }
};
</script>

<template>
    <Head :title="`Detail Siswa: ${student.name} - Disiplin Pro`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 w-full pr-4 text-sm font-medium">
                <Link href="/students" class="text-[#697586] hover:text-[#202B3C] transition-colors">Data Siswa</Link>
                <span class="text-[#E5E9EF]">/</span>
                <span class="text-[#202B3C]">Detail Profil</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#202B3C] tracking-tight">{{ student.name }}</h1>
                    <p class="text-[#697586] mt-1 text-sm">Profil Siswa & Riwayat Kedisiplinan</p>
                </div>
                <div class="flex gap-3">
                    <a 
                        :href="`/students/${student.id}/print-card?semester_id=${selectedSemesterId || ''}`" 
                        target="_blank"
                        class="inline-flex items-center justify-center bg-white border border-[#E5E9EF] hover:bg-[#F7F8FA] text-[#202B3C] text-sm font-medium py-2 px-4 rounded transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E5E9EF]"
                    >
                        Cetak Kartu
                    </a>
                    <Link 
                        :href="`/discipline-events/create?student_id=${student.id}`" 
                        class="inline-flex items-center justify-center bg-[#1D3557] hover:bg-[#0D1B2A] text-white text-sm font-medium py-2 px-4 rounded transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D3557]"
                    >
                        + Catat Kejadian
                    </Link>
                    <Link 
                        :href="`/students/${student.id}/edit`" 
                        class="inline-flex items-center justify-center bg-white border border-[#E5E9EF] text-[#202B3C] hover:bg-[#F7F8FA] text-sm font-medium py-2 px-4 rounded transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E5E9EF]"
                    >
                        Edit Data
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Identity & Summary -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Identity Card -->
                    <div class="bg-white border border-[#E5E9EF] shadow-sm">
                        <div class="px-5 py-4 border-b border-[#E5E9EF] bg-[#F7F8FA]/50">
                            <h3 class="font-semibold text-[#202B3C] text-sm">Informasi Identitas</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs font-semibold text-[#697586] uppercase tracking-wider">NIS</p>
                                <p class="mt-1 text-sm text-[#202B3C] font-medium">{{ student.nis }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-[#697586] uppercase tracking-wider">Kelas Saat Ini</p>
                                <p class="mt-1 text-sm text-[#202B3C] font-medium">{{ student.class_room ? student.class_room.name : 'Belum Ditetapkan' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-[#697586] uppercase tracking-wider">Nama Orang Tua/Wali</p>
                                <p class="mt-1 text-sm text-[#202B3C] font-medium">{{ student.parent_name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-[#697586] uppercase tracking-wider">Kontak Darurat (Wali)</p>
                                <p class="mt-1 text-sm text-[#202B3C] font-medium">{{ student.parent_phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-[#697586] uppercase tracking-wider">Status Siswa</p>
                                <span class="mt-1 inline-flex px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded"
                                    :class="(student.status.toLowerCase() === 'active' || student.status.toLowerCase() === 'aktif') ? 'bg-[#237A57]/10 text-[#237A57]' : 'bg-[#B42318]/10 text-[#B42318]'">
                                    {{ student.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Discipline Summary Card -->
                    <div class="bg-white border border-[#E5E9EF] shadow-sm">
                        <div class="px-5 py-4 border-b border-[#E5E9EF] bg-[#F7F8FA]/50 flex justify-between items-center">
                            <h3 class="font-semibold text-[#202B3C] text-sm">Ringkasan Poin</h3>
                            <span class="text-xs text-[#697586]">Semester Aktif</span>
                        </div>
                        <div class="p-5 flex flex-col items-center border-b border-[#E5E9EF]">
                            <p class="text-xs font-semibold text-[#697586] uppercase tracking-wider mb-2">Total Net Point</p>
                            <p class="text-4xl font-bold text-[#202B3C] mb-2">{{ summary.net_points }}</p>
                            <span class="px-3 py-1 text-[11px] font-bold uppercase tracking-wider rounded border"
                                :class="getStatusBadgeClass(summary.risk_status)">
                                Status: {{ getStatusLabel(summary.risk_status) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-[#E5E9EF] bg-[#F7F8FA]">
                            <div class="p-4 text-center">
                                <p class="text-xs font-semibold text-[#B42318] uppercase tracking-wider mb-1">Pelanggaran</p>
                                <p class="text-lg font-bold text-[#B42318]">+{{ summary.violation_points }}</p>
                            </div>
                            <div class="p-4 text-center">
                                <p class="text-xs font-semibold text-[#237A57] uppercase tracking-wider mb-1">Prestasi</p>
                                <p class="text-lg font-bold text-[#237A57]">-{{ summary.achievement_points }}</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Timeline & History -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white border border-[#E5E9EF] shadow-sm flex flex-col h-full">
                        <div class="px-5 py-4 border-b border-[#E5E9EF] bg-[#F7F8FA]/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <h3 class="font-semibold text-[#202B3C] text-sm">Riwayat Kedisiplinan</h3>
                            
                            <!-- Filters -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <select v-model="eventType" class="px-2 py-1.5 text-xs border border-[#E5E9EF] bg-white rounded focus:ring-1 focus:ring-[#315E8A] outline-none text-[#202B3C]">
                                    <option value="">Semua Jenis</option>
                                    <option value="violation">Pelanggaran Saja</option>
                                    <option value="achievement">Prestasi Saja</option>
                                </select>
                                <select v-model="semesterId" class="px-2 py-1.5 text-xs border border-[#E5E9EF] bg-white rounded focus:ring-1 focus:ring-[#315E8A] outline-none text-[#202B3C]">
                                    <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
                                        {{ sem.name }} {{ (sem.status.toLowerCase() === 'active' || sem.status.toLowerCase() === 'aktif') ? '(Aktif)' : '' }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Timeline Content -->
                        <div class="flex-1 p-0">
                            <div v-if="events.data.length === 0" class="flex flex-col items-center justify-center py-16 px-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#697586]/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium text-[#697586]">Belum ada riwayat kedisiplinan untuk siswa ini.</p>
                                <p class="text-xs text-[#697586]/70 mt-1 text-center">Riwayat pada semester yang dipilih akan muncul di sini.</p>
                            </div>
                            
                            <ul v-else class="divide-y divide-[#E5E9EF]">
                                <li v-for="event in events.data" :key="event.id" class="p-5 hover:bg-[#F7F8FA]/50 transition-colors">
                                    <div class="flex gap-4">
                                        <!-- Icon -->
                                        <div class="mt-0.5">
                                            <div v-if="event.event_type.toLowerCase() === 'violation'" class="w-8 h-8 rounded bg-[#B42318]/10 flex items-center justify-center text-[#B42318]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                            <div v-else class="w-8 h-8 rounded bg-[#237A57]/10 flex items-center justify-center text-[#237A57]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                </svg>
                                            </div>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-start gap-2">
                                                <div>
                                                    <p class="text-sm font-semibold text-[#202B3C]">
                                                        {{ event.event_type.toLowerCase() === 'violation' ? event.violation?.name : event.achievement?.name }}
                                                    </p>
                                                    <p class="text-xs text-[#697586] mt-0.5">
                                                        {{ formatDate(event.event_date) }} • Dicatat oleh: {{ event.recorded_by?.name || 'Sistem' }}
                                                    </p>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <span class="inline-flex px-2 py-1 text-xs font-bold rounded"
                                                        :class="event.event_type.toLowerCase() === 'violation' ? 'bg-[#B42318]/10 text-[#B42318]' : 'bg-[#237A57]/10 text-[#237A57]'">
                                                        {{ event.event_type.toLowerCase() === 'violation' ? '+' : '-' }}{{ event.points_effect }} Poin
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div v-if="event.description || event.follow_up" class="mt-3 text-sm text-[#3E4C5F] bg-[#F7F8FA] p-3 rounded border border-[#E5E9EF]">
                                                <div v-if="event.description" class="mb-2 last:mb-0">
                                                    <span class="block text-[10px] font-bold text-[#697586] uppercase tracking-wider mb-0.5">Catatan Kronologi:</span>
                                                    <p>{{ event.description }}</p>
                                                </div>
                                                <div v-if="event.follow_up" class="mb-0">
                                                    <span class="block text-[10px] font-bold text-[#697586] uppercase tracking-wider mb-0.5">Tindak Lanjut:</span>
                                                    <p>{{ event.follow_up }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Pagination -->
                        <div v-if="events.links.length > 3" class="px-5 py-4 border-t border-[#E5E9EF] bg-[#F7F8FA]/30 flex items-center justify-between">
                            <div class="flex items-center space-x-1">
                                <Link 
                                    v-for="(link, index) in events.links" 
                                    :key="index"
                                    :href="link.url || '#'"
                                    class="px-3 py-1 text-xs font-medium border rounded transition-colors"
                                    :class="[
                                        link.active ? 'bg-[#1D3557] text-white border-[#1D3557]' : 'text-[#697586] border-[#E5E9EF] hover:bg-[#F7F8FA] bg-white',
                                        !link.url && 'opacity-50 cursor-not-allowed hover:bg-white'
                                    ]"
                                    v-html="link.label"
                                    preserve-scroll
                                />
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
