<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    school: {
        id: number;
        name: string;
        npsn: string | null;
        address: string;
        city: string;
        phone: string | null;
        principal_name: string | null;
        vice_principal_name: string | null;
        counseling_officer_name: string | null;
        application_name: string;
        logo_path?: string | null;
        attention_threshold?: number;
        early_warning_days?: number;
        rapid_increase_threshold?: number;
        highest_point_threshold?: number;
    };
    academicYears: Array<{
        id: number;
        name: string;
        status: string;
        semesters: Array<{ id: number; name: string; status: string }>;
    }>;
    activeSemester: {
        id: number;
        name: string;
        academic_year?: { name: string };
    } | null;
}>();

const logoPreview = ref<string | null>(props.school.logo_path || null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: props.school.name,
    npsn: props.school.npsn || '',
    address: props.school.address,
    city: props.school.city,
    phone: props.school.phone || '',
    principal_name: props.school.principal_name || '',
    vice_principal_name: props.school.vice_principal_name || '',
    counseling_officer_name: props.school.counseling_officer_name || '',
    application_name: props.school.application_name,
    attention_threshold: props.school.attention_threshold ?? 20,
    early_warning_days: props.school.early_warning_days ?? 30,
    rapid_increase_threshold: props.school.rapid_increase_threshold ?? 15,
    highest_point_threshold: props.school.highest_point_threshold ?? 150,
    logo: null as File | null,
    remove_logo: false,
    _method: 'put',
});

const onLogoSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        form.remove_logo = false;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    form.remove_logo = true;
    logoPreview.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const submit = () => {
    form.post('/settings', {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Sekolah - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <div class="max-w-[1200px] mx-auto space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Pengaturan Sekolah</h1>
                    <p class="text-gray-500 text-[15px] mt-1">Konfigurasi profil identitas sekolah, logo institusi, pejabat resmi, dan parameter akademik.</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Card 0: Logo & Kostumisasi Tampilan -->
                <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="border-b border-gray-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Logo & Kostumisasi Visual Sekolah</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Logo ini otomatis digunakan pada navigasi utama bagian atas aplikasi, kop surat dinas resmi, dan kartu pelajar.</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 text-[#6366F1] text-xs font-semibold">
                            <i class="pi pi-palette text-[11px]"></i>
                            Kostumisasi Instansi
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                        <!-- Preview Box -->
                        <div class="relative group">
                            <div class="w-24 h-24 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden p-2 shadow-xs transition-all">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    alt="Logo Sekolah"
                                    class="w-full h-full object-contain"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-gray-400">
                                    <i class="pi pi-image text-2xl mb-1"></i>
                                    <span class="text-[10px] font-medium">Belum Ada</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions & Guidelines -->
                        <div class="space-y-3 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                    class="hidden"
                                    @change="onLogoSelected"
                                />
                                <button
                                    type="button"
                                    @click="fileInputRef?.click()"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-xs transition-all active:scale-95 cursor-pointer"
                                >
                                    <i class="pi pi-upload text-xs text-[#6366F1]"></i>
                                    <span>{{ logoPreview ? 'Ganti Logo Sekolah' : 'Unggah Logo Sekolah' }}</span>
                                </button>

                                <button
                                    v-if="logoPreview"
                                    type="button"
                                    @click="removeLogo"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-red-100 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold transition-all active:scale-95 cursor-pointer"
                                >
                                    <i class="pi pi-trash text-xs"></i>
                                    <span>Hapus Logo</span>
                                </button>
                            </div>

                            <p class="text-xs text-gray-500 leading-relaxed">
                                Format didukung: <strong>PNG, JPG, SVG</strong>. Ukuran proporsional persegi (1:1) disarankan untuk hasil terbaik di bilah navigasi atas (Maksimal 2MB).
                            </p>
                            <p v-if="form.errors.logo" class="text-xs text-red-600 font-medium">
                                {{ form.errors.logo }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 1: Identitas Sekolah -->
                <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="border-b border-gray-100 pb-4">
                        <h2 class="text-lg font-bold text-gray-900">Identitas Sekolah</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Informasi ini akan tertera pada kop surat resmi, kartu disiplin siswa, dan laporan rekapitulasi.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Sekolah *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">NPSN</label>
                            <input
                                v-model="form.npsn"
                                type="text"
                                placeholder="Nomor Pokok Sekolah Nasional"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="form.errors.npsn" class="mt-1 text-xs text-red-600">{{ form.errors.npsn }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Alamat Lengkap *</label>
                            <textarea
                                v-model="form.address"
                                required
                                rows="2"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            ></textarea>
                            <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kota / Kabupaten *</label>
                            <input
                                v-model="form.city"
                                type="text"
                                required
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="form.errors.city" class="mt-1 text-xs text-red-600">{{ form.errors.city }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nomor Telepon</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="Contoh: (0341) 123456"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Aplikasi *</label>
                            <input
                                v-model="form.application_name"
                                type="text"
                                required
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Card 2: Pejabat & Penandatangan Dokumen -->
                <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="border-b border-gray-100 pb-4">
                        <h2 class="text-lg font-bold text-gray-900">Pejabat & Penandatangan Dokumen Resmi</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Nama pejabat yang digunakan saat penerbitan Surat Panggilan I (BK), Panggilan II (Kesiswaan), dan Panggilan III (Kepala Sekolah).</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kepala Sekolah</label>
                            <input
                                v-model="form.principal_name"
                                type="text"
                                placeholder="Nama Lengkap & Gelar"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Penandatangan Surat Panggilan III</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Wakil Kepala Sekolah Kesiswaan</label>
                            <input
                                v-model="form.vice_principal_name"
                                type="text"
                                placeholder="Nama Lengkap & Gelar"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Penandatangan Surat Panggilan II</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Koordinator BK / Guru BK</label>
                            <input
                                v-model="form.counseling_officer_name"
                                type="text"
                                placeholder="Nama Lengkap & Gelar"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Penandatangan Surat Panggilan I</p>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Ambang Batas Peringatan Dini (Early Warning Thresholds) -->
                <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                    <div class="border-b border-gray-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Ambang Batas Peringatan Dini (Early Warning)</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Konfigurasi batas akumulasi poin kedisiplinan yang memicu perubahan status risiko siswa (Aman, Perhatian, Waspada, Kritis).</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 text-[#6366F1] text-xs font-semibold">
                            <i class="pi pi-shield text-[10px]"></i>
                            <span>Aturan Tata Tertib</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- Ambang Perhatian -->
                        <div class="bg-amber-50/40 border border-amber-100 p-4 rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-amber-950">Ambang Perhatian</label>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 uppercase">Perhatian</span>
                            </div>
                            <div class="relative">
                                <input
                                    v-model.number="form.attention_threshold"
                                    type="number"
                                    min="1"
                                    max="999"
                                    required
                                    class="w-full text-base font-bold rounded-xl bg-white border border-amber-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                />
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Poin</span>
                            </div>
                            <p class="text-[11px] text-amber-800/80 leading-snug">
                                Siswa masuk kategori perhatian saat poin bersih mencapai angka ini.
                            </p>
                        </div>

                        <!-- Ambang Lonjakan Cepat -->
                        <div class="bg-orange-50/40 border border-orange-100 p-4 rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-orange-950">Lonjakan Cepat</label>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800 uppercase">Waspada</span>
                            </div>
                            <div class="relative">
                                <input
                                    v-model.number="form.rapid_increase_threshold"
                                    type="number"
                                    min="1"
                                    max="999"
                                    required
                                    class="w-full text-base font-bold rounded-xl bg-white border border-orange-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Poin</span>
                            </div>
                            <p class="text-[11px] text-orange-800/80 leading-snug">
                                Kenaikan poin pelanggaran mendadak dalam rentang hari evaluasi.
                            </p>
                        </div>

                        <!-- Rentang Hari Evaluasi -->
                        <div class="bg-blue-50/40 border border-blue-100 p-4 rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-blue-950">Rentang Evaluasi</label>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase">Periode</span>
                            </div>
                            <div class="relative">
                                <input
                                    v-model.number="form.early_warning_days"
                                    type="number"
                                    min="1"
                                    max="365"
                                    required
                                    class="w-full text-base font-bold rounded-xl bg-white border border-blue-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Hari</span>
                            </div>
                            <p class="text-[11px] text-blue-800/80 leading-snug">
                                Jendela waktu pemantauan lonjakan pelanggaran (misal: 30 hari terakhir).
                            </p>
                        </div>

                        <!-- Ambang Poin Kritis -->
                        <div class="bg-red-50/40 border border-red-100 p-4 rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-red-950">Ambang Kritis</label>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 uppercase">Kritis</span>
                            </div>
                            <div class="relative">
                                <input
                                    v-model.number="form.highest_point_threshold"
                                    type="number"
                                    min="1"
                                    max="9999"
                                    required
                                    class="w-full text-base font-bold rounded-xl bg-white border border-red-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-500"
                                />
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Poin</span>
                            </div>
                            <p class="text-[11px] text-red-800/80 leading-snug">
                                Poin puncak yang memicu sanksi terberat (SP 3 atau Konferensi Kasus).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Status Periode Akademik -->
                <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Status Periode Akademik</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Tahun ajaran dan semester aktif saat ini yang mengatur pencatatan seluruh kejadian.</p>
                        </div>
                        <div v-if="$page.props.auth?.roles?.includes('Super Admin')" class="flex items-center gap-2">
                            <Link href="/academic-years" class="text-xs font-semibold text-[#6366F1] hover:underline">
                                Kelola Tahun Ajaran &rarr;
                            </Link>
                            <span class="text-gray-300">&bull;</span>
                            <Link href="/semesters" class="text-xs font-semibold text-[#6366F1] hover:underline">
                                Kelola Semester &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Semester Berjalan</span>
                            <div class="text-base font-bold text-gray-900 mt-1">
                                {{ activeSemester ? `${activeSemester.name} (${activeSemester.academic_year?.name})` : 'Belum ada semester aktif' }}
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Tahun Ajaran</span>
                            <div class="text-base font-bold text-gray-900 mt-1">
                                {{ academicYears.length }} Tahun Ajaran Terdaftar
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-8 py-3 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-sm font-semibold transition-all shadow-[0_4px_14px_#6366F140]"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan Sekolah' }}
                    </button>
                </div>
            </form>

        </div>
    </AuthenticatedLayout>
</template>
