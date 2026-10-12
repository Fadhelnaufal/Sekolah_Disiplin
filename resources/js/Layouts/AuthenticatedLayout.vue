<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';

const page = usePage();
const user = page.props.auth.user;
const toast = useToast();

watch(() => page.props.flash, (flash: any) => {
    if (flash?.success) {
        toast.add({ severity: 'success', summary: 'Berhasil', detail: flash.success, life: 3000 });
    }
    if (flash?.error) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: flash.error, life: 5000 });
    }
}, { deep: true, immediate: true });

const isMobileMenuOpen = ref(false);

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-background flex flex-col md:flex-row antialiased font-sans">
        <Toast />
        <ConfirmDialog />
        
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-40 bg-gray-900/50 md:hidden backdrop-blur-sm transition-opacity" @click="isMobileMenuOpen = false"></div>
        
        <!-- Sidebar -->
        <aside :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-[260px] bg-white transition-transform duration-300 ease-in-out md:relative md:translate-x-0 flex flex-col shadow-[1px_0_10px_#00000005]">
            
            <!-- Branding -->
            <div class="h-20 flex items-center px-6 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center mr-3 shadow-sm text-white font-bold text-sm">
                    DP
                </div>
                <span class="font-bold tracking-wide text-lg text-gray-900">DisiplinPro</span>
            </div>

            <!-- Navigation Menu -->
            <div class="flex-1 overflow-y-auto py-2 px-4 space-y-6">
                <div>
                    <nav class="space-y-1">
                        <Link 
                            href="/dashboard"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url === '/dashboard' ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-th-large text-lg" :class="page.url === '/dashboard' ? 'text-gray-900' : 'text-gray-400'"></i>
                            Dashboard
                        </Link>
                    </nav>
                </div>

                <!-- Operasional -->
                <div>
                    <p class="px-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Operasional</p>
                    <nav class="space-y-1">
                        <Link 
                            href="/students"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/students') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-users text-lg" :class="page.url.startsWith('/students') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Data Siswa
                        </Link>
                        <Link 
                            href="/discipline-events"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/discipline-events') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-briefcase text-lg" :class="page.url.startsWith('/discipline-events') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Catatan Disiplin
                        </Link>
                        <Link 
                            v-if="$page.props.auth.roles.some(r => ['Super Admin', 'BK', 'Principal'].includes(r))"
                            href="/counseling-records"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/counseling-records') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-comments text-lg" :class="page.url.startsWith('/counseling-records') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Konseling
                        </Link>
                    </nav>
                </div>

                <!-- Laporan -->
                <div v-if="$page.props.auth?.roles?.some(r => ['Super Admin', 'BK', 'Principal', 'Tata Tertib', 'Teacher'].includes(r))">
                    <p class="px-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Laporan</p>
                    <nav class="space-y-1">
                        <Link 
                            v-if="!$page.props.auth?.roles?.includes('Teacher') || $page.props.auth?.roles?.some(r => ['Super Admin', 'BK', 'Principal', 'Tata Tertib'].includes(r))"
                            href="/reports/monthly"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/reports/monthly') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-file-o text-lg" :class="page.url.startsWith('/reports/monthly') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Lap. Bulanan
                        </Link>
                        <Link 
                            v-if="!$page.props.auth?.roles?.includes('Teacher') || $page.props.auth?.roles?.some(r => ['Super Admin', 'BK', 'Principal', 'Tata Tertib'].includes(r))"
                            href="/reports/classes"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/reports/classes') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-table text-lg" :class="page.url.startsWith('/reports/classes') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Lap. Kelas
                        </Link>
                        <Link 
                            href="/reports/homerooms"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/reports/homerooms') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-address-book text-lg" :class="page.url.startsWith('/reports/homerooms') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Lap. Wali Kelas
                        </Link>
                        <Link 
                            v-if="!$page.props.auth?.roles?.includes('Teacher') || $page.props.auth?.roles?.some(r => ['Super Admin', 'BK', 'Principal', 'Tata Tertib'].includes(r))"
                            href="/reports/analytics"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/reports/analytics') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-chart-pie text-lg" :class="page.url.startsWith('/reports/analytics') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Analitik
                        </Link>
                    </nav>
                </div>

                <!-- Master Setup -->
                <div v-if="$page.props.auth?.roles?.includes('Super Admin')">
                    <p class="px-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Pengaturan Akademik</p>
                    <nav class="space-y-1">
                        <Link 
                            href="/academic-years"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/academic-years') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-calendar text-lg" :class="page.url.startsWith('/academic-years') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Tahun Ajaran
                        </Link>
                        <Link 
                            href="/semesters"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/semesters') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-book text-lg" :class="page.url.startsWith('/semesters') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Semester
                        </Link>
                    </nav>
                </div>

                <!-- Master Data -->
                <div v-if="['Super Admin', 'Tata Tertib'].some(r => $page.props.auth?.roles?.includes(r))">
                    <p class="px-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Master Data</p>
                    <nav class="space-y-1">
                        <Link 
                            href="/teachers"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/teachers') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-id-card text-lg" :class="page.url.startsWith('/teachers') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Data Guru
                        </Link>
                        <Link 
                            href="/class-rooms"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/class-rooms') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-building text-lg" :class="page.url.startsWith('/class-rooms') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Data Kelas
                        </Link>
                        <Link 
                            href="/violations"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/violations') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-exclamation-triangle text-lg" :class="page.url.startsWith('/violations') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Pelanggaran
                        </Link>
                        <Link 
                            href="/achievements"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/achievements') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-star text-lg" :class="page.url.startsWith('/achievements') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Prestasi
                        </Link>
                        <Link 
                            href="/sanction-rules"
                            class="flex items-center gap-3 px-4 py-3 text-[14px] rounded-xl transition-all"
                            :class="page.url.startsWith('/sanction-rules') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900'"
                        >
                            <i class="pi pi-shield text-lg" :class="page.url.startsWith('/sanction-rules') ? 'text-gray-900' : 'text-gray-400'"></i>
                            Aturan Sanksi
                        </Link>
                    </nav>
                </div>
            </div>
            
            <!-- User Info (Bottom Sidebar) -->
            <div class="p-5 mt-auto mb-4 mx-4 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Avatar :label="user.name.charAt(0)" class="bg-gray-900 text-white font-bold rounded-full w-10 h-10 shadow-sm" />
                    <div class="flex flex-col overflow-hidden">
                        <span class="text-[14px] font-bold text-gray-900 truncate w-24">{{ user.name }}</span>
                        <span class="text-[12px] font-medium text-gray-500 truncate w-24">{{ $page.props.auth.roles[0] || 'Pengguna' }}</span>
                    </div>
                </div>
                <button @click="logout" class="text-gray-400 hover:text-red-500 transition-colors focus:outline-none">
                    <i class="pi pi-sign-out text-lg"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            <header class="h-20 bg-transparent flex items-center justify-between px-4 sm:px-8 shrink-0 z-10 w-full pt-4 pb-2">
                <!-- Mobile Menu Button -->
                <Button icon="pi pi-bars" text class="md:hidden text-gray-600 -ml-2" @click="isMobileMenuOpen = true" />

                <!-- Page Header / Breadcrumb -->
                <div class="hidden md:flex items-center text-gray-500 text-sm font-medium">
                    <slot name="header">Dashboard</slot>
                </div>
                
                <!-- Right Actions -->
                <div class="flex flex-1 md:flex-none justify-end items-center gap-4">
                    <!-- Search Input Simulation -->
                    <div class="hidden sm:flex items-center bg-white rounded-full px-4 py-2.5 w-64 shadow-[0_2px_10px_#00000005]">
                        <i class="pi pi-search text-gray-400 mr-2 text-sm"></i>
                        <input type="text" placeholder="Search here..." class="bg-transparent border-none outline-none text-sm w-full text-gray-700 placeholder-gray-400" />
                    </div>

                    <button class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 shadow-[0_2px_10px_#00000005] hover:text-gray-800 transition-colors">
                        <i class="pi pi-bell text-lg"></i>
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-8 pb-8 pt-4">
                <slot />
            </main>
        </div>
    </div>
</template>
