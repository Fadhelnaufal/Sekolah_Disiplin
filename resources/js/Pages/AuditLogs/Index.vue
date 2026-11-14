<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppTableToolbar from '@/Components/AppTableToolbar.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';

interface AuditLogItem {
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
}

interface PaginatedLogs {
    data: AuditLogItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total?: number;
    from?: number;
    to?: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

const props = defineProps<{
    logs: PaginatedLogs;
    filters: {
        search?: string;
        action_type?: string;
        start_date?: string;
        end_date?: string;
        per_page?: string | number;
    };
}>();

const search = ref(props.filters.search || '');
const actionType = ref(props.filters.action_type || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const perPage = ref(String(props.filters.per_page || props.logs.per_page || '15'));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        actionType.value ||
        startDate.value ||
        endDate.value ||
        (perPage.value && perPage.value !== '15')
    );
});

const applyFilters = () => {
    router.get(
        '/audit-logs',
        {
            search: search.value || undefined,
            action_type: actionType.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
            per_page: perPage.value !== '15' ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const resetFilters = () => {
    search.value = '';
    actionType.value = '';
    startDate.value = '';
    endDate.value = '';
    perPage.value = '15';
    applyFilters();
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

// Detail Modal
const isDetailModalOpen = ref(false);
const selectedLog = ref<AuditLogItem | null>(null);

const openDetailModal = (log: AuditLogItem) => {
    selectedLog.value = log;
    isDetailModalOpen.value = true;
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(d);
};

const getActionBadge = (action: string) => {
    switch (action.toLowerCase()) {
        case 'created':
            return { label: 'CREATE', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'updated':
            return { label: 'UPDATE', class: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'deleted':
            return { label: 'DELETE', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'closed':
            return { label: 'CLOSE', class: 'bg-purple-50 text-purple-700 border-purple-200' };
        case 'imported':
            return { label: 'IMPORT', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        default:
            return { label: action.toUpperCase(), class: 'bg-gray-100 text-gray-700 border-gray-200' };
    }
};
</script>

<template>
    <Head title="Audit Log Aktivitas - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="hover:text-gray-900 transition-colors font-medium">Pengaturan</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Log Aktivitas</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Audit Log Aktivitas"
                description="Rekam jejak aktivitas pengguna, mutasi data, dan audit kepatuhan sistem keamanan."
                badge="Keamanan Sistem"
                badge-icon="pi pi-shield"
            />

            <!-- Table & Filter Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                <!-- Standardized AppTableToolbar -->
                <AppTableToolbar
                    v-model="search"
                    search-placeholder="Cari deskripsi atau nama pengguna..."
                    search-width="w-full sm:w-72"
                    :total-records="logs.total"
                    :filtered-records="logs.data.length"
                    record-label="aktivitas"
                    :has-active-filters="hasActiveFilters"
                    @reset="resetFilters"
                >
                    <template #filters>
                        <!-- Filter: Jenis Aksi -->
                        <div class="w-full sm:w-36">
                            <select
                                v-model="actionType"
                                @change="applyFilters"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Semua Aksi</option>
                                <option value="created">Create</option>
                                <option value="updated">Update</option>
                                <option value="deleted">Delete</option>
                                <option value="closed">Close</option>
                                <option value="imported">Import</option>
                            </select>
                        </div>

                        <!-- Filter: Rentang Tanggal -->
                        <div class="w-full sm:w-36">
                            <input
                                v-model="startDate"
                                @change="applyFilters"
                                type="date"
                                class="w-full px-3 py-1.5 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                                title="Tanggal Mulai"
                            />
                        </div>

                        <div class="w-full sm:w-36">
                            <input
                                v-model="endDate"
                                @change="applyFilters"
                                type="date"
                                class="w-full px-3 py-1.5 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                                title="Tanggal Selesai"
                            />
                        </div>

                        <!-- Filter: Per Page -->
                        <div class="w-full sm:w-32">
                            <select
                                v-model="perPage"
                                @change="applyFilters"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="10">10 / halaman</option>
                                <option value="15">15 / halaman</option>
                                <option value="25">25 / halaman</option>
                                <option value="50">50 / halaman</option>
                            </select>
                        </div>
                    </template>
                </AppTableToolbar>

                <!-- PrimeVue DataTable Section -->
                <div v-if="logs.data && logs.data.length > 0" class="overflow-x-auto">
                    <DataTable
                        :value="logs.data"
                        responsiveLayout="stack"
                        breakpoint="768px"
                        class="p-datatable-sm"
                        removableSort
                        :pt="{
                            table: { class: 'w-full text-left border-collapse text-xs' },
                            thead: { class: 'bg-gray-50/70 border-b border-gray-200' },
                            headerRow: { class: 'border-b border-gray-200 text-[11px] font-bold uppercase tracking-wider text-gray-500' },
                            row: { class: 'border-b border-gray-100 hover:bg-gray-50/80 transition-colors' }
                        }"
                    >
                        <!-- Kolom 1: Waktu -->
                        <Column field="created_at" header="Waktu Aktivitas" sortable class="py-3 px-4 w-44 font-mono text-gray-600">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1.5">
                                    <i class="pi pi-clock text-[10px] text-gray-400"></i>
                                    <span>{{ formatDate(data.created_at) }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 2: Pengguna -->
                        <Column field="user.name" header="Pelaku & IP" sortable class="py-3 px-4 min-w-[180px]">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-indigo-50 text-indigo-700 font-bold text-[11px] flex items-center justify-center shrink-0 border border-indigo-100">
                                        {{ (data.user?.name || 'S').charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-gray-900 text-xs truncate">
                                            {{ data.user?.name || 'Sistem Otomatis' }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                                            IP: {{ data.ip_address || '-' }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 3: Aksi -->
                        <Column field="action" header="Aksi" sortable class="py-3 px-4 w-28">
                            <template #body="{ data }">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase border tracking-wider"
                                    :class="getActionBadge(data.action).class"
                                >
                                    {{ getActionBadge(data.action).label }}
                                </span>
                            </template>
                        </Column>

                        <!-- Kolom 4: Deskripsi -->
                        <Column field="description" header="Deskripsi Mutasi" class="py-3 px-4 min-w-[260px]">
                            <template #body="{ data }">
                                <span class="text-xs text-gray-700 font-medium">
                                    {{ data.description }}
                                </span>
                            </template>
                        </Column>

                        <!-- Kolom 5: Detail -->
                        <Column header="Detail" class="py-3 px-4 text-right w-24">
                            <template #body="{ data }">
                                <button
                                    v-if="data.old_values || data.new_values || data.metadata"
                                    type="button"
                                    @click="openDetailModal(data)"
                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-indigo-50 hover:text-[#001AFF] text-gray-700 transition-colors inline-flex items-center gap-1 cursor-pointer"
                                    title="Lihat Rincian Perubahan"
                                >
                                    <i class="pi pi-eye text-[10px]"></i>
                                    <span>Lihat</span>
                                </button>
                                <span v-else class="text-xs text-gray-400 italic">-</span>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Server-side Pagination Bar -->
                    <div v-if="logs.links && logs.links.length > 3" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ logs.from || 0 }}</strong> sampai <strong>{{ logs.to || 0 }}</strong> dari <strong>{{ logs.total || logs.data.length }}</strong> rekaman log
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            <Link
                                v-for="(link, index) in logs.links"
                                :key="index"
                                :href="link.url || '#'"
                                class="min-w-[32px] h-8 flex items-center justify-center px-2.5 text-xs font-medium rounded-lg transition-colors"
                                :class="[
                                    link.active ? 'bg-[#001AFF] text-white font-bold' : 'text-gray-600 hover:bg-gray-100',
                                    !link.url && 'opacity-40 cursor-not-allowed hover:bg-transparent'
                                ]"
                                v-html="link.label"
                                preserve-scroll
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty State Component -->
                <AppEmptyState
                    v-else
                    icon="pi pi-shield"
                    title="Belum Ada Log Aktivitas"
                    description="Seluruh aksi mutasi data dan catatan keamanan sistem akan dicatat di sini secara otomatis."
                />
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PRIME VUE DIALOG: DETAIL MUTASI LOG -->
        <!-- ========================================================================= -->
        <Dialog
            v-model:visible="isDetailModalOpen"
            modal
            :header="`Rincian Log Aktivitas #${selectedLog?.id || ''}`"
            :style="{ width: '92vw', maxWidth: '720px' }"
            :closable="true"
            class="p-dialog-custom"
        >
            <div v-if="selectedLog" class="space-y-4 pt-2">
                <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 text-xs space-y-1">
                    <div class="font-bold text-gray-900">{{ selectedLog.description }}</div>
                    <div class="text-gray-500 flex items-center gap-2">
                        <span>Oleh: <strong>{{ selectedLog.user?.name || 'Sistem' }}</strong></span>
                        <span>&bull;</span>
                        <span>IP: <strong>{{ selectedLog.ip_address || '-' }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ formatDate(selectedLog.created_at) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-if="selectedLog.old_values" class="space-y-1">
                        <h4 class="font-bold text-xs text-rose-700 flex items-center gap-1">
                            <i class="pi pi-arrow-left text-[10px]"></i>
                            <span>Data Sebelum Perubahan:</span>
                        </h4>
                        <pre class="bg-white p-3 rounded-xl border border-gray-200 overflow-x-auto text-[11px] font-mono max-h-60 leading-relaxed text-gray-800">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                    </div>

                    <div v-if="selectedLog.new_values" class="space-y-1">
                        <h4 class="font-bold text-xs text-emerald-700 flex items-center gap-1">
                            <i class="pi pi-arrow-right text-[10px]"></i>
                            <span>Data Sesudah Perubahan:</span>
                        </h4>
                        <pre class="bg-white p-3 rounded-xl border border-gray-200 overflow-x-auto text-[11px] font-mono max-h-60 leading-relaxed text-gray-800">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                    </div>
                </div>

                <div v-if="selectedLog.metadata" class="space-y-1">
                    <h4 class="font-bold text-xs text-indigo-700">Metadata Tambahan:</h4>
                    <pre class="bg-white p-3 rounded-xl border border-gray-200 overflow-x-auto text-[11px] font-mono max-h-40 leading-relaxed text-gray-800">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                </div>

                <div class="pt-3 border-t border-gray-100 flex justify-end">
                    <button
                        type="button"
                        @click="isDetailModalOpen = false"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>
