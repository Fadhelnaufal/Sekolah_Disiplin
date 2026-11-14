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

interface ClassRoomItem {
    id: number;
    name: string;
    grade_level: number;
    status: string;
    homeroom_teacher: {
        id: number;
        name: string;
    } | null;
    academic_year: {
        id: number;
        name: string;
    } | null;
}

interface PaginatedClassRooms {
    data: ClassRoomItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total?: number;
    from?: number;
    to?: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

const props = defineProps<{
    classRooms: PaginatedClassRooms;
    filters: {
        search?: string;
        per_page?: string | number;
    };
}>();

const search = ref(props.filters.search || '');
const perPage = ref(String(props.filters.per_page || props.classRooms.per_page || '15'));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        (perPage.value && perPage.value !== '15')
    );
});

const applyFilters = () => {
    router.get(
        '/class-rooms',
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

const promptDelete = async (classRoom: ClassRoomItem) => {
    const confirmed = await confirmDelete({
        title: 'Hapus Data Kelas?',
        text: `Apakah Anda yakin ingin menghapus kelas "${classRoom.name}"? Tindakan ini tidak dapat dibatalkan.`,
        confirmButtonText: 'Ya, Hapus Kelas',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/class-rooms/${classRoom.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Data Kelas - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="hover:text-gray-900 transition-colors font-medium">Data Master</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Kelas</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Data Kelas"
                description="Kelola daftar rombongan belajar (rombel), tingkat kelas, dan penetapan wali kelas."
                badge="Data Akademik"
                badge-icon="pi pi-th-large"
            >
                <template #actions>
                    <Link
                        href="/class-rooms/create"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Tambah Kelas</span>
                    </Link>
                </template>
            </AppPageHeader>

            <!-- Table & Filter Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                <!-- Standardized AppTableToolbar -->
                <AppTableToolbar
                    v-model="search"
                    search-placeholder="Cari nama kelas..."
                    search-width="w-full sm:w-80"
                    :total-records="classRooms.total"
                    :filtered-records="classRooms.data.length"
                    record-label="kelas"
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
                <div v-if="classRooms.data && classRooms.data.length > 0" class="overflow-x-auto">
                    <DataTable
                        :value="classRooms.data"
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
                        <!-- Kolom 1: Tingkat -->
                        <Column field="grade_level" header="Tingkat" sortable class="py-3 px-4 w-28">
                            <template #body="{ data }">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-700">
                                    Kelas {{ data.grade_level }}
                                </span>
                            </template>
                        </Column>

                        <!-- Kolom 2: Nama Rombel -->
                        <Column field="name" header="Nama Kelas" sortable class="py-3 px-4 font-semibold text-gray-900 min-w-[180px]">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100">
                                        {{ data.name }}
                                    </div>
                                    <div class="font-bold text-gray-900 text-xs">
                                        {{ data.name }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 3: Wali Kelas -->
                        <Column field="homeroom_teacher.name" header="Wali Kelas" sortable class="py-3 px-4 min-w-[200px]">
                            <template #body="{ data }">
                                <div v-if="data.homeroom_teacher" class="flex items-center gap-1.5 text-xs text-gray-800">
                                    <i class="pi pi-user text-[11px] text-gray-400"></i>
                                    <span class="font-medium">{{ data.homeroom_teacher.name }}</span>
                                </div>
                                <span v-else class="text-xs text-gray-400 italic">Belum ditentukan</span>
                            </template>
                        </Column>

                        <!-- Kolom 4: Tahun Ajaran -->
                        <Column field="academic_year.name" header="Tahun Ajaran" class="py-3 px-4 w-40">
                            <template #body="{ data }">
                                <div v-if="data.academic_year" class="text-xs text-gray-600 font-medium">
                                    {{ data.academic_year.name }}
                                </div>
                                <span v-else class="text-xs text-gray-400 italic">-</span>
                            </template>
                        </Column>

                        <!-- Kolom 5: Status -->
                        <Column field="status" header="Status" sortable class="py-3 px-4 w-32 text-center">
                            <template #body="{ data }">
                                <AppStatusBadge :status="data.status" size="sm" />
                            </template>
                        </Column>

                        <!-- Kolom 6: Aksi -->
                        <Column header="Aksi" class="py-3 px-4 text-right w-28">
                            <template #body="{ data }">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link
                                        :href="`/class-rooms/${data.id}/edit`"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors inline-flex items-center gap-1"
                                        title="Edit Kelas"
                                    >
                                        <i class="pi pi-pencil text-[10px]"></i>
                                        <span>Edit</span>
                                    </Link>

                                    <button
                                        type="button"
                                        @click="promptDelete(data)"
                                        class="px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Hapus Kelas"
                                    >
                                        <i class="pi pi-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Server-side Pagination Bar -->
                    <div v-if="classRooms.links && classRooms.links.length > 3" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ classRooms.from || 0 }}</strong> sampai <strong>{{ classRooms.to || 0 }}</strong> dari <strong>{{ classRooms.total || classRooms.data.length }}</strong> rombel kelas
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            <Link
                                v-for="(link, index) in classRooms.links"
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
                    icon="pi pi-th-large"
                    title="Belum Ada Data Kelas"
                    description="Tambahkan rombongan belajar baru untuk mengelompokkan siswa berdasarkan tingkat dan tahun ajaran."
                    action-label="Tambah Kelas Baru"
                    action-href="/class-rooms/create"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
