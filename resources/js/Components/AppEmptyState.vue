<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        icon?: string;
        title?: string;
        description?: string;
        actionLabel?: string;
        actionHref?: string;
        actionIcon?: string;
        bordered?: boolean;
    }>(),
    {
        icon: 'pi pi-inbox',
        title: 'Tidak ada data ditemukan',
        description: 'Belum ada rekaman data atau tidak ada data yang cocok dengan kriteria filter pencarian.',
        actionLabel: '',
        actionHref: '',
        actionIcon: 'pi pi-plus',
        bordered: false,
    }
);

const emit = defineEmits<{
    (e: 'action'): void;
}>();
</script>

<template>
    <div
        class="py-14 sm:py-16 text-center flex flex-col items-center justify-center p-6"
        :class="{ 'rounded-2xl border border-dashed border-gray-200 bg-gray-50/50': bordered }"
    >
        <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-[#001AFF] flex items-center justify-center mb-3.5 shadow-2xs">
            <i :class="[icon, 'text-2xl']"></i>
        </div>

        <h3 class="text-sm sm:text-base font-bold text-gray-900 tracking-tight">
            {{ title }}
        </h3>

        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto leading-relaxed">
            {{ description }}
        </p>

        <!-- Optional Action Button -->
        <div v-if="actionLabel" class="mt-5">
            <Link
                v-if="actionHref"
                :href="actionHref"
                class="px-5 py-2.5 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-xs inline-flex items-center gap-1.5 cursor-pointer"
            >
                <i v-if="actionIcon" :class="[actionIcon, 'text-xs']"></i>
                <span>{{ actionLabel }}</span>
            </Link>
            <button
                v-else
                type="button"
                @click="emit('action')"
                class="px-5 py-2.5 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold transition-all shadow-xs inline-flex items-center gap-1.5 cursor-pointer"
            >
                <i v-if="actionIcon" :class="[actionIcon, 'text-xs']"></i>
                <span>{{ actionLabel }}</span>
            </button>
        </div>

        <slot />
    </div>
</template>

