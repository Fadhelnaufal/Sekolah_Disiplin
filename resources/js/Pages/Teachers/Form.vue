<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    teacher?: {
        id: number;
        employee_number: string | null;
        name: string;
        phone: string | null;
        email: string | null;
        status: string;
    };
}>();

const isEditing = !!props.teacher;

const form = useForm({
    employee_number: props.teacher?.employee_number || '',
    name: props.teacher?.name || '',
    phone: props.teacher?.phone || '',
    email: props.teacher?.email || '',
    status: props.teacher?.status || 'Active',
    create_account: false,
});

const submit = () => {
    if (isEditing) {
        form.put(`/teachers/${props.teacher!.id}`);
    } else {
        form.post('/teachers');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Guru - Sekolah Disiplin' : 'Tambah Guru - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/teachers" class="text-gray-500 hover:text-gray-900 transition-colors">Data Guru</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Data' : 'Tambah Data' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Profil Guru' : 'Tambah Guru Baru' }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Silakan lengkapi informasi identitas guru di bawah ini.</p>
            </div>

            <div class="bg-white rounded-[24px] p-7 sm:p-8 border border-gray-100 shadow-[0_2px_20px_#00000006]">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Employee Number -->
                        <div>
                            <label for="employee_number" class="block text-xs font-semibold text-gray-700 mb-1.5">NIP / NUPTK</label>
                            <input
                                id="employee_number"
                                v-model="form.employee_number"
                                type="text"
                                placeholder="Opsional"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.employee_number }"
                            />
                            <p v-if="form.errors.employee_number" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.employee_number }}</p>
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
                                <option value="Cuti">Cuti</option>
                                <option value="Pindah">Pindah</option>
                                <option value="Pensiun">Pensiun</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Lengkap Guru <span class="text-red-600">*</span></label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Sertakan gelar jika ada"
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-gray-700 mb-1.5">No. HP / WhatsApp</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                placeholder="Contoh: 08123456789"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-gray-700 mb-1.5">Alamat Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="guru@sekolah.sch.id"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-4" v-if="form.email">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <div class="flex items-center h-5">
                                <input
                                    type="checkbox"
                                    v-model="form.create_account"
                                    class="w-4 h-4 text-[#6366F1] border-gray-300 rounded focus:ring-[#6366F1]"
                                >
                            </div>
                            <div class="text-sm">
                                <p class="font-medium text-gray-900">{{ isEditing ? 'Perbarui/Buat Akun Login (Default Pass: password)' : 'Buat Akun Login (Default Pass: password)' }}</p>
                                <p class="text-gray-500 text-xs mt-0.5">Guru akan diberikan role "Teacher" dan bisa login menggunakan email yang dimasukkan.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link 
                            href="/teachers"
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
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Data Guru' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
