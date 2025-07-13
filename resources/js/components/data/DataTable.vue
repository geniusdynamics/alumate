<script setup lang="ts" generic="T extends Record<string, any>">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { debounce } from 'lodash-es';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

export interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    searchable?: boolean;
    render?: (value: any, row: T) => string;
}

export interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface DataTableProps<T> {
    data: T[];
    columns: Column[];
    pagination?: PaginationData;
    searchable?: boolean;
    title?: string;
    loading?: boolean;
    perPageOptions?: number[];
}

const props = withDefaults(defineProps<DataTableProps<T>>(), {
    searchable: true,
    title: '',
    loading: false,
    perPageOptions: () => [10, 25, 50, 100],
});

const emit = defineEmits<{
    search: [query: string];
    sort: [column: string, direction: 'asc' | 'desc'];
    paginate: [page: number];
    perPageChange: [perPage: number];
}>();

const searchQuery = ref('');
const sortColumn = ref('');
const sortDirection = ref<'asc' | 'desc'>('asc');

const debouncedSearch = debounce((query: string) => {
    emit('search', query);
}, 300);

watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery);
});

const handleSort = (column: Column) => {
    if (!column.sortable) return;

    if (sortColumn.value === column.key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column.key;
        sortDirection.value = 'asc';
    }

    emit('sort', column.key, sortDirection.value);
};

const handlePageChange = (page: number) => {
    if (page < 1 || (props.pagination && page > props.pagination.last_page)) return;
    emit('paginate', page);
};

const handlePerPageChange = (perPage: string) => {
    emit('perPageChange', parseInt(perPage));
};

const getSortIcon = (column: Column) => {
    if (!column.sortable) return ArrowUpDown;
    if (sortColumn.value !== column.key) return ArrowUpDown;
    return sortDirection.value === 'asc' ? ArrowUp : ArrowDown;
};

const renderCell = (column: Column, row: T) => {
    const value = row[column.key];
    return column.render ? column.render(value, row) : value;
};

const pageNumbers = computed(() => {
    if (!props.pagination) return [];

    const { current_page, last_page } = props.pagination;
    const delta = 2;
    const range = [];
    const rangeWithDots = [];

    for (let i = Math.max(2, current_page - delta); i <= Math.min(last_page - 1, current_page + delta); i++) {
        range.push(i);
    }

    if (current_page - delta > 2) {
        rangeWithDots.push(1, '...');
    } else {
        rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (current_page + delta < last_page - 1) {
        rangeWithDots.push('...', last_page);
    } else if (last_page > 1) {
        rangeWithDots.push(last_page);
    }

    return rangeWithDots;
});
</script>

<template>
    <Card>
        <CardHeader v-if="title || searchable">
            <div class="flex items-center justify-between">
                <CardTitle v-if="title">{{ title }}</CardTitle>
                <div v-if="searchable" class="relative max-w-sm">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                    <Input v-model="searchQuery" placeholder="Search..." class="pl-10" />
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <div class="relative">
                <div v-if="loading" class="bg-background/50 absolute inset-0 z-10 flex items-center justify-center rounded-md backdrop-blur-sm">
                    <div class="flex items-center space-x-2">
                        <div class="border-primary h-4 w-4 animate-spin rounded-full border-2 border-t-transparent"></div>
                        <span class="text-muted-foreground text-sm">Loading...</span>
                    </div>
                </div>

                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead
                                    v-for="column in columns"
                                    :key="column.key"
                                    :class="[column.sortable ? 'hover:text-foreground cursor-pointer select-none' : '']"
                                    @click="handleSort(column)"
                                >
                                    <div class="flex items-center space-x-2">
                                        <span>{{ column.label }}</span>
                                        <component
                                            v-if="column.sortable"
                                            :is="getSortIcon(column)"
                                            class="h-4 w-4"
                                            :class="{
                                                'text-foreground': sortColumn === column.key,
                                                'text-muted-foreground/50': sortColumn !== column.key,
                                            }"
                                        />
                                    </div>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="data.length === 0">
                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <div class="text-muted-foreground">No data available</div>
                                        <div class="text-muted-foreground/70 text-sm">Try adjusting your search or filters</div>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="(row, index) in data" :key="index">
                                <TableCell v-for="column in columns" :key="column.key">
                                    <slot :name="`cell.${column.key}`" :value="row[column.key]" :row="row">
                                        {{ renderCell(column, row) }}
                                    </slot>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="pagination" class="flex items-center justify-between space-x-2 py-4">
                <div class="flex items-center space-x-2">
                    <span class="text-muted-foreground text-sm">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
                    </span>
                    <Select :model-value="pagination.per_page.toString()" @update:model-value="handlePerPageChange">
                        <SelectTrigger class="w-20">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in perPageOptions" :key="option" :value="option.toString()">
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span class="text-muted-foreground text-sm">per page</span>
                </div>

                <div class="flex items-center space-x-1">
                    <Button variant="outline" size="sm" :disabled="pagination.current_page === 1" @click="handlePageChange(1)">
                        <ChevronsLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="pagination.current_page === 1"
                        @click="handlePageChange(pagination.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>

                    <template v-for="page in pageNumbers" :key="page">
                        <Button v-if="page === '...'" variant="ghost" size="sm" disabled> ... </Button>
                        <Button
                            v-else
                            :variant="pagination.current_page === page ? 'default' : 'outline'"
                            size="sm"
                            @click="handlePageChange(page as number)"
                        >
                            {{ page }}
                        </Button>
                    </template>

                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="pagination.current_page === pagination.last_page"
                        @click="handlePageChange(pagination.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="pagination.current_page === pagination.last_page"
                        @click="handlePageChange(pagination.last_page)"
                    >
                        <ChevronsRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
