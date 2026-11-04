<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';
import { buildWhatsAppUrl } from '../../utils';

interface RecordItem {
    id: number;
    student_id: number;
    counselor_id: number;
    original_recorder_name?: string | null;
    date_scheduled: string;
    date_completed?: string | null;
    category?: string | null;
    reason?: string | null;
    summary?: string | null;
    follow_up_action?: string | null;
    status: string;
    confidentiality_level: string;
    created_at?: string;
    updated_at?: string;
    student?: {
        id: number;
        nis: string;
        nisn?: string | null;
        name: string;
        parent_name?: string | null;
        parent_phone?: string | null;
        address?: string | null;
        class_room?: {
            id: number;
            name: string;
            grade_level?: string | null;
            homeroom_teacher?: {
                id: number;
                name: string;
            } | null;
        } | null;
    } | null;
    counselor?: {
        id: number;
        name: string;
        email?: string;
    } | null;
    semester?: {
        id: number;
        name: string;
        status: string;
        academic_year?: {
            id: number;
            name: string;
        } | null;
    } | null;
}

interface StudentSummary {
    violation_points: number;
    achievement_points: number;
    net_points: number;
    risk_status: string;
}

interface Permissions {
    update: boolean;
    delete: boolean;
}

const props = defineProps<{
    record: RecordItem;
    studentSummary?: StudentSummary | null;
    can?: Permissions;
}>();

const studentInitials = computed(() => {
    const name = props.record.student?.name || 'Siswa';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const statusInfo = computed(() => {
    const st = (props.record.status || '').toLowerCase();
    switch (st) {
        case 'resolved':
        case 'completed':
            return {
                label: 'Selesai',
                icon: 'pi pi-check-circle',
                badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                step: 3,
                stepDesc: 'Sesi bimbingan telah rampung dan hasil tercatat.',
            };
        case 'in_progress':
            return {
                label: 'Sedang Berjalan',
                icon: 'pi pi-spinner',
                badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
                step: 2,
                stepDesc: 'Tahap penggalian masalah dan dialog bersama siswa.',
            };
        case 'referred':
            return {
                label: 'Dirujuk ke Pihak Luar',
                icon: 'pi pi-external-link',
                badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',
                step: 3,
                stepDesc: 'Kasus dialihkan ke ahli profesional atau instansi luar.',
            };
        case 'cancelled':
            return {
                label: 'Dibatalkan',
                icon: 'pi pi-times-circle',
                badgeClass: 'bg-gray-100 text-gray-600 border-gray-200',
                step: 0,
                stepDesc: 'Agenda sesi konseling dibatalkan oleh petugas.',
            };
        case 'scheduled':
        default:
            return {
                label: 'Terjadwal',
                icon: 'pi pi-calendar-clock',
                badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
                step: 1,
                stepDesc: 'Jadwal telah diagendakan, menunggu pelaksanaan sesi.',
            };
    }
});

const riskInfo = computed(() => {
    const st = props.studentSummary?.risk_status || 'NORMAL';
    switch (st) {
        case 'NORMAL':
            return { label: 'Aman', class: 'bg-green-50 text-green-700 border-green-200' };
        case 'ATTENTION':
            return { label: 'Perhatian', class: 'bg-yellow-50 text-yellow-700 border-yellow-200' };
        case 'RAPID_INCREASE':
            return { label: 'Waspada', class: 'bg-orange-50 text-orange-700 border-orange-200' };
        case 'HIGH_THRESHOLD':
            return { label: 'Kritis', class: 'bg-red-50 text-red-700 border-red-200' };
        default:
            return { label: 'Normal', class: 'bg-gray-50 text-gray-700 border-gray-200' };
    }
});

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        }).format(d);
    } catch {
        return dateStr;
    }
};

const formatShortDate = (dateStr?: string | null) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(d);
    } catch {
        return dateStr;
    }
};

const whatsappUrl = computed(() => {
    const phone = props.record.student?.parent_phone;
    if (!phone) return null;
    const msg = `Halo Bapak/Ibu ${props.record.student?.parent_name || 'Wali Murid'}, kami dari Tim Bimbingan & Konseling menginformasikan agenda tindak lanjut pembinaan ananda ${props.record.student?.name || 'Siswa'}. Terima kasih.`;
    return buildWhatsAppUrl(phone, msg);
});

const confirmDelete = () => {
    Swal.fire({
        title: 'Konfirmasi Hapus Sesi',
        text: 'Apakah Anda yakin ingin menghapus catatan sesi konseling ini? Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Catatan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/counseling-records/${props.record.id}`);
        }
    });
};
</script>

<template>
    <Head :title="`Detail Konseling - ${record.student?.name || 'Siswa'} - Sekolah Disiplin`" />

    <AuthenticatedLayout>
        <!-- Header Slot -->
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/counseling-records" class="text-gray-500 hover:text-gray-900 transition-colors">
                    Pembinaan
                </Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Detail Sesi Konseling</span>
            </div>
        </template>

        <!-- Main Workspace Container -->
        <div class="max-w-[1400px] mx-auto space-y-6 pb-12">
            
            <!-- Page Title & Primary Actions Area -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Detail Sesi Pembinaan</h1>
                        <!-- Status Badge -->
                        <span 
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-2xs"
                            :class="statusInfo.badgeClass"
                        >
                            <i :class="statusInfo.icon" class="text-xs"></i>
                            <span>{{ statusInfo.label }}</span>
                        </span>
                        <!-- Confidentiality Badge -->
                        <span 
                            v-if="record.confidentiality_level === 'strict'"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200"
                        >
                            <i class="pi pi-lock text-[10px]"></i>
                            <span>Sangat Rahasia</span>
                        </span>
                    </div>
                    <p class="text-gray-500 text-[15px] mt-1">
                        Workspace terpadu penanganan karakter, dialog konseling, dan evaluasi perkembangan siswa.
                    </p>
                </div>

                <!-- Action Button Toolbar -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link
                        href="/counseling-records"
                        class="px-5 py-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-2xs inline-flex items-center gap-2"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                        <span>Kembali ke Daftar</span>
                    </Link>

                    <Link
                        v-if="can?.update"
                        :href="`/counseling-records/${record.id}/edit`"
                        class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-[0_4px_14px_#6366F140] inline-flex items-center gap-2"
                    >
                        <i class="pi pi-pencil text-xs"></i>
                        <span>{{ record.summary ? 'Edit Catatan Hasil' : 'Catat Hasil Sesi' }}</span>
                    </Link>

                    <button
                        v-if="can?.delete"
                        type="button"
                        @click="confirmDelete"
                        class="px-4 py-2.5 rounded-full border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer"
                        title="Hapus Sesi Konseling"
                    >
                        <i class="pi pi-trash text-xs"></i>
                        <span class="hidden sm:inline">Hapus</span>
                    </button>
                </div>
            </div>

            <!-- Two-Column Editorial Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- ================= LEFT COLUMN: IDENTITY & METADATA (4 Cols) ================= -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- 1. Student Identity Card -->
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Identitas Siswa</h2>
                            <Link 
                                v-if="record.student_id" 
                                :href="`/students/${record.student_id}`"
                                class="text-xs font-semibold text-[#6366F1] hover:underline flex items-center gap-1"
                            >
                                <span>Profil Siswa</span>
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </Link>
                        </div>

                        <!-- Profile Header -->
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-[#6366F1] flex items-center justify-center font-bold text-xl shrink-0 shadow-2xs">
                                {{ studentInitials }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-lg font-bold text-gray-900 leading-snug truncate">
                                    {{ record.student?.name || 'Nama Tidak Ditemukan' }}
                                </h3>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">
                                    {{ record.student?.class_room?.name ? `Kelas ${record.student.class_room.name}` : 'Tanpa Kelas' }}
                                    <span class="mx-1">•</span>
                                    NIS: {{ record.student?.nis || '-' }}
                                </p>
                            </div>
                        </div>

                        <!-- Disciplinary Score Context (if available) -->
                        <div v-if="studentSummary" class="p-4 rounded-2xl bg-gray-50/70 border border-gray-100 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 font-medium">Status Risiko Disiplin</span>
                                <span 
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                    :class="riskInfo.class"
                                >
                                    {{ riskInfo.label }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center pt-1 border-t border-gray-100">
                                <div>
                                    <span class="text-[10px] font-semibold text-gray-400 uppercase block">Pelanggaran</span>
                                    <span class="text-sm font-bold text-rose-600">+{{ studentSummary.violation_points }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-semibold text-gray-400 uppercase block">Prestasi</span>
                                    <span class="text-sm font-bold text-emerald-600">-{{ studentSummary.achievement_points }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-semibold text-gray-400 uppercase block">Net Poin</span>
                                    <span class="text-sm font-bold text-gray-900">{{ studentSummary.net_points }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Attributes List -->
                        <div class="space-y-3 text-xs divide-y divide-gray-50">
                            <div class="pt-2 flex justify-between items-center gap-2">
                                <span class="text-gray-500">Wali Kelas:</span>
                                <span class="font-semibold text-gray-800 text-right">
                                    {{ record.student?.class_room?.homeroom_teacher?.name || 'Belum Diatur' }}
                                </span>
                            </div>

                            <div v-if="record.student?.parent_name" class="pt-3 flex justify-between items-center gap-2">
                                <span class="text-gray-500">Nama Orang Tua:</span>
                                <span class="font-semibold text-gray-800 text-right">
                                    {{ record.student.parent_name }}
                                </span>
                            </div>

                            <div v-if="record.student?.parent_phone" class="pt-3 flex justify-between items-center gap-2">
                                <span class="text-gray-500">Kontak WhatsApp:</span>
                                <a 
                                    v-if="whatsappUrl"
                                    :href="whatsappUrl" 
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1.5 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100"
                                >
                                    <i class="pi pi-whatsapp text-xs"></i>
                                    <span>{{ record.student.parent_phone }}</span>
                                </a>
                                <span v-else class="font-semibold text-gray-800">
                                    {{ record.student.parent_phone }}
                                </span>
                            </div>

                            <div v-if="record.student?.address" class="pt-3 flex justify-between items-start gap-2">
                                <span class="text-gray-500 shrink-0">Alamat:</span>
                                <span class="text-gray-700 text-right leading-relaxed">
                                    {{ record.student.address }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Action Link -->
                        <div class="pt-2 border-t border-gray-100">
                            <Link 
                                v-if="record.student_id" 
                                :href="`/students/${record.student_id}`"
                                class="w-full py-2.5 rounded-full border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-semibold transition-all flex items-center justify-center gap-2"
                            >
                                <i class="pi pi-history text-xs text-[#6366F1]"></i>
                                <span>Lihat Riwayat Pelanggaran & Prestasi</span>
                            </Link>
                        </div>
                    </div>

                    <!-- 2. Session Metadata Card -->
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-4">
                        <div class="border-b border-gray-100 pb-3">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Metadata Bimbingan</h2>
                        </div>

                        <div class="space-y-4 text-xs">
                            <!-- Kategori Bimbingan -->
                            <div>
                                <span class="text-gray-400 block mb-1">Kategori Bimbingan</span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-bold bg-indigo-50 text-[#6366F1] border border-indigo-100 text-[11px]">
                                    <i class="pi pi-tag text-[10px]"></i>
                                    {{ record.category || 'Konseling' }}
                                </span>
                            </div>

                            <!-- Konselor / Petugas BK -->
                            <div>
                                <span class="text-gray-400 block mb-0.5">Konselor Penanggung Jawab</span>
                                <p class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                    <i class="pi pi-user text-gray-400 text-xs"></i>
                                    {{ record.counselor?.name || record.original_recorder_name || 'Guru Bimbingan Konseling' }}
                                </p>
                            </div>

                            <!-- Semester & Tahun Ajaran -->
                            <div>
                                <span class="text-gray-400 block mb-0.5">Periode Akademik</span>
                                <p class="font-semibold text-gray-800">
                                    Semester {{ record.semester?.name || '-' }}
                                    <span v-if="record.semester?.academic_year" class="text-gray-500 font-normal">
                                        ({{ record.semester.academic_year.name }})
                                    </span>
                                </p>
                            </div>

                            <!-- Tanggal Jadwal & Selesai -->
                            <div class="grid grid-cols-2 gap-3 pt-1 border-t border-gray-50">
                                <div>
                                    <span class="text-gray-400 block mb-0.5">Jadwal Sesi</span>
                                    <p class="font-semibold text-gray-900">
                                        {{ formatShortDate(record.date_scheduled) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-400 block mb-0.5">Tanggal Rampung</span>
                                    <p class="font-semibold" :class="record.date_completed ? 'text-emerald-700' : 'text-gray-400'">
                                        {{ record.date_completed ? formatShortDate(record.date_completed) : 'Belum Selesai' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Keamanan Catatan -->
                            <div class="pt-3 border-t border-gray-100 flex items-start gap-2.5 text-[11px] text-gray-500 bg-gray-50/60 p-3 rounded-xl border border-gray-100">
                                <i class="pi pi-shield text-emerald-600 mt-0.5 text-xs"></i>
                                <div class="leading-relaxed">
                                    <strong class="text-gray-800 block">Perlindungan Kerahasiaan</strong>
                                    Alasan, dialog, dan catatan pembinaan dienkripsi di basis data untuk menjaga privasi siswa.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ================= RIGHT COLUMN: NARRATIVE WORKSPACE (8 Cols) ================= -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- 1. Milestone / Case Progression Card -->
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 border border-gray-100 shadow-[0_2px_20px_#00000006]">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-5">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Alur Perkembangan Kasus</h2>
                            <span class="text-xs font-medium text-gray-500">{{ statusInfo.stepDesc }}</span>
                        </div>

                        <!-- Responsive 3-Step Milestone Indicator -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Step 1: Penjadwalan -->
                            <div 
                                class="p-4 rounded-2xl border transition-all space-y-1"
                                :class="statusInfo.step >= 1 ? 'bg-indigo-50/50 border-indigo-100' : 'bg-gray-50 border-gray-100 opacity-60'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                        :class="statusInfo.step >= 1 ? 'bg-[#6366F1] text-white' : 'bg-gray-200 text-gray-600'"
                                    >1</span>
                                    <i v-if="statusInfo.step > 1" class="pi pi-check text-xs text-emerald-600"></i>
                                </div>
                                <h3 class="text-xs font-bold text-gray-900 pt-1">Penjadwalan</h3>
                                <p class="text-[11px] text-gray-500 leading-tight">
                                    {{ formatDate(record.date_scheduled) }}
                                </p>
                            </div>

                            <!-- Step 2: Proses Konseling -->
                            <div 
                                class="p-4 rounded-2xl border transition-all space-y-1"
                                :class="statusInfo.step >= 2 ? 'bg-indigo-50/50 border-indigo-100' : 'bg-gray-50 border-gray-100 opacity-60'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                        :class="statusInfo.step >= 2 ? 'bg-[#6366F1] text-white' : 'bg-gray-200 text-gray-600'"
                                    >2</span>
                                    <i v-if="statusInfo.step > 2" class="pi pi-check text-xs text-emerald-600"></i>
                                </div>
                                <h3 class="text-xs font-bold text-gray-900 pt-1">Proses Dialog</h3>
                                <p class="text-[11px] text-gray-500 leading-tight">
                                    {{ record.summary ? 'Hasil dialog tercatat' : 'Menunggu pelaksanaan sesi' }}
                                </p>
                            </div>

                            <!-- Step 3: Penyelesaian & Tindak Lanjut -->
                            <div 
                                class="p-4 rounded-2xl border transition-all space-y-1"
                                :class="statusInfo.step >= 3 ? 'bg-emerald-50/50 border-emerald-100' : 'bg-gray-50 border-gray-100 opacity-60'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                        :class="statusInfo.step >= 3 ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-600'"
                                    >3</span>
                                    <i v-if="statusInfo.step >= 3" class="pi pi-check text-xs text-emerald-600"></i>
                                </div>
                                <h3 class="text-xs font-bold text-gray-900 pt-1">Tuntas & Evaluasi</h3>
                                <p class="text-[11px] text-gray-500 leading-tight">
                                    {{ record.date_completed ? formatDate(record.date_completed) : 'Evaluasi lanjutan' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Core Editorial Narrative Workspace -->
                    <div class="bg-white rounded-[24px] p-7 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-8">
                        
                        <!-- SECTION A: POKOK PERMASALAHAN / ALASAN -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-[#6366F1] flex items-center justify-center shrink-0">
                                    <i class="pi pi-info-circle text-sm"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Pokok Permasalahan & Latar Belakang</h2>
                                    <p class="text-xs text-gray-500">Latar belakang kasus atau alasan siswa dipanggil untuk bimbingan.</p>
                                </div>
                            </div>

                            <div class="p-5 rounded-2xl bg-gray-50/70 border border-gray-100 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap font-normal">
                                {{ record.reason || 'Tidak ada catatan alasan khusus yang dicantumkan.' }}
                            </div>
                        </div>

                        <!-- SECTION B: RINGKASAN PEMBAHASAN & HASIL KONSELING -->
                        <div class="space-y-3 pt-6 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                        <i class="pi pi-comments text-sm"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-bold text-gray-900">Ringkasan Pembahasan & Hasil Konseling</h2>
                                        <p class="text-xs text-gray-500">Catatan dialog bimbingan, komitmen siswa, dan kesimpulan konseling.</p>
                                    </div>
                                </div>

                                <Link
                                    v-if="can?.update && record.summary"
                                    :href="`/counseling-records/${record.id}/edit`"
                                    class="text-xs font-semibold text-[#6366F1] hover:underline flex items-center gap-1"
                                >
                                    <i class="pi pi-pencil text-[10px]"></i>
                                    <span>Edit</span>
                                </Link>
                            </div>

                            <!-- Populated Summary -->
                            <div 
                                v-if="record.summary"
                                class="p-6 rounded-2xl bg-white border border-gray-200/80 shadow-2xs text-sm text-gray-900 leading-relaxed whitespace-pre-wrap font-normal"
                            >
                                {{ record.summary }}
                            </div>

                            <!-- Empty Summary State (Session is still scheduled) -->
                            <div 
                                v-else
                                class="p-8 rounded-2xl bg-amber-50/60 border border-amber-200/60 text-center space-y-3"
                            >
                                <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto text-base">
                                    <i class="pi pi-clock"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">Hasil Konseling Belum Dicatat</h4>
                                    <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">
                                        Sesi bimbingan ini masih berstatus terjadwal. Setelah konseling terlaksana, silakan catat hasil pembahasan dan komitmen siswa.
                                    </p>
                                </div>
                                <div v-if="can?.update" class="pt-2">
                                    <Link
                                        :href="`/counseling-records/${record.id}/edit`"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm"
                                    >
                                        <i class="pi pi-pencil text-xs"></i>
                                        <span>Catat Hasil Sesi Sekarang</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION C: REKOMENDASI & TINDAK LANJUT -->
                        <div v-if="record.follow_up_action" class="space-y-3 pt-6 border-t border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-[#6366F1] flex items-center justify-center shrink-0">
                                    <i class="pi pi-sliders-h text-sm"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Rencana & Rekomendasi Tindak Lanjut</h2>
                                    <p class="text-xs text-gray-500">Langkah pendampingan lanjutan, arahan wali kelas, atau evaluasi berkala.</p>
                                </div>
                            </div>

                            <div class="p-5 rounded-2xl bg-indigo-50/40 border border-indigo-100 text-sm text-indigo-950 leading-relaxed whitespace-pre-wrap font-normal">
                                {{ record.follow_up_action }}
                            </div>
                        </div>

                        <!-- SECTION D: QUICK CROSS-MODULE ACTIONS -->
                        <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/60 p-4 rounded-2xl border border-gray-100">
                            <span class="text-xs font-semibold text-gray-700 flex items-center gap-2">
                                <i class="pi pi-bolt text-amber-500"></i>
                                Tindak Lanjut Administratif Terkait:
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    v-if="record.student_id"
                                    :href="`/follow-ups?action=create&student_id=${record.student_id}&title=${encodeURIComponent('Tindak Lanjut Konseling: ' + (record.category || 'Pembinaan Siswa'))}`"
                                    class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-2xs flex items-center gap-1.5"
                                >
                                    <i class="pi pi-sliders-h text-xs text-indigo-600"></i>
                                    <span>Delegasikan Tindak Lanjut</span>
                                </Link>

                                <Link
                                    v-if="record.student_id"
                                    :href="`/letters/create?student_id=${record.student_id}`"
                                    class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-2xs flex items-center gap-1.5"
                                >
                                    <i class="pi pi-envelope text-xs text-rose-600"></i>
                                    <span>Terbitkan Surat Panggilan</span>
                                </Link>

                                <a
                                    v-if="record.student_id"
                                    :href="`/students/${record.student_id}/print-card`"
                                    target="_blank"
                                    class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-2xs flex items-center gap-1.5"
                                >
                                    <i class="pi pi-print text-xs text-gray-600"></i>
                                    <span>Cetak Kartu Siswa</span>
                                </a>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
