<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import { buildParentCallWhatsAppMessage, buildWhatsAppUrl } from '../../utils';

interface StudentItem {
    id: number;
    name: string;
    nis: string;
    class_name: string;
    parent_name?: string;
    parent_phone?: string;
    address?: string;
    violation_points: number;
    achievement_points: number;
    net_points: number;
}

interface SchoolItem {
    id?: number;
    name: string;
    address: string;
    city?: string;
    province?: string;
    postal_code?: string;
    npsn?: string;
    phone?: string;
    email?: string;
    website?: string;
    principal_name?: string;
    principal_nip?: string;
    vice_principal_name?: string;
    counseling_officer_name?: string;
    signer_title?: string;
    letter_city?: string;
    classification_code?: string;
    school_code?: string;
    template_style?: string;
    show_npsn?: boolean;
    show_phone?: boolean;
    show_email?: boolean;
    show_website?: boolean;
    show_signature_image?: boolean;
    show_stamp?: boolean;
    logo_path?: string;
    signature_path?: string;
    stamp_path?: string;
}

const props = defineProps<{
    students: StudentItem[];
    school: SchoolItem;
    activeSemester: any;
    defaultStudentId?: number | null;
    letters?: any;
    initialTab?: 'generate' | 'template' | 'archive';
}>();

const toast = useToast();
const page = usePage();

const userRoles = computed<string[]>(() => {
    return (page.props.auth as any)?.roles || [];
});

const canConfigureKop = computed(() => {
    return userRoles.value.includes('Super Admin') || userRoles.value.includes('Tata Tertib') || userRoles.value.includes('BK') || userRoles.value.includes('Principal');
});

const canIssueOfficialLetters = computed(() => {
    return userRoles.value.includes('Super Admin') || userRoles.value.includes('BK') || userRoles.value.includes('Tata Tertib') || userRoles.value.includes('Principal');
});

// Tab state: 'generate' (Penerbitan), 'template' (Pengaturan Kop), 'archive' (Arsip Surat)
const activeTab = ref<'generate' | 'template' | 'archive'>(
    props.initialTab === 'template' && !canConfigureKop.value ? 'generate' : (props.initialTab || 'generate')
);

// ================= TAB 1: PENERBITAN SURAT & DOKUMEN =================
const docTypes = [
    { value: 'notification', label: 'Surat Pemberitahuan Orang Tua', title: 'SURAT PEMBERITAHUAN ORANG TUA/WALI' },
    { value: 'call_1', label: 'Surat Panggilan Orang Tua I', title: 'SURAT PANGGILAN ORANG TUA/WALI I' },
    { value: 'call_2', label: 'Surat Panggilan Orang Tua II', title: 'SURAT PANGGILAN ORANG TUA/WALI II' },
    { value: 'call_3', label: 'Surat Panggilan Orang Tua III', title: 'SURAT PANGGILAN ORANG TUA/WALI III' },
    { value: 'statement', label: 'Surat Perjanjian Siswa', title: 'SURAT PERJANJIAN KEDISIPLINAN SISWA' },
    { value: 'recap', label: 'Rekapitulasi Disiplin Siswa', title: 'LAPORAN REKAPITULASI KEDISIPLINAN SISWA' },
];

const selectedType = ref<string>('call_1');
const selectedStudentId = ref<number | ''>(props.defaultStudentId || '');
const notes = ref<string>('');

// Meeting fields for Parent Call letters
const meeting_day_date = ref<string>('');
const meeting_time = ref<string>('08.00 WIB s/d Selesai');
const meeting_venue = ref<string>('Ruang BK / Ruang Tata Tertib');
const meeting_purpose = ref<string>('Koordinasi dan pembinaan kedisiplinan siswa');

const isCallLetter = computed(() => selectedType.value.startsWith('call_'));

const currentStudent = computed(() => {
    if (!selectedStudentId.value) return null;
    return props.students.find(s => s.id === Number(selectedStudentId.value)) || null;
});

const currentDocTitle = computed(() => {
    const found = docTypes.find(d => d.value === selectedType.value);
    return found ? found.title : 'SURAT PEMBERITAHUAN ORANG TUA/WALI';
});

const currentCallHal = computed(() => {
    if (selectedType.value === 'call_1') return 'Panggilan Orang Tua / Wali Murid I';
    if (selectedType.value === 'call_2') return 'Panggilan Orang Tua / Wali Murid II';
    if (selectedType.value === 'call_3') return 'Panggilan Orang Tua / Wali Murid III';
    return 'Panggilan Orang Tua / Wali Murid';
});

const signatoryInfo = computed(() => {
    if (selectedType.value === 'call_1') {
        return {
            title: props.school?.counseling_officer_name ? 'Guru BK / Koordinator BK' : (props.school?.signer_title || 'Guru BK'),
            name: props.school?.counseling_officer_name || props.school?.principal_name || 'Koordinator BK',
            nip: props.school?.principal_nip || '-',
            isKepsek: false,
        };
    }
    if (selectedType.value === 'call_2') {
        return {
            title: props.school?.vice_principal_name ? 'Wakil Kepala Sekolah Bidang Kesiswaan' : (props.school?.signer_title || 'Waka Kesiswaan'),
            name: props.school?.vice_principal_name || props.school?.principal_name || 'Waka Kesiswaan',
            nip: props.school?.principal_nip || '-',
            isKepsek: false,
        };
    }
    if (selectedType.value === 'call_3') {
        return {
            title: 'Kepala Sekolah',
            name: props.school?.principal_name || 'Kepala Sekolah',
            nip: props.school?.principal_nip || '-',
            isKepsek: true,
        };
    }
    return {
        title: props.school?.signer_title || 'Kepala Sekolah',
        name: props.school?.principal_name || 'Kepala Sekolah',
        nip: props.school?.principal_nip || '-',
        isKepsek: true,
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

const previewMetaText = computed(() => {
    const parts: string[] = [];
    if ((props.school?.show_npsn ?? true) && props.school?.npsn) {
        parts.push(`NPSN ${props.school.npsn}`);
    }
    if ((props.school?.show_phone ?? true) && props.school?.phone) {
        parts.push(`Telp. ${props.school.phone}`);
    }
    if ((props.school?.show_email ?? true) && props.school?.email) {
        parts.push(props.school.email);
    }
    if ((props.school?.show_website ?? false) && props.school?.website) {
        parts.push(props.school.website);
    }
    return parts.join(' · ');
});

const downloadDocument = (format: 'pdf' | 'word') => {
    let studentId = selectedStudentId.value;

    if (!studentId) {
        if (props.students.length > 0) {
            studentId = props.students[0].id;
            selectedStudentId.value = studentId;
        } else {
            toast.add({
                severity: 'warn',
                summary: 'Peringatan',
                detail: 'Silakan pilih siswa terlebih dahulu',
                life: 3000,
            });
            return;
        }
    }

    const params = new URLSearchParams({
        student_id: String(studentId),
        type: selectedType.value,
        notes: notes.value || '',
        format: format,
    });

    if (isCallLetter.value) {
        if (meeting_day_date.value) params.set('meeting_day_date', meeting_day_date.value);
        if (meeting_time.value) params.set('meeting_time', meeting_time.value);
        if (meeting_venue.value) params.set('meeting_venue', meeting_venue.value);
        if (meeting_purpose.value) params.set('meeting_purpose', meeting_purpose.value);
    }

    window.open(`/reports/generate?${params.toString()}`, '_blank');
};

const sendWhatsAppNotification = () => {
    if (!currentStudent.value) {
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
        studentName: currentStudent.value.name,
        studentNis: currentStudent.value.nis,
        className: currentStudent.value.class_name,
        letterSubject: currentDocTitle.value,
        meetingDayDate: meeting_day_date.value || undefined,
        meetingTime: meeting_time.value || undefined,
        meetingVenue: meeting_venue.value || undefined,
        meetingPurpose: meeting_purpose.value || undefined,
        notes: notes.value || undefined,
    });

    const phone = currentStudent.value.parent_phone;
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

const sendArchiveLetterWhatsApp = (letItem: any) => {
    const student = letItem.student;
    const phone = student?.parent_phone;
    const meta = letItem.metadata || {};

    const message = buildParentCallWhatsAppMessage({
        schoolName: props.school?.name || 'Sekolah',
        studentName: student?.name || 'Siswa',
        studentNis: student?.nis,
        className: student?.class_room?.name,
        letterNumber: letItem.letter_number,
        letterSubject: letItem.subject,
        meetingDayDate: meta.meeting_day_date,
        meetingTime: meta.meeting_time,
        meetingVenue: meta.meeting_venue,
        meetingPurpose: meta.notes,
    });

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

// ================= TAB 2: PENGATURAN KOP SURAT & LEGALITAS =================
const templateForm = ref({
    name: props.school?.name || 'SMP Malang',
    npsn: props.school?.npsn || '',
    phone: props.school?.phone || '',
    address: props.school?.address || '',
    city: props.school?.city || '',
    province: props.school?.province || 'Jawa Timur',
    postal_code: props.school?.postal_code || '',
    email: props.school?.email || '',
    website: props.school?.website || '',
    principal_name: props.school?.principal_name || '',
    principal_nip: props.school?.principal_nip || '',
    signer_title: props.school?.signer_title || 'Kepala Sekolah',
    letter_city: props.school?.letter_city || props.school?.city || 'Malang',
    classification_code: props.school?.classification_code || '421.3',
    school_code: props.school?.school_code || 'SMP-MALANG',
    template_style: props.school?.template_style || 'formal_indonesia',
    show_npsn: props.school?.show_npsn ?? true,
    show_phone: props.school?.show_phone ?? true,
    show_email: props.school?.show_email ?? true,
    show_website: props.school?.show_website ?? false,
    show_signature_image: props.school?.show_signature_image ?? true,
    show_stamp: props.school?.show_stamp ?? false,
});

const isSavingTemplate = ref(false);

const logoFile = ref<File | null>(null);
const logoPreview = ref<string | null>(props.school?.logo_path || null);
const logoInput = ref<HTMLInputElement | null>(null);

const signatureFile = ref<File | null>(null);
const signaturePreview = ref<string | null>(props.school?.signature_path || null);
const signatureInput = ref<HTMLInputElement | null>(null);

const stampFile = ref<File | null>(null);
const stampPreview = ref<string | null>(props.school?.stamp_path || null);
const stampInput = ref<HTMLInputElement | null>(null);

const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        logoFile.value = target.files[0];
        logoPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const handleSignatureChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        signatureFile.value = target.files[0];
        signaturePreview.value = URL.createObjectURL(target.files[0]);
    }
};

const handleStampChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        stampFile.value = target.files[0];
        stampPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const templateEditorMetaText = computed(() => {
    const parts: string[] = [];
    if (templateForm.value.show_npsn && templateForm.value.npsn) {
        parts.push(`NPSN ${templateForm.value.npsn}`);
    }
    if (templateForm.value.show_phone && templateForm.value.phone) {
        parts.push(`Telp. ${templateForm.value.phone}`);
    }
    if (templateForm.value.show_email && templateForm.value.email) {
        parts.push(templateForm.value.email);
    }
    if (templateForm.value.show_website && templateForm.value.website) {
        parts.push(templateForm.value.website);
    }
    return parts.join(' · ');
});

const saveTemplateSettings = () => {
    isSavingTemplate.value = true;
    const payload = new FormData();

    Object.entries(templateForm.value).forEach(([key, val]) => {
        if (typeof val === 'boolean') {
            payload.append(key, val ? '1' : '0');
        } else if (val !== null && val !== undefined) {
            payload.append(key, String(val));
        }
    });

    if (logoFile.value) payload.append('logo', logoFile.value);
    if (signatureFile.value) payload.append('signature', signatureFile.value);
    if (stampFile.value) payload.append('stamp', stampFile.value);

    router.post('/letters/template', payload, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isSavingTemplate.value = false;
            toast.add({
                severity: 'success',
                summary: 'Berhasil Disimpan',
                detail: 'Pengaturan kop surat dan legalitas berhasil disinkronkan dengan dokumen.',
                life: 3000,
            });
        },
        onError: (errors) => {
            isSavingTemplate.value = false;
            const firstErr = Object.values(errors)[0] || 'Gagal menyimpan template kop surat.';
            toast.add({
                severity: 'error',
                summary: 'Gagal Menyimpan',
                detail: String(firstErr),
                life: 5000,
            });
        },
    });
};

// ================= TAB 3: RIWAYAT ARSIP SURAT =================
const getTypeBadge = (type: string) => {
    switch (type) {
        case 'parent_notification':
        case 'notification':
            return { label: 'Pemberitahuan', bg: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'student_agreement':
        case 'statement':
            return { label: 'Perjanjian Siswa', bg: 'bg-purple-50 text-purple-700 border-purple-200' };
        case 'call_1':
            return { label: 'Panggilan I (BK)', bg: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'call_2':
            return { label: 'Panggilan II (Waka)', bg: 'bg-orange-50 text-orange-700 border-orange-200' };
        case 'call_3':
            return { label: 'Panggilan III (Kepsek)', bg: 'bg-red-50 text-red-700 border-red-200' };
        case 'parent_call':
            return { label: 'Panggilan Ortu', bg: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'recap':
            return { label: 'Rekap Disiplin', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        default:
            return { label: type, bg: 'bg-gray-50 text-gray-700 border-gray-200' };
    }
};
</script>

<template>
    <Head title="Surat & Dokumen Siswa - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-gray-900 leading-tight">Surat & Dokumen</span>
                <span class="text-xs font-medium text-gray-400">{{ school?.name || 'SMP Malang' }}</span>
            </div>
        </template>

        <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-6">
            <!-- Header Section & Action Bar -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div>
                    <span class="text-[11px] font-bold text-[#6366F1] tracking-wider uppercase block">
                        MANAJEMEN SURAT & DOKUMEN RESMI
                    </span>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-0.5">
                        Surat & Dokumen Siswa
                    </h1>
                    <p class="text-xs text-gray-500 mt-1 max-w-2xl">
                        Penerbitan surat panggilan dinas, pengaturan kop surat sekolah, dan pengarsipan surat dalam satu tempat yang terpadu.
                    </p>
                </div>

                <!-- Action Button in Tab 1 (Quick Download) -->
                <div v-if="activeTab === 'generate'" class="flex items-center gap-2.5 shrink-0 flex-wrap">
                    <button
                        type="button"
                        @click="sendWhatsAppNotification"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-[0_4px_14px_#05966935] transition-all cursor-pointer active:scale-95"
                        title="Kirim pemberitahuan resmi ke nomor WhatsApp Orang Tua"
                    >
                        <i class="pi pi-whatsapp text-xs"></i>
                        <span>Kirim WA Ortu</span>
                    </button>

                    <button
                        type="button"
                        @click="downloadDocument('word')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-xs transition-all cursor-pointer active:scale-95"
                    >
                        <i class="pi pi-download text-xs text-blue-600"></i>
                        <span>Unduh Word (.doc)</span>
                    </button>

                    <button
                        type="button"
                        @click="downloadDocument('pdf')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-[0_4px_14px_#6366F135] transition-all cursor-pointer active:scale-95"
                    >
                        <i class="pi pi-print text-xs"></i>
                        <span>Unduh PDF Resmi</span>
                    </button>
                </div>

                <!-- Action Button in Tab 2 (Save Kop) -->
                <div v-else-if="activeTab === 'template'" class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="activeTab = 'generate'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-xs transition-all cursor-pointer"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                        <span>Kembali ke Dokumen</span>
                    </button>

                    <button
                        type="button"
                        @click="saveTemplateSettings"
                        :disabled="isSavingTemplate"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-xs transition-all cursor-pointer active:scale-95 disabled:opacity-50"
                    >
                        <i :class="isSavingTemplate ? 'pi pi-spin pi-spinner' : 'pi pi-check'" class="text-xs"></i>
                        <span>{{ isSavingTemplate ? 'Menyimpan...' : 'Simpan Pengaturan Kop' }}</span>
                    </button>
                </div>
            </div>

            <!-- UNIFIED TAB SWITCHER -->
            <div class="flex items-center gap-2 border-b border-gray-200 mb-6 overflow-x-auto [scrollbar-width:none]">
                <button
                    type="button"
                    @click="activeTab = 'generate'"
                    :class="activeTab === 'generate' ? 'border-[#6366F1] text-[#6366F1] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                    class="py-3 px-4 border-b-2 text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
                >
                    <i class="pi pi-file-edit text-xs"></i>
                    <span>Cetak Surat & Dokumen</span>
                </button>

                <button
                    v-if="canConfigureKop"
                    type="button"
                    @click="activeTab = 'template'"
                    :class="activeTab === 'template' ? 'border-[#6366F1] text-[#6366F1] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                    class="py-3 px-4 border-b-2 text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
                >
                    <i class="pi pi-sliders-h text-xs"></i>
                    <span>Pengaturan Kop Surat & Legalitas</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'archive'"
                    :class="activeTab === 'archive' ? 'border-[#6366F1] text-[#6366F1] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                    class="py-3 px-4 border-b-2 text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
                >
                    <i class="pi pi-folder-open text-xs"></i>
                    <span>Arsip Dokumen Terbit ({{ letters?.total ?? letters?.data?.length ?? (letters?.length ?? 0) }})</span>
                </button>
            </div>

            <!-- ================= TAB 1: PENERBITAN SURAT & DOKUMEN ================= -->
            <div v-if="activeTab === 'generate'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column: Form Panel -->
                <div class="lg:col-span-4 xl:col-span-4 space-y-4">
                    <!-- Card 1: Data Utama Dokumen -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Data Dokumen</h3>
                            <button
                                v-if="canConfigureKop"
                                type="button"
                                @click="activeTab = 'template'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-[#6366F1] text-[11px] font-semibold transition-colors cursor-pointer"
                                title="Buka pengaturan kop surat"
                            >
                                <i class="pi pi-sliders-h text-[10px]"></i>
                                <span>Atur Kop</span>
                            </button>
                        </div>

                        <!-- Jenis Dokumen -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Jenis Dokumen
                            </label>
                            <select
                                v-model="selectedType"
                                class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all cursor-pointer"
                            >
                                <option v-for="doc in docTypes" :key="doc.value" :value="doc.value">
                                    {{ doc.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Pilih Siswa -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Pilih Peserta Didik
                            </label>
                            <select
                                v-model="selectedStudentId"
                                class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all cursor-pointer"
                            >
                                <option value="" disabled>-- Pilih Siswa --</option>
                                <option v-for="st in students" :key="st.id" :value="st.id">
                                    {{ st.name }} (NIS: {{ st.nis }}) - {{ st.class_name }}
                                </option>
                            </select>
                        </div>

                        <!-- Badge Ringkasan Siswa Terpilih -->
                        <div v-if="currentStudent" class="bg-gray-50 border border-gray-100 rounded-xl p-3.5 space-y-1.5 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Kelas:</span>
                                <span class="font-bold text-gray-900">{{ currentStudent.class_name }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Orang Tua / Wali:</span>
                                <span class="font-medium text-gray-800 truncate max-w-[180px]">{{ currentStudent.parent_name || 'Orang Tua Murid' }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-gray-200/60">
                                <span class="text-gray-500">Poin Pelanggaran:</span>
                                <span class="font-bold text-red-600">{{ currentStudent.violation_points }} Poin</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Jadwal Kehadiran (Khusus Surat Panggilan) -->
                    <div v-if="isCallLetter" class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-3.5">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#6366F1]"></span>
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Jadwal Kehadiran Orang Tua</h3>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Hari, Tanggal
                            </label>
                            <input
                                v-model="meeting_day_date"
                                type="text"
                                :placeholder="'Contoh: ' + todayFormatted"
                                class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all placeholder:text-gray-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Waktu
                            </label>
                            <input
                                v-model="meeting_time"
                                type="text"
                                placeholder="08.00 WIB s/d Selesai"
                                class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all placeholder:text-gray-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Tempat
                            </label>
                            <input
                                v-model="meeting_venue"
                                type="text"
                                placeholder="Ruang BK / Ruang Tata Tertib"
                                class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all placeholder:text-gray-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Keperluan / Menemui
                            </label>
                            <input
                                v-model="meeting_purpose"
                                type="text"
                                placeholder="Koordinasi dan pembinaan kedisiplinan siswa"
                                class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all placeholder:text-gray-400"
                            />
                        </div>
                    </div>

                    <!-- Card 3: Catatan Tambahan -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004]">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Catatan Tambahan
                        </label>
                        <textarea
                            v-model="notes"
                            rows="2"
                            placeholder="Opsional. Hal penting yang perlu dibawa orang tua."
                            class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all placeholder:text-gray-400 resize-y"
                        ></textarea>
                    </div>

                    <!-- Info Box: Status Kop Sinkron -->
                    <div class="bg-[#F5F3FF] border border-[#DDD6FE] rounded-2xl p-4 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center shrink-0 mt-0.5">
                            <i class="pi pi-check-circle text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-gray-900 leading-tight">
                                Kop Surat Tersinkronisasi Penuh
                            </h4>
                            <p class="text-[11px] text-gray-600 mt-1 leading-relaxed">
                                Nomor surat, logo, garis dinas, dan penandatangan di pratinjau ini langsung mengikuti konfigurasi kop dinas resmi sekolah.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Document Live Preview Canvas -->
                <div class="lg:col-span-8 xl:col-span-8 bg-[#F3F4F6] rounded-2xl border border-gray-200/60 p-4 sm:p-10 flex justify-center items-start overflow-x-auto min-h-[780px]">
                    <!-- A4 Document Paper Sheet -->
                    <div class="bg-white shadow-[0_10px_35px_#00000008] border border-gray-200/80 rounded-sm w-full max-w-[640px] p-8 sm:p-12 text-gray-900 select-text font-serif">
                        <!-- Kop Surat -->
                        <div class="relative flex items-center justify-center min-h-[64px] mb-2">
                            <!-- Logo Box -->
                            <div class="absolute left-0 top-0 w-14 h-14 flex items-center justify-center">
                                <img
                                    v-if="school?.logo_path"
                                    :src="school.logo_path"
                                    alt="Logo"
                                    class="w-14 h-14 object-contain"
                                />
                                <div
                                    v-else
                                    class="w-14 h-14 border border-dashed border-gray-300 flex items-center justify-center text-[10px] text-gray-400 font-mono tracking-wider"
                                >
                                    LOGO
                                </div>
                            </div>
                            <!-- Centered School Info -->
                            <div class="text-center px-16 w-full">
                                <h2 class="text-base sm:text-lg font-bold tracking-wider uppercase text-gray-900 leading-tight">
                                    {{ school?.name || 'SMP MALANG' }}
                                </h2>
                                <p class="text-[11px] text-gray-600 italic mt-0.5 leading-snug">
                                    {{ [school?.address, school?.city, school?.province, school?.postal_code].filter(Boolean).join(', ') || 'Alamat sekolah' }}
                                </p>
                                <p v-if="previewMetaText" class="text-[10px] text-gray-500 mt-0.5 leading-snug">
                                    {{ previewMetaText }}
                                </p>
                            </div>
                        </div>

                        <!-- Divider Line Kop (Sesuai Gaya Pengaturan) -->
                        <div v-if="school?.template_style === 'formal_minimal'" class="mt-3 mb-5">
                            <div class="h-[1px] bg-gray-300 w-full"></div>
                        </div>
                        <div v-else class="mt-3 mb-5">
                            <div class="h-[2px] bg-black w-full"></div>
                            <div class="h-[1px] bg-black w-full mt-[2px]"></div>
                        </div>

                        <!-- ================= SURAT PANGGILAN ORANG TUA FORMAT DINAS ================= -->
                        <div v-if="isCallLetter">
                            <!-- Metadata Table: Nomor, Lamp., Hal (Kiri) & Tanggal (Kanan) -->
                            <table class="w-full mb-4 border-collapse text-xs sm:text-[13px] leading-tight">
                                <tbody>
                                    <tr>
                                        <td class="w-3/5 align-top">
                                            <table class="border-collapse">
                                                <tbody>
                                                    <tr>
                                                        <td class="w-16 py-0.5 text-gray-700">Nomor</td>
                                                        <td class="w-3 py-0.5 text-center">:</td>
                                                        <td class="py-0.5 text-gray-900 font-mono text-[11px] sm:text-xs">
                                                            {{ school?.classification_code || '421.3' }} / _____ / {{ school?.school_code || 'SMP-MALANG' }} / {{ new Date().getFullYear() }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-0.5 text-gray-700">Lamp.</td>
                                                        <td class="py-0.5 text-center">:</td>
                                                        <td class="py-0.5 text-gray-900">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-0.5 text-gray-800 font-semibold">Hal</td>
                                                        <td class="py-0.5 text-center font-semibold">:</td>
                                                        <td class="py-0.5 text-gray-900 font-semibold underline">{{ currentCallHal }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td class="w-2/5 align-top text-right text-gray-800 text-xs sm:text-[13px]">
                                            {{ school?.letter_city || school?.city || 'Malang' }}, {{ todayFormatted }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Penerima Surat -->
                            <div class="mb-4 text-xs sm:text-[13px] leading-relaxed text-gray-900">
                                <p>Kepada :</p>
                                <p>Yth. Bapak/Ibu Wali Murid dari <strong>{{ currentStudent?.name || 'Nama Siswa' }}</strong></p>
                                <p>Di Tempat</p>
                            </div>

                            <!-- Salam & Pembuka -->
                            <div class="mb-3 text-xs sm:text-[13px] leading-relaxed text-gray-900">
                                <p class="mb-1">Assalamu’alaikum wr. wb.</p>
                                <p class="text-justify leading-relaxed">
                                    Mengharap dengan hormat atas kehadiran Bapak/Ibu wali murid dari :
                                </p>
                            </div>

                            <!-- Rincian Siswa -->
                            <div class="my-3 pl-4 text-xs sm:text-[13px]">
                                <table class="w-full border-collapse">
                                    <tbody>
                                        <tr>
                                            <td class="w-28 sm:w-32 py-1 text-gray-700">Nama Siswa</td>
                                            <td class="w-4 py-1 text-center">:</td>
                                            <td class="py-1 font-bold text-gray-900">{{ currentStudent?.name || 'Nama Siswa' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700">Kelas</td>
                                            <td class="py-1 text-center">:</td>
                                            <td class="py-1 text-gray-900">{{ currentStudent?.class_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700">NIS / NISN</td>
                                            <td class="py-1 text-center">:</td>
                                            <td class="py-1 text-gray-900">{{ currentStudent?.nis || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700 align-top">Alamat</td>
                                            <td class="py-1 text-center align-top">:</td>
                                            <td class="py-1 text-gray-900">{{ currentStudent?.address || 'Tempat tinggal siswa' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ketentuan Kehadiran -->
                            <div class="mt-3 mb-2 text-xs sm:text-[13px] text-gray-900">
                                <p class="text-justify">Untuk hadir dengan ketentuan sebagai berikut :</p>
                            </div>

                            <div class="my-2 pl-4 text-xs sm:text-[13px]">
                                <table class="w-full border-collapse">
                                    <tbody>
                                        <tr>
                                            <td class="w-28 sm:w-32 py-1 text-gray-700">Hari, tanggal</td>
                                            <td class="w-4 py-1 text-center">:</td>
                                            <td class="py-1 font-bold text-gray-900">{{ meeting_day_date || todayFormatted }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700">Waktu</td>
                                            <td class="py-1 text-center">:</td>
                                            <td class="py-1 text-gray-900">{{ meeting_time || '08.00 WIB s/d Selesai' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700">Tempat</td>
                                            <td class="py-1 text-center">:</td>
                                            <td class="py-1 text-gray-900">{{ meeting_venue || 'Ruang BK / Ruang Tata Tertib' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700 align-top">Keterangan</td>
                                            <td class="py-1 text-center align-top">:</td>
                                            <td class="py-1 text-gray-900">
                                                Menemui {{ signatoryInfo.title }}
                                                <div v-if="meeting_purpose || notes" class="text-gray-600 mt-0.5 italic">
                                                    ({{ meeting_purpose || notes }})
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Penutup -->
                            <div class="mt-4 mb-3 text-xs sm:text-[13px] leading-relaxed text-gray-900">
                                <p class="text-justify mb-2">
                                    Demikian surat ini disampaikan, atas perhatian dan kehadirannya kami ucapkan terima kasih.
                                </p>
                                <p>Wassalamu’alaikum Wr. Wb.</p>
                            </div>

                            <!-- Tanda Tangan Berjenjang -->
                            <div class="mt-6 flex justify-end">
                                <div class="text-center w-56 text-xs sm:text-[13px] text-gray-900 space-y-0.5 relative">
                                    <p v-if="!signatoryInfo.isKepsek" class="leading-tight">a.n. Kepala Sekolah</p>
                                    <p class="font-medium leading-tight">{{ signatoryInfo.title }},</p>

                                    <div class="h-16 flex items-center justify-center relative my-1">
                                        <img
                                            v-if="school?.show_stamp && school?.stamp_path"
                                            :src="school.stamp_path"
                                            alt="Stempel"
                                            class="absolute -left-2 h-14 w-14 object-contain opacity-80 pointer-events-none"
                                        />
                                        <img
                                            v-if="school?.show_signature_image && school?.signature_path"
                                            :src="school.signature_path"
                                            alt="Tanda Tangan"
                                            class="h-14 object-contain"
                                        />
                                    </div>

                                    <p class="font-bold underline leading-tight">
                                        {{ signatoryInfo.name }}
                                    </p>
                                    <p v-if="signatoryInfo.nip && signatoryInfo.nip !== '-'" class="text-[11px] text-gray-600">
                                        NIP. {{ signatoryInfo.nip }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ================= SURAT STANDAR / PEMBERITAHUAN / REKAP ================= -->
                        <div v-else>
                            <!-- Document Title & Number -->
                            <div class="text-center my-6">
                                <h3 class="font-bold underline uppercase text-xs sm:text-sm tracking-wider text-gray-900">
                                    {{ currentDocTitle }}
                                </h3>
                                <p class="text-xs text-gray-700 mt-1">
                                    Nomor: {{ school?.classification_code || '421.3' }} / _____ / {{ school?.school_code || 'SMP-MALANG' }} / {{ new Date().getFullYear() }}
                                </p>
                            </div>

                            <!-- Recipient & Intro -->
                            <div class="text-xs text-gray-800 space-y-1 my-4 leading-relaxed">
                                <p>Yth. Bapak/Ibu Orang Tua/Wali Peserta Didik</p>
                                <p>di Tempat</p>
                                <p class="pt-2">Dengan hormat,</p>
                                <p class="leading-relaxed">
                                    Sehubungan dengan proses pembinaan dan pemantauan kedisiplinan peserta didik, sekolah menyampaikan informasi mengenai:
                                </p>
                            </div>

                            <!-- Student Data Table -->
                            <div class="my-4 text-xs">
                                <table class="w-full border-collapse">
                                    <tbody>
                                        <tr>
                                            <td class="w-28 py-1 text-gray-700">Nama Siswa</td>
                                            <td class="w-4 py-1 text-center">:</td>
                                            <td class="py-1 font-bold text-gray-900">{{ currentStudent?.name || 'Nama Siswa' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700">NIS / NISN</td>
                                            <td class="py-1 text-center">:</td>
                                            <td class="py-1 text-gray-900">{{ currentStudent?.nis || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700">Kelas</td>
                                            <td class="py-1 text-center">:</td>
                                            <td class="py-1 text-gray-900">{{ currentStudent?.class_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-gray-700">Orang Tua / Wali</td>
                                            <td class="py-1 text-center">:</td>
                                            <td class="py-1 text-gray-900">{{ currentStudent?.parent_name || 'Nama Orang Tua/Wali' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- 3 Metric Boxes -->
                            <div class="grid grid-cols-3 gap-3 my-5">
                                <div class="border border-gray-300 rounded-sm p-3 text-center bg-gray-50/50">
                                    <span class="block text-[10px] text-gray-600 uppercase font-sans">Pelanggaran</span>
                                    <span class="block text-sm sm:text-base font-bold text-red-600 mt-0.5">
                                        {{ currentStudent?.violation_points || 0 }} poin
                                    </span>
                                </div>
                                <div class="border border-gray-300 rounded-sm p-3 text-center bg-gray-50/50">
                                    <span class="block text-[10px] text-gray-600 uppercase font-sans">Prestasi</span>
                                    <span class="block text-sm sm:text-base font-bold text-emerald-600 mt-0.5">
                                        {{ currentStudent?.achievement_points || 0 }} poin
                                    </span>
                                </div>
                                <div class="border border-gray-300 rounded-sm p-3 text-center bg-gray-50/50">
                                    <span class="block text-[10px] text-gray-600 uppercase font-sans">Poin Bersih</span>
                                    <span class="block text-sm sm:text-base font-bold text-gray-900 mt-0.5">
                                        {{ currentStudent?.net_points || 0 }} poin
                                    </span>
                                </div>
                            </div>

                            <!-- Catatan -->
                            <div v-if="notes" class="my-4 p-3 bg-amber-50/50 border border-amber-200/60 rounded text-xs text-gray-800">
                                <span class="font-bold block mb-0.5">Catatan Khusus:</span>
                                <p>{{ notes }}</p>
                            </div>

                            <!-- Closing -->
                            <div class="mt-4 mb-6 text-xs text-gray-800 leading-relaxed">
                                <p>
                                    Demikian surat pemberitahuan ini kami sampaikan. Atas perhatian dan kerja sama yang baik dari Bapak/Ibu, kami ucapkan terima kasih.
                                </p>
                            </div>

                            <!-- Signature -->
                            <div class="mt-6 flex justify-end">
                                <div class="text-center w-52 text-xs text-gray-900 space-y-0.5 relative">
                                    <p>{{ school?.letter_city || school?.city || 'Malang' }}, {{ todayFormatted }}</p>
                                    <p class="font-medium">{{ school?.signer_title || 'Kepala Sekolah' }},</p>

                                    <div class="h-16 flex items-center justify-center relative my-1">
                                        <img
                                            v-if="school?.show_stamp && school?.stamp_path"
                                            :src="school.stamp_path"
                                            alt="Stempel"
                                            class="absolute -left-2 h-14 w-14 object-contain opacity-80 pointer-events-none"
                                        />
                                        <img
                                            v-if="school?.show_signature_image && school?.signature_path"
                                            :src="school.signature_path"
                                            alt="Tanda Tangan"
                                            class="h-14 object-contain"
                                        />
                                    </div>

                                    <p class="font-bold underline leading-tight">
                                        {{ school?.principal_name || 'Kepala Sekolah' }}
                                    </p>
                                    <p v-if="school?.principal_nip" class="text-[11px] text-gray-600">
                                        NIP. {{ school.principal_nip }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 2: PENGATURAN KOP SURAT & LEGALITAS ================= -->
            <div v-else-if="activeTab === 'template'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column: Settings Form Panels -->
                <div class="lg:col-span-7 xl:col-span-7 space-y-6">
                    <!-- SECTION 1: Identitas & Berkas Kop -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#6366F1] flex items-center justify-center shrink-0">
                                <i class="pi pi-image text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Identitas & Berkas Kop Sekolah</h2>
                                <span class="text-[11px] text-gray-400">Data dan logo ini otomatis terpasang pada semua surat resmi sekolah.</span>
                            </div>
                        </div>

                        <!-- 3 Upload Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Logo Upload -->
                            <div
                                @click="logoInput?.click()"
                                class="border border-dashed border-gray-200 rounded-xl p-3.5 flex flex-col items-center justify-center text-center hover:border-[#6366F1] hover:bg-[#FBFBFF] transition-all cursor-pointer group bg-[#F9FAFB]/50 relative overflow-hidden min-h-[105px]"
                            >
                                <input
                                    ref="logoInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleLogoChange"
                                />
                                <div v-if="logoPreview" class="flex flex-col items-center">
                                    <img :src="logoPreview" alt="Logo" class="h-10 object-contain mb-1" />
                                    <span class="text-[10px] text-[#6366F1] font-medium group-hover:underline">Ubah Logo</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                                        <i class="pi pi-upload text-xs"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-800">Logo Sekolah</span>
                                    <span class="text-[10px] text-gray-400 mt-0.5">PNG/JPG transparan</span>
                                </div>
                            </div>

                            <!-- Tanda Tangan Upload -->
                            <div
                                @click="signatureInput?.click()"
                                class="border border-dashed border-gray-200 rounded-xl p-3.5 flex flex-col items-center justify-center text-center hover:border-[#6366F1] hover:bg-[#FBFBFF] transition-all cursor-pointer group bg-[#F9FAFB]/50 relative overflow-hidden min-h-[105px]"
                            >
                                <input
                                    ref="signatureInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleSignatureChange"
                                />
                                <div v-if="signaturePreview" class="flex flex-col items-center">
                                    <img :src="signaturePreview" alt="Tanda Tangan" class="h-10 object-contain mb-1" />
                                    <span class="text-[10px] text-[#6366F1] font-medium group-hover:underline">Ubah TTD</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                                        <i class="pi pi-upload text-xs"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-800">Tanda Tangan</span>
                                    <span class="text-[10px] text-gray-400 mt-0.5">Scan PNG transparan</span>
                                </div>
                            </div>

                            <!-- Stempel Upload -->
                            <div
                                @click="stampInput?.click()"
                                class="border border-dashed border-gray-200 rounded-xl p-3.5 flex flex-col items-center justify-center text-center hover:border-[#6366F1] hover:bg-[#FBFBFF] transition-all cursor-pointer group bg-[#F9FAFB]/50 relative overflow-hidden min-h-[105px]"
                            >
                                <input
                                    ref="stampInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleStampChange"
                                />
                                <div v-if="stampPreview" class="flex flex-col items-center">
                                    <img :src="stampPreview" alt="Stempel" class="h-10 object-contain mb-1" />
                                    <span class="text-[10px] text-[#6366F1] font-medium group-hover:underline">Ubah Stempel</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-lg bg-[#EDE9FE] text-[#6366F1] flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                                        <i class="pi pi-upload text-xs"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-800">Stempel Sekolah</span>
                                    <span class="text-[10px] text-gray-400 mt-0.5">Opsional</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fields -->
                        <div class="space-y-3.5 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Instansi Sekolah</label>
                                <input
                                    v-model="templateForm.name"
                                    type="text"
                                    class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">NPSN</label>
                                    <input
                                        v-model="templateForm.npsn"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nomor Telepon</label>
                                    <input
                                        v-model="templateForm.phone"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Alamat Lengkap</label>
                                <input
                                    v-model="templateForm.address"
                                    type="text"
                                    class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kota / Kabupaten</label>
                                    <input
                                        v-model="templateForm.city"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Provinsi</label>
                                    <input
                                        v-model="templateForm.province"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Pos</label>
                                    <input
                                        v-model="templateForm.postal_code"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Email Sekolah</label>
                                    <input
                                        v-model="templateForm.email"
                                        type="email"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Website Resmi</label>
                                    <input
                                        v-model="templateForm.website"
                                        type="text"
                                        placeholder="Opsional"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all placeholder:text-gray-400"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Penandatangan Resmi -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#6366F1] flex items-center justify-center shrink-0">
                                <i class="pi pi-user text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Pejabat Penandatangan Resmi</h2>
                                <span class="text-[11px] text-gray-400">Data penandatangan dan lokasi penulisan surat dinas.</span>
                            </div>
                        </div>

                        <div class="space-y-3.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Kepala Sekolah</label>
                                    <input
                                        v-model="templateForm.principal_name"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">NIP Kepala Sekolah</label>
                                    <input
                                        v-model="templateForm.principal_nip"
                                        type="text"
                                        placeholder="Opsional"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all placeholder:text-gray-400"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Jabatan Penandatangan Default</label>
                                    <input
                                        v-model="templateForm.signer_title"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kota pada Surat</label>
                                    <input
                                        v-model="templateForm.letter_city"
                                        type="text"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Penomoran Dokumen -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#6366F1] flex items-center justify-center shrink-0">
                                <i class="pi pi-file text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Format Penomoran Dokumen</h2>
                                <span class="text-[11px] text-gray-400">Kode klasifikasi dan identitas kode sekolah untuk penomoran otomatis.</span>
                            </div>
                        </div>

                        <div class="space-y-3.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Klasifikasi</label>
                                    <input
                                        v-model="templateForm.classification_code"
                                        type="text"
                                        placeholder="Contoh: 421.3"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all font-mono"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Sekolah</label>
                                    <input
                                        v-model="templateForm.school_code"
                                        type="text"
                                        placeholder="Contoh: SMP-MALANG"
                                        class="w-full text-xs sm:text-sm bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 focus:border-[#6366F1] transition-all font-mono uppercase"
                                    />
                                </div>
                            </div>

                            <div class="bg-[#F9FAFB] rounded-xl p-4 border border-gray-100">
                                <span class="text-[10px] font-bold text-gray-400 tracking-wider uppercase block">
                                    CONTOH FORMAT NOMOR SURAT
                                </span>
                                <div class="text-sm font-bold text-gray-900 font-mono mt-1">
                                    {{ templateForm.classification_code || '421.3' }}/001/{{ templateForm.school_code || 'SMP-MALANG' }}/{{ new Date().getFullYear() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: Tampilan & Visibilitas -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004] space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#F5F3FF] text-[#6366F1] flex items-center justify-center shrink-0">
                                <i class="pi pi-sliders-h text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-gray-900 leading-tight">Gaya Kop & Visibilitas Informasi</h2>
                                <span class="text-[11px] text-gray-400">Pilih gaya garis pembatas dan elemen yang ditampilkan.</span>
                            </div>
                        </div>

                        <!-- Gaya Kop Selector -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                            <div
                                @click="templateForm.template_style = 'formal_indonesia'"
                                :class="templateForm.template_style === 'formal_indonesia' ? 'border-[#6366F1] bg-[#FBFBFF] ring-2 ring-[#6366F1]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                                class="rounded-xl border p-4 cursor-pointer transition-all flex items-start gap-3 relative"
                            >
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex flex-col items-center justify-center gap-0.5 shrink-0 p-1.5">
                                    <div class="w-full h-1 bg-[#6366F1] rounded-xs"></div>
                                    <div class="w-full h-0.5 bg-gray-600 rounded-xs"></div>
                                </div>
                                <div class="flex-1 min-w-0 pr-6">
                                    <h3 class="text-xs font-bold text-gray-900 leading-tight">Formal Indonesia</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Kop dinas tegas dengan garis ganda tebal-tipis</p>
                                </div>
                                <div v-if="templateForm.template_style === 'formal_indonesia'" class="absolute top-3 right-3 text-[#6366F1]">
                                    <i class="pi pi-check-circle text-base"></i>
                                </div>
                            </div>

                            <div
                                @click="templateForm.template_style = 'formal_minimal'"
                                :class="templateForm.template_style === 'formal_minimal' ? 'border-[#6366F1] bg-[#FBFBFF] ring-2 ring-[#6366F1]/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                                class="rounded-xl border p-4 cursor-pointer transition-all flex items-start gap-3 relative"
                            >
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex flex-col items-center justify-center shrink-0 p-2">
                                    <div class="w-full h-0.5 bg-gray-400 rounded-xs"></div>
                                </div>
                                <div class="flex-1 min-w-0 pr-6">
                                    <h3 class="text-xs font-bold text-gray-900 leading-tight">Formal Minimalis</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Garis pembatas tunggal bersih dan modern</p>
                                </div>
                                <div v-if="templateForm.template_style === 'formal_minimal'" class="absolute top-3 right-3 text-[#6366F1]">
                                    <i class="pi pi-check-circle text-base"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Checkboxes Visibilitas -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 select-none">
                                <input v-model="templateForm.show_npsn" type="checkbox" class="rounded text-[#6366F1] focus:ring-[#6366F1]" />
                                <span>Tampilkan NPSN</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 select-none">
                                <input v-model="templateForm.show_phone" type="checkbox" class="rounded text-[#6366F1] focus:ring-[#6366F1]" />
                                <span>Tampilkan Telepon</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 select-none">
                                <input v-model="templateForm.show_email" type="checkbox" class="rounded text-[#6366F1] focus:ring-[#6366F1]" />
                                <span>Tampilkan Email</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 select-none">
                                <input v-model="templateForm.show_website" type="checkbox" class="rounded text-[#6366F1] focus:ring-[#6366F1]" />
                                <span>Tampilkan Website</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 select-none">
                                <input v-model="templateForm.show_signature_image" type="checkbox" class="rounded text-[#6366F1] focus:ring-[#6366F1]" />
                                <span>Tampilkan TTD Digital</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 select-none">
                                <input v-model="templateForm.show_stamp" type="checkbox" class="rounded text-[#6366F1] focus:ring-[#6366F1]" />
                                <span>Tampilkan Stempel</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Kop Preview Card in Editor -->
                <div class="lg:col-span-5 xl:col-span-5 sticky top-24 space-y-4">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-900">Pratinjau Langsung Kop</span>
                            <span class="text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Sinkron Real-time</span>
                        </div>

                        <!-- Pratinjau Kop Interaktif -->
                        <div class="p-6 bg-white border border-gray-200/60 rounded-xl select-none font-serif">
                            <div class="relative flex items-center justify-center min-h-[56px] mb-2">
                                <div class="absolute left-0 top-0 w-12 h-12 flex items-center justify-center">
                                    <img
                                        v-if="logoPreview || templateForm.logo_path"
                                        :src="logoPreview || templateForm.logo_path"
                                        alt="Logo"
                                        class="w-12 h-12 object-contain"
                                    />
                                    <div v-else class="w-12 h-12 border border-dashed border-gray-300 flex items-center justify-center text-[9px] text-gray-400 font-mono">
                                        LOGO
                                    </div>
                                </div>
                                <div class="text-center px-14 w-full">
                                    <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-gray-900 leading-tight">
                                        {{ templateForm.name || 'SMP MALANG' }}
                                    </h2>
                                    <p class="text-[10px] text-gray-500 italic mt-0.5 leading-snug">
                                        {{ [templateForm.address, templateForm.city, templateForm.province, templateForm.postal_code].filter(Boolean).join(', ') }}
                                    </p>
                                    <p v-if="templateEditorMetaText" class="text-[9px] text-gray-500 mt-0.5 leading-snug">
                                        {{ templateEditorMetaText }}
                                    </p>
                                </div>
                            </div>

                            <!-- Line Divider -->
                            <div v-if="templateForm.template_style === 'formal_minimal'" class="mt-2.5 mb-4">
                                <div class="h-[1px] bg-gray-300 w-full"></div>
                            </div>
                            <div v-else class="mt-2.5 mb-4">
                                <div class="h-[2px] bg-black w-full"></div>
                                <div class="h-[1px] bg-black w-full mt-[1.5px]"></div>
                            </div>

                            <!-- Preview Signature -->
                            <div class="mt-8 flex justify-end text-[11px]">
                                <div class="text-center w-48 space-y-0.5">
                                    <p>{{ templateForm.letter_city || 'Malang' }}, {{ todayFormatted }}</p>
                                    <p class="font-medium">{{ templateForm.signer_title || 'Kepala Sekolah' }}</p>
                                    <div class="h-14 flex items-center justify-center relative my-1">
                                        <img
                                            v-if="templateForm.show_stamp && (stampPreview || templateForm.stamp_path)"
                                            :src="stampPreview || templateForm.stamp_path"
                                            alt="Stempel"
                                            class="absolute -left-2 h-12 w-12 object-contain opacity-80 pointer-events-none"
                                        />
                                        <img
                                            v-if="templateForm.show_signature_image && (signaturePreview || templateForm.signature_path)"
                                            :src="signaturePreview || templateForm.signature_path"
                                            alt="Tanda Tangan"
                                            class="h-12 object-contain"
                                        />
                                    </div>
                                    <p class="font-bold underline leading-tight">{{ templateForm.principal_name || 'Kepala Sekolah' }}</p>
                                    <p v-if="templateForm.principal_nip" class="text-[10px] text-gray-500">NIP. {{ templateForm.principal_nip }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-end">
                            <button
                                type="button"
                                @click="saveTemplateSettings"
                                :disabled="isSavingTemplate"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-xs transition-all cursor-pointer active:scale-95 disabled:opacity-50"
                            >
                                <i :class="isSavingTemplate ? 'pi pi-spin pi-spinner' : 'pi pi-check'" class="text-xs"></i>
                                <span>{{ isSavingTemplate ? 'Menyimpan Pengaturan...' : 'Simpan Pengaturan Kop' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 3: RIWAYAT ARSIP DOKUMEN TERBIT ================= -->
            <div v-else-if="activeTab === 'archive'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-[0_2px_12px_#00000004]">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Daftar Dokumen Resmi Terbit</h2>
                            <p class="text-xs text-gray-500">Riwayat arsip surat panggilan dan laporan kedisiplinan yang telah diterbitkan.</p>
                        </div>
                        <button
                            type="button"
                            @click="activeTab = 'generate'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold shadow-xs transition-all cursor-pointer"
                        >
                            <i class="pi pi-plus text-xs"></i>
                            <span>Buat Dokumen Baru</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/75 text-gray-600 border-b border-gray-100 uppercase text-[10px] tracking-wider font-semibold">
                                <tr>
                                    <th class="py-3 px-3.5">Nomor Surat</th>
                                    <th class="py-3 px-3.5">Jenis Dokumen</th>
                                    <th class="py-3 px-3.5">Peserta Didik</th>
                                    <th class="py-3 px-3.5">Perihal</th>
                                    <th class="py-3 px-3.5">Tanggal Terbit</th>
                                    <th class="py-3 px-3.5 text-right">Aksi Unduh</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="letItem in (letters?.data || letters || [])" :key="letItem.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-3.5 font-mono font-medium text-gray-900 whitespace-nowrap">
                                        {{ letItem.letter_number }}
                                    </td>
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        <span :class="['px-2 py-0.5 rounded-md border text-[10px] font-semibold', getTypeBadge(letItem.type).bg]">
                                            {{ getTypeBadge(letItem.type).label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ letItem.student?.name || '-' }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">NIS: {{ letItem.student?.nis || '-' }} · {{ letItem.student?.class_room?.name || 'Tanpa Kelas' }}</div>
                                    </td>
                                    <td class="py-3 px-3.5 text-gray-700 max-w-xs truncate">
                                        {{ letItem.subject || 'Dokumen Resmi' }}
                                    </td>
                                    <td class="py-3 px-3.5 text-gray-500 whitespace-nowrap">
                                        {{ new Date(letItem.generated_at || letItem.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </td>
                                    <td class="py-3 px-3.5 text-right whitespace-nowrap space-x-1.5">
                                        <button
                                            type="button"
                                            @click="sendArchiveLetterWhatsApp(letItem)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-medium transition-colors cursor-pointer"
                                            title="Kirim ringkasan surat ke WhatsApp Orang Tua"
                                        >
                                            <i class="pi pi-whatsapp text-xs text-emerald-600"></i>
                                            <span>WA</span>
                                        </button>
                                        <a
                                            v-if="canIssueOfficialLetters"
                                            :href="'/letters/' + letItem.id + '/download'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-[11px] font-medium transition-colors"
                                            title="Unduh PDF Resmi"
                                        >
                                            <i class="pi pi-file-pdf text-xs text-red-500"></i>
                                            <span>PDF</span>
                                        </a>
                                        <a
                                            v-if="canIssueOfficialLetters"
                                            :href="'/letters/' + letItem.id + '/word'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-[11px] font-medium transition-colors"
                                            title="Unduh Word Editable"
                                        >
                                            <i class="pi pi-file-word text-xs text-blue-500"></i>
                                            <span>Word</span>
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="!(letters?.data?.length || (Array.isArray(letters) && letters.length))">
                                    <td colspan="6" class="py-8 text-center text-gray-400 text-xs">
                                        Belum ada dokumen yang diterbitkan. Silakan buat dokumen pada tab "Cetak Surat & Dokumen".
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
