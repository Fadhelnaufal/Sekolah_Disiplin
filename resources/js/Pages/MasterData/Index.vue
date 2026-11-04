<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Swal from 'sweetalert2';

const props = defineProps<{
    violations: Array<{
        id: number;
        code: string;
        name: string;
        category: string;
        points: number;
        is_active: boolean;
    }>;
    achievements: Array<{
        id: number;
        code: string;
        name: string;
        level: string;
        deduction_points: number;
        is_active: boolean;
    }>;
    sanctionRules: Array<{
        id: number;
        level: number;
        min_points: number;
        max_points: number | null;
        action: string;
        executor: string;
        is_active: boolean;
    }>;
    current_tab?: string;
}>();

const page = usePage();
const activeTab = ref(props.current_tab || 'violations');

const setTab = (tab: string) => {
    activeTab.value = tab;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    }
};

const userRoles = computed<string[]>(() => {
    return (page.props.auth as any)?.roles || [];
});

const canManage = computed(() => {
    return userRoles.value.includes('Super Admin') || userRoles.value.includes('Tata Tertib') || userRoles.value.includes('BK');
});

const deleteViolation = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus pelanggaran ini? Jika pelanggaran sudah digunakan pada catatan kedisiplinan, sistem akan menolak penghapusan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/violations/${id}`);
        }
    });
};

const deleteAchievement = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus prestasi ini? Jika prestasi sudah digunakan pada catatan kedisiplinan, sistem akan menolak penghapusan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/achievements/${id}`);
        }
    });
};

const deleteSanctionRule = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus aturan sanksi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/sanction-rules/${id}`);
        }
    });
};

// Filters for Violations
const violationSearch = ref('');
const violationCategory = ref('');

const violationCategories = computed(() => {
    const set = new Set(props.violations.map(v => v.category).filter(Boolean));
    return Array.from(set).sort();
});

const filteredViolations = computed(() => {
    return props.violations.filter(v => {
        const query = violationSearch.value.toLowerCase().trim();
        const matchesQuery = !query ||
            v.code.toLowerCase().includes(query) ||
            v.name.toLowerCase().includes(query) ||
            v.category.toLowerCase().includes(query);
        const matchesCategory = !violationCategory.value || v.category === violationCategory.value;
        return matchesQuery && matchesCategory;
    });
});

// Filters for Achievements
const achievementSearch = ref('');
const achievementLevel = ref('');

const achievementLevels = computed(() => {
    const set = new Set(props.achievements.map(a => a.level).filter(Boolean));
    return Array.from(set).sort();
});

const filteredAchievements = computed(() => {
    return props.achievements.filter(a => {
        const query = achievementSearch.value.toLowerCase().trim();
        const matchesQuery = !query ||
            a.code.toLowerCase().includes(query) ||
            a.name.toLowerCase().includes(query) ||
            a.level.toLowerCase().includes(query);
        const matchesLevel = !achievementLevel.value || a.level === achievementLevel.value;
        return matchesQuery && matchesLevel;
    });
});

// Filters for Sanction Rules
const sanctionSearch = ref('');

const filteredSanctionRules = computed(() => {
    return props.sanctionRules.filter(s => {
        const query = sanctionSearch.value.toLowerCase().trim();
        if (!query) return true;
        return String(s.level).includes(query) ||
            s.action.toLowerCase().includes(query) ||
            s.executor.toLowerCase().includes(query) ||
            String(s.min_points).includes(query) ||
            (s.max_points !== null && String(s.max_points).includes(query));
    });
});
</script>

<template>
    <Head title="Master Data - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Master Data</h1>
                        <span
                            v-if="!canManage"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-[#6366F1] border border-indigo-100"
                        >
                            Katalog Referensi (Mode Baca)
                        </span>
                    </div>
                    <p class="text-gray-500 text-[15px] mt-0.5">
                        {{ canManage ? 'Kelola indeks pelanggaran, prestasi siswa, dan tingkatan sanksi disiplin.' : 'Katalog referensi poin pelanggaran tata tertib, capaian prestasi, dan tingkatan sanksi siswa.' }}
                    </p>
                </div>
                <div v-if="canManage" class="flex items-center gap-2">
                    <Link
                        v-if="activeTab === 'violations'"
                        href="/violations/create"
                        class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        Tambah Pelanggaran
                    </Link>
                    <Link
                        v-if="activeTab === 'achievements'"
                        href="/achievements/create"
                        class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        Tambah Prestasi
                    </Link>
                    <Link
                        v-if="activeTab === 'sanctions'"
                        href="/sanction-rules/create"
                        class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm flex items-center gap-1.5"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        Tambah Aturan Sanksi
                    </Link>
                </div>
            </div>

            <!-- Tab Switcher Card -->
            <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] space-y-6">
                <!-- Navigation Tabs -->
                <div class="flex items-center gap-2 border-b border-gray-100 pb-4 overflow-x-auto [scrollbar-width:none]">
                    <button
                        @click="setTab('violations')"
                        class="px-5 py-2 rounded-full text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                        :class="activeTab === 'violations' ? 'bg-[#6366F1] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    >
                        <i class="pi pi-exclamation-triangle text-xs"></i>
                        Master Pelanggaran ({{ violations.length }})
                    </button>
                    <button
                        @click="setTab('achievements')"
                        class="px-5 py-2 rounded-full text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                        :class="activeTab === 'achievements' ? 'bg-[#6366F1] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    >
                        <i class="pi pi-star text-xs"></i>
                        Master Prestasi ({{ achievements.length }})
                    </button>
                    <button
                        @click="setTab('sanctions')"
                        class="px-5 py-2 rounded-full text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                        :class="activeTab === 'sanctions' ? 'bg-[#6366F1] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    >
                        <i class="pi pi-shield text-xs"></i>
                        Aturan Sanksi ({{ sanctionRules.length }})
                    </button>
                </div>

                <!-- Tab 1: Pelanggaran (Interactive DataTable) -->
                <div v-if="activeTab === 'violations'" class="space-y-4">
                    <!-- DataTable Filter Toolbar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <div class="relative w-full sm:w-72">
                                <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                                <input
                                    v-model="violationSearch"
                                    type="text"
                                    placeholder="Cari kode, nama, atau kategori..."
                                    class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                />
                            </div>

                            <div class="w-full sm:w-48">
                                <select
                                    v-model="violationCategory"
                                    class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                                >
                                    <option value="">Semua Kategori</option>
                                    <option v-for="cat in violationCategories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>

                            <button
                                v-if="violationSearch || violationCategory"
                                @click="violationSearch = ''; violationCategory = '';"
                                class="text-xs text-gray-500 hover:text-gray-900 underline px-2 py-1 cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>

                        <div class="text-xs font-medium text-gray-400">
                            Menampilkan <span class="text-gray-900 font-bold">{{ filteredViolations.length }}</span> dari {{ violations.length }} pelanggaran
                        </div>
                    </div>

                    <!-- DataTable Component -->
                    <DataTable
                        :value="filteredViolations"
                        :paginator="filteredViolations.length > 10"
                        :rows="10"
                        :rowsPerPageOptions="[10, 25, 50]"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} data"
                        responsiveLayout="stack"
                        breakpoint="768px"
                        class="p-datatable-sm"
                        removableSort
                        emptyMessage="Tidak ada data pelanggaran yang sesuai dengan filter pencarian."
                    >
                        <Column field="code" header="Kode" sortable class="py-3.5 pl-2 font-mono text-xs font-bold text-gray-700 w-28">
                           
                        </Column>

                        <Column field="name" header="Nama Pelanggaran" sortable class="py-3.5 font-semibold text-gray-900">
                            <template #body="{ data }">
                                <span class="font-bold text-gray-900 text-sm">{{ data.name }}</span>
                            </template>
                        </Column>

                        <Column field="category" header="Kategori" sortable class="py-3.5 text-xs text-gray-500 w-44">
                         
                            <template #body="{ data }">
                                <span class="text-gray-600 text-xs font-medium">{{ data.category }}</span>
                            </template>
                        </Column>

                        <Column field="points" header="Poin" sortable class="py-3.5 text-center w-28">
                            
                            <template #body="{ data }">
                                <div class="text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-red-50 text-red-600">
                                        +{{ data.points }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column field="is_active" header="Status" sortable class="py-3.5 text-center w-28">
                           
                            <template #body="{ data }">
                                <div class="text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold" :class="data.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                        {{ data.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column v-if="canManage" header="Aksi" class="py-3.5 text-center w-28">
                            <template #body="{ data }">
                                <div class="flex items-center justify-center gap-2">
                                    <Link :href="`/violations/${data.id}/edit`" class="text-xs text-[#6366F1] font-semibold hover:underline">
                                        Edit
                                    </Link>
                                    <span class="text-gray-300">|</span>
                                    <button @click="deleteViolation(data.id)" class="text-xs text-red-600 font-semibold hover:underline cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Tab 2: Prestasi (Interactive DataTable) -->
                <div v-if="activeTab === 'achievements'" class="space-y-4">
                    <!-- DataTable Filter Toolbar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <div class="relative w-full sm:w-72">
                                <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                                <input
                                    v-model="achievementSearch"
                                    type="text"
                                    placeholder="Cari kode, nama, atau tingkat..."
                                    class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                />
                            </div>

                            <div class="w-full sm:w-48">
                                <select
                                    v-model="achievementLevel"
                                    class="w-full px-3.5 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                                >
                                    <option value="">Semua Tingkat</option>
                                    <option v-for="lvl in achievementLevels" :key="lvl" :value="lvl">{{ lvl }}</option>
                                </select>
                            </div>

                            <button
                                v-if="achievementSearch || achievementLevel"
                                @click="achievementSearch = ''; achievementLevel = '';"
                                class="text-xs text-gray-500 hover:text-gray-900 underline px-2 py-1 cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>

                        <div class="text-xs font-medium text-gray-400">
                            Menampilkan <span class="text-gray-900 font-bold">{{ filteredAchievements.length }}</span> dari {{ achievements.length }} prestasi
                        </div>
                    </div>

                    <!-- DataTable Component -->
                    <DataTable
                        :value="filteredAchievements"
                        :paginator="filteredAchievements.length > 10"
                        :rows="10"
                        :rowsPerPageOptions="[10, 25, 50]"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} data"
                        responsiveLayout="stack"
                        breakpoint="768px"
                        class="p-datatable-sm"
                        removableSort
                        emptyMessage="Tidak ada data prestasi yang sesuai dengan filter pencarian."
                    >
                        <Column field="code" header="Kode" sortable class="py-3.5 pl-2 font-mono text-xs font-bold text-gray-700 w-28">
                          
                        </Column>

                        <Column field="name" header="Nama Prestasi" sortable class="py-3.5 font-semibold text-gray-900">
                          
                            <template #body="{ data }">
                                <span class="font-bold text-gray-900 text-sm">{{ data.name }}</span>
                            </template>
                        </Column>

                        <Column field="level" header="Tingkat" sortable class="py-3.5 text-xs text-gray-500 w-44">
                          
                            <template #body="{ data }">
                                <span class="text-gray-600 text-xs font-medium">{{ data.level }}</span>
                            </template>
                        </Column>

                        <Column field="deduction_points" header="Pengurang Poin" sortable class="py-3.5 text-center w-36">
                           
                            <template #body="{ data }">
                                <div class="text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-green-50 text-green-600">
                                        -{{ data.deduction_points }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column field="is_active" header="Status" sortable class="py-3.5 text-center w-28">
                          
                            <template #body="{ data }">
                                <div class="text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold" :class="data.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                        {{ data.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column v-if="canManage" header="Aksi" class="py-3.5 text-center w-28">
                            <template #body="{ data }">
                                <div class="flex items-center justify-center gap-2">
                                    <Link :href="`/achievements/${data.id}/edit`" class="text-xs text-[#6366F1] font-semibold hover:underline">
                                        Edit
                                    </Link>
                                    <span class="text-gray-300">|</span>
                                    <button @click="deleteAchievement(data.id)" class="text-xs text-red-600 font-semibold hover:underline cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Tab 3: Aturan Sanksi (Interactive DataTable) -->
                <div v-if="activeTab === 'sanctions'" class="space-y-4">
                    <!-- DataTable Filter Toolbar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <div class="relative w-full sm:w-80">
                                <i class="pi pi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                                <input
                                    v-model="sanctionSearch"
                                    type="text"
                                    placeholder="Cari sanksi, eksekutor, atau tingkat..."
                                    class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                                />
                            </div>

                            <button
                                v-if="sanctionSearch"
                                @click="sanctionSearch = '';"
                                class="text-xs text-gray-500 hover:text-gray-900 underline px-2 py-1 cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>

                        <div class="text-xs font-medium text-gray-400">
                            Menampilkan <span class="text-gray-900 font-bold">{{ filteredSanctionRules.length }}</span> dari {{ sanctionRules.length }} tingkatan sanksi
                        </div>
                    </div>

                    <!-- DataTable Component -->
                    <DataTable
                        :value="filteredSanctionRules"
                        :paginator="filteredSanctionRules.length > 10"
                        :rows="10"
                        :rowsPerPageOptions="[10, 25, 50]"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} data"
                        responsiveLayout="stack"
                        breakpoint="768px"
                        class="p-datatable-sm"
                        removableSort
                        emptyMessage="Tidak ada aturan sanksi yang sesuai dengan filter pencarian."
                    >
                        <Column field="level" header="Tingkat" sortable class="py-3.5 pl-2 font-bold text-gray-900 w-28">
                            
                            <template #body="{ data }">
                                <span class="font-bold text-gray-900">Tingkat {{ data.level }}</span>
                            </template>
                        </Column>

                        <Column field="min_points" header="Rentang Poin" sortable class="py-3.5 text-xs text-gray-700 font-medium w-48">
                            
                            <template #body="{ data }">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                                    {{ data.min_points }} s/d {{ data.max_points ? data.max_points : 'ke atas' }} poin
                                </span>
                            </template>
                        </Column>

                        <Column field="action" header="Tindakan / Sanksi" sortable class="py-3.5 font-medium text-gray-800">
                           
                            <template #body="{ data }">
                                <span class="font-medium text-gray-900 text-sm">{{ data.action }}</span>
                            </template>
                        </Column>

                        <Column field="executor" header="Eksekutor" sortable class="py-3.5 text-xs text-gray-500 w-48">
                          
                            <template #body="{ data }">
                                <span class="text-gray-600 text-xs font-medium">{{ data.executor }}</span>
                            </template>
                        </Column>

                        <Column field="is_active" header="Status" sortable class="py-3.5 text-center w-28">
                           
                            <template #body="{ data }">
                                <div class="text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold" :class="data.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                        {{ data.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column v-if="canManage" header="Aksi" class="py-3.5 text-center w-28">
                            <template #body="{ data }">
                                <div class="flex items-center justify-center gap-2">
                                    <Link :href="`/sanction-rules/${data.id}/edit`" class="text-xs text-[#6366F1] font-semibold hover:underline">
                                        Edit
                                    </Link>
                                    <span class="text-gray-300">|</span>
                                    <button @click="deleteSanctionRule(data.id)" class="text-xs text-red-600 font-semibold hover:underline cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
