<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    semesters: {
        data: Array<{
            id: number;
            academic_year: {
                id: number;
                name: string;
            } | null;
            name: string;
            start_date: string;
            end_date: string;
            status: string;
        }>;
        links: any[];
    };
    academicYears: Array<{
        id: number;
        name: string;
    }>;
    filters: {
        search?: string;
        academic_year_id?: string | number;
    };
}>();

const confirm = useConfirm();
const search = ref(props.filters.search || '');
const academicYearId = ref(props.filters.academic_year_id || '');

function debounce(fn: Function, delay: number) {
  let timeoutId: ReturnType<typeof setTimeout>;
  return (...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

watch([search, academicYearId], debounce(([searchValue, yearValue]: [string, string]) => {
    router.get('/semesters', { 
        search: searchValue,
        academic_year_id: yearValue
    }, { preserveState: true, replace: true });
}, 300));

const deleteSemester = (id: number) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menghapus data semester ini?',
        header: 'Konfirmasi Hapus Semester',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: { severity: 'danger' },
        accept: () => {
            router.delete(`/semesters/${id}`);
        }
    });
};

const closeSemester = (id: number) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menutup semester ini? Poin seluruh siswa akan disimpan sebagai snapshot dan transaksi semester ini akan terkunci.',
        header: 'Konfirmasi Tutup Semester',
        icon: 'pi pi-lock',
        acceptProps: { severity: 'warn' },
        accept: () => {
            router.post(`/semesters/${id}/close`);
        }
    });
};

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }).format(d);
};
</script>

<template>
    <Head title="Manajemen Semester - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/settings" class="text-gray-500 hover:text-gray-900 transition-colors">Pengaturan</Link>
                <span class="text-gray-300">/</span>
                <Link href="/academic-years" class="text-gray-500 hover:text-gray-900 transition-colors">Tahun Ajaran</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Semester</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Area -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Manajemen Semester</h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">Kelola periode semester (Ganjil/Genap) untuk setiap tahun ajaran aktif.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link 
                        href="/academic-years" 
                        class="px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-all shadow-xs"
                    >
                        Ke Tahun Ajaran
                    </Link>
                    <Link 
                        href="/semesters/create" 
                        class="px-5 py-2 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Tambah Semester</span>
                    </Link>
                </div>
            </div>

            <!-- Card Container -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-5">
                <!-- Toolbar (Search & Filter) -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-72">
                            <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Cari semester (misal: Ganjil)..." 
                                class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            >
                        </div>
                        <div class="w-full sm:w-56">
                            <select
                                v-model="academicYearId"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                            >
                                <option value="">Semua Tahun Ajaran</option>
                                <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3 text-left">Tahun Ajaran</th>
                                <th class="pb-3 text-left">Nama Semester</th>
                                <th class="pb-3 text-left">Periode</th>
                                <th class="pb-3 w-28 text-center">Status</th>
                                <th class="pb-3 w-36 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="semester in semesters.data" :key="semester.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4 text-gray-900 font-bold">{{ semester.academic_year ? semester.academic_year.name : '-' }}</td>
                                <td class="py-4 text-gray-900 font-semibold">{{ semester.name }}</td>
                                <td class="py-4 text-xs text-gray-600">
                                    {{ formatDate(semester.start_date) }} s.d. {{ formatDate(semester.end_date) }}
                                </td>
                                <td class="py-4 text-center">
                                    <span 
                                        class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider"
                                        :class="(semester.status.toLowerCase() === 'active' || semester.status.toLowerCase() === 'aktif') ? 'bg-green-50 text-green-600' : (semester.status === 'Closed' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600')"
                                    >
                                        {{ semester.status === 'Active' ? 'Aktif' : (semester.status === 'Closed' ? 'Selesai' : 'Tidak Aktif') }}
                                    </span>
                                </td>
                                <td class="py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="semester.status === 'Active' || semester.status === 'Aktif'"
                                            @click="closeSemester(semester.id)"
                                            class="w-7 h-7 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-700 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400"
                                            title="Tutup Semester & Kunci Data"
                                        >
                                            <i class="pi pi-lock text-[11px]"></i>
                                        </button>
                                        <Link
                                            :href="`/semesters/${semester.id}/edit`"
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-indigo-50 hover:text-[#6366F1] text-gray-600 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                            title="Edit Semester"
                                        >
                                            <i class="pi pi-pencil text-[11px]"></i>
                                        </Link>
                                        <button
                                            @click="deleteSemester(semester.id)"
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-600 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-red-500/40"
                                            title="Hapus Semester"
                                        >
                                            <i class="pi pi-trash text-[11px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="semesters.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-500 text-xs">
                                    Tidak ada data semester ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="semesters.links.length > 3" class="pt-4 border-t border-gray-100 flex items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in semesters.links" 
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
