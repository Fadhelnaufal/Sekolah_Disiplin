<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    record: Object,
});
</script>

<template>
    <Head title="Detail Konseling" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Konseling</h2>
                <Link v-if="$page.props.auth.roles.includes('BK') && $page.props.auth.user.id === record.counselor_id" 
                      :href="`/counseling-records/${record.id}/edit`" 
                      class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    Catat / Edit Hasil
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Dasar</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Siswa</span>
                            <span class="block text-gray-900">{{ record.student?.name }} ({{ record.student?.class_room?.name }})</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Status</span>
                            <span class="block text-gray-900 uppercase font-bold">{{ record.status }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Jadwal</span>
                            <span class="block text-gray-900">{{ new Date(record.date_scheduled).toLocaleDateString('id-ID') }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Kategori</span>
                            <span class="block text-gray-900">{{ record.category }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-sm font-medium text-gray-500">Alasan</span>
                            <p class="text-gray-900 mt-1 whitespace-pre-wrap">{{ record.reason }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" v-if="record.summary || record.follow_up_action">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Catatan Sesi (Terenkripsi)</h3>
                    <div class="space-y-4">
                        <div v-if="record.summary">
                            <span class="block text-sm font-medium text-gray-500">Ringkasan</span>
                            <p class="text-gray-900 mt-1 whitespace-pre-wrap">{{ record.summary }}</p>
                        </div>
                        <div v-if="record.follow_up_action">
                            <span class="block text-sm font-medium text-gray-500">Tindak Lanjut</span>
                            <p class="text-gray-900 mt-1 whitespace-pre-wrap">{{ record.follow_up_action }}</p>
                        </div>
                        <div v-if="record.date_completed">
                            <span class="block text-sm font-medium text-gray-500">Tanggal Selesai</span>
                            <p class="text-gray-900 mt-1">{{ new Date(record.date_completed).toLocaleDateString('id-ID') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
