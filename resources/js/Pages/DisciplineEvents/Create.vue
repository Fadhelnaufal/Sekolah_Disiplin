<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    students: Array<{ id: number; name: string; nis: string; class_room?: { name: string } }>;
    violations: Array<{ id: number; code: string; name: string; points: number }>;
    achievements: Array<{ id: number; code: string; name: string; points: number }>;
    activeSemester: { id: number; name: string };
    defaultStudentId?: string | number;
}>();

const form = useForm({
    student_id: props.defaultStudentId || '',
    event_date: new Date().toISOString().split('T')[0],
    event_type: 'violation',
    violation_id: '',
    achievement_id: '',
    description: '',
    follow_up: '',
});

const submit = () => {
    form.post('/discipline-events');
};
</script>

<template>
    <Head title="Input Kejadian - Disiplin Pro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/discipline-events" class="text-[#697586] hover:text-[#202B3C] transition-colors">Pencatatan Kedisiplinan</Link>
                <span class="text-[#E5E9EF]">/</span>
                <span class="text-[#202B3C]">Input Kejadian Baru</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-[#202B3C] tracking-tight">Input Kejadian Baru</h1>
                <p class="text-[#697586] mt-1 text-sm">Pilih siswa dan catat pelanggaran atau prestasi yang dilakukan. Poin akan terkalkulasi secara otomatis.</p>
            </div>

            <!-- Active Semester Info -->
            <div class="bg-[#315E8A]/10 border border-[#315E8A]/20 p-4 rounded flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#315E8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-[#315E8A] font-medium">
                    Pencatatan ini akan dimasukkan ke <strong>Semester {{ activeSemester.name }}</strong> (Semester Aktif).
                </p>
            </div>

            <div class="bg-white border border-[#E5E9EF] shadow-sm">
                <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                    
                    <!-- Student Selection -->
                    <div>
                        <label for="student_id" class="block text-sm font-semibold text-[#202B3C] mb-2">Nama Siswa <span class="text-[#B42318]">*</span></label>
                        <select
                            id="student_id"
                            v-model="form.student_id"
                            required
                            class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                        >
                            <option value="" disabled>Cari dan Pilih Siswa...</option>
                            <option v-for="student in students" :key="student.id" :value="student.id">
                                {{ student.nis }} - {{ student.name }} ({{ student.class_room?.name }})
                            </option>
                        </select>
                        <p v-if="form.errors.student_id" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.student_id }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-[#E5E9EF] pt-6">
                        <!-- Event Date -->
                        <div>
                            <label for="event_date" class="block text-sm font-semibold text-[#202B3C] mb-2">Tanggal Kejadian <span class="text-[#B42318]">*</span></label>
                            <input
                                id="event_date"
                                v-model="form.event_date"
                                type="date"
                                required
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                                :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.event_date }"
                            />
                            <p v-if="form.errors.event_date" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.event_date }}</p>
                        </div>

                        <!-- Event Type -->
                        <div>
                            <label class="block text-sm font-semibold text-[#202B3C] mb-2">Jenis Kejadian <span class="text-[#B42318]">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.event_type" value="violation" class="w-4 h-4 border-[#E1E7EE] text-[#B42318] focus:ring-[#B42318]" />
                                    <span class="text-sm font-medium text-[#202B3C]">Pelanggaran</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.event_type" value="achievement" class="w-4 h-4 border-[#E1E7EE] text-[#237A57] focus:ring-[#237A57]" />
                                    <span class="text-sm font-medium text-[#202B3C]">Prestasi</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Violation Specific -->
                    <div v-if="form.event_type === 'violation'" class="bg-[#F7F8FA] p-4 border border-[#E5E9EF] rounded-sm">
                        <label for="violation_id" class="block text-sm font-semibold text-[#202B3C] mb-2">Pilih Pelanggaran <span class="text-[#B42318]">*</span></label>
                        <select
                            id="violation_id"
                            v-model="form.violation_id"
                            :required="form.event_type === 'violation'"
                            class="block w-full px-3 py-2.5 bg-white border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#B42318] focus:border-[#B42318] transition-colors"
                        >
                            <option value="" disabled>Pilih Jenis Pelanggaran...</option>
                            <option v-for="violation in violations" :key="violation.id" :value="violation.id">
                                [{{ violation.code }}] {{ violation.name }} (+{{ violation.points }} Poin)
                            </option>
                        </select>
                        <p v-if="form.errors.violation_id" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.violation_id }}</p>
                    </div>

                    <!-- Achievement Specific -->
                    <div v-if="form.event_type === 'achievement'" class="bg-[#F7F8FA] p-4 border border-[#E5E9EF] rounded-sm">
                        <label for="achievement_id" class="block text-sm font-semibold text-[#202B3C] mb-2">Pilih Prestasi <span class="text-[#B42318]">*</span></label>
                        <select
                            id="achievement_id"
                            v-model="form.achievement_id"
                            :required="form.event_type === 'achievement'"
                            class="block w-full px-3 py-2.5 bg-white border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#237A57] focus:border-[#237A57] transition-colors"
                        >
                            <option value="" disabled>Pilih Jenis Prestasi...</option>
                            <option v-for="achievement in achievements" :key="achievement.id" :value="achievement.id">
                                [{{ achievement.code }}] {{ achievement.name }} (-{{ achievement.points }} Poin)
                            </option>
                        </select>
                        <p v-if="form.errors.achievement_id" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.achievement_id }}</p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-[#202B3C] mb-2">Keterangan / Kronologi (Opsional)</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            placeholder="Deskripsikan detail kejadian..."
                            class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors resize-y"
                        ></textarea>
                    </div>

                    <!-- Follow Up -->
                    <div v-if="form.event_type === 'violation'">
                        <label for="follow_up" class="block text-sm font-semibold text-[#202B3C] mb-2">Tindak Lanjut Sementara (Opsional)</label>
                        <input
                            id="follow_up"
                            v-model="form.follow_up"
                            type="text"
                            placeholder="Contoh: Diberi teguran lisan di tempat"
                            class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-[#E5E9EF] flex items-center justify-end gap-3">
                        <Link 
                            href="/discipline-events"
                            class="px-4 py-2 text-sm font-medium text-[#697586] hover:text-[#202B3C] bg-white border border-[#E5E9EF] hover:bg-[#F7F8FA] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E5E9EF]"
                        >
                            Batal
                        </Link>
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#1D3557] hover:bg-[#0D1B2A] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D3557] disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Simpan Kejadian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
