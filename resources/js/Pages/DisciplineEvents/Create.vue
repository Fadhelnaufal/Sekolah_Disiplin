<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import { buildDisciplineEventWhatsAppMessage, buildWhatsAppUrl } from '../../utils';

interface StudentItem {
    id: number;
    name: string;
    nis: string;
    parent_name?: string;
    parent_phone?: string;
    class_room?: { name: string };
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

const form = useForm({
    student_id: props.defaultStudentId || '',
    event_date: todayDate,
    event_time: currentTime,
    event_type: 'violation',
    violation_id: '',
    achievement_id: '',
    original_recorder_name: currentUser.value?.name || '',
    description: '',
    follow_up: '',
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
    form.post('/discipline-events', {
        preserveScroll: true,
        onSuccess: () => {
            // Keep student or reset depending on workflow
            form.description = '';
            form.follow_up = '';
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
            <div class="flex items-center gap-2 text-sm">
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
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-xs"
                    >
                        Riwayat Kejadian
                    </Link>
                    <Link
                        href="/master-data"
                        class="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs"
                    >
                        {{ canManageMasterData ? 'Master Aturan & Poin' : 'Katalog Aturan & Poin' }}
                    </Link>
                </div>
            </div>

            <!-- 2-Column Responsive Layout (Screenshots 3 & 4) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Column: Incident Entry Form (8 Cols) -->
                <div class="lg:col-span-7 xl:col-span-8 bg-white border border-gray-100 rounded-[24px] shadow-sm p-6 sm:p-8 space-y-6">
                    
                    <!-- Segmented Big Type Switcher (Screenshot 3 & 4) -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">
                            Pilih Kategori Kejadian <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Pelanggaran Tab -->
                            <button
                                type="button"
                                @click="setEventType('violation')"
                                class="relative flex items-center gap-4 p-4 rounded-2xl border-2 transition-all text-left cursor-pointer group"
                                :class="[
                                    form.event_type === 'violation'
                                        ? 'border-rose-500 bg-rose-50/40 text-rose-950 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 bg-white text-gray-700'
                                ]"
                            >
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                    :class="[
                                        form.event_type === 'violation'
                                            ? 'bg-rose-500 text-white shadow-sm'
                                            : 'bg-gray-100 text-gray-500 group-hover:bg-rose-50 group-hover:text-rose-600'
                                    ]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-base leading-tight">Pelanggaran</span>
                                        <span
                                            v-if="form.event_type === 'violation'"
                                            class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-200 text-rose-800 uppercase"
                                        >
                                            Aktif
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">
                                        Menambah poin pelanggaran tata tertib
                                    </p>
                                </div>
                            </button>

                            <!-- Prestasi Tab -->
                            <button
                                type="button"
                                @click="setEventType('achievement')"
                                class="relative flex items-center gap-4 p-4 rounded-2xl border-2 transition-all text-left cursor-pointer group"
                                :class="[
                                    form.event_type === 'achievement'
                                        ? 'border-emerald-500 bg-emerald-50/40 text-emerald-950 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 bg-white text-gray-700'
                                ]"
                            >
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                    :class="[
                                        form.event_type === 'achievement'
                                            ? 'bg-emerald-500 text-white shadow-sm'
                                            : 'bg-gray-100 text-gray-500 group-hover:bg-emerald-50 group-hover:text-emerald-600'
                                    ]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-base leading-tight">Prestasi</span>
                                        <span
                                            v-if="form.event_type === 'achievement'"
                                            class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-200 text-emerald-800 uppercase"
                                        >
                                            Aktif
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">
                                        Penghargaan & pengurangan poin sanksi
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6 pt-2">
                        
                        <!-- Student Selection -->
                        <div class="space-y-1.5">
                            <label for="student_id" class="block text-sm font-semibold text-gray-900">
                                Pilih Siswa <span class="text-rose-500">*</span>
                            </label>
                            
                            <!-- Search filter box if large list -->
                            <div v-if="students.length > 8" class="mb-2">
                                <div class="relative">
                                    <input
                                        v-model="studentSearch"
                                        type="text"
                                        placeholder="Ketik nama atau NIS untuk memfilter siswa..."
                                        class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                    />
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <select
                                id="student_id"
                                v-model="form.student_id"
                                required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium"
                                :class="{ 'border-rose-400 ring-rose-200': form.errors.student_id }"
                            >
                                <option value="" disabled>-- Cari dan Pilih Siswa --</option>
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
                                class="w-full px-4 py-3 bg-white border border-rose-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"
                                :class="{ 'border-rose-500 ring-rose-200': form.errors.violation_id }"
                            >
                                <option value="" disabled>-- Pilih Jenis Pelanggaran --</option>
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
                                class="w-full px-4 py-3 bg-white border border-emerald-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                :class="{ 'border-rose-500 ring-rose-200': form.errors.achievement_id }"
                            >
                                <option value="" disabled>-- Pilih Jenis Prestasi --</option>
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

                        <!-- Actions Bar -->
                        <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <Link
                                href="/discipline-events"
                                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
                            >
                                Batal
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl transition-all shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                                :class="[
                                    form.event_type === 'violation'
                                        ? 'bg-rose-600 hover:bg-rose-700 focus:ring-4 focus:ring-rose-200'
                                        : 'bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200'
                                ]"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ form.event_type === 'violation' ? 'Simpan Pelanggaran' : 'Simpan Prestasi' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Live Feed / Recent Incidents (4 Cols) (Screenshot 3 & 4) -->
                <div class="lg:col-span-5 xl:col-span-4 space-y-6">
                    
                    <div class="bg-white border border-gray-100 rounded-[24px] shadow-sm p-6 sm:p-7 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
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
                                        <i class="pi pi-whatsapp"></i>
                                        <span>Kirim WA</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State when no recent activities (Screenshots 3 & 4) -->
                        <div v-else class="py-10 px-4 text-center space-y-3">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Panduan Pencatatan Cepat</span>
                        </div>
                        <ul class="space-y-1.5 text-gray-600 list-disc list-inside">
                            <li>Poin pelanggaran diakumulasikan per semester aktif.</li>
                            <li>Poin prestasi akan otomatis mereduksi akumulasi poin sanksi.</li>
                            <li>Tindak lanjut lanjutan dapat dibuat melalui menu <strong>Tindak Lanjut</strong>.</li>
                        </ul>
                    </div>

                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
