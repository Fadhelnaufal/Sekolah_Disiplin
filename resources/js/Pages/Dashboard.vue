<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const page = usePage();
const user = page.props.auth.user;

defineProps<{
    metrics: {
        total_students: number;
        current_violations: number;
        attention_needed: number;
        counseling_pending: number;
    };
    priority_students: Array<{
        id: number;
        name: string;
        nis: string;
        class_name: string;
        net_points: number;
        status: string;
    }>;
    recent_activities: Array<{
        id: number;
        date: string;
        student_name: string;
        class_name: string;
        type: string;
        category: string;
        points: number;
        recorded_by: string;
    }>;
    current_semester: string;
}>();

const getStatusBadgeClass = (status: string) => {
    switch(status) {
        case 'NORMAL': return 'bg-green-50 text-green-600';
        case 'ATTENTION': return 'bg-yellow-50 text-yellow-600';
        case 'RAPID_INCREASE': return 'bg-orange-50 text-orange-600';
        case 'HIGH_THRESHOLD': return 'bg-red-50 text-red-600';
        default: return 'bg-gray-50 text-gray-600';
    }
};

const getStatusLabel = (status: string) => {
    switch(status) {
        case 'NORMAL': return 'Aman';
        case 'ATTENTION': return 'Perhatian';
        case 'RAPID_INCREASE': return 'Waspada';
        case 'HIGH_THRESHOLD': return 'Kritis';
        default: return 'Unknown';
    }
};

const getTransactionBadgeClass = (type: string) => {
    return type === 'VIOLATION' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600';
};
</script>

<template>
    <Head title="Dashboard - Disiplin Pro" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-8">
            
            <!-- Welcome Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Welcome back, {{ user.name.split(' ')[0] }}!</h1>
                    <p class="text-gray-500 text-[15px] mt-1">Sistem informasi kedisiplinan (Semester: {{ current_semester }})</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/students/create" class="px-5 py-2.5 rounded-full border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-colors shadow-sm">
                        Siswa Baru
                    </Link>
                    <Link href="/discipline-events/create" class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold transition-colors shadow-glow">
                        Catat Pelanggaran
                    </Link>
                </div>
            </div>

            <!-- Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Metric 1 -->
                <div class="bg-white rounded-card p-6 shadow-card relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="pi pi-users text-sm"></i>
                            <span class="font-medium text-[13px]">Total Siswa Aktif</span>
                        </div>
                        <i class="pi pi-ellipsis-v text-gray-400 text-xs"></i>
                    </div>
                    <div class="flex items-end justify-between">
                        <div class="text-[34px] font-bold text-gray-900 leading-none">{{ metrics.total_students }}</div>
                        <div class="bg-green-50 text-green-600 rounded-full px-2.5 py-0.5 text-[11px] font-bold flex items-center gap-1">
                            <i class="pi pi-arrow-up-right text-[9px]"></i> Aktif
                        </div>
                    </div>
                </div>
                
                <!-- Metric 2 -->
                <div class="bg-white rounded-card p-6 shadow-card relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="pi pi-file text-sm"></i>
                            <span class="font-medium text-[13px]">Pelanggaran (Bulan Ini)</span>
                        </div>
                        <i class="pi pi-ellipsis-v text-gray-400 text-xs"></i>
                    </div>
                    <div class="flex items-end justify-between">
                        <div class="text-[34px] font-bold text-gray-900 leading-none">{{ metrics.current_violations }}</div>
                        <div class="bg-red-50 text-red-600 rounded-full px-2.5 py-0.5 text-[11px] font-bold flex items-center gap-1">
                            <i class="pi pi-arrow-up-right text-[9px]"></i> +2.4%
                        </div>
                    </div>
                </div>

                <!-- Metric 3 -->
                <div class="bg-white rounded-card p-6 shadow-card relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="pi pi-shield text-sm"></i>
                            <span class="font-medium text-[13px]">Perhatian Khusus</span>
                        </div>
                        <i class="pi pi-ellipsis-v text-gray-400 text-xs"></i>
                    </div>
                    <div class="flex items-end justify-between">
                        <div class="text-[34px] font-bold text-gray-900 leading-none">{{ metrics.attention_needed }}</div>
                        <div class="bg-orange-50 text-orange-600 rounded-full px-2.5 py-0.5 text-[11px] font-bold flex items-center gap-1">
                            Pantau
                        </div>
                    </div>
                </div>

                <!-- Metric 4 -->
                <div class="bg-white rounded-card p-6 shadow-card relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="pi pi-comments text-sm"></i>
                            <span class="font-medium text-[13px]">Konseling Menunggu</span>
                        </div>
                        <i class="pi pi-ellipsis-v text-gray-400 text-xs"></i>
                    </div>
                    <div class="flex items-end justify-between">
                        <div class="text-[34px] font-bold text-gray-900 leading-none">{{ metrics.counseling_pending }}</div>
                        <div class="bg-gray-100 text-gray-600 rounded-full px-2.5 py-0.5 text-[11px] font-bold flex items-center gap-1">
                            Pending
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                
                <!-- Recent Activity Table -->
                <div class="bg-white rounded-card shadow-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-900 text-lg">Aktivitas Terkini</h3>
                        <Link href="/discipline-events" class="text-[13px] font-bold text-primary hover:underline">View All</Link>
                    </div>
                    
                    <DataTable :value="recent_activities" responsiveLayout="stack" breakpoint="640px"
                        class="p-datatable-sm !border-none"
                        :pt="{
                            table: { class: 'border-none' },
                            thead: { class: 'border-none' },
                            tbody: { class: 'border-none' },
                            headerRow: { class: 'border-none' },
                            row: { class: 'border-none hover:bg-gray-50 transition-colors' }
                        }"
                        emptyMessage="Belum ada aktivitas di periode ini.">
                        
                        <Column field="student_name" header="Siswa" class="border-b border-gray-50 py-3">
                            <template #body="slotProps">
                                <div class="font-bold text-gray-900 text-[13.5px]">{{ slotProps.data.student_name }}</div>
                                <div class="text-[12px] text-gray-500 mt-0.5">{{ slotProps.data.class_name }}</div>
                            </template>
                        </Column>
                        
                        <Column field="category" header="Tindakan" class="border-b border-gray-50 py-3">
                            <template #body="slotProps">
                                <div class="font-medium text-gray-700 text-[13px] line-clamp-1" :title="slotProps.data.category">{{ slotProps.data.category }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">{{ new Date(slotProps.data.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</div>
                            </template>
                        </Column>
                        
                        <Column field="points" header="Poin" class="text-right border-b border-gray-50 py-3 w-20">
                           
                            <template #body="slotProps">
                                <div class="inline-flex px-2 py-1 rounded-md text-[11px] font-bold" :class="getTransactionBadgeClass(slotProps.data.type)">
                                    {{ slotProps.data.points > 0 && slotProps.data.type === 'VIOLATION' ? '+' : '' }}{{ slotProps.data.points }}
                                </div>
                            </template>
                        </Column>
                        
                        <Column class="text-right border-b border-gray-50 py-3 w-10">
                            <template #body>
                                <button class="text-gray-400 hover:text-gray-900 focus:outline-none"><i class="pi pi-ellipsis-v text-xs"></i></button>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Priority Follow-up -->
                <div class="bg-white rounded-card shadow-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex flex-col">
                            <h3 class="font-bold text-gray-900 text-lg">Perlu Perhatian Khusus</h3>
                            <span class="text-[12px] text-gray-500">Siswa dengan akumulasi poin tinggi</span>
                        </div>
                        <Link href="/students" class="text-[13px] font-bold text-primary hover:underline">View All</Link>
                    </div>
                    
                    <div class="space-y-4">
                        <div v-if="priority_students.length === 0" class="text-sm text-gray-500 py-4 text-center">
                            Semua siswa dalam batas aman.
                        </div>
                        
                        <div v-for="student in priority_students" :key="student.id" class="flex items-center justify-between p-4 rounded-2xl border border-gray-100 hover:border-gray-200 transition-colors bg-[#FAFAFA]">
                            <div class="flex flex-col">
                                <Link :href="`/students/${student.id}`" class="font-bold text-gray-900 text-[14px] hover:text-primary transition-colors">{{ student.name }}</Link>
                                <span class="text-[12px] text-gray-500">{{ student.class_name }} &bull; {{ student.nis }}</span>
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5 max-w-[150px]">
                                    <div class="bg-red-500 h-1.5 rounded-full" :style="`width: ${Math.min((student.net_points / 100) * 100, 100)}%`"></div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="text-[20px] font-bold text-gray-900">{{ student.net_points }}<span class="text-[12px] text-gray-400 font-medium ml-1">pts</span></span>
                                <div class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider" :class="getStatusBadgeClass(student.status)">
                                    {{ getStatusLabel(student.status) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>

