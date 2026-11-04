<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

interface UserItem {
    id: number;
    name: string;
    email: string;
    created_at: string;
    roles: Array<{ id: number; name: string }>;
}

const props = defineProps<{
    users: {
        data: UserItem[];
        links: any[];
        total: number;
        current_page: number;
        per_page: number;
    };
    filters: {
        search?: string;
        role?: string;
    };
    available_roles: string[];
}>();

const confirm = useConfirm();

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');

// Debounced search
let timeout: any = null;
watch([search, roleFilter], ([newSearch, newRole]) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/users', {
            search: newSearch || undefined,
            role: newRole || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
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
        onSuccess: () => {
            isResetModalOpen.value = false;
            resetForm.reset();
        },
    });
};

// Delete user
const deleteUser = (user: UserItem) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus akun pengguna "${user.name}"? Pengguna tidak akan dapat masuk lagi ke dalam sistem.`,
        header: 'Konfirmasi Hapus Pengguna',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: { severity: 'danger' },
        accept: () => {
            router.delete(`/users/${user.id}`);
        },
    });
};

const getRoleBadge = (roleName: string) => {
    switch (roleName) {
        case 'Super Admin':
            return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'Tata Tertib':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'BK':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'Principal':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
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
    <Head title="Manajemen Pengguna - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/settings" class="text-gray-500 hover:text-gray-900 transition-colors">Pengaturan</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Manajemen Pengguna</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Area -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Manajemen Pengguna</h1>
                    <p class="text-gray-500 text-[15px] mt-0.5">Kelola akun pengguna, penetapan peran akses, dan pemulihan kata sandi staf sekolah.</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer active:scale-95"
                >
                    <i class="pi pi-plus text-xs"></i>
                    <span>Tambah Pengguna</span>
                </button>
            </div>

            <!-- Card Container -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-5 border border-gray-100">
                <!-- Toolbar: Filter & Search -->
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div class="sm:col-span-2 relative">
                        <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama pengguna atau email..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>
                    <div>
                        <select
                            v-model="roleFilter"
                            class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                        >
                            <option value="">Semua Peran</option>
                            <option v-for="r in available_roles" :key="r" :value="r">
                                {{ r }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center justify-end text-xs text-gray-500 font-medium">
                        Total: {{ users.total }} akun
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3 text-left w-80">Pengguna</th>
                                <th class="pb-3 text-left w-48">Peran</th>
                                <th class="pb-3 text-left w-44">Terdaftar</th>
                                <th class="pb-3 text-right w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50/70 transition-colors">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-indigo-50 text-[#6366F1] font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-bold text-gray-900 text-xs truncate">{{ user.name }}</span>
                                            <span class="text-[11px] text-gray-400 font-mono truncate">{{ user.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <span
                                        v-for="r in user.roles"
                                        :key="r.id"
                                        :class="`px-2.5 py-1 text-[10px] font-bold uppercase rounded-full border tracking-wider inline-block ${getRoleBadge(r.name)}`"
                                    >
                                        {{ r.name }}
                                    </span>
                                </td>
                                <td class="py-4 text-xs text-gray-500">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit Button -->
                                        <button
                                            type="button"
                                            @click="openEditModal(user)"
                                            class="w-8 h-8 rounded-lg text-gray-600 hover:text-[#6366F1] hover:bg-indigo-50 flex items-center justify-center transition-colors cursor-pointer"
                                            title="Ubah Data Akun"
                                        >
                                            <i class="pi pi-user-edit text-xs"></i>
                                        </button>

                                        <!-- Reset Password Button -->
                                        <button
                                            type="button"
                                            @click="openResetModal(user)"
                                            class="w-8 h-8 rounded-lg text-amber-600 hover:text-amber-800 hover:bg-amber-50 flex items-center justify-center transition-colors cursor-pointer"
                                            title="Atur Ulang Kata Sandi"
                                        >
                                            <i class="pi pi-key text-xs"></i>
                                        </button>

                                        <!-- Delete Button (guarded) -->
                                        <button
                                            v-if="user.id !== ($page.props.auth as any)?.user?.id"
                                            type="button"
                                            @click="deleteUser(user)"
                                            class="w-8 h-8 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 flex items-center justify-center transition-colors cursor-pointer"
                                            title="Hapus Akun Pengguna"
                                        >
                                            <i class="pi pi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="4" class="py-12 text-center text-gray-500 text-xs">
                                    Tidak ada data pengguna yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="pt-4 border-t border-gray-100 flex items-center justify-center gap-1">
                    <Link
                        v-for="(link, index) in users.links"
                        :key="index"
                        :href="link.url || '#'"
                        class="min-w-[32px] h-8 flex items-center justify-center px-2 text-[13px] font-medium rounded-lg transition-colors"
                        :class="[
                            link.active ? 'bg-[#6366F1] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100',
                            !link.url && 'opacity-40 cursor-not-allowed hover:bg-transparent',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>
        </div>

        <!-- ================= MODAL TAMBAH PENGGUNA ================= -->
        <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
            <div class="bg-white rounded-[24px] max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Tambah Akun Pengguna</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Daftarkan akun staf baru untuk mengakses sistem.</p>
                    </div>
                    <button @click="isCreateModalOpen = false" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-gray-100">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            placeholder="Contoh: Budi Santoso, S.Pd."
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                        <p v-if="createForm.errors.name" class="text-red-500 text-[11px] mt-1">{{ createForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Email</label>
                        <input
                            v-model="createForm.email"
                            type="email"
                            required
                            placeholder="nama@sekolah.sch.id"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                        <p v-if="createForm.errors.email" class="text-red-500 text-[11px] mt-1">{{ createForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Peran / Role</label>
                        <select
                            v-model="createForm.role"
                            required
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                        >
                            <option v-for="r in available_roles" :key="r" :value="r">{{ r }}</option>
                        </select>
                        <p v-if="createForm.errors.role" class="text-red-500 text-[11px] mt-1">{{ createForm.errors.role }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kata Sandi</label>
                            <input
                                v-model="createForm.password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="Minimal 8 karakter"
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="createForm.errors.password" class="text-red-500 text-[11px] mt-1">{{ createForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
                            <input
                                v-model="createForm.password_confirmation"
                                type="password"
                                required
                                placeholder="Ulangi kata sandi"
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50 cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2 text-xs font-semibold rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white transition-all shadow-sm cursor-pointer disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan Pengguna' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL EDIT PENGGUNA ================= -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
            <div class="bg-white rounded-[24px] max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Ubah Data Pengguna</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Perbarui nama, email, dan peran akses pengguna.</p>
                    </div>
                    <button @click="isEditModalOpen = false" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-gray-100">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                        <p v-if="editForm.errors.name" class="text-red-500 text-[11px] mt-1">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Email</label>
                        <input
                            v-model="editForm.email"
                            type="email"
                            required
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                        <p v-if="editForm.errors.email" class="text-red-500 text-[11px] mt-1">{{ editForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Peran / Role</label>
                        <select
                            v-model="editForm.role"
                            required
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                        >
                            <option v-for="r in available_roles" :key="r" :value="r">{{ r }}</option>
                        </select>
                        <p v-if="editForm.errors.role" class="text-red-500 text-[11px] mt-1">{{ editForm.errors.role }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50 cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2 text-xs font-semibold rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white transition-all shadow-sm cursor-pointer disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL ATUR ULANG KATA SANDI ================= -->
        <div v-if="isResetModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
            <div class="bg-white rounded-[24px] max-w-md w-full p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Atur Ulang Kata Sandi</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Tentukan kata sandi baru untuk pengguna ini.</p>
                    </div>
                    <button @click="isResetModalOpen = false" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-gray-100">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                    <span class="font-bold">Perhatian:</span> Kata sandi untuk akun <span class="font-bold underline">{{ selectedUser?.name }}</span> akan segera diperbarui. Berikan kata sandi baru ini kepada pengguna bersangkutan.
                </div>

                <form @submit.prevent="submitReset" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kata Sandi Baru</label>
                        <input
                            v-model="resetForm.password"
                            type="password"
                            required
                            minlength="8"
                            placeholder="Minimal 8 karakter"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                        <p v-if="resetForm.errors.password" class="text-red-500 text-[11px] mt-1">{{ resetForm.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Ulangi Kata Sandi Baru</label>
                        <input
                            v-model="resetForm.password_confirmation"
                            type="password"
                            required
                            placeholder="Ketik ulang kata sandi baru"
                            class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="isResetModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50 cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="resetForm.processing"
                            class="px-5 py-2 text-xs font-semibold rounded-full bg-amber-600 hover:bg-amber-700 text-white transition-all shadow-sm cursor-pointer disabled:opacity-50"
                        >
                            {{ resetForm.processing ? 'Memproses...' : 'Terapkan Kata Sandi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

