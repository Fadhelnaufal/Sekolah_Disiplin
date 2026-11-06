<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    student?: {
        id: number;
        nis: string;
        name: string;
        class_room_id: number;
        parent_name: string | null;
        parent_phone: string | null;
        status: string;
        notes: string | null;
    };
    classRooms: Array<{
        id: number;
        name: string;
    }>;
}>();

const isEditing = !!props.student;

const form = useForm({
    nis: props.student?.nis || '',
    name: props.student?.name || '',
    class_room_id: props.student?.class_room_id || '',
    parent_name: props.student?.parent_name || '',
    parent_phone: props.student?.parent_phone || '',
    status: props.student?.status || 'Active',
    notes: props.student?.notes || '',
});

const submit = () => {
    if (isEditing) {
        form.put(`/students/${props.student!.id}`);
    } else {
        form.post('/students');
    }
};

const selectedClassName = computed(() => {
    if (!form.class_room_id) return 'Belum dipilih';
    const found = props.classRooms.find(c => c.id === Number(form.class_room_id));
    return found ? found.name : 'Kelas #' + form.class_room_id;
});

const getStatusBadge = (status: string) => {
    switch ((status || '').toLowerCase()) {
        case 'active':
        case 'aktif':
            return { label: 'Aktif', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'lulus':
            return { label: 'Lulus', class: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'pindah':
            return { label: 'Pindah', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'dikeluarkan':
            return { label: 'Dikeluarkan', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'cuti':
            return { label: 'Cuti', class: 'bg-gray-100 text-gray-700 border-gray-200' };
        default:
            return { label: status || 'Aktif', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    }
};
</script>

<template>
    <Head :title="isEditing ? `Edit ${form.name || 'Siswa'} - Sekolah Disiplin` : 'Registrasi Siswa Baru - Sekolah Disiplin'" />

    <AuthenticatedLayout>
        <!-- Breadcrumb Header -->
        <template #header>
            <div class="flex items-center gap-2.5 w-full pr-4 text-sm font-medium">
                <Link href="/students" class="text-gray-500 hover:text-gray-900 transition-colors">Data Siswa</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">{{ isEditing ? 'Edit Profil Siswa' : 'Registrasi Siswa Baru' }}</span>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200/60 pb-5">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">
                            {{ isEditing ? 'Edit Profil Siswa' : 'Registrasi Siswa Baru' }}
                        </h1>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-[#001AFF] border border-indigo-100">
                            {{ isEditing ? 'Mode Pembaruan' : 'Workspace Registrasi' }}
                        </span>
                    </div>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ isEditing ? 'Perbarui identitas pokok, penempatan rombel, dan informasi kontak wali murid.' : 'Daftarkan data pokok siswa, penempatan rombongan belajar, serta kontak wali murid ke dalam sistem.' }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        href="/students"
                        class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-xs flex items-center gap-1.5"
                    >
                        <i class="pi pi-arrow-left text-[11px]"></i>
                        <span>Kembali ke Direktori</span>
                    </Link>
                    <Link
                        v-if="isEditing && props.student"
                        :href="`/students/${props.student.id}`"
                        class="px-4 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs flex items-center gap-1.5"
                    >
                        <i class="pi pi-user text-[11px]"></i>
                        <span>Lihat Profil Siswa</span>
                    </Link>
                </div>
            </div>

            <!-- Main Workspace Grid: 12 Cols -->
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Column: Form Sections (8 Cols) -->
                <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                    
                    <!-- Section 1: Identitas Pokok Siswa -->
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-[#001AFF] flex items-center justify-center font-bold text-xs">
                                    <i class="pi pi-id-card"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Identitas Pokok Siswa</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Informasi nama resmi dan nomor identitas kesiswaan</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-400 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">
                                Wajib diisi
                            </span>
                        </div>

                        <div class="space-y-4">
                            <!-- Nama Lengkap -->
                            <div>
                                <label for="name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Nama Lengkap Siswa <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="pi pi-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Contoh: Muhammad Farhan Al-Fatih"
                                        class="block w-full pl-10 pr-4 py-3 bg-gray-50/70 border border-gray-200 rounded-xl text-gray-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent transition-all placeholder:text-gray-400"
                                        :class="{ 'border-rose-400 bg-rose-50/30 focus:ring-rose-500': form.errors.name }"
                                    />
                                </div>
                                <p v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-rose-600 flex items-center gap-1">
                                    <i class="pi pi-exclamation-circle text-xs"></i>
                                    <span>{{ form.errors.name }}</span>
                                </p>
                                <p v-else class="mt-1 text-[11px] text-gray-400">
                                    Gunakan nama lengkap resmi sesuai dokumen kartu keluarga atau rapor jenjang sebelumnya.
                                </p>
                            </div>

                            <!-- NIS & Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                                <div>
                                    <label for="nis" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                        NIS (Nomor Induk Siswa) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i class="pi pi-hashtag absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                        <input
                                            id="nis"
                                            v-model="form.nis"
                                            type="text"
                                            required
                                            placeholder="Contoh: 20261001"
                                            class="block w-full pl-10 pr-4 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-gray-900 text-sm font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent transition-all placeholder:text-gray-400"
                                            :class="{ 'border-rose-400 bg-rose-50/30 focus:ring-rose-500': form.errors.nis }"
                                        />
                                    </div>
                                    <p v-if="form.errors.nis" class="mt-1.5 text-xs font-medium text-rose-600 flex items-center gap-1">
                                        <i class="pi pi-exclamation-circle text-xs"></i>
                                        <span>{{ form.errors.nis }}</span>
                                    </p>
                                    <p v-else class="mt-1 text-[11px] text-gray-400">
                                        Nomor induk bersifat unik di lingkungan sekolah.
                                    </p>
                                </div>

                                <!-- Status Kesiswaan -->
                                <div>
                                    <label for="status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                        Status Kesiswaan <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select
                                            id="status"
                                            v-model="form.status"
                                            required
                                            class="block w-full pl-4 pr-10 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-gray-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent transition-all appearance-none cursor-pointer"
                                            :class="{ 'border-rose-400 bg-rose-50/30 focus:ring-rose-500': form.errors.status }"
                                        >
                                            <option value="Active">Aktif</option>
                                            <option value="Lulus">Lulus</option>
                                            <option value="Pindah">Pindah Sekolah</option>
                                            <option value="Dikeluarkan">Dikeluarkan</option>
                                            <option value="Cuti">Cuti</option>
                                        </select>
                                        <i class="pi pi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                                    </div>
                                    <p v-if="form.errors.status" class="mt-1.5 text-xs font-medium text-rose-600 flex items-center gap-1">
                                        <i class="pi pi-exclamation-circle text-xs"></i>
                                        <span>{{ form.errors.status }}</span>
                                    </p>
                                    <p v-else class="mt-1 text-[11px] text-gray-400">
                                        Status menentukan hak akses siswa dalam rekap poin aktif.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Penempatan Kelas & Rombel -->
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                                    <i class="pi pi-building"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Penempatan Kelas & Rombel</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Penetapan rombongan belajar aktif siswa</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="class_room_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Kelas / Rombongan Belajar <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    id="class_room_id"
                                    v-model="form.class_room_id"
                                    required
                                    class="block w-full pl-4 pr-10 py-3 bg-gray-50/70 border border-gray-200 rounded-xl text-gray-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent transition-all appearance-none cursor-pointer"
                                    :class="{ 'border-rose-400 bg-rose-50/30 focus:ring-rose-500': form.errors.class_room_id }"
                                >
                                    <option value="" disabled>Pilih Kelas / Rombel...</option>
                                    <option v-for="c in classRooms" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                                <i class="pi pi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                            <p v-if="form.errors.class_room_id" class="mt-1.5 text-xs font-medium text-rose-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-xs"></i>
                                <span>{{ form.errors.class_room_id }}</span>
                            </p>
                            <div v-else class="mt-2 flex items-center gap-1.5 text-[11px] text-gray-500">
                                <i class="pi pi-info-circle text-gray-400 text-xs"></i>
                                <span>Wali kelas terkait akan otomatis memantau riwayat kedisiplinan siswa di kelas ini.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Kontak Orang Tua / Wali -->
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                    <i class="pi pi-users"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Kontak Orang Tua / Wali</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Saluran komunikasi pemanggilan dan notifikasi kedisiplinan</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-100">
                                Sangat Dianjurkan
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Nama Orang Tua / Wali -->
                            <div>
                                <label for="parent_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Nama Orang Tua / Wali
                                </label>
                                <div class="relative">
                                    <i class="pi pi-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input
                                        id="parent_name"
                                        v-model="form.parent_name"
                                        type="text"
                                        placeholder="Contoh: H. Ahmad Subarjo"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent transition-all placeholder:text-gray-400"
                                        :class="{ 'border-rose-400 bg-rose-50/30 focus:ring-rose-500': form.errors.parent_name }"
                                    />
                                </div>
                                <p v-if="form.errors.parent_name" class="mt-1.5 text-xs font-medium text-rose-600 flex items-center gap-1">
                                    <i class="pi pi-exclamation-circle text-xs"></i>
                                    <span>{{ form.errors.parent_name }}</span>
                                </p>
                                <p v-else class="mt-1 text-[11px] text-gray-400">
                                    Nama wali yang akan tercantum pada surat resmi pemanggilan.
                                </p>
                            </div>

                            <!-- No. WhatsApp / Telepon -->
                            <div>
                                <label for="parent_phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    No. WhatsApp / Telepon Wali
                                </label>
                                <div class="relative">
                                    <i class="pi pi-whatsapp absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-600 text-sm"></i>
                                    <input
                                        id="parent_phone"
                                        v-model="form.parent_phone"
                                        type="tel"
                                        placeholder="Contoh: 081234567890"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent transition-all placeholder:text-gray-400 font-mono"
                                        :class="{ 'border-rose-400 bg-rose-50/30 focus:ring-rose-500': form.errors.parent_phone }"
                                    />
                                </div>
                                <p v-if="form.errors.parent_phone" class="mt-1.5 text-xs font-medium text-rose-600 flex items-center gap-1">
                                    <i class="pi pi-exclamation-circle text-xs"></i>
                                    <span>{{ form.errors.parent_phone }}</span>
                                </p>
                                <p v-else class="mt-1 text-[11px] text-emerald-600 font-medium">
                                    Mendukung pengiriman pesan WhatsApp otomatis saat pelanggaran atau prestasi dicatat.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Catatan Khusus Kesiswaan -->
                    <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">
                                    <i class="pi pi-file-edit"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Catatan Khusus Kesiswaan</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Informasi pendukung, kondisi kesehatan, atau riwayat pendampingan</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-400 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">
                                Opsional
                            </span>
                        </div>

                        <div>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Tuliskan catatan bila ada, misalnya: alergi tertentu, riwayat bimbingan konseling awal, beasiswa, atau catatan khusus..."
                                class="block w-full p-3.5 bg-gray-50/70 border border-gray-200 rounded-xl text-gray-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent transition-all placeholder:text-gray-400 resize-y"
                                :class="{ 'border-rose-400 bg-rose-50/30 focus:ring-rose-500': form.errors.notes }"
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1.5 text-xs font-medium text-rose-600 flex items-center gap-1">
                                <i class="pi pi-exclamation-circle text-xs"></i>
                                <span>{{ form.errors.notes }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Mobile Action Footer -->
                    <div class="lg:hidden pt-4 flex items-center justify-end gap-3">
                        <Link
                            href="/students"
                            class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 text-xs font-bold text-white bg-[#001AFF] hover:bg-[#0015cc] rounded-xl transition-all shadow-sm flex items-center gap-2 disabled:opacity-60 cursor-pointer"
                        >
                            <i v-if="!form.processing" :class="isEditing ? 'pi pi-check' : 'pi pi-user-plus'" class="text-xs"></i>
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Daftarkan Siswa' }}</span>
                        </button>
                    </div>

                </div>

                <!-- Right Column: Live Identity Preview & Actions (4 Cols Sticky) -->
                <div class="lg:col-span-5 xl:col-span-4 space-y-6 lg:sticky lg:top-24">
                    
                    <!-- Live Student Identity Card Preview -->
                    <div class="bg-gradient-to-br from-[#001AFF] to-[#3730A3] rounded-[24px] p-6 text-white shadow-md space-y-5 relative overflow-hidden">
                        <div class="absolute -right-12 -bottom-12 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>
                        <div class="absolute -left-10 -top-10 w-36 h-36 rounded-full bg-white/5 pointer-events-none"></div>

                        <div class="relative z-10 flex items-center justify-between border-b border-white/15 pb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-200">Pratinjau Kartu Siswa</span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-white/15 text-white">
                                {{ getStatusBadge(form.status).label }}
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="relative z-10 space-y-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-14 h-14 rounded-2xl bg-white text-[#001AFF] font-extrabold text-xl flex items-center justify-center shrink-0 shadow-sm border border-white/20">
                                    {{ (form.name || 'S').charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-white truncate leading-snug">
                                        {{ form.name || 'Nama Lengkap Siswa' }}
                                    </h3>
                                    <p class="text-xs text-blue-200 font-mono mt-0.5">
                                        NIS: {{ form.nis || 'Belum diisi' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Details Table in Card -->
                            <div class="bg-black/15 rounded-xl p-3.5 space-y-2.5 text-xs backdrop-blur-xs border border-white/10">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-blue-200 text-[11px]">Kelas / Rombel</span>
                                    <span class="font-bold text-white truncate max-w-[150px]">{{ selectedClassName }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2 border-t border-white/10 pt-2">
                                    <span class="text-blue-200 text-[11px]">Wali Murid</span>
                                    <span class="font-bold text-white truncate max-w-[150px]">{{ form.parent_name || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2 border-t border-white/10 pt-2">
                                    <span class="text-blue-200 text-[11px]">No. WhatsApp</span>
                                    <span class="font-mono text-white truncate max-w-[150px]">{{ form.parent_phone || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="relative z-10 pt-1 text-[11px] text-blue-100/80 flex items-center gap-1.5">
                            <i class="pi pi-sparkles text-xs text-amber-300"></i>
                            <span>Pratinjau otomatis menyesuaikan data formulir secara real-time.</span>
                        </div>
                    </div>

                    <!-- Actions & Checklist Card -->
                    <div class="bg-white rounded-[24px] p-6 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-4">
                        <div class="border-b border-gray-100 pb-3">
                            <h3 class="text-sm font-bold text-gray-900">Aksi Formulir</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Pastikan kelengkapan data sebelum menyimpan</p>
                        </div>

                        <!-- Guidance Checklist -->
                        <div class="space-y-2 text-xs text-gray-600">
                            <div class="flex items-start gap-2">
                                <i :class="form.name ? 'pi pi-check text-emerald-600' : 'pi pi-circle text-gray-300'" class="text-xs mt-0.5 shrink-0"></i>
                                <span :class="{ 'font-semibold text-gray-900': form.name }">Nama lengkap terisi</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i :class="form.nis ? 'pi pi-check text-emerald-600' : 'pi pi-circle text-gray-300'" class="text-xs mt-0.5 shrink-0"></i>
                                <span :class="{ 'font-semibold text-gray-900': form.nis }">NIS unik terisi</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i :class="form.class_room_id ? 'pi pi-check text-emerald-600' : 'pi pi-circle text-gray-300'" class="text-xs mt-0.5 shrink-0"></i>
                                <span :class="{ 'font-semibold text-gray-900': form.class_room_id }">Kelas rombel dipilih</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 space-y-2.5">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3 px-4 text-xs font-bold text-white bg-[#001AFF] hover:bg-[#0015cc] active:bg-[#0012b3] rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <i v-else :class="isEditing ? 'pi pi-check' : 'pi pi-user-plus'" class="text-xs"></i>
                                <span>{{ isEditing ? 'Simpan Perubahan Profil' : 'Daftarkan Siswa Sekarang' }}</span>
                            </button>

                            <Link
                                href="/students"
                                class="w-full py-2.5 px-4 text-xs font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 border border-gray-200 rounded-xl transition-all flex items-center justify-center gap-1.5"
                            >
                                <span>Batal & Kembali</span>
                            </Link>
                        </div>
                    </div>

                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
