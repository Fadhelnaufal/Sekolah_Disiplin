<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    logs: {
        data: Array<{
            id: number;
            user: { name: string } | null;
            action: string;
            subject_type: string | null;
            subject_id: string | null;
            description: string;
            old_values: any | null;
            new_values: any | null;
            metadata: any | null;
            ip_address: string | null;
            created_at: string;
        }>;
        links: any[];
        current_page?: number;
    };
    filters: {
        search?: string;
        action_type?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

const search = ref(props.filters.search || '');
const actionType = ref(props.filters.action_type || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

// Debounce search
let timeout: any = null;
watch([search, actionType, startDate, endDate], ([newSearch, newAction, newStart, newEnd]) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/audit-logs', { 
            search: newSearch, 
            action_type: newAction,
            start_date: newStart,
            end_date: newEnd
        }, { preserveState: true, replace: true });
    }, 300);
});

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { 
        year: 'numeric', month: 'short', day: 'numeric', 
        hour: '2-digit', minute: '2-digit', second: '2-digit' 
    }).format(d);
};

const getActionColor = (action: string) => {
    switch(action.toLowerCase()) {
        case 'created': return 'bg-emerald-50 text-emerald-700';
        case 'updated': return 'bg-blue-50 text-blue-700';
        case 'deleted': return 'bg-rose-50 text-rose-700';
        case 'closed': return 'bg-purple-50 text-purple-700';
        case 'imported': return 'bg-amber-50 text-amber-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};

const getActionLabel = (action: string) => {
    switch(action.toLowerCase()) {
        case 'created': return 'CREATE';
        case 'updated': return 'UPDATE';
        case 'deleted': return 'DELETE';
        case 'closed': return 'CLOSE';
        case 'imported': return 'IMPORT';
        default: return action.toUpperCase();
    }
};

const expandedLogs = ref<number[]>([]);
const toggleDetails = (id: number) => {
    const index = expandedLogs.value.indexOf(id);
    if (index > -1) {
        expandedLogs.value.splice(index, 1);
    } else {
        expandedLogs.value.push(id);
    }
};
</script>

<template>
    <Head title="Audit Logs - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <span class="text-gray-500">Pengaturan</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Log Aktivitas</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Area -->
            <div>
                <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Audit Logs Sistem</h1>
                <p class="text-gray-500 text-[15px] mt-0.5">Rekam jejak aktivitas, mutasi data, dan audit keamanan sistem.</p>
            </div>

            <!-- Card Container -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-5 border border-gray-100">
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-1 relative">
                        <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari deskripsi atau pengguna..." 
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                    </div>
                    <div>
                        <select 
                            v-model="actionType" 
                            class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                        >
                            <option value="">Semua Aksi</option>
                            <option value="created">Create</option>
                            <option value="updated">Update</option>
                            <option value="deleted">Delete</option>
                            <option value="closed">Close</option>
                            <option value="imported">Import</option>
                        </select>
                    </div>
                    <div>
                        <input 
                            v-model="startDate" 
                            type="date" 
                            class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]" 
                            title="Tanggal Mulai"
                        >
                    </div>
                    <div>
                        <input 
                            v-model="endDate" 
                            type="date" 
                            class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]" 
                            title="Tanggal Selesai"
                        >
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3 text-left w-48">Waktu</th>
                                <th class="pb-3 text-left w-52">Pengguna</th>
                                <th class="pb-3 text-left w-28">Aksi</th>
                                <th class="pb-3 text-left">Deskripsi</th>
                                <th class="pb-3 text-right w-20">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <template v-for="log in logs.data" :key="log.id">
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="py-4 text-xs font-mono text-gray-500">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="py-4 text-xs">
                                        <div class="font-bold text-gray-900">{{ log.user?.name || 'Sistem' }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">{{ log.ip_address || '-' }}</div>
                                    </td>
                                    <td class="py-4">
                                        <span :class="`px-2.5 py-0.5 inline-flex text-[10px] font-bold uppercase rounded-md tracking-wider ${getActionColor(log.action)}`">
                                            {{ getActionLabel(log.action) }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-xs text-gray-700">
                                        {{ log.description }}
                                    </td>
                                    <td class="py-4 text-right">
                                        <button 
                                            v-if="log.old_values || log.new_values || log.metadata" 
                                            @click="toggleDetails(log.id)" 
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 hover:bg-indigo-50 hover:text-[#6366F1] text-gray-600 transition-colors"
                                        >
                                            {{ expandedLogs.includes(log.id) ? 'Tutup' : 'Lihat' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expandedLogs.includes(log.id)" class="bg-gray-50/70">
                                    <td colspan="5" class="p-4 rounded-xl">
                                        <div class="text-xs text-gray-700 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div v-if="log.old_values">
                                                <h4 class="font-bold text-rose-700 mb-1">Sebelum:</h4>
                                                <pre class="bg-white p-3 rounded-xl border border-gray-200 overflow-x-auto text-[11px] font-mono">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                                            </div>
                                            <div v-if="log.new_values">
                                                <h4 class="font-bold text-emerald-700 mb-1">Sesudah / Baru:</h4>
                                                <pre class="bg-white p-3 rounded-xl border border-gray-200 overflow-x-auto text-[11px] font-mono">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                                            </div>
                                            <div v-if="log.metadata && !log.old_values && !log.new_values" class="md:col-span-2">
                                                <h4 class="font-bold text-gray-700 mb-1">Metadata:</h4>
                                                <pre class="bg-white p-3 rounded-xl border border-gray-200 overflow-x-auto text-[11px] font-mono">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500 font-mono">
                                            Model: {{ log.subject_type || '-' }} | ID: {{ log.subject_id || '-' }}
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-500 text-xs">
                                    Tidak ada catatan audit yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="logs.links.length > 3" class="pt-4 border-t border-gray-100 flex items-center justify-center gap-1">
                    <Link 
                        v-for="(link, index) in logs.links" 
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
