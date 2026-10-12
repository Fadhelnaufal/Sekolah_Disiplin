<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    monthly_trend: Array<any>;
    category_distribution: Array<any>;
    academic_years: Array<any>;
    current_semester: any;
    filters: any;
}>();

const filterForm = ref({
    semester_id: props.filters.semester_id || props.current_semester.id,
});

const applyFilter = () => {
    router.get('/reports/analytics', filterForm.value, { preserveState: true });
};
</script>

<template>
    <Head title="Analitik Kedisiplinan - Disiplin Pro" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Analitik Kedisiplinan Siswa</h2>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 space-y-8">
            <!-- Filter Section -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tahun/Semester</label>
                    <select v-model="filterForm.semester_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                        <template v-for="ay in academic_years" :key="ay.id">
                            <option v-for="sem in ay.semesters" :key="sem.id" :value="sem.id">
                                {{ ay.name }} - {{ sem.name }}
                            </option>
                        </template>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilter" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 transition">Filter</button>
                </div>
            </div>

            <!-- Charts (Simplified HTML representation as native Chartjs isn't pre-installed) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Monthly Trend List -->
                <div class="bg-white p-6 shadow-xl sm:rounded-lg border border-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Tren Kejadian Bulanan</h3>
                    <div class="space-y-4">
                        <div v-for="trend in monthly_trend" :key="trend.month" class="flex items-center">
                            <div class="w-24 text-sm font-medium text-gray-600">{{ trend.month }}</div>
                            <div class="flex-1 ml-4">
                                <!-- Simple visual bar using tailwind -->
                                <div class="bg-blue-100 h-6 rounded-full overflow-hidden flex items-center">
                                    <div class="bg-blue-500 h-full text-xs text-white text-right pr-2 flex items-center justify-end" 
                                         :style="`width: ${Math.min((trend.count / Math.max(...monthly_trend.map(t => t.count))) * 100, 100)}%`">
                                        {{ trend.count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="monthly_trend.length === 0" class="text-center text-sm text-gray-500 py-4">
                            Belum ada tren data.
                        </div>
                    </div>
                </div>

                <!-- Category Distribution List -->
                <div class="bg-white p-6 shadow-xl sm:rounded-lg border border-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Distribusi Kategori Pelanggaran</h3>
                    <div class="space-y-4">
                        <div v-for="(cat, index) in category_distribution" :key="index" class="flex items-center">
                            <div class="w-1/2 text-sm font-medium text-gray-600 truncate pr-4">{{ cat.name }}</div>
                            <div class="flex-1">
                                <!-- Simple visual bar using tailwind -->
                                <div class="bg-orange-100 h-6 rounded-full overflow-hidden flex items-center">
                                    <div class="bg-orange-500 h-full text-xs text-white text-right pr-2 flex items-center justify-end" 
                                         :style="`width: ${Math.min((cat.count / Math.max(...category_distribution.map(c => c.count))) * 100, 100)}%`">
                                        {{ cat.count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="category_distribution.length === 0" class="text-center text-sm text-gray-500 py-4">
                            Belum ada distribusi data.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
