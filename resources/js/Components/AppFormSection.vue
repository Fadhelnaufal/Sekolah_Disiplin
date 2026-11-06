<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: string;
        badge?: string;
        required?: boolean;
    }>(),
    {
        description: '',
        icon: '',
        badge: '',
        required: false,
    }
);
</script>

<template>
    <div class="bg-white rounded-[24px] p-6 sm:p-7 shadow-[0_2px_20px_#00000006] border border-gray-100 space-y-5">
        <!-- Section Header -->
        <div class="flex items-center justify-between border-b border-gray-100 pb-3.5 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
                <div v-if="icon" class="w-8 h-8 rounded-xl bg-indigo-50 text-[#001AFF] flex items-center justify-center font-bold text-xs shrink-0">
                    <i :class="icon"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 leading-tight">
                        {{ title }}
                    </h2>
                    <p v-if="description" class="text-xs text-gray-500 mt-0.5 leading-normal">
                        {{ description }}
                    </p>
                </div>
            </div>

            <!-- Right Header Slot / Badges -->
            <div class="flex items-center gap-2 shrink-0">
                <slot name="header-action">
                    <span
                        v-if="required"
                        class="text-[11px] font-semibold text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-md border border-rose-100"
                    >
                        Wajib diisi
                    </span>
                    <span
                        v-else-if="badge"
                        class="text-[11px] font-semibold text-gray-500 bg-gray-50 px-2.5 py-0.5 rounded-md border border-gray-100"
                    >
                        {{ badge }}
                    </span>
                </slot>
            </div>
        </div>

        <!-- Section Fields Content -->
        <div class="space-y-4">
            <slot />
        </div>
    </div>
</template>

