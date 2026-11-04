<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    student?: {
        id: number;
        nis: string;
        name: string;
        class_room_id: number;
        parent_name: string;
        parent_phone: string;
        status: string;
        notes: string;
    };
    classRooms: Array<{
        id: number;
        name: string;
    }>;
}>();

const isEditing = !!props.student;

const form = useForm({
    nis: props.student?.nis || '',
    name: props.student?.name || '',
    class_room_id: props.student?.class_room_id || '',
    parent_name: props.student?.parent_name || '',
    parent_phone: props.student?.parent_phone || '',
    status: props.student?.status || 'Active',
    notes: props.student?.notes || '',
});

const submit = () => {
    if (isEditing) {
        form.put(`/students/${props.student!.id}`);
    } else {
        form.post('/students');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Siswa - Sekolah Disiplin' : 'Tambah Siswa - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/students" class="text-gray-500 hover:text-gray-900 transition-colors">Data Siswa</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Data' : 'Tambah Data' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Profil Siswa' : 'Tambah Siswa Baru' }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Lengkapi formulir di bawah ini dengan data diri siswa yang sesuai.</p>
            </div>

            <div class="bg-white rounded-[24px] p-7 sm:p-8 shadow-[0_2px_20px_#00000006] border border-gray-100">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <h3 class="text-xs font-bold text-gray-900 border-b border-gray-100 pb-2 uppercase tracking-wider">Data Akademik</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- NIS -->
                        <div>
                            <label for="nis" class="block text-xs font-semibold text-gray-700 mb-1.5">NIS (Nomor Induk Siswa) <span class="text-red-600">*</span></label>
                            <input
                                id="nis"
                                v-model="form.nis"
                                type="text"
                                required
                                placeholder="Contoh: 10102030"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.nis }"
                            />
                            <p v-if="form.errors.nis" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.nis }}</p>
                        </div>

                        <!-- Class -->
                        <div>
                            <label for="class_room_id" class="block text-xs font-semibold text-gray-700 mb-1.5">Kelas <span class="text-red-600">*</span></label>
                            <select
                                id="class_room_id"
                                v-model="form.class_room_id"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.class_room_id }"
                            >
                                <option value="" disabled>Pilih Kelas</option>
                                <option v-for="c in classRooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p v-if="form.errors.class_room_id" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.class_room_id }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Lengkap Siswa <span class="text-red-600">*</span></label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Sesuai akta kelahiran atau ijazah sebelumnya"
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <h3 class="text-xs font-bold text-gray-900 border-b border-gray-100 pb-2 pt-2 uppercase tracking-wider">Data Wali & Informasi Tambahan</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Parent Name -->
                        <div>
                            <label for="parent_name" class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Orang Tua / Wali</label>
                            <input
                                id="parent_name"
                                v-model="form.parent_name"
                                type="text"
                                placeholder="Nama Lengkap Wali"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            />
                            <p v-if="form.errors.parent_name" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.parent_name }}</p>
                        </div>

                        <!-- Parent Phone -->
                        <div>
                            <label for="parent_phone" class="block text-xs font-semibold text-gray-700 mb-1.5">No. HP / WhatsApp Wali</label>
                            <input
                                id="parent_phone"
                                v-model="form.parent_phone"
                                type="text"
                                placeholder="Contoh: 08123456789"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                            />
                            <p v-if="form.errors.parent_phone" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.parent_phone }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-xs font-semibold text-gray-700 mb-1.5">Status Siswa <span class="text-red-600">*</span></label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="Active">Aktif</option>
                                <option value="Lulus">Lulus</option>
                                <option value="Pindah">Pindah</option>
                                <option value="Dikeluarkan">Dikeluarkan</option>
                                <option value="Cuti">Cuti</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-xs font-semibold text-gray-700 mb-1.5">Catatan Khusus (Opsional)</label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            placeholder="Informasi kesehatan, riwayat khusus, dll."
                            class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all resize-y"
                        ></textarea>
                        <p v-if="form.errors.notes" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.notes }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link 
                            href="/students"
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
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Data Siswa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
