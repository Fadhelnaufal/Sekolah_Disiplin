<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        status?: string | number | null;
        label?: string;
        dot?: boolean;
        size?: 'sm' | 'md';
    }>(),
    {
        status: '',
        label: '',
        dot: true,
        size: 'sm',
    }
);

interface StatusConfig {
    label: string;
    badgeClass: string;
    dotClass: string;
}

const config = computed<StatusConfig>(() => {
    const raw = String(props.status ?? '').trim().toUpperCase();

    // 1. Student Discipline / Risk Statuses
    if (raw === 'SAFE' || raw === 'NORMAL' || raw === 'AMAN') {
        return {
            label: props.label || 'Aman',
            badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            dotClass: 'bg-emerald-500',
        };
    }
    if (raw === 'ATTENTION' || raw === 'PERHATIAN') {
        return {
            label: props.label || 'Perhatian',
            badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
            dotClass: 'bg-amber-500',
        };
    }
    if (raw === 'WARNING' || raw === 'WASPADA' || raw === 'RAPID_INCREASE') {
        return {
            label: props.label || 'Waspada',
            badgeClass: 'bg-orange-50 text-orange-700 border-orange-200',
            dotClass: 'bg-orange-500',
        };
    }
    if (raw === 'CRITICAL' || raw === 'KRITIS' || raw === 'HIGH_THRESHOLD' || raw === 'DANGER') {
        return {
            label: props.label || 'Kritis',
            badgeClass: 'bg-red-50 text-red-700 border-red-200',
            dotClass: 'bg-red-500',
        };
    }

    // 2. Lifecycle / Entity Statuses
    if (raw === 'ACTIVE' || raw === 'AKTIF') {
        return {
            label: props.label || 'Aktif',
            badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            dotClass: 'bg-emerald-500',
        };
    }
    if (raw === 'INACTIVE' || raw === 'NONAKTIF' || raw === 'TIDAK AKTIF') {
        return {
            label: props.label || 'Nonaktif',
            badgeClass: 'bg-gray-100 text-gray-600 border-gray-200',
            dotClass: 'bg-gray-400',
        };
    }

    // 3. Follow Up / Counseling Progress
    if (raw === 'SCHEDULED' || raw === 'TERJADWAL') {
        return {
            label: props.label || 'Terjadwal',
            badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
            dotClass: 'bg-blue-500',
        };
    }
    if (raw === 'PENDING' || raw === 'MENUNGGU') {
        return {
            label: props.label || 'Menunggu',
            badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
            dotClass: 'bg-amber-500',
        };
    }
    if (raw === 'IN_PROGRESS' || raw === 'DIPROSES' || raw === 'BERJALAN' || raw === 'SEDANG BERJALAN') {
        return {
            label: props.label || 'Diproses',
            badgeClass: 'bg-indigo-50 text-[#001AFF] border-indigo-200',
            dotClass: 'bg-[#001AFF]',
        };
    }
    if (raw === 'RESOLVED' || raw === 'COMPLETED' || raw === 'SELESAI') {
        return {
            label: props.label || 'Selesai',
            badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            dotClass: 'bg-emerald-500',
        };
    }
    if (raw === 'REFERRED' || raw === 'DIALIHKAN') {
        return {
            label: props.label || 'Dialihkan',
            badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',
            dotClass: 'bg-purple-500',
        };
    }
    if (raw === 'OVERDUE' || raw === 'TERLAMBAT') {
        return {
            label: props.label || 'Terlambat',
            badgeClass: 'bg-red-50 text-red-700 border-red-200',
            dotClass: 'bg-red-500',
        };
    }
    if (raw === 'CANCELLED' || raw === 'DIBATALKAN') {
        return {
            label: props.label || 'Dibatalkan',
            badgeClass: 'bg-gray-100 text-gray-600 border-gray-200',
            dotClass: 'bg-gray-400',
        };
    }

    // 4. Discipline Events
    if (raw === 'VIOLATION' || raw === 'PELANGGARAN') {
        return {
            label: props.label || 'Pelanggaran',
            badgeClass: 'bg-rose-50 text-rose-700 border-rose-200',
            dotClass: 'bg-rose-500',
        };
    }
    if (raw === 'ACHIEVEMENT' || raw === 'PRESTASI') {
        return {
            label: props.label || 'Prestasi',
            badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            dotClass: 'bg-emerald-500',
        };
    }

    // Default Fallback
    return {
        label: props.label || String(props.status || 'Netral'),
        badgeClass: 'bg-gray-100 text-gray-700 border-gray-200',
        dotClass: 'bg-gray-400',
    };
});
</script>

<template>
    <div
        class="inline-flex items-center gap-1.5 rounded-full font-semibold border transition-colors select-none"
        :class="[
            config.badgeClass,
            size === 'md' ? 'px-3.5 py-1 text-xs' : 'px-2.5 py-0.5 text-[11px]'
        ]"
    >
        <span
            v-if="dot"
            class="rounded-full shrink-0"
            :class="[
                config.dotClass,
                size === 'md' ? 'w-2 h-2' : 'w-1.5 h-1.5'
            ]"
        ></span>
        <span class="tracking-wide leading-tight">
            {{ config.label }}
        </span>
    </div>
</template>

