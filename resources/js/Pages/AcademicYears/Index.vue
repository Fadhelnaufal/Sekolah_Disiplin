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

interface AcademicYearItem {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
}

interface PaginatedAcademicYears {
    data: AcademicYearItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total?: number;
    from?: number;
    to?: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

const props = defineProps<{
    academicYears: PaginatedAcademicYears;
    filters: {
        search?: string;
        per_page?: string | number;
    };
}>();

const search = ref(props.filters.search || '');
const perPage = ref(String(props.filters.per_page || props.academicYears.per_page || '15'));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        (perPage.value && perPage.value !== '15')
    );
});

const applyFilters = () => {
    router.get(
        '/academic-years',
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

const promptDelete = async (year: AcademicYearItem) => {
    const confirmed = await confirmDelete({
        title: 'Hapus Tahun Ajaran?',
        text: `Apakah Anda yakin ingin menghapus data tahun ajaran "${year.name}"? Tindakan ini tidak dapat dibatalkan.`,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/academic-years/${year.id}`, {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(d);
};
</script>

<template>
    <Head title="Tahun Ajaran - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <Link href="/settings" class="hover:text-gray-900 transition-colors">Pengaturan</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Tahun Ajaran</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Tahun Ajaran"
                description="Kelola periode kalender akademik tahunan dan status keaktifan tahun ajaran sekolah."
                badge="Konfigurasi Akademik"
                badge-icon="pi pi-calendar"
            >
                <template #actions>
                    <div class="flex items-center gap-2.5">
                        <Link
                            href="/semesters"
                            class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-clock text-[11px]"></i>
                            <span>Kelola Semester</span>
                        </Link>

                        <Link
                            href="/academic-years/create"
                            class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-plus text-xs"></i>
                            <span>Tambah Tahun Ajaran</span>
                        </Link>
                    </div>
                </template>
            </AppPageHeader>

            <!-- Table & Filter Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                <!-- Standardized AppTableToolbar -->
                <AppTableToolbar
                    v-model="search"
                    search-placeholder="Cari tahun ajaran (misal: 2024/2025)..."
                    search-width="w-full sm:w-80"
                    :total-records="academicYears.total"
                    :filtered-records="academicYears.data.length"
                    record-label="tahun ajaran"
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
                <div v-if="academicYears.data && academicYears.data.length > 0" class="overflow-x-auto">
                    <DataTable
                        :value="academicYears.data"
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
                        <!-- Kolom 1: Nama Tahun Ajaran -->
                        <Column field="name" header="Tahun Ajaran" sortable class="py-3 px-4 font-semibold text-gray-900 min-w-[200px]">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100">
                                        <i class="pi pi-calendar text-xs"></i>
                                    </div>
                                    <div class="font-bold text-gray-900 text-xs">
                                        {{ data.name }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 2: Tanggal Mulai -->
                        <Column field="start_date" header="Tanggal Mulai" sortable class="py-3 px-4 text-xs font-medium text-gray-700 w-44">
                            <template #body="{ data }">
                                {{ formatDate(data.start_date) }}
                            </template>
                        </Column>

                        <!-- Kolom 3: Tanggal Selesai -->
                        <Column field="end_date" header="Tanggal Selesai" sortable class="py-3 px-4 text-xs font-medium text-gray-700 w-44">
                            <template #body="{ data }">
                                {{ formatDate(data.end_date) }}
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
                                        :href="`/academic-years/${data.id}/edit`"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors inline-flex items-center gap-1"
                                        title="Edit Tahun Ajaran"
                                    >
                                        <i class="pi pi-pencil text-[10px]"></i>
                                        <span>Edit</span>
                                    </Link>

                                    <button
                                        type="button"
                                        @click="promptDelete(data)"
                                        class="px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Hapus Tahun Ajaran"
                                    >
                                        <i class="pi pi-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Server-side Pagination Bar -->
                    <div v-if="academicYears.links && academicYears.links.length > 3" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ academicYears.from || 0 }}</strong> sampai <strong>{{ academicYears.to || 0 }}</strong> dari <strong>{{ academicYears.total || academicYears.data.length }}</strong> data tahun ajaran
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            <Link
                                v-for="(link, index) in academicYears.links"
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
                    icon="pi pi-calendar"
                    title="Belum Ada Tahun Ajaran"
                    description="Tambahkan tahun ajaran baru untuk mengaktifkan konfigurasi periode kalender sekolah."
                    action-label="Tambah Tahun Ajaran Baru"
                    action-href="/academic-years/create"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
