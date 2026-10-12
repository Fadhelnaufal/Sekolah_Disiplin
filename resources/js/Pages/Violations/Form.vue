<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    violation?: {
        id: number;
        code: string;
        name: string;
        category: string;
        points: number;
        description: string;
        is_active: boolean;
    };
}>();

const isEditing = !!props.violation;

const form = useForm({
    code: props.violation?.code || '',
    name: props.violation?.name || '',
    category: props.violation?.category || 'RINGAN',
    points: props.violation?.points || 1,
    description: props.violation?.description || '',
    is_active: props.violation?.is_active ?? true,
});

const submit = () => {
    if (isEditing) {
        form.put(`/violations/${props.violation!.id}`);
    } else {
        form.post('/violations');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Pelanggaran - Disiplin Pro' : 'Tambah Pelanggaran - Disiplin Pro'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full pr-4 text-sm font-medium">
                <Link href="/violations" class="text-[#697586] hover:text-[#202B3C] transition-colors">Master Pelanggaran</Link>
                <span class="text-[#E5E9EF]">/</span>
                <span class="text-[#202B3C]">{{ isEditing ? 'Edit Data' : 'Tambah Data' }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-[#202B3C] tracking-tight">{{ isEditing ? 'Edit Master Pelanggaran' : 'Tambah Master Pelanggaran' }}</h1>
                <p class="text-[#697586] mt-1 text-sm">Silakan lengkapi formulir di bawah ini untuk menyimpan data pelanggaran.</p>
            </div>

            <div class="bg-white border border-[#E5E9EF] shadow-sm">
                <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Code -->
                        <div>
                            <label for="code" class="block text-sm font-semibold text-[#202B3C] mb-2">Kode Pelanggaran <span class="text-[#B42318]">*</span></label>
                            <input
                                id="code"
                                v-model="form.code"
                                type="text"
                                required
                                placeholder="Contoh: PLG-001"
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                                :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.code }}</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-semibold text-[#202B3C] mb-2">Kategori <span class="text-[#B42318]">*</span></label>
                            <select
                                id="category"
                                v-model="form.category"
                                required
                                class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors appearance-none"
                            >
                                <option value="RINGAN">Ringan</option>
                                <option value="SEDANG">Sedang</option>
                                <option value="BERAT">Berat</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.category }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-[#202B3C] mb-2">Nama Pelanggaran <span class="text-[#B42318]">*</span></label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Terlambat datang ke sekolah"
                            class="block w-full px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                            :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.name }}</p>
                    </div>

                    <!-- Points -->
                    <div>
                        <label for="points" class="block text-sm font-semibold text-[#202B3C] mb-2">Bobot Poin <span class="text-[#B42318]">*</span></label>
                        <input
                            id="points"
                            v-model="form.points"
                            type="number"
                            min="1"
                            required
                            class="block w-full md:w-1/3 px-3 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-1 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                            :class="{ 'border-[#B42318] focus:ring-[#B42318]': form.errors.points }"
                        />
                        <p class="mt-1.5 text-xs text-[#697586]">Masukkan angka bulat positif (misal: 5, 10, 50).</p>
                        <p v-if="form.errors.points" class="mt-1.5 text-xs font-medium text-[#B42318]">{{ form.errors.points }}</p>
                    </div>

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
                    <p class="text-xs text-[#697586] ml-6">Jika tidak aktif, pelanggaran ini tidak akan muncul di form pencatatan kedisiplinan.</p>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-[#E5E9EF] flex items-center justify-end gap-3">
                        <Link 
                            href="/violations"
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
