<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    classRoom?: {
        id: number;
        academic_year_id: number;
        name: string;
        grade_level: number;
        homeroom_teacher_id: number | null;
        status: string;
    };
    teachers: Array<{
        id: number;
        name: string;
    }>;
    academicYears: Array<{
        id: number;
        name: string;
        status: string;
    }>;
}>();

const isEditing = !!props.classRoom;

const form = useForm({
    academic_year_id: props.classRoom?.academic_year_id || (props.academicYears.length > 0 ? props.academicYears[0].id : ''),
    name: props.classRoom?.name || '',
    grade_level: props.classRoom?.grade_level || 10,
    homeroom_teacher_id: props.classRoom?.homeroom_teacher_id || '',
    status: props.classRoom?.status || 'Active',
});

const submit = () => {
    if (isEditing) {
        form.put(`/class-rooms/${props.classRoom!.id}`);
    } else {
        form.post('/class-rooms');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Kelas - Sekolah Disiplin' : 'Tambah Kelas - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/class-rooms" class="text-gray-500 hover:text-gray-900 transition-colors">Data Kelas</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Data' : 'Tambah Data' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Informasi Kelas' : 'Tambah Kelas Baru' }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Lengkapi pengaturan tingkat, nama kelas, dan wali kelas di bawah ini.</p>
            </div>

            <div class="bg-white rounded-[24px] p-7 sm:p-8 shadow-[0_2px_20px_#00000006] border border-gray-100">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Grade Level -->
                        <div>
                            <label for="grade_level" class="block text-xs font-semibold text-gray-700 mb-1.5">Tingkat Kelas <span class="text-red-600">*</span></label>
                            <select
                                id="grade_level"
                                v-model="form.grade_level"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="7">Tingkat 7 (SMP/MTs)</option>
                                <option value="8">Tingkat 8 (SMP/MTs)</option>
                                <option value="9">Tingkat 9 (SMP/MTs)</option>
                                <option value="10">Tingkat 10 (SMA/SMK)</option>
                                <option value="11">Tingkat 11 (SMA/SMK)</option>
                                <option value="12">Tingkat 12 (SMA/SMK)</option>
                            </select>
                            <p v-if="form.errors.grade_level" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.grade_level }}</p>
                        </div>

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Kelas <span class="text-red-600">*</span></label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: 7A, X MIPA 1"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Academic Year -->
                        <div>
                            <label for="academic_year_id" class="block text-xs font-semibold text-gray-700 mb-1.5">Tahun Ajaran <span class="text-red-600">*</span></label>
                            <select
                                id="academic_year_id"
                                v-model="form.academic_year_id"
                                required
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="" disabled>Pilih Tahun Ajaran</option>
                                <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">
                                    {{ ay.name }} {{ ay.status === 'Active' ? '(Aktif)' : '' }}
                                </option>
                            </select>
                            <p v-if="form.errors.academic_year_id" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.academic_year_id }}</p>
                        </div>

                        <!-- Homeroom Teacher -->
                        <div>
                            <label for="homeroom_teacher_id" class="block text-xs font-semibold text-gray-700 mb-1.5">Wali Kelas (Opsional)</label>
                            <select
                                id="homeroom_teacher_id"
                                v-model="form.homeroom_teacher_id"
                                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="">Tanpa Wali Kelas</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <p v-if="form.errors.homeroom_teacher_id" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.homeroom_teacher_id }}</p>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="w-full md:w-1/2">
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
                        <p v-if="form.errors.status" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.status }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link 
                            href="/class-rooms"
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
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Data Kelas' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
