<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    students: Array,
    selectedStudent: Object
});

const form = useForm({
    student_id: props.selectedStudent ? props.selectedStudent.id : '',
    type: 'notification',
    subject: 'Surat Pemberitahuan'
});

const submit = () => {
    form.post('/letters', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Buat Surat" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Surat Baru</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="student_id" class="block font-medium text-sm text-gray-700">Pilih Siswa</label>
                                <select 
                                    id="student_id" 
                                    v-model="form.student_id" 
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="" disabled>-- Pilih Siswa --</option>
                                    <option v-for="student in students" :key="student.id" :value="student.id">
                                        {{ student.name }} ({{ student.nis }}) - {{ student.class_room?.name || 'Tanpa Kelas' }}
                                    </option>
                                </select>
                                <div v-if="form.errors.student_id" class="mt-2 text-sm text-red-600">{{ form.errors.student_id }}</div>
                            </div>

                            <div>
                                <label for="type" class="block font-medium text-sm text-gray-700">Tipe Surat</label>
                                <select 
                                    id="type" 
                                    v-model="form.type" 
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="notification">Pemberitahuan (Notification)</option>
                                    <option value="call_1">Panggilan I (BK)</option>
                                    <option value="call_2">Panggilan II (Waka Kesiswaan)</option>
                                    <option value="call_3">Panggilan III / Terakhir (Kepala Sekolah)</option>
                                    <option value="parent_call">Pemanggilan Orang Tua (Lainnya)</option>
                                    <option value="statement">Surat Pernyataan (Statement)</option>
                                    <option value="counseling">Pemanggilan Konseling</option>
                                    <option value="other">Lainnya</option>
                                </select>
                                <div v-if="form.errors.type" class="mt-2 text-sm text-red-600">{{ form.errors.type }}</div>
                            </div>

                            <div>
                                <label for="subject" class="block font-medium text-sm text-gray-700">Perihal (Opsional)</label>
                                <input
                                    id="subject"
                                    v-model="form.subject"
                                    type="text"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                />
                                <div v-if="form.errors.subject" class="mt-2 text-sm text-red-600">{{ form.errors.subject }}</div>
                            </div>

                            <div class="flex items-center gap-4">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-[#1D3557] hover:bg-[#0D1B2A] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D3557] disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Generate PDF
                                </button>

                                <transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Surat berhasil dibuat.</p>
                                </transition>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
