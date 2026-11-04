<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    academicYear?: {
        id: number;
        name: string;
        start_date: string;
        end_date: string;
        status: string;
    };
}>();

const isEditing = !!props.academicYear;

// Extract YYYY-MM-DD from ISO date string
const formatDateForInput = (dateStr?: string) => {
    if (!dateStr) return '';
    return dateStr.split('T')[0];
};

const form = useForm({
    name: props.academicYear?.name || '',
    start_date: formatDateForInput(props.academicYear?.start_date),
    end_date: formatDateForInput(props.academicYear?.end_date),
    status: props.academicYear?.status || 'Active',
});

const submit = () => {
    if (isEditing) {
        form.put(`/academic-years/${props.academicYear!.id}`);
    } else {
        form.post('/academic-years');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Tahun Ajaran - Sekolah Disiplin' : 'Tambah Tahun Ajaran - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/academic-years" class="text-gray-500 hover:text-gray-900 transition-colors">Tahun Ajaran</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Data' : 'Tambah Data' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran' }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Lengkapi rincian kalender dan periode tahun ajaran akademik.</p>
            </div>

            <div class="bg-white rounded-[24px] p-7 sm:p-8 shadow-[0_2px_20px_#00000006] border border-gray-100">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Tahun Ajaran <span class="text-red-600">*</span></label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: 2026/2027"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-xs font-semibold text-gray-700 mb-1.5">Status <span class="text-red-600">*</span></label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="Active">Aktif</option>
                                <option value="Inactive">Tidak Aktif</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Status "Aktif" akan secara otomatis menonaktifkan tahun ajaran sebelumnya.</p>
                            <p v-if="form.errors.status" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal Mulai <span class="text-red-600">*</span></label>
                            <input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.start_date }"
                            />
                            <p v-if="form.errors.start_date" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.start_date }}</p>
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal Selesai <span class="text-red-600">*</span></label>
                            <input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.end_date }"
                            />
                            <p v-if="form.errors.end_date" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link 
                            href="/academic-years"
                            class="px-5 py-2.5 text-xs font-semibold text-gray-700 hover:text-gray-900 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Batal
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
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Tahun Ajaran' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
