<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    violation?: {
        id: number;
        code: string;
        name: string;
        category: string;
        points: number;
        description: string;
        is_active: boolean;
    };
}>();

const isEditing = !!props.violation;

const form = useForm({
    code: props.violation?.code || '',
    name: props.violation?.name || '',
    category: props.violation?.category || 'RINGAN',
    points: props.violation?.points || 1,
    description: props.violation?.description || '',
    is_active: props.violation?.is_active ?? true,
});

const submit = () => {
    if (isEditing) {
        form.put(`/violations/${props.violation!.id}`);
    } else {
        form.post('/violations');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Pelanggaran - Sekolah Disiplin' : 'Tambah Pelanggaran - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/master-data?tab=violations" class="text-gray-500 hover:text-gray-900 transition-colors">Master Data</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Pelanggaran' : 'Tambah Pelanggaran' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Master Pelanggaran' : 'Tambah Master Pelanggaran' }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Lengkapi rincian formulir di bawah ini untuk menyimpan data pelanggaran.</p>
            </div>

            <div class="bg-white rounded-[24px] p-7 sm:p-8 shadow-[0_2px_20px_#00000006] border border-gray-100">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Code -->
                        <div>
                            <label for="code" class="block text-xs font-semibold text-gray-700 mb-1.5">Kode Pelanggaran <span class="text-red-600">*</span></label>
                            <input
                                id="code"
                                v-model="form.code"
                                type="text"
                                required
                                placeholder="Contoh: PLG-001"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.code }}</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-xs font-semibold text-gray-700 mb-1.5">Kategori <span class="text-red-600">*</span></label>
                            <select
                                id="category"
                                v-model="form.category"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="RINGAN">Ringan</option>
                                <option value="SEDANG">Sedang</option>
                                <option value="BERAT">Berat</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.category }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Pelanggaran <span class="text-red-600">*</span></label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Terlambat datang ke sekolah"
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Points -->
                    <div>
                        <label for="points" class="block text-xs font-semibold text-gray-700 mb-1.5">Bobot Poin <span class="text-red-600">*</span></label>
                        <input
                            id="points"
                            v-model="form.points"
                            type="number"
                            min="1"
                            required
                            class="block w-full md:w-1/3 px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.points }"
                        />
                        <p class="mt-1 text-xs text-gray-500">Masukkan angka bulat positif (misal: 5, 10, 50).</p>
                        <p v-if="form.errors.points" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.points }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-xs font-semibold text-gray-700 mb-1.5">Deskripsi (Opsional)</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all resize-y"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 border-gray-300 text-[#6366F1] focus:ring-[#6366F1] rounded cursor-pointer"
                        />
                        <label for="is_active" class="text-sm font-medium text-gray-800 cursor-pointer select-none">Status Aktif</label>
                    </div>
                    <p class="text-xs text-gray-500 ml-6">Jika tidak aktif, pelanggaran ini tidak akan muncul di form pencatatan kedisiplinan.</p>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link 
                            href="/master-data?tab=violations"
                            class="px-5 py-2.5 text-xs font-semibold text-gray-700 hover:text-gray-900 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300 inline-flex items-center gap-1.5"
                        >
                            <i class="pi pi-arrow-left text-[10px]"></i>
                            Kembali
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-6 py-2.5 text-xs font-semibold text-white bg-[#6366F1] hover:bg-[#4F46E5] rounded-full transition-all shadow-[0_4px_14px_#6366F140] focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Master' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
