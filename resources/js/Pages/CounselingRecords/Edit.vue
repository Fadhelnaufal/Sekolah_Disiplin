<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

interface StudentInfo {
    id: number;
    nis: string;
    nisn?: string | null;
    name: string;
    parent_name?: string | null;
    parent_phone?: string | null;
    class_room?: {
        id: number;
        name: string;
        grade_level?: string | null;
    } | null;
}

interface RecordData {
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
    student?: StudentInfo | null;
    counselor?: {
        id: number;
        name: string;
    } | null;
    semester?: {
        id: number;
        name: string;
        academic_year?: {
            id: number;
            name: string;
        } | null;
    } | null;
}

const props = defineProps<{
    record: RecordData;
    categories: string[];
    statuses: string[];
}>();

const formatDateForInput = (val?: string | null): string => {
    if (!val) return '';
    return String(val).substring(0, 10);
};

const form = useForm({
    date_scheduled: formatDateForInput(props.record.date_scheduled),
    date_completed: formatDateForInput(props.record.date_completed),
    category: props.record.category || 'Konseling',
    reason: props.record.reason || '',
    summary: props.record.summary || '',
    follow_up_action: props.record.follow_up_action || '',
    status: props.record.status || 'scheduled',
    confidentiality_level: props.record.confidentiality_level || 'normal',
});

const studentInitials = computed(() => {
    const name = props.record.student?.name || 'Siswa';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const statusOptions = [
    { value: 'scheduled', label: 'Terjadwal', desc: 'Sesi diagendakan, menunggu pelaksanaan' },
    { value: 'in_progress', label: 'Sedang Berjalan', desc: 'Sesi sedang aktif dalam proses penanganan' },
    { value: 'resolved', label: 'Selesai', desc: 'Sesi telah dilaksanakan dan hasil disepakati' },
    { value: 'referred', label: 'Dirujuk (Referral)', desc: 'Dialihkan ke pihak eksternal atau spesialis' },
    { value: 'cancelled', label: 'Dibatalkan', desc: 'Sesi dibatalkan karena alasan tertentu' },
];

const currentStatusLabel = computed(() => {
    const found = statusOptions.find(o => o.value === form.status);
    return found ? found.label : form.status;
});

const submit = () => {
    form.put(`/counseling-records/${props.record.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit Catatan Konseling: ${record.student?.name || 'Siswa'} - Sekolah Disiplin`" />

    <AuthenticatedLayout>
        <!-- Top Breadcrumb & Header Slot -->
        <template #header>
            <div class="flex items-center gap-2.5 w-full text-sm font-medium text-gray-500">
                <Link href="/counseling-records" class="hover:text-gray-900 transition-colors">
                    Pembinaan
                </Link>
                <i class="pi pi-angle-right text-xs text-gray-400"></i>
                <Link href="/counseling-records" class="hover:text-gray-900 transition-colors">
                    Catatan Konseling
                </Link>
                <i class="pi pi-angle-right text-xs text-gray-400"></i>
                <Link :href="`/counseling-records/${record.id}`" class="hover:text-gray-900 transition-colors">
                    Detail #{{ record.id }}
                </Link>
                <i class="pi pi-angle-right text-xs text-gray-400"></i>
                <span class="text-gray-900 font-semibold">Edit</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6 pb-16">
            <!-- Page Header Card with Student Context -->
            <div class="bg-white rounded-[24px] p-6 sm:p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#6366F1] to-[#4F46E5] text-white flex items-center justify-center font-bold text-lg shadow-sm flex-shrink-0">
                        {{ studentInitials }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-[#6366F1] border border-indigo-100">
                                <i class="pi pi-pencil text-[10px]"></i> Mode Edit
                            </span>
                            <span class="text-xs text-gray-400 font-medium">
                                ID Rekam: #{{ record.id }}
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">
                            Edit Catatan Konseling
                        </h1>
                        <p class="text-sm text-gray-600 mt-0.5">
                            Siswa: <span class="font-semibold text-gray-900">{{ record.student?.name || 'Siswa' }}</span>
                            <span v-if="record.student?.nis" class="text-gray-400"> (NIS: {{ record.student.nis }})</span>
                            <span v-if="record.student?.class_room" class="text-gray-400"> &bull; Kelas: {{ record.student.class_room.name }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 self-start md:self-center">
                    <Link
                        :href="`/counseling-records/${record.id}`"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-sm transition-all"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                        Kembali ke Detail
                    </Link>
                </div>
            </div>

            <!-- Error Notification Banner if Form has Errors -->
            <div
                v-if="form.hasErrors"
                class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 shadow-sm"
            >
                <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="pi pi-exclamation-triangle text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan pada isian formulir</h3>
                    <p class="text-xs text-red-600 mt-0.5">
                        Mohon periksa kembali kolom isian yang ditandai merah di bawah ini sebelum menyimpan perubahan.
                    </p>
                </div>
            </div>

            <!-- Main Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: Informasi Sesi & Status -->
                <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-indigo-50 text-[#6366F1] font-bold text-xs flex items-center justify-center flex-shrink-0">
                                1
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Informasi Sesi & Perkembangan</h2>
                                <p class="text-xs text-gray-500">Jadwal pelaksanaan, klasifikasi topik, dan status tahapan konseling.</p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">
                            Status saat ini: <strong class="ml-1 text-gray-900">{{ currentStatusLabel }}</strong>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Tanggal Jadwal -->
                        <div>
                            <label for="date_scheduled" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Tanggal Jadwal Sesi <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="date_scheduled"
                                    type="date"
                                    required
                                    v-model="form.date_scheduled"
                                    class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    :class="{ '!border-red-400 !bg-red-50/30': form.errors.date_scheduled }"
                                />
                            </div>
                            <p v-if="form.errors.date_scheduled" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-[11px]"></i>
                                {{ form.errors.date_scheduled }}
                            </p>
                            <p v-else class="mt-1 text-[11px] text-gray-400">
                                Tanggal rencana pertemuan tatap muka bimbingan.
                            </p>
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <label for="date_completed" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Tanggal Selesai Dilaksanakan
                            </label>
                            <div class="relative">
                                <input
                                    id="date_completed"
                                    type="date"
                                    v-model="form.date_completed"
                                    class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    :class="{ '!border-red-400 !bg-red-50/30': form.errors.date_completed }"
                                />
                            </div>
                            <p v-if="form.errors.date_completed" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-[11px]"></i>
                                {{ form.errors.date_completed }}
                            </p>
                            <p v-else class="mt-1 text-[11px] text-gray-400">
                                Kosongkan bila sesi masih dalam proses atau belum tuntas.
                            </p>
                        </div>

                        <!-- Kategori Konseling -->
                        <div>
                            <label for="category" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Kategori Konseling <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="category"
                                v-model="form.category"
                                required
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all cursor-pointer"
                                :class="{ '!border-red-400 !bg-red-50/30': form.errors.category }"
                            >
                                <option disabled value="">Pilih Kategori</option>
                                <option v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-[11px]"></i>
                                {{ form.errors.category }}
                            </p>
                        </div>

                        <!-- Status Konseling -->
                        <div>
                            <label for="status" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Status Perkembangan <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all cursor-pointer font-medium"
                                :class="{ '!border-red-400 !bg-red-50/30': form.errors.status }"
                            >
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }} ({{ opt.desc }})
                                </option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-[11px]"></i>
                                {{ form.errors.status }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Catatan Naratif Konseling -->
                <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-indigo-50 text-[#6366F1] font-bold text-xs flex items-center justify-center flex-shrink-0">
                                2
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Catatan & Narasi Konseling</h2>
                                <p class="text-xs text-gray-500">Mencakup latar belakang permasalahan, jalannya konseling, serta kesepakatan tindak lanjut.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="pi pi-lock text-[10px]"></i>
                            Terenkripsi AES-256
                        </span>
                    </div>

                    <!-- Alasan / Pokok Masalah -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="reason" class="block text-xs font-semibold text-gray-700">
                                Alasan / Pokok Masalah Pemanggilan
                            </label>
                            <span class="text-[11px] text-gray-400">Deskripsi singkat latar belakang bimbingan</span>
                        </div>
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            placeholder="Tuliskan alasan pemanggilan, keluhan wali kelas, atau pokok masalah kedisiplinan yang menjadi pemicu sesi ini..."
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all resize-y leading-relaxed"
                            :class="{ '!border-red-400 !bg-red-50/30': form.errors.reason }"
                        ></textarea>
                        <p v-if="form.errors.reason" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                            <i class="pi pi-exclamation-circle text-[11px]"></i>
                            {{ form.errors.reason }}
                        </p>
                    </div>

                    <!-- Ringkasan Sesi (Prominent) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="summary" class="block text-xs font-semibold text-gray-700">
                                Ringkasan Pembahasan & Hasil Sesi Konseling
                            </label>
                            <span class="text-[11px] font-medium text-[#6366F1] bg-indigo-50/70 px-2 py-0.5 rounded-md">
                                Konten Utama
                            </span>
                        </div>
                        <textarea
                            id="summary"
                            v-model="form.summary"
                            rows="6"
                            placeholder="Catat eksplorasi masalah bersama siswa, pengakuan siswa, faktor pemicu keluarga atau pertemanan, komitmen perbaikan sikap, serta poin konseling yang dicapai..."
                            class="w-full px-3.5 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all resize-y leading-relaxed font-normal"
                            :class="{ '!border-red-400 !bg-red-50/30': form.errors.summary }"
                        ></textarea>
                        <p v-if="form.errors.summary" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                            <i class="pi pi-exclamation-circle text-[11px]"></i>
                            {{ form.errors.summary }}
                        </p>
                        <p v-else class="mt-1 text-[11px] text-gray-400">
                            Memberikan gambaran komprehensif bagi guru pembimbing untuk penanganan siswa di masa mendatang.
                        </p>
                    </div>

                    <!-- Tindak Lanjut -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="follow_up_action" class="block text-xs font-semibold text-gray-700">
                                Rencana Tindak Lanjut & Monitoring
                            </label>
                            <span class="text-[11px] text-gray-400">Langkah konkret berikutnya</span>
                        </div>
                        <textarea
                            id="follow_up_action"
                            v-model="form.follow_up_action"
                            rows="3"
                            placeholder="Contoh: Pemantauan kehadiran bersama wali kelas selama 2 pekan, penjadwalan sesi evaluasi lanjutan, atau komunikasi berkala dengan orang tua..."
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all resize-y leading-relaxed"
                            :class="{ '!border-red-400 !bg-red-50/30': form.errors.follow_up_action }"
                        ></textarea>
                        <p v-if="form.errors.follow_up_action" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                            <i class="pi pi-exclamation-circle text-[11px]"></i>
                            {{ form.errors.follow_up_action }}
                        </p>
                    </div>
                </div>

                <!-- SECTION 3: Pengaturan Kerahasiaan (Access Control) -->
                <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-indigo-50 text-[#6366F1] font-bold text-xs flex items-center justify-center flex-shrink-0">
                                3
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Hak Akses & Tingkat Kerahasiaan</h2>
                                <p class="text-xs text-gray-500">Tentukan siapa saja pihak yang berwenang meninjau rekam konseling ini.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs text-gray-500 font-medium">
                            <i class="pi pi-shield text-[#6366F1]"></i> Kebijakan Privasi
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Pilihan Normal -->
                        <label
                            class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all"
                            :class="form.confidentiality_level === 'normal'
                                ? 'border-[#6366F1] bg-indigo-50/20 shadow-sm'
                                : 'border-gray-100 hover:border-gray-200 bg-gray-50/50'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                        <i class="pi pi-users text-xs"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">Normal (Standar)</span>
                                </div>
                                <input
                                    type="radio"
                                    name="confidentiality"
                                    value="normal"
                                    v-model="form.confidentiality_level"
                                    class="w-4 h-4 text-[#6366F1] focus:ring-[#6366F1]"
                                />
                            </div>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Dapat diakses oleh Guru BK bersangkutan, Kepala Sekolah, dan Super Admin untuk koordinasi dan pelaporan disiplin sekolah.
                            </p>
                        </label>

                        <!-- Pilihan Strict -->
                        <label
                            class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all"
                            :class="form.confidentiality_level === 'strict'
                                ? 'border-[#6366F1] bg-indigo-50/20 shadow-sm'
                                : 'border-gray-100 hover:border-gray-200 bg-gray-50/50'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">
                                        <i class="pi pi-lock text-xs"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">Strict (Sangat Rahasia)</span>
                                </div>
                                <input
                                    type="radio"
                                    name="confidentiality"
                                    value="strict"
                                    v-model="form.confidentiality_level"
                                    class="w-4 h-4 text-[#6366F1] focus:ring-[#6366F1]"
                                />
                            </div>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Khusus materi bimbingan sangat sensitif. Hanya dapat diakses dan dilihat oleh akun Konselor pencatat sesi ini.
                            </p>
                        </label>
                    </div>
                    <p v-if="form.errors.confidentiality_level" class="mt-1 text-xs font-medium text-red-600">
                        {{ form.errors.confidentiality_level }}
                    </p>
                </div>

                <!-- FORM ACTIONS: Bottom Bar -->
                <div class="bg-white rounded-[24px] p-5 sm:p-6 border border-gray-100 shadow-[0_2px_20px_#00000006] flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="pi pi-info-circle text-gray-400"></i>
                        <span>Pastikan informasi yang dimasukkan sudah sesuai dengan catatan resmi bimbingan konseling.</span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <Link
                            :href="`/counseling-records/${record.id}`"
                            class="w-full sm:w-auto text-center px-6 py-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-sm transition-all"
                        >
                            Batal
                        </Link>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-[0_4px_14px_#6366F140] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="form.processing" class="pi pi-spinner pi-spin text-xs"></i>
                            <i v-else class="pi pi-check text-xs"></i>
                            <span>{{ form.processing ? 'Menyimpan Perubahan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
