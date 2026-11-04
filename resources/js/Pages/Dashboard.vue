<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import TrendLineChart, { type MonthlyTrendItem } from '../Components/Charts/TrendLineChart.vue';

interface RoleContext {
    role: string;
    role_label: string;
    class_name?: string | null;
    banner: {
        badge: string;
        title: string;
        description: string;
        action_label: string;
        action_url: string;
        action_icon: string;
    };
    kpi_cards: Array<{
        label: string;
        value: number;
        icon: string;
        theme: 'indigo' | 'rose' | 'emerald' | 'amber' | 'violet' | string;
    }>;
    quick_links: Array<{
        label: string;
        desc: string;
        href: string;
        icon: string;
        color: string;
    }>;
}

const props = defineProps<{
    metrics: {
        total_students: number;
        today_violations: number;
        today_achievements: number;
        active_follow_ups: number;
        current_violations: number;
        attention_needed: number;
        counseling_pending: number;
    };
    priority_students: Array<{
        id: number;
        name: string;
        nis: string;
        class_name: string;
        net_points: number;
        status: string;
    }>;
    recent_activities: Array<{
        id: number;
        date: string;
        student_name: string;
        class_name: string;
        type: string;
        category: string;
        points: number;
        recorded_by: string;
    }>;
    monthly_trend?: MonthlyTrendItem[];
    current_semester: string;
    school_name: string;
    academic_year: string;
    role_context?: RoleContext;
}>();

const getThemeClasses = (theme: string) => {
    switch (theme) {
        case 'rose':
            return {
                bg: 'bg-rose-50',
                text: 'text-rose-600',
                border: 'border-rose-100/80',
            };
        case 'emerald':
            return {
                bg: 'bg-emerald-50',
                text: 'text-emerald-600',
                border: 'border-emerald-100/80',
            };
        case 'amber':
            return {
                bg: 'bg-amber-50',
                text: 'text-amber-600',
                border: 'border-amber-100/80',
            };
        case 'violet':
            return {
                bg: 'bg-purple-50',
                text: 'text-purple-600',
                border: 'border-purple-100/80',
            };
        case 'indigo':
        default:
            return {
                bg: 'bg-indigo-50',
                text: 'text-[#6366F1]',
                border: 'border-indigo-100/80',
            };
    }
};

const getQuickLinkTheme = (color: string) => {
    switch (color) {
        case 'rose':
            return 'group-hover:bg-rose-50 text-rose-500 group-hover:text-rose-600';
        case 'emerald':
            return 'group-hover:bg-emerald-50 text-emerald-500 group-hover:text-emerald-600';
        case 'amber':
            return 'group-hover:bg-amber-50 text-amber-500 group-hover:text-amber-600';
        case 'slate':
            return 'group-hover:bg-slate-100 text-slate-500 group-hover:text-slate-700';
        case 'indigo':
        default:
            return 'group-hover:bg-indigo-50 text-[#6366F1] group-hover:text-[#6366F1]';
    }
};

const getRiskBadgeClass = (status: string) => {
    switch (status) {
        case 'Kritis':
        case 'Skorsing':
            return 'bg-red-50 text-red-700 border-red-200';
        case 'Panggilan Ortu':
        case 'Peringatan':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'Perhatian':
        default:
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    }
};
</script>

<template>
    <Head title="Dashboard - Sekolah Disiplin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 leading-tight">Dashboard</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ role_context?.role_label || 'Pusat Kendali Kedisiplinan' }} • Semester {{ current_semester }}
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-[1400px] mx-auto space-y-6">
            <!-- Top Banner Card (Role-Aware Surface) -->
            <div class="relative overflow-hidden rounded-[24px] bg-gradient-to-br from-blue-700 via-violet-600 to-blue-600 p-7 sm:p-8 text-white shadow-sm border border-indigo-900/30 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2 max-w-2xl z-10">
                    <span class="text-[11px] font-bold uppercase tracking-widest text-indigo-300 block">
                        {{ role_context?.banner.badge || 'RINGKASAN SEKOLAH' }}
                    </span>
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white leading-snug">
                        {{ role_context?.banner.title || 'Semua aktivitas disiplin, langsung siap dipakai.' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-indigo-200/90 leading-relaxed">
                        {{ role_context?.banner.description || 'Catat kejadian, pantau pembinaan, dan tindak lanjut siswa tanpa spreadsheet terpisah.' }}
                    </p>
                </div>

                <div class="z-10 shrink-0">
                    <Link
                        :href="role_context?.banner.action_url || '/discipline-events/create'"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#6366F1] hover:bg-[#4F46E5] text-white text-xs font-semibold transition-all shadow-[0_4px_14px_#6366F140]"
                    >
                        <i :class="role_context?.banner.action_icon || 'pi pi-plus-circle'" class="text-xs"></i>
                        <span>{{ role_context?.banner.action_label || 'Catat kejadian baru' }}</span>
                    </Link>
                </div>
            </div>

            <!-- 4 Dynamic KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="(card, index) in role_context?.kpi_cards || [
                        { label: 'Total Siswa', value: metrics.total_students, icon: 'pi pi-users', theme: 'indigo' },
                        { label: 'Pelanggaran Hari Ini', value: metrics.today_violations, icon: 'pi pi-exclamation-triangle', theme: 'rose' },
                        { label: 'Prestasi Hari Ini', value: metrics.today_achievements, icon: 'pi pi-star', theme: 'emerald' },
                        { label: 'Tindak Lanjut Aktif', value: metrics.active_follow_ups, icon: 'pi pi-sliders-h', theme: 'indigo' }
                    ]"
                    :key="index"
                    class="bg-white rounded-[20px] p-5 shadow-[0_2px_15px_#00000004] border flex items-center gap-4 transition-all"
                    :class="getThemeClasses(card.theme).border"
                >
                    <div
                        class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
                        :class="[getThemeClasses(card.theme).bg, getThemeClasses(card.theme).text]"
                    >
                        <i :class="card.icon" class="text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 leading-tight">{{ card.value }}</div>
                        <div
                            class="text-xs font-semibold mt-0.5"
                            :class="getThemeClasses(card.theme).text"
                        >
                            {{ card.label }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Priority Students (Early Warning Radar) -->
            <div
                v-if="priority_students && priority_students.length > 0"
                class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-amber-100/60"
            >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <h3 class="text-base font-bold text-gray-900">Siswa Perlu Perhatian (Radar Early Warning)</h3>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Siswa dengan akumulasi poin risiko tinggi yang memerlukan tindakan atau pembinaan preventif.
                        </p>
                    </div>
                    <Link
                        v-if="role_context?.role === 'bk' || role_context?.role === 'admin'"
                        href="/counseling-records/create"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-amber-50 text-amber-700 hover:bg-amber-100 text-xs font-semibold transition-colors shrink-0 w-fit"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Jadwalkan Pembinaan</span>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 pt-4">
                    <div
                        v-for="student in priority_students"
                        :key="student.id"
                        class="p-4 rounded-2xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/20 transition-all flex flex-col justify-between gap-3 bg-white"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="text-xs font-bold text-gray-900 leading-snug">{{ student.name }}</h4>
                                <p class="text-[11px] text-gray-500 mt-0.5">NIS: {{ student.nis }} • Kelas: {{ student.class_name }}</p>
                            </div>
                            <span
                                class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0"
                                :class="getRiskBadgeClass(student.status)"
                            >
                                {{ student.status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                            <div class="text-[11px] font-semibold text-gray-600">
                                Total Poin: <span class="font-bold text-rose-600">{{ student.net_points }}</span>
                            </div>
                            <Link
                                :href="`/students/${student.id}`"
                                class="text-xs font-bold text-[#6366F1] hover:underline inline-flex items-center gap-1"
                            >
                                <span>Lihat Profil</span>
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual Chart Section: Tren Disiplin Semester Ini -->
            <div v-if="monthly_trend && monthly_trend.length > 0">
                <TrendLineChart
                    :trend-data="monthly_trend"
                    title="Tren Pelanggaran & Prestasi Semester Ini"
                    subtitle="Dinamika kejadian pelanggaran tata tertib dan capaian prestasi per bulan"
                    height="280px"
                />
            </div>

            <!-- Two-Column Section: Aktivitas Terbaru & Akses Cepat (Role-Safe) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Aktivitas Terbaru -->
                <div class="lg:col-span-2 bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] border border-gray-50 space-y-5 flex flex-col justify-between">
                    <div>
                        <div class="border-b border-gray-100 pb-4">
                            <h3 class="text-base font-bold text-gray-900">Aktivitas terbaru</h3>
                            <p class="text-xs text-gray-400 mt-0.5">10 catatan kejadian terbaru.</p>
                        </div>

                        <!-- Content List -->
                        <div v-if="recent_activities.length > 0" class="divide-y divide-gray-50 pt-2">
                            <div
                                v-for="act in recent_activities"
                                :key="act.id"
                                class="py-3 flex items-center justify-between gap-4 hover:bg-gray-50/50 px-2 rounded-xl transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0"
                                        :class="act.type === 'violation' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'"
                                    >
                                        <i :class="act.type === 'violation' ? 'pi pi-exclamation-triangle' : 'pi pi-star'"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-gray-900">{{ act.student_name }} ({{ act.class_name }})</div>
                                        <div class="text-[11px] text-gray-500">{{ act.category }} • {{ act.date }}</div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span
                                        class="text-xs font-bold inline-flex px-2 py-0.5 rounded-full"
                                        :class="act.type === 'violation' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'"
                                    >
                                        {{ act.type === 'violation' ? `+${act.points}` : act.points }} Poin
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-purple-50 text-[#8B5CF6] flex items-center justify-center">
                                <i class="pi pi-clipboard text-2xl"></i>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900">Belum ada kejadian</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Data akan muncul setelah mulai digunakan.</p>
                        </div>
                    </div>

                    <div v-if="recent_activities.length > 0" class="pt-3 border-t border-gray-100 text-right">
                        <Link href="/discipline-events" class="text-xs font-semibold text-[#6366F1] hover:underline">
                            Lihat semua catatan
                        </Link>
                    </div>
                </div>

                <!-- Right 1 Col: Akses Cepat (Role-Tailored & 100% Authorized) -->
                <div class="bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006] border border-gray-50 space-y-4">
                    <div class="border-b border-gray-100 pb-4">
                        <h3 class="text-base font-bold text-gray-900">Akses Cepat</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pekerjaan sesuai peran Anda</p>
                    </div>

                    <div class="space-y-2.5 pt-1">
                        <Link
                            v-for="(link, lIdx) in role_context?.quick_links || []"
                            :key="lIdx"
                            :href="link.href"
                            class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-gray-50 border border-gray-100/80 transition-all text-gray-700 hover:text-gray-900 group"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-xl bg-gray-50 flex items-center justify-center transition-colors shrink-0"
                                    :class="getQuickLinkTheme(link.color)"
                                >
                                    <i :class="link.icon" class="text-xs"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold block leading-tight">{{ link.label }}</span>
                                    <span class="text-[10px] text-gray-400 group-hover:text-gray-500 transition-colors block mt-0.5">{{ link.desc }}</span>
                                </div>
                            </div>
                            <i class="pi pi-chevron-right text-[11px] text-gray-400 group-hover:text-gray-600 transition-colors ml-2 shrink-0"></i>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
