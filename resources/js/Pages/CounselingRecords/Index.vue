<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppTableToolbar from '@/Components/AppTableToolbar.vue';
import AppStatusBadge from '@/Components/AppStatusBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { confirmDelete } from '@/utils';

interface StudentInfo {
    id: number;
    name: string;
    nis: string;
    class_room?: { name: string };
}

interface CounselorInfo {
    id: number;
    name: string;
}

interface CounselingRecordItem {
    id: number;
    student_id: number;
    date_scheduled: string;
    date_completed?: string;
    student?: StudentInfo;
    counselor?: CounselorInfo;
    original_recorder_name?: string;
    category: string;
    status: string;
    confidentiality_level: string;
    reason?: string;
    summary?: string;
    follow_up_action?: string;
}

interface PaginatedRecords {
    data: CounselingRecordItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
    from: number;
    to: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

const props = defineProps<{
    records: PaginatedRecords;
    students: StudentInfo[];
    can_create: boolean;
    filters: {
        category?: string;
        status?: string;
        search?: string;
        per_page?: string | number;
    };
    categories: string[];
    statuses: string[];
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const userRoles = computed<string[]>(() => (page.props.auth as any)?.roles || []);

// Filter state
const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const currentStatus = ref(props.filters.status || '');
const perPage = ref(String(props.filters.per_page || props.records.per_page || '10'));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        selectedCategory.value ||
        currentStatus.value ||
        (perPage.value && perPage.value !== '10')
    );
});

const applyFilters = () => {
    router.get(
        '/counseling-records',
        {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
            status: currentStatus.value || undefined,
            per_page: perPage.value !== '10' ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const filterByStatus = (statusKey: string) => {
    currentStatus.value = statusKey;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    currentStatus.value = '';
    perPage.value = '10';
    applyFilters();
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

// Modal Create/Edit State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingRecordId = ref<number | null>(null);

const now = new Date();
const currentFormattedDate = now.toISOString().split('T')[0];
const currentFormattedTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
const currentDateTimeString = `${currentFormattedDate}T${currentFormattedTime}`;

const form = useForm({
    student_id: '' as string | number,
    category: 'Konseling',
    date_scheduled: currentDateTimeString,
    reason: '',
    summary: '',
    original_recorder_name: currentUser.value?.name || '',
    status: 'scheduled',
    confidentiality_level: 'normal',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingRecordId.value = null;
    form.reset();
    form.clearErrors();
    form.student_id = '';
    form.category = 'Konseling';
    form.date_scheduled = `${new Date().toISOString().split('T')[0]}T${String(new Date().getHours()).padStart(2, '0')}:${String(new Date().getMinutes()).padStart(2, '0')}`;
    form.reason = '';
    form.summary = '';
    form.original_recorder_name = currentUser.value?.name || '';
    form.status = 'scheduled';
    form.confidentiality_level = 'normal';
    isModalOpen.value = true;
};

const openEditModal = (record: CounselingRecordItem) => {
    isEditing.value = true;
    editingRecordId.value = record.id;
    form.clearErrors();
    form.student_id = record.student_id || record.student?.id || '';
    form.category = record.category || 'Konseling';
    
    const dateVal = record.date_scheduled ? record.date_scheduled.substring(0, 10) : currentFormattedDate;
    form.date_scheduled = `${dateVal}T12:00`;
    
    form.reason = record.reason || '';
    form.summary = record.summary || '';
    form.original_recorder_name = record.original_recorder_name || record.counselor?.name || currentUser.value?.name || '';
    form.status = record.status || 'scheduled';
    form.confidentiality_level = record.confidentiality_level || 'normal';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    isEditing.value = false;
    editingRecordId.value = null;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    const payloadDate = form.date_scheduled.includes('T')
        ? form.date_scheduled.split('T')[0]
        : form.date_scheduled;

    if (isEditing.value && editingRecordId.value) {
        form.transform((data) => ({
            ...data,
            date_scheduled: payloadDate,
        })).put(`/counseling-records/${editingRecordId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.transform((data) => ({
            ...data,
            date_scheduled: payloadDate,
        })).post('/counseling-records', {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const promptDelete = async (record: CounselingRecordItem) => {
    const confirmed = await confirmDelete({
        title: 'Hapus Catatan Pembinaan?',
        text: `Apakah Anda yakin ingin menghapus catatan pembinaan untuk siswa "${record.student?.name || 'Siswa'}"? Tindakan ini tidak dapat dibatalkan.`,
        confirmButtonText: 'Ya, Hapus Catatan',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/counseling-records/${record.id}`, {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateString?: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Pembinaan Siswa - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="hover:text-gray-900 transition-colors font-medium">Bimbingan & Konseling</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Pembinaan Siswa</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Pembinaan Siswa"
                description="Data proses pendampingan, konseling, dan pembinaan karakter siswa secara terstruktur dan konfidensial."
                badge="Bimbingan & Konseling"
                badge-icon="pi pi-heart"
            >
                <template #actions>
                    <div class="flex items-center gap-2.5">
                        <Link
                            v-if="userRoles.includes('Super Admin') || userRoles.includes('Tata Tertib') || userRoles.includes('Teacher') || userRoles.includes('BK')"
                            href="/discipline-events/create"
                            class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors shadow-2xs hidden md:inline-flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-plus text-[11px]"></i>
                            <span>Catat Kejadian</span>
                        </Link>

                        <button
                            v-if="can_create || userRoles.includes('BK') || userRoles.includes('Super Admin')"
                            type="button"
                            @click="openCreateModal"
                            class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-plus text-xs"></i>
                            <span>Sesi Pembinaan Baru</span>
                        </button>
                    </div>
                </template>
            </AppPageHeader>

            <!-- Table & Filter Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                <!-- Status Pills -->
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 pb-4">
                    <button
                        type="button"
                        @click="filterByStatus('')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="[
                            !currentStatus
                                ? 'bg-[#001AFF] text-white shadow-xs'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >
                        Semua Status
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('scheduled')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="[
                            currentStatus === 'scheduled'
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-blue-50 text-blue-700 hover:bg-blue-100'
                        ]"
                    >
                        Terjadwal
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('in_progress')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="[
                            currentStatus === 'in_progress'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'
                        ]"
                    >
                        Sedang Berjalan
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('resolved')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="[
                            currentStatus === 'resolved'
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                        ]"
                    >
                        Selesai
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('referred')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="[
                            currentStatus === 'referred'
                                ? 'bg-purple-600 text-white shadow-xs'
                                : 'bg-purple-50 text-purple-700 hover:bg-purple-100'
                        ]"
                    >
                        Dialihkan
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('cancelled')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="[
                            currentStatus === 'cancelled'
                                ? 'bg-gray-600 text-white shadow-xs'
                                : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                        ]"
                    >
                        Dibatalkan
                    </button>
                </div>

                <!-- Standardized AppTableToolbar -->
                <AppTableToolbar
                    v-model="search"
                    search-placeholder="Cari berdasarkan nama siswa atau NIS..."
                    search-width="w-full sm:w-80"
                    :total-records="records.total"
                    :filtered-records="records.data.length"
                    record-label="sesi pembinaan"
                    :has-active-filters="hasActiveFilters"
                    @reset="resetFilters"
                >
                    <template #filters>
                        <!-- Filter: Bentuk Pembinaan -->
                        <div class="w-full sm:w-56">
                            <select
                                v-model="selectedCategory"
                                @change="applyFilters"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Semua Bentuk Pembinaan</option>
                                <option v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </option>
                            </select>
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
                <div v-if="records.data && records.data.length > 0" class="overflow-x-auto">
                    <DataTable
                        :value="records.data"
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
                        <!-- Kolom 1: Tanggal Sesi -->
                        <Column field="date_scheduled" header="Tanggal Sesi" sortable class="py-3 px-4 text-xs font-medium text-gray-700 w-36">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-calendar text-gray-400 text-xs shrink-0"></i>
                                    <span class="font-bold text-gray-900 text-xs">{{ formatDate(data.date_scheduled) }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 2: Siswa & Kelas -->
                        <Column field="student.name" header="Siswa" sortable class="py-3 px-4 font-semibold text-gray-900 min-w-[220px]">
                            <template #body="{ data }">
                                <div v-if="data.student" class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100">
                                        {{ (data.student.name || 'S').charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <Link
                                            :href="`/students/${data.student.id}`"
                                            class="font-bold text-gray-900 hover:text-[#001AFF] transition-colors text-xs truncate block"
                                        >
                                            {{ data.student.name }}
                                        </Link>
                                        <div class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1.5">
                                            <span class="font-mono text-gray-500">{{ data.student.nis }}</span>
                                            <span>&bull;</span>
                                            <span class="inline-flex px-1.5 py-0.2 text-[10px] rounded bg-gray-100 text-gray-700 font-medium">
                                                {{ data.student.class_room?.name || 'Tanpa Kelas' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-gray-400 italic">Data siswa tidak ditemukan</span>
                            </template>
                        </Column>

                        <!-- Kolom 3: Bentuk Pembinaan -->
                        <Column field="category" header="Bentuk Pembinaan" sortable class="py-3 px-4 w-44">
                            <template #body="{ data }">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                                    {{ data.category || 'Konseling' }}
                                </span>
                            </template>
                        </Column>

                        <!-- Kolom 4: Catatan / Hasil Ringkas -->
                        <Column header="Catatan / Hasil" class="py-3 px-4 min-w-[200px] max-w-xs">
                            <template #body="{ data }">
                                <div v-if="data.summary" class="text-xs text-gray-800 line-clamp-2">
                                    <span class="font-bold text-gray-900">Hasil:</span> {{ data.summary }}
                                </div>
                                <div v-else-if="data.reason" class="text-xs text-gray-500 line-clamp-2">
                                    <span class="font-medium text-gray-700">Alasan:</span> {{ data.reason }}
                                </div>
                                <div v-else class="text-xs text-gray-400 italic">
                                    Belum ada catatan
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 5: Konselor / Pencatat -->
                        <Column field="original_recorder_name" header="Konselor / Pencatat" class="py-3 px-4 text-xs font-medium text-gray-600 w-44">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1.5 text-xs text-gray-700">
                                    <i class="pi pi-user text-[11px] text-gray-400"></i>
                                    <span class="truncate">{{ data.original_recorder_name || data.counselor?.name || 'Petugas BK' }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 6: Status -->
                        <Column field="status" header="Status" sortable class="py-3 px-4 w-36">
                            <template #body="{ data }">
                                <AppStatusBadge :status="data.status" size="sm" />
                            </template>
                        </Column>

                        <!-- Kolom 7: Aksi -->
                        <Column header="Aksi" class="py-3 px-4 text-right w-44">
                            <template #body="{ data }">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link
                                        :href="`/counseling-records/${data.id}`"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 transition-colors inline-flex items-center gap-1"
                                        title="Detail Lengkap"
                                    >
                                        <i class="pi pi-eye text-[10px]"></i>
                                        <span>Detail</span>
                                    </Link>

                                    <button
                                        v-if="currentUser?.id === data.counselor?.id || userRoles.includes('Super Admin')"
                                        type="button"
                                        @click="openEditModal(data)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Edit Sesi Pembinaan"
                                    >
                                        <i class="pi pi-pencil text-[10px]"></i>
                                        <span>Edit</span>
                                    </button>

                                    <button
                                        v-if="['scheduled', 'cancelled'].includes(data.status) && (currentUser?.id === data.counselor?.id || userRoles.includes('Super Admin'))"
                                        type="button"
                                        @click="promptDelete(data)"
                                        class="px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Hapus Catatan"
                                    >
                                        <i class="pi pi-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Server-side Pagination Bar -->
                    <div v-if="records.links && records.links.length > 3" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ records.from || 0 }}</strong> sampai <strong>{{ records.to || 0 }}</strong> dari <strong>{{ records.total }}</strong> sesi pembinaan
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            <Link
                                v-for="(link, index) in records.links"
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
                    icon="pi pi-heart"
                    title="Belum Ada Catatan Pembinaan"
                    description="Catat sesi pembinaan baru untuk mendampingi perkembangan kedisiplinan dan karakter siswa secara berkala."
                    :action-label="(can_create || userRoles.includes('BK') || userRoles.includes('Super Admin')) ? 'Buat Sesi Pembinaan' : ''"
                    @action="openCreateModal"
                />
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PRIME VUE DIALOG: MODAL CREATE / EDIT PEMBINAAN -->
        <!-- ========================================================================= -->
        <Dialog
            v-model:visible="isModalOpen"
            modal
            :header="isEditing ? 'Edit Catatan Pembinaan' : 'Sesi Pembinaan Baru'"
            :style="{ width: '92vw', maxWidth: '600px' }"
            :closable="true"
            @hide="closeModal"
            class="p-dialog-custom"
        >
            <form @submit.prevent="submitForm" class="space-y-4 pt-2">
                <!-- Siswa * -->
                <div>
                    <label for="modal_student_id" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Siswa <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="modal_student_id"
                        v-model="form.student_id"
                        required
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        :class="{ 'border-rose-400 ring-rose-200': form.errors.student_id }"
                    >
                        <option value="" disabled>Pilih siswa...</option>
                        <option v-for="student in students" :key="student.id" :value="student.id">
                            {{ student.name }} (NIS: {{ student.nis }}) ({{ student.class_room?.name || 'Tanpa Kelas' }})
                        </option>
                    </select>
                    <p v-if="form.errors.student_id" class="text-xs font-medium text-rose-600 mt-1">
                        {{ form.errors.student_id }}
                    </p>
                </div>

                <!-- Bentuk Pembinaan & Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="modal_category" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Bentuk Pembinaan
                        </label>
                        <input
                            id="modal_category"
                            v-model="form.category"
                            type="text"
                            placeholder="Konseling"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        />
                        <p v-if="form.errors.category" class="text-xs font-medium text-rose-600 mt-1">
                            {{ form.errors.category }}
                        </p>
                    </div>

                    <div>
                        <label for="modal_date_scheduled" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="modal_date_scheduled"
                            v-model="form.date_scheduled"
                            type="datetime-local"
                            required
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                            :class="{ 'border-rose-400 ring-rose-200': form.errors.date_scheduled }"
                        />
                        <p v-if="form.errors.date_scheduled" class="text-xs font-medium text-rose-600 mt-1">
                            {{ form.errors.date_scheduled }}
                        </p>
                    </div>
                </div>

                <!-- Alasan -->
                <div>
                    <label for="modal_reason" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Alasan / Latar Belakang
                    </label>
                    <textarea
                        id="modal_reason"
                        v-model="form.reason"
                        rows="3"
                        placeholder="Alasan pelaksanaan pembinaan atau latar belakang masalah..."
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all resize-y"
                    ></textarea>
                    <p v-if="form.errors.reason" class="text-xs font-medium text-rose-600 mt-1">
                        {{ form.errors.reason }}
                    </p>
                </div>

                <!-- Hasil / Catatan -->
                <div>
                    <label for="modal_summary" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Hasil / Catatan Pembinaan
                    </label>
                    <textarea
                        id="modal_summary"
                        v-model="form.summary"
                        rows="3"
                        placeholder="Catatan hasil pembinaan, kesepakatan, atau solusi..."
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all resize-y"
                    ></textarea>
                    <p v-if="form.errors.summary" class="text-xs font-medium text-rose-600 mt-1">
                        {{ form.errors.summary }}
                    </p>
                </div>

                <!-- Pencatat -->
                <div>
                    <label for="modal_recorder" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Pencatat / Petugas
                    </label>
                    <input
                        id="modal_recorder"
                        v-model="form.original_recorder_name"
                        type="text"
                        placeholder="Nama petugas atau konselor"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                    />
                </div>

                <!-- Status Sesi (Edit mode) -->
                <div v-if="isEditing" class="pt-1">
                    <label for="modal_status" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Status Sesi
                    </label>
                    <select
                        id="modal_status"
                        v-model="form.status"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    >
                        <option value="scheduled">Terjadwal</option>
                        <option value="in_progress">Sedang Berjalan</option>
                        <option value="resolved">Selesai</option>
                        <option value="referred">Dialihkan</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-check text-xs"></i>
                        <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Pembinaan' }}</span>
                    </button>
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
