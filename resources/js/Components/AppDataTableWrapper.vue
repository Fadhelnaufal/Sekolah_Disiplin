<script setup lang="ts">
import DataTable from 'primevue/datatable';
import AppEmptyState from './AppEmptyState.vue';

withDefaults(
    defineProps<{
        value: Array<any>;
        paginator?: boolean;
        rows?: number;
        rowsPerPageOptions?: number[];
        paginatorTemplate?: string;
        currentPageReportTemplate?: string;
        responsiveLayout?: 'stack' | 'scroll';
        breakpoint?: string;
        removableSort?: boolean;
        emptyTitle?: string;
        emptyMessage?: string;
        emptyIcon?: string;
    }>(),
    {
        paginator: true,
        rows: 15,
        rowsPerPageOptions: () => [10, 15, 25, 50],
        paginatorTemplate: 'CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown',
        currentPageReportTemplate: 'Menampilkan {first} sampai {last} dari {totalRecords} data',
        responsiveLayout: 'stack',
        breakpoint: '768px',
        removableSort: true,
        emptyTitle: 'Tidak ada data ditemukan',
        emptyMessage: 'Tidak ada data yang cocok dengan kriteria filter atau pencarian saat ini.',
        emptyIcon: 'pi pi-inbox',
    }
);
</script>

<template>
    <DataTable
        v-bind="$attrs"
        :value="value"
        :paginator="paginator && value && value.length > 0"
        :rows="rows"
        :rowsPerPageOptions="rowsPerPageOptions"
        :paginatorTemplate="paginatorTemplate"
        :currentPageReportTemplate="currentPageReportTemplate"
        :responsiveLayout="responsiveLayout"
        :breakpoint="breakpoint"
        :removableSort="removableSort"
        class="p-datatable-sm"
    >
        <!-- Forward all slots (including Column components and custom headers/bodies) -->
        <template v-for="(_, slotName) in $slots" #[slotName]="slotProps">
            <slot :name="slotName" v-bind="slotProps || {}" />
        </template>

        <!-- Default Empty Slot if parent didn't specify one -->
        <template v-if="!$slots.empty" #empty>
            <AppEmptyState
                :title="emptyTitle"
                :description="emptyMessage"
                :icon="emptyIcon"
            />
        </template>
    </DataTable>
</template>

