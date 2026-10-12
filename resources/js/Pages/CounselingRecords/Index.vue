<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    records: Object,
    filters: Object,
    categories: Array,
    statuses: Array,
});

const getStatusBadgeClass = (status) => {
    const map = {
        'scheduled': 'bg-blue-50 text-blue-600',
        'in_progress': 'bg-yellow-50 text-yellow-600',
        'resolved': 'bg-green-50 text-green-600',
        'referred': 'bg-purple-50 text-purple-600',
        'cancelled': 'bg-gray-100 text-gray-500',
    };
    return map[status] || 'bg-gray-100 text-gray-500';
};
</script>

<template>
    <Head title="Bimbingan Konseling - Disiplin Pro" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-8">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Jadwal & Catatan Konseling</h1>
                    <p class="text-gray-500 text-[15px] mt-1">Kelola sesi bimbingan konseling siswa dengan pendekatan yang terstruktur.</p>
                </div>
                <div class="flex gap-3">
                    <Link v-if="$page.props.auth.roles.includes('BK')" href="/counseling-records/create" class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold transition-colors shadow-glow flex items-center gap-2">
                        <i class="pi pi-plus text-xs"></i> Jadwal Baru
                    </Link>
                </div>
            </div>

            <div v-if="$page.props.flash?.success" class="bg-green-50 text-green-600 px-5 py-4 rounded-[16px] text-[14px] font-medium border border-green-100 flex items-center gap-3 shadow-sm">
                <i class="pi pi-check-circle"></i> {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 text-red-600 px-5 py-4 rounded-[16px] text-[14px] font-medium border border-red-100 flex items-center gap-3 shadow-sm">
                <i class="pi pi-exclamation-circle"></i> {{ $page.props.flash.error }}
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-card shadow-card p-7 flex flex-col">
                
                <DataTable :value="records.data" responsiveLayout="stack" breakpoint="640px"
                    class="p-datatable-sm !border-none"
                    :pt="{
                        table: { class: 'border-none' },
                        thead: { class: 'border-none' },
                        tbody: { class: 'border-none' },
                        headerRow: { class: 'border-none' },
                        row: { class: 'border-none hover:bg-gray-50 transition-colors' }
                    }"
                    emptyMessage="Tidak ada catatan konseling ditemukan.">
                    
                    <Column field="date_scheduled" header="Tanggal" class="border-b border-gray-50 py-4 w-32">
                        <template #header>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Tanggal</span>
                        </template>
                        <template #body="slotProps">
                            <span class="text-gray-900 font-medium text-[13px]">{{ new Date(slotProps.data.date_scheduled).toLocaleDateString('id-ID') }}</span>
                        </template>
                    </Column>
                    
                    <Column field="student.name" header="Siswa" class="border-b border-gray-50 py-4">
                        <template #header>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Siswa</span>
                        </template>
                        <template #body="slotProps">
                            <Link v-if="slotProps.data.student" :href="`/students/${slotProps.data.student.id}`" class="font-bold text-gray-900 text-[14px] hover:text-primary transition-colors">
                                {{ slotProps.data.student.name }}
                            </Link>
                            <div v-if="slotProps.data.student" class="text-[12px] text-gray-500 mt-0.5">
                                {{ slotProps.data.student.class_room?.name }}
                            </div>
                        </template>
                    </Column>
                    
                    <Column field="category" header="Kategori" class="border-b border-gray-50 py-4 w-48">
                        <template #header>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Kategori</span>
                        </template>
                        <template #body="slotProps">
                            <span class="text-gray-700 text-[13px] font-medium">{{ slotProps.data.category }}</span>
                        </template>
                    </Column>
                    
                    <Column field="status" header="Status" class="border-b border-gray-50 py-4 w-32">
                        <template #header>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Status</span>
                        </template>
                        <template #body="slotProps">
                            <div class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider" :class="getStatusBadgeClass(slotProps.data.status)">
                                {{ slotProps.data.status.replace('_', ' ') }}
                            </div>
                        </template>
                    </Column>
                    
                    <Column class="text-right border-b border-gray-50 py-4 w-32">
                        <template #body="slotProps">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/counseling-records/${slotProps.data.id}`" class="px-4 py-1.5 rounded-full bg-gray-50 text-gray-600 font-semibold text-[12px] flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Detail">
                                    Detail
                                </Link>
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Custom Inertia Pagination -->
                <div v-if="records.links?.length > 3" class="mt-6 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in records.links" 
                        :key="index"
                        :href="link.url || '#'"
                        class="min-w-[32px] h-8 flex items-center justify-center px-2 text-[13px] font-medium rounded-lg transition-colors"
                        :class="[
                            link.active ? 'bg-gray-900 text-white' : 'text-gray-500 hover:bg-gray-100',
                            !link.url && 'opacity-50 cursor-not-allowed hover:bg-transparent'
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

