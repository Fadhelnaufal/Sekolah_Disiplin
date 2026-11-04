<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

interface UserProfile {
    id: number;
    name: string;
    email: string;
    created_at: string;
    roles: string[];
    teacher: {
        id: number;
        name: string;
        employee_number: string | null;
        phone: string | null;
        status: string;
        homeroom_classes: string[];
    } | null;
}

const props = defineProps<{
    user: UserProfile;
}>();

// Form Update Profile
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.teacher?.phone || '',
});

const submitProfile = () => {
    profileForm.put('/profile', {
        preserveScroll: true,
    });
};

// Form Update Password
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPassword = () => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
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
        month: 'long',
        day: 'numeric',
    }).format(d);
};
</script>

<template>
    <Head title="Profil Akun & Keamanan - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <span class="text-gray-500">Pengaturan</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-semibold">Profil Akun & Keamanan</span>
            </div>
        </template>

        <div class="max-w-[1000px] mx-auto space-y-7">
            <!-- Header Area -->
            <div>
                <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Profil Akun & Keamanan</h1>
                <p class="text-gray-500 text-[15px] mt-0.5">Kelola informasi identitas akun pribadi dan perbarui kata sandi Anda secara berkala.</p>
            </div>

            <!-- Identity Overview Card -->
            <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-[#001AFF] text-white font-bold text-2xl flex items-center justify-center shrink-0 shadow-md">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="space-y-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900 truncate">{{ user.name }}</h2>
                            <span
                                v-for="role in user.roles"
                                :key="role"
                                :class="`px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-full border ${getRoleBadge(role)}`"
                            >
                                {{ role }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 font-mono">{{ user.email }}</p>
                        <p class="text-[11px] text-gray-500">
                            Terdaftar sejak: <span class="font-medium text-gray-700">{{ formatDate(user.created_at) }}</span>
                        </p>
                    </div>
                </div>

                <!-- Teacher Attachment Detail if available -->
                <div v-if="user.teacher" class="bg-gray-50 rounded-xl p-3.5 border border-gray-200/70 text-xs space-y-1 w-full sm:w-auto">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Tautan Data Pengajar</div>
                    <div class="font-semibold text-gray-800">{{ user.teacher.name }}</div>
                    <div class="text-[11px] text-gray-500 font-mono">NIP: {{ user.teacher.employee_number || '-' }}</div>
                    <div v-if="user.teacher.homeroom_classes.length > 0" class="text-[11px] text-indigo-600 font-medium">
                        Wali Kelas: {{ user.teacher.homeroom_classes.join(', ') }}
                    </div>
                </div>
            </div>

            <!-- Grid 2 Kolom: Ubah Profil & Ubah Password -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <!-- Section 1: Informasi Profil -->
                <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-5">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="text-base font-bold text-gray-900">Informasi Pribadi</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Perbarui nama pengguna dan alamat kontak Anda.</p>
                    </div>

                    <form @submit.prevent="submitProfile" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap</label>
                            <input
                                v-model="profileForm.name"
                                type="text"
                                required
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="profileForm.errors.name" class="text-red-500 text-[11px] mt-1">{{ profileForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Email</label>
                            <input
                                v-model="profileForm.email"
                                type="email"
                                required
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="profileForm.errors.email" class="text-red-500 text-[11px] mt-1">{{ profileForm.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Telepon / WhatsApp (Opsional)</label>
                            <input
                                v-model="profileForm.phone"
                                type="text"
                                placeholder="08xxxxxxxxxx"
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="profileForm.errors.phone" class="text-red-500 text-[11px] mt-1">{{ profileForm.errors.phone }}</p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-sm cursor-pointer disabled:opacity-50"
                            >
                                {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Profil' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section 2: Ubah Kata Sandi -->
                <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-5">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="text-base font-bold text-gray-900">Ubah Kata Sandi</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.</p>
                    </div>

                    <form @submit.prevent="submitPassword" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kata Sandi Saat Ini</label>
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                required
                                placeholder="Ketik kata sandi lama Anda"
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="passwordForm.errors.current_password" class="text-red-500 text-[11px] mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kata Sandi Baru</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="Minimal 8 karakter"
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                            <p v-if="passwordForm.errors.password" class="text-red-500 text-[11px] mt-1">{{ passwordForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                placeholder="Ulangi kata sandi baru"
                                class="w-full text-xs rounded-xl bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6366F1]"
                            />
                        </div>

                        <div class="flex justify-end pt-2">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="px-5 py-2.5 rounded-full bg-gray-900 hover:bg-black text-white text-xs font-semibold transition-all shadow-sm cursor-pointer disabled:opacity-50"
                            >
                                {{ passwordForm.processing ? 'Memperbarui...' : 'Perbarui Kata Sandi' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

