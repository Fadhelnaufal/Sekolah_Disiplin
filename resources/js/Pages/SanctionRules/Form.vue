<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    sanctionRule?: {
        id: number;
        level: string;
        min_points: number;
        max_points: number;
        action: string;
        executor: string;
        description: string;
        is_active: boolean;
    };
}>();

const isEditing = !!props.sanctionRule;

const form = useForm({
    level: props.sanctionRule?.level || 'SP 1',
    min_points: props.sanctionRule?.min_points || 10,
    max_points: props.sanctionRule?.max_points || 20,
    action: props.sanctionRule?.action || '',
    executor: props.sanctionRule?.executor || 'Wali Kelas',
    description: props.sanctionRule?.description || '',
    is_active: props.sanctionRule?.is_active ?? true,
});

const submit = () => {
    if (isEditing) {
        form.put(`/sanction-rules/${props.sanctionRule!.id}`);
    } else {
        form.post('/sanction-rules');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Aturan Sanksi - Sekolah Disiplin' : 'Tambah Aturan Sanksi - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/master-data?tab=sanctions" class="text-gray-500 hover:text-gray-900 transition-colors">Master Data</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Aturan Sanksi' : 'Tambah Aturan Sanksi' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Aturan Sanksi' : 'Tambah Aturan Sanksi' }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Tentukan rentang poin dan tindak lanjut yang sesuai berdasarkan Tata Tertib.</p>
            </div>

            <div class="bg-white rounded-[24px] p-7 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006]">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Level -->
                        <div>
                            <label for="level" class="block text-xs font-semibold text-gray-700 mb-1.5">Tingkat / Kategori Sanksi <span class="text-red-600">*</span></label>
                            <input
                                id="level"
                                v-model="form.level"
                                type="text"
                                required
                                placeholder="Contoh: Peringatan Lisan, SP 1, SP 2"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.level }"
                            />
                            <p v-if="form.errors.level" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.level }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <!-- Min Points -->
                        <div>
                            <label for="min_points" class="block text-xs font-semibold text-gray-700 mb-1.5">Poin Minimal <span class="text-red-600">*</span></label>
                            <input
                                id="min_points"
                                v-model="form.min_points"
                                type="number"
                                min="0"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.min_points }"
                            />
                            <p v-if="form.errors.min_points" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.min_points }}</p>
                        </div>

                        <!-- Max Points -->
                        <div>
                            <label for="max_points" class="block text-xs font-semibold text-gray-700 mb-1.5">Poin Maksimal <span class="text-red-600">*</span></label>
                            <input
                                id="max_points"
                                v-model="form.max_points"
                                type="number"
                                min="0"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.max_points }"
                            />
                            <p v-if="form.errors.max_points" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.max_points }}</p>
                        </div>
                    </div>

                    <!-- Action -->
                    <div>
                        <label for="action" class="block text-xs font-semibold text-gray-700 mb-1.5">Tindak Lanjut <span class="text-red-600">*</span></label>
                        <input
                            id="action"
                            v-model="form.action"
                            type="text"
                            required
                            placeholder="Contoh: Pemanggilan Orang Tua dan Penandatanganan Surat Perjanjian"
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.action }"
                        />
                        <p v-if="form.errors.action" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.action }}</p>
                    </div>

                    <!-- Executor -->
                    <div>
                        <label for="executor" class="block text-xs font-semibold text-gray-700 mb-1.5">Eksekutor Sanksi <span class="text-red-600">*</span></label>
                        <input
                            id="executor"
                            v-model="form.executor"
                            type="text"
                            required
                            placeholder="Contoh: Wali Kelas / Guru BK / Kepala Sekolah"
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.executor }"
                        />
                        <p v-if="form.errors.executor" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.executor }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-xs font-semibold text-gray-700 mb-1.5">Deskripsi (Opsional)</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            placeholder="Penjelasan detail tentang sanksi atau syarat tertentu..."
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
                        <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Status Aktif</label>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link 
                            href="/master-data?tab=sanctions"
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
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Aturan Sanksi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
