<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    academicYears: {
        data: Array<{
            id: number;
            name: string;
            start_date: string;
            end_date: string;
            status: string;
        }>;
        links: any[];
    };
    filters: {
        search?: string;
    };
}>();

const confirm = useConfirm();
const search = ref(props.filters.search || '');

function debounce(fn: Function, delay: number) {
  let timeoutId: ReturnType<typeof setTimeout>;
  return (...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

watch(search, debounce((value: string) => {
    router.get('/academic-years', { search: value }, { preserveState: true, replace: true });
}, 300));

const deleteAcademicYear = (id: number) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menghapus data tahun ajaran ini?',
        header: 'Konfirmasi Hapus Tahun Ajaran',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: { severity: 'danger' },
        accept: () => {
            router.delete(`/academic-years/${id}`);
        }
    });
};

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }).format(d);
};
</script>

<template>
    <Head title="Tahun Ajaran - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/settings" class="text-gray-500 hover:text-gray-900 transition-colors">Pengaturan</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Tahun Ajaran</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Area -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Tahun Ajaran</h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">Kelola daftar tahun ajaran dan konfigurasi periode akademik aktif.</p>
                </div>
                <Link 
                    href="/academic-years/create" 
                    class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                >
                    <i class="pi pi-plus text-xs"></i>
                    <span>Tambah Tahun Ajaran</span>
                </Link>
            </div>

            <!-- Card Container -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-5">
                <!-- Toolbar (Search) -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="relative w-full sm:w-80">
                        <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari tahun ajaran..." 
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3 text-left">Tahun Ajaran</th>
                                <th class="pb-3 text-left">Tanggal Mulai</th>
                                <th class="pb-3 text-left">Tanggal Selesai</th>
                                <th class="pb-3 w-28 text-center">Status</th>
                                <th class="pb-3 w-32 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="ay in academicYears.data" :key="ay.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4 text-gray-900 font-bold">{{ ay.name }}</td>
                                <td class="py-4 text-xs text-gray-600">{{ formatDate(ay.start_date) }}</td>
                                <td class="py-4 text-xs text-gray-600">{{ formatDate(ay.end_date) }}</td>
                                <td class="py-4 text-center">
                                    <span 
                                        class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider"
                                        :class="(ay.status.toLowerCase() === 'active' || ay.status.toLowerCase() === 'aktif') ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ ay.status === 'Active' ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </td>
                                <td class="py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="`/academic-years/${ay.id}/edit`"
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-indigo-50 hover:text-[#6366F1] text-gray-600 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                            title="Edit Tahun Ajaran"
                                        >
                                            <i class="pi pi-pencil text-[11px]"></i>
                                        </Link>
                                        <button
                                            @click="deleteAcademicYear(ay.id)"
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-600 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-red-500/40"
                                            title="Hapus Tahun Ajaran"
                                        >
                                            <i class="pi pi-trash text-[11px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="academicYears.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-500 text-xs">
                                    Tidak ada data tahun ajaran ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="academicYears.links.length > 3" class="pt-4 border-t border-gray-100 flex items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in academicYears.links" 
                        :key="index"
                        :href="link.url || '#'"
                        class="min-w-[32px] h-8 flex items-center justify-center px-2 text-[13px] font-medium rounded-lg transition-colors"
                        :class="[
                            link.active ? 'bg-[#6366F1] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100',
                            !link.url && 'opacity-40 cursor-not-allowed hover:bg-transparent'
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
