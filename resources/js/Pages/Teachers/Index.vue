<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppTableToolbar from '@/Components/AppTableToolbar.vue';
import AppStatusBadge from '@/Components/AppStatusBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { confirmDelete } from '@/utils';

interface TeacherItem {
    id: number;
    employee_number: string | null;
    name: string;
    phone: string | null;
    email: string | null;
    status: string;
}

interface PaginatedTeachers {
    data: TeacherItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total?: number;
    from?: number;
    to?: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

const props = defineProps<{
    teachers: PaginatedTeachers;
    filters: {
        search?: string;
        per_page?: string | number;
    };
}>();

const search = ref(props.filters.search || '');
const perPage = ref(String(props.filters.per_page || props.teachers.per_page || '15'));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        (perPage.value && perPage.value !== '15')
    );
});

const applyFilters = () => {
    router.get(
        '/teachers',
        {
            search: search.value || undefined,
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

const promptDelete = async (teacher: TeacherItem) => {
    const confirmed = await confirmDelete({
        title: 'Hapus Data Guru?',
        text: `Apakah Anda yakin ingin menghapus data guru "${teacher.name}"? Tindakan ini tidak dapat dibatalkan.`,
        confirmButtonText: 'Ya, Hapus Guru',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/teachers/${teacher.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Data Guru - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="hover:text-gray-900 transition-colors font-medium">Data Master</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Guru</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Data Guru"
                description="Kelola direktori pendidik, data kontak, dan penugasan wali kelas sekolah."
                badge="Data Akademik"
                badge-icon="pi pi-briefcase"
            >
                <template #actions>
                    <Link
                        href="/teachers/create"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Tambah Guru</span>
                    </Link>
                </template>
            </AppPageHeader>

            <!-- Table & Filter Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                <!-- Standardized AppTableToolbar -->
                <AppTableToolbar
                    v-model="search"
                    search-placeholder="Cari NIP atau nama guru..."
                    search-width="w-full sm:w-80"
                    :total-records="teachers.total"
                    :filtered-records="teachers.data.length"
                    record-label="guru"
                    :has-active-filters="hasActiveFilters"
                    @reset="resetFilters"
                >
                    <template #filters>
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
                <div v-if="teachers.data && teachers.data.length > 0" class="overflow-x-auto">
                    <DataTable
                        :value="teachers.data"
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
                        <!-- Kolom 1: NIP / NUPTK -->
                        <Column field="employee_number" header="NIP / NUPTK" sortable class="py-3 px-4 w-44">
                            <template #body="{ data }">
                                <span class="font-mono text-xs text-gray-600 bg-gray-50 px-2 py-0.5 rounded border border-gray-100">
                                    {{ data.employee_number || '-' }}
                                </span>
                            </template>
                        </Column>

                        <!-- Kolom 2: Nama Guru -->
                        <Column field="name" header="Nama Guru" sortable class="py-3 px-4 font-semibold text-gray-900 min-w-[220px]">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100">
                                        {{ (data.name || 'G').charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-gray-900 text-xs truncate">
                                            {{ data.name }}
                                        </div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            Pendidik / Tenaga Kependidikan
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 3: Kontak -->
                        <Column header="Kontak" class="py-3 px-4 min-w-[200px]">
                            <template #body="{ data }">
                                <div v-if="data.phone || data.email" class="space-y-0.5">
                                    <div v-if="data.phone" class="flex items-center gap-1.5 text-xs text-gray-700">
                                        <i class="pi pi-phone text-[10px] text-gray-400"></i>
                                        <span>{{ data.phone }}</span>
                                    </div>
                                    <div v-if="data.email" class="flex items-center gap-1.5 text-xs text-gray-500">
                                        <i class="pi pi-envelope text-[10px] text-gray-400"></i>
                                        <span class="truncate">{{ data.email }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-gray-400 italic">-</span>
                            </template>
                        </Column>

                        <!-- Kolom 4: Status -->
                        <Column field="status" header="Status" sortable class="py-3 px-4 w-32 text-center">
                            <template #body="{ data }">
                                <AppStatusBadge :status="data.status" size="sm" />
                            </template>
                        </Column>

                        <!-- Kolom 5: Aksi -->
                        <Column header="Aksi" class="py-3 px-4 text-right w-28">
                            <template #body="{ data }">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link
                                        :href="`/teachers/${data.id}/edit`"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors inline-flex items-center gap-1"
                                        title="Edit Data Guru"
                                    >
                                        <i class="pi pi-pencil text-[10px]"></i>
                                        <span>Edit</span>
                                    </Link>

                                    <button
                                        type="button"
                                        @click="promptDelete(data)"
                                        class="px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Hapus Guru"
                                    >
                                        <i class="pi pi-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Server-side Pagination Bar -->
                    <div v-if="teachers.links && teachers.links.length > 3" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ teachers.from || 0 }}</strong> sampai <strong>{{ teachers.to || 0 }}</strong> dari <strong>{{ teachers.total || teachers.data.length }}</strong> data guru
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            <Link
                                v-for="(link, index) in teachers.links"
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
                    icon="pi pi-users"
                    title="Belum Ada Data Guru"
                    description="Tambahkan data pendidik baru untuk mulai mengelola pembagian wali kelas dan bimbingan siswa."
                    action-label="Tambah Guru Baru"
                    action-href="/teachers/create"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
