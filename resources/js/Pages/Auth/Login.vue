<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
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
    <Head title="Masuk - Disiplin Pro" />

    <div class="min-h-screen bg-[#F5F7FA] flex items-center justify-center p-4 sm:p-6 lg:p-8 font-sans">
        <div class="w-full max-w-5xl bg-white flex flex-col md:flex-row shadow-sm border border-[#E1E7EE] overflow-hidden">
            <!-- Left Panel: Product Identity -->
            <div class="w-full md:w-5/12 bg-[#14263D] p-8 md:p-12 flex flex-col justify-between text-white border-r border-[#0D1B2A]/20">
                <div>
                    <div class="mb-8">
                        <!-- Typographic Wordmark -->
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-[#315E8A] text-white font-bold text-xl mb-4">
                            DP
                        </div>
                        <h1 class="text-3xl font-semibold tracking-tight text-white mb-2">DISIPLIN PRO</h1>
                        <p class="text-[#657386] text-sm uppercase tracking-wider font-semibold mb-6">Sistem Manajemen Kedisiplinan Siswa</p>
                    </div>
                    
                    <p class="text-white/90 leading-relaxed text-base">
                        Pengelolaan kedisiplinan siswa yang lebih terstruktur, transparan, dan terukur.
                    </p>

                    <!-- Subtle Geometric Structure -->
                    <div class="mt-12 space-y-4">
                        <div class="h-1 w-12 bg-[#477BAA]"></div>
                        <div class="h-1 w-8 bg-[#315E8A] opacity-50"></div>
                    </div>
                </div>
                
                <div class="mt-16 text-sm text-white/50">
                    &copy; {{ new Date().getFullYear() }} Disiplin Pro. All rights reserved.
                </div>
            </div>

            <!-- Right Panel: Authentication -->
            <div class="w-full md:w-7/12 p-8 md:p-14 lg:p-20 flex flex-col justify-center bg-white">
                <div class="mb-10">
                    <h2 class="text-2xl font-semibold text-[#182230]">Selamat datang kembali</h2>
                    <p class="text-[#657386] mt-2 text-sm">Silakan masuk dengan akun sistem Anda.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-[#182230] mb-2">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="block w-full px-4 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-2 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                            :class="{ 'border-[#B42318] focus:ring-[#B42318] focus:border-[#B42318]': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-[#B42318]" role="alert">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-[#182230] mb-2">Kata Sandi</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                class="block w-full pl-4 pr-12 py-2.5 bg-[#F5F7FA] border border-[#E1E7EE] text-[#182230] text-sm focus:outline-none focus:ring-2 focus:ring-[#315E8A] focus:border-[#315E8A] transition-colors"
                                :class="{ 'border-[#B42318] focus:ring-[#B42318] focus:border-[#B42318]': form.errors.password }"
                            />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-[#657386] hover:text-[#182230] focus:outline-none"
                                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            >
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                  <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                  <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-2 text-sm text-[#B42318]" role="alert">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 border-[#E1E7EE] text-[#315E8A] focus:ring-[#315E8A] rounded-none cursor-pointer"
                            />
                            <span class="text-sm text-[#182230]">Ingat saya</span>
                        </label>

                        <Link href="/forgot-password" class="text-sm font-medium text-[#315E8A] hover:text-[#14263D] hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#315E8A]">
                            Lupa kata sandi?
                        </Link>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-[#14263D] hover:bg-[#0D1B2A] text-white font-medium py-2.5 px-4 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#14263D] disabled:opacity-70 disabled:cursor-not-allowed transition-colors flex justify-center items-center"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Masuk...
                            </span>
                            <span v-else>Masuk</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>