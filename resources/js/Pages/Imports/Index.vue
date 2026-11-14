<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { showAlert } from '@/utils';

interface AcademicYearItem {
    id: number;
    name: string;
}

interface SemesterItem {
    id: number;
    name: string;
}

const props = defineProps<{
    academicYears?: AcademicYearItem[];
    semesters?: SemesterItem[];
}>();

const page = usePage();
const userRoles = computed<string[]>(() => (page.props.auth as any)?.roles || []);
const isSuperAdmin = computed(() => userRoles.value.includes('Super Admin'));
const isTataTertib = computed(() => userRoles.value.includes('Tata Tertib'));
const isBK = computed(() => userRoles.value.includes('BK'));

const sheetMeta: Record<string, { title: string; desc: string; icon: string }> = {
    'DATA_SISWA': {
        title: 'Data Siswa & Rombel',
        desc: 'Impor profil data induk siswa (NIS, NISN, Nama, Kelas/Rombel, dan Kontak Orang Tua).',
        icon: 'pi pi-users',
    },
    'MASTER_PELANGGARAN': {
        title: 'Master Pelanggaran',
        desc: 'Katalog jenis pelanggaran tata tertib dan bobot poin pelanggaran sekolah.',
        icon: 'pi pi-exclamation-triangle',
    },
    'MASTER_PRESTASI': {
        title: 'Master Prestasi',
        desc: 'Katalog capaian prestasi dan bobot apresiasi poin penghargaan siswa.',
        icon: 'pi pi-star',
    },
    'MASTER_SANKSI': {
        title: 'Master Aturan Sanksi',
        desc: 'Ambang batas akumulasi poin risiko dan rekomendasi sanksi pembinaan.',
        icon: 'pi pi-shield',
    },
    'CATATAN': {
        title: 'Catatan Kejadian Disiplin',
        desc: 'Jurnal kejadian pelanggaran atau prestasi siswa (semester dideteksi otomatis).',
        icon: 'pi pi-book',
    },
    'PEMBINAAN': {
        title: 'Catatan Bimbingan Konseling',
        desc: 'Rekam sesi pembinaan, konseling siswa, dan tindak lanjut petugas BK.',
        icon: 'pi pi-heart',
    },
    'SETUP': {
        title: 'Setup & Konfigurasi Sekolah',
        desc: 'Konfigurasi identitas sekolah dan parameter dasar (akan menimpa data konfigurasi).',
        icon: 'pi pi-cog',
    },
};

const allowedSheets = computed(() => {
    let sheets: string[] = [];
    if (isSuperAdmin.value) {
        sheets = ['SETUP', 'DATA_SISWA', 'MASTER_PELANGGARAN', 'MASTER_PRESTASI', 'MASTER_SANKSI', 'CATATAN', 'PEMBINAAN'];
    } else {
        if (isTataTertib.value) {
            sheets.push('DATA_SISWA', 'CATATAN', 'MASTER_PELANGGARAN', 'MASTER_PRESTASI', 'MASTER_SANKSI');
        }
        if (isBK.value) {
            sheets.push('PEMBINAAN', 'CATATAN');
        }
    }
    return [...new Set(sheets)];
});

const currentStep = ref(1);
const form = useForm({
    sheet: '',
    file: null as File | null,
    academic_year_id: '',
    semester_id: '',
});

const previewState = ref<any>(null);
const isUploading = ref(false);
const isCommitting = ref(false);
const uploadError = ref<string | null>(null);
const commitResult = ref<any>(null);
const confirmSetup = ref(false);
const isDragging = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.file = target.files[0];
        uploadError.value = null;
    }
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        form.file = e.dataTransfer.files[0];
        uploadError.value = null;
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const removeSelectedFile = () => {
    form.file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const uploadFile = () => {
    if (!form.sheet || !form.file) return;
    if (form.sheet === 'DATA_SISWA' && !form.academic_year_id) {
        uploadError.value = 'Tahun Ajaran wajib dipilih untuk import Data Siswa.';
        return;
    }

    isUploading.value = true;
    uploadError.value = null;

    const formData = new FormData();
    formData.append('sheet', form.sheet);
    formData.append('file', form.file);
    if (form.academic_year_id) formData.append('academic_year_id', form.academic_year_id);
    if (form.semester_id) formData.append('semester_id', form.semester_id);

    fetch('/imports/upload', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': (page.props as any).auth?.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: formData,
    })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || data.message || 'Gagal mengunggah file.');
            previewState.value = data;
            currentStep.value = 2;
        })
        .catch((err) => {
            uploadError.value = err.message;
        })
        .finally(() => {
            isUploading.value = false;
        });
};

const commitImport = () => {
    if (form.sheet === 'SETUP' && !confirmSetup.value) {
        showAlert({
            title: 'Konfirmasi Diperlukan',
            text: 'Harap centang konfirmasi penimpaan konfigurasi SETUP sekolah sebelum melanjutkan.',
            icon: 'warning',
        });
        return;
    }

    isCommitting.value = true;

    fetch('/imports/commit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': (page.props as any).auth?.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ preview_id: previewState.value.preview_id }),
    })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || data.message || 'Gagal saat commit ke database.');
            commitResult.value = data.result;
            currentStep.value = 3;
        })
        .catch((err) => {
            showAlert({
                title: 'Gagal Menyimpan Data',
                text: err.message,
                icon: 'error',
            });
        })
        .finally(() => {
            isCommitting.value = false;
        });
};

const resetWizard = () => {
    currentStep.value = 1;
    form.reset();
    previewState.value = null;
    commitResult.value = null;
    uploadError.value = null;
    confirmSetup.value = false;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const previewHeaders = computed<string[]>(() => {
    if (!previewState.value?.preview_rows?.length) return [];
    const firstRow = previewState.value.preview_rows[0];
    return Object.keys(firstRow.original || {});
});

const getStatusBadgeConfig = (status: string) => {
    switch (status) {
        case 'valid':
            return { label: 'VALID', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'update':
            return { label: 'UPDATE', class: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'invalid':
            return { label: 'ERROR', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'duplicate':
            return { label: 'DUPLIKAT', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        default:
            return { label: 'SKIP', class: 'bg-gray-100 text-gray-600 border-gray-200' };
    }
};
</script>

<template>
    <Head title="Import Data Excel - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="hover:text-gray-900 transition-colors font-medium">Pengaturan & Master</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Import & Export Data</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Import Data Excel"
                description="Impor massal data induk siswa, master tata tertib, dan rekam kejadian kedisiplinan dari spreadsheet."
                badge="Migrasi & Integrasi Data"
                badge-icon="pi pi-file-excel"
            >
                <template #actions>
                    <a
                        :href="`/imports/template?sheet=${form.sheet || 'DATA_SISWA'}`"
                        download
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-full bg-white border border-gray-200 text-gray-700 hover:text-[#001AFF] hover:border-[#001AFF] hover:bg-indigo-50/20 transition-all shadow-2xs hover:shadow-xs cursor-pointer"
                    >
                        <i class="pi pi-download text-[#001AFF] text-xs"></i>
                        <span>Unduh Template {{ form.sheet === 'DATA_SISWA' ? 'Siswa' : (sheetMeta[form.sheet]?.title || form.sheet) }} (.xlsx)</span>
                    </a>
                </template>
            </AppPageHeader>

            <!-- Stepper Navigation Bar -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
                <div class="flex items-center justify-between max-w-3xl mx-auto">
                    <!-- Step 1 Indicator -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all"
                            :class="[
                                currentStep === 1
                                    ? 'bg-[#001AFF] text-white shadow-xs ring-4 ring-indigo-50'
                                    : currentStep > 1
                                    ? 'bg-emerald-500 text-white'
                                    : 'bg-gray-100 text-gray-500'
                            ]"
                        >
                            <i v-if="currentStep > 1" class="pi pi-check text-xs"></i>
                            <span v-else>1</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-bold text-gray-900" :class="{ 'text-[#001AFF]': currentStep === 1 }">
                                Upload & Pengaturan
                            </p>
                            <p class="text-[11px] text-gray-400">Pilih sheet dan berkas</p>
                        </div>
                    </div>

                    <!-- Divider Line 1 -->
                    <div
                        class="flex-1 h-0.5 mx-3 sm:mx-6 transition-colors"
                        :class="currentStep > 1 ? 'bg-emerald-500' : 'bg-gray-200'"
                    ></div>

                    <!-- Step 2 Indicator -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all"
                            :class="[
                                currentStep === 2
                                    ? 'bg-[#001AFF] text-white shadow-xs ring-4 ring-indigo-50'
                                    : currentStep > 2
                                    ? 'bg-emerald-500 text-white'
                                    : 'bg-gray-100 text-gray-500'
                            ]"
                        >
                            <i v-if="currentStep > 2" class="pi pi-check text-xs"></i>
                            <span v-else>2</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-bold text-gray-900" :class="{ 'text-[#001AFF]': currentStep === 2 }">
                                Preview & Validasi
                            </p>
                            <p class="text-[11px] text-gray-400">Pemeriksaan baris data</p>
                        </div>
                    </div>

                    <!-- Divider Line 2 -->
                    <div
                        class="flex-1 h-0.5 mx-3 sm:mx-6 transition-colors"
                        :class="currentStep === 3 ? 'bg-emerald-500' : 'bg-gray-200'"
                    ></div>

                    <!-- Step 3 Indicator -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all"
                            :class="[
                                currentStep === 3
                                    ? 'bg-emerald-500 text-white shadow-xs ring-4 ring-emerald-50'
                                    : 'bg-gray-100 text-gray-500'
                            ]"
                        >
                            <i v-if="currentStep === 3" class="pi pi-check text-xs"></i>
                            <span v-else>3</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-bold text-gray-900" :class="{ 'text-emerald-600': currentStep === 3 }">
                                Selesai
                            </p>
                            <p class="text-[11px] text-gray-400">Hasil penyimpanan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- STEP 1: UPLOAD & PENGATURAN -->
            <!-- ========================================================================= -->
            <div v-if="currentStep === 1" class="space-y-6">
                <!-- Error Alert Box -->
                <div
                    v-if="uploadError"
                    class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-3"
                >
                    <i class="pi pi-exclamation-circle text-base text-rose-600 shrink-0"></i>
                    <span class="font-medium">{{ uploadError }}</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Sisi Kiri: Pilihan Target Sheet (7 Kolom) -->
                    <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                        <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Pilih Kategori Sheet</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Tentukan jenis data spreadsheet yang akan diimpor.</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-[#001AFF] border border-indigo-100">
                                Langkah 1A
                            </span>
                        </div>

                        <!-- Sheet Options Radio Cards -->
                        <div class="grid grid-cols-1 gap-2.5 pt-1">
                            <label
                                v-for="s in allowedSheets"
                                :key="s"
                                class="flex items-start gap-3 p-3.5 rounded-xl border transition-all cursor-pointer select-none"
                                :class="[
                                    form.sheet === s
                                        ? 'border-[#001AFF] bg-indigo-50/40 ring-2 ring-[#001AFF]/20'
                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/60'
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="target_sheet"
                                    :value="s"
                                    v-model="form.sheet"
                                    class="mt-1 text-[#001AFF] focus:ring-[#001AFF]"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <i :class="[sheetMeta[s]?.icon || 'pi pi-file', 'text-xs text-[#001AFF]']"></i>
                                        <span class="text-xs font-bold text-gray-900">{{ sheetMeta[s]?.title || s }}</span>
                                        <span class="font-mono text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.2 rounded">
                                            {{ s }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                                        {{ sheetMeta[s]?.desc || 'Impor data sesuai struktur format sheet.' }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Parameter Tambahan: Tahun Ajaran untuk DATA_SISWA -->
                        <div
                            v-if="form.sheet === 'DATA_SISWA' || form.sheet === 'CATATAN'"
                            class="pt-3 border-t border-gray-100 space-y-2"
                        >
                            <label class="block text-xs font-bold text-gray-900">
                                Tahun Ajaran Penempatan Siswa <span v-if="form.sheet === 'DATA_SISWA'" class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.academic_year_id"
                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-gray-50 border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Deteksi Otomatis / Sesuai Tahun Ajaran Aktif</option>
                                <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                            </select>
                            <p class="text-[11px] text-gray-400">
                                Siswa akan otomatis ditautkan ke tahun ajaran yang dipilih saat rombel dibentuk.
                            </p>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Unggah Berkas & Panduan (5 Kolom) -->
                    <div class="lg:col-span-5 space-y-5">
                        <!-- Template Download Card -->
                        <div class="bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-white rounded-2xl border border-indigo-100 p-4 shadow-2xs space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-white border border-indigo-100 flex items-center justify-center text-[#001AFF] shadow-2xs shrink-0">
                                        <i class="pi pi-file-excel text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-900">Template Resmi (.xlsx)</h4>
                                        <p class="text-[11px] text-gray-500">Struktur kolom & nama sheet tervalidasi</p>
                                    </div>
                                </div>
                                <a
                                    :href="`/imports/template?sheet=${form.sheet || 'DATA_SISWA'}`"
                                    download
                                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-[#001AFF] text-white hover:bg-[#0015cc] shadow-2xs hover:shadow-xs transition-all cursor-pointer"
                                >
                                    <i class="pi pi-download text-[11px]"></i>
                                    <span>Unduh (.xlsx)</span>
                                </a>
                            </div>
                            <div class="text-[11px] text-gray-600 bg-white/80 rounded-xl px-3 py-2 border border-indigo-50 flex items-center gap-2">
                                <i class="pi pi-info-circle text-[#001AFF] shrink-0 text-xs"></i>
                                <span>
                                    Target sheet: <strong class="text-gray-900 font-semibold">{{ form.sheet }}</strong>. File template sudah mencakup contoh data dan struktur yang valid.
                                </span>
                            </div>
                        </div>

                        <!-- Dropzone Card -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Unggah Berkas Spreadsheet</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Format .xlsx, .xls, atau .csv (maks. 10MB).</p>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-[#001AFF] border border-indigo-100">
                                    Langkah 1B
                                </span>
                            </div>

                            <!-- Hidden file input -->
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                @change="handleFileUpload"
                                class="hidden"
                            />

                            <!-- Drag and Drop Box -->
                            <div
                                @click="triggerFileInput"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                                class="p-6 border-2 border-dashed rounded-2xl text-center cursor-pointer transition-all flex flex-col items-center justify-center space-y-2 select-none"
                                :class="[
                                    isDragging
                                        ? 'border-[#001AFF] bg-indigo-50/60'
                                        : form.file
                                        ? 'border-emerald-300 bg-emerald-50/30'
                                        : 'border-gray-200 hover:border-gray-300 bg-gray-50/50 hover:bg-gray-50'
                                ]"
                            >
                                <div
                                    class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-2xs transition-colors"
                                    :class="form.file ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-50 text-[#001AFF]'"
                                >
                                    <i :class="form.file ? 'pi pi-file-excel' : 'pi pi-upload'" class="text-xl"></i>
                                </div>

                                <div v-if="form.file" class="space-y-0.5 text-center">
                                    <p class="text-xs font-bold text-gray-900 truncate max-w-xs">{{ form.file.name }}</p>
                                    <p class="text-[11px] text-gray-500 font-mono">{{ formatFileSize(form.file.size) }}</p>
                                </div>
                                <div v-else class="space-y-1">
                                    <p class="text-xs font-bold text-gray-800">
                                        Klik untuk memilih file <span class="text-gray-400 font-normal">atau seret ke sini</span>
                                    </p>
                                    <p class="text-[11px] text-gray-400">Microsoft Excel Spreadsheet (.xlsx)</p>
                                    <a
                                        :href="`/imports/template?sheet=${form.sheet || 'DATA_SISWA'}`"
                                        @click.stop
                                        download
                                        class="inline-flex items-center gap-1 text-[11px] font-medium text-[#001AFF] hover:underline pt-1 cursor-pointer"
                                    >
                                        <i class="pi pi-download text-[10px]"></i>
                                        <span>Unduh template kosong</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Clear File Button -->
                            <div v-if="form.file" class="flex items-center justify-between text-xs pt-1">
                                <span class="text-emerald-700 font-medium flex items-center gap-1.5">
                                    <i class="pi pi-check-circle text-xs"></i>
                                    <span>File siap diperiksa</span>
                                </span>
                                <button
                                    type="button"
                                    @click="removeSelectedFile"
                                    class="text-gray-400 hover:text-rose-600 transition-colors text-xs cursor-pointer"
                                >
                                    Ganti Berkas
                                </button>
                            </div>

                            <!-- Submit Upload Button -->
                            <div class="pt-3 border-t border-gray-100">
                                <button
                                    type="button"
                                    @click="uploadFile"
                                    :disabled="isUploading || !form.file || !form.sheet"
                                    class="w-full py-2.5 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    <i v-if="isUploading" class="pi pi-spin pi-spinner text-xs"></i>
                                    <i v-else class="pi pi-arrow-right text-xs"></i>
                                    <span>{{ isUploading ? 'Memeriksa & Memvalidasi File...' : 'Upload & Periksa Data (Preview)' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Guidance Box -->
                        <div class="bg-indigo-50/50 rounded-2xl border border-indigo-100 p-4 text-xs text-gray-600 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-gray-900">
                                <i class="pi pi-info-circle text-[#001AFF] text-xs"></i>
                                <span>Petunjuk Import Template</span>
                            </div>
                            <ul class="list-disc pl-4 space-y-1 text-[11px] text-gray-600 leading-relaxed">
                                <li>Pastikan nama kolom pada baris pertama sesuai dengan format standar DISIPLIN PRO.</li>
                                <li>Format tanggal pada kolom tanggal disarankan <code>YYYY-MM-DD</code>.</li>
                                <li>Data akan dipratinjau terlebih dahulu pada Langkah 2 sebelum disimpan permanen ke database.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- STEP 2: PREVIEW & VALIDASI -->
            <!-- ========================================================================= -->
            <div v-if="currentStep === 2 && previewState" class="space-y-6">
                <!-- 4 KPI Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Valid -->
                    <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-2xs space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 flex items-center gap-1.5">
                            <i class="pi pi-check-circle text-xs"></i>
                            <span>Valid / Siap Simpan</span>
                        </span>
                        <p class="text-3xl font-bold text-gray-900">{{ previewState.stats.valid }}</p>
                        <p class="text-[11px] text-gray-400">Baris data lolos validasi</p>
                    </div>

                    <!-- Invalid -->
                    <div class="bg-white rounded-2xl p-5 border border-rose-100 shadow-2xs space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600 flex items-center gap-1.5">
                            <i class="pi pi-times-circle text-xs"></i>
                            <span>Invalid / Error</span>
                        </span>
                        <p class="text-3xl font-bold text-gray-900">{{ previewState.stats.invalid }}</p>
                        <p class="text-[11px] text-gray-400">Baris gagal validasi skema</p>
                    </div>

                    <!-- Duplicate -->
                    <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-2xs space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 flex items-center gap-1.5">
                            <i class="pi pi-copy text-xs"></i>
                            <span>Duplikat</span>
                        </span>
                        <p class="text-3xl font-bold text-gray-900">{{ previewState.stats.duplicate }}</p>
                        <p class="text-[11px] text-gray-400">Data telah ada sebelumnya</p>
                    </div>

                    <!-- Skipped -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-2xs space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 flex items-center gap-1.5">
                            <i class="pi pi-forward text-xs"></i>
                            <span>Dilewati / Skip</span>
                        </span>
                        <p class="text-3xl font-bold text-gray-900">{{ previewState.stats.skipped }}</p>
                        <p class="text-[11px] text-gray-400">Baris kosong atau diabaikan</p>
                    </div>
                </div>

                <!-- Error List Card (If any) -->
                <div v-if="previewState.stats.invalid > 0" class="bg-white rounded-2xl border border-rose-100 shadow-sm p-5 space-y-3">
                    <div class="flex items-center gap-2 text-rose-700">
                        <i class="pi pi-exclamation-triangle text-base"></i>
                        <h4 class="text-xs font-bold uppercase tracking-wider">
                            Rincian Kesalahan Validasi (Maks. 50 ditampilkan)
                        </h4>
                    </div>
                    <div class="bg-rose-50/60 p-4 rounded-xl max-h-44 overflow-y-auto border border-rose-100 text-xs text-rose-700 font-mono space-y-1.5">
                        <div v-for="(err, i) in previewState.errors" :key="i" class="flex items-start gap-2">
                            <span class="text-rose-400">&bull;</span>
                            <span>{{ err }}</span>
                        </div>
                    </div>
                </div>

                <!-- Warning Callout for SETUP Sheet -->
                <div v-if="form.sheet === 'SETUP'" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                    <label class="flex items-center gap-3 font-bold cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="confirmSetup"
                            class="rounded text-amber-600 focus:ring-amber-500 h-4 w-4"
                        />
                        <span>Saya mengonfirmasi bahwa tindakan ini akan menimpa seluruh konfigurasi sekolah saat ini.</span>
                    </label>
                </div>

                <!-- Preview Table Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Pratinjau Baris Data (Maks. 100 Baris)</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Periksa keakuratan kolom sebelum melakukan commit ke database.</p>
                        </div>
                        <span class="text-xs font-medium text-gray-400">
                            Total Pratinjau: <strong>{{ previewState.preview_rows?.length || 0 }}</strong> baris
                        </span>
                    </div>

                    <!-- PrimeVue DataTable Preview -->
                    <div class="overflow-x-auto">
                        <DataTable
                            :value="previewState.preview_rows"
                            class="p-datatable-sm"
                            responsiveLayout="scroll"
                            :pt="{
                                table: { class: 'w-full text-left border-collapse text-xs' },
                                thead: { class: 'bg-gray-50/70 border-b border-gray-200' },
                                headerRow: { class: 'border-b border-gray-200 text-[11px] font-bold uppercase tracking-wider text-gray-500' },
                                row: { class: 'border-b border-gray-100 hover:bg-gray-50/80 transition-colors' }
                            }"
                        >
                            <!-- Kolom Status Validasi -->
                            <Column header="Status" class="py-2.5 px-3 w-28 whitespace-nowrap">
                                <template #body="{ data }">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border tracking-wider"
                                        :class="getStatusBadgeConfig(data.status).class"
                                    >
                                        {{ getStatusBadgeConfig(data.status).label }}
                                    </span>
                                </template>
                            </Column>

                            <!-- Kolom Dinamis Berdasarkan Header Excel -->
                            <Column
                                v-for="header in previewHeaders"
                                :key="header"
                                :header="header"
                                class="py-2.5 px-3 text-xs max-w-xs truncate"
                            >
                                <template #body="{ data }">
                                    <span class="text-gray-800">{{ data.original?.[header] ?? '-' }}</span>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Action Buttons Footer -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            @click="currentStep = 1"
                            class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer text-center"
                        >
                            <i class="pi pi-arrow-left text-xs mr-1.5"></i>
                            <span>Batal & Kembali ke Upload</span>
                        </button>

                        <button
                            type="button"
                            @click="commitImport"
                            :disabled="isCommitting || (form.sheet === 'SETUP' && !confirmSetup) || previewState.stats.valid === 0"
                            class="px-6 py-2.5 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <i v-if="isCommitting" class="pi pi-spin pi-spinner text-xs"></i>
                            <i v-else class="pi pi-check text-xs"></i>
                            <span v-if="isCommitting">Sedang Menyimpan {{ previewState.stats.valid }} Baris Data...</span>
                            <span v-else>Proses Simpan ({{ previewState.stats.valid }} Baris Valid)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- STEP 3: SELESAI -->
            <!-- ========================================================================= -->
            <div v-if="currentStep === 3 && commitResult" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center space-y-4 max-w-xl mx-auto py-14">
                <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-2xl border border-emerald-100 shadow-xs">
                    <i class="pi pi-check-circle text-3xl"></i>
                </div>

                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-gray-900">Import Berhasil Diselesaikan!</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto leading-relaxed">
                        Data dari berkas spreadsheet Anda telah berhasil diproses dan disimpan secara aman ke dalam database sistem.
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-200">
                    <i class="pi pi-check text-xs"></i>
                    <span>{{ commitResult.success }} baris berhasil disimpan</span>
                </div>

                <div class="pt-6 border-t border-gray-100 flex flex-wrap items-center justify-center gap-3">
                    <button
                        type="button"
                        @click="resetWizard"
                        class="px-5 py-2.5 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        <i class="pi pi-upload text-xs mr-1.5"></i>
                        <span>Import Berkas Lain</span>
                    </button>

                    <Link
                        href="/students"
                        class="px-5 py-2.5 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>Lihat Data Siswa</span>
                        <i class="pi pi-arrow-right text-xs"></i>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
