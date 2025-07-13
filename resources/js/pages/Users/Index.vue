<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import DataTable, { type Column, type PaginationData } from '@/components/data/DataTable.vue';
import { useDataTable } from '@/composables/useDataTable';
import type { BreadcrumbItemType, Role, User } from '@/types/index.d';

import { Edit, Eye, MoreHorizontal, Plus, Trash } from 'lucide-vue-next';

interface Props {
    users: User[];
    pagination: PaginationData;
    filters: {
        search?: string;
        sort_column?: string;
        sort_direction?: string;
    };
}

const props = defineProps<Props>();

const columns: Column[] = [
    {
        key: 'name',
        label: 'Name',
        sortable: true,
        searchable: true,
    },
    {
        key: 'email',
        label: 'Email',
        sortable: true,
        searchable: true,
    },
    {
        key: 'roles',
        label: 'Roles',
        sortable: false,
        render: (value: Role[]) => value.map((role) => role.name).join(', '),
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

const deleteUser = (user: User) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(route('users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};

const breadcrumbs: BreadcrumbItemType[] = [{ title: 'Dashboard', href: route('dashboard') }, { title: 'Users' }];
</script>

<template>
    <Head title="Users" />
    <DefaultLayout title="Users" :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Users</h1>
                <Button as="a" :href="route('users.create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </div>

            <DataTable
                :data="users"
                :columns="columns"
                :pagination="pagination"
                :loading="loading"
                :filters="filters"
                title="All Users"
                @search="(query) => handleSearch(query, { ...props.filters }, route('users.index'))"
                @sort="(column, direction) => handleSort(column, direction, { ...props.filters }, route('users.index'))"
                @paginate="(page) => handlePaginate(page, { ...props.filters }, route('users.index'))"
                @per-page-change="(perPage) => handlePerPageChange(perPage, { ...props.filters }, route('users.index'))"
            >
                <template #cell.roles="{ row }">
                    <div class="flex flex-wrap gap-1">
                        <Badge v-for="role in row.roles" :key="role.id" variant="secondary" class="capitalize">
                            {{ role.name }}
                        </Badge>
                    </div>
                </template>

                <template #cell.actions="{ row }">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem :as="Link" :href="route('users.show', row.id)">
                                <Eye class="mr-2 h-4 w-4" />
                                View
                            </DropdownMenuItem>
                            <DropdownMenuItem :as="Link" :href="route('users.edit', row.id)">
                                <Edit class="mr-2 h-4 w-4" />
                                Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteUser(row)" class="text-destructive focus:bg-destructive/10 focus:text-destructive">
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
