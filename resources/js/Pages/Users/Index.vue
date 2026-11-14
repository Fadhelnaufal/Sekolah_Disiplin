<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppTableToolbar from '@/Components/AppTableToolbar.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { confirmDelete } from '@/utils';

interface UserItem {
    id: number;
    name: string;
    email: string;
    created_at: string;
    roles: Array<{ id: number; name: string }>;
}

interface PaginatedUsers {
    data: UserItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total?: number;
    from?: number;
    to?: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search?: string;
        role?: string;
        per_page?: string | number;
    };
    available_roles: string[];
}>();

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const perPage = ref(String(props.filters.per_page || props.users.per_page || '15'));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        roleFilter.value ||
        (perPage.value && perPage.value !== '15')
    );
});

const applyFilters = () => {
    router.get(
        '/users',
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
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
    roleFilter.value = '';
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

// Modals State
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isResetModalOpen = ref(false);
const selectedUser = ref<UserItem | null>(null);

// Create Form
const createForm = useForm({
    name: '',
    email: '',
    role: 'Teacher',
    password: '',
    password_confirmation: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.role = props.available_roles[0] || 'Teacher';
    isCreateModalOpen.value = true;
};

const submitCreate = () => {
    createForm.post('/users', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
};

// Edit Form
const editForm = useForm({
    name: '',
    email: '',
    role: '',
});

const openEditModal = (user: UserItem) => {
    selectedUser.value = user;
    editForm.clearErrors();
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.roles[0]?.name || 'Teacher';
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!selectedUser.value) return;
    editForm.put(`/users/${selectedUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};

// Reset Password Form
const resetForm = useForm({
    password: '',
    password_confirmation: '',
});

const openResetModal = (user: UserItem) => {
    selectedUser.value = user;
    resetForm.reset();
    resetForm.clearErrors();
    isResetModalOpen.value = true;
};

const submitReset = () => {
    if (!selectedUser.value) return;
    resetForm.post(`/users/${selectedUser.value.id}/reset-password`, {
        preserveScroll: true,
        onSuccess: () => {
            isResetModalOpen.value = false;
            resetForm.reset();
        },
    });
};

const promptDelete = async (user: UserItem) => {
    const confirmed = await confirmDelete({
        title: 'Hapus Akun Pengguna?',
        text: `Apakah Anda yakin ingin menghapus akun pengguna "${user.name}"? Pengguna tidak akan dapat masuk lagi ke dalam sistem.`,
        confirmButtonText: 'Ya, Hapus Akun',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/users/${user.id}`, {
            preserveScroll: true,
        });
    }
};

const getRoleBadge = (roleName: string) => {
    switch (roleName) {
        case 'Super Admin':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'Tata Tertib':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'BK':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'Principal':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
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
    <Head title="Manajemen Pengguna - DISIPLIN PRO" />

    <AuthenticatedLayout>
        <!-- Header slot with breadcrumbs -->
        <template #header>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <Link href="/settings" class="hover:text-gray-900 transition-colors">Pengaturan</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Manajemen Pengguna</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Standardized Page Header -->
            <AppPageHeader
                title="Manajemen Pengguna"
                description="Kelola akun pengguna, penetapan peran hak akses, dan pemulihan kata sandi staf sekolah."
                badge="Pengaturan Sistem"
                badge-icon="pi pi-shield"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Tambah Pengguna</span>
                    </button>
                </template>
            </AppPageHeader>

            <!-- Table & Filter Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5 space-y-4">
                <!-- Standardized AppTableToolbar -->
                <AppTableToolbar
                    v-model="search"
                    search-placeholder="Cari nama pengguna atau email..."
                    search-width="w-full sm:w-80"
                    :total-records="users.total"
                    :filtered-records="users.data.length"
                    record-label="pengguna"
                    :has-active-filters="hasActiveFilters"
                    @reset="resetFilters"
                >
                    <template #filters>
                        <!-- Filter: Role -->
                        <div class="w-full sm:w-44">
                            <select
                                v-model="roleFilter"
                                @change="applyFilters"
                                class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#001AFF] cursor-pointer"
                            >
                                <option value="">Semua Peran</option>
                                <option v-for="r in available_roles" :key="r" :value="r">
                                    {{ r }}
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
                <div v-if="users.data && users.data.length > 0" class="overflow-x-auto">
                    <DataTable
                        :value="users.data"
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
                        <!-- Kolom 1: Pengguna (Nama & Email) -->
                        <Column field="name" header="Pengguna" sortable class="py-3 px-4 font-semibold text-gray-900 min-w-[240px]">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100">
                                        {{ (data.name || 'U').charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-gray-900 text-xs truncate">
                                            {{ data.name }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-mono truncate mt-0.5">
                                            {{ data.email }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 2: Peran -->
                        <Column header="Peran Akses" class="py-3 px-4 w-48">
                            <template #body="{ data }">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="r in data.roles"
                                        :key="r.id"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border"
                                        :class="getRoleBadge(r.name)"
                                    >
                                        {{ r.name }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 3: Terdaftar Sejak -->
                        <Column field="created_at" header="Terdaftar" sortable class="py-3 px-4 text-xs font-medium text-gray-600 w-36">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1.5">
                                    <i class="pi pi-calendar text-[11px] text-gray-400"></i>
                                    <span>{{ formatDate(data.created_at) }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Kolom 4: Aksi -->
                        <Column header="Aksi" class="py-3 px-4 text-right w-44">
                            <template #body="{ data }">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openResetModal(data)"
                                        class="px-2 py-1 rounded-lg text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Reset Password"
                                    >
                                        <i class="pi pi-key text-[10px]"></i>
                                        <span>Reset</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="openEditModal(data)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Edit Pengguna"
                                    >
                                        <i class="pi pi-pencil text-[10px]"></i>
                                        <span>Edit</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="promptDelete(data)"
                                        class="px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Hapus Pengguna"
                                    >
                                        <i class="pi pi-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Server-side Pagination Bar -->
                    <div v-if="users.links && users.links.length > 3" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Menampilkan <strong>{{ users.from || 0 }}</strong> sampai <strong>{{ users.to || 0 }}</strong> dari <strong>{{ users.total || users.data.length }}</strong> akun pengguna
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            <Link
                                v-for="(link, index) in users.links"
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
                    icon="pi pi-user-plus"
                    title="Belum Ada Akun Pengguna"
                    description="Tambahkan pengguna baru untuk memberikan akses staf ke sistem DISIPLIN PRO."
                    action-label="Tambah Pengguna Baru"
                    @action="openCreateModal"
                />
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PRIME VUE DIALOG: MODAL TAMBAH PENGGUNA -->
        <!-- ========================================================================= -->
        <Dialog
            v-model:visible="isCreateModalOpen"
            modal
            header="Tambah Akun Pengguna"
            :style="{ width: '92vw', maxWidth: '540px' }"
            :closable="true"
            class="p-dialog-custom"
        >
            <form @submit.prevent="submitCreate" class="space-y-4 pt-2">
                <div>
                    <label for="create_name" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="create_name"
                        v-model="createForm.name"
                        type="text"
                        required
                        placeholder="Nama staf atau guru"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        :class="{ 'border-rose-400 ring-rose-200': createForm.errors.name }"
                    />
                    <p v-if="createForm.errors.name" class="text-xs font-medium text-rose-600 mt-1">
                        {{ createForm.errors.name }}
                    </p>
                </div>

                <div>
                    <label for="create_email" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="create_email"
                        v-model="createForm.email"
                        type="email"
                        required
                        placeholder="user@sekolah.sch.id"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                        :class="{ 'border-rose-400 ring-rose-200': createForm.errors.email }"
                    />
                    <p v-if="createForm.errors.email" class="text-xs font-medium text-rose-600 mt-1">
                        {{ createForm.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="create_role" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Peran Akses <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="create_role"
                        v-model="createForm.role"
                        required
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    >
                        <option v-for="r in available_roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="create_password" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="create_password"
                            v-model="createForm.password"
                            type="password"
                            required
                            placeholder="Minimal 8 karakter"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                            :class="{ 'border-rose-400 ring-rose-200': createForm.errors.password }"
                        />
                        <p v-if="createForm.errors.password" class="text-xs font-medium text-rose-600 mt-1">
                            {{ createForm.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label for="create_password_confirmation" class="block text-xs font-bold text-gray-900 mb-1.5">
                            Konfirmasi Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="create_password_confirmation"
                            v-model="createForm.password_confirmation"
                            type="password"
                            required
                            placeholder="Ulangi kata sandi"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                        />
                    </div>
                </div>

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
                        <span>Simpan Pengguna</span>
                    </button>
                </div>
            </form>
        </Dialog>

        <!-- ========================================================================= -->
        <!-- PRIME VUE DIALOG: MODAL EDIT PENGGUNA -->
        <!-- ========================================================================= -->
        <Dialog
            v-model:visible="isEditModalOpen"
            modal
            header="Edit Akun Pengguna"
            :style="{ width: '92vw', maxWidth: '540px' }"
            :closable="true"
            class="p-dialog-custom"
        >
            <form @submit.prevent="submitEdit" class="space-y-4 pt-2">
                <div>
                    <label for="edit_name" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="edit_name"
                        v-model="editForm.name"
                        type="text"
                        required
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    />
                </div>

                <div>
                    <label for="edit_email" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="edit_email"
                        v-model="editForm.email"
                        type="email"
                        required
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    />
                </div>

                <div>
                    <label for="edit_role" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Peran Akses <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="edit_role"
                        v-model="editForm.role"
                        required
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all font-medium"
                    >
                        <option v-for="r in available_roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>

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

        <!-- ========================================================================= -->
        <!-- PRIME VUE DIALOG: MODAL RESET PASSWORD -->
        <!-- ========================================================================= -->
        <Dialog
            v-model:visible="isResetModalOpen"
            modal
            :header="`Atur Ulang Kata Sandi: ${selectedUser?.name || 'Pengguna'}`"
            :style="{ width: '92vw', maxWidth: '480px' }"
            :closable="true"
            class="p-dialog-custom"
        >
            <form @submit.prevent="submitReset" class="space-y-4 pt-2">
                <div>
                    <label for="reset_password" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="reset_password"
                        v-model="resetForm.password"
                        type="password"
                        required
                        placeholder="Minimal 8 karakter"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                        :class="{ 'border-rose-400 ring-rose-200': resetForm.errors.password }"
                    />
                    <p v-if="resetForm.errors.password" class="text-xs font-medium text-rose-600 mt-1">
                        {{ resetForm.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="reset_password_confirmation" class="block text-xs font-bold text-gray-900 mb-1.5">
                        Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="reset_password_confirmation"
                        v-model="resetForm.password_confirmation"
                        type="password"
                        required
                        placeholder="Ulangi kata sandi baru"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] transition-all"
                    />
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="isResetModalOpen = false"
                        class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="resetForm.processing"
                        class="px-5 py-2 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <i v-if="resetForm.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-check text-xs"></i>
                        <span>Simpan Sandi Baru</span>
                    </button>
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
