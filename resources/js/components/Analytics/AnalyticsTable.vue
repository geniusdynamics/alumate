<template>
    <div class="analytics-table">
        <div class="table-header">
            <h4 class="table-title">{{ title }}</h4>
            <div class="table-actions">
                <button @click="exportTable" class="action-button" title="Export table data">
                    <Icon name="download" class="h-4 w-4" />
                </button>
                <button @click="refreshTable" class="action-button" title="Refresh data">
                    <Icon name="refresh-cw" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead class="table-head">
                    <tr>
                        <th v-for="column in columns" :key="column.key" class="table-header-cell" @click="sortBy(column.key)">
                            <div class="header-content">
                                <span>{{ column.label }}</span>
                                <Icon
                                    v-if="sortColumn === column.key"
                                    :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'"
                                    class="ml-1 h-4 w-4"
                                />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="table-body">
                    <tr v-for="(row, index) in sortedData" :key="index" class="table-row">
                        <td v-for="column in columns" :key="column.key" class="table-cell">
                            <div class="cell-content">
                                {{ formatCellValue(row[column.key], column) }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="sortedData.length === 0" class="empty-state">
                <Icon name="inbox" class="h-8 w-8 text-gray-400" />
                <p class="empty-text">No data available</p>
            </div>
        </div>

        <div v-if="showPagination && totalPages > 1" class="table-pagination">
            <div class="pagination-info">Showing {{ startIndex + 1 }}-{{ endIndex }} of {{ totalItems }} items</div>

            <div class="pagination-controls">
                <button @click="previousPage" :disabled="currentPage === 1" class="pagination-button">
                    <Icon name="chevron-left" class="h-4 w-4" />
                </button>

                <span class="page-info"> Page {{ currentPage }} of {{ totalPages }} </span>

                <button @click="nextPage" :disabled="currentPage === totalPages" class="pagination-button">
                    <Icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import { computed, ref } from 'vue';

interface Column {
    key: string;
    label: string;
    type?: 'text' | 'number' | 'date' | 'percentage';
    sortable?: boolean;
}

interface Props {
    title: string;
    data: any[];
    columns: Column[];
    pageSize?: number;
    showPagination?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    pageSize: 10,
    showPagination: true,
});

const emit = defineEmits<{
    export: [];
    refresh: [];
}>();

const sortColumn = ref<string>('');
const sortDirection = ref<'asc' | 'desc'>('asc');
const currentPage = ref(1);

const sortedData = computed(() => {
    const data = [...props.data];

    if (sortColumn.value) {
        data.sort((a, b) => {
            const aValue = a[sortColumn.value];
            const bValue = b[sortColumn.value];

            if (typeof aValue === 'number' && typeof bValue === 'number') {
                return sortDirection.value === 'asc' ? aValue - bValue : bValue - aValue;
            }

            const aStr = String(aValue).toLowerCase();
            const bStr = String(bValue).toLowerCase();

            if (sortDirection.value === 'asc') {
                return aStr.localeCompare(bStr);
            } else {
                return bStr.localeCompare(aStr);
            }
        });
    }

    if (props.showPagination) {
        const start = (currentPage.value - 1) * props.pageSize;
        const end = start + props.pageSize;
        return data.slice(start, end);
    }

    return data;
});

const totalItems = computed(() => props.data.length);
const totalPages = computed(() => Math.ceil(totalItems.value / props.pageSize));
const startIndex = computed(() => (currentPage.value - 1) * props.pageSize);
const endIndex = computed(() => Math.min(startIndex.value + props.pageSize, totalItems.value));

const sortBy = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const formatCellValue = (value: any, column: Column) => {
    if (value === null || value === undefined) {
        return '-';
    }

    switch (column.type) {
        case 'number':
            return typeof value === 'number' ? value.toLocaleString() : value;
        case 'percentage':
            return typeof value === 'number' ? `${value.toFixed(1)}%` : value;
        case 'date':
            return new Date(value).toLocaleDateString();
        default:
            return value;
    }
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const exportTable = () => {
    emit('export');
};

const refreshTable = () => {
    emit('refresh');
};
</script>

<style scoped>
.analytics-table {
    @apply w-full;
}

.table-header {
    @apply mb-4 flex items-center justify-between;
}

.table-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.table-actions {
    @apply flex items-center space-x-2;
}

.action-button {
    @apply p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
    @apply rounded-md transition-colors hover:bg-gray-100 dark:hover:bg-gray-700;
}

.table-container {
    @apply overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700;
}

.data-table {
    @apply w-full divide-y divide-gray-200 dark:divide-gray-700;
}

.table-head {
    @apply bg-gray-50 dark:bg-gray-800;
}

.table-header-cell {
    @apply px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400;
    @apply cursor-pointer transition-colors hover:bg-gray-100 dark:hover:bg-gray-700;
}

.header-content {
    @apply flex items-center;
}

.table-body {
    @apply divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900;
}

.table-row {
    @apply transition-colors hover:bg-gray-50 dark:hover:bg-gray-800;
}

.table-cell {
    @apply whitespace-nowrap px-6 py-4;
}

.cell-content {
    @apply text-sm text-gray-900 dark:text-white;
}

.empty-state {
    @apply flex flex-col items-center justify-center py-12;
}

.empty-text {
    @apply mt-2 text-gray-500 dark:text-gray-400;
}

.table-pagination {
    @apply mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800;
}

.pagination-info {
    @apply text-sm text-gray-700 dark:text-gray-300;
}

.pagination-controls {
    @apply flex items-center space-x-4;
}

.pagination-button {
    @apply p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
    @apply rounded-md transition-colors hover:bg-gray-100 dark:hover:bg-gray-700;
    @apply disabled:cursor-not-allowed disabled:opacity-50;
}

.page-info {
    @apply text-sm text-gray-700 dark:text-gray-300;
}
</style>

