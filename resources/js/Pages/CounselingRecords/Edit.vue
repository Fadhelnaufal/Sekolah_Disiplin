<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    record: Object,
    categories: Array,
    statuses: Array,
});

const form = useForm({
    date_scheduled: props.record.date_scheduled,
    date_completed: props.record.date_completed || '',
    category: props.record.category,
    reason: props.record.reason,
    summary: props.record.summary || '',
    follow_up_action: props.record.follow_up_action || '',
    status: props.record.status,
    confidentiality_level: props.record.confidentiality_level,
});

const submit = () => {
    form.put(`/counseling-records/${props.record.id}`);
};
</script>

<template>
    <Head title="Edit Catatan Konseling" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Catatan Konseling ({{ record.student?.name }})</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="date_scheduled" class="block text-sm font-medium text-gray-700">Tanggal Jadwal</label>
                                <input id="date_scheduled" type="date" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.date_scheduled" />
                                <div v-if="form.errors.date_scheduled" class="text-sm text-red-600 mt-2">{{ form.errors.date_scheduled }}</div>
                            </div>
                            <div>
                                <label for="date_completed" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                                <input id="date_completed" type="date" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.date_completed" />
                                <div v-if="form.errors.date_completed" class="text-sm text-red-600 mt-2">{{ form.errors.date_completed }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700">Kategori</label>
                                <select id="category" v-model="form.category" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                                <div v-if="form.errors.category" class="text-sm text-red-600 mt-2">{{ form.errors.category }}</div>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select id="status" v-model="form.status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option v-for="stat in statuses" :key="stat" :value="stat">{{ stat }}</option>
                                </select>
                                <div v-if="form.errors.status" class="text-sm text-red-600 mt-2">{{ form.errors.status }}</div>
                            </div>
                        </div>

                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">Alasan (Dienkripsi)</label>
                            <textarea id="reason" v-model="form.reason" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            <div v-if="form.errors.reason" class="text-sm text-red-600 mt-2">{{ form.errors.reason }}</div>
                        </div>

                        <div>
                            <label for="summary" class="block text-sm font-medium text-gray-700">Ringkasan Sesi (Dienkripsi)</label>
                            <textarea id="summary" v-model="form.summary" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            <div v-if="form.errors.summary" class="text-sm text-red-600 mt-2">{{ form.errors.summary }}</div>
                        </div>

                        <div>
                            <label for="follow_up_action" class="block text-sm font-medium text-gray-700">Tindak Lanjut (Dienkripsi)</label>
                            <textarea id="follow_up_action" v-model="form.follow_up_action" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            <div v-if="form.errors.follow_up_action" class="text-sm text-red-600 mt-2">{{ form.errors.follow_up_action }}</div>
                        </div>

                        <div>
                            <label for="confidentiality_level" class="block text-sm font-medium text-gray-700">Tingkat Kerahasiaan</label>
                            <select id="confidentiality_level" v-model="form.confidentiality_level" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="normal">Normal</option>
                                <option value="strict">Strict (Hanya Anda)</option>
                            </select>
                            <div v-if="form.errors.confidentiality_level" class="text-sm text-red-600 mt-2">{{ form.errors.confidentiality_level }}</div>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :class="{ 'opacity-25': form.processing }">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
