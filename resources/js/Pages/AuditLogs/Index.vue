<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
        case 'created': return 'bg-green-100 text-green-800';
        case 'updated': return 'bg-blue-100 text-blue-800';
        case 'deleted': return 'bg-red-100 text-red-800';
        case 'closed': return 'bg-purple-100 text-purple-800';
        case 'imported': return 'bg-orange-100 text-orange-800';
        default: return 'bg-gray-100 text-gray-800';
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
    <Head title="Audit Logs - Disiplin Pro" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Logs Sistem</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Filters -->
                        <div class="flex flex-col md:flex-row gap-4 mb-6">
                            <div class="flex-1">
                                <input 
                                    v-model="search" 
                                    type="text" 
                                    placeholder="Cari deskripsi atau pengguna..." 
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                            </div>
                            <div class="w-full md:w-48">
                                <select v-model="actionType" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Semua Aksi</option>
                                    <option value="created">Create</option>
                                    <option value="updated">Update</option>
                                    <option value="deleted">Delete</option>
                                    <option value="closed">Close</option>
                                    <option value="imported">Import</option>
                                </select>
                            </div>
                            <div class="w-full md:w-48">
                                <input v-model="startDate" type="date" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" title="Tanggal Mulai">
                            </div>
                            <div class="w-full md:w-48">
                                <input v-model="endDate" type="date" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" title="Tanggal Selesai">
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template v-for="log in logs.data" :key="log.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ formatDate(log.created_at) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ log.user?.name || 'Sistem' }}
                                                <div class="text-xs text-gray-400 font-normal">{{ log.ip_address }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getActionColor(log.action)}`">
                                                    {{ getActionLabel(log.action) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ log.description }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button v-if="log.old_values || log.new_values || log.metadata" @click="toggleDetails(log.id)" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ expandedLogs.includes(log.id) ? 'Tutup' : 'Lihat' }}
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="expandedLogs.includes(log.id)" class="bg-gray-50">
                                            <td colspan="5" class="px-6 py-4">
                                                <div class="text-xs text-gray-700 grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div v-if="log.old_values">
                                                        <h4 class="font-bold text-red-700 mb-2">Sebelum:</h4>
                                                        <pre class="bg-white p-3 rounded border overflow-x-auto">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                                                    </div>
                                                    <div v-if="log.new_values">
                                                        <h4 class="font-bold text-green-700 mb-2">Sesudah / Baru:</h4>
                                                        <pre class="bg-white p-3 rounded border overflow-x-auto">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                                                    </div>
                                                    <div v-if="log.metadata && !log.old_values && !log.new_values" class="md:col-span-2">
                                                        <h4 class="font-bold text-gray-700 mb-2">Metadata:</h4>
                                                        <pre class="bg-white p-3 rounded border overflow-x-auto">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-xs text-gray-500">
                                                    <strong>Model:</strong> {{ log.subject_type || '-' }} | <strong>ID:</strong> {{ log.subject_id || '-' }}
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="logs.data.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Tidak ada catatan audit yang ditemukan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="mt-4 flex items-center justify-between" v-if="logs.links.length > 3">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <a :href="logs.links[0].url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                                <a :href="logs.links[logs.links.length - 1].url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Menampilkan halaman
                                        <span class="font-medium">{{ logs.current_page || 1 }}</span>
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <a v-for="(link, index) in logs.links" 
                                           :key="index"
                                           :href="link.url || '#'" 
                                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                           :class="[
                                                link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                !link.url && 'opacity-50 cursor-not-allowed'
                                           ]"
                                           v-html="link.label">
                                        </a>
                                    </nav>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
