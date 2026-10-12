<script setup lang="ts">
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps<{
    events: {
        data: Array<{
            id: number;
            event_date: string;
            event_type: string;
            points_effect: number;
            student: { id: number; name: string; nis: string; class_room?: { name: string } } | null;
            violation: { name: string; code: string } | null;
            achievement: { name: string; code: string } | null;
            recorded_by: { name: string } | null;
            semester: { name: string } | null;
        }>;
        links: any[];
    };
    filters: {
        search?: string;
        event_type?: string;
    };
}>();

const search = ref(props.filters.search || '');
const eventType = ref(props.filters.event_type || '');

function debounce(fn: Function, delay: number) {
  let timeoutId: ReturnType<typeof setTimeout>;
  return (...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

watch([search, eventType], debounce(([searchValue, typeValue]: [string, string]) => {
    router.get('/discipline-events', { search: searchValue, event_type: typeValue }, { preserveState: true, replace: true });
}, 300));

const deleteEvent = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Tindakan',
        text: 'Apakah Anda yakin ingin menghapus catatan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#B42318',
        cancelButtonColor: '#697586',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/discipline-events/${id}`);
        }
    });
};

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }).format(d);
};
</script>

<template>
    <Head title="Pencatatan Kedisiplinan - Disiplin Pro" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-8">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Catatan Kedisiplinan</h1>
                    <p class="text-gray-500 text-[15px] mt-1">Lihat rekam jejak pelanggaran dan prestasi siswa secara real-time.</p>
                </div>
                <div class="flex gap-3">
                    <Link href="/discipline-events/create" class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold transition-colors shadow-glow flex items-center gap-2">
                        <i class="pi pi-plus text-xs"></i> Input Kejadian Baru
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
                
                <!-- Toolbar -->
                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto mb-6">
                    <div class="relative w-full sm:w-96">
                        <i class="pi pi-search absolute top-1/2 -translate-y-1/2 left-4 text-gray-400" />
                        <input type="text" v-model="search" placeholder="Cari berdasarkan nama atau NIS siswa..." class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-[14px] focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:border-transparent transition-all" />
                    </div>
                    <div class="w-full sm:w-64">
                        <select v-model="eventType" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-[14px] text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:border-transparent transition-all appearance-none cursor-pointer">
                            <option value="">Semua Jenis Kejadian</option>
                            <option value="violation">Pelanggaran Saja</option>
                            <option value="achievement">Prestasi Saja</option>
                        </select>
                    </div>
                </div>

                <DataTable :value="events.data" responsiveLayout="stack" breakpoint="640px"
                    class="p-datatable-sm !border-none"
                    :pt="{
                        table: { class: 'border-none' },
                        thead: { class: 'border-none' },
                        tbody: { class: 'border-none' },
                        headerRow: { class: 'border-none' },
                        row: { class: 'border-none hover:bg-gray-50 transition-colors' }
                    }"
                    emptyMessage="Tidak ada catatan kedisiplinan ditemukan.">
                    
                    <Column field="event_date" header="Tanggal" class="border-b border-gray-50 py-4 w-32">
                        <template #header>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Tanggal</span>
                        </template>
                        <template #body="slotProps">
                            <span class="text-gray-900 font-medium text-[13px]">{{ formatDate(slotProps.data.event_date) }}</span>
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
                                {{ slotProps.data.student.nis }} &bull; {{ slotProps.data.student.class_room?.name }}
                            </div>
                        </template>
                    </Column>
                    
                    <Column field="event_type" header="Jenis" class="border-b border-gray-50 py-4 w-32">
                        <template #header>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Jenis</span>
                        </template>
                        <template #body="slotProps">
                            <span class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider" :class="slotProps.data.event_type === 'violation' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'">
                                {{ slotProps.data.event_type === 'violation' ? 'Pelanggaran' : 'Prestasi' }}
                            </span>
                        </template>
                    </Column>
                    
                    <Column field="description" header="Deskripsi Kejadian" class="border-b border-gray-50 py-4 w-64">
                        <template #header>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Deskripsi Kejadian</span>
                        </template>
                        <template #body="slotProps">
                            <span class="text-gray-700 text-[13px]">{{ slotProps.data.event_type === 'violation' ? slotProps.data.violation?.name : slotProps.data.achievement?.name }}</span>
                        </template>
                    </Column>
                    
                    <Column field="points_effect" header="Poin" class="text-center border-b border-gray-50 py-4 w-24">
                        <template #header>
                            <div class="text-xs font-semibold text-gray-400 uppercase text-center w-full">Poin</div>
                        </template>
                        <template #body="slotProps">
                            <span class="text-[14px] font-bold" :class="slotProps.data.event_type === 'violation' ? 'text-red-600' : 'text-green-600'">
                                {{ slotProps.data.event_type === 'violation' ? '+' : '-' }}{{ slotProps.data.points_effect }}
                            </span>
                        </template>
                    </Column>
                    
                    <Column class="text-right border-b border-gray-50 py-4 w-32">
                        <template #body="slotProps">
                            <div class="flex justify-end gap-2">
                                <button @click="deleteEvent(slotProps.data.id)" class="w-8 h-8 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-red-50 hover:text-red-600 transition-colors focus:outline-none" title="Hapus">
                                    <i class="pi pi-trash text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Custom Inertia Pagination -->
                <div v-if="events.links.length > 3" class="mt-6 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in events.links" 
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

