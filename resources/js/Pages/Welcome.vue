<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { motion } from 'motion-v';

const page = usePage();
const isAuthenticated = ref(!!(page.props.auth as any)?.user);

// Mobile navigation state
const isMobileMenuOpen = ref(false);

// Interactive Mockup Tab State
const activeMockupTab = ref<'radar' | 'events' | 'counseling'>('radar');

// FAQ Accordion State
const activeFaqIndex = ref<number | null>(0);

const toggleFaq = (index: number) => {
    activeFaqIndex.value = activeFaqIndex.value === index ? null : index;
};

// Selected Role Showcase
const selectedRole = ref<'tatib' | 'bk' | 'walikelas' | 'kepsek'>('tatib');

const roleDetails = {
    tatib: {
        title: 'Tim Tata Tertib Sekolah',
        badge: 'Penegakan Aturan & Sanksi',
        desc: 'Mencatat pelanggaran harian, memantau batas akumulasi poin siswa, serta mengelola master aturan sanksi sesuai pedoman tata tertib sekolah.',
        features: [
            'Input cepat kejadian pelanggaran dan poin berdasar kode master',
            'Pemantauan status eksekusi sanksi dan tindak lanjut',
            'Penerbitan kartu disiplin siswa dengan riwayat terinci',
            'Pencatatan apresiasi prestasi untuk pengurangan poin bersih',
        ],
        icon: 'pi pi-shield',
    },
    bk: {
        title: 'Guru Bimbingan Konseling (BK)',
        badge: 'Pembinaan & Pendampingan',
        desc: 'Mengelola penanganan kasus sensitif secara konfidensial, menjadwalkan sesi konseling individual, dan memantau komitmen kontrak perilaku siswa.',
        features: [
            'Akses catatan konseling yang dilindungi hak akses khusus',
            'Deteksi dini siswa dengan akumulasi poin risiko tinggi (Radar Early Warning)',
            'Penyusunan rencana tindak lanjut dan rekam perkembangan perilaku',
            'Integrasi pemanggilan orang tua untuk koordinasi pembinaan',
        ],
        icon: 'pi pi-heart',
    },
    walikelas: {
        title: 'Wali Kelas & Guru Pengajar',
        badge: 'Monitoring Kelas Binaan',
        desc: 'Mendapatkan visibilitas lengkap terhadap catatan kedisiplinan dan pencapaian prestasi siswa di kelas binaan tanpa terbebani rekap manual.',
        features: [
            'Laporan rekapitulasi poin kedisiplinan kelas binaan',
            'Input apresiasi prestasi akademik dan non-akademik siswa',
            'Notifikasi jika terdapat siswa binaan yang membutuhkan perhatian khusus',
            'Penyediaan data objektif untuk bahan evaluasi rapor siswa',
        ],
        icon: 'pi pi-users',
    },
    kepsek: {
        title: 'Kepala Sekolah',
        badge: 'Supervisi & Keputusan Strategis',
        desc: 'Memantau tren iklim kedisiplinan sekolah secara makro, menyetujui langkah sanksi tingkat akhir, dan memverifikasi arsip semester.',
        features: [
            'Dashboard analitik tren pelanggaran vs prestasi per semester',
            'Monitoring performa pembinaan antar tingkatan kelas',
            'Persetujuan sanksi tingkat berat (konferensi kasus, skorsing, pemindahan)',
            'Audit log aktivitas pengguna untuk transparansi dan kepatuhan sistem',
        ],
        icon: 'pi pi-chart-line',
    },
};

const faqs = [
    {
        q: 'Apakah data siswa dan master pelanggaran dari Excel lama bisa diimpor ke sistem?',
        a: 'Ya. DISIPLIN PRO menyediakan modul migrasi data spreadsheet (.xlsx) lengkap dengan fitur unduh template resmi, validasi skema otomatis, pratinjau data sebelum disimpan, dan penanganan duplikasi data.',
    },
    {
        q: 'Bagaimana privasi dan kerahasiaan catatan konseling siswa dilindungi?',
        a: 'Sistem menerapkan Role-Based Access Control (RBAC) yang ketat. Catatan bimbingan konseling dan kronologi kasus pribadi hanya dapat diakses oleh Guru BK yang berwenang dan Kepala Sekolah. Guru umum tidak dapat membuka data rahasia ini.',
    },
    {
        q: 'Bagaimana sistem memperhitungkan prestasi yang diraih oleh siswa?',
        a: 'Sistem menggunakan Point Engine terpadu. Setiap pelanggaran menambah akumulasi poin (+), sedangkan setiap prestasi siswa mengurangi poin bersih (-). Hal ini mendorong motivasi positif siswa untuk memperbaiki perilaku mereka.',
    },
    {
        q: 'Apakah surat panggilan orang tua dan kartu disiplin dapat langsung dicetak?',
        a: 'Ya. Tersedia generator dokumen PDF otomatis dengan tata letak kop surat resmi sekolah, nomor surat otomatis, identitas siswa, rincian kasus, dan tanda tangan pejabat yang berwenang.',
    },
    {
        q: 'Apa yang terjadi dengan data kedisiplinan saat penutupan semester?',
        a: 'Pada akhir semester, Super Admin atau Tim Tatib dapat menjalankan fitur Tutup Semester. Sistem akan membuat snapshot permanen untuk arsip riwayat, mereset poin berjalan untuk semester baru, dan mempertahankan seluruh jejak audit secara aman.',
    },
];
</script>

<template>
    <Head title="DISIPLIN PRO - Sistem Administrasi Tata Tertib & Konseling Siswa" />

    <div class="min-h-screen bg-[#F8F9FA] text-gray-900 font-sans selection:bg-[#6366F1]/20 selection:text-[#4F46E5]">
        <!-- ========================================================================= -->
        <!-- TOP STICKY NAVBAR -->
        <!-- ========================================================================= -->
        <header class="sticky top-0 z-50 backdrop-blur-md bg-white/85 border-b border-gray-100 transition-all">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#6366F1] text-white font-bold text-base flex items-center justify-center shadow-sm">
                        SD
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight text-gray-900 flex items-center gap-1.5">
                            DISIPLIN <span class="text-[#6366F1]">PRO</span>
                        </span>
                        <p class="text-[11px] font-medium text-gray-400 -mt-1 hidden sm:block">
                            Tata Tertib & Karakter Siswa
                        </p>
                    </div>
                </div>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-7 text-xs font-semibold text-gray-600">
                    <a href="#features" class="hover:text-[#6366F1] transition-colors">Fitur Unggulan</a>
                    <a href="#comparison" class="hover:text-[#6366F1] transition-colors">Masalah & Solusi</a>
                    <a href="#workflow" class="hover:text-[#6366F1] transition-colors">Alur Sistem</a>
                    <a href="#roles" class="hover:text-[#6366F1] transition-colors">Modul Peran</a>
                    <a href="#faq" class="hover:text-[#6366F1] transition-colors">FAQ</a>
                </nav>

                <!-- Desktop Action Button -->
                <div class="hidden md:flex items-center gap-3">
                    <Link
                        v-if="isAuthenticated"
                        href="/dashboard"
                        class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-[0_4px_14px_#6366F140] flex items-center gap-2"
                    >
                        <span>Buka Dashboard</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                    <Link
                        v-else
                        href="/login"
                        class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-[0_4px_14px_#6366F140] flex items-center gap-2"
                    >
                        <span>Masuk ke Sistem</span>
                        <i class="pi pi-sign-in text-[10px]"></i>
                    </Link>
                </div>

                <!-- Mobile Hamburger Button -->
                <button
                    type="button"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    class="md:hidden p-2 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                    aria-label="Toggle Navigation"
                >
                    <i :class="isMobileMenuOpen ? 'pi pi-times' : 'pi pi-bars'" class="text-lg"></i>
                </button>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div
                v-if="isMobileMenuOpen"
                class="md:hidden border-t border-gray-100 bg-white px-6 py-5 space-y-4 shadow-lg animate-in fade-in duration-200"
            >
                <div class="flex flex-col space-y-3 text-sm font-semibold text-gray-600">
                    <a href="#features" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#6366F1]">Fitur Unggulan</a>
                    <a href="#comparison" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#6366F1]">Masalah & Solusi</a>
                    <a href="#workflow" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#6366F1]">Alur Sistem</a>
                    <a href="#roles" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#6366F1]">Modul Peran</a>
                    <a href="#faq" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#6366F1]">FAQ</a>
                </div>
                <div class="pt-3 border-t border-gray-100">
                    <Link
                        v-if="isAuthenticated"
                        href="/dashboard"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-full bg-[#6366F1] text-white text-xs font-semibold shadow-sm"
                    >
                        <span>Buka Dashboard</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                    <Link
                        v-else
                        href="/login"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-full bg-[#6366F1] text-white text-xs font-semibold shadow-sm"
                    >
                        <span>Masuk ke Sistem</span>
                        <i class="pi pi-sign-in text-[10px]"></i>
                    </Link>
                </div>
            </div>
        </header>

        <!-- ========================================================================= -->
        <!-- HERO SECTION -->
        <!-- ========================================================================= -->
        <section class="relative pt-12 pb-20 sm:pt-16 sm:pb-28 overflow-hidden">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8">
                <!-- Center Hero Header -->
                <div class="max-w-3xl mx-auto text-center space-y-5">
                    <!-- Badge -->
                    <motion.div
                        :initial="{ opacity: 0, y: -10 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.4 }"
                    >
                        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-semibold bg-indigo-50 text-[#6366F1] border border-indigo-100 shadow-2xs">
                            <i class="pi pi-verified mr-2 text-[11px]"></i>
                            Sistem Administrasi Tata Tertib & Konseling Siswa Terpadu
                        </span>
                    </motion.div>

                    <!-- Title -->
                    <motion.h1
                        :initial="{ opacity: 0, y: 15 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.5, delay: 0.1 }"
                        class="text-3xl sm:text-5xl font-extrabold text-gray-900 tracking-tight leading-[1.18]"
                    >
                        Transformasi Digital Kedisiplinan & Pembinaan Karakter Siswa
                    </motion.h1>

                    <!-- Description -->
                    <motion.p
                        :initial="{ opacity: 0, y: 15 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.5, delay: 0.2 }"
                        class="text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl mx-auto font-normal"
                    >
                        Platform terpadu modern pengganti spreadsheet manual untuk Tim Tata Tertib, Guru BK, Wali Kelas, dan Kepala Sekolah dalam merekam kejadian disiplin, apresiasi prestasi, radar deteksi dini, dan penanganan bimbingan konseling terarah.
                    </motion.p>

                    <!-- CTAs -->
                    <motion.div
                        :initial="{ opacity: 0, y: 15 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.5, delay: 0.3 }"
                        class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3"
                    >
                        <Link
                            v-if="isAuthenticated"
                            href="/dashboard"
                            class="w-full sm:w-auto px-7 py-3 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-[0_4px_14px_#6366F140] flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>Buka Dashboard Operasional</span>
                            <i class="pi pi-arrow-right text-[11px]"></i>
                        </Link>
                        <Link
                            v-else
                            href="/login"
                            class="w-full sm:w-auto px-7 py-3 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-[0_4px_14px_#6366F140] flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>Masuk ke Sistem DISIPLIN PRO</span>
                            <i class="pi pi-sign-in text-[11px]"></i>
                        </Link>
                        <a
                            href="#workflow"
                            class="w-full sm:w-auto px-6 py-3 rounded-full bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold transition-all shadow-2xs flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="pi pi-compass text-[11px] text-gray-400"></i>
                            <span>Pelajari Alur Kerja</span>
                        </a>
                    </motion.div>
                </div>

                <!-- ================================================================= -->
                <!-- HERO PRODUCT INTERFACE MOCKUP (INTERACTIVE) -->
                <!-- ================================================================= -->
                <motion.div
                    :initial="{ opacity: 0, y: 30 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.6, delay: 0.35 }"
                    class="mt-12 sm:mt-16 max-w-5xl mx-auto"
                >
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-[0_2px_20px_#00000006] overflow-hidden">
                        <!-- Mockup Top Window Bar -->
                        <div class="px-5 py-3.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span>
                                <span class="ml-2 font-mono text-[11px] text-gray-400 hidden sm:inline-block">
                                    disiplin-pro.sekolah/dashboard
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Semester Genap 2026/2027 Aktif
                                </span>
                            </div>
                        </div>

                        <!-- Mockup Inner Content Area -->
                        <div class="p-6 sm:p-8 space-y-6">
                            <!-- Banner -->
                            <div class="rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-600 to-indigo-800 p-6 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-bold tracking-widest uppercase text-blue-200">
                                        Tim Tata Tertib & Bimbingan Konseling
                                    </span>
                                    <h3 class="text-lg sm:text-xl font-bold tracking-tight">
                                        Penegakan Tata Tertib & Rekapitulasi Disiplin
                                    </h3>
                                    <p class="text-xs text-blue-100/90 max-w-xl">
                                        Rekam kejadian harian, pantau radar deteksi dini, dan kelola penanganan tindak lanjut secara terpadu.
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <span class="px-4 py-2 rounded-full bg-white text-[#001AFF] text-xs font-bold shadow-xs inline-flex items-center gap-1.5">
                                        <i class="pi pi-plus-circle text-xs"></i>
                                        <span>Catat Kejadian Baru</span>
                                    </span>
                                </div>
                            </div>

                            <!-- 4 KPI Metrics -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                                <div class="p-4 rounded-2xl bg-gray-50/70 border border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-medium text-gray-500">Total Siswa Aktif</span>
                                        <i class="pi pi-users text-gray-400 text-xs"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">485</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Terbagi di 14 Rombel</p>
                                </div>
                                <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-medium text-rose-600">Pelanggaran Hari Ini</span>
                                        <i class="pi pi-exclamation-triangle text-rose-500 text-xs"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-rose-700 mt-1">2</p>
                                    <p class="text-[10px] text-rose-500 mt-0.5">Terkonfirmasi & tercatat</p>
                                </div>
                                <div class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-medium text-emerald-600">Prestasi Hari Ini</span>
                                        <i class="pi pi-star text-emerald-500 text-xs"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-emerald-700 mt-1">3</p>
                                    <p class="text-[10px] text-emerald-500 mt-0.5">Poin pengurang aktif</p>
                                </div>
                                <div class="p-4 rounded-2xl bg-amber-50/50 border border-amber-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-medium text-amber-600">Tindak Lanjut Aktif</span>
                                        <i class="pi pi-clock text-amber-500 text-xs"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-amber-700 mt-1">5</p>
                                    <p class="text-[10px] text-amber-500 mt-0.5">Dalam pemantauan BK</p>
                                </div>
                            </div>

                            <!-- Interactive Tab Widget Inside Mockup -->
                            <div class="space-y-4 pt-2">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="activeMockupTab = 'radar'"
                                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                                            :class="activeMockupTab === 'radar' ? 'bg-[#001AFF] text-white' : 'text-gray-500 hover:text-gray-800'"
                                        >
                                            Radar Early Warning
                                        </button>
                                        <button
                                            type="button"
                                            @click="activeMockupTab = 'events'"
                                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                                            :class="activeMockupTab === 'events' ? 'bg-[#001AFF] text-white' : 'text-gray-500 hover:text-gray-800'"
                                        >
                                            Kejadian Terbaru
                                        </button>
                                        <button
                                            type="button"
                                            @click="activeMockupTab = 'counseling'"
                                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                                            :class="activeMockupTab === 'counseling' ? 'bg-[#001AFF] text-white' : 'text-gray-500 hover:text-gray-800'"
                                        >
                                            Pembinaan Konseling
                                        </button>
                                    </div>
                                    <span class="text-[11px] text-gray-400 hidden sm:inline">Data Real-Time</span>
                                </div>

                                <!-- Tab 1: Radar Early Warning Content -->
                                <div v-if="activeMockupTab === 'radar'" class="space-y-2.5">
                                    <div class="p-3.5 rounded-xl border border-rose-100 bg-rose-50/30 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 font-bold text-xs flex items-center justify-center">
                                                AP
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900">Agus Pratama (NIS: 1001)</p>
                                                <p class="text-[11px] text-gray-500">Kelas X MIPA 1 · Sering terlambat dan atribut tidak lengkap</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                                ATTENTION · 65 POIN
                                            </span>
                                            <span class="text-xs font-semibold text-[#001AFF]">Jadwalkan Konseling →</span>
                                        </div>
                                    </div>

                                    <div class="p-3.5 rounded-xl border border-amber-100 bg-amber-50/20 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 font-bold text-xs flex items-center justify-center">
                                                DP
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900">Dimas Prasetyo (NIS: 1042)</p>
                                                <p class="text-[11px] text-gray-500">Kelas XI IPS 2 · Akumulasi ketidakhadiran tanpa izin</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 border border-orange-200">
                                                WARNING · 85 POIN
                                            </span>
                                            <span class="text-xs font-semibold text-[#001AFF]">Panggil Ortu →</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 2: Events Content -->
                                <div v-if="activeMockupTab === 'events'" class="space-y-2.5">
                                    <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/50 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                                                <i class="pi pi-arrow-up"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900">Terlambat Masuk Jam Pertama (+5 Poin)</p>
                                                <p class="text-[11px] text-gray-500">Siswa: Siti Fatimah · Dicatat oleh: Pak Tatib</p>
                                            </div>
                                        </div>
                                        <span class="text-[11px] text-gray-400">Hari ini, 07:20 WIB</span>
                                    </div>

                                    <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/50 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                                                <i class="pi pi-arrow-down"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900">Juara 1 OSN Matematika Tingkat Kota (-25 Poin)</p>
                                                <p class="text-[11px] text-gray-500">Siswa: Budi Wicaksono · Dicatat oleh: Bu Ratna (Wali Kelas)</p>
                                            </div>
                                        </div>
                                        <span class="text-[11px] text-emerald-600 font-medium">Apresiasi Aktif</span>
                                    </div>
                                </div>

                                <!-- Tab 3: Counseling Content -->
                                <div v-if="activeMockupTab === 'counseling'" class="space-y-2.5">
                                    <div class="p-3.5 rounded-xl border border-indigo-100 bg-indigo-50/30 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-[#001AFF] flex items-center justify-center text-xs font-bold">
                                                BK
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900">Konseling Individual & Kontrak Komitmen Belajar</p>
                                                <p class="text-[11px] text-gray-500">Konselor: Dra. Nurhayati · Siswa: Rian Hidayat · Hasil: Komitmen Disepakati</p>
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Dalam Pemantauan
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </motion.div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PROBLEM VS SOLUTION (SPREADSHEET COMPARISON) -->
        <!-- ========================================================================= -->
        <section id="comparison" class="py-20 bg-white border-y border-gray-100">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="text-xs font-bold text-[#6366F1] uppercase tracking-wider">
                        Evaluasi Sistem & Kebutuhan Sekolah
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Mengapa Beralih dari Spreadsheet Manual ke DISIPLIN PRO?
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">
                        Buku catatan kertas dan spreadsheet 18 lembar kerja memiliki keterbatasan kapasitas, rawan korup rumus, dan tidak menjamin privasi data konseling siswa.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
                    <!-- Spreadsheet Lama (Problem Card) -->
                    <div class="p-6 sm:p-8 rounded-[24px] bg-rose-50/40 border border-rose-100 space-y-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center">
                                <i class="pi pi-times-circle text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Metode Spreadsheet Manual</h3>
                                <p class="text-[11px] text-rose-600 font-medium">Batas baris, rawan korup, tanpa kontrol akses</p>
                            </div>
                        </div>

                        <ul class="space-y-3 text-xs text-gray-600">
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-times text-rose-500 text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Kapasitas Terbatas:</strong> Dibatasi formula statis (500 siswa, 3.000 kejadian) yang memperlambat kinerja file.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-times text-rose-500 text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Rumus Mudah Tertimpa:</strong> Kesalahan ketik satu sel dapat merusak kalkulasi akumulasi poin seluruh semester.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-times text-rose-500 text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Tanpa Privasi Konseling:</strong> Siapapun yang membuka file dapat membaca catatan pembinaan pribadi siswa.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-times text-rose-500 text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Rekapitulasi Manual:</strong> Pembuatan surat panggilan orang tua dan laporan bulanan memakan waktu berhari-hari.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- DISIPLIN PRO (Solution Card) -->
                    <div class="p-6 sm:p-8 rounded-[24px] bg-indigo-50/40 border border-indigo-100 space-y-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-[#6366F1] text-white flex items-center justify-center shadow-2xs">
                                <i class="pi pi-check-circle text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Solusi DISIPLIN PRO</h3>
                                <p class="text-[11px] text-[#6366F1] font-medium">Database relasional, terenkripsi, terotomasi</p>
                            </div>
                        </div>

                        <ul class="space-y-3 text-xs text-gray-600">
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-check text-[#6366F1] text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Kapasitas Skalabel:</strong> Berbasis database PostgreSQL / MySQL tanpa batasan kuota baris buatan.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-check text-[#6366F1] text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Kalkulasi Point Engine Otomatis:</strong> Perhitungan poin pelanggaran dan prestasi tervalidasi di level server.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-check text-[#6366F1] text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Kerahasiaan Terjaga (RBAC):</strong> Hak akses khusus memisahkan data konseling Guru BK dari guru umum.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="pi pi-check text-[#6366F1] text-xs mt-0.5 shrink-0"></i>
                                <span><strong>Surat & Laporan 1 Klik:</strong> Cetak langsung Kartu Disiplin Siswa dan Surat Panggilan dengan format kop resmi.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- CORE CAPABILITIES / FITUR UNGGULAN -->
        <!-- ========================================================================= -->
        <section id="features" class="py-20">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="text-xs font-bold text-[#6366F1] uppercase tracking-wider">
                        Kemampuan Utama Aplikasi
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Fitur Spesifik untuk Tata Kelola Kedisiplinan Sekolah
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">
                        Dirancang sesuai proses bisnis nyata di sekolah untuk menyelaraskan penegakan aturan dengan pendekatan edukatif bimbingan konseling.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Feature 1: Point Engine -->
                    <div class="bg-white rounded-[24px] p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-3 hover:border-indigo-100 transition-all">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-[#6366F1] flex items-center justify-center">
                            <i class="pi pi-calculator text-base"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Point Engine & Poin Bersih</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Kalkulasi matematis transparan yang menjumlahkan akumulasi pelanggaran (+) dan mengurangkan poin prestasi (-) secara real-time.
                        </p>
                    </div>

                    <!-- Feature 2: Early Warning -->
                    <div class="bg-white rounded-[24px] p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-3 hover:border-indigo-100 transition-all">
                        <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i class="pi pi-radar text-base"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Radar Deteksi Dini (Early Warning)</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Mengelompokkan siswa secara otomatis ke status Attention, Warning, atau Critical sebelum akumulasi poin mencapai sanksi berat.
                        </p>
                    </div>

                    <!-- Feature 3: Konseling BK -->
                    <div class="bg-white rounded-[24px] p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-3 hover:border-indigo-100 transition-all">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="pi pi-comments text-base"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Manajemen Konseling & Kasus</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Dokumentasi sesi bimbingan konseling individual, jadwal pertemuan berkala, kontrak komitmen perilaku, dan catatan hasil evaluasi.
                        </p>
                    </div>

                    <!-- Feature 4: Generator Surat -->
                    <div class="bg-white rounded-[24px] p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-3 hover:border-indigo-100 transition-all">
                        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i class="pi pi-file-pdf text-base"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Generator Surat & Kartu Disiplin</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Penerbitan otomatis Surat Panggilan Orang Tua, Surat Peringatan (SP), dan Kartu Kedisiplinan Siswa dengan kop resmi dan tanda tangan.
                        </p>
                    </div>

                    <!-- Feature 5: Import Excel -->
                    <div class="bg-white rounded-[24px] p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-3 hover:border-indigo-100 transition-all">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="pi pi-file-excel text-base"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Migrasi & Template Spreadsheet</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Integrasi impor massal dari file .xlsx lengkap dengan template resmi, verifikasi skema, dan pratinjau data interaktif sebelum disimpan.
                        </p>
                    </div>

                    <!-- Feature 6: Tutup Semester -->
                    <div class="bg-white rounded-[24px] p-7 border border-gray-100 shadow-[0_2px_20px_#00000006] space-y-3 hover:border-indigo-100 transition-all">
                        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="pi pi-history text-base"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Rekapitulasi & Tutup Semester</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Laporan bulanan per kelas, analitik tren semester, serta pembekuan snapshot data historis kedisiplinan yang tersimpan aman selamanya.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- WORKFLOW SECTION (4 TAHAP KEDISIPLINAN) -->
        <!-- ========================================================================= -->
        <section id="workflow" class="py-20 bg-white border-y border-gray-100">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="text-xs font-bold text-[#6366F1] uppercase tracking-wider">
                        Siklus Kerja Terpadu
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Alur Manajemen Kedisiplinan 4 Tahap
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">
                        Sistem mengawal proses mulai dari pencatatan harian di lapangan hingga pengarsipan resmi pada akhir semester.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Step 1 -->
                    <div class="p-6 rounded-[24px] bg-[#F8F9FA] border border-gray-100 space-y-4 relative">
                        <div class="w-9 h-9 rounded-full bg-[#6366F1] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                            1
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Pencatatan Kejadian</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Guru piket, wali kelas, atau tim tatib merekam kejadian pelanggaran atau apresiasi prestasi siswa secara cepat sesuai kode master.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="p-6 rounded-[24px] bg-[#F8F9FA] border border-gray-100 space-y-4 relative">
                        <div class="w-9 h-9 rounded-full bg-[#6366F1] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                            2
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Kalkulasi & Deteksi Dini</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Point Engine menghitung poin bersih dan otomatis menaikkan indikator radar peringatan jika siswa mendekati ambang batas sanksi.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="p-6 rounded-[24px] bg-[#F8F9FA] border border-gray-100 space-y-4 relative">
                        <div class="w-9 h-9 rounded-full bg-[#6366F1] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                            3
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Pembinaan & Konseling</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Guru BK menerima notifikasi radar dan memfasilitasi sesi konseling individual, penyusunan kontrak perilaku, dan pemantauan berkala.
                        </p>
                    </div>

                    <!-- Step 4 -->
                    <div class="p-6 rounded-[24px] bg-[#F8F9FA] border border-gray-100 space-y-4 relative">
                        <div class="w-9 h-9 rounded-full bg-[#6366F1] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                            4
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Pelaporan & Arsip</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Dokumen surat resmi diterbitkan jika diperlukan koordinasi orang tua, dilanjutkan dengan rekapitulasi kelas dan penutupan semester.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- ROLE-BASED ACCESS CONTROL (MODUL PERAN) -->
        <!-- ========================================================================= -->
        <section id="roles" class="py-20">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="text-xs font-bold text-[#6366F1] uppercase tracking-wider">
                        Hak Akses Proporsional (RBAC)
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Tanggung Jawab Terarah untuk Setiap Pihak Sekolah
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">
                        Sistem memastikan setiap pengguna hanya mengakses fitur dan informasi yang relevan dengan tugas dan kewenangannya.
                    </p>
                </div>

                <!-- Role Selector Tabs -->
                <div class="flex items-center justify-center gap-2 flex-wrap max-w-2xl mx-auto">
                    <button
                        type="button"
                        @click="selectedRole = 'tatib'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="selectedRole === 'tatib' ? 'bg-[#6366F1] text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        Tim Tata Tertib
                    </button>
                    <button
                        type="button"
                        @click="selectedRole = 'bk'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="selectedRole === 'bk' ? 'bg-[#6366F1] text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        Guru BK
                    </button>
                    <button
                        type="button"
                        @click="selectedRole = 'walikelas'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="selectedRole === 'walikelas' ? 'bg-[#6366F1] text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        Wali Kelas & Guru
                    </button>
                    <button
                        type="button"
                        @click="selectedRole = 'kepsek'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="selectedRole === 'kepsek' ? 'bg-[#6366F1] text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        Kepala Sekolah
                    </button>
                </div>

                <!-- Role Detail Card -->
                <div class="max-w-4xl mx-auto bg-white rounded-[24px] p-8 border border-gray-100 shadow-[0_2px_20px_#00000006]">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-[#6366F1] flex items-center justify-center text-lg shadow-2xs">
                                <i :class="roleDetails[selectedRole].icon"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">{{ roleDetails[selectedRole].title }}</h3>
                                <p class="text-xs text-gray-500">{{ roleDetails[selectedRole].desc }}</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-[#6366F1] border border-indigo-100 shrink-0">
                            {{ roleDetails[selectedRole].badge }}
                        </span>
                    </div>

                    <div class="pt-6">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Kewenangan Utama</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="(feat, idx) in roleDetails[selectedRole].features"
                                :key="idx"
                                class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50/70 border border-gray-100 text-xs text-gray-700"
                            >
                                <i class="pi pi-check text-emerald-600 text-xs mt-0.5 shrink-0"></i>
                                <span>{{ feat }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- FAQ SECTION -->
        <!-- ========================================================================= -->
        <section id="faq" class="py-20 bg-white border-y border-gray-100">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="text-xs font-bold text-[#6366F1] uppercase tracking-wider">
                        Tanya Jawab Operasional
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Pertanyaan Umum Seputar DISIPLIN PRO
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">
                        Informasi praktis tentang migrasi data, privasi konseling, dan integrasi operasional sekolah.
                    </p>
                </div>

                <div class="max-w-3xl mx-auto space-y-3">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="bg-[#F8F9FA] rounded-2xl border border-gray-200/80 overflow-hidden transition-all"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(index)"
                            class="w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 cursor-pointer select-none"
                        >
                            <span class="text-xs sm:text-sm font-bold text-gray-900 leading-relaxed">
                                {{ faq.q }}
                            </span>
                            <i
                                :class="activeFaqIndex === index ? 'pi pi-chevron-up text-[#6366F1]' : 'pi pi-chevron-down text-gray-400'"
                                class="text-xs shrink-0 transition-transform"
                            ></i>
                        </button>
                        <div
                            v-if="activeFaqIndex === index"
                            class="px-6 pb-5 pt-1 text-xs text-gray-600 leading-relaxed border-t border-gray-100/60"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- FINAL CTA SECTION -->
        <!-- ========================================================================= -->
        <section class="py-20 bg-[#F8F9FA]">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8">
                <div class="max-w-4xl mx-auto rounded-[24px] bg-gradient-to-r from-blue-700 via-indigo-600 to-indigo-800 p-8 sm:p-12 text-center text-white space-y-6 shadow-md">
                    <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-semibold bg-white/10 text-white border border-white/20">
                        Siap Digunakan di Sekolah Anda
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight max-w-xl mx-auto leading-tight">
                        Tingkatkan Akuntabilitas & Pembinaan Siswa Hari Ini
                    </h2>
                    <p class="text-xs sm:text-sm text-blue-100 max-w-lg mx-auto leading-relaxed">
                        Hentikan pencatatan manual yang rentan korup. Wujudkan tata kelola kedisiplinan yang transparan, aman, dan mendidik.
                    </p>
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <Link
                            v-if="isAuthenticated"
                            href="/dashboard"
                            class="w-full sm:w-auto px-8 py-3 rounded-full bg-white text-[#001AFF] text-xs font-bold hover:bg-gray-50 transition-all shadow-sm"
                        >
                            Masuk ke Dashboard
                        </Link>
                        <Link
                            v-else
                            href="/login"
                            class="w-full sm:w-auto px-8 py-3 rounded-full bg-white text-[#001AFF] text-xs font-bold hover:bg-gray-50 transition-all shadow-sm"
                        >
                            Masuk ke Sistem DISIPLIN PRO
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- FOOTER -->
        <!-- ========================================================================= -->
        <footer class="bg-white border-t border-gray-100 py-12 text-xs text-gray-500">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-8 space-y-8">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border-b border-gray-100 pb-8">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-[#6366F1] text-white font-bold text-xs flex items-center justify-center">
                                SD
                            </div>
                            <span class="text-sm font-bold text-gray-900 tracking-tight">
                                DISIPLIN <span class="text-[#6366F1]">PRO</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 max-w-md">
                            Sistem Administrasi Tata Tertib, Bimbingan Konseling, dan Pembinaan Karakter Siswa Berbasis Domain Web.
                        </p>
                    </div>

                    <div class="flex items-center gap-6 text-xs font-medium text-gray-600">
                        <a href="#features" class="hover:text-[#6366F1] transition-colors">Fitur</a>
                        <a href="#comparison" class="hover:text-[#6366F1] transition-colors">Perbandingan</a>
                        <a href="#workflow" class="hover:text-[#6366F1] transition-colors">Alur</a>
                        <a href="#roles" class="hover:text-[#6366F1] transition-colors">Peran</a>
                        <a href="#faq" class="hover:text-[#6366F1] transition-colors">FAQ</a>
                        <Link href="/login" class="hover:text-[#6366F1] transition-colors font-semibold">Login</Link>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-gray-400">
                    <p>© 2026 DISIPLIN PRO. Hak cipta dilindungi undang-undang.</p>
                    <p>Dibangun di atas arsitektur Laravel, Inertia.js, Vue 3, dan Tailwind CSS.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
