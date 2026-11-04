<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

interface StudentItem {
    id: number;
    name: string;
    nis: string;
    parent_name?: string | null;
    parent_phone?: string | null;
    class_room?: {
        id: number;
        name: string;
    } | null;
}

const props = defineProps<{
    students: StudentItem[];
    categories: string[];
    default_student_id?: number | string | null;
}>();

const studentSearch = ref('');

const filteredStudents = computed(() => {
    if (!studentSearch.value) return props.students;
    const q = studentSearch.value.toLowerCase().trim();
    return props.students.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.nis.toLowerCase().includes(q) ||
        (s.class_room?.name && s.class_room.name.toLowerCase().includes(q))
    );
});

const todayDate = new Date().toISOString().substring(0, 10);

const form = useForm({
    student_id: props.default_student_id ? Number(props.default_student_id) : '',
    date_scheduled: todayDate,
    category: 'Konseling',
    reason: '',
    summary: '',
    follow_up_action: '',
    confidentiality_level: 'normal',
});

const selectedStudent = computed(() => {
    if (!form.student_id) return null;
    return props.students.find(s => s.id === Number(form.student_id)) || null;
});

const studentInitials = computed(() => {
    if (!selectedStudent.value) return 'BK';
    const parts = selectedStudent.value.name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return selectedStudent.value.name.slice(0, 2).toUpperCase();
});

const submit = () => {
    form.post('/counseling-records', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Jadwalkan Sesi Konseling - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <!-- Top Breadcrumbs -->
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
                <span class="text-gray-900 font-semibold">Jadwalkan Konseling</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6 pb-16">
            <!-- Header Banner -->
            <div class="bg-white rounded-[24px] p-6 sm:p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-[#6366F1] border border-indigo-100">
                            <i class="pi pi-calendar-plus text-[10px]"></i> Sesi Baru
                        </span>
                        <span class="text-xs text-gray-400 font-medium">Bimbingan & Konseling Terpadu</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">
                        Jadwalkan Sesi Bimbingan Siswa
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Buat agenda pembinaan karakter, tetapkan topik konseling, dan lindungi catatan dengan enkripsi data.
                    </p>
                </div>

                <div class="shrink-0">
                    <Link
                        href="/counseling-records"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-sm transition-all"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                        Kembali ke Daftar
                    </Link>
                </div>
            </div>

            <!-- Error Banner -->
            <div
                v-if="form.hasErrors"
                class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 shadow-sm"
            >
                <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="pi pi-exclamation-triangle text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan isian formulir</h3>
                    <p class="text-xs text-red-600 mt-0.5">
                        Mohon periksa kembali kolom isian yang ditandai merah di bawah ini sebelum menyimpan jadwal.
                    </p>
                </div>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: Sasaran Siswa & Jadwal -->
                <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-indigo-50 text-[#6366F1] font-bold text-xs flex items-center justify-center flex-shrink-0">
                                1
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Siswa & Jadwal Pelaksanaan</h2>
                                <p class="text-xs text-gray-500">Pilih siswa yang akan dibina dan tentukan tanggal rencana konseling.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Student Preview Card if chosen -->
                    <div
                        v-if="selectedStudent"
                        class="p-4 rounded-2xl bg-indigo-50/30 border border-indigo-100 flex items-center justify-between gap-4 transition-all"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="w-11 h-11 rounded-xl bg-[#6366F1] text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs">
                                {{ studentInitials }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 truncate">{{ selectedStudent.name }}</h3>
                                <p class="text-xs text-gray-500 truncate">
                                    NIS: <span class="font-medium text-gray-700">{{ selectedStudent.nis }}</span>
                                    <span v-if="selectedStudent.class_room"> &bull; Kelas: <strong class="text-gray-800">{{ selectedStudent.class_room.name }}</strong></span>
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="form.student_id = ''"
                            class="text-xs font-semibold text-gray-500 hover:text-red-600 transition-colors shrink-0 px-2 py-1"
                        >
                            Ganti Siswa
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Student Selection Dropdown with Search Filter -->
                        <div class="sm:col-span-2" v-if="!selectedStudent">
                            <label for="student_id" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Pilih Siswa <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <div class="relative">
                                    <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    <input
                                        type="text"
                                        v-model="studentSearch"
                                        placeholder="Ketik nama siswa, NIS, atau kelas untuk memfilter..."
                                        class="w-full pl-9 pr-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                                <select
                                    id="student_id"
                                    v-model="form.student_id"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all cursor-pointer font-medium"
                                    :class="{ '!border-red-400 !bg-red-50/30': form.errors.student_id }"
                                >
                                    <option disabled value="">Pilih Siswa dari Daftar ({{ filteredStudents.length }} siswa)</option>
                                    <option v-for="student in filteredStudents" :key="student.id" :value="student.id">
                                        {{ student.name }} ({{ student.class_room ? student.class_room.name : 'Tanpa Kelas' }}) - NIS: {{ student.nis }}
                                    </option>
                                </select>
                            </div>
                            <p v-if="form.errors.student_id" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-[11px]"></i>
                                {{ form.errors.student_id }}
                            </p>
                        </div>

                        <!-- Tanggal Jadwal Sesi -->
                        <div>
                            <label for="date_scheduled" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Tanggal Jadwal Pertemuan <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="date_scheduled"
                                type="date"
                                required
                                v-model="form.date_scheduled"
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                :class="{ '!border-red-400 !bg-red-50/30': form.errors.date_scheduled }"
                            />
                            <p v-if="form.errors.date_scheduled" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-[11px]"></i>
                                {{ form.errors.date_scheduled }}
                            </p>
                            <p v-else class="mt-1 text-[11px] text-gray-400">
                                Tanggal pelaksanaan sesi tatap muka bimbingan.
                            </p>
                        </div>

                        <!-- Kategori Konseling -->
                        <div>
                            <label for="category" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Kategori Topik Bimbingan <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="category"
                                v-model="form.category"
                                required
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all cursor-pointer font-medium"
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
                    </div>
                </div>

                <!-- SECTION 2: Pokok Masalah & Pembahasan Awal -->
                <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-indigo-50 text-[#6366F1] font-bold text-xs flex items-center justify-center flex-shrink-0">
                                2
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Alasan & Catatan Kasus</h2>
                                <p class="text-xs text-gray-500">Latar belakang pemanggilan atau keluhan kedisiplinan yang memicu bimbingan.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="pi pi-lock text-[10px]"></i>
                            Terenkripsi AES-256
                        </span>
                    </div>

                    <!-- Alasan Pemanggilan -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="reason" class="block text-xs font-semibold text-gray-700">
                                Alasan / Pokok Masalah Bimbingan <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-gray-400">Deskripsi singkat pemicu konseling</span>
                        </div>
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            required
                            placeholder="Contoh: Sering terlambat datang ke sekolah, perselisihan dengan teman sekelas, atau keluhan dari wali kelas mengenai penurunan motivasi belajar..."
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all resize-y leading-relaxed"
                            :class="{ '!border-red-400 !bg-red-50/30': form.errors.reason }"
                        ></textarea>
                        <p v-if="form.errors.reason" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                            <i class="pi pi-exclamation-circle text-[11px]"></i>
                            {{ form.errors.reason }}
                        </p>
                    </div>

                    <!-- Ringkasan Pembahasan Awal / Hasil (Opsional) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="summary" class="block text-xs font-semibold text-gray-700">
                                Catatan Hasil Sesi (Opsional)
                            </label>
                            <span class="text-[11px] text-gray-400">Dapat dilengkapi setelah pertemuan selesai</span>
                        </div>
                        <textarea
                            id="summary"
                            v-model="form.summary"
                            rows="4"
                            placeholder="Kosongkan bila sesi baru diagendakan. Jika sesi sudah berlangsung langsung, catat hasil pembahasan di sini..."
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all resize-y leading-relaxed"
                            :class="{ '!border-red-400 !bg-red-50/30': form.errors.summary }"
                        ></textarea>
                        <p v-if="form.errors.summary" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                            <i class="pi pi-exclamation-circle text-[11px]"></i>
                            {{ form.errors.summary }}
                        </p>
                    </div>
                </div>

                <!-- SECTION 3: Hak Akses & Kerahasiaan -->
                <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-indigo-50 text-[#6366F1] font-bold text-xs flex items-center justify-center flex-shrink-0">
                                3
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Hak Akses & Tingkat Kerahasiaan</h2>
                                <p class="text-xs text-gray-500">Tentukan privasi rekam bimbingan sesuai sensitivitas permasalahan siswa.</p>
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
                                Dapat dipantau oleh Guru BK pembuat, Kepala Sekolah, dan Super Admin untuk koordinasi dan pelaporan disiplin sekolah.
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
                                Khusus materi bimbingan yang sangat sensitif. Hanya dapat diakses dan dilihat oleh akun Konselor pencatat sesi ini.
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="bg-white rounded-[24px] p-5 sm:p-6 border border-gray-100 shadow-[0_2px_20px_#00000006] flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="pi pi-info-circle text-gray-400"></i>
                        <span>Sesi yang dijadwalkan akan muncul di agenda pembinaan dan rekap riwayat konseling.</span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <Link
                            href="/counseling-records"
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
                            <i v-else class="pi pi-calendar-plus text-xs"></i>
                            <span>{{ form.processing ? 'Menyimpan Jadwal...' : 'Simpan Jadwal Konseling' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
