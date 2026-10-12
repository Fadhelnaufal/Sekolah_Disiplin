<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    achievement?: {
        id: number;
        code: string;
        name: string;
        level: string;
        deduction_points: number;
        description: string;
        frequency_rule: string;
        is_active: boolean;
    };
}>();

const isEditing = !!props.achievement;

const form = useForm({
    code: props.achievement?.code || '',
    name: props.achievement?.name || '',
    level: props.achievement?.level || 'SEKOLAH',
    deduction_points: props.achievement?.deduction_points || 1,
    description: props.achievement?.description || '',
    frequency_rule: props.achievement?.frequency_rule || '',
    is_active: props.achievement?.is_active ?? true,
});

const submit = () => {
    if (isEditing) {
        form.put(`/achievements/${props.achievement!.id}`);
    } else {
        form.post('/achievements');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Prestasi - Disiplin Pro' : 'Tambah Prestasi - Disiplin Pro'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/achievements" class="text-[#697586] hover:text-[#202B3C] transition-colors">Master Prestasi</Link>
                <span class="text-[#E5E9EF]">/</span>
                <span class="text-[#202B3C]">{{ isEditing ? 'Edit Data' : 'Tambah Data' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-[#202B3C] tracking-tight">{{ isEditing ? 'Edit Master Prestasi' : 'Tambah Master Prestasi' }}</h1>
                <p class="text-[#697586] mt-1 text-sm">Silakan lengkapi formulir di bawah ini untuk menyimpan data prestasi dan pengurang poin.</p>
            </div>

            <div class="bg-white border border-[#E5E9EF] shadow-sm">
                <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Code -->
                        <div>
                            <label for="code" class="block text-sm font-semibold text-[#202B3C] mb-2">Kode Prestasi <span class="text-[#B42318]">*</span></label>
                            <input
                                id="code"
                                v-model="form.code"
                                type="text"
                                required
                                placeholder="Contoh: PRS-001"
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                                :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.code }}</p>
                        </div>

                        <!-- Level -->
                        <div>
                            <label for="level" class="block text-sm font-semibold text-[#202B3C] mb-2">Tingkat / Level <span class="text-[#B42318]">*</span></label>
                            <select
                                id="level"
                                v-model="form.level"
                                required
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors appearance-none"
                            >
                                <option value="SEKOLAH">Sekolah</option>
                                <option value="KABUPATEN">Kabupaten / Kota</option>
                                <option value="PROVINSI">Provinsi</option>
                                <option value="NASIONAL">Nasional</option>
                                <option value="INTERNASIONAL">Internasional</option>
                            </select>
                            <p v-if="form.errors.level" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.level }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-[#202B3C] mb-2">Nama Prestasi <span class="text-[#B42318]">*</span></label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Juara 1 Olimpiade Sains Nasional"
                            class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                            :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.name }}</p>
                    </div>

                    <!-- Deduction Points -->
                    <div>
                        <label for="deduction_points" class="block text-sm font-semibold text-[#202B3C] mb-2">Poin Pengurang <span class="text-[#B42318]">*</span></label>
                        <input
                            id="deduction_points"
                            v-model="form.deduction_points"
                            type="number"
                            min="1"
                            required
                            class="block w-full md:w-1/3 px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                            :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.deduction_points }"
                        />
                        <p class="mt-1.5 text-xs text-[#697586]">Masukkan angka bulat positif. Sistem akan otomatis mengubahnya menjadi pengurang total poin (minus).</p>
                        <p v-if="form.errors.deduction_points" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.deduction_points }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-semibold text-[#202B3C] mb-2">Deskripsi (Opsional)</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors resize-y"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.description }}</p>
                        </div>

                        <!-- Frequency Rule -->
                        <div>
                            <label for="frequency_rule" class="block text-sm font-semibold text-[#202B3C] mb-2">Aturan Frekuensi (Opsional)</label>
                            <textarea
                                id="frequency_rule"
                                v-model="form.frequency_rule"
                                rows="3"
                                placeholder="Contoh: Maksimal diklaim 1 kali per semester"
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors resize-y"
                            ></textarea>
                            <p v-if="form.errors.frequency_rule" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.frequency_rule }}</p>
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 border-[#E1E7EE] text-[#315E8A] focus:ring-[#315E8A] rounded-none cursor-pointer"
                        />
                        <label for="is_active" class="text-sm font-medium text-[#182230] cursor-pointer select-none">Status Aktif</label>
                    </div>
                    <p class="text-xs text-[#697586] ml-6">Jika tidak aktif, prestasi ini tidak dapat dipilih saat pencatatan.</p>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-[#E5E9EF] flex items-center justify-end gap-3">
                        <Link 
                            href="/achievements"
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
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Master' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
