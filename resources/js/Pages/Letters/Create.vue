<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import { buildParentCallWhatsAppMessage, buildWhatsAppUrl } from '../../utils';

interface StudentItem {
    id: number;
    nis: string;
    name: string;
    parent_name?: string;
    parent_phone?: string;
    class_room?: {
        id: number;
        name: string;
        homeroom_teacher?: {
            name: string;
            nip?: string;
        };
    };
}

interface StudentSummary {
    id: number;
    nis: string;
    name: string;
    parent_name?: string;
    parent_phone?: string;
    class_name: string;
    homeroom_teacher: string;
    violation_points: number;
    achievement_points: number;
    net_points: number;
    sanction_level?: string;
    sanction_action?: string;
    recent_events: Array<{
        date: string;
        description: string;
        points: number;
    }>;
}

const props = defineProps<{
    students: StudentItem[];
    selectedStudent?: StudentItem | null;
    studentDisciplineSummary?: StudentSummary | null;
    documentDefinitions: Record<string, any>;
    initialType?: string;
    school: any;
    semester?: any;
}>();

const toast = useToast();

const currentYear = new Date().getFullYear();
const activeType = ref<string>(props.initialType || 'parent_notification');
const selectedStudentId = ref<number | ''>(props.selectedStudent?.id || '');
const studentSummary = ref<StudentSummary | null>(props.studentDisciplineSummary || null);
const isLoadingStudent = ref<boolean>(false);

// Form model
const form = useForm({
    student_id: selectedStudentId.value,
    type: activeType.value,
    subject: '',
    // Parent Notification fields
    incident_summary: '',
    school_action: 'Pihak sekolah telah memberikan pembinaan lisan dan bimbingan awal melalui Tim Ketertiban dan Wali Kelas.',
    expected_follow_up: 'Diharapkan orang tua/wali dapat memberikan pendampingan, pengawasan waktu belajar, serta pembiasaan tertib di rumah.',
    notes: '',
    // Student Agreement fields
    problem_summary: '',
    student_commitments: "1. Menyadari dan mengakui kesalahan serta berjanji tidak akan mengulangi perbuatan tersebut.\n2. Siap mematuhi seluruh peraturan dan tata tertib yang berlaku di sekolah dengan penuh tanggung jawab.\n3. Bersedia menerima sanksi yang lebih berat sesuai ketentuan tata tertib apabila di kemudian hari melanggar kembali.",
    guidance_plan: 'Siswa bersedia mengikuti bimbingan konseling berkala serta pemantauan kedisiplinan mingguan oleh Wali Kelas dan Guru BK.',
    evaluation_period: '30 (tiga puluh) hari kalender',
    sanction_consequence: 'Apabila melanggar perjanjian ini, siswa siap menerima sanksi peningkatan ke tingkat pemanggilan berikutnya atau skorsing sesuai aturan tata tertib sekolah.',
    // Parent Call fields
    call_level: 'call_1',
    reason: 'Membicarakan perkembangan sikap dan pembinaan kedisiplinan peserta didik di sekolah sehubungan dengan akumulasi catatan tata tertib.',
    meeting_day_date: '',
    meeting_time: '08.30 WIB s/d selesai',
    meeting_venue: 'Ruang Bimbingan dan Konseling (BK)',
    contact_person: '',
    // Discipline History fields
    include_achievements: '1',
    evaluation_notes: 'Peserta didik dalam pemantauan berkala tim ketertiban sekolah dan bimbingan wali kelas.',
});

// Update default subject and call level when document type changes
const syncTypeDefaults = (type: string) => {
    activeType.value = type;
    form.type = type;

    const def = props.documentDefinitions[type];
    if (def) {
        form.subject = def.default_subject || '';
    }

    if (type === 'parent_call' && !form.call_level) {
        form.call_level = 'call_1';
    }
};

onMounted(() => {
    syncTypeDefaults(activeType.value);
    if (!form.meeting_day_date) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 2);
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        form.meeting_day_date = `${dayNames[tomorrow.getDay()]}, ${tomorrow.getDate()} ${monthNames[tomorrow.getMonth()]} ${tomorrow.getFullYear()}`;
    }
});

// Fetch student data dynamically on student selection
const fetchStudentData = async (studentId: number) => {
    if (!studentId) {
        studentSummary.value = null;
        return;
    }

    isLoadingStudent.value = true;
    try {
        const response = await fetch(`/letters/student-data/${studentId}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            const data: StudentSummary = await response.json();
            studentSummary.value = data;

            // Auto-populate incident summary and problem summary if empty
            if (!form.incident_summary) {
                form.incident_summary = `Peserta didik saat ini memiliki akumulasi catatan ketidakdisiplinan sebesar ${data.net_points} poin pada semester aktif.`;
            }
            if (!form.problem_summary) {
                form.problem_summary = `Pelanggaran tata tertib sekolah dengan akumulasi ketidaktertiban ${data.net_points} poin.`;
            }
        }
    } catch (e) {
        console.error('Failed to load student discipline data:', e);
    } finally {
        isLoadingStudent.value = false;
    }
};

watch(selectedStudentId, (newId) => {
    form.student_id = newId;
    if (newId) {
        fetchStudentData(Number(newId));
    } else {
        studentSummary.value = null;
    }
});

// Computed properties for Live Preview
const activeStudent = computed(() => {
    if (studentSummary.value) {
        return studentSummary.value;
    }
    const found = props.students.find(s => s.id === selectedStudentId.value);
    if (found) {
        return {
            id: found.id,
            nis: found.nis,
            name: found.name,
            parent_name: found.parent_name || 'Orang Tua / Wali Siswa',
            parent_phone: found.parent_phone || '-',
            class_name: found.class_room?.name || 'Tanpa Kelas',
            homeroom_teacher: found.class_room?.homeroom_teacher?.name || '-',
            violation_points: 0,
            achievement_points: 0,
            net_points: 0,
            recent_events: [],
        };
    }
    return {
        id: 0,
        nis: '12345',
        name: 'Nama Peserta Didik',
        parent_name: 'Nama Orang Tua / Wali',
        parent_phone: '-',
        class_name: 'Kelas / Rombel',
        homeroom_teacher: 'Wali Kelas',
        violation_points: 0,
        achievement_points: 0,
        net_points: 0,
        recent_events: [],
    };
});

const todayFormatted = computed(() => {
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const now = new Date();
    return `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
});

const metaText = computed(() => {
    const parts: string[] = [];
    if (props.school?.show_npsn && props.school?.npsn) {
        parts.push(`NPSN ${props.school.npsn}`);
    }
    if (props.school?.show_phone && props.school?.phone) {
        parts.push(`Telp. ${props.school.phone}`);
    }
    if (props.school?.show_email && props.school?.email) {
        parts.push(props.school.email);
    }
    if (props.school?.show_website && props.school?.website) {
        parts.push(props.school.website);
    }
    return parts.join(' · ');
});

const calculatedSignatory = computed(() => {
    if (activeType.value === 'parent_call') {
        if (form.call_level === 'call_1') {
            return {
                title: props.school?.counseling_officer_name ? 'Guru BK / Koordinator BK' : (props.school?.signer_title || 'Kepala Sekolah'),
                name: props.school?.counseling_officer_name || props.school?.principal_name || '.......................',
                nip: null,
            };
        } else if (form.call_level === 'call_2') {
            return {
                title: props.school?.vice_principal_name ? 'Wakil Kepala Sekolah Bidang Kesiswaan' : (props.school?.signer_title || 'Kepala Sekolah'),
                name: props.school?.vice_principal_name || props.school?.principal_name || '.......................',
                nip: null,
            };
        }
    }
    return {
        title: props.school?.signer_title || 'Kepala Sekolah',
        name: props.school?.principal_name || '.......................',
        nip: props.school?.principal_nip || null,
    };
});

const submit = () => {
    form.post('/letters', {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Berhasil',
                detail: 'Dokumen resmi berhasil diterbitkan.',
                life: 3000,
            });
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0] || 'Gagal menerbitkan dokumen.';
            toast.add({
                severity: 'error',
                summary: 'Validasi Gagal',
                detail: String(firstErr),
                life: 5000,
            });
        },
    });
};

const sendWhatsAppNotification = () => {
    if (!activeStudent.value) {
        toast.add({
            severity: 'warn',
            summary: 'Peringatan',
            detail: 'Silakan pilih siswa terlebih dahulu',
            life: 3000,
        });
        return;
    }

    const message = buildParentCallWhatsAppMessage({
        schoolName: props.school?.name || 'Sekolah',
        studentName: activeStudent.value.name,
        studentNis: activeStudent.value.nis,
        className: activeStudent.value.class_name,
        letterSubject: form.subject || 'Pemberitahuan / Pemanggilan Kedisiplinan',
        meetingDayDate: form.meeting_day_date || undefined,
        meetingTime: form.meeting_time || undefined,
        meetingVenue: form.meeting_venue || undefined,
        meetingPurpose: form.reason || undefined,
        notes: form.notes || undefined,
    });

    const phone = activeStudent.value.parent_phone;
    if (!phone) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(message);
        }
        toast.add({
            severity: 'info',
            summary: 'Nomor WA Belum Ada',
            detail: 'Nomor WhatsApp orang tua belum terdaftar di profil siswa. Draf pesan resmi telah disalin ke clipboard Anda.',
            life: 5000,
        });
        window.open(buildWhatsAppUrl('', message), '_blank');
        return;
    }

    window.open(buildWhatsAppUrl(phone, message), '_blank');
};
</script>

<template>
    <Head title="Penerbitan Dokumen Resmi - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        href="/letters"
                        class="w-8 h-8 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 flex items-center justify-center transition-colors shadow-2xs"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                    </Link>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900 leading-tight">Penerbitan Dokumen Resmi</span>
                        <span class="text-xs font-medium text-gray-400">Administrasi Kesiswaan & Tata Tertib</span>
                    </div>
                </div>

                <Link
                    href="/letters"
                    class="text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors"
                >
                    Kembali ke Arsip
                </Link>
            </div>
        </template>

        <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-6">
            <!-- Document Type Selection Tabs -->
            <div class="mb-6">
                <span class="text-[11px] font-bold text-[#8B5CF6] tracking-wider uppercase block mb-2">
                    PILIH JENIS DOKUMEN RESMI
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- 1. Surat Pemberitahuan -->
                    <button
                        type="button"
                        @click="syncTypeDefaults('parent_notification')"
                        :class="activeType === 'parent_notification' ? 'border-[#6366F1] bg-[#F5F3FF] ring-2 ring-[#6366F1]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                        class="p-4 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between group shadow-2xs"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 rounded-xl bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center">
                                <i class="pi pi-bell text-xs"></i>
                            </div>
                            <i v-if="activeType === 'parent_notification'" class="pi pi-check-circle text-[#6366F1] text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-900">Surat Pemberitahuan</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2">Pemberitahuan catatan kedisiplinan dan poin kepada orang tua.</p>
                        </div>
                    </button>

                    <!-- 2. Surat Perjanjian Siswa -->
                    <button
                        type="button"
                        @click="syncTypeDefaults('student_agreement')"
                        :class="activeType === 'student_agreement' ? 'border-[#6366F1] bg-[#F5F3FF] ring-2 ring-[#6366F1]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                        class="p-4 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between group shadow-2xs"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 rounded-xl bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center">
                                <i class="pi pi-file-edit text-xs"></i>
                            </div>
                            <i v-if="activeType === 'student_agreement'" class="pi pi-check-circle text-[#6366F1] text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-900">Surat Perjanjian Siswa</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2">Komitmen tertulis siswa disaksikan orang tua, BK, dan wali kelas.</p>
                        </div>
                    </button>

                    <!-- 3. Surat Panggilan Orang Tua -->
                    <button
                        type="button"
                        @click="syncTypeDefaults('parent_call')"
                        :class="activeType === 'parent_call' ? 'border-[#6366F1] bg-[#F5F3FF] ring-2 ring-[#6366F1]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                        class="p-4 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between group shadow-2xs"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 rounded-xl bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center">
                                <i class="pi pi-envelope text-xs"></i>
                            </div>
                            <i v-if="activeType === 'parent_call'" class="pi pi-check-circle text-[#6366F1] text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-900">Surat Panggilan Orang Tua</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2">Pemanggilan resmi Panggilan I, II, III, atau khusus.</p>
                        </div>
                    </button>

                    <!-- 4. Riwayat Kedisiplinan Siswa -->
                    <button
                        type="button"
                        @click="syncTypeDefaults('discipline_history')"
                        :class="activeType === 'discipline_history' ? 'border-[#6366F1] bg-[#F5F3FF] ring-2 ring-[#6366F1]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                        class="p-4 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between group shadow-2xs"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 rounded-xl bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center">
                                <i class="pi pi-history text-xs"></i>
                            </div>
                            <i v-if="activeType === 'discipline_history'" class="pi pi-check-circle text-[#6366F1] text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-900">Riwayat Kedisiplinan</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2">Laporan rekam jejak ketertiban, prestasi, dan pembinaan siswa.</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Two-Column Grid: Left (Form Inputs) / Right (Live A4 Preview) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column: Form -->
                <div class="lg:col-span-7 xl:col-span-7 space-y-5">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Student Selector Card -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center">
                                        <i class="pi pi-user text-xs"></i>
                                    </div>
                                    <h2 class="text-xs font-bold text-gray-900">Pilih Peserta Didik</h2>
                                </div>
                                <span v-if="isLoadingStudent" class="text-[11px] text-[#6366F1] flex items-center gap-1 font-medium">
                                    <i class="pi pi-spin pi-spinner text-xs"></i> Memuat data disiplin...
                                </span>
                            </div>

                            <div>
                                <label for="student_id" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Peserta Didik <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="student_id"
                                    v-model="selectedStudentId"
                                    class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    required
                                >
                                    <option value="" disabled>-- Pilih Siswa --</option>
                                    <option v-for="std in students" :key="std.id" :value="std.id">
                                        {{ std.name }} (NIS: {{ std.nis }}) - {{ std.class_room?.name || 'Tanpa Kelas' }}
                                    </option>
                                </select>
                                <p v-if="form.errors.student_id" class="text-xs text-red-600 mt-1">{{ form.errors.student_id }}</p>
                            </div>

                            <!-- Live Student Summary Card -->
                            <div v-if="studentSummary" class="bg-[#F9FAFB] rounded-xl p-3.5 border border-gray-100 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-900">{{ studentSummary.name }}</span>
                                    <span class="text-[11px] font-mono text-gray-500">NIS: {{ studentSummary.nis }} · {{ studentSummary.class_name }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div class="bg-white p-2 rounded-lg border border-gray-100">
                                        <span class="text-[10px] text-gray-500 block">Pelanggaran</span>
                                        <span class="font-bold text-red-600">{{ studentSummary.violation_points }} pt</span>
                                    </div>
                                    <div class="bg-white p-2 rounded-lg border border-gray-100">
                                        <span class="text-[10px] text-gray-500 block">Prestasi</span>
                                        <span class="font-bold text-emerald-600">{{ studentSummary.achievement_points }} pt</span>
                                    </div>
                                    <div class="bg-white p-2 rounded-lg border border-gray-100">
                                        <span class="text-[10px] text-gray-500 block">Poin Bersih</span>
                                        <span class="font-bold text-indigo-700">{{ studentSummary.net_points }} pt</span>
                                    </div>
                                </div>
                                <div v-if="studentSummary.sanction_level" class="text-[11px] text-amber-700 bg-amber-50 p-2 rounded-lg border border-amber-200">
                                    Status Sanksi: <strong>{{ studentSummary.sanction_level }}</strong>: {{ studentSummary.sanction_action }}
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Type-Specific Form Fields -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-2xs space-y-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center">
                                    <i class="pi pi-pencil text-xs"></i>
                                </div>
                                <h2 class="text-xs font-bold text-gray-900">Isi Dokumen & Parameter</h2>
                            </div>

                            <!-- Common Subject Field -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Perihal / Judul Dokumen</label>
                                <input
                                    v-model="form.subject"
                                    type="text"
                                    class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    required
                                />
                            </div>

                            <!-- TYPE 1: Surat Pemberitahuan Orang Tua -->
                            <template v-if="activeType === 'parent_notification'">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Ringkasan Kejadian / Permasalahan</label>
                                    <textarea
                                        v-model="form.incident_summary"
                                        rows="3"
                                        placeholder="Uraikan ringkasan permasalahan atau kejadian yang terjadi..."
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tindakan Pembinaan Sekolah</label>
                                    <textarea
                                        v-model="form.school_action"
                                        rows="2"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tindak Lanjut yang Diharapkan dari Orang Tua</label>
                                    <textarea
                                        v-model="form.expected_follow_up"
                                        rows="2"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    ></textarea>
                                </div>
                            </template>

                            <!-- TYPE 2: Surat Perjanjian Siswa -->
                            <template v-if="activeType === 'student_agreement'">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Ringkasan Permasalahan / Pelanggaran</label>
                                    <textarea
                                        v-model="form.problem_summary"
                                        rows="2"
                                        placeholder="Pelanggaran tata tertib yang telah dilakukan siswa..."
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                        required
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Butir-Butir Komitmen Siswa</label>
                                    <textarea
                                        v-model="form.student_commitments"
                                        rows="4"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all font-mono"
                                        required
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Masa Evaluasi</label>
                                        <input
                                            v-model="form.evaluation_period"
                                            type="text"
                                            placeholder="Contoh: 30 hari kalender"
                                            class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Bentuk Pembinaan / Pemantauan</label>
                                        <input
                                            v-model="form.guidance_plan"
                                            type="text"
                                            class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Konsekuensi Sanksi bila Melanggar</label>
                                    <textarea
                                        v-model="form.sanction_consequence"
                                        rows="2"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    ></textarea>
                                </div>
                            </template>

                            <!-- TYPE 3: Surat Panggilan Orang Tua -->
                            <template v-if="activeType === 'parent_call'">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tingkat Panggilan</label>
                                    <select
                                        v-model="form.call_level"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    >
                                        <option value="call_1">Panggilan I (BK / Koordinator BK)</option>
                                        <option value="call_2">Panggilan II (Wakil Kepala Sekolah Kesiswaan)</option>
                                        <option value="call_3">Panggilan III / Terakhir (Kepala Sekolah)</option>
                                        <option value="call_general">Panggilan Umum / Pertemuan Khusus</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Alasan Pemanggilan</label>
                                    <textarea
                                        v-model="form.reason"
                                        rows="2"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                        required
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Hari & Tanggal Pertemuan</label>
                                        <input
                                            v-model="form.meeting_day_date"
                                            type="text"
                                            placeholder="Contoh: Senin, 19 Oktober 2026"
                                            class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Waktu / Jam Pertemuan</label>
                                        <input
                                            v-model="form.meeting_time"
                                            type="text"
                                            class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                            required
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tempat Pertemuan</label>
                                        <input
                                            v-model="form.meeting_venue"
                                            type="text"
                                            class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Kontak Konfirmasi (Opsional)</label>
                                        <input
                                            v-model="form.contact_person"
                                            type="text"
                                            placeholder="Contoh: 0812-xxxx-xxxx (Guru BK)"
                                            class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                        />
                                    </div>
                                </div>
                            </template>

                            <!-- TYPE 4: Riwayat Kedisiplinan Siswa -->
                            <template v-if="activeType === 'discipline_history'">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Sertakan Catatan Prestasi</label>
                                    <select
                                        v-model="form.include_achievements"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    >
                                        <option value="1">Ya, tampilkan prestasi sebagai pengurang poin</option>
                                        <option value="0">Tidak, hanya tampilkan data pelanggaran</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan & Rekomendasi Tim Ketertiban</label>
                                    <textarea
                                        v-model="form.evaluation_notes"
                                        rows="3"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    ></textarea>
                                </div>
                            </template>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-2 flex-wrap">
                            <Link
                                href="/letters"
                                class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-semibold transition-colors"
                            >
                                Batal
                            </Link>
                            <button
                                type="button"
                                @click="sendWhatsAppNotification"
                                :disabled="!selectedStudentId"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition-all cursor-pointer active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                                title="Kirim ringkasan pemanggilan ke WhatsApp orang tua"
                            >
                                <i class="pi pi-whatsapp text-xs"></i>
                                <span>Kirim WA Ortu</span>
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || !selectedStudentId"
                                class="px-6 py-2.5 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-md transition-all cursor-pointer active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                            >
                                <i :class="form.processing ? 'pi pi-spin pi-spinner' : 'pi pi-print'" class="text-xs"></i>
                                <span>{{ form.processing ? 'Menerbitkan...' : 'Terbitkan Dokumen (PDF)' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Live A4 Preview Canvas -->
                <div class="lg:col-span-5 xl:col-span-5 sticky top-6 space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <span class="text-[11px] font-bold text-[#6366F1] tracking-wider uppercase">
                            LIVE PREVIEW A4
                        </span>
                        <span class="text-[11px] text-gray-400 italic">
                            Otomatis terbarui saat form diisi
                        </span>
                    </div>

                    <div class="bg-[#F3F4F6] rounded-2xl border border-gray-200/60 p-4 sm:p-5 flex justify-center items-start overflow-x-auto min-h-[760px]">
                        <!-- A4 Paper Box -->
                        <div class="bg-white shadow-[0_10px_35px_#00000008] border border-gray-200/80 rounded-xs w-full max-w-[520px] p-6 sm:p-8 text-gray-900 select-text font-serif text-[10.5px] leading-relaxed">
                            <!-- Kop Surat -->
                            <div class="relative flex items-center justify-center min-h-[60px] mb-2">
                                <div class="absolute left-0 top-0 w-11 h-11 flex items-center justify-center">
                                    <img
                                        v-if="props.school?.logo_path"
                                        :src="props.school.logo_path"
                                        alt="Logo"
                                        class="w-11 h-11 object-contain"
                                    />
                                    <div
                                        v-else
                                        class="w-11 h-11 border border-dashed border-gray-300 flex items-center justify-center text-[9px] text-gray-400 font-mono"
                                    >
                                        LOGO
                                    </div>
                                </div>

                                <div class="text-center px-12 w-full">
                                    <h2 class="text-xs sm:text-[13px] font-bold tracking-wider uppercase font-serif text-gray-900 leading-tight">
                                        {{ props.school?.name || 'NAMA SEKOLAH' }}
                                    </h2>
                                    <p class="text-[9px] text-gray-500 font-serif italic mt-0.5 leading-snug">
                                        {{ [props.school?.address, props.school?.city, props.school?.province, props.school?.postal_code].filter(Boolean).join(', ') }}
                                    </p>
                                    <p v-if="metaText" class="text-[8.5px] text-gray-500 font-serif mt-0.5 leading-snug">
                                        {{ metaText }}
                                    </p>
                                </div>
                            </div>

                            <!-- Kop Divider Line -->
                            <div v-if="props.school?.template_style === 'formal_minimal'" class="mt-2.5 mb-4">
                                <div class="h-[1px] bg-gray-300 w-full"></div>
                            </div>
                            <div v-else class="mt-2.5 mb-4">
                                <div class="h-[2px] bg-black w-full"></div>
                                <div class="h-[1px] bg-black w-full mt-[1.5px]"></div>
                            </div>

                            <!-- Document Title & Number -->
                            <div class="text-center my-4">
                                <h3 class="font-bold underline uppercase text-xs tracking-wide">
                                    {{ form.subject || 'DOKUMEN RESMI KESISWAAN' }}
                                </h3>
                                <p class="text-[9.5px] font-mono text-gray-600 mt-0.5">
                                    Nomor: {{ props.school?.classification_code || '421.3' }}/001/{{ props.school?.school_code || 'SEKOLAH' }}/{{ currentYear }}
                                </p>
                            </div>

                            <!-- PREVIEW BODY: Document Type Specific -->
                            <!-- TYPE 1: Surat Pemberitahuan Orang Tua -->
                            <div v-if="activeType === 'parent_notification'" class="space-y-2.5">
                                <div class="text-[10px] text-gray-800">
                                    <p>Kepada Yth.</p>
                                    <p class="font-bold">Bapak/Ibu Orang Tua / Wali Peserta Didik</p>
                                    <p>di Tempat</p>
                                </div>
                                <p class="text-[10px] leading-relaxed">
                                    Dengan hormat, sehubungan dengan pemantauan kedisiplinan peserta didik, sekolah menyampaikan informasi mengenai:
                                </p>
                                <table class="w-full text-[10px] my-2">
                                    <tbody>
                                        <tr>
                                            <td class="w-20 text-gray-600">Nama Siswa</td>
                                            <td class="w-2 text-center">:</td>
                                            <td class="font-bold">{{ activeStudent.name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-600">NIS / Kelas</td>
                                            <td class="text-center">:</td>
                                            <td>{{ activeStudent.nis }} / {{ activeStudent.class_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-600">Orang Tua/Wali</td>
                                            <td class="text-center">:</td>
                                            <td>{{ activeStudent.parent_name }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="grid grid-cols-3 gap-2 my-2 text-center">
                                    <div class="border border-red-200 bg-red-50/50 p-1 rounded text-[9.5px]">
                                        <span class="text-red-700 font-bold">{{ activeStudent.violation_points }} pt</span>
                                        <span class="text-[8.5px] text-gray-500 block">Pelanggaran</span>
                                    </div>
                                    <div class="border border-emerald-200 bg-emerald-50/50 p-1 rounded text-[9.5px]">
                                        <span class="text-emerald-700 font-bold">{{ activeStudent.achievement_points }} pt</span>
                                        <span class="text-[8.5px] text-gray-500 block">Prestasi</span>
                                    </div>
                                    <div class="border border-indigo-200 bg-indigo-50/50 p-1 rounded text-[9.5px]">
                                        <span class="text-indigo-700 font-bold">{{ activeStudent.net_points }} pt</span>
                                        <span class="text-[8.5px] text-gray-500 block">Poin Bersih</span>
                                    </div>
                                </div>
                                <div class="text-[9.5px] space-y-1">
                                    <p><strong>1. Ringkasan Kejadian:</strong> {{ form.incident_summary || 'Terdapat akumulasi catatan ketidakdisiplinan yang memerlukan pembinaan.' }}</p>
                                    <p><strong>2. Tindakan Sekolah:</strong> {{ form.school_action }}</p>
                                    <p><strong>3. Harapan Orang Tua:</strong> {{ form.expected_follow_up }}</p>
                                </div>
                            </div>

                            <!-- TYPE 2: Surat Perjanjian Siswa -->
                            <div v-else-if="activeType === 'student_agreement'" class="space-y-2.5">
                                <p class="text-[10px]">Yang bertanda tangan di bawah ini:</p>
                                <table class="w-full text-[10px] my-1">
                                    <tbody>
                                        <tr>
                                            <td class="w-20 text-gray-600">Nama Siswa</td>
                                            <td class="w-2 text-center">:</td>
                                            <td class="font-bold">{{ activeStudent.name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-600">NIS / Kelas</td>
                                            <td class="text-center">:</td>
                                            <td>{{ activeStudent.nis }} / {{ activeStudent.class_name }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="text-[9.5px] leading-relaxed">
                                    Menyatakan berjanji dan berkomitmen sehubungan dengan: <em>{{ form.problem_summary || 'Pelanggaran tata tertib sekolah.' }}</em>
                                </p>
                                <div class="bg-gray-50 p-2 rounded border border-gray-200 text-[9px] font-mono whitespace-pre-line leading-relaxed">
                                    {{ form.student_commitments }}
                                </div>
                                <p class="text-[9.5px]">
                                    <strong>Masa Evaluasi:</strong> {{ form.evaluation_period }}
                                </p>
                            </div>

                            <!-- TYPE 3: Surat Panggilan Orang Tua -->
                            <div v-else-if="activeType === 'parent_call'" class="space-y-2.5">
                                <div class="text-[10px] text-gray-800">
                                    <p>Kepada Yth.</p>
                                    <p class="font-bold">Bapak/Ibu Orang Tua / Wali Peserta Didik</p>
                                    <p>di Tempat</p>
                                </div>
                                <p class="text-[10px] leading-relaxed">
                                    Mengharap kehadiran Bapak/Ibu pada agenda pemanggilan orang tua siswa:
                                </p>
                                <table class="w-full text-[10px] my-1">
                                    <tbody>
                                        <tr>
                                            <td class="w-24 text-gray-600">Nama Siswa</td>
                                            <td class="w-2 text-center">:</td>
                                            <td class="font-bold">{{ activeStudent.name }} ({{ activeStudent.class_name }})</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-600">Hari, Tanggal</td>
                                            <td class="text-center">:</td>
                                            <td class="font-bold">{{ form.meeting_day_date }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-600">Waktu</td>
                                            <td class="text-center">:</td>
                                            <td>{{ form.meeting_time }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-600">Tempat</td>
                                            <td class="text-center">:</td>
                                            <td>{{ form.meeting_venue }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="text-[9.5px]"><strong>Alasan:</strong> {{ form.reason }}</p>
                            </div>

                            <!-- TYPE 4: Riwayat Kedisiplinan -->
                            <div v-else class="space-y-2.5">
                                <table class="w-full text-[10px] my-1">
                                    <tbody>
                                        <tr>
                                            <td class="w-20 text-gray-600">Nama Siswa</td>
                                            <td class="w-2 text-center">:</td>
                                            <td class="font-bold">{{ activeStudent.name }}</td>
                                            <td class="w-20 text-gray-600">Kelas</td>
                                            <td class="w-2 text-center">:</td>
                                            <td>{{ activeStudent.class_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-600">NIS</td>
                                            <td class="text-center">:</td>
                                            <td>{{ activeStudent.nis }}</td>
                                            <td class="text-gray-600">Wali Kelas</td>
                                            <td class="text-center">:</td>
                                            <td>{{ activeStudent.homeroom_teacher }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="grid grid-cols-3 gap-2 my-2 text-center">
                                    <div class="border border-red-200 bg-red-50/50 p-1 rounded text-[9.5px]">
                                        <span class="text-red-700 font-bold">{{ activeStudent.violation_points }} pt</span>
                                        <span class="text-[8.5px] text-gray-500 block">Pelanggaran</span>
                                    </div>
                                    <div class="border border-emerald-200 bg-emerald-50/50 p-1 rounded text-[9.5px]">
                                        <span class="text-emerald-700 font-bold">{{ activeStudent.achievement_points }} pt</span>
                                        <span class="text-[8.5px] text-gray-500 block">Prestasi</span>
                                    </div>
                                    <div class="border border-indigo-200 bg-indigo-50/50 p-1 rounded text-[9.5px]">
                                        <span class="text-indigo-700 font-bold">{{ activeStudent.net_points }} pt</span>
                                        <span class="text-[8.5px] text-gray-500 block">Poin Bersih</span>
                                    </div>
                                </div>
                                <p class="text-[9.5px] italic text-gray-600">
                                    Tabel riwayat kronologis lengkap dan pengesahan dicetak penuh pada dokumen PDF final.
                                </p>
                            </div>

                            <!-- Preview Signatory Block -->
                            <div class="mt-6 flex justify-end font-serif">
                                <div class="text-center w-44 text-[10px] text-gray-800 space-y-0.5 relative">
                                    <p>{{ props.school?.letter_city || 'Ponorogo' }}, {{ todayFormatted }}</p>
                                    <p class="font-medium">{{ calculatedSignatory.title }}</p>

                                    <div class="h-12 flex items-center justify-center relative my-0.5">
                                        <img
                                            v-if="props.school?.show_stamp && props.school?.stamp_path"
                                            :src="props.school.stamp_path"
                                            alt="Stempel"
                                            class="absolute -left-2 h-11 w-11 object-contain opacity-80 pointer-events-none"
                                        />
                                        <img
                                            v-if="props.school?.show_signature_image && props.school?.signature_path"
                                            :src="props.school.signature_path"
                                            alt="Tanda Tangan"
                                            class="h-11 object-contain"
                                        />
                                    </div>

                                    <p class="font-bold underline leading-tight">
                                        {{ calculatedSignatory.name }}
                                    </p>
                                    <p v-if="calculatedSignatory.nip" class="text-[9px] text-gray-500">
                                        NIP. {{ calculatedSignatory.nip }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
