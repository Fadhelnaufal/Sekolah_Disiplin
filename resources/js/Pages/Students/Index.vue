<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps<{
    students: {
        data: Array<{
            id: number;
            nis: string;
            name: string;
            status: string;
            class_room: {
                id: number;
                name: string;
            } | null;
            net_points: number;
            risk_status: string;
        }>;
        links: any[];
    };
    classRooms: Array<{
        id: number;
        name: string;
    }>;
    filters: {
        search?: string;
        class_room_id?: string | number;
    };
    hasActiveSemester: boolean;
}>();

const confirm = useConfirm();
const search = ref(props.filters.search || '');
const classRoomId = ref(props.filters.class_room_id ? Number(props.filters.class_room_id) : '');

function debounce(fn: Function, delay: number) {
  let timeoutId: ReturnType<typeof setTimeout>;
  return (...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

watch([search, classRoomId], debounce(([searchValue, classValue]: [string, any]) => {
    router.get('/students', { 
        search: searchValue, 
        class_room_id: classValue 
    }, { preserveState: true, replace: true });
}, 300));

const deleteStudent = (id: number) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menghapus data siswa ini?',
        header: 'Konfirmasi Tindakan',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: { severity: 'danger' },
        accept: () => {
            router.delete(`/students/${id}`);
        }
    });
};

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
</script>

<template>
    <Head title="Data Siswa - Disiplin Pro" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-8">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Data Siswa</h1>
                    <p class="text-gray-500 text-[15px] mt-1">Kelola daftar siswa dan pantau akumulasi poin kedisiplinan secara real-time.</p>
                </div>
                <div class="flex gap-3">
                    <Link href="/students/create" class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold transition-colors shadow-glow flex items-center gap-2">
                        <i class="pi pi-plus text-xs"></i> Tambah Siswa
                    </Link>
                </div>
            </div>

            <div v-if="!hasActiveSemester" class="bg-yellow-50 text-yellow-600 px-5 py-4 rounded-[16px] text-[14px] font-medium border border-yellow-100 flex items-center gap-3 shadow-sm">
                <i class="pi pi-exclamation-triangle"></i>
                Tidak ada semester aktif. Kalkulasi poin kedisiplinan dinonaktifkan.
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-card shadow-card p-7 flex flex-col">
                
                <!-- Toolbar -->
                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto mb-6">
                    <div class="relative w-full sm:w-80">
                        <i class="pi pi-search absolute top-1/2 -translate-y-1/2 left-4 text-gray-400" />
                        <input type="text" v-model="search" placeholder="Cari NIS atau Nama..." class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-[14px] focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:border-transparent transition-all" />
                    </div>
                    <div class="w-full sm:w-64">
                        <select v-model="classRoomId" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-[14px] text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:border-transparent transition-all appearance-none cursor-pointer">
                            <option value="">Semua Kelas</option>
                            <option v-for="cls in classRooms" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                </div>

                <DataTable :value="students.data" responsiveLayout="stack" breakpoint="640px"
                    class="p-datatable-sm !border-none"
                    :pt="{
                        table: { class: 'border-none' },
                        thead: { class: 'border-none' },
                        tbody: { class: 'border-none' },
                        headerRow: { class: 'border-none' },
                        row: { class: 'border-none hover:bg-gray-50 transition-colors' }
                    }"
                    emptyMessage="Tidak ada data siswa ditemukan.">
                    
                    <Column field="nis" header="NIS" headerStyle="text-align: left;" bodyStyle="text-align: left;" class="border-b border-gray-50 py-4 w-32">
                      
                        <template #body="slotProps">
                            <span class="text-gray-500 font-medium text-[13px]">{{ slotProps.data.nis }}</span>
                        </template>
                    </Column>
                    
                    <Column field="name" header="Siswa" headerStyle="text-align: left;" bodyStyle="text-align: left;" class="border-b border-gray-50 py-4">
                        <template #body="slotProps">
                            <Link :href="`/students/${slotProps.data.id}`" class="font-bold text-gray-900 text-[14px] hover:text-primary transition-colors">
                                {{ slotProps.data.name }}
                            </Link>
                            <div class="text-[12px] mt-0.5" :class="(slotProps.data.status.toLowerCase() === 'active' || slotProps.data.status.toLowerCase() === 'aktif') ? 'text-green-500' : 'text-red-400'">
                                &bull; {{ slotProps.data.status }}
                            </div>
                        </template>
                    </Column>
                    
                    <Column field="class_room.name" header="Kelas" headerStyle="text-align: left;" bodyStyle="text-align: left;" class="border-b border-gray-50 py-4 w-40">
                        <template #body="slotProps">
                            <span class="text-gray-700 text-[13px] font-medium">{{ slotProps.data.class_room ? slotProps.data.class_room.name : '-' }}</span>
                        </template>
                    </Column>
                    
                    <Column field="net_points" header="Poin" headerStyle="text-align: center;" bodyStyle="text-align: center;" class="border-b border-gray-50 py-4 w-32">
                        <template #body="slotProps">
                            <span class="text-[15px] font-bold" :class="slotProps.data.net_points > 0 ? 'text-gray-900' : 'text-gray-400'">
                                {{ slotProps.data.net_points }}
                            </span>
                        </template>
                    </Column>
                    
                    <Column field="risk_status" header="Status" headerStyle="text-align: center;" bodyStyle="text-align: center;" class="border-b border-gray-50 py-4 w-40">
                        <template #body="slotProps">
                            <div class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider" :class="getStatusBadgeClass(slotProps.data.risk_status)">
                                {{ getStatusLabel(slotProps.data.risk_status) }}
                            </div>
                        </template>
                    </Column>
                    
                    <Column header="Aksi" headerStyle="text-align: center;" bodyStyle="text-align: center;" class="border-b border-gray-50 py-4 w-32">
                        <template #body="slotProps">
                            <div class="flex justify-center gap-2">
                                <Link :href="`/discipline-events/create?student_id=${slotProps.data.id}`" class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-100 transition-colors" title="Catat Pelanggaran">
                                    <i class="pi pi-plus text-xs"></i>
                                </Link>
                                <Link :href="`/students/${slotProps.data.id}/edit`" class="w-8 h-8 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-gray-100 transition-colors" title="Edit">
                                    <i class="pi pi-pencil text-xs"></i>
                                </Link>
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Custom Inertia Pagination -->
                <div v-if="students.links.length > 3" class="mt-6 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in students.links" 
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

