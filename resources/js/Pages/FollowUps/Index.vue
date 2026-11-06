<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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

interface UserInfo {
    id: number;
    name: string;
}

interface FollowUpItem {
    id: number;
    title: string;
    action_plan: string | null;
    due_date: string | null;
    status: string;
    outcome: string | null;
    is_overdue: boolean;
    student: StudentInfo;
    assigned_to_user?: UserInfo;
    assigned_to?: number;
}

interface PaginatedFollowUps {
    data: FollowUpItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total?: number;
    from?: number;
    to?: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

const props = defineProps<{
    followUps: PaginatedFollowUps;
    counts: {
        all: number;
        pending: number;
        in_progress: number;
        completed: number;
        overdue: number;
    };
    filters: {
        search?: string;
        status?: string;
        student_id?: string | number;
        per_page?: string | number;
    };
    students: Array<{
        id: number;
        name: string;
        nis: string;
        class_room_id?: number;
    }>;
    users: Array<{
        id: number;
        name: string;
    }>;
}>();

const search = ref(props.filters.search || '');
const activeTab = ref(props.filters.status || 'all');
const studentId = ref(props.filters.student_id ? String(props.filters.student_id) : '');
const perPage = ref(String(props.filters.per_page || props.followUps.per_page || '15'));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        studentId.value ||
        activeTab.value !== 'all' ||
        (perPage.value && perPage.value !== '15')
    );
});

const applyFilters = () => {
    router.get(
        '/follow-ups',
        {
            search: search.value || undefined,
            status: activeTab.value === 'all' ? undefined : activeTab.value,
            student_id: studentId.value || undefined,
            per_page: perPage.value !== '15' ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const setTab = (status: string) => {
    activeTab.value = status;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    activeTab.value = 'all';
    studentId.value = '';
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

// Create Modal State
const isCreateModalOpen = ref(false);
const createForm = useForm({
    student_id: '',
    title: '',
    action_plan: '',
    due_date: '',
    status: 'pending',
    assigned_to: '',
});

// Edit Modal State
const isEditModalOpen = ref(false);
const selectedFollowUp = ref<FollowUpItem | null>(null);
const editForm = useForm({
    title: '',
    action_plan: '',
    due_date: '',
    status: 'pending',
    outcome: '',
    assigned_to: '',
});

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'create' || (urlParams.get('student_id') && urlParams.get('title'))) {
            createForm.reset();
            createForm.student_id = urlParams.get('student_id') || '';
            createForm.title = urlParams.get('title') || '';
            if (urlParams.get('action_plan')) {
                createForm.action_plan = urlParams.get('action_plan') || '';
            }
            isCreateModalOpen.value = true;
        }
    }
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
};

const submitCreate = () => {
    createForm.post('/follow-ups', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (item: FollowUpItem) => {
    selectedFollowUp.value = item;
    editForm.clearErrors();
    editForm.title = item.title;
    editForm.action_plan = item.action_plan || '';
    editForm.due_date = item.due_date ? item.due_date.substring(0, 10) : '';
    editForm.status = item.status;
    editForm.outcome = item.outcome || '';
    editForm.assigned_to = item.assigned_to ? String(item.assigned_to) : '';
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!selectedFollowUp.value) return;
    editForm.put(`/follow-ups/${selectedFollowUp.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};

const promptDelete = async (item: FollowUpItem) => {
    const confirmed = await confirmDelete({
        title: 'Hapus Tindak Lanjut?',
        text: `Apakah Anda yakin ingin menghapus tindak lanjut "${item.title}" untuk siswa ${item.student?.name || 'Siswa'}? Tindakan ini tidak dapat dibatalkan.`,
        confirmButtonText: 'Ya, Hapus Tindak Lanjut',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/follow-ups/${item.id}`, {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateString?: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Tindak Lanjut Siswa - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="hover:text-gray-900 transition-colors font-medium">Penanganan Siswa</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Tindak Lanjut</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Tindak Lanjut Siswa"
                description="Kelola rencana aksi penanganan siswa dan pantau status penyelesaiannya secara komprehensif."
                badge="Tindak Lanjut & Intervensi"
                badge-icon="pi pi-check-circle"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Tambah Tindak Lanjut</span>
                    </button>
                </template>
            </AppPageHeader>

            <!-- Table & Filter Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                <!-- Status Tabs with Counter Badges -->
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 pb-4">
                    <button
                        type="button"
                        @click="setTab('all')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5"
                        :class="[
                            activeTab === 'all'
                                ? 'bg-[#001AFF] text-white shadow-xs'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >
                        <span>Semua</span>
                        <span
                            class="px-1.5 py-0.2 rounded-full text-[10px]"
                            :class="activeTab === 'all' ? 'bg-white/20 text-white font-bold' : 'bg-gray-200 text-gray-700'"
                        >
                            {{ counts.all }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('pending')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5"
                        :class="[
                            activeTab === 'pending'
                                ? 'bg-amber-600 text-white shadow-xs'
                                : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                        ]"
                    >
                        <span>Menunggu</span>
                        <span
                            class="px-1.5 py-0.2 rounded-full text-[10px]"
                            :class="activeTab === 'pending' ? 'bg-white/20 text-white font-bold' : 'bg-amber-100 text-amber-800'"
                        >
                            {{ counts.pending }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('in_progress')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5"
                        :class="[
                            activeTab === 'in_progress'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'
                        ]"
                    >
                        <span>Sedang Berjalan</span>
                        <span
                            class="px-1.5 py-0.2 rounded-full text-[10px]"
                            :class="activeTab === 'in_progress' ? 'bg-white/20 text-white font-bold' : 'bg-indigo-100 text-indigo-800'"
                        >
                            {{ counts.in_progress }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('overdue')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5"
                        :class="[
                            activeTab === 'overdue'
                                ? 'bg-red-600 text-white shadow-xs'
                                : 'bg-red-50 text-red-600 hover:bg-red-100'
                        ]"
                    >
                        <span>Terlambat</span>
                        <span
                            class="px-1.5 py-0.2 rounded-full text-[10px]"
                            :class="activeTab === 'overdue' ? 'bg-white/20 text-white font-bold' : 'bg-red-100 text-red-800'"
                        >
                            {{ counts.overdue }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('completed')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5"
                        :class="[
                            activeTab === 'completed'
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                        ]"
                    >
                        <span>Selesai</span>
                        <span
                            class="px-1.5 py-0.2 rounded-full text-[10px]"
                            :class="activeTab === 'completed' ? 'bg-white/20 text-white font-bold' : 'bg-emerald-100 text-emerald-800'"
                        >
                            {{ counts.completed }}
                        </span>
                    </button>
                </div>

                <!-- Standardized AppTableToolbar -->
                <AppTableToolbar
                    v-model="search"
                    search-placeholder="Cari judul, aksi, nama siswa, atau NIS..."
                    search-width="w-full sm:w-80"
                    :total-records="counts.all"
                    :filtered-records="followUps.data.length"
                    record-label="tindak lanjut"
                    :has-active-filters="hasActiveFilters"
                    @reset="resetFilters"
                >
                    <template #filters>
                        <!-- Filter: Siswa -->
                        <div class="w-full sm:w-56">
                            <select
                                v-model="studentId"
                                @change="applyFilters"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Semua Siswa</option>
                                <option v-for="s in students" :key="s.id" :value="s.id">
                                    {{ s.name }} (NIS: {{ s.nis }})
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
                <div v-if="followUps.data && followUps.data.length > 0" class="overflow-x-auto">
                    <DataTable
                        :value="followUps.data"
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
                        <!-- Kolom 1: Tenggat Waktu -->
                        <Column field="due_date" header="Tenggat Waktu" sortable class="py-3 px-4 text-xs font-medium text-gray-700 w-36">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-1.5">
                                        <i class="pi pi-calendar text-gray-400 text-xs"></i>
                                        <span class="font-bold text-gray-900 text-xs">{{ formatDate(data.due_date) }}</span>
                                    </div>
                                    <span
                                        v-if="data.is_overdue && data.status !== 'completed' && data.status !== 'cancelled'"
                                        class="inline-flex items-center gap-1 text-[10px] font-semibold text-red-600 mt-1"
                                    >
                                        <i class="pi pi-exclamation-circle text-[10px]"></i>
                                        <span>Melewati Tenggat</span>
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 2: Siswa & Kelas -->
                        <Column field="student.name" header="Siswa" sortable class="py-3 px-4 font-semibold text-gray-900 min-w-[200px]">
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

                        <!-- Kolom 3: Judul & Rencana Aksi -->
                        <Column field="title" header="Judul & Rencana Aksi" sortable class="py-3 px-4 min-w-[220px] max-w-sm">
                            <template #body="{ data }">
                                <div class="font-bold text-gray-900 text-xs">
                                    {{ data.title }}
                                </div>
                                <div v-if="data.action_plan" class="text-xs text-gray-500 mt-0.5 line-clamp-2">
                                    {{ data.action_plan }}
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 4: Penanggung Jawab -->
                        <Column field="assigned_to_user.name" header="Penanggung Jawab" class="py-3 px-4 text-xs font-medium text-gray-600 w-44">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1.5 text-xs text-gray-700">
                                    <i class="pi pi-user text-[11px] text-gray-400"></i>
                                    <span class="truncate">{{ data.assigned_to_user?.name || 'Belum Ditugaskan' }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 5: Status -->
                        <Column field="status" header="Status" sortable class="py-3 px-4 w-36">
                            <template #body="{ data }">
                                <AppStatusBadge
                                    v-if="data.is_overdue && data.status !== 'completed' && data.status !== 'cancelled'"
                                    status="OVERDUE"
                                    label="Terlambat"
                                    size="sm"
                                />
                                <AppStatusBadge
                                    v-else
                                    :status="data.status"
                                    size="sm"
                                />
                            </template>
                        </Column>

                        <!-- Kolom 6: Hasil / Evaluasi -->
                        <Column header="Hasil Evaluasi" class="py-3 px-4 min-w-[180px] max-w-xs">
                            <template #body="{ data }">
                                <div v-if="data.outcome" class="text-xs text-gray-800 line-clamp-2 font-medium">
                                    {{ data.outcome }}
                                </div>
                                <div v-else class="text-xs text-gray-400 italic">
                                    Belum ada evaluasi
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 7: Aksi -->
                        <Column header="Aksi" class="py-3 px-4 text-right w-28">
                            <template #body="{ data }">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEditModal(data)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Perbarui Hasil & Status"
                                    >
                                        <i class="pi pi-pencil text-[10px]"></i>
                                        <span>Edit</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="promptDelete(data)"
                                        class="px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Hapus Tindak Lanjut"
                                    >
                                        <i class="pi pi-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Server-side Pagination Bar -->
                    <div v-if="followUps.links && followUps.links.length > 3" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ followUps.from || 0 }}</strong> sampai <strong>{{ followUps.to || 0 }}</strong> dari <strong>{{ followUps.total || followUps.data.length }}</strong> data tindak lanjut
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            <Link
                                v-for="(link, index) in followUps.links"
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
                    icon="pi pi-check-square"
                    title="Belum Ada Tindak Lanjut"
                    description="Tindak lanjut yang dibuat untuk menangani pelanggaran atau proses pembinaan siswa akan tercatat di sini."
                    action-label="Buat Tindak Lanjut Baru"
                    @action="openCreateModal"
                />
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PRIME VUE DIALOG: MODAL TAMBAH TINDAK LANJUT -->
        <!-- ========================================================================= -->
        <Dialog
            v-model:visible="isCreateModalOpen"
            modal
            header="Tambah Tindak Lanjut Baru"
            :style="{ width: '92vw', maxWidth: '580px' }"
            :closable="true"
            class="p-dialog-custom"
        >
            <form @submit.prevent="submitCreate" class="space-y-4 pt-2">
                <!-- Siswa * -->
                <div>
                    <label for="create_student_id" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Pilih Siswa <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="create_student_id"
                        v-model="createForm.student_id"
                        required
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        :class="{ 'border-rose-400 ring-rose-200': createForm.errors.student_id }"
                    >
                        <option value="" disabled>Pilih Siswa...</option>
                        <option v-for="s in students" :key="s.id" :value="s.id">
                            {{ s.nis }} - {{ s.name }}
                        </option>
                    </select>
                    <p v-if="createForm.errors.student_id" class="text-xs font-medium text-rose-600 mt-1">
                        {{ createForm.errors.student_id }}
                    </p>
                </div>

                <!-- Judul * -->
                <div>
                    <label for="create_title" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Judul Tindak Lanjut <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="create_title"
                        v-model="createForm.title"
                        type="text"
                        required
                        placeholder="Contoh: Pemanggilan Orang Tua & Pembinaan Wali Kelas"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        :class="{ 'border-rose-400 ring-rose-200': createForm.errors.title }"
                    />
                    <p v-if="createForm.errors.title" class="text-xs font-medium text-rose-600 mt-1">
                        {{ createForm.errors.title }}
                    </p>
                </div>

                <!-- Rencana Aksi -->
                <div>
                    <label for="create_action_plan" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Rencana Aksi
                    </label>
                    <textarea
                        id="create_action_plan"
                        v-model="createForm.action_plan"
                        rows="3"
                        placeholder="Tuliskan langkah-langkah konkret yang akan dilaksanakan..."
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all resize-y"
                    ></textarea>
                </div>

                <!-- Tenggat Waktu & Status Awal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="create_due_date" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Tenggat Waktu
                        </label>
                        <input
                            id="create_due_date"
                            v-model="createForm.due_date"
                            type="date"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                        />
                    </div>

                    <div>
                        <label for="create_status" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Status Awal
                        </label>
                        <select
                            id="create_status"
                            v-model="createForm.status"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        >
                            <option value="pending">Menunggu</option>
                            <option value="in_progress">Sedang Berjalan</option>
                            <option value="completed">Selesai</option>
                        </select>
                    </div>
                </div>

                <!-- Penanggung Jawab -->
                <div>
                    <label for="create_assigned_to" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Penanggung Jawab (Opsional)
                    </label>
                    <select
                        id="create_assigned_to"
                        v-model="createForm.assigned_to"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    >
                        <option value="">Pilih Petugas / Guru...</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <i v-if="createForm.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-check text-xs"></i>
                        <span>Simpan Tindak Lanjut</span>
                    </button>
                </div>
            </form>
        </Dialog>

        <!-- ========================================================================= -->
        <!-- PRIME VUE DIALOG: MODAL EDIT / UPDATE OUTCOME -->
        <!-- ========================================================================= -->
        <Dialog
            v-model:visible="isEditModalOpen"
            modal
            header="Perbarui Progres & Hasil Tindak Lanjut"
            :style="{ width: '92vw', maxWidth: '580px' }"
            :closable="true"
            class="p-dialog-custom"
        >
            <form @submit.prevent="submitEdit" class="space-y-4 pt-2">
                <!-- Judul -->
                <div>
                    <label for="edit_title" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Judul Tindak Lanjut <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="edit_title"
                        v-model="editForm.title"
                        type="text"
                        required
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    />
                </div>

                <!-- Rencana Aksi -->
                <div>
                    <label for="edit_action_plan" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Rencana Aksi
                    </label>
                    <textarea
                        id="edit_action_plan"
                        v-model="editForm.action_plan"
                        rows="2"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all resize-y"
                    ></textarea>
                </div>

                <!-- Tenggat Waktu & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_due_date" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Tenggat Waktu
                        </label>
                        <input
                            id="edit_due_date"
                            v-model="editForm.due_date"
                            type="date"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                        />
                    </div>

                    <div>
                        <label for="edit_status" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Status Penanganan
                        </label>
                        <select
                            id="edit_status"
                            v-model="editForm.status"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        >
                            <option value="pending">Menunggu</option>
                            <option value="in_progress">Sedang Berjalan</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <!-- Penanggung Jawab -->
                <div>
                    <label for="edit_assigned_to" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Penanggung Jawab
                    </label>
                    <select
                        id="edit_assigned_to"
                        v-model="editForm.assigned_to"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    >
                        <option value="">Pilih Petugas / Guru...</option>
                        <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                    </select>
                </div>

                <!-- Hasil Evaluasi / Outcome -->
                <div>
                    <label for="edit_outcome" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Hasil Evaluasi / Catatan Penyelesaian
                    </label>
                    <textarea
                        id="edit_outcome"
                        v-model="editForm.outcome"
                        rows="3"
                        placeholder="Tuliskan perkembangan perilaku siswa, catatan evaluasi, atau hasil kesepakatan..."
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all resize-y"
                    ></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <i v-if="editForm.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-check text-xs"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
