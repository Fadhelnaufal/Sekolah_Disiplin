<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
}

const props = defineProps<{
    records: PaginatedRecords;
    students: StudentInfo[];
    can_create: boolean;
    filters: {
        category?: string;
        status?: string;
        search?: string;
    };
    categories: string[];
    statuses: string[];
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const schoolName = computed(() => page.props.school?.name || 'SMP Malang');

// Filter state
const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const currentStatus = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(
        '/counseling-records',
        {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
            status: currentStatus.value || undefined,
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

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingRecordId = ref<number | null>(null);

// Delete confirmation modal state
const isDeleteModalOpen = ref(false);
const recordToDelete = ref<CounselingRecordItem | null>(null);
const isDeleting = ref(false);

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
    
    // Format date string for input
    let dateVal = record.date_scheduled ? record.date_scheduled.substring(0, 10) : currentFormattedDate;
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
    // Extract YYYY-MM-DD from date_scheduled if time is included
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

const promptDelete = (record: CounselingRecordItem) => {
    recordToDelete.value = record;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    recordToDelete.value = null;
};

const confirmDelete = () => {
    if (!recordToDelete.value) return;
    isDeleting.value = true;
    router.delete(`/counseling-records/${recordToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
};

// Handle ESC key to close modal
const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        if (isModalOpen.value) closeModal();
        if (isDeleteModalOpen.value) closeDeleteModal();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'scheduled':
            return { label: 'Terjadwal', class: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'in_progress':
            return { label: 'Sedang Berjalan', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'resolved':
            return { label: 'Selesai', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'referred':
            return { label: 'Dialihkan', class: 'bg-purple-50 text-purple-700 border-purple-200' };
        case 'cancelled':
            return { label: 'Dibatalkan', class: 'bg-gray-100 text-gray-500 border-gray-200' };
        default:
            return { label: status, class: 'bg-gray-50 text-gray-700 border-gray-200' };
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
    <Head title="Pembinaan - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="hover:text-gray-900 transition-colors font-medium">Pembinaan</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ schoolName }}</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            
            <!-- Page Header (Matching screenshot background) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Pembinaan</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Data proses pembinaan siswa dan rekomendasi bimbingan konseling.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth?.roles?.includes('Super Admin') || $page.props.auth?.roles?.includes('Tata Tertib') || $page.props.auth?.roles?.includes('Teacher') || $page.props.auth?.roles?.includes('BK')"
                        href="/discipline-events/create"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-xs hidden md:inline-flex items-center gap-1.5"
                    >
                        <span>+ Catat Kejadian</span>
                    </Link>

                    <button
                        v-if="can_create || $page.props.auth?.roles?.includes('BK') || $page.props.auth?.roles?.includes('Super Admin')"
                        type="button"
                        @click="openCreateModal"
                        class="px-5 py-2.5 rounded-xl bg-[#6C47FF] hover:bg-[#5936EB] text-white text-sm font-semibold transition-all shadow-sm flex items-center gap-2 cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Pembinaan Baru</span>
                    </button>
                </div>
            </div>

            <!-- Filter Card & Status Tabs -->
            <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-5 space-y-4">
                
                <!-- Status Pills -->
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 pb-4">
                    <button
                        type="button"
                        @click="filterByStatus('')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                        :class="[
                            !currentStatus
                                ? 'bg-indigo-600 text-white shadow-xs'
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
                                ? 'bg-amber-600 text-white shadow-xs'
                                : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
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

                <!-- Search and Category Filters -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="md:col-span-8 relative">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari berdasarkan nama siswa atau NIS..."
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                        />
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="md:col-span-4">
                        <select
                            v-model="selectedCategory"
                            @change="applyFilters"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all font-medium"
                        >
                            <option value="">Semua Bentuk Pembinaan</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Table / List Card -->
            <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                
                <!-- If Records Exist -->
                <div v-if="records.data && records.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                                <th class="py-3.5 px-6">Tanggal</th>
                                <th class="py-3.5 px-6">Siswa</th>
                                <th class="py-3.5 px-6">Bentuk Pembinaan</th>
                                <th class="py-3.5 px-6">Catatan / Alasan</th>
                                <th class="py-3.5 px-6">Pencatat</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="record in records.data"
                                :key="record.id"
                                class="hover:bg-gray-50/60 transition-colors"
                            >
                                <td class="py-4 px-6 text-gray-900 font-medium whitespace-nowrap">
                                    {{ formatDate(record.date_scheduled) }}
                                </td>
                                
                                <td class="py-4 px-6">
                                    <div class="font-bold text-gray-900">
                                        {{ record.student?.name || '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        NIS: {{ record.student?.nis || '-' }} &bull; {{ record.student?.class_room?.name || 'Tanpa Kelas' }}
                                    </div>
                                </td>

                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                                        {{ record.category || 'Konseling' }}
                                    </span>
                                </td>

                                <td class="py-4 px-6 max-w-xs">
                                    <div v-if="record.summary" class="text-xs text-gray-800 font-medium line-clamp-2">
                                        <span class="font-bold text-gray-900">Hasil:</span> {{ record.summary }}
                                    </div>
                                    <div v-else-if="record.reason" class="text-xs text-gray-500 line-clamp-2">
                                        {{ record.reason }}
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Tidak ada catatan
                                    </div>
                                </td>

                                <td class="py-4 px-6 text-gray-600 text-xs font-medium whitespace-nowrap">
                                    {{ record.original_recorder_name || record.counselor?.name || 'Petugas BK' }}
                                </td>

                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold border uppercase tracking-wider"
                                        :class="getStatusBadge(record.status).class"
                                    >
                                        {{ getStatusBadge(record.status).label }}
                                    </span>
                                </td>

                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="currentUser?.id === record.counselor?.id || $page.props.auth?.roles?.includes('Super Admin')"
                                            type="button"
                                            @click="openEditModal(record)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 transition-colors cursor-pointer"
                                            title="Edit Catatan Pembinaan"
                                        >
                                            Edit
                                        </button>
                                        
                                        <button
                                            v-if="['scheduled', 'cancelled'].includes(record.status) && (currentUser?.id === record.counselor?.id || $page.props.auth?.roles?.includes('Super Admin'))"
                                            type="button"
                                            @click="promptDelete(record)"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 transition-colors cursor-pointer"
                                            title="Hapus Catatan"
                                        >
                                            Hapus
                                        </button>

                                        <Link
                                            :href="`/counseling-records/${record.id}`"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 transition-colors"
                                            title="Detail Lengkap"
                                        >
                                            Detail
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="records.links && records.links.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ records.from || 0 }}</strong> - <strong>{{ records.to || 0 }}</strong> dari <strong>{{ records.total }}</strong> sesi pembinaan
                        </div>
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="(link, index) in records.links"
                                :key="index"
                                :href="link.url || '#'"
                                class="min-w-[32px] h-8 flex items-center justify-center px-2 text-xs font-medium rounded-lg transition-colors"
                                :class="[
                                    link.active ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                    !link.url && 'opacity-40 cursor-not-allowed hover:bg-transparent'
                                ]"
                                v-html="link.label"
                                preserve-scroll
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty State (Screenshot 5) -->
                <div v-else class="py-16 px-6 text-center space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-3xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>

                    <div class="space-y-1 max-w-sm mx-auto">
                        <h3 class="text-base font-bold text-gray-900">Belum Ada Catatan Pembinaan</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Catat sesi pembinaan baru untuk mendampingi perkembangan kedisiplinan dan karakter siswa secara berkala.
                        </p>
                    </div>

                    <div v-if="can_create || $page.props.auth?.roles?.includes('BK') || $page.props.auth?.roles?.includes('Super Admin')" class="pt-2">
                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#6C47FF] hover:bg-[#5936EB] text-white text-xs font-semibold transition-all shadow-xs cursor-pointer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>+ Buat Sesi Pembinaan</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: PEMBINAAN BARU / EDIT PEMBINAAN (MATCHING SCREENSHOT REFERENSI) -->
        <!-- ========================================================================= -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            @click.self="closeModal"
        >
            <div class="relative w-full max-w-xl bg-white rounded-[24px] shadow-2xl p-6 sm:p-8 border border-gray-100 my-8 transition-all">
                
                <!-- Close Button (X in top right) -->
                <button
                    type="button"
                    @click="closeModal"
                    class="absolute top-6 right-6 p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors border border-gray-100 cursor-pointer"
                    title="Tutup"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Modal Header -->
                <div class="pr-8 mb-6">
                    <h2 class="text-xl font-bold tracking-tight text-gray-900">
                        {{ isEditing ? 'Edit Pembinaan' : 'Pembinaan Baru' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Catat sesi pembinaan dan pendampingan konseling siswa secara rahasia dan terstruktur.
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="space-y-4">
                    
                    <!-- Siswa * -->
                    <div>
                        <label for="modal_student_id" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Siswa <span class="text-rose-500">*</span>
                        </label>
                        <select
                            id="modal_student_id"
                            v-model="form.student_id"
                            required
                            class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all font-medium"
                            :class="{ 'border-rose-400 ring-rose-200': form.errors.student_id }"
                        >
                            <option value="" disabled>Pilih siswa...</option>
                            <option v-for="student in students" :key="student.id" :value="student.id">
                                {{ student.name }} (NIS: {{ student.nis }}) - {{ student.class_room?.name || 'Tanpa Kelas' }}
                            </option>
                        </select>
                        <p v-if="form.errors.student_id" class="text-xs font-medium text-rose-600 mt-1">
                            {{ form.errors.student_id }}
                        </p>
                    </div>

                    <!-- Bentuk Pembinaan & Tanggal (2-Columns on wide screen) -->
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
                                class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all font-medium"
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
                                class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
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
                            Alasan
                        </label>
                        <textarea
                            id="modal_reason"
                            v-model="form.reason"
                            rows="3"
                            placeholder="Alasan pelaksanaan pembinaan atau latar belakang masalah..."
                            class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all resize-y"
                        ></textarea>
                        <p v-if="form.errors.reason" class="text-xs font-medium text-rose-600 mt-1">
                            {{ form.errors.reason }}
                        </p>
                    </div>

                    <!-- Hasil / Catatan -->
                    <div>
                        <label for="modal_summary" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Hasil / Catatan
                        </label>
                        <textarea
                            id="modal_summary"
                            v-model="form.summary"
                            rows="3"
                            placeholder="Catatan hasil pembinaan, kesepakatan, atau solusi..."
                            class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all resize-y"
                        ></textarea>
                        <p v-if="form.errors.summary" class="text-xs font-medium text-rose-600 mt-1">
                            {{ form.errors.summary }}
                        </p>
                    </div>

                    <!-- Pencatat -->
                    <div>
                        <label for="modal_recorder" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Pencatat
                        </label>
                        <input
                            id="modal_recorder"
                            v-model="form.original_recorder_name"
                            type="text"
                            placeholder="Nama petugas / konselor"
                            class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                        />
                    </div>

                    <!-- Optional Status selector in Edit Mode -->
                    <div v-if="isEditing" class="pt-1">
                        <label for="modal_status" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Status Sesi
                        </label>
                        <select
                            id="modal_status"
                            v-model="form.status"
                            class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all font-medium"
                        >
                            <option value="scheduled">Terjadwal</option>
                            <option value="in_progress">Sedang Berjalan</option>
                            <option value="resolved">Selesai</option>
                            <option value="referred">Dialihkan</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Action Buttons (Matching Screenshot) -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-[#6C47FF] hover:bg-[#5936EB] text-white text-sm font-semibold transition-all shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- CONFIRMATION MODAL: DELETE RECORD -->
        <!-- ========================================================================= -->
        <div
            v-if="isDeleteModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            @click.self="closeDeleteModal"
        >
            <div class="relative w-full max-w-md bg-white rounded-[24px] shadow-2xl p-6 border border-gray-100 my-8 transition-all space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <div class="text-center space-y-1">
                    <h3 class="text-lg font-bold text-gray-900">Hapus Catatan Pembinaan?</h3>
                    <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">
                        Apakah Anda yakin ingin menghapus catatan pembinaan untuk siswa 
                        <strong class="text-gray-900">{{ recordToDelete?.student?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                        class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition-all shadow-xs flex items-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <svg v-if="isDeleting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Ya, Hapus Catatan</span>
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
