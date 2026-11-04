<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import { buildDisciplineEventWhatsAppMessage, buildWhatsAppUrl } from '../../utils';

interface StudentItem {
    id: number;
    name: string;
    nis: string;
    class_room_id?: number;
    parent_name?: string;
    parent_phone?: string;
    class_room?: { name: string };
}

interface ClassItem {
    id: number;
    name: string;
    grade_level: string;
}

interface ViolationItem {
    id: number;
    code: string;
    name: string;
    points: number;
}

interface AchievementItem {
    id: number;
    code: string;
    name: string;
    points: number;
}

interface RecentEventItem {
    id: number;
    event_type: string;
    points_effect: number;
    event_date: string;
    created_at: string;
    description?: string;
    original_recorder_name?: string;
    student?: { name: string; nis?: string; parent_name?: string; parent_phone?: string; class_room?: { name: string } };
    violation?: { name: string };
    achievement?: { name: string };
    recorded_by?: { name: string };
}

const props = defineProps<{
    students: StudentItem[];
    classes?: ClassItem[];
    violations: ViolationItem[];
    achievements: AchievementItem[];
    activeSemester: { id: number; name: string };
    defaultStudentId?: string | number;
    recentEvents?: RecentEventItem[];
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const userRoles = computed<string[]>(() => (page.props.auth as any)?.roles || []);
const canManageMasterData = computed(() => userRoles.value.includes('Super Admin') || userRoles.value.includes('Tata Tertib'));

const now = new Date();
const todayDate = now.toISOString().split('T')[0];
const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

// Mode Switcher: Satuan vs Massal
const entryMode = ref<'single' | 'bulk'>('single');

// Single Mode Student Filter
const studentSearch = ref('');
const filteredStudents = computed(() => {
    if (!studentSearch.value.trim()) {
        return props.students;
    }
    const query = studentSearch.value.toLowerCase();
    return props.students.filter(
        (s) =>
            s.name.toLowerCase().includes(query) ||
            s.nis.toLowerCase().includes(query) ||
            (s.class_room?.name && s.class_room.name.toLowerCase().includes(query))
    );
});

// Bulk Mode State
const selectedClassId = ref<string | number>('');
const bulkStudentSearch = ref('');
const selectedStudentIds = ref<number[]>(props.defaultStudentId ? [Number(props.defaultStudentId)] : []);

const bulkAvailableStudents = computed(() => {
    let list = props.students;
    if (selectedClassId.value) {
        list = list.filter((s) => s.class_room_id === Number(selectedClassId.value));
    }
    if (bulkStudentSearch.value.trim()) {
        const q = bulkStudentSearch.value.toLowerCase();
        list = list.filter(
            (s) =>
                s.name.toLowerCase().includes(q) ||
                s.nis.toLowerCase().includes(q) ||
                (s.class_room?.name && s.class_room.name.toLowerCase().includes(q))
        );
    }
    return list;
});

const isAllCurrentBulkSelected = computed(() => {
    if (bulkAvailableStudents.value.length === 0) return false;
    return bulkAvailableStudents.value.every((s) => selectedStudentIds.value.includes(s.id));
});

const toggleSelectAllBulk = () => {
    const currentIds = bulkAvailableStudents.value.map((s) => s.id);
    if (isAllCurrentBulkSelected.value) {
        selectedStudentIds.value = selectedStudentIds.value.filter((id) => !currentIds.includes(id));
    } else {
        const set = new Set([...selectedStudentIds.value, ...currentIds]);
        selectedStudentIds.value = Array.from(set);
    }
};

const toggleStudentSelection = (id: number) => {
    const idx = selectedStudentIds.value.indexOf(id);
    if (idx > -1) {
        selectedStudentIds.value.splice(idx, 1);
    } else {
        selectedStudentIds.value.push(id);
    }
};

const removeSelectedStudent = (id: number) => {
    selectedStudentIds.value = selectedStudentIds.value.filter((sid) => sid !== id);
};

const selectedStudentsObjects = computed(() => {
    return props.students.filter((s) => selectedStudentIds.value.includes(s.id));
});

// Attachment Image State
const attachmentFile = ref<File | null>(null);
const attachmentPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const onAttachmentSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        attachmentFile.value = file;
        attachmentPreview.value = URL.createObjectURL(file);
        form.attachment = file;
    }
};

const removeAttachment = () => {
    attachmentFile.value = null;
    attachmentPreview.value = null;
    form.attachment = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

// Form
const form = useForm({
    student_id: props.defaultStudentId || '',
    student_ids: [] as number[],
    event_date: todayDate,
    event_time: currentTime,
    event_type: 'violation',
    violation_id: '',
    achievement_id: '',
    original_recorder_name: currentUser.value?.name || '',
    description: '',
    follow_up: '',
    attachment: null as File | null,
});

const selectedViolation = computed(() => {
    if (!form.violation_id) return null;
    return props.violations.find((v) => v.id === Number(form.violation_id));
});

const selectedAchievement = computed(() => {
    if (!form.achievement_id) return null;
    return props.achievements.find((a) => a.id === Number(form.achievement_id));
});

const setEventType = (type: 'violation' | 'achievement') => {
    form.event_type = type;
    if (type === 'violation') {
        form.achievement_id = '';
    } else {
        form.violation_id = '';
        form.follow_up = '';
    }
};

const submit = () => {
    if (entryMode.value === 'bulk') {
        if (selectedStudentIds.value.length === 0) {
            alert('Silakan pilih minimal 1 siswa untuk pencatatan massal.');
            return;
        }
        form.student_ids = selectedStudentIds.value;
        form.student_id = '';
    } else {
        form.student_ids = [];
    }

    form.post('/discipline-events', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.description = '';
            form.follow_up = '';
            removeAttachment();
            if (entryMode.value === 'bulk') {
                selectedStudentIds.value = [];
            }
        },
    });
};

const selectedStudent = computed(() => {
    if (!form.student_id) return null;
    return props.students.find((s) => s.id === Number(form.student_id)) || null;
});

const sendWhatsAppRecentEvent = (item: RecentEventItem) => {
    const student = item.student;
    const phone = student?.parent_phone;
    const schoolName = (page.props.school as any)?.name || 'Sekolah';
    const eventName = item.violation?.name || item.achievement?.name || (item.event_type === 'violation' ? 'Pelanggaran' : 'Prestasi');

    const message = buildDisciplineEventWhatsAppMessage({
        schoolName,
        studentName: student?.name || 'Siswa',
        studentNis: student?.nis,
        className: student?.class_room?.name,
        eventType: item.event_type,
        eventName,
        eventDate: formatDate(item.event_date),
        points: item.points_effect,
        description: item.description,
        recorderName: item.original_recorder_name || item.recorded_by?.name,
    });

    if (!phone) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(message);
        }
        alert('Nomor WhatsApp orang tua belum terdaftar di profil siswa. Draf pesan telah disalin ke clipboard Anda.');
        window.open(buildWhatsAppUrl('', message), '_blank');
        return;
    }

    window.open(buildWhatsAppUrl(phone, message), '_blank');
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Catat Kejadian - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 text-sm font-medium">
                <Link href="/discipline-events" class="text-gray-500 hover:text-gray-900 transition-colors">
                    Pencatatan Kedisiplinan
                </Link>
                <span class="text-gray-300">/</span>
                <span class="font-semibold text-gray-900">Catat Kejadian Baru</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            
            <!-- Page Title Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Catat Kejadian Siswa</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Pencatatan pelanggaran tata tertib dan prestasi siswa untuk Semester Aktif: <strong class="text-indigo-600">{{ activeSemester.name }}</strong>.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        href="/discipline-events"
                        class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-xs"
                    >
                        Riwayat Kejadian
                    </Link>
                    <Link
                        href="/master-data"
                        class="px-4 py-2 text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs"
                    >
                        {{ canManageMasterData ? 'Master Aturan & Poin' : 'Katalog Aturan & Poin' }}
                    </Link>
                </div>
            </div>

            <!-- 2-Column Responsive Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Column: Incident Entry Form (8 Cols) -->
                <div class="lg:col-span-7 xl:col-span-8 bg-white border border-gray-100 rounded-[24px] shadow-sm p-6 sm:p-8 space-y-6">
                    
                    <!-- Mode Switcher: Satuan vs Massal -->
                    <div>
                        <div class="flex items-center justify-between p-1 rounded-2xl bg-gray-100/80 border border-gray-200">
                            <button
                                type="button"
                                @click="entryMode = 'single'"
                                class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
                                :class="entryMode === 'single' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                            >
                                <i class="pi pi-user text-xs"></i>
                                <span>Pencatatan Satuan (1 Siswa)</span>
                            </button>
                            <button
                                type="button"
                                @click="entryMode = 'bulk'"
                                class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
                                :class="entryMode === 'bulk' ? 'bg-[#6366F1] text-white shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                            >
                                <i class="pi pi-users text-xs"></i>
                                <span>Pencatatan Massal (Razia / Serentak)</span>
                                <span
                                    v-if="selectedStudentIds.length > 0"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase ml-1"
                                    :class="entryMode === 'bulk' ? 'bg-white text-[#6366F1]' : 'bg-indigo-100 text-indigo-700'"
                                >
                                    {{ selectedStudentIds.length }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Segmented Big Type Switcher -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Pilih Kategori Kejadian <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3.5">
                            <!-- Pelanggaran Tab -->
                            <button
                                type="button"
                                @click="setEventType('violation')"
                                class="relative flex items-center gap-3.5 p-3.5 sm:p-4 rounded-2xl border-2 transition-all text-left cursor-pointer group"
                                :class="[
                                    form.event_type === 'violation'
                                        ? 'border-rose-500 bg-rose-50/40 text-rose-950 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 bg-white text-gray-700'
                                ]"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                    :class="[
                                        form.event_type === 'violation'
                                            ? 'bg-rose-500 text-white shadow-sm'
                                            : 'bg-gray-100 text-gray-500 group-hover:bg-rose-50 group-hover:text-rose-600'
                                    ]"
                                >
                                    <i class="pi pi-exclamation-triangle text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-sm sm:text-base leading-tight">Pelanggaran</span>
                                        <span
                                            v-if="form.event_type === 'violation'"
                                            class="inline-block px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-rose-200 text-rose-800 uppercase"
                                        >
                                            Aktif
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                        Menambah poin pelanggaran tata tertib
                                    </p>
                                </div>
                            </button>

                            <!-- Prestasi Tab -->
                            <button
                                type="button"
                                @click="setEventType('achievement')"
                                class="relative flex items-center gap-3.5 p-3.5 sm:p-4 rounded-2xl border-2 transition-all text-left cursor-pointer group"
                                :class="[
                                    form.event_type === 'achievement'
                                        ? 'border-emerald-500 bg-emerald-50/40 text-emerald-950 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 bg-white text-gray-700'
                                ]"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                    :class="[
                                        form.event_type === 'achievement'
                                            ? 'bg-emerald-500 text-white shadow-sm'
                                            : 'bg-gray-100 text-gray-500 group-hover:bg-emerald-50 group-hover:text-emerald-600'
                                    ]"
                                >
                                    <i class="pi pi-star text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-sm sm:text-base leading-tight">Prestasi</span>
                                        <span
                                            v-if="form.event_type === 'achievement'"
                                            class="inline-block px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-emerald-200 text-emerald-800 uppercase"
                                        >
                                            Aktif
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                        Penghargaan & pereduksi poin sanksi
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6 pt-1">
                        
                        <!-- ================= SINGLE STUDENT MODE ================= -->
                        <div v-if="entryMode === 'single'" class="space-y-1.5">
                            <label for="student_id" class="block text-sm font-semibold text-gray-900">
                                Pilih Siswa <span class="text-rose-500">*</span>
                            </label>
                            
                            <!-- Search filter box if large list -->
                            <div v-if="students.length > 6" class="mb-2">
                                <div class="relative">
                                    <input
                                        v-model="studentSearch"
                                        type="text"
                                        placeholder="Ketik nama atau NIS untuk memfilter siswa..."
                                        class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                    />
                                    <i class="pi pi-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                                </div>
                            </div>

                            <select
                                id="student_id"
                                v-model="form.student_id"
                                :required="entryMode === 'single'"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium cursor-pointer"
                                :class="{ 'border-rose-400 ring-rose-200': form.errors.student_id }"
                            >
                                <option value="" disabled>Cari dan Pilih Siswa</option>
                                <option v-for="student in filteredStudents" :key="student.id" :value="student.id">
                                    {{ student.name }} (NIS: {{ student.nis }}) - {{ student.class_room?.name || 'Tanpa Kelas' }}
                                </option>
                            </select>
                            <p v-if="form.errors.student_id" class="text-xs font-medium text-rose-600 mt-1">
                                {{ form.errors.student_id }}
                            </p>

                            <!-- Parent Contact Indicator -->
                            <div
                                v-if="selectedStudent"
                                class="mt-2 flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-indigo-50/50 border border-indigo-100/70 text-xs"
                            >
                                <div class="flex items-center gap-2 text-gray-700 truncate">
                                    <i class="pi pi-user text-indigo-500"></i>
                                    <span>Orang Tua/Wali: <strong class="text-gray-900">{{ selectedStudent.parent_name || 'Wali Siswa' }}</strong></span>
                                </div>
                                <div
                                    class="flex items-center gap-1.5 font-medium shrink-0"
                                    :class="selectedStudent.parent_phone ? 'text-emerald-700' : 'text-amber-600'"
                                >
                                    <i class="pi pi-whatsapp"></i>
                                    <span>{{ selectedStudent.parent_phone || 'No. WA belum terisi' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- ================= BULK STUDENT MODE ================= -->
                        <div v-else class="space-y-3 p-5 rounded-2xl bg-indigo-50/30 border border-indigo-100">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100/80 pb-3">
                                <div>
                                    <label class="block text-sm font-bold text-gray-900">
                                        Pilih Siswa Terlibat (Pencatatan Massal) <span class="text-rose-500">*</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Pilih beberapa siswa yang melanggar/berprestasi dalam kejadian yang sama.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="toggleSelectAllBulk"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-indigo-200 bg-white hover:bg-indigo-50 text-indigo-700 transition-colors cursor-pointer"
                                    >
                                        {{ isAllCurrentBulkSelected ? 'Batalkan Semua' : 'Pilih Semua di Tampilan' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Kelas & Search Siswa Massal -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Filter Rombel / Kelas</label>
                                    <select
                                        v-model="selectedClassId"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer"
                                    >
                                        <option value="">Semua Kelas</option>
                                        <option v-for="c in classes" :key="c.id" :value="c.id">
                                            Kelas {{ c.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Cari Nama atau NIS</label>
                                    <div class="relative">
                                        <input
                                            v-model="bulkStudentSearch"
                                            type="text"
                                            placeholder="Cari siswa..."
                                            class="w-full pl-8 pr-3 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        />
                                        <i class="pi pi-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Students Tag / Chips Area -->
                            <div v-if="selectedStudentIds.length > 0" class="pt-2">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-indigo-900">
                                        {{ selectedStudentIds.length }} Siswa Terpilih:
                                    </span>
                                    <button
                                        type="button"
                                        @click="selectedStudentIds = []"
                                        class="text-[11px] font-semibold text-rose-600 hover:underline cursor-pointer"
                                    >
                                        Kosongkan Pilihan
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto p-2 bg-white rounded-xl border border-indigo-100">
                                    <span
                                        v-for="st in selectedStudentsObjects"
                                        :key="st.id"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-xs text-indigo-900 font-medium"
                                    >
                                        <span>{{ st.name }} ({{ st.class_room?.name || 'Kls' }})</span>
                                        <button
                                            type="button"
                                            @click="removeSelectedStudent(st.id)"
                                            class="text-indigo-400 hover:text-rose-600 w-4 h-4 rounded-full flex items-center justify-center hover:bg-white"
                                        >
                                            <i class="pi pi-times text-[9px]"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <!-- Checkbox List of Available Students -->
                            <div class="space-y-1">
                                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                    Daftar Siswa ({{ bulkAvailableStudents.length }} ditemukan):
                                </div>
                                <div class="max-h-60 overflow-y-auto divide-y divide-gray-100 bg-white rounded-xl border border-gray-200 p-1">
                                    <div
                                        v-for="st in bulkAvailableStudents"
                                        :key="st.id"
                                        @click="toggleStudentSelection(st.id)"
                                        class="flex items-center justify-between p-2.5 hover:bg-indigo-50/40 rounded-lg cursor-pointer transition-colors"
                                        :class="{ 'bg-indigo-50/60 font-semibold': selectedStudentIds.includes(st.id) }"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <input
                                                type="checkbox"
                                                :checked="selectedStudentIds.includes(st.id)"
                                                class="w-4 h-4 text-[#6366F1] rounded border-gray-300 focus:ring-[#6366F1] pointer-events-none"
                                            />
                                            <div class="truncate">
                                                <div class="text-xs text-gray-900 leading-tight">{{ st.name }}</div>
                                                <div class="text-[10px] text-gray-400 font-mono mt-0.5">NIS: {{ st.nis }}</div>
                                            </div>
                                        </div>
                                        <span class="text-[11px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-md shrink-0">
                                            {{ st.class_room?.name || 'Tanpa Kelas' }}
                                        </span>
                                    </div>

                                    <div v-if="bulkAvailableStudents.length === 0" class="py-8 text-center text-xs text-gray-400">
                                        Tidak ada siswa yang sesuai dengan filter.
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.student_ids" class="text-xs font-medium text-rose-600">
                                {{ form.errors.student_ids }}
                            </p>
                        </div>

                        <!-- Violation Selection (if event_type === 'violation') -->
                        <div v-if="form.event_type === 'violation'" class="space-y-2 p-5 rounded-2xl bg-rose-50/40 border border-rose-100">
                            <div class="flex items-center justify-between">
                                <label for="violation_id" class="block text-sm font-bold text-rose-950">
                                    Jenis Pelanggaran <span class="text-rose-500">*</span>
                                </label>
                                <Link
                                    href="/master-data?tab=violations"
                                    class="text-xs font-semibold text-rose-700 hover:text-rose-800 underline underline-offset-2 flex items-center gap-1"
                                    :title="canManageMasterData ? 'Kelola data pelanggaran' : 'Lihat katalog aturan pelanggaran'"
                                >
                                    <span>{{ canManageMasterData ? '+ Kelola di Master Data' : 'Lihat Katalog Aturan' }}</span>
                                </Link>
                            </div>

                            <select
                                id="violation_id"
                                v-model="form.violation_id"
                                :required="form.event_type === 'violation'"
                                class="w-full px-4 py-3 bg-white border border-rose-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer"
                                :class="{ 'border-rose-500 ring-rose-200': form.errors.violation_id }"
                            >
                                <option value="" disabled>Pilih Jenis Pelanggaran</option>
                                <option v-for="v in violations" :key="v.id" :value="v.id">
                                    [{{ v.code }}] {{ v.name }} (+{{ v.points }} Poin)
                                </option>
                            </select>
                            <p v-if="form.errors.violation_id" class="text-xs font-medium text-rose-600 mt-1">
                                {{ form.errors.violation_id }}
                            </p>

                            <!-- Point preview badge -->
                            <div v-if="selectedViolation" class="flex items-center justify-between pt-2 text-xs text-rose-900 border-t border-rose-100 mt-2">
                                <span class="font-medium">Bobot Sanksi:</span>
                                <span class="px-2.5 py-1 bg-rose-600 text-white font-bold rounded-lg text-xs">
                                    +{{ selectedViolation.points }} Poin Pelanggaran
                                </span>
                            </div>
                        </div>

                        <!-- Achievement Selection (if event_type === 'achievement') -->
                        <div v-if="form.event_type === 'achievement'" class="space-y-2 p-5 rounded-2xl bg-emerald-50/40 border border-emerald-100">
                            <div class="flex items-center justify-between">
                                <label for="achievement_id" class="block text-sm font-bold text-emerald-950">
                                    Jenis Prestasi Siswa <span class="text-rose-500">*</span>
                                </label>
                                <Link
                                    href="/master-data?tab=achievements"
                                    class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 underline underline-offset-2 flex items-center gap-1"
                                    :title="canManageMasterData ? 'Kelola data prestasi' : 'Lihat katalog prestasi siswa'"
                                >
                                    <span>{{ canManageMasterData ? '+ Kelola di Master Data' : 'Lihat Katalog Prestasi' }}</span>
                                </Link>
                            </div>

                            <select
                                id="achievement_id"
                                v-model="form.achievement_id"
                                :required="form.event_type === 'achievement'"
                                class="w-full px-4 py-3 bg-white border border-emerald-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                :class="{ 'border-rose-500 ring-rose-200': form.errors.achievement_id }"
                            >
                                <option value="" disabled>Pilih Jenis Prestasi</option>
                                <option v-for="a in achievements" :key="a.id" :value="a.id">
                                    [{{ a.code }}] {{ a.name }} (-{{ a.points }} Poin Pengurang)
                                </option>
                            </select>
                            <p v-if="form.errors.achievement_id" class="text-xs font-medium text-rose-600 mt-1">
                                {{ form.errors.achievement_id }}
                            </p>

                            <!-- Point preview badge -->
                            <div v-if="selectedAchievement" class="flex items-center justify-between pt-2 text-xs text-emerald-900 border-t border-emerald-100 mt-2">
                                <span class="font-medium">Reduksi Poin:</span>
                                <span class="px-2.5 py-1 bg-emerald-600 text-white font-bold rounded-lg text-xs">
                                    -{{ selectedAchievement.points }} Poin Pengurang
                                </span>
                            </div>
                        </div>

                        <!-- Date and Time Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="event_date" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Tanggal Kejadian <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="event_date"
                                    v-model="form.event_date"
                                    type="date"
                                    required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                />
                                <p v-if="form.errors.event_date" class="text-xs font-medium text-rose-600 mt-1">
                                    {{ form.errors.event_date }}
                                </p>
                            </div>

                            <div>
                                <label for="original_recorder_name" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Nama Pencatat / Pelapor
                                </label>
                                <input
                                    id="original_recorder_name"
                                    v-model="form.original_recorder_name"
                                    type="text"
                                    placeholder="Nama guru/petugas piket"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                />
                            </div>
                        </div>

                        <!-- Chronology / Description -->
                        <div>
                            <label for="description" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                Keterangan / Kronologi Kejadian
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Jelaskan rincian kejadian secara objektif dan faktual..."
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all resize-y"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-xs font-medium text-rose-600 mt-1">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Temporary Follow-up for Violations -->
                        <div v-if="form.event_type === 'violation'">
                            <label for="follow_up" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                Tindak Lanjut Sementara (Opsional)
                            </label>
                            <input
                                id="follow_up"
                                v-model="form.follow_up"
                                type="text"
                                placeholder="Contoh: Diberi teguran lisan di tempat & pemanggilan ortu oleh Wali Kelas"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <!-- Foto Bukti Kejadian (Barang Sitaan / Dokumentasi Razia) -->
                        <div class="space-y-2 p-4 rounded-2xl bg-gray-50 border border-gray-200/80">
                            <label class="block text-xs font-bold text-gray-900">
                                Foto Bukti Kejadian (Opsional)
                            </label>
                            <p class="text-[11px] text-gray-500">
                                Unggah foto dokumentasi seperti foto barang sitaan, kerusakan fasilitas, atau bukti razia di lapangan.
                            </p>

                            <div v-if="!attachmentPreview" class="border-2 border-dashed border-gray-200 hover:border-indigo-400 rounded-xl p-4 text-center transition-colors bg-white cursor-pointer">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/*"
                                    @change="onAttachmentSelected"
                                    class="hidden"
                                    id="attachment-upload"
                                />
                                <label for="attachment-upload" class="cursor-pointer flex flex-col items-center justify-center gap-1.5">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-[#6366F1] flex items-center justify-center">
                                        <i class="pi pi-camera text-base"></i>
                                    </div>
                                    <span class="text-xs font-bold text-[#6366F1] hover:underline">Pilih atau Seret Foto ke Sini</span>
                                    <span class="text-[10px] text-gray-400">Format PNG, JPG, JPEG (Maks. 5MB)</span>
                                </label>
                            </div>

                            <div v-else class="relative inline-block border border-gray-200 rounded-xl p-2 bg-white shadow-xs">
                                <img :src="attachmentPreview" alt="Bukti Kejadian" class="h-32 w-auto object-cover rounded-lg border border-gray-100" />
                                <button
                                    type="button"
                                    @click="removeAttachment"
                                    class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center shadow-md hover:bg-red-700 transition-colors cursor-pointer"
                                    title="Hapus Foto"
                                >
                                    <i class="pi pi-times text-[10px]"></i>
                                </button>
                                <span class="text-[10px] text-gray-500 block text-center mt-1 truncate max-w-[140px]">
                                    {{ attachmentFile?.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                            <div class="text-xs font-semibold text-gray-500">
                                <span v-if="entryMode === 'bulk'">
                                    Total target: <strong class="text-indigo-600 font-bold">{{ selectedStudentIds.length }} siswa</strong>
                                </span>
                                <span v-else-if="selectedStudent">
                                    Target: <strong class="text-gray-900">{{ selectedStudent.name }}</strong>
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <Link
                                    href="/discipline-events"
                                    class="px-5 py-2.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer"
                                >
                                    Batal
                                </Link>

                                <button
                                    type="submit"
                                    :disabled="form.processing || (entryMode === 'bulk' && selectedStudentIds.length === 0)"
                                    class="px-6 py-2.5 text-xs font-semibold text-white rounded-xl transition-all shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="[
                                        form.event_type === 'violation'
                                            ? 'bg-rose-600 hover:bg-rose-700'
                                            : 'bg-emerald-600 hover:bg-emerald-700'
                                    ]"
                                >
                                    <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                                    <span>
                                        {{ entryMode === 'bulk'
                                            ? (form.event_type === 'violation' ? `Simpan Pelanggaran (${selectedStudentIds.length} Siswa)` : `Simpan Prestasi (${selectedStudentIds.length} Siswa)`)
                                            : (form.event_type === 'violation' ? 'Simpan Pelanggaran' : 'Simpan Prestasi')
                                        }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Live Feed / Recent Incidents (4 Cols) -->
                <div class="lg:col-span-5 xl:col-span-4 space-y-6">
                    
                    <div class="bg-white border border-gray-100 rounded-[24px] shadow-sm p-6 sm:p-7 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i class="pi pi-history text-sm"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-sm">Catatan Terbaru</h3>
                                    <p class="text-xs text-gray-400">Aktivitas yang baru saja dicatat</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] uppercase tracking-wider">
                                Live
                            </span>
                        </div>

                        <!-- List of recent items -->
                        <div v-if="recentEvents && recentEvents.length > 0" class="space-y-3">
                            <div
                                v-for="item in recentEvents"
                                :key="item.id"
                                class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:border-gray-200 transition-all flex flex-col gap-2"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-xs leading-snug">
                                            {{ item.student?.name || 'Siswa' }}
                                        </h4>
                                        <span class="text-[11px] text-gray-500">
                                            {{ item.student?.class_room?.name || 'Kelas' }}
                                        </span>
                                    </div>
                                    <span
                                        class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider"
                                        :class="[
                                            item.event_type === 'violation'
                                                ? 'bg-rose-100 text-rose-700'
                                                : 'bg-emerald-100 text-emerald-700'
                                        ]"
                                    >
                                        {{ item.points_effect > 0 ? `+${item.points_effect}` : item.points_effect }} Poin
                                    </span>
                                </div>

                                <p class="text-xs text-gray-700 font-medium line-clamp-1">
                                    {{ item.violation?.name || item.achievement?.name || 'Kejadian' }}
                                </p>

                                <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1 border-t border-gray-100">
                                    <span>{{ formatDate(item.event_date) }}</span>
                                    <button
                                        type="button"
                                        @click="sendWhatsAppRecentEvent(item)"
                                        class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:text-emerald-700 hover:underline cursor-pointer"
                                        title="Kirim pemberitahuan kejadian ini ke WhatsApp orang tua"
                                    >
                                        <i class="pi pi-whatsapp text-xs"></i>
                                        <span>Kirim WA</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State when no recent activities -->
                        <div v-else class="py-10 px-4 text-center space-y-3">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400">
                                <i class="pi pi-clipboard text-xl"></i>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-gray-800 text-sm">Belum Ada Catatan Terbaru</h4>
                                <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">
                                    Catatan pelanggaran dan prestasi yang baru disimpan akan tampil di sini secara teratur.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Guidance Card -->
                    <div class="bg-gradient-to-br from-indigo-50/50 to-purple-50/30 border border-indigo-100/60 rounded-2xl p-5 text-xs text-indigo-950 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-indigo-900">
                            <i class="pi pi-info-circle text-indigo-600"></i>
                            <span>Panduan Pencatatan Kejadian</span>
                        </div>
                        <ul class="space-y-1.5 text-gray-600 list-disc list-inside">
                            <li><strong>Mode Satuan:</strong> Khusus untuk menangani 1 siswa secara individual.</li>
                            <li><strong>Mode Massal:</strong> Mempermudah penertiban razia atribut seragam, razia gawai, atau keterlambatan berjamaah.</li>
                            <li><strong>Foto Bukti:</strong> Foto barang bukti yang diunggah akan tersimpan aman dan terlampir pada profil disiplin siswa.</li>
                        </ul>
                    </div>

                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
