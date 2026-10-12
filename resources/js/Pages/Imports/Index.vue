<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    academicYears: Array,
    semesters: Array,
});

const userRoles = usePage().props.auth.roles || [];
const isSuperAdmin = userRoles.includes('Super Admin');
const isTataTertib = userRoles.includes('Tata Tertib');
const isBK = userRoles.includes('BK');

const allowedSheets = computed(() => {
    let sheets = [];
    if (isSuperAdmin) {
        sheets = ['SETUP', 'MASTER_PELANGGARAN', 'MASTER_PRESTASI', 'MASTER_SANKSI', 'DATA_SISWA', 'CATATAN', 'PEMBINAAN'];
    } else {
        if (isTataTertib) {
            sheets.push('DATA_SISWA', 'CATATAN', 'MASTER_PELANGGARAN', 'MASTER_PRESTASI', 'MASTER_SANKSI');
        }
        if (isBK) {
            sheets.push('PEMBINAAN', 'CATATAN');
        }
    }
    return [...new Set(sheets)];
});

const currentStep = ref(1);
const form = useForm({
    sheet: '',
    file: null,
    academic_year_id: '',
    semester_id: '',
});

const previewState = ref(null);
const isUploading = ref(false);
const isCommitting = ref(false);
const uploadError = ref(null);
const commitResult = ref(null);
const confirmSetup = ref(false);

const handleFileUpload = (e) => {
    form.file = e.target.files[0];
};

const uploadFile = () => {
    if (!form.sheet || !form.file) return;
    if (form.sheet === 'DATA_SISWA' && !form.academic_year_id) {
        uploadError.value = "Tahun Ajaran wajib dipilih untuk import Data Siswa.";
        return;
    }
    
    isUploading.value = true;
    uploadError.value = null;
    
    // Use raw fetch for manual json mapping since we need the JSON response back
    const formData = new FormData();
    formData.append('sheet', form.sheet);
    formData.append('file', form.file);
    if (form.academic_year_id) formData.append('academic_year_id', form.academic_year_id);
    if (form.semester_id) formData.append('semester_id', form.semester_id);
    
    fetch('/imports/upload', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': usePage().props.auth.csrf || document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: formData
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
        alert("Harap centang konfirmasi penimpaan konfigurasi SETUP sekolah.");
        return;
    }
    
    isCommitting.value = true;
    
    fetch('/imports/commit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': usePage().props.auth.csrf || document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({ preview_id: previewState.value.preview_id })
    })
    .then(async (res) => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || data.message || 'Gagal saat commit ke database.');
        commitResult.value = data.result;
        currentStep.value = 3;
    })
    .catch((err) => {
        alert(err.message);
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
};
</script>

<template>
    <Head title="Excel Import" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Import Data Excel</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Wizard Steps -->
                    <div class="flex items-center mb-8 border-b pb-4">
                        <div :class="{'text-blue-600 font-bold': currentStep === 1, 'text-gray-400': currentStep !== 1}" class="flex-1 text-center">
                            1. Upload & Pengaturan
                        </div>
                        <div :class="{'text-blue-600 font-bold': currentStep === 2, 'text-gray-400': currentStep !== 2}" class="flex-1 text-center border-l">
                            2. Preview & Validasi
                        </div>
                        <div :class="{'text-blue-600 font-bold': currentStep === 3, 'text-gray-400': currentStep !== 3}" class="flex-1 text-center border-l">
                            3. Selesai
                        </div>
                    </div>

                    <!-- STEP 1 -->
                    <div v-if="currentStep === 1">
                        <div v-if="uploadError" class="mb-4 bg-red-50 text-red-700 p-4 rounded">
                            {{ uploadError }}
                        </div>
                        
                        <div class="space-y-4 max-w-xl mx-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Target Sheet</label>
                                <select v-model="form.sheet" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                                    <option value="" disabled>Pilih Sheet...</option>
                                    <option v-for="s in allowedSheets" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>
                            
                            <div v-if="form.sheet === 'DATA_SISWA' || form.sheet === 'CATATAN'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran Aktif (Wajib untuk Data Siswa)</label>
                                <select v-model="form.academic_year_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                                    <option value="">Deteksi Otomatis / Abaikan</option>
                                    <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                                </select>
                            </div>
                            
                            <div v-if="form.sheet === 'CATATAN' || form.sheet === 'PEMBINAAN'">
                                <p class="text-sm text-gray-500 italic">Catatan: Semester akan dideteksi secara otomatis dari kolom Tanggal Excel.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">File Excel (.xlsx)</label>
                                <input type="file" accept=".xlsx,.xls,.csv" @change="handleFileUpload" class="block w-full border border-gray-300 rounded-md p-2">
                            </div>
                            
                            <div class="pt-4 text-right">
                                <button @click="uploadFile" :disabled="isUploading || !form.file || !form.sheet" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 disabled:opacity-50">
                                    {{ isUploading ? 'Memproses...' : 'Upload & Preview' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- STEP 2 -->
                    <div v-if="currentStep === 2 && previewState">
                        <div class="grid grid-cols-4 gap-4 mb-6">
                            <div class="bg-green-50 p-4 rounded text-center border border-green-200">
                                <span class="block text-green-700 font-bold text-2xl">{{ previewState.stats.valid }}</span>
                                <span class="text-sm text-green-600">Valid / Update</span>
                            </div>
                            <div class="bg-red-50 p-4 rounded text-center border border-red-200">
                                <span class="block text-red-700 font-bold text-2xl">{{ previewState.stats.invalid }}</span>
                                <span class="text-sm text-red-600">Invalid / Error</span>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded text-center border border-yellow-200">
                                <span class="block text-yellow-700 font-bold text-2xl">{{ previewState.stats.duplicate }}</span>
                                <span class="text-sm text-yellow-600">Duplikat Kandidat</span>
                            </div>
                            <div class="bg-gray-50 p-4 rounded text-center border border-gray-200">
                                <span class="block text-gray-700 font-bold text-2xl">{{ previewState.stats.skipped }}</span>
                                <span class="text-sm text-gray-600">Baris Dilewati</span>
                            </div>
                        </div>
                        
                        <div v-if="previewState.stats.invalid > 0" class="mb-6">
                            <h4 class="font-bold text-red-700 mb-2">Daftar Error (Maks. 50 ditampilkan)</h4>
                            <ul class="list-disc pl-5 text-sm text-red-600 bg-red-50 p-4 rounded h-32 overflow-y-auto">
                                <li v-for="(err, i) in previewState.errors" :key="i">{{ err }}</li>
                            </ul>
                        </div>
                        
                        <div class="mb-6">
                            <h4 class="font-bold text-gray-700 mb-2">Preview 100 Baris Pertama</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left border">
                                    <thead class="bg-gray-50 text-gray-700 border-b">
                                        <tr>
                                            <th class="p-2 border-r">Status</th>
                                            <th class="p-2 border-r" v-for="key in Object.keys(previewState.preview_rows[0]?.original || {})" :key="key">{{ key }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in previewState.preview_rows" :key="i" class="border-b" :class="{'bg-red-50': row.status === 'invalid', 'bg-yellow-50': row.status === 'duplicate', 'bg-gray-100': row.status === 'skip'}">
                                            <td class="p-2 border-r font-bold">
                                                <span v-if="row.status === 'valid'" class="text-green-600">VALID</span>
                                                <span v-else-if="row.status === 'update'" class="text-blue-600">UPDATE</span>
                                                <span v-else-if="row.status === 'invalid'" class="text-red-600">ERROR</span>
                                                <span v-else-if="row.status === 'duplicate'" class="text-yellow-600">DUPLIKAT</span>
                                                <span v-else class="text-gray-500">SKIP</span>
                                            </td>
                                            <td class="p-2 border-r truncate max-w-xs" v-for="(val, k) in row.original" :key="k">{{ val }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div v-if="form.sheet === 'SETUP'" class="mb-6 bg-yellow-50 p-4 border border-yellow-200 rounded">
                            <label class="flex items-center space-x-3 text-yellow-800 font-bold">
                                <input type="checkbox" v-model="confirmSetup" class="form-checkbox h-5 w-5 text-yellow-600">
                                <span>Saya paham bahwa ini akan MENIMPA Konfigurasi Sekolah saat ini.</span>
                            </label>
                        </div>
                        
                        <div class="flex justify-between items-center pt-4 border-t">
                            <button @click="currentStep = 1" class="text-gray-600 hover:text-gray-900 underline">Batal & Kembali</button>
                            <button @click="commitImport" :disabled="isCommitting || (form.sheet === 'SETUP' && !confirmSetup)" class="bg-green-600 text-white px-6 py-2 rounded shadow hover:bg-green-700 disabled:opacity-50 flex items-center">
                                <span v-if="isCommitting">Menyimpan {{ previewState.stats.valid }} baris...</span>
                                <span v-else>Proses Import ({{ previewState.stats.valid }} baris)</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- STEP 3 -->
                    <div v-if="currentStep === 3 && commitResult" class="text-center py-12">
                        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">✓</div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Import Selesai!</h3>
                        <p class="text-gray-600 mb-6">Berhasil menyimpan <strong class="text-green-600">{{ commitResult.success }}</strong> baris ke dalam database.</p>
                        <button @click="resetWizard" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700">Import File Lain</button>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
