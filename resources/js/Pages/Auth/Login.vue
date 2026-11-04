<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login');
};
</script>

<template>
    <Head title="Masuk - Sekolah Disiplin" />

    <div class="min-h-screen bg-[#F8F9FA] flex items-center justify-center p-4 sm:p-6 lg:p-8 font-sans">
        <div class="w-full max-w-4xl bg-white flex flex-col md:flex-row shadow-[0_2px_20px_#00000006] rounded-[24px] border border-gray-100 overflow-hidden">
            <!-- Left Panel: Product Identity -->
            <div class="w-full md:w-5/12 bg-[#1E1B4B] p-8 md:p-12 flex flex-col justify-between text-white border-r border-indigo-950/20">
                <div>
                    <div class="mb-8">
                        <!-- Typographic Wordmark -->
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#6366F1] text-white font-bold text-xl mb-4 shadow-sm">
                            SD
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-white mb-1.5">Sekolah Disiplin</h1>
                        <p class="text-indigo-200 text-xs uppercase tracking-wider font-semibold mb-6">Sistem Manajemen Kedisiplinan Siswa</p>
                    </div>
                    
                    <p class="text-indigo-100/90 leading-relaxed text-sm">
                        Pengelolaan kedisiplinan dan apresiasi prestasi siswa yang terstruktur, transparan, dan terukur.
                    </p>

                    <div class="mt-8 space-y-2">
                        <div class="h-1 w-12 rounded-full bg-[#6366F1]"></div>
                        <div class="h-1 w-8 rounded-full bg-indigo-400/40"></div>
                    </div>
                </div>
                
                <div class="mt-12 text-xs text-indigo-200/60">
                    &copy; {{ new Date().getFullYear() }} Sekolah Disiplin. Hak cipta dilindungi.
                </div>
            </div>

            <!-- Right Panel: Authentication -->
            <div class="w-full md:w-7/12 p-8 md:p-12 lg:p-16 flex flex-col justify-center bg-white">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Selamat datang kembali</h2>
                    <p class="text-gray-500 mt-1 text-sm">Masuk dengan akun resmi yang telah didaftarkan.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-gray-700 mb-1.5">Alamat Email *</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            placeholder="nama@sekolah.sch.id"
                            class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:border-transparent transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600" role="alert">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-gray-700 mb-1.5">Kata Sandi *</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="block w-full pl-4 pr-12 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:border-transparent transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.password }"
                            />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#6366F1]/20 rounded-r-xl"
                                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            >
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                  <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                  <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                  <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600" role="alert">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 border-gray-300 text-[#6366F1] focus:ring-[#6366F1] rounded cursor-pointer"
                            />
                            <span class="text-xs font-medium text-gray-700">Ingat sesi saya</span>
                        </label>

                        <span class="text-xs text-gray-400">
                            Lupa kata sandi? Hubungi tim Tata Tertib
                        </span>
                    </div>

                    <div class="pt-3">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-[#6366F1] hover:bg-[#4F46E5] text-white font-semibold py-2.5 px-5 rounded-full shadow-[0_4px_14px_#6366F140] focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed transition-all flex justify-center items-center text-sm"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memproses...
                            </span>
                            <span v-else>Masuk ke Akun</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>