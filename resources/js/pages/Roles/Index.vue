<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import DataTable, { type Column, type PaginationData } from '@/components/data/DataTable.vue';
import { useDataTable } from '@/composables/useDataTable';
import type { BreadcrumbItemType, Role } from '@/types/index.d';

import { Edit, MoreHorizontal, Plus, Trash } from 'lucide-vue-next';

interface Props {
    roles: Role[];
    pagination: PaginationData;
    filters: {
        search?: string;
        sort_column?: string;
        sort_direction?: string;
    };
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [{ title: 'Dashboard', href: route('dashboard') }, { title: 'Roles' }];

const columns: Column[] = [
    {
        key: 'name',
        label: 'Name',
        sortable: true,
        searchable: true,
    },
    {
        key: 'created_at',
        label: 'Created',
        sortable: true,
        render: (value: string) => new Date(value).toLocaleDateString(),
    },
    {
        key: 'actions',
        label: 'Actions',
        sortable: false,
    },
];

const { loading, handleSearch, handleSort, handlePaginate, handlePerPageChange } = useDataTable();

const deleteRole = (role: Role) => {
    if (confirm('Are you sure you want to delete this role?')) {
        router.delete(route('roles.destroy', role.id), {
            preserveScroll: true,
        });
    }
};
</script>
<template>
    <Head title="All Roles" />
    <DefaultLayout title="All Roles" :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Roles</h1>
                <Button as="a" :href="route('roles.create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Role
                </Button>
            </div>
            <DataTable
                :data="roles"
                :columns="columns"
                :pagination="pagination"
                :loading="loading"
                :filters="filters"
                title="All Roles"
                @search="(query) => handleSearch(query, { ...props.filters }, route('roles.index'))"
                @sort="(column, direction) => handleSort(column, direction, { ...props.filters }, route('roles.index'))"
                @paginate="(page) => handlePaginate(page, { ...props.filters }, route('roles.index'))"
                @per-page-change="(perPage) => handlePerPageChange(perPage, { ...props.filters }, route('roles.index'))"
            >
                <template #cell.actions="{ row }">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem :as="Link" :href="route('roles.edit', row.id)">
                                <Edit class="mr-2 h-4 w-4" />
                                Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteRole(row)" class="text-destructive focus:bg-destructive/10 focus:text-destructive">
                                <Trash class="mr-2 h-4 w-4" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
            </DataTable>
        </div>
    </DefaultLayout>
</template>
