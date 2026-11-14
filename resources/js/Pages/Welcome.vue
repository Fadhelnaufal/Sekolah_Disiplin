<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { motion } from 'motion-v';

const page = usePage();
const isAuthenticated = ref(!!(page.props.auth as any)?.user);

// Mobile navigation toggle
const isMobileMenuOpen = ref(false);

// Overview interactive tabs (Section C)
const activeOverviewTab = ref<number>(0);
const overviewItems = [
    {
        title: 'Pencatatan Kejadian Terpadu',
        desc: 'Merekam pelanggaran harian dan capaian prestasi siswa secara cepat menggunakan kode master terstandar.',
        badge: 'Input Cepat & Akurat',
    },
    {
        title: 'Radar Deteksi Dini (Early Warning)',
        desc: 'Mengelompokkan siswa berisiko secara otomatis ke status Attention, Warning, atau Critical saat poin terakumulasi.',
        badge: 'Pencegahan Preventif',
    },
    {
        title: 'Pembinaan & Bimbingan Konseling (BK)',
        desc: 'Memfasilitasi sesi konseling individual, jadwal pertemuan berkala, dan komitmen kontrak perilaku tertutup.',
        badge: 'Kerahasiaan RBAC',
    },
    {
        title: 'Dokumentasi & Cetak Surat Otomatis',
        desc: 'Menerbitkan surat panggilan orang tua, surat peringatan, dan kartu disiplin siswa dengan kop sekolah resmi.',
        badge: 'Format Siap Cetak',
    },
];

// Interactive Role Switcher (Section F)
const activeRoleTab = ref<'tatib' | 'bk' | 'walikelas' | 'kepsek'>('tatib');
const roleData = {
    tatib: {
        roleName: 'Tim Tata Tertib Sekolah',
        badge: 'Penegakan Peraturan',
        desc: 'Mencatat pelanggaran harian, memantau batas akumulasi poin siswa, serta mengelola master aturan sanksi sesuai pedoman tata tertib sekolah.',
        points: [
            'Input kejadian pelanggaran dan poin berdasar kode master',
            'Monitoring status tindak lanjut dan penyelesaian sanksi',
            'Penerbitan kartu kedisiplinan siswa dengan rincian lengkap',
            'Pencatatan poin prestasi sebagai pengurang poin bersih',
        ],
    },
    bk: {
        roleName: 'Guru Bimbingan Konseling (BK)',
        badge: 'Pembinaan & Pendampingan',
        desc: 'Mengelola penanganan kasus sensitif secara konfidensial, menjadwalkan sesi konseling individual, dan memantau komitmen kontrak perilaku siswa.',
        points: [
            'Akses khusus catatan konseling terlindungi sistem RBAC',
            'Deteksi dini siswa dengan akumulasi poin risiko tinggi',
            'Penyusunan rencana tindak lanjut dan evaluasi berkala',
            'Koordinasi pemanggilan orang tua untuk pembinaan terarah',
        ],
    },
    walikelas: {
        roleName: 'Wali Kelas & Guru Pengajar',
        badge: 'Monitoring Kelas Binaan',
        desc: 'Mendapatkan visibilitas lengkap terhadap catatan kedisiplinan dan pencapaian prestasi siswa di kelas binaan tanpa terbebani rekap manual.',
        points: [
            'Laporan rekapitulasi poin kedisiplinan kelas binaan',
            'Input apresiasi prestasi akademik dan non-akademik siswa',
            'Notifikasi jika terdapat siswa yang butuh perhatian khusus',
            'Penyediaan data objektif untuk evaluasi rapor berkala',
        ],
    },
    kepsek: {
        roleName: 'Kepala Sekolah',
        badge: 'Supervisi & Kebijakan',
        desc: 'Memantau tren iklim kedisiplinan sekolah secara makro, menyetujui langkah sanksi tingkat akhir, dan memverifikasi arsip penutupan semester.',
        points: [
            'Dashboard analitik tren pelanggaran vs prestasi semester',
            'Pemantauan performa pembinaan antar tingkatan kelas',
            'Persetujuan sanksi tingkat berat dan konferensi kasus',
            'Audit log aktivitas sistem untuk transparansi operasional',
        ],
    },
};

// FAQ Accordion State (Section G)
const activeFaqIndex = ref<number | null>(0);
const toggleFaq = (index: number) => {
    activeFaqIndex.value = activeFaqIndex.value === index ? null : index;
};

const faqs = [
    {
        q: 'Apakah data siswa dan master tata tertib dari Excel bisa diimpor ke sistem?',
        a: 'Ya. DISIPLIN PRO menyediakan modul migrasi spreadsheet (.xlsx) lengkap dengan berkas template resmi, validasi skema otomatis, dan pratinjau baris data sebelum commit ke database.',
    },
    {
        q: 'Bagaimana privasi dan kerahasiaan catatan konseling siswa dilindungi?',
        a: 'Sistem menerapkan Role-Based Access Control (RBAC) yang ketat. Catatan bimbingan konseling dan kronologi kasus pribadi hanya dapat diakses oleh Guru BK yang berwenang dan Kepala Sekolah. Guru umum tidak dapat membuka data rahasia ini.',
    },
    {
        q: 'Bagaimana cara sistem menghitung poin jika siswa memiliki prestasi?',
        a: 'Sistem menggunakan Point Engine terpadu. Setiap pelanggaran menambah akumulasi poin (+), sedangkan setiap prestasi siswa mengurangi poin bersih (-). Hal ini mendorong motivasi positif siswa untuk memperbaiki perilaku mereka.',
    },
    {
        q: 'Apakah surat panggilan orang tua dan kartu disiplin dapat langsung dicetak?',
        a: 'Ya. Tersedia generator dokumen PDF otomatis dengan tata letak kop surat resmi sekolah, nomor surat otomatis, identitas siswa, rincian kasus, dan tanda tangan pejabat yang berwenang.',
    },
    {
        q: 'Apa yang terjadi dengan data kedisiplinan saat penutupan semester?',
        a: 'Pada akhir semester, Super Admin atau Tim Tatib dapat menjalankan fitur Tutup Semester. Sistem membuat snapshot permanen untuk arsip riwayat, mereset poin berjalan untuk semester baru, dan mempertahankan seluruh jejak audit secara aman.',
    },
];
</script>

<template>
    <Head title="DISIPLIN PRO - Sistem Manajemen Kedisiplinan Siswa" />

    <div class="min-h-screen bg-[#FFFFFF] text-[#14231D] font-sans antialiased selection:bg-[#E5F8EF] selection:text-[#10B981]">
        <!-- ========================================================================= -->
        <!-- A. NAVIGATION -->
        <!-- ========================================================================= -->
        <header class="sticky top-0 z-50 backdrop-blur-md bg-white/90 border-b border-[#E9EFEB] transition-all">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#10B981] text-white font-bold text-sm flex items-center justify-center shadow-xs">
                        SD
                    </div>
                    <span class="text-base font-bold tracking-tight text-[#14231D] flex items-center gap-1.5">
                        DISIPLIN <span class="text-[#10B981]">PRO</span>
                    </span>
                </div>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-[13px] font-medium text-[#7B8580]">
                    <a href="#hero" class="hover:text-[#14231D] transition-colors">Beranda</a>
                    <a href="#features" class="hover:text-[#14231D] transition-colors">Fitur</a>
                    <a href="#workflow" class="hover:text-[#14231D] transition-colors">Cara Kerja</a>
                    <a href="#roles" class="hover:text-[#14231D] transition-colors">Peran Pengguna</a>
                    <a href="#faq" class="hover:text-[#14231D] transition-colors">FAQ</a>
                </nav>

                <!-- Desktop Actions -->
                <div class="hidden md:flex items-center gap-3">
                    <Link
                        v-if="isAuthenticated"
                        href="/dashboard"
                        class="px-5 py-2.5 rounded-full bg-[#10B981] hover:bg-[#059669] text-white text-[13px] font-semibold transition-all shadow-xs flex items-center gap-2 cursor-pointer"
                    >
                        <span>Buka Dashboard</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="px-4 py-2 text-[13px] font-medium text-[#14231D] hover:text-[#10B981] transition-colors cursor-pointer"
                        >
                            Masuk
                        </Link>
                        <Link
                            href="/login"
                            class="px-5 py-2.5 rounded-full bg-[#10B981] hover:bg-[#059669] text-white text-[13px] font-semibold transition-all shadow-xs flex items-center gap-2 cursor-pointer"
                        >
                            <span>Coba DISIPLIN PRO</span>
                            <i class="pi pi-arrow-right text-[10px]"></i>
                        </Link>
                    </template>
                </div>

                <!-- Mobile Hamburger Button -->
                <button
                    type="button"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    class="md:hidden p-2 rounded-lg text-[#7B8580] hover:text-[#14231D] hover:bg-[#F1FBF6] transition-colors"
                    aria-label="Toggle Navigation"
                >
                    <i :class="isMobileMenuOpen ? 'pi pi-times' : 'pi pi-bars'" class="text-base"></i>
                </button>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div
                v-if="isMobileMenuOpen"
                class="md:hidden border-t border-[#E9EFEB] bg-white px-6 py-5 space-y-4 shadow-lg animate-in fade-in duration-200"
            >
                <div class="flex flex-col space-y-3 text-sm font-medium text-[#7B8580]">
                    <a href="#hero" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#14231D]">Beranda</a>
                    <a href="#features" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#14231D]">Fitur</a>
                    <a href="#workflow" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#14231D]">Cara Kerja</a>
                    <a href="#roles" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#14231D]">Peran Pengguna</a>
                    <a href="#faq" @click="isMobileMenuOpen = false" class="py-1 hover:text-[#14231D]">FAQ</a>
                </div>
                <div class="pt-3 border-t border-[#E9EFEB] flex flex-col gap-2">
                    <Link
                        v-if="isAuthenticated"
                        href="/dashboard"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-full bg-[#10B981] text-white text-xs font-semibold shadow-xs"
                    >
                        <span>Buka Dashboard</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="w-full flex items-center justify-center py-2.5 rounded-full border border-[#E9EFEB] text-[#14231D] text-xs font-semibold"
                        >
                            Masuk ke Sistem
                        </Link>
                        <Link
                            href="/login"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-full bg-[#10B981] text-white text-xs font-semibold shadow-xs"
                        >
                            <span>Coba DISIPLIN PRO</span>
                            <i class="pi pi-arrow-right text-[10px]"></i>
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- ========================================================================= -->
        <!-- B. HERO SECTION (WITH SOFT MINT ATMOSPHERIC BACKGROUND) -->
        <!-- ========================================================================= -->
        <section id="hero" class="relative pt-16 pb-20 sm:pt-24 sm:pb-32 overflow-hidden">
            <!-- Subtle Mint Glow / Atmosphere (Inspired by Reference) -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1200px] h-[550px] bg-gradient-to-b from-[#F1FBF6] via-[#E5F8EF]/40 to-transparent pointer-events-none -z-10 rounded-full blur-3xl opacity-80"></div>

            <div class="max-w-[1240px] mx-auto px-4 sm:px-6">
                <!-- Centered Hero Typography -->
                <div class="max-w-2xl mx-auto text-center space-y-4">
                    <!-- Eyebrow Badge -->
                    <motion.div
                        :initial="{ opacity: 0, y: -8 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.4 }"
                    >
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                            Sistem Manajemen Kedisiplinan Siswa
                        </span>
                    </motion.div>

                    <!-- Headline -->
                    <motion.h1
                        :initial="{ opacity: 0, y: 12 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.5, delay: 0.08 }"
                        class="text-3xl sm:text-5xl font-extrabold text-[#14231D] tracking-tight leading-[1.18]"
                    >
                        Pengelolaan Kedisiplinan Sekolah, Lebih Terarah.
                    </motion.h1>

                    <!-- Supporting Description -->
                    <motion.p
                        :initial="{ opacity: 0, y: 12 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.5, delay: 0.16 }"
                        class="text-sm sm:text-base text-[#7B8580] leading-relaxed max-w-xl mx-auto font-normal"
                    >
                        Kelola pelanggaran, prestasi, poin siswa, dan pembinaan dalam satu sistem yang terintegrasi untuk membantu sekolah membangun budaya disiplin yang lebih baik.
                    </motion.p>

                    <!-- Action Buttons -->
                    <motion.div
                        :initial="{ opacity: 0, y: 12 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.5, delay: 0.24 }"
                        class="pt-3 flex flex-col sm:flex-row items-center justify-center gap-3"
                    >
                        <a
                            href="#features"
                            class="w-full sm:w-auto px-6 py-3 rounded-full bg-[#14231D] hover:bg-black text-white text-xs font-semibold transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>Jelajahi Fitur</span>
                            <i class="pi pi-arrow-right text-[10px]"></i>
                        </a>
                        <Link
                            :href="isAuthenticated ? '/dashboard' : '/login'"
                            class="w-full sm:w-auto px-6 py-3 rounded-full bg-white hover:bg-gray-50 border border-[#E9EFEB] text-[#14231D] text-xs font-semibold transition-all shadow-2xs flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>{{ isAuthenticated ? 'Buka Dashboard' : 'Masuk ke Sistem' }}</span>
                        </Link>
                    </motion.div>
                </div>

                <!-- ================================================================= -->
                <!-- LARGE BROWSER-FRAME PRODUCT MOCKUP (PRIMARY VISUAL FOCUS) -->
                <!-- ================================================================= -->
                <motion.div
                    :initial="{ opacity: 0, y: 25 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.6, delay: 0.32 }"
                    class="mt-12 sm:mt-16 max-w-5xl mx-auto"
                >
                    <div class="rounded-2xl border border-[#E9EFEB] bg-white shadow-xl shadow-gray-200/50 overflow-hidden">
                        <!-- Browser Window Frame Top Bar -->
                        <div class="px-4 py-3 bg-[#F8FAF9] border-b border-[#E9EFEB] flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-[#FF5F56] inline-block"></span>
                                <span class="w-3 h-3 rounded-full bg-[#FFBD2E] inline-block"></span>
                                <span class="w-3 h-3 rounded-full bg-[#27C93F] inline-block"></span>
                                <span class="ml-3 font-mono text-[11px] text-[#7B8580] bg-white px-3 py-1 rounded-md border border-[#E9EFEB] hidden sm:inline-block">
                                    https://app.disiplinpro.sch.id/dashboard
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0]">
                                    TA 2026/2027 · Semester Genap
                                </span>
                            </div>
                        </div>

                        <!-- Inside Browser: Faithful Representation of DISIPLIN PRO Application -->
                        <div class="flex flex-col md:flex-row min-h-[460px] bg-[#F8F9FA]">
                            <!-- App Sidebar Representation -->
                            <div class="w-full md:w-56 bg-white border-r border-[#E9EFEB] p-4 flex flex-col justify-between shrink-0">
                                <div class="space-y-4">
                                    <div class="flex items-center gap-2 px-2 py-1">
                                        <div class="w-7 h-7 rounded-lg bg-[#001AFF] text-white font-bold text-xs flex items-center justify-center">
                                            SD
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900 leading-none">SMA Keren</p>
                                            <p class="text-[10px] text-gray-400 mt-0.5">Sistem Tata Tertib</p>
                                        </div>
                                    </div>

                                    <div class="space-y-1 text-xs">
                                        <div class="px-2.5 py-1.5 rounded-xl bg-gray-100 font-semibold text-gray-900 flex items-center gap-2">
                                            <i class="pi pi-home text-[11px] text-[#001AFF]"></i>
                                            <span>Dashboard</span>
                                        </div>
                                        <div class="px-2.5 py-1.5 rounded-xl font-medium text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                                            <i class="pi pi-users text-[11px] text-gray-400"></i>
                                            <span>Data Siswa</span>
                                        </div>
                                        <div class="px-2.5 py-1.5 rounded-xl font-medium text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                                            <i class="pi pi-book text-[11px] text-gray-400"></i>
                                            <span>Catatan Disiplin</span>
                                        </div>
                                        <div class="px-2.5 py-1.5 rounded-xl font-medium text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                                            <i class="pi pi-check-square text-[11px] text-gray-400"></i>
                                            <span>Tindak Lanjut</span>
                                        </div>
                                        <div class="px-2.5 py-1.5 rounded-xl font-medium text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                                            <i class="pi pi-chart-bar text-[11px] text-gray-400"></i>
                                            <span>Rekap Disiplin</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-100 flex items-center gap-2 px-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-[#001AFF] text-[10px] font-bold flex items-center justify-center">
                                        PT
                                    </div>
                                    <div class="text-[11px] leading-tight">
                                        <p class="font-bold text-gray-900">Pak Tatib</p>
                                        <p class="text-gray-400 text-[10px]">Tim Tata Tertib</p>
                                    </div>
                                </div>
                            </div>

                            <!-- App Main Content Workspace -->
                            <div class="flex-1 p-5 sm:p-6 space-y-5 overflow-hidden">
                                <!-- Banner Header -->
                                <div class="rounded-xl bg-gradient-to-r from-[#001AFF] to-[#4338CA] p-4 text-white flex items-center justify-between">
                                    <div>
                                        <span class="text-[9px] font-bold tracking-widest uppercase text-blue-200">
                                            Penegakan Tata Tertib & Rekapitulasi
                                        </span>
                                        <h4 class="text-sm font-bold mt-0.5">Ringkasan Kedisiplinan Sekolah</h4>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-white text-[#001AFF] text-[11px] font-bold shadow-2xs">
                                        + Catat Kejadian
                                    </span>
                                </div>

                                <!-- 4 Actual KPI Stat Cards -->
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-2xs">
                                        <span class="text-[10px] font-medium text-gray-400">Total Siswa Aktif</span>
                                        <p class="text-lg font-bold text-gray-900 mt-0.5">485</p>
                                        <span class="text-[9px] text-gray-400">14 Rombel</span>
                                    </div>
                                    <div class="p-3 bg-white rounded-xl border border-rose-100 shadow-2xs">
                                        <span class="text-[10px] font-medium text-rose-500">Pelanggaran Hari Ini</span>
                                        <p class="text-lg font-bold text-rose-600 mt-0.5">2</p>
                                        <span class="text-[9px] text-rose-400">Tercatat di sistem</span>
                                    </div>
                                    <div class="p-3 bg-white rounded-xl border border-emerald-100 shadow-2xs">
                                        <span class="text-[10px] font-medium text-emerald-600">Prestasi Hari Ini</span>
                                        <p class="text-lg font-bold text-emerald-700 mt-0.5">3</p>
                                        <span class="text-[9px] text-emerald-500">Pengurang poin</span>
                                    </div>
                                    <div class="p-3 bg-white rounded-xl border border-amber-100 shadow-2xs">
                                        <span class="text-[10px] font-medium text-amber-600">Tindak Lanjut</span>
                                        <p class="text-lg font-bold text-amber-700 mt-0.5">5</p>
                                        <span class="text-[9px] text-amber-500">Dalam pemantauan BK</span>
                                    </div>
                                </div>

                                <!-- Live Student Radar Table Representation -->
                                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-2xs space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                            Siswa Perlu Perhatian (Radar Early Warning)
                                        </span>
                                        <span class="text-[10px] font-semibold text-[#001AFF]">Lihat Rekap Lengkap →</span>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 text-xs">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 font-bold text-[10px] flex items-center justify-center">
                                                    AP
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-900">Agus Pratama</span>
                                                    <span class="text-gray-400 text-[10px] ml-1">X MIPA 1 · NIS: 1001</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    ATTENTION · 65 Poin
                                                </span>
                                                <span class="text-[11px] text-gray-400">Bimbingan BK</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 text-xs">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 font-bold text-[10px] flex items-center justify-center">
                                                    DP
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-900">Dimas Prasetyo</span>
                                                    <span class="text-gray-400 text-[10px] ml-1">XI IPS 2 · NIS: 1042</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                    WARNING · 95 Poin
                                                </span>
                                                <span class="text-[11px] text-gray-400">Panggilan Ortu</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </motion.div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- C. PRODUCT OVERVIEW (ASYMMETRIC TWO-COLUMN COMPOSITION) -->
        <!-- ========================================================================= -->
        <section class="py-20 bg-white border-t border-[#E9EFEB]">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6">
                <!-- Section Header -->
                <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0]">
                        Sistem Terpusat
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#14231D] tracking-tight">
                        Pilihan Tepat untuk Pengelolaan Disiplin Modern
                    </h2>
                    <p class="text-xs sm:text-sm text-[#7B8580] leading-relaxed">
                        Mengintegrasikan seluruh siklus kedisiplinan dan pembinaan siswa dalam satu alur kerja yang rapi dan terukur.
                    </p>
                </div>

                <!-- Two-Column Asymmetric Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center max-w-5xl mx-auto">
                    <!-- Left Column: Interactive Benefit Selector List -->
                    <div class="lg:col-span-5 space-y-3">
                        <div
                            v-for="(item, idx) in overviewItems"
                            :key="idx"
                            @click="activeOverviewTab = idx"
                            class="p-4 rounded-xl border transition-all cursor-pointer select-none"
                            :class="[
                                activeOverviewTab === idx
                                    ? 'bg-[#F1FBF6] border-[#10B981] shadow-2xs'
                                    : 'bg-white border-[#E9EFEB] hover:border-gray-300 hover:bg-[#F8FAF9]'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#14231D]">{{ item.title }}</span>
                                <i
                                    :class="activeOverviewTab === idx ? 'pi pi-chevron-down text-[#10B981]' : 'pi pi-chevron-right text-gray-300'"
                                    class="text-[10px]"
                                ></i>
                            </div>
                            <p v-if="activeOverviewTab === idx" class="text-[12px] text-[#7B8580] mt-2 leading-relaxed">
                                {{ item.desc }}
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Mint Surface Preview Card (Inspired by Reference) -->
                    <div class="lg:col-span-7 bg-[#E5F8EF]/50 rounded-2xl p-6 sm:p-8 border border-[#D1F4E0] relative overflow-hidden">
                        <div class="bg-white rounded-xl p-6 border border-[#E9EFEB] shadow-sm space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <span class="text-[10px] font-bold text-[#10B981] uppercase tracking-wider">
                                        {{ overviewItems[activeOverviewTab].badge }}
                                    </span>
                                    <h4 class="text-sm font-bold text-[#14231D] mt-0.5">
                                        {{ overviewItems[activeOverviewTab].title }}
                                    </h4>
                                </div>
                                <span class="w-7 h-7 rounded-full bg-[#E5F8EF] text-[#10B981] flex items-center justify-center text-xs">
                                    <i class="pi pi-check text-[11px]"></i>
                                </span>
                            </div>

                            <p class="text-xs text-[#7B8580] leading-relaxed">
                                {{ overviewItems[activeOverviewTab].desc }}
                            </p>

                            <!-- Metric Summary Box -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <div class="p-3 rounded-lg bg-[#F1FBF6] border border-[#D1F4E0]/60">
                                    <span class="text-[10px] text-[#7B8580]">Point Engine</span>
                                    <p class="text-sm font-bold text-[#14231D] mt-0.5">Otomatis & Presisi</p>
                                </div>
                                <div class="p-3 rounded-lg bg-[#F1FBF6] border border-[#D1F4E0]/60">
                                    <span class="text-[10px] text-[#7B8580]">Akses Pengguna</span>
                                    <p class="text-sm font-bold text-[#14231D] mt-0.5">Role-Based (RBAC)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- D. CORE FEATURES (BALANCED FEATURE GRID WITH MINT BACKGROUNDS) -->
        <!-- ========================================================================= -->
        <section id="features" class="py-20 bg-[#F1FBF6]/60 border-t border-[#E9EFEB]">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6 space-y-12">
                <!-- Section Header -->
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0]">
                        Fitur Unggulan
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#14231D] tracking-tight">
                        Fitur Andal untuk Menopang Tata Tertib Sekolah
                    </h2>
                    <p class="text-xs sm:text-sm text-[#7B8580] leading-relaxed">
                        Dirancang berdasarkan kebutuhan operasional riil sekolah untuk menyelaraskan ketegasan aturan dengan pendekatan konseling.
                    </p>
                </div>

                <!-- Feature Grid: Varied Card Compositions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
                    <!-- Feature Card 1: Data Siswa & Point Engine -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-[#E9EFEB] shadow-2xs space-y-4 hover:border-[#10B981]/50 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-[#E5F8EF] text-[#10B981] flex items-center justify-center">
                            <i class="pi pi-users text-base"></i>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-sm font-bold text-[#14231D]">Manajemen Data Siswa & Kelas</h3>
                            <p class="text-xs text-[#7B8580] leading-relaxed">
                                Pengelolaan data induk siswa per tahun ajaran dan rombel, lengkap dengan kontak wali, riwayat penempatan, dan status aktif.
                            </p>
                        </div>
                    </div>

                    <!-- Feature Card 2: Pencatatan Pelanggaran & Prestasi -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-[#E9EFEB] shadow-2xs space-y-4 hover:border-[#10B981]/50 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-[#E5F8EF] text-[#10B981] flex items-center justify-center">
                            <i class="pi pi-calculator text-base"></i>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-sm font-bold text-[#14231D]">Pencatatan Pelanggaran & Prestasi</h3>
                            <p class="text-xs text-[#7B8580] leading-relaxed">
                                Sistem Point Engine transparan yang menambahkan poin pelanggaran (+) dan memperhitungkan poin prestasi (-) sebagai pengurang poin bersih.
                            </p>
                        </div>
                    </div>

                    <!-- Feature Card 3: Radar Early Warning -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-[#E9EFEB] shadow-2xs space-y-4 hover:border-[#10B981]/50 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-[#E5F8EF] text-[#10B981] flex items-center justify-center">
                            <i class="pi pi-radar text-base"></i>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-sm font-bold text-[#14231D]">Radar Deteksi Dini Siswa (Early Warning)</h3>
                            <p class="text-xs text-[#7B8580] leading-relaxed">
                                Notifikasi otomatis saat akumulasi poin mendekati batas sanksi tingkat tertentu (Attention, Warning, Critical) untuk intervensi preventif.
                            </p>
                        </div>
                    </div>

                    <!-- Feature Card 4: Konseling BK & Tindak Lanjut -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-[#E9EFEB] shadow-2xs space-y-4 hover:border-[#10B981]/50 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-[#E5F8EF] text-[#10B981] flex items-center justify-center">
                            <i class="pi pi-comments text-base"></i>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-sm font-bold text-[#14231D]">Pembinaan & Konseling BK Terintegrasi</h3>
                            <p class="text-xs text-[#7B8580] leading-relaxed">
                                Dokumentasi sesi konseling individual, kontrak komitmen perilaku siswa, serta rekam perkembangan pembinaan dalam modul rahasia.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Wide Bottom Feature Card: Dokumen & Tutup Semester -->
                <div class="max-w-5xl mx-auto bg-white rounded-2xl p-6 sm:p-8 border border-[#E9EFEB] shadow-2xs grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <div class="space-y-2">
                        <span class="text-[10px] font-bold text-[#10B981] uppercase tracking-wider">Pelaporan & Arsip Permanen</span>
                        <h3 class="text-base font-bold text-[#14231D]">Penerbitan Surat Resmi & Tutup Semester</h3>
                        <p class="text-xs text-[#7B8580] leading-relaxed">
                            Cetak Surat Panggilan Orang Tua dan Kartu Disiplin Siswa siap cetak berformat PDF resmi, serta pengarsipan snapshot riwayat semester secara permanen.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3.5 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] space-y-1">
                            <i class="pi pi-file-pdf text-[#10B981] text-xs"></i>
                            <p class="font-bold text-[#14231D]">PDF Generator</p>
                            <p class="text-[11px] text-[#7B8580]">Format kop sekolah & nomor surat resmi</p>
                        </div>
                        <div class="p-3.5 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] space-y-1">
                            <i class="pi pi-lock text-[#10B981] text-xs"></i>
                            <p class="font-bold text-[#14231D]">Arsip Semester</p>
                            <p class="text-[11px] text-[#7B8580]">Snapshot permanen anti-kehilangan data</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- E. HOW IT WORKS (SEQUENTIAL DISCIPLINE WORKFLOW) -->
        <!-- ========================================================================= -->
        <section id="workflow" class="py-20 bg-white border-t border-[#E9EFEB]">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6 space-y-14">
                <!-- Section Header -->
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0]">
                        Alur Kerja Sistem
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#14231D] tracking-tight">
                        Cara Kerja Terstruktur dalam 5 Langkah
                    </h2>
                    <p class="text-xs sm:text-sm text-[#7B8580] leading-relaxed">
                        Mendukung kolaborasi guru, tim tatib, dan bimbingan konseling dari pencatatan hingga evaluasi akhir.
                    </p>
                </div>

                <!-- 5-Step Clean Sequential Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 max-w-5xl mx-auto">
                    <!-- Step 1 -->
                    <div class="p-5 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] space-y-3 relative">
                        <span class="w-7 h-7 rounded-full bg-[#10B981] text-white font-bold text-xs flex items-center justify-center">
                            1
                        </span>
                        <h4 class="text-xs font-bold text-[#14231D]">Pencatatan Kejadian</h4>
                        <p class="text-[11px] text-[#7B8580] leading-relaxed">
                            Input pelanggaran atau prestasi siswa sesuai kode master tata tertib sekolah.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="p-5 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] space-y-3 relative">
                        <span class="w-7 h-7 rounded-full bg-[#10B981] text-white font-bold text-xs flex items-center justify-center">
                            2
                        </span>
                        <h4 class="text-xs font-bold text-[#14231D]">Perhitungan Poin</h4>
                        <p class="text-[11px] text-[#7B8580] leading-relaxed">
                            Point Engine menghitung poin bersih siswa secara otomatis di level server.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="p-5 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] space-y-3 relative">
                        <span class="w-7 h-7 rounded-full bg-[#10B981] text-white font-bold text-xs flex items-center justify-center">
                            3
                        </span>
                        <h4 class="text-xs font-bold text-[#14231D]">Evaluasi & Radar</h4>
                        <p class="text-[11px] text-[#7B8580] leading-relaxed">
                            Radar mendeteksi status risiko dan menampilkan siswa yang butuh perhatian.
                        </p>
                    </div>

                    <!-- Step 4 -->
                    <div class="p-5 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] space-y-3 relative">
                        <span class="w-7 h-7 rounded-full bg-[#10B981] text-white font-bold text-xs flex items-center justify-center">
                            4
                        </span>
                        <h4 class="text-xs font-bold text-[#14231D]">Pembinaan BK</h4>
                        <p class="text-[11px] text-[#7B8580] leading-relaxed">
                            Guru BK memfasilitasi sesi konseling individual dan kontrak perilaku terarah.
                        </p>
                    </div>

                    <!-- Step 5 -->
                    <div class="p-5 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] space-y-3 relative">
                        <span class="w-7 h-7 rounded-full bg-[#10B981] text-white font-bold text-xs flex items-center justify-center">
                            5
                        </span>
                        <h4 class="text-xs font-bold text-[#14231D]">Laporan & Dokumen</h4>
                        <p class="text-[11px] text-[#7B8580] leading-relaxed">
                            Penerbitan surat panggilan orang tua, kartu disiplin, dan rekap semester.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- F. USER ROLES (STAKEHOLDER SPECIFICATION) -->
        <!-- ========================================================================= -->
        <section id="roles" class="py-20 bg-[#F1FBF6]/60 border-t border-[#E9EFEB]">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6 space-y-12">
                <!-- Section Header -->
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0]">
                        Peran Pengguna
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#14231D] tracking-tight">
                        Akses Berbasis Peran untuk Seluruh Pemangku Kepentingan
                    </h2>
                    <p class="text-xs sm:text-sm text-[#7B8580] leading-relaxed">
                        Sistem membatasi wewenang dan melindungi kerahasiaan data sesuai peran masing-masing staf sekolah.
                    </p>
                </div>

                <!-- Role Selector Tabs -->
                <div class="flex items-center justify-center gap-2 flex-wrap max-w-2xl mx-auto">
                    <button
                        type="button"
                        @click="activeRoleTab = 'tatib'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="activeRoleTab === 'tatib' ? 'bg-[#10B981] text-white shadow-xs' : 'bg-white border border-[#E9EFEB] text-[#7B8580] hover:bg-gray-50'"
                    >
                        Tim Tata Tertib
                    </button>
                    <button
                        type="button"
                        @click="activeRoleTab = 'bk'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="activeRoleTab === 'bk' ? 'bg-[#10B981] text-white shadow-xs' : 'bg-white border border-[#E9EFEB] text-[#7B8580] hover:bg-gray-50'"
                    >
                        Guru BK
                    </button>
                    <button
                        type="button"
                        @click="activeRoleTab = 'walikelas'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="activeRoleTab === 'walikelas' ? 'bg-[#10B981] text-white shadow-xs' : 'bg-white border border-[#E9EFEB] text-[#7B8580] hover:bg-gray-50'"
                    >
                        Wali Kelas & Guru
                    </button>
                    <button
                        type="button"
                        @click="activeRoleTab = 'kepsek'"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="activeRoleTab === 'kepsek' ? 'bg-[#10B981] text-white shadow-xs' : 'bg-white border border-[#E9EFEB] text-[#7B8580] hover:bg-gray-50'"
                    >
                        Kepala Sekolah
                    </button>
                </div>

                <!-- Role Details Showcase Card -->
                <div class="max-w-4xl mx-auto bg-white rounded-2xl p-7 sm:p-8 border border-[#E9EFEB] shadow-2xs space-y-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-[#E9EFEB] pb-5">
                        <div>
                            <h3 class="text-base font-bold text-[#14231D]">{{ roleData[activeRoleTab].roleName }}</h3>
                            <p class="text-xs text-[#7B8580] mt-0.5">{{ roleData[activeRoleTab].desc }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0] shrink-0">
                            {{ roleData[activeRoleTab].badge }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="(point, pIdx) in roleData[activeRoleTab].points"
                            :key="pIdx"
                            class="flex items-start gap-2.5 p-3 rounded-xl bg-[#F8FAF9] border border-[#E9EFEB] text-xs text-[#14231D]"
                        >
                            <i class="pi pi-check text-[#10B981] text-xs mt-0.5 shrink-0"></i>
                            <span>{{ point }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- G. FAQ SECTION (CLEAN ACCORDION) -->
        <!-- ========================================================================= -->
        <section id="faq" class="py-20 bg-white border-t border-[#E9EFEB]">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6 space-y-12">
                <!-- Section Header -->
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E5F8EF] text-[#10B981] border border-[#D1F4E0]">
                        FAQ
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#14231D] tracking-tight">
                        Pertanyaan Umum
                    </h2>
                    <p class="text-xs sm:text-sm text-[#7B8580] leading-relaxed">
                        Jawaban ringkas seputar fungsi, privasi data, dan implementasi teknis DISIPLIN PRO.
                    </p>
                </div>

                <!-- Accordion List -->
                <div class="max-w-3xl mx-auto space-y-3">
                    <div
                        v-for="(faq, fIndex) in faqs"
                        :key="fIndex"
                        class="rounded-xl border border-[#E9EFEB] bg-[#F8FAF9] overflow-hidden transition-all"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(fIndex)"
                            class="w-full px-5 py-4 text-left flex items-center justify-between gap-4 cursor-pointer select-none"
                        >
                            <span class="text-xs sm:text-sm font-bold text-[#14231D]">
                                {{ faq.q }}
                            </span>
                            <i
                                :class="activeFaqIndex === fIndex ? 'pi pi-chevron-up text-[#10B981]' : 'pi pi-chevron-down text-gray-400'"
                                class="text-xs shrink-0 transition-transform"
                            ></i>
                        </button>
                        <div
                            v-if="activeFaqIndex === fIndex"
                            class="px-5 pb-5 pt-1 text-xs text-[#7B8580] leading-relaxed border-t border-gray-100"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- H. FINAL CONVERSION CTA (ATMOSPHERIC MINT BACKGROUND) -->
        <!-- ========================================================================= -->
        <section class="py-20 sm:py-28 bg-gradient-to-b from-white via-[#F1FBF6] to-[#E5F8EF]/40 border-t border-[#E9EFEB] relative overflow-hidden">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6 text-center space-y-5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-white text-[#10B981] border border-[#D1F4E0] shadow-2xs">
                    Implementasi Mudah di Sekolah
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-[#14231D] tracking-tight max-w-xl mx-auto leading-tight">
                    Mulai Kelola Kedisiplinan Sekolah dengan Lebih Terstruktur.
                </h2>
                <p class="text-xs sm:text-sm text-[#7B8580] max-w-lg mx-auto leading-relaxed">
                    Kenali bagaimana DISIPLIN PRO membantu sekolah mengelola data, memantau perkembangan, dan mendukung proses pembinaan siswa.
                </p>
                <div class="pt-3 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link
                        :href="isAuthenticated ? '/dashboard' : '/login'"
                        class="w-full sm:w-auto px-7 py-3 rounded-full bg-[#10B981] hover:bg-[#059669] text-white text-xs font-semibold transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>{{ isAuthenticated ? 'Buka Dashboard Operasional' : 'Masuk ke Sistem DISIPLIN PRO' }}</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- I. MINIMAL FOOTER -->
        <!-- ========================================================================= -->
        <footer class="bg-white border-t border-[#E9EFEB] py-12 text-xs text-[#7B8580]">
            <div class="max-w-[1240px] mx-auto px-4 sm:px-6 space-y-8">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border-b border-[#E9EFEB] pb-8">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#10B981] text-white font-bold text-xs flex items-center justify-center">
                                SD
                            </div>
                            <span class="text-sm font-bold text-[#14231D] tracking-tight">
                                DISIPLIN <span class="text-[#10B981]">PRO</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-[#7B8580]">
                            Sistem Administrasi Tata Tertib dan Pembinaan Siswa Terintegrasi.
                        </p>
                    </div>

                    <div class="flex items-center gap-6 text-xs font-medium text-[#7B8580]">
                        <a href="#hero" class="hover:text-[#14231D] transition-colors">Beranda</a>
                        <a href="#features" class="hover:text-[#14231D] transition-colors">Fitur</a>
                        <a href="#workflow" class="hover:text-[#14231D] transition-colors">Cara Kerja</a>
                        <a href="#roles" class="hover:text-[#14231D] transition-colors">Peran</a>
                        <a href="#faq" class="hover:text-[#14231D] transition-colors">FAQ</a>
                        <Link href="/login" class="hover:text-[#10B981] transition-colors font-semibold">Masuk</Link>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-[#7B8580]">
                    <p>© 2026 DISIPLIN PRO. Hak cipta dilindungi undang-undang.</p>
                    <p>Dibangun di atas arsitektur Laravel, Inertia.js, Vue 3, dan Tailwind CSS.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
