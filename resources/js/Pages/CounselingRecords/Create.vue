<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    students: Array,
    categories: Array,
});

const form = useForm({
    student_id: '',
    date_scheduled: '',
    category: '',
    reason: '',
    confidentiality_level: 'normal',
});

const submit = () => {
    form.post('/counseling-records');
};
</script>

<template>
    <Head title="Jadwalkan Konseling" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwalkan Konseling</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700">Siswa</label>
                            <select id="student_id" v-model="form.student_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option disabled value="">Pilih Siswa</option>
                                <option v-for="student in students" :key="student.id" :value="student.id">
                                    {{ student.name }} ({{ student.class_room?.name }})
                                </option>
                            </select>
                            <div v-if="form.errors.student_id" class="text-sm text-red-600 mt-2">{{ form.errors.student_id }}</div>
                        </div>

                        <div>
                            <label for="date_scheduled" class="block text-sm font-medium text-gray-700">Tanggal Jadwal</label>
                            <input id="date_scheduled" type="date" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.date_scheduled" />
                            <div v-if="form.errors.date_scheduled" class="text-sm text-red-600 mt-2">{{ form.errors.date_scheduled }}</div>
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Kategori</label>
                            <select id="category" v-model="form.category" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option disabled value="">Pilih Kategori</option>
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                            <div v-if="form.errors.category" class="text-sm text-red-600 mt-2">{{ form.errors.category }}</div>
                        </div>

                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">Alasan Pemanggilan (Akan dienkripsi)</label>
                            <textarea id="reason" v-model="form.reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            <div v-if="form.errors.reason" class="text-sm text-red-600 mt-2">{{ form.errors.reason }}</div>
                        </div>

                        <div>
                            <label for="confidentiality_level" class="block text-sm font-medium text-gray-700">Tingkat Kerahasiaan</label>
                            <select id="confidentiality_level" v-model="form.confidentiality_level" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="normal">Normal (Dapat dilihat Principal & Super Admin)</option>
                                <option value="strict">Strict (Hanya Anda yang dapat melihat detail)</option>
                            </select>
                            <div v-if="form.errors.confidentiality_level" class="text-sm text-red-600 mt-2">{{ form.errors.confidentiality_level }}</div>
                        </div>

                        <div class="flex items-center justify-end">
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="px-4 py-2 text-sm font-medium text-white bg-[#1D3557] hover:bg-[#0D1B2A] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D3557] disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Simpan Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
