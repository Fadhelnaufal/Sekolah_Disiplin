<script setup lang="ts">
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    sanctionRules: {
        data: Array<{
            id: number;
            level: string;
            min_points: number;
            max_points: number;
            action: string;
            executor: string;
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
    router.get('/sanction-rules', { search: value }, { preserveState: true, replace: true });
}, 300));

const deleteSanctionRule = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Tindakan',
        text: 'Apakah Anda yakin ingin menghapus aturan sanksi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#B42318',
        cancelButtonColor: '#697586',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/sanction-rules/${id}`);
        }
    });
};
</script>

<template>
    <Head title="Aturan Sanksi - Disiplin Pro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full pr-4">
                <span class="text-[#697586] text-sm">Pengaturan <span class="mx-1">/</span> <span class="text-[#202B3C] font-semibold">Aturan Sanksi</span></span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Area -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#202B3C] tracking-tight">Aturan Sanksi</h1>
                    <p class="text-[#697586] mt-1 text-sm">Kelola aturan tindak lanjut berdasarkan ambang batas poin pelanggaran siswa.</p>
                </div>
                <Link 
                    href="/sanction-rules/create" 
                    class="inline-flex items-center justify-center bg-[#1D3557] hover:bg-[#0D1B2A] text-white text-sm font-medium py-2 px-4 rounded transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D3557]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Aturan Sanksi
                </Link>
            </div>

            <!-- Toolbar (Search) -->
            <div class="bg-white p-4 border border-[#E5E9EF] shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="w-full sm:max-w-md relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#697586]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Cari tingkat sanksi, tindak lanjut..." 
                        class="block w-full pl-9 pr-3 py-2 border border-[#E5E9EF] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] bg-[#F7F8FA] focus:bg-white transition-colors"
                    >
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="bg-[#237A57]/10 border border-[#237A57]/30 text-[#237A57] px-4 py-3 rounded text-sm font-medium">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-[#B42318]/10 border border-[#B42318]/30 text-[#B42318] px-4 py-3 rounded text-sm font-medium">
                {{ $page.props.flash.error }}
            </div>

            <!-- Data Table -->
            <div class="bg-white border border-[#E5E9EF] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-[#F7F8FA] text-[#697586] font-semibold text-xs uppercase tracking-wider border-b border-[#E5E9EF]">
                            <tr>
                                <th class="px-5 py-3 w-32">Rentang Poin</th>
                                <th class="px-5 py-3 w-32">Tingkat</th>
                                <th class="px-5 py-3">Tindak Lanjut</th>
                                <th class="px-5 py-3 w-40">Eksekutor</th>
                                <th class="px-5 py-3 w-32 text-center">Status</th>
                                <th class="px-5 py-3 w-32 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E9EF]">
                            <tr v-for="rule in sanctionRules.data" :key="rule.id" class="hover:bg-[#F7F8FA]/80 transition-colors">
                                <td class="px-5 py-3 text-[#B42318] font-bold">{{ rule.min_points }} - {{ rule.max_points }}</td>
                                <td class="px-5 py-3 text-[#202B3C] font-bold">{{ rule.level }}</td>
                                <td class="px-5 py-3 text-[#202B3C] font-medium">{{ rule.action }}</td>
                                <td class="px-5 py-3 text-[#697586]">{{ rule.executor }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span 
                                        class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded"
                                        :class="rule.is_active ? 'bg-[#237A57]/10 text-[#237A57]' : 'bg-[#697586]/10 text-[#697586]'"
                                    >
                                        {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right space-x-3">
                                    <Link :href="`/sanction-rules/${rule.id}/edit`" class="text-[#315E8A] hover:text-[#1D3557] font-medium transition-colors">Edit</Link>
                                    <button @click="deleteSanctionRule(rule.id)" class="text-[#B42318] hover:text-red-800 font-medium transition-colors">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="sanctionRules.data.length === 0">
                                <td colspan="6" class="px-5 py-10 text-center text-[#697586]">
                                    <p class="text-sm font-medium">Tidak ada data aturan sanksi ditemukan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="sanctionRules.links.length > 3" class="px-5 py-4 border-t border-[#E5E9EF] flex items-center justify-between">
                    <div class="flex items-center space-x-1">
                        <Link 
                            v-for="(link, index) in sanctionRules.links" 
                            :key="index"
                            :href="link.url || '#'"
                            class="px-3 py-1 text-sm border rounded transition-colors"
                            :class="[
                                link.active ? 'bg-[#1D3557] text-white border-[#1D3557]' : 'text-[#697586] border-[#E5E9EF] hover:bg-[#F7F8FA]',
                                !link.url && 'opacity-50 cursor-not-allowed hover:bg-white'
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
