<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    achievement?: {
        id: number;
        code: string;
        name: string;
        level: string;
        deduction_points: number;
        description: string;
        frequency_rule: string;
        is_active: boolean;
    };
}>();

const isEditing = !!props.achievement;

const form = useForm({
    code: props.achievement?.code || '',
    name: props.achievement?.name || '',
    level: props.achievement?.level || 'SEKOLAH',
    deduction_points: props.achievement?.deduction_points || 1,
    description: props.achievement?.description || '',
    frequency_rule: props.achievement?.frequency_rule || '',
    is_active: props.achievement?.is_active ?? true,
});

const submit = () => {
    if (isEditing) {
        form.put(`/achievements/${props.achievement!.id}`);
    } else {
        form.post('/achievements');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Prestasi - Sekolah Disiplin' : 'Tambah Prestasi - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/master-data?tab=achievements" class="text-gray-500 hover:text-gray-900 transition-colors">Master Data</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Prestasi' : 'Tambah Prestasi' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Master Prestasi' : 'Tambah Master Prestasi' }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Silakan lengkapi formulir di bawah ini untuk menyimpan data prestasi dan pengurang poin.</p>
            </div>

            <div class="bg-white rounded-[24px] p-7 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006]">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Code -->
                        <div>
                            <label for="code" class="block text-xs font-semibold text-gray-700 mb-1.5">Kode Prestasi <span class="text-red-600">*</span></label>
                            <input
                                id="code"
                                v-model="form.code"
                                type="text"
                                required
                                placeholder="Contoh: PRS-001"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.code }}</p>
                        </div>

                        <!-- Level -->
                        <div>
                            <label for="level" class="block text-xs font-semibold text-gray-700 mb-1.5">Tingkat / Level <span class="text-red-600">*</span></label>
                            <select
                                id="level"
                                v-model="form.level"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="SEKOLAH">Sekolah</option>
                                <option value="KABUPATEN">Kabupaten / Kota</option>
                                <option value="PROVINSI">Provinsi</option>
                                <option value="NASIONAL">Nasional</option>
                                <option value="INTERNASIONAL">Internasional</option>
                            </select>
                            <p v-if="form.errors.level" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.level }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Prestasi <span class="text-red-600">*</span></label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Juara 1 Olimpiade Sains Nasional"
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Deduction Points -->
                    <div>
                        <label for="deduction_points" class="block text-xs font-semibold text-gray-700 mb-1.5">Poin Pengurang <span class="text-red-600">*</span></label>
                        <input
                            id="deduction_points"
                            v-model="form.deduction_points"
                            type="number"
                            min="1"
                            required
                            class="block w-full md:w-1/3 px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.deduction_points }"
                        />
                        <p class="mt-1 text-xs text-gray-500">Masukkan angka bulat positif. Sistem akan otomatis mengubahnya menjadi pengurang total poin (minus).</p>
                        <p v-if="form.errors.deduction_points" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.deduction_points }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                        <!-- Frequency Rule -->
                        <div>
                            <label for="frequency_rule" class="block text-xs font-semibold text-gray-700 mb-1.5">Aturan Frekuensi (Opsional)</label>
                            <textarea
                                id="frequency_rule"
                                v-model="form.frequency_rule"
                                rows="3"
                                placeholder="Contoh: Maksimal diklaim 1 kali per semester"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all resize-y"
                            ></textarea>
                            <p v-if="form.errors.frequency_rule" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.frequency_rule }}</p>
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 border-gray-300 text-[#6366F1] focus:ring-[#6366F1] rounded cursor-pointer"
                        />
                        <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Status Aktif</label>
                    </div>
                    <p class="text-xs text-gray-500 ml-6">Jika tidak aktif, prestasi ini tidak dapat dipilih saat pencatatan.</p>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link 
                            href="/master-data?tab=achievements"
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
