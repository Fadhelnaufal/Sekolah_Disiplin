<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import Dialog from 'primevue/dialog';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps<{
    followUps: {
        data: Array<{
            id: number;
            title: string;
            action_plan: string | null;
            due_date: string | null;
            status: string;
            outcome: string | null;
            is_overdue: boolean;
            student: {
                id: number;
                name: string;
                nis: string;
                class_room?: { name: string };
            };
            assigned_to_user?: { id: number; name: string };
            assigned_to?: number;
        }>;
        links: any[];
    };
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

const confirm = useConfirm();
const search = ref(props.filters.search || '');
const activeTab = ref(props.filters.status || 'all');

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedFollowUp = ref<any>(null);

const createForm = useForm({
    student_id: '',
    title: '',
    action_plan: '',
    due_date: '',
    status: 'pending',
    assigned_to: '',
});

const editForm = useForm({
    title: '',
    action_plan: '',
    due_date: '',
    status: 'pending',
    outcome: '',
    assigned_to: '',
});

const setTab = (status: string) => {
    activeTab.value = status;
    router.get('/follow-ups', {
        search: search.value || undefined,
        status: status === 'all' ? undefined : status,
    }, { preserveState: true, replace: true });
};

const handleSearch = () => {
    router.get('/follow-ups', {
        search: search.value || undefined,
        status: activeTab.value === 'all' ? undefined : activeTab.value,
    }, { preserveState: true, replace: true });
};

const openCreateModal = () => {
    createForm.reset();
    isCreateModalOpen.value = true;
};

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

const submitCreate = () => {
    createForm.post('/follow-ups', {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (item: any) => {
    selectedFollowUp.value = item;
    editForm.title = item.title;
    editForm.action_plan = item.action_plan || '';
    editForm.due_date = item.due_date ? item.due_date.substring(0, 10) : '';
    editForm.status = item.status;
    editForm.outcome = item.outcome || '';
    editForm.assigned_to = item.assigned_to || '';
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!selectedFollowUp.value) return;
    editForm.put(`/follow-ups/${selectedFollowUp.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};

const deleteFollowUp = (id: number) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menghapus tindak lanjut ini?',
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: { severity: 'danger' },
        accept: () => {
            router.delete(`/follow-ups/${id}`);
        },
    });
};

const getStatusBadge = (status: string, isOverdue: boolean) => {
    if (isOverdue && status !== 'completed' && status !== 'cancelled') {
        return { label: 'Terlambat', class: 'bg-red-50 text-red-600 border border-red-200' };
    }
    switch (status) {
        case 'pending': return { label: 'Menunggu', class: 'bg-yellow-50 text-yellow-700 border border-yellow-200' };
        case 'in_progress': return { label: 'Sedang Berjalan', class: 'bg-blue-50 text-blue-700 border border-blue-200' };
        case 'completed': return { label: 'Selesai', class: 'bg-green-50 text-green-700 border border-green-200' };
        case 'cancelled': return { label: 'Dibatalkan', class: 'bg-gray-100 text-gray-600 border border-gray-200' };
        default: return { label: status, class: 'bg-gray-50 text-gray-600 border border-gray-200' };
    }
};
</script>

<template>
    <Head title="Tindak Lanjut - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Tindak Lanjut</h1>
                    <p class="text-gray-500 text-[15px] mt-1">Kelola rencana aksi penanganan siswa dan pantau status penyelesaiannya.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-sm font-semibold transition-all shadow-[0_4px_14px_#6366F140]"
                >
                    <i class="pi pi-plus text-xs"></i>
                    Tambah Tindak Lanjut
                </button>
            </div>

            <!-- Status Tabs & Filter -->
            <div class="bg-white rounded-[24px] p-6 shadow-[0_2px_20px_#00000006] space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            @click="setTab('all')"
                            class="px-4 py-2 rounded-full text-xs font-semibold transition-all"
                            :class="activeTab === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                            Semua ({{ counts.all }})
                        </button>
                        <button
                            @click="setTab('pending')"
                            class="px-4 py-2 rounded-full text-xs font-semibold transition-all"
                            :class="activeTab === 'pending' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100'"
                        >
                            Menunggu ({{ counts.pending }})
                        </button>
                        <button
                            @click="setTab('in_progress')"
                            class="px-4 py-2 rounded-full text-xs font-semibold transition-all"
                            :class="activeTab === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                        >
                            Sedang Berjalan ({{ counts.in_progress }})
                        </button>
                        <button
                            @click="setTab('overdue')"
                            class="px-4 py-2 rounded-full text-xs font-semibold transition-all"
                            :class="activeTab === 'overdue' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600 hover:bg-red-100'"
                        >
                            Terlambat ({{ counts.overdue }})
                        </button>
                        <button
                            @click="setTab('completed')"
                            class="px-4 py-2 rounded-full text-xs font-semibold transition-all"
                            :class="activeTab === 'completed' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100'"
                        >
                            Selesai ({{ counts.completed }})
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <div class="relative w-full sm:w-64">
                            <i class="pi pi-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                            <input
                                v-model="search"
                                @keyup.enter="handleSearch"
                                type="text"
                                placeholder="Cari nama, judul, atau aksi..."
                                class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Table Content -->
                <div v-if="followUps.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="pb-3 pl-2">Siswa</th>
                                <th class="pb-3">Judul & Rencana Aksi</th>
                                <th class="pb-3">Tenggat Waktu</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">Hasil / Catatan</th>
                                <th class="pb-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr v-for="item in followUps.data" :key="item.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4 pl-2">
                                    <div class="font-bold text-gray-900">{{ item.student?.name }}</div>
                                    <div class="text-xs text-gray-400">NIS: {{ item.student?.nis }} • {{ item.student?.class_room?.name || 'Tanpa Kelas' }}</div>
                                </td>
                                <td class="py-4 max-w-xs">
                                    <div class="font-semibold text-gray-900">{{ item.title }}</div>
                                    <div v-if="item.action_plan" class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ item.action_plan }}</div>
                                </td>
                                <td class="py-4 whitespace-nowrap">
                                    <div v-if="item.due_date" class="text-xs font-medium text-gray-700">
                                        {{ new Date(item.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </div>
                                    <div v-else class="text-xs text-gray-400">-</div>
                                </td>
                                <td class="py-4 whitespace-nowrap">
                                    <span
                                        :class="getStatusBadge(item.status, item.is_overdue).class"
                                        class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider"
                                    >
                                        {{ getStatusBadge(item.status, item.is_overdue).label }}
                                    </span>
                                </td>
                                <td class="py-4 max-w-xs">
                                    <div v-if="item.outcome" class="text-xs text-gray-600 line-clamp-2">
                                        {{ item.outcome }}
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">Belum ada evaluasi</div>
                                </td>
                                <td class="py-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            @click="openEditModal(item)"
                                            title="Update Status / Hasil"
                                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors"
                                        >
                                            <i class="pi pi-pencil text-xs"></i>
                                        </button>
                                        <button
                                            @click="deleteFollowUp(item.id)"
                                            title="Hapus"
                                            class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition-colors"
                                        >
                                            <i class="pi pi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-6 flex justify-between items-center text-xs text-gray-500 border-t border-gray-100 pt-4">
                        <span>Menampilkan {{ followUps.data.length }} data tindak lanjut</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-16 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 text-[#6366F1] flex items-center justify-center shadow-sm">
                        <i class="pi pi-check-square text-2xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Belum ada tindak lanjut</h3>
                    <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                        Tindak lanjut yang dibuat untuk menangani pelanggaran atau proses pembinaan siswa akan tercatat di sini.
                    </p>
                    <button
                        @click="openCreateModal"
                        class="mt-4 px-4 py-2 rounded-full bg-[#6366F1] text-white text-xs font-semibold hover:bg-[#4F46E5] transition-all"
                    >
                        Buat Tindak Lanjut Baru
                    </button>
                </div>
            </div>

            <!-- Create Modal -->
            <Dialog v-model:visible="isCreateModalOpen" modal header="Tambah Tindak Lanjut" :style="{ width: '560px' }">
                <form @submit.prevent="submitCreate" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Pilih Siswa *</label>
                        <select
                            v-model="createForm.student_id"
                            required
                            class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                            <option value="" disabled>Pilih Siswa...</option>
                            <option v-for="s in students" :key="s.id" :value="s.id">
                                {{ s.nis }} - {{ s.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Judul Tindak Lanjut *</label>
                        <input
                            v-model="createForm.title"
                            type="text"
                            required
                            placeholder="Contoh: Pemanggilan Orang Tua & Pembinaan Wali Kelas"
                            class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Rencana Aksi</label>
                        <textarea
                            v-model="createForm.action_plan"
                            rows="3"
                            placeholder="Tuliskan langkah-langkah yang akan dilaksanakan..."
                            class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tenggat Waktu</label>
                            <input
                                v-model="createForm.due_date"
                                type="date"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Status Awal</label>
                            <select
                                v-model="createForm.status"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            >
                                <option value="pending">Menunggu</option>
                                <option value="in_progress">Sedang Berjalan</option>
                                <option value="completed">Selesai</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Penanggung Jawab (Opsional)</label>
                        <select
                            v-model="createForm.assigned_to"
                            class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                            <option value="">Pilih Petugas / Guru...</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2 rounded-full bg-[#6366F1] text-white text-xs font-semibold hover:bg-[#4F46E5] transition-all shadow-sm"
                        >
                            Simpan Tindak Lanjut
                        </button>
                    </div>
                </form>
            </Dialog>

            <!-- Edit / Update Outcome Modal -->
            <Dialog v-model:visible="isEditModalOpen" modal header="Perbarui Progres & Hasil Tindak Lanjut" :style="{ width: '560px' }">
                <form @submit.prevent="submitEdit" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Judul Tindak Lanjut</label>
                        <input
                            v-model="editForm.title"
                            type="text"
                            required
                            class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Rencana Aksi</label>
                        <textarea
                            v-model="editForm.action_plan"
                            rows="2"
                            class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tenggat Waktu</label>
                            <input
                                v-model="editForm.due_date"
                                type="date"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Status Penanganan</label>
                            <select
                                v-model="editForm.status"
                                class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            >
                                <option value="pending">Menunggu</option>
                                <option value="in_progress">Sedang Berjalan</option>
                                <option value="completed">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Hasil / Catatan Penyelesaian (Outcome)</label>
                        <textarea
                            v-model="editForm.outcome"
                            rows="3"
                            placeholder="Tuliskan perkembangan perilaku siswa dan hasil evaluasi..."
                            class="w-full text-sm rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-4 py-2 rounded-full border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2 rounded-full bg-[#6366F1] text-white text-xs font-semibold hover:bg-[#4F46E5] transition-all shadow-sm"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </Dialog>

        </div>
    </AuthenticatedLayout>
</template>
