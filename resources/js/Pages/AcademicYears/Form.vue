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
    <Head :title="isEditing ? 'Edit Tahun Ajaran - Disiplin Pro' : 'Tambah Tahun Ajaran - Disiplin Pro'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/academic-years" class="text-[#697586] hover:text-[#202B3C] transition-colors">Tahun Ajaran</Link>
                <span class="text-[#E5E9EF]">/</span>
                <span class="text-[#202B3C]">{{ isEditing ? 'Edit Data' : 'Tambah Data' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-[#202B3C] tracking-tight">{{ isEditing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran' }}</h1>
                <p class="text-[#697586] mt-1 text-sm">Silakan lengkapi informasi tahun ajaran.</p>
            </div>

            <div class="bg-white border border-[#E5E9EF] shadow-sm">
                <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-semibold text-[#202B3C] mb-2">Tahun Ajaran <span class="text-[#B42318]">*</span></label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: 2023/2024"
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                                :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.name }}</p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-semibold text-[#202B3C] mb-2">Status <span class="text-[#B42318]">*</span></label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors appearance-none"
                            >
                                <option value="Active">Aktif</option>
                                <option value="Inactive">Tidak Aktif</option>
                            </select>
                            <p class="mt-1.5 text-xs text-[#697586]">Menyetel status "Aktif" akan secara otomatis menonaktifkan tahun ajaran lain.</p>
                            <p v-if="form.errors.status" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-semibold text-[#202B3C] mb-2">Tanggal Mulai <span class="text-[#B42318]">*</span></label>
                            <input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                required
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                                :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.start_date }"
                            />
                            <p v-if="form.errors.start_date" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.start_date }}</p>
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-semibold text-[#202B3C] mb-2">Tanggal Selesai <span class="text-[#B42318]">*</span></label>
                            <input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                required
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                                :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.end_date }"
                            />
                            <p v-if="form.errors.end_date" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-[#E5E9EF] flex items-center justify-end gap-3">
                        <Link 
                            href="/academic-years"
                            class="px-4 py-2 text-sm font-medium text-[#697586] hover:text-[#202B3C] bg-white border border-[#E5E9EF] hover:bg-[#F7F8FA] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E5E9EF]"
                        >
                            Batal
                        </Link>
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#1D3557] hover:bg-[#0D1B2A] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D3557] disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center"
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
