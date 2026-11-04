<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Selamat Datang - Sekolah Disiplin" />

    <div class="min-h-screen bg-[#F8F9FA] flex flex-col items-center justify-center p-6 text-center font-sans">
        <div class="w-full max-w-md bg-white rounded-[24px] p-8 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-[#6366F1] text-white font-bold text-xl flex items-center justify-center mx-auto shadow-sm">
                SD
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Sekolah Disiplin</h1>
            <p class="text-sm text-gray-500">Sistem Administrasi dan Manajemen Tata Tertib Siswa</p>
            <div class="pt-4">
                <Link
                    href="/login"
                    class="inline-flex w-full justify-center px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-sm font-semibold transition-all shadow-[0_4px_14px_#6366F140]"
                >
                    Masuk ke Sistem
                </Link>
            </div>
        </div>
    </div>
</template>