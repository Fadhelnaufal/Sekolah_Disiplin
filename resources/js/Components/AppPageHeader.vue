<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        badge?: string;
        badgeIcon?: string;
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        description: '',
        badge: '',
        badgeIcon: '',
        breadcrumbs: () => [],
    }
);
</script>

<template>
    <div class="space-y-4">
        <!-- Optional Breadcrumb Bar (if provided in header) -->
        <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="flex items-center gap-2 text-xs font-medium text-gray-500">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <Link
                    v-if="item.href && index < breadcrumbs.length - 1"
                    :href="item.href"
                    class="hover:text-gray-900 transition-colors"
                >
                    {{ item.label }}
                </Link>
                <span v-else :class="index === breadcrumbs.length - 1 ? 'text-gray-900 font-semibold' : 'text-gray-500'">
                    {{ item.label }}
                </span>
                <span v-if="index < breadcrumbs.length - 1" class="text-gray-300">/</span>
            </template>
        </nav>

        <!-- Main Header Layout -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-[26px] sm:text-[28px] font-bold text-gray-900 tracking-tight leading-tight">
                        {{ title }}
                    </h1>
                    
                    <slot name="badge">
                        <span
                            v-if="badge"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-[#001AFF] border border-indigo-100 shadow-2xs"
                        >
                            <i v-if="badgeIcon" :class="[badgeIcon, 'mr-1.5 text-[10px]']"></i>
                            {{ badge }}
                        </span>
                    </slot>
                </div>

                <p v-if="description" class="text-gray-500 text-[14px] sm:text-[15px] max-w-3xl leading-relaxed">
                    {{ description }}
                </p>

                <slot />
            </div>

            <!-- Action Buttons Area -->
            <div v-if="$slots.actions" class="flex items-center gap-2.5 flex-wrap shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>

