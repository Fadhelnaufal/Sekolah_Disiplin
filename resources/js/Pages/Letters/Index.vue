<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';

const activeTab = ref<'template' | 'archive'>('template');

const getTypeBadge = (type: string) => {
    switch (type) {
        case 'parent_notification':
        case 'notification':
            return { label: 'Pemberitahuan', bg: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'student_agreement':
        case 'statement':
            return { label: 'Perjanjian Siswa', bg: 'bg-purple-50 text-purple-700 border-purple-200' };
        case 'call_1':
            return { label: 'Panggilan I (BK)', bg: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'call_2':
            return { label: 'Panggilan II (Waka)', bg: 'bg-orange-50 text-orange-700 border-orange-200' };
        case 'call_3':
            return { label: 'Panggilan III (Kepsek)', bg: 'bg-red-50 text-red-700 border-red-200' };
        case 'parent_call':
            return { label: 'Panggilan Ortu', bg: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'discipline_history':
            return { label: 'Riwayat Disiplin', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        default:
            return { label: type, bg: 'bg-gray-50 text-gray-700 border-gray-200' };
    }
};

interface SchoolData {
    id: number;
    name: string;
    npsn?: string;
    phone?: string;
    address: string;
    city: string;
    province?: string;
    postal_code?: string;
    email?: string;
    website?: string;
    principal_name?: string;
    principal_nip?: string;
    signer_title?: string;
    letter_city?: string;
    classification_code?: string;
    school_code?: string;
    template_style?: string;
    show_npsn?: boolean;
    show_phone?: boolean;
    show_email?: boolean;
    show_website?: boolean;
    show_signature_image?: boolean;
    show_stamp?: boolean;
    logo_path?: string;
    signature_path?: string;
    stamp_path?: string;
}

const props = defineProps<{
    school: SchoolData;
    letters?: any;
}>();

const toast = useToast();

const form = ref({
    name: props.school?.name || 'SMA Negeri 1 Sambit',
    npsn: props.school?.npsn || '123123',
    phone: props.school?.phone || '1234566',
    address: props.school?.address || 'Jl. Ponorogo - Trenggalek, Besuki, Kec. Sambit, Kabupaten Ponorogo, Jawa Timur',
    city: props.school?.city || 'Ponorogo',
    province: props.school?.province || 'Jawa Timur',
    postal_code: props.school?.postal_code || '63474',
    email: props.school?.email || 'tes@gmail.com',
    website: props.school?.website || '',
    principal_name: props.school?.principal_name || 'Fadhel',
    principal_nip: props.school?.principal_nip || '',
    signer_title: props.school?.signer_title || 'Kepala Sekolah',
    letter_city: props.school?.letter_city || 'Ponorogo',
    classification_code: props.school?.classification_code || '421.3',
    school_code: props.school?.school_code || 'SMA-NEGERI-1-SAMBIT',
    template_style: props.school?.template_style || 'formal_indonesia',
    show_npsn: props.school?.show_npsn ?? true,
    show_phone: props.school?.show_phone ?? true,
    show_email: props.school?.show_email ?? true,
    show_website: props.school?.show_website ?? false,
    show_signature_image: props.school?.show_signature_image ?? true,
    show_stamp: props.school?.show_stamp ?? false,
});

const isSaving = ref(false);

// Image file uploads & previews
const logoFile = ref<File | null>(null);
const logoPreview = ref<string | null>(props.school?.logo_path || null);
const logoInput = ref<HTMLInputElement | null>(null);

const signatureFile = ref<File | null>(null);
const signaturePreview = ref<string | null>(props.school?.signature_path || null);
const signatureInput = ref<HTMLInputElement | null>(null);

const stampFile = ref<File | null>(null);
const stampPreview = ref<string | null>(props.school?.stamp_path || null);
const stampInput = ref<HTMLInputElement | null>(null);

const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        logoFile.value = target.files[0];
        logoPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const handleSignatureChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        signatureFile.value = target.files[0];
        signaturePreview.value = URL.createObjectURL(target.files[0]);
    }
};

const handleStampChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        stampFile.value = target.files[0];
        stampPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const currentYear = new Date().getFullYear();

const todayFormatted = computed(() => {
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const now = new Date();
    return `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
});

const metaText = computed(() => {
    const parts: string[] = [];
    if (form.value.show_npsn && form.value.npsn) {
        parts.push(`NPSN ${form.value.npsn}`);
    }
    if (form.value.show_phone && form.value.phone) {
        parts.push(`Telp. ${form.value.phone}`);
    }
    if (form.value.show_email && form.value.email) {
        parts.push(form.value.email);
    }
    if (form.value.show_website && form.value.website) {
        parts.push(form.value.website);
    }
    return parts.join(' · ');
});

const submit = () => {
    isSaving.value = true;
    const payload = new FormData();

    Object.entries(form.value).forEach(([key, val]) => {
        if (typeof val === 'boolean') {
            payload.append(key, val ? '1' : '0');
        } else if (val !== null && val !== undefined) {
            payload.append(key, String(val));
        }
    });

    if (logoFile.value) {
        payload.append('logo', logoFile.value);
    }
    if (signatureFile.value) {
        payload.append('signature', signatureFile.value);
    }
    if (stampFile.value) {
        payload.append('stamp', stampFile.value);
    }

    router.post('/letters/template', payload, {
        forceFormData: true,
        onSuccess: () => {
            isSaving.value = false;
            toast.add({
                severity: 'success',
                summary: 'Berhasil',
                detail: 'Template laporan berhasil disimpan.',
                life: 3000,
            });
        },
        onError: (errors) => {
            isSaving.value = false;
            const firstErr = Object.values(errors)[0] || 'Gagal menyimpan template.';
            toast.add({
                severity: 'error',
                summary: 'Gagal',
                detail: String(firstErr),
                life: 5000,
            });
        },
    });
};
</script>

<template>
    <Head title="Template Laporan - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-gray-900 leading-tight">Template Laporan</span>
                <span class="text-xs font-medium text-gray-400">{{ form.name || 'SMA Negeri 1 Sambit' }}</span>
            </div>
        </template>

        <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div>
                    <span class="text-[11px] font-bold text-[#8B5CF6] tracking-wider uppercase block">
                        TEMPLATE DOKUMEN SEKOLAH
                    </span>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-0.5">
                        Template Laporan
                    </h1>
                    <p class="text-xs text-gray-500 mt-1 max-w-2xl leading-relaxed">
                        Isi identitas sekolah sekali, unggah logo dan tanda tangan, lalu semua surat akan menggunakan format yang konsisten.
                    </p>
                </div>

                <!-- Top Right Badge & Actions -->
                <div class="flex items-center gap-2.5">
                    <Link
                        href="/letters/create"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-2xs transition-all cursor-pointer active:scale-95"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Buat Dokumen Baru</span>
                    </Link>

                    <button
                        v-if="activeTab === 'template'"
                        type="button"
                        @click="submit"
                        :disabled="isSaving"
                        class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 hover:bg-black text-white text-xs font-semibold shadow-2xs transition-all cursor-pointer active:scale-95 disabled:opacity-50"
                    >
                        <i :class="isSaving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" class="text-xs"></i>
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                    </button>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center gap-2 border-b border-gray-200 mb-6">
                <button
                    type="button"
                    @click="activeTab = 'template'"
                    :class="activeTab === 'template' ? 'border-[#6366F1] text-[#6366F1] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                    class="py-3 px-4 border-b-2 text-xs flex items-center gap-2 transition-all cursor-pointer"
                >
                    <i class="pi pi-sliders-h text-xs"></i>
                    <span>Pengaturan Template & Kop</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'archive'"
                    :class="activeTab === 'archive' ? 'border-[#6366F1] text-[#6366F1] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                    class="py-3 px-4 border-b-2 text-xs flex items-center gap-2 transition-all cursor-pointer"
                >
                    <i class="pi pi-folder-open text-xs"></i>
                    <span>Arsip Dokumen Terbit ({{ letters?.total ?? letters?.data?.length ?? (letters?.length ?? 0) }})</span>
                </button>
            </div>

            <!-- Two-Column Layout for Template Settings -->
            <div v-if="activeTab === 'template'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column: Settings Form Panels -->
                <div class="lg:col-span-7 xl:col-span-7 space-y-6">
                    <!-- SECTION 1: Identitas & Kop Sekolah -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#8B5CF6] flex items-center justify-center shrink-0">
                                <i class="pi pi-image text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Identitas & Kop Sekolah</h2>
                                <span class="text-[11px] text-gray-400">Data ini otomatis muncul di bagian kepala surat.</span>
                            </div>
                        </div>

                        <!-- 3 Upload Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Logo Upload -->
                            <div
                                @click="logoInput?.click()"
                                class="border border-dashed border-gray-200 rounded-xl p-3.5 flex flex-col items-center justify-center text-center hover:border-[#8B5CF6] hover:bg-[#FBFBFF] transition-all cursor-pointer group bg-[#F9FAFB]/50 relative overflow-hidden min-h-[105px]"
                            >
                                <input
                                    ref="logoInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleLogoChange"
                                />
                                <div v-if="logoPreview" class="flex flex-col items-center">
                                    <img :src="logoPreview" alt="Logo" class="h-10 object-contain mb-1" />
                                    <span class="text-[10px] text-[#8B5CF6] font-medium group-hover:underline">Ubah Logo</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#8B5CF6] flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                                        <i class="pi pi-upload text-xs"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-800">Logo Sekolah</span>
                                    <span class="text-[10px] text-gray-400 mt-0.5">PNG/JPG, disarankan transparan</span>
                                </div>
                            </div>

                            <!-- Tanda Tangan Upload -->
                            <div
                                @click="signatureInput?.click()"
                                class="border border-dashed border-gray-200 rounded-xl p-3.5 flex flex-col items-center justify-center text-center hover:border-[#8B5CF6] hover:bg-[#FBFBFF] transition-all cursor-pointer group bg-[#F9FAFB]/50 relative overflow-hidden min-h-[105px]"
                            >
                                <input
                                    ref="signatureInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleSignatureChange"
                                />
                                <div v-if="signaturePreview" class="flex flex-col items-center">
                                    <img :src="signaturePreview" alt="Tanda Tangan" class="h-10 object-contain mb-1" />
                                    <span class="text-[10px] text-[#8B5CF6] font-medium group-hover:underline">Ubah Tanda Tangan</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#8B5CF6] flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                                        <i class="pi pi-upload text-xs"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-800">Tanda Tangan</span>
                                    <span class="text-[10px] text-gray-400 mt-0.5">PNG transparan hasil scan</span>
                                </div>
                            </div>

                            <!-- Stempel Upload -->
                            <div
                                @click="stampInput?.click()"
                                class="border border-dashed border-gray-200 rounded-xl p-3.5 flex flex-col items-center justify-center text-center hover:border-[#8B5CF6] hover:bg-[#FBFBFF] transition-all cursor-pointer group bg-[#F9FAFB]/50 relative overflow-hidden min-h-[105px]"
                            >
                                <input
                                    ref="stampInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleStampChange"
                                />
                                <div v-if="stampPreview" class="flex flex-col items-center">
                                    <img :src="stampPreview" alt="Stempel" class="h-10 object-contain mb-1" />
                                    <span class="text-[10px] text-[#8B5CF6] font-medium group-hover:underline">Ubah Stempel</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#8B5CF6] flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                                        <i class="pi pi-upload text-xs"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-800">Stempel Sekolah</span>
                                    <span class="text-[10px] text-gray-400 mt-0.5">Opsional</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fields -->
                        <div class="space-y-3.5 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Sekolah</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">NPSN</label>
                                    <input
                                        v-model="form.npsn"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Telepon</label>
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Alamat</label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kota / Kabupaten</label>
                                    <input
                                        v-model="form.city"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Provinsi</label>
                                    <input
                                        v-model="form.province"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Pos</label>
                                    <input
                                        v-model="form.postal_code"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Email Sekolah</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Website Sekolah</label>
                                <input
                                    v-model="form.website"
                                    type="text"
                                    placeholder="Opsional"
                                    class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all placeholder:text-gray-400"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Penandatangan -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#8B5CF6] flex items-center justify-center shrink-0">
                                <i class="pi pi-user text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Penandatangan</h2>
                                <span class="text-[11px] text-gray-400">Dipakai pada surat pemberitahuan, panggilan, dan laporan resmi.</span>
                            </div>
                        </div>

                        <div class="space-y-3.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Kepala Sekolah</label>
                                    <input
                                        v-model="form.principal_name"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">NIP Kepala Sekolah</label>
                                    <input
                                        v-model="form.principal_nip"
                                        type="text"
                                        placeholder="Opsional"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all placeholder:text-gray-400"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Jabatan Penandatangan</label>
                                    <input
                                        v-model="form.signer_title"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kota pada Surat</label>
                                    <input
                                        v-model="form.letter_city"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Penomoran Dokumen -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#8B5CF6] flex items-center justify-center shrink-0">
                                <i class="pi pi-file text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Penomoran Dokumen</h2>
                                <span class="text-[11px] text-gray-400">Nomor dibuat otomatis per akun sekolah dan kembali ke 001 saat tahun berganti.</span>
                            </div>
                        </div>

                        <div class="space-y-3.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Klasifikasi</label>
                                    <input
                                        v-model="form.classification_code"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Sekolah</label>
                                    <input
                                        v-model="form.school_code"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-all uppercase"
                                    />
                                </div>
                            </div>

                            <!-- Automatic Format Preview Box -->
                            <div class="bg-[#F9FAFB] rounded-xl p-4 border border-gray-100">
                                <span class="text-[10px] font-bold text-gray-400 tracking-wider uppercase block">
                                    FORMAT OTOMATIS
                                </span>
                                <div class="text-sm font-bold text-gray-900 font-mono mt-1">
                                    {{ form.classification_code || '421.3' }}/001/{{ form.school_code || 'SMA-NEGERI-1-SAMBIT' }}/{{ currentYear }}
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1 leading-normal">
                                    Klasifikasi dapat disesuaikan bila sekolah/dinas memiliki kode arsip resmi sendiri.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: Tampilan Dokumen -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#8B5CF6] flex items-center justify-center shrink-0">
                                <i class="pi pi-sliders-h text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Tampilan Dokumen</h2>
                                <span class="text-[11px] text-gray-400">Pilih informasi yang ditampilkan pada kop dan tanda tangan.</span>
                            </div>
                        </div>

                        <!-- Template Style Selector -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                            <!-- Formal Indonesia -->
                            <div
                                @click="form.template_style = 'formal_indonesia'"
                                :class="form.template_style === 'formal_indonesia' ? 'border-[#8B5CF6] bg-[#FBFBFF] ring-2 ring-[#8B5CF6]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                                class="rounded-xl border p-4 cursor-pointer transition-all flex items-start gap-3 relative"
                            >
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex flex-col items-center justify-center gap-0.5 shrink-0 p-1.5">
                                    <div class="w-full h-1 bg-[#8B5CF6] rounded-xs"></div>
                                    <div class="w-3/4 h-0.5 bg-gray-400 rounded-xs"></div>
                                    <div class="w-full h-0.5 bg-gray-300 rounded-xs"></div>
                                </div>
                                <div class="flex-1 min-w-0 pr-6">
                                    <h3 class="text-xs font-bold text-gray-900 leading-tight">Formal Indonesia</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Kop tegas, garis ganda, struktur surat resmi</p>
                                </div>
                                <div v-if="form.template_style === 'formal_indonesia'" class="absolute top-3 right-3 text-[#8B5CF6]">
                                    <i class="pi pi-check-circle text-base"></i>
                                </div>
                            </div>

                            <!-- Formal Minimal -->
                            <div
                                @click="form.template_style = 'formal_minimal'"
                                :class="form.template_style === 'formal_minimal' ? 'border-[#8B5CF6] bg-[#FBFBFF] ring-2 ring-[#8B5CF6]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                                class="rounded-xl border p-4 cursor-pointer transition-all flex items-start gap-3 relative"
                            >
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex flex-col items-center justify-center gap-1 shrink-0 p-1.5">
                                    <div class="w-full h-1 bg-gray-600 rounded-xs"></div>
                                    <div class="w-3/4 h-0.5 bg-gray-300 rounded-xs"></div>
                                </div>
                                <div class="flex-1 min-w-0 pr-6">
                                    <h3 class="text-xs font-bold text-gray-900 leading-tight">Formal Minimal</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Lebih bersih untuk sekolah modern</p>
                                </div>
                                <div v-if="form.template_style === 'formal_minimal'" class="absolute top-3 right-3 text-[#8B5CF6]">
                                    <i class="pi pi-check-circle text-base"></i>
                                </div>
                            </div>
                        </div>

                        <!-- 6 Toggles (2 columns x 3 rows) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 pt-3 border-t border-gray-100">
                            <!-- Toggle: Tampilkan NPSN -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700">Tampilkan NPSN</span>
                                <button
                                    type="button"
                                    @click="form.show_npsn = !form.show_npsn"
                                    :class="form.show_npsn ? 'bg-[#6366F1]' : 'bg-gray-200'"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span
                                        :class="form.show_npsn ? 'translate-x-4' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>

                            <!-- Toggle: Tampilkan telepon -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700">Tampilkan telepon</span>
                                <button
                                    type="button"
                                    @click="form.show_phone = !form.show_phone"
                                    :class="form.show_phone ? 'bg-[#6366F1]' : 'bg-gray-200'"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span
                                        :class="form.show_phone ? 'translate-x-4' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>

                            <!-- Toggle: Tampilkan email -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700">Tampilkan email</span>
                                <button
                                    type="button"
                                    @click="form.show_email = !form.show_email"
                                    :class="form.show_email ? 'bg-[#6366F1]' : 'bg-gray-200'"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span
                                        :class="form.show_email ? 'translate-x-4' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>

                            <!-- Toggle: Tampilkan website -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700">Tampilkan website</span>
                                <button
                                    type="button"
                                    @click="form.show_website = !form.show_website"
                                    :class="form.show_website ? 'bg-[#6366F1]' : 'bg-gray-200'"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span
                                        :class="form.show_website ? 'translate-x-4' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>

                            <!-- Toggle: Tampilkan gambar tanda tangan -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700">Tampilkan gambar tanda tangan</span>
                                <button
                                    type="button"
                                    @click="form.show_signature_image = !form.show_signature_image"
                                    :class="form.show_signature_image ? 'bg-[#6366F1]' : 'bg-gray-200'"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span
                                        :class="form.show_signature_image ? 'translate-x-4' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>

                            <!-- Toggle: Tampilkan stempel -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700">Tampilkan stempel</span>
                                <button
                                    type="button"
                                    @click="form.show_stamp = !form.show_stamp"
                                    :class="form.show_stamp ? 'bg-[#6366F1]' : 'bg-gray-200'"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span
                                        :class="form.show_stamp ? 'translate-x-4' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Bar (Mobile / Responsive) -->
                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="submit"
                            :disabled="isSaving"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-[0_4px_14px_#6366F135] transition-all cursor-pointer active:scale-95 disabled:opacity-50"
                        >
                            <i :class="isSaving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" class="text-xs"></i>
                            <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Pengaturan Template' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Live A4 Preview Canvas -->
                <div class="lg:col-span-5 xl:col-span-5 sticky top-6 space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <span class="text-[11px] font-bold text-[#6366F1] tracking-wider uppercase">
                            PREVIEW A4
                        </span>
                        <span class="text-[11px] text-gray-400 italic">
                            Preview otomatis mengikuti data di kiri
                        </span>
                    </div>

                    <div class="bg-[#F3F4F6] rounded-2xl border border-gray-200/60 p-4 sm:p-6 flex justify-center items-start overflow-x-auto min-h-[780px]">
                        <!-- A4 Sheet -->
                        <div class="bg-white shadow-[0_10px_35px_#00000008] border border-gray-200/80 rounded-sm w-full max-w-[540px] p-6 sm:p-9 text-gray-900 select-text font-serif text-[11px] leading-relaxed">
                            <!-- Kop Surat -->
                            <div class="relative flex items-center justify-center min-h-[64px] mb-2">
                                <!-- Logo Box -->
                                <div class="absolute left-0 top-0 w-12 h-12 flex items-center justify-center">
                                    <img
                                        v-if="logoPreview || form.logo_path"
                                        :src="logoPreview || form.logo_path"
                                        alt="Logo"
                                        class="w-12 h-12 object-contain"
                                    />
                                    <div
                                        v-else
                                        class="w-12 h-12 border border-dashed border-gray-300 flex items-center justify-center text-[9px] text-gray-400 font-mono tracking-wider"
                                    >
                                        LOGO
                                    </div>
                                </div>

                                <!-- Centered School Header -->
                                <div class="text-center px-14 w-full">
                                    <h2 class="text-xs sm:text-sm font-bold tracking-wider uppercase font-serif text-gray-900 leading-tight">
                                        {{ form.name || 'SMA NEGERI 1 SAMBIT' }}
                                    </h2>
                                    <p class="text-[9px] sm:text-[10px] text-gray-500 font-serif italic mt-0.5 leading-snug">
                                        {{ [form.address, form.city, form.province, form.postal_code].filter(Boolean).join(', ') }}
                                    </p>
                                    <p v-if="metaText" class="text-[9px] text-gray-500 font-serif mt-0.5 leading-snug">
                                        {{ metaText }}
                                    </p>
                                </div>
                            </div>

                            <!-- Divider Line -->
                            <div v-if="form.template_style === 'formal_indonesia'" class="mt-3 mb-5">
                                <div class="h-[2px] bg-black w-full"></div>
                                <div class="h-[1px] bg-black w-full mt-[1.5px]"></div>
                            </div>
                            <div v-else class="mt-3 mb-5">
                                <div class="h-[1px] bg-gray-300 w-full"></div>
                            </div>

                            <!-- Document Title & Number -->
                            <div class="text-center my-5">
                                <h3 class="font-bold underline uppercase text-xs tracking-wider font-serif text-gray-900">
                                    SURAT PEMBERITAHUAN ORANG TUA/WALI
                                </h3>
                                <p class="text-[10px] text-gray-700 mt-0.5 font-serif">
                                    Nomor: ____________________
                                </p>
                            </div>

                            <!-- Recipient & Body -->
                            <div class="text-[11px] text-gray-800 font-serif space-y-1 my-3">
                                <p>Yth. Bapak/Ibu Orang Tua/Wali Peserta Didik</p>
                                <p>di Tempat</p>
                                <p class="pt-1.5">Dengan hormat,</p>
                                <p class="leading-relaxed">
                                    Sehubungan dengan proses pembinaan dan pemantauan kedisiplinan peserta didik, sekolah menyampaikan informasi mengenai:
                                </p>
                            </div>

                            <!-- Student Info Table -->
                            <div class="my-3 text-[11px] font-serif">
                                <table class="w-full">
                                    <tbody>
                                        <tr>
                                            <td class="w-24 py-0.5 text-gray-700">Nama</td>
                                            <td class="w-3 text-center py-0.5">:</td>
                                            <td class="py-0.5 font-medium text-gray-900">Nama Siswa</td>
                                        </tr>
                                        <tr>
                                            <td class="py-0.5 text-gray-700">NIS</td>
                                            <td class="text-center py-0.5">:</td>
                                            <td class="py-0.5 text-gray-900">12345</td>
                                        </tr>
                                        <tr>
                                            <td class="py-0.5 text-gray-700">Kelas</td>
                                            <td class="text-center py-0.5">:</td>
                                            <td class="py-0.5 text-gray-900">VII A</td>
                                        </tr>
                                        <tr>
                                            <td class="py-0.5 text-gray-700">Orang Tua/Wali</td>
                                            <td class="text-center py-0.5">:</td>
                                            <td class="py-0.5 text-gray-900">Nama Orang Tua/Wali</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- 3 Poin Boxes (Pelanggaran 12 poin, Prestasi 4 poin, Poin Bersih 8 poin) -->
                            <div class="grid grid-cols-3 gap-2.5 my-4 font-serif">
                                <div class="border border-gray-300 p-2.5 text-center rounded-xs">
                                    <span class="block text-[10px] text-gray-600">Pelanggaran</span>
                                    <span class="block text-xs font-bold text-gray-900 mt-0.5">12 poin</span>
                                </div>
                                <div class="border border-gray-300 p-2.5 text-center rounded-xs">
                                    <span class="block text-[10px] text-gray-600">Prestasi</span>
                                    <span class="block text-xs font-bold text-gray-900 mt-0.5">4 poin</span>
                                </div>
                                <div class="border border-gray-300 p-2.5 text-center rounded-xs">
                                    <span class="block text-[10px] text-gray-600">Poin Bersih</span>
                                    <span class="block text-xs font-bold text-gray-900 mt-0.5">8 poin</span>
                                </div>
                            </div>

                            <!-- Closing -->
                            <p class="text-[11px] text-gray-800 font-serif leading-relaxed my-3">
                                Demikian surat ini disampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami mengucapkan terima kasih.
                            </p>

                            <!-- Signature Block -->
                            <div class="mt-6 flex justify-end font-serif">
                                <div class="text-center w-48 text-[11px] text-gray-800 space-y-0.5 relative">
                                    <p>{{ form.letter_city || 'Ponorogo' }}, {{ todayFormatted }}</p>
                                    <p class="font-medium">{{ form.signer_title || 'Kepala Sekolah' }}</p>

                                    <!-- Signature Image / Stamp / Spacer -->
                                    <div class="h-14 flex items-center justify-center relative my-1">
                                        <img
                                            v-if="form.show_stamp && (stampPreview || form.stamp_path)"
                                            :src="stampPreview || form.stamp_path"
                                            alt="Stempel"
                                            class="absolute -left-3 h-12 w-12 object-contain opacity-80 pointer-events-none"
                                        />
                                        <img
                                            v-if="form.show_signature_image && (signaturePreview || form.signature_path)"
                                            :src="signaturePreview || form.signature_path"
                                            alt="Tanda Tangan"
                                            class="h-12 object-contain"
                                        />
                                    </div>

                                    <p class="font-bold underline leading-tight">
                                        {{ form.principal_name || 'Fadhel' }}
                                    </p>
                                    <p v-if="form.principal_nip" class="text-[10px] text-gray-500">
                                        NIP. {{ form.principal_nip }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Archive Table -->
            <div v-else class="space-y-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-2xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Daftar Dokumen Resmi Terbit</h2>
                            <p class="text-xs text-gray-500">Semua dokumen resmi kesiswaan yang telah digenerate dan diarsipkan dengan snapshot data.</p>
                        </div>
                        <Link
                            href="/letters/create"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-2xs transition-all"
                        >
                            <i class="pi pi-plus text-xs"></i>
                            <span>Buat Dokumen Baru</span>
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/75 text-gray-600 border-b border-gray-100 uppercase text-[10px] tracking-wider font-semibold">
                                <tr>
                                    <th class="py-3 px-3.5">Nomor Surat</th>
                                    <th class="py-3 px-3.5">Jenis Dokumen</th>
                                    <th class="py-3 px-3.5">Peserta Didik</th>
                                    <th class="py-3 px-3.5">Perihal</th>
                                    <th class="py-3 px-3.5">Tanggal Terbit</th>
                                    <th class="py-3 px-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="letItem in (letters?.data || letters || [])" :key="letItem.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-3.5 font-mono font-medium text-gray-900 whitespace-nowrap">
                                        {{ letItem.letter_number }}
                                    </td>
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        <span :class="['px-2 py-0.5 rounded-md border text-[10px] font-semibold', getTypeBadge(letItem.type).bg]">
                                            {{ getTypeBadge(letItem.type).label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ letItem.student?.name || '-' }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">NIS: {{ letItem.student?.nis || '-' }} · {{ letItem.student?.class_room?.name || 'Tanpa Kelas' }}</div>
                                    </td>
                                    <td class="py-3 px-3.5 text-gray-700 max-w-xs truncate">
                                        {{ letItem.subject || 'Dokumen Resmi' }}
                                    </td>
                                    <td class="py-3 px-3.5 text-gray-500 whitespace-nowrap">
                                        {{ new Date(letItem.generated_at || letItem.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </td>
                                    <td class="py-3 px-3.5 text-right whitespace-nowrap space-x-1">
                                        <a
                                            :href="'/letters/' + letItem.id"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-100 text-[11px] font-medium transition-colors"
                                            title="Buka PDF"
                                        >
                                            <i class="pi pi-eye text-xs"></i>
                                            <span>Lihat</span>
                                        </a>
                                        <a
                                            :href="'/letters/' + letItem.id + '/download'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-gray-700 hover:bg-gray-100 text-[11px] font-medium transition-colors"
                                            title="Unduh PDF"
                                        >
                                            <i class="pi pi-download text-xs"></i>
                                            <span>PDF</span>
                                        </a>
                                        <a
                                            :href="'/letters/' + letItem.id + '/word'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 text-[11px] font-medium transition-colors"
                                            title="Unduh Word (.doc)"
                                        >
                                            <i class="pi pi-file-word text-xs"></i>
                                            <span>Word</span>
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="(!letters?.data && !letters?.length) || (letters?.data?.length === 0)">
                                    <td colspan="6" class="py-8 text-center text-gray-400">
                                        <i class="pi pi-inbox text-2xl mb-2 block"></i>
                                        <span>Belum ada dokumen resmi yang diterbitkan.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
