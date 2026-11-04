<script setup lang="ts">
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps<{
    achievements: {
        data: Array<{
            id: number;
            code: string;
            name: string;
            level: string;
            deduction_points: number;
            is_active: boolean;
        }>;
        links: any[];
    };
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');

function debounce(fn: Function, delay: number) {
  let timeoutId: ReturnType<typeof setTimeout>;
  return (...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

watch(search, debounce((value: string) => {
    router.get('/achievements', { search: value }, { preserveState: true, replace: true });
}, 300));

const deleteAchievement = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Tindakan',
        text: 'Apakah Anda yakin ingin menghapus prestasi ini? Jika sudah digunakan dalam catatan kedisiplinan, sistem akan menolak penghapusan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#B42318',
        cancelButtonColor: '#697586',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/achievements/${id}`);
        }
    });
};
</script>

<template>
    <Head title="Master Prestasi - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-8">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Master Prestasi</h1>
                    <p class="text-gray-500 text-[15px] mt-1">Kelola daftar prestasi, tingkat/level, dan pengurang poin.</p>
                </div>
                <div class="flex gap-3">
                    <Link href="/achievements/create" class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold transition-colors shadow-glow flex items-center gap-2">
                        <i class="pi pi-plus text-xs"></i> Tambah Prestasi
                    </Link>
                </div>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-card shadow-card p-7 flex flex-col">
                
                <!-- Toolbar -->
                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto mb-6">
                    <div class="relative w-full sm:w-96">
                        <i class="pi pi-search absolute top-1/2 -translate-y-1/2 left-4 text-gray-400" />
                        <input type="text" v-model="search" placeholder="Cari berdasarkan kode, nama, atau level..." class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-[14px] focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:border-transparent transition-all" />
                    </div>
                </div>

                <DataTable :value="achievements.data" responsiveLayout="stack" breakpoint="640px"
                    class="p-datatable-sm !border-none"
                    :pt="{
                        table: { class: 'border-none' },
                        thead: { class: 'border-none' },
                        tbody: { class: 'border-none' },
                        headerRow: { class: 'border-none' },
                        row: { class: 'border-none hover:bg-gray-50 transition-colors' }
                    }"
                    emptyMessage="Tidak ada data prestasi ditemukan.">
                    
                    <Column field="code" header="Kode" class="border-b border-gray-50 py-4 w-28">
                       
                        <template #body="slotProps">
                            <span class="text-gray-500 font-medium text-[13px]">{{ slotProps.data.code }}</span>
                        </template>
                    </Column>
                    
                    <Column field="name" header="Nama Prestasi" class="border-b border-gray-50 py-4">
                        
                        <template #body="slotProps">
                            <span class="font-bold text-gray-900 text-[14px]">{{ slotProps.data.name }}</span>
                        </template>
                    </Column>
                    
                    <Column field="level" header="Tingkat" class="border-b border-gray-50 py-4 w-48">
                       
                        <template #body="slotProps">
                            <span class="text-gray-700 text-[13px] font-medium">{{ slotProps.data.level }}</span>
                        </template>
                    </Column>
                    
                    <Column field="deduction_points" header="Pengurang" class="text-right border-b border-gray-50 py-4 w-32">
                      
                        <template #body="slotProps">
                            <div class="inline-flex px-2 py-1 rounded-md text-[12px] font-bold bg-green-50 text-green-600">
                                -{{ slotProps.data.deduction_points }}
                            </div>
                        </template>
                    </Column>
                    
                    <Column field="is_active" header="Status" class="text-center border-b border-gray-50 py-4 w-32">
                      
                        <template #body="slotProps">
                            <div class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider" :class="slotProps.data.is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500'">
                                {{ slotProps.data.is_active ? 'Aktif' : 'Nonaktif' }}
                            </div>
                        </template>
                    </Column>
                    
                    <Column class="text-right border-b border-gray-50 py-4 w-32">
                        <template #body="slotProps">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/achievements/${slotProps.data.id}/edit`" class="w-8 h-8 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Edit">
                                    <i class="pi pi-pencil text-xs"></i>
                                </Link>
                                <button @click="deleteAchievement(slotProps.data.id)" class="w-8 h-8 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-red-50 hover:text-red-600 transition-colors focus:outline-none" title="Hapus">
                                    <i class="pi pi-trash text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Custom Inertia Pagination -->
                <div v-if="achievements.links.length > 3" class="mt-6 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in achievements.links" 
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

