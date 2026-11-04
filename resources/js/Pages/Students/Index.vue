<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import Dialog from "primevue/dialog";

import AuthenticatedLayout from "../../Layouts/AuthenticatedLayout.vue";

const props = defineProps<{
    students: {
        data: Array<{
            id: number;
            nis: string;
            name: string;
            status: string;
            class_room: {
                id: number;
                name: string;
            } | null;
            net_points?: number;
            risk_status?: string;
            deleted_at?: string | null;
        }>;
        links: any[];
    };
    classRooms: Array<{
        id: number;
        name: string;
    }>;
    filters: {
        search?: string;
        class_room_id?: string | number;
        tab?: string;
    };
    hasActiveSemester: boolean;
    active_count: number;
    draft_count: number;
    current_tab: string;
}>();

const confirm = useConfirm();
const page = usePage();
const userRoles = computed<string[]>(
    () => (page.props.auth as any)?.roles || [],
);
const canManageStudents = computed(
    () =>
        userRoles.value.includes("Super Admin") ||
        userRoles.value.includes("Tata Tertib"),
);

const search = ref(props.filters.search || "");
const classRoomId = ref(
    props.filters.class_room_id ? Number(props.filters.class_room_id) : "",
);
const currentTab = ref(props.current_tab || "active");

const selectedStudentIds = ref<number[]>([]);
const selectAll = ref(false);

const isRenameModalOpen = ref(false);
const renameForm = useForm({
    class_room_id: "",
    name: "",
});

function debounce(fn: Function, delay: number) {
    let timeoutId: ReturnType<typeof setTimeout>;
    return (...args: any[]) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
}

const applyFilters = () => {
    router.get(
        "/students",
        {
            search: search.value || undefined,
            class_room_id: classRoomId.value || undefined,
            tab: currentTab.value === "active" ? undefined : currentTab.value,
        },
        { preserveState: true, replace: true },
    );
};

watch(
    [search, classRoomId],
    debounce(() => {
        applyFilters();
    }, 300),
);

const toggleTab = (tabName: string) => {
    currentTab.value = tabName;
    selectedStudentIds.value = [];
    selectAll.value = false;
    applyFilters();
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedStudentIds.value = props.students.data.map((s) => s.id);
    } else {
        selectedStudentIds.value = [];
    }
};

const moveToDraft = (student: any) => {
    confirm.require({
        message: `Pindahkan siswa "${student.name}" ke Draft Hapus? Selama di Draft Hapus, data dan riwayat siswa tetap aman.`,
        header: "Pindahkan ke Draft Hapus",
        icon: "pi pi-info-circle",
        acceptProps: { severity: "warn" },
        accept: () => {
            router.delete(`/students/${student.id}`);
        },
    });
};

const restoreStudent = (student: any) => {
    router.post(`/students/${student.id}/restore`);
};

const forceDeleteStudent = (student: any) => {
    confirm.require({
        message: `PERINGATAN: Apakah Anda yakin ingin menghapus siswa "${student.name}" secara PERMANEN? Tindakan ini tidak dapat dibatalkan.`,
        header: "Konfirmasi Hapus Permanen",
        icon: "pi pi-exclamation-triangle",
        acceptProps: { severity: "danger" },
        accept: () => {
            router.delete(`/students/${student.id}/force`);
        },
    });
};

const bulkDraftSelected = () => {
    if (selectedStudentIds.value.length === 0) return;
    confirm.require({
        message: `Pindahkan ${selectedStudentIds.value.length} siswa yang dipilih ke Draft Hapus?`,
        header: "Pindahkan ke Draft",
        icon: "pi pi-info-circle",
        acceptProps: { severity: "warn" },
        accept: () => {
            router.post(
                "/students/bulk-draft",
                {
                    student_ids: selectedStudentIds.value,
                },
                {
                    onSuccess: () => {
                        selectedStudentIds.value = [];
                        selectAll.value = false;
                    },
                },
            );
        },
    });
};

const bulkRestoreSelected = () => {
    if (selectedStudentIds.value.length === 0) return;
    router.post(
        "/students/bulk-restore",
        {
            student_ids: selectedStudentIds.value,
        },
        {
            onSuccess: () => {
                selectedStudentIds.value = [];
                selectAll.value = false;
            },
        },
    );
};

const allToDraft = () => {
    const allIds = props.students.data.map((s) => s.id);
    if (allIds.length === 0) return;
    confirm.require({
        message: `Pindahkan seluruh ${allIds.length} siswa di halaman ini ke Draft Hapus?`,
        header: "Pindahkan Semua ke Draft",
        icon: "pi pi-exclamation-triangle",
        acceptProps: { severity: "warn" },
        accept: () => {
            router.post("/students/bulk-draft", { student_ids: allIds });
        },
    });
};

const openRenameModal = () => {
    renameForm.reset();
    isRenameModalOpen.value = true;
};

const submitRenameClass = () => {
    renameForm.post("/students/update-class-name", {
        onSuccess: () => {
            isRenameModalOpen.value = false;
        },
    });
};

// UI: Menggunakan warna solid yang kontras dan proporsional
const getStatusBadgeClass = (status?: string) => {
    switch (status) {
        case "NORMAL":
            return "bg-emerald-500 text-white";
        case "ATTENTION":
            return "bg-amber-500 text-white";
        case "RAPID_INCREASE":
            return "bg-orange-500 text-white";
        case "HIGH_THRESHOLD":
            return "bg-red-600 text-white";
        default:
            return "bg-gray-500 text-white";
    }
};

const getStatusLabel = (status?: string) => {
    switch (status) {
        case "NORMAL":
            return "Aman";
        case "ATTENTION":
            return "Perhatian";
        case "RAPID_INCREASE":
            return "Waspada";
        case "HIGH_THRESHOLD":
            return "Kritis";
        default:
            return "Normal";
    }
};
</script>

<template>
    <Head title="Data Siswa - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Page Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight"
                    >
                        Data Siswa
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">
                        Kelola seluruh siswa atau tampilkan data per kelas tanpa
                        mengubah data yang tersimpan.
                    </p>
                </div>
            </div>

            <!-- Draft Hapus Aman Banner (Admin & Tatib Only) -->
            <div
                v-if="canManageStudents"
                class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-violet-600 to-blue-600 rounded-2xl p-6 md:p-8 shadow-xl shadow-violet-900/20 border border-white/10 flex flex-col xl:flex-row xl:items-center justify-between gap-8"
            >
                <!-- Dekorasi Background (Glow/Pattern opsional) -->
                <div
                    class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"
                ></div>

                <div class="space-y-4 max-w-2xl relative z-10">
                    <!-- Badge -->
                    <div
                        class="inline-flex items-center gap-2 text-blue-100 font-bold text-xs uppercase tracking-widest bg-white/10 border border-white/20 px-3 py-1.5 rounded-full backdrop-blur-sm"
                    >
                        <i class="pi pi-shield"></i>
                        Draft Hapus Aman
                    </div>

                    <!-- Teks -->
                    <div class="space-y-1.5">
                        <h3
                            class="text-xl md:text-2xl font-bold text-white tracking-tight"
                        >
                            Hapus bertahap supaya data tidak salah terhapus
                        </h3>
                        <p
                            class="text-sm text-blue-100/80 leading-relaxed max-w-xl"
                        >
                            Siswa dari daftar aktif harus masuk Draft Hapus
                            terlebih dahulu. Selama masih di Draft Hapus, siswa
                            dapat dikembalikan dan seluruh riwayat tetap aman.
                        </p>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button
                            v-if="currentTab === 'active'"
                            @click="bulkDraftSelected"
                            :disabled="selectedStudentIds.length === 0"
                            class="px-4 py-2 rounded-lg bg-white/10 border border-white/20 text-sm font-semibold text-white hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2 transition-all backdrop-blur-sm"
                        >
                            <i class="pi pi-inbox text-xs opacity-70"></i>
                            Pilih ke Draft ({{ selectedStudentIds.length }})
                        </button>

                        <button
                            v-if="currentTab === 'draft'"
                            @click="bulkRestoreSelected"
                            :disabled="selectedStudentIds.length === 0"
                            class="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2 transition-all shadow-sm"
                        >
                            <i class="pi pi-refresh text-xs"></i>
                            Pulihkan Pilihan ({{ selectedStudentIds.length }})
                        </button>

                        <button
                            @click="
                                toggleTab(
                                    currentTab === 'active'
                                        ? 'draft'
                                        : 'active',
                                )
                            "
                            class="px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition-all shadow-sm border"
                            :class="
                                currentTab === 'draft'
                                    ? 'bg-white text-violet-700 border-white hover:bg-blue-50'
                                    : 'bg-transparent border-white/20 text-white hover:bg-white/10 backdrop-blur-sm'
                            "
                        >
                            <i class="pi pi-trash text-xs opacity-70"></i>
                            {{
                                currentTab === "draft"
                                    ? "Lihat Siswa Aktif"
                                    : "Draft Hapus"
                            }}
                        </button>

                        <button
                            v-if="currentTab === 'active'"
                            @click="allToDraft"
                            class="px-4 py-2 rounded-lg border border-red-400/50 bg-red-500/10 text-red-200 text-sm font-semibold hover:bg-red-500/20 hover:text-white flex items-center gap-2 transition-all backdrop-blur-sm"
                        >
                            <i class="pi pi-users text-xs opacity-70"></i>
                            Semua ke Draft
                        </button>
                    </div>
                </div>

                <!-- Statistik Kanan (Glassmorphism) -->
                <div
                    class="flex items-center justify-center sm:justify-start gap-6 shrink-0 bg-white/10 border border-white/20 rounded-2xl p-5 backdrop-blur-md relative z-10 w-full md:w-auto"
                >
                    <div
                        class="text-center px-4 border-r border-white/20 w-1/2 md:w-auto"
                    >
                        <div class="text-3xl font-bold text-white">
                            {{ active_count }}
                        </div>
                        <div
                            class="text-[11px] font-bold text-blue-200 uppercase tracking-widest mt-1"
                        >
                            Siswa Aktif
                        </div>
                    </div>
                    <div class="text-center px-4 w-1/2 md:w-auto">
                        <div class="text-3xl font-bold text-white">
                            {{ draft_count }}
                        </div>
                        <div
                            class="text-[11px] font-bold text-blue-200 uppercase tracking-widest mt-1"
                        >
                            Draft Hapus
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Students Card -->
            <div
                class="bg-white rounded-2xl p-5 md:p-7 shadow-sm border border-gray-100 space-y-6"
            >
                <!-- Card Header with Action Buttons -->
                <div
                    class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-5"
                >
                    <div>
                        <h2 class="text-lg md:text-xl font-bold text-gray-900">
                            {{
                                currentTab === "draft"
                                    ? "Daftar Siswa (Draft Hapus)"
                                    : "Data Siswa"
                            }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{
                                currentTab === "draft"
                                    ? "Siswa yang dihapus sementara. Anda dapat memulihkannya atau menghapus permanen."
                                    : "Kelola data siswa, edit identitas, atau cetak kartu disiplin."
                            }}
                        </p>
                    </div>

                    <div
                        v-if="canManageStudents"
                        class="flex flex-wrap items-center gap-3"
                    >
                        <button
                            @click="openRenameModal"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-2"
                        >
                            <i class="pi pi-pencil text-xs"></i>
                            Ubah Nama Kelas
                        </button>
                        <a
                            href="/students/template"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-2"
                        >
                            <i class="pi pi-download text-xs"></i>
                            Download Template
                        </a>
                        <Link
                            href="/imports"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all flex items-center gap-2"
                        >
                            <i class="pi pi-upload text-xs"></i>
                            Import Excel
                        </Link>
                        <Link
                            href="/students/create"
                            class="px-5 py-2 rounded-lg bg-[#6366F1] hover:bg-[#4F46E5] text-white text-sm font-semibold transition-all shadow-sm flex items-center gap-2"
                        >
                            <i class="pi pi-plus text-xs"></i>
                            Tambah Siswa
                        </Link>
                    </div>
                </div>

                <!-- Filters & Search Toolbar -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div
                        class="flex flex-wrap items-center gap-3 w-full sm:w-auto"
                    >
                        <!-- Search Input -->
                        <div class="relative w-full sm:w-72">
                            <i
                                class="pi pi-search absolute left-3.5 top-2.5 text-gray-400 text-sm"
                            ></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama atau NIS..."
                                class="w-full pl-10 pr-4 py-2 text-sm rounded-lg bg-gray-50 border border-gray-300 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                        </div>

                        <!-- Class Dropdown -->
                        <div class="relative w-full sm:w-48">
                            <select
                                v-model="classRoomId"
                                class="w-full h-10 pl-4 pr-10 text-sm rounded-lg bg-gray-50 border border-gray-300 text-gray-800 appearance-none focus:outline-none focus:ring-2 focus:ring-[#6366F1] cursor-pointer"
                            >
                                <option value="">Semua kelas</option>
                                <option
                                    v-for="cls in classRooms"
                                    :key="cls.id"
                                    :value="cls.id"
                                >
                                    {{ cls.name }}
                                </option>
                            </select>
                            <!-- Ikon kustom PrimeIcons -->
                            <i
                                class="pi pi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs pointer-events-none"
                            ></i>
                        </div>
                    </div>

                    <!-- Tab Switcher (Semua / Draft Hapus - Admin & Tatib Only) -->
                    <div
                        v-if="canManageStudents"
                        class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg text-sm font-semibold border border-gray-200"
                    >
                        <button
                            @click="toggleTab('active')"
                            class="px-4 py-1.5 rounded-md transition-all"
                            :class="
                                currentTab === 'active'
                                    ? 'bg-white text-gray-900 shadow-sm border border-gray-200'
                                    : 'text-gray-500 hover:text-gray-900'
                            "
                        >
                            Aktif ({{ active_count }})
                        </button>
                        <button
                            @click="toggleTab('draft')"
                            class="px-4 py-1.5 rounded-md transition-all"
                            :class="
                                currentTab === 'draft'
                                    ? 'bg-white text-red-600 shadow-sm border border-red-100'
                                    : 'text-gray-500 hover:text-gray-900'
                            "
                        >
                            Draft Hapus ({{ draft_count }})
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div v-if="students.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50/50"
                            >
                                <th
                                    v-if="canManageStudents"
                                    class="py-3 pl-4 w-12 rounded-tl-lg"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        @change="toggleSelectAll"
                                        class="rounded border-gray-300 text-[#6366F1] focus:ring-[#6366F1] w-4 h-4"
                                    />
                                </th>
                                <th
                                    class="py-3 px-4"
                                    :class="
                                        !canManageStudents
                                            ? 'rounded-tl-lg'
                                            : ''
                                    "
                                >
                                    NIS
                                </th>
                                <th class="py-3 px-4">Siswa</th>
                                <th class="py-3 px-4">Kelas</th>
                                <th class="py-3 px-4">Orang Tua</th>
                                <th class="py-3 px-4 text-center">
                                    Poin Bersih
                                </th>
                                <th class="py-3 px-4 text-center">
                                    Status Disiplin
                                </th>
                                <th class="py-3 px-4 text-center rounded-tr-lg">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr
                                v-for="student in students.data"
                                :key="student.id"
                                class="hover:bg-gray-50/80 transition-colors group"
                            >
                                <td v-if="canManageStudents" class="py-3 pl-4">
                                    <input
                                        type="checkbox"
                                        :value="student.id"
                                        v-model="selectedStudentIds"
                                        class="rounded border-gray-300 text-[#6366F1] focus:ring-[#6366F1] w-4 h-4"
                                    />
                                </td>
                                <td
                                    class="py-3 px-4 font-mono text-sm text-gray-600"
                                >
                                    {{ student.nis }}
                                </td>
                                <td class="py-3 px-4">
                                    <Link
                                        :href="`/students/${student.id}`"
                                        class="font-bold text-gray-900 hover:text-indigo-600 transition-colors text-base"
                                    >
                                        {{ student.name }}
                                    </Link>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Status: {{ student.status }}
                                    </div>
                                </td>
                                <td
                                    class="py-3 px-4 font-semibold text-gray-700"
                                >
                                    {{
                                        student.class_room?.name ||
                                        "Tanpa Kelas"
                                    }}
                                </td>
                                <td class="py-3 px-4 text-gray-700">
                                    <div class="font-semibold">
                                        {{
                                            student.parent_name ||
                                            "Belum Dicantumkan"
                                        }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{
                                            student.parent_phone ||
                                            "Nomor Tidak Terdaftar"
                                        }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="text-sm font-semibold">
                                        {{ student.net_points ?? 0 }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        :class="
                                            getStatusBadgeClass(
                                                student.risk_status,
                                            )
                                        "
                                        class="inline-flex px-3 py-1 rounded-md text-xs font-bold shadow-sm tracking-wide"
                                    >
                                        {{
                                            getStatusLabel(student.risk_status)
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="py-3 px-4 text-center whitespace-nowrap"
                                >
                                    <div
                                        class="inline-flex items-center gap-2 justify-center w-full"
                                    >
                                        <!-- Active Tab Actions -->
                                        <template
                                            v-if="currentTab === 'active'"
                                        >
                                            <Link
                                                :href="`/students/${student.id}`"
                                                v-tooltip.top="'Lihat Detail'"
                                                class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors border border-transparent hover:border-gray-300"
                                            >
                                                <i
                                                    class="pi pi-eye text-sm"
                                                ></i>
                                            </Link>
                                            <Link
                                                v-if="canManageStudents"
                                                :href="`/students/${student.id}/edit`"
                                                v-tooltip.top="'Edit Siswa'"
                                                class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors border border-transparent hover:border-gray-300"
                                            >
                                                <i
                                                    class="pi pi-pencil text-sm"
                                                ></i>
                                            </Link>
                                            <Link
                                                :href="`/students/${student.id}/print-card`"
                                                v-tooltip.top="'Cetak Kartu'"
                                                class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors border border-transparent hover:border-gray-300"
                                            >
                                                <i
                                                    class="pi pi-print text-sm"
                                                ></i>
                                            </Link>
                                            <button
                                                v-if="canManageStudents"
                                                @click="moveToDraft(student)"
                                                v-tooltip.top="'Hapus ke Draft'"
                                                class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition-colors border border-transparent hover:border-red-200"
                                            >
                                                <i
                                                    class="pi pi-trash text-sm"
                                                ></i>
                                            </button>
                                        </template>

                                        <!-- Draft Tab Actions -->
                                        <template v-else>
                                            <button
                                                @click="restoreStudent(student)"
                                                class="px-4 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold flex items-center gap-2 transition-colors shadow-sm"
                                            >
                                                <i
                                                    class="pi pi-refresh text-xs"
                                                ></i>
                                                Pulihkan
                                            </button>
                                            <button
                                                @click="
                                                    forceDeleteStudent(student)
                                                "
                                                class="px-4 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold flex items-center gap-2 transition-colors shadow-sm"
                                            >
                                                <i
                                                    class="pi pi-times text-xs"
                                                ></i>
                                                Hapus Permanen
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        class="mt-6 flex justify-between items-center text-sm text-gray-500 border-t border-gray-100 pt-4"
                    >
                        <span
                            >Menampilkan {{ students.data.length }} siswa dari
                            total data.</span
                        >
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="py-16 text-center border-2 border-dashed border-gray-200 rounded-2xl"
                >
                    <div
                        class="w-16 h-16 mx-auto mb-4 rounded-full bg-indigo-50 text-[#6366F1] flex items-center justify-center border border-indigo-100"
                    >
                        <i class="pi pi-users text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">
                        {{
                            currentTab === "draft"
                                ? "Draft Hapus Kosong"
                                : "Belum ada data siswa"
                        }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
                        {{
                            currentTab === "draft"
                                ? "Tidak ada data siswa yang berada dalam tahap draft hapus."
                                : "Data siswa yang ditambahkan atau diimpor dari Excel akan muncul di sini."
                        }}
                    </p>
                    <div
                        v-if="currentTab === 'active' && canManageStudents"
                        class="mt-6 flex justify-center gap-3"
                    >
                        <Link
                            href="/students/create"
                            class="px-5 py-2.5 rounded-lg bg-[#6366F1] text-white text-sm font-semibold hover:bg-[#4F46E5] transition-all shadow-sm"
                        >
                            + Tambah Siswa Baru
                        </Link>
                        <Link
                            href="/imports"
                            class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-all"
                        >
                            Import dari Excel
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Modal Ubah Nama Kelas -->
            <Dialog
                v-model:visible="isRenameModalOpen"
                modal
                header="Ubah Nama Kelas"
                :style="{ width: '480px' }"
            >
                <form
                    @submit.prevent="submitRenameClass"
                    class="space-y-5 pt-2"
                >
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-1.5"
                            >Pilih Kelas yang Ingin Diubah *</label
                        >
                        <select
                            v-model="renameForm.class_room_id"
                            required
                            class="w-full text-sm rounded-lg bg-gray-50 border border-gray-300 px-4 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        >
                            <option value="" disabled>Pilih Kelas...</option>
                            <option
                                v-for="cls in classRooms"
                                :key="cls.id"
                                :value="cls.id"
                            >
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-1.5"
                            >Nama Kelas Baru *</label
                        >
                        <input
                            v-model="renameForm.name"
                            type="text"
                            required
                            placeholder="Contoh: 7 Unggulan A"
                            class="w-full text-sm rounded-lg bg-gray-50 border border-gray-300 px-4 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                        />
                    </div>

                    <div
                        class="flex justify-end gap-3 pt-4 border-t border-gray-100"
                    >
                        <button
                            type="button"
                            @click="isRenameModalOpen = false"
                            class="px-5 py-2.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="renameForm.processing"
                            class="px-5 py-2.5 rounded-lg bg-[#6366F1] text-white text-sm font-semibold hover:bg-[#4F46E5] transition-all shadow-sm"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>
