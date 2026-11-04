<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    teachers: {
        data: Array<{
            id: number;
            employee_number: string | null;
            name: string;
            phone: string | null;
            email: string | null;
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
    router.get('/teachers', { search: value }, { preserveState: true, replace: true });
}, 300));

const deleteTeacher = (id: number) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menghapus data guru ini?',
        header: 'Konfirmasi Hapus Guru',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: { severity: 'danger' },
        accept: () => {
            router.delete(`/teachers/${id}`);
        }
    });
};
</script>

<template>
    <Head title="Data Guru - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <span class="text-gray-500">Data Master</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Guru</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Area -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Data Guru</h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">Kelola daftar guru dan penugasan wali kelas sekolah.</p>
                </div>
                <Link 
                    href="/teachers/create" 
                    class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                >
                    <i class="pi pi-plus text-xs"></i>
                    <span>Tambah Guru</span>
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
                            placeholder="Cari NIP atau Nama Guru..." 
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3 text-left w-44">NIP / NUPTK</th>
                                <th class="pb-3 text-left">Nama Guru</th>
                                <th class="pb-3 text-left">Kontak</th>
                                <th class="pb-3 w-32 text-center">Status</th>
                                <th class="pb-3 w-28 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="teacher in teachers.data" :key="teacher.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4 text-xs font-mono text-gray-500">{{ teacher.employee_number || '-' }}</td>
                                <td class="py-4 text-gray-900 font-bold">{{ teacher.name }}</td>
                                <td class="py-4 text-xs text-gray-600">
                                    <div v-if="teacher.phone || teacher.email">
                                        <div v-if="teacher.phone" class="font-medium text-gray-800">{{ teacher.phone }}</div>
                                        <div v-if="teacher.email" class="text-gray-500">{{ teacher.email }}</div>
                                    </div>
                                    <div v-else class="text-gray-400">-</div>
                                </td>
                                <td class="py-4 text-center">
                                    <span 
                                        class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider"
                                        :class="(teacher.status.toLowerCase() === 'active' || teacher.status.toLowerCase() === 'aktif') ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ teacher.status }}
                                    </span>
                                </td>
                                <td class="py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link 
                                            :href="`/teachers/${teacher.id}/edit`" 
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-indigo-50 hover:text-[#6366F1] text-gray-600 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                            title="Edit Guru"
                                        >
                                            <i class="pi pi-pencil text-[11px]"></i>
                                        </Link>
                                        <button 
                                            @click="deleteTeacher(teacher.id)" 
                                            class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-600 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-red-500/40"
                                            title="Hapus Guru"
                                        >
                                            <i class="pi pi-trash text-[11px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="teachers.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-500 text-xs">
                                    Tidak ada data guru ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="teachers.links.length > 3" class="pt-4 border-t border-gray-100 flex items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in teachers.links" 
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
