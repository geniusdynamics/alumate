<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="User Management" />

        <!-- Header -->
        <div class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage users across all institutions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="mb-6 rounded-lg bg-white shadow">
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by name or email..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                @input="debouncedSearch"
                            />
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select
                                id="role"
                                v-model="searchForm.role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                @change="search"
                            >
                                <option value="">All Roles</option>
                                <option value="super-admin">Super Admin</option>
                                <option value="institution-admin">Institution Admin</option>
                                <option value="employer">Employer</option>
                                <option value="graduate">Graduate</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Users ({{ users.total }})</h3>
                </div>

                <ul class="divide-y divide-gray-200">
                    <li v-for="user in users.data" :key="user.id" class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300">
                                        <UserIcon class="h-6 w-6 text-gray-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="flex items-center">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ user.name }}
                                        </p>
                                        <div class="ml-2 flex space-x-1">
                                            <span
                                                v-for="role in user.roles"
                                                :key="role.id"
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="getRoleClass(role.name)"
                                            >
                                                {{ role.name }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-1 flex items-center text-sm text-gray-500">
                                        <EnvelopeIcon class="mr-1 h-4 w-4" />
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <!-- User Info -->
                                <div class="text-sm text-gray-500">
                                    <div>Joined: {{ formatDate(user.created_at) }}</div>
                                    <div v-if="user.last_login_at">Last login: {{ formatDate(user.last_login_at) }}</div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <Link :href="route('users.show', user.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                        View
                                    </Link>
                                    <Link :href="route('users.edit', user.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </Link>
                                    <button @click="confirmSuspend(user)" class="text-sm font-medium text-yellow-600 hover:text-yellow-900">
                                        Suspend
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>

                <div v-if="users.data.length === 0" class="py-12 text-center">
                    <UserIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="users.data.length > 0"
                class="mt-6 flex items-center justify-between rounded-lg border-t border-gray-200 bg-white px-4 py-3 shadow sm:px-6"
            >
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link
                        v-if="users.prev_page_url"
                        :href="users.prev_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="users.next_page_url"
                        :href="users.next_page_url"
                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Next
                    </Link>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results</p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                            <Link
                                v-if="users.prev_page_url"
                                :href="users.prev_page_url"
                                class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="users.next_page_url"
                                :href="users.next_page_url"
                                class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suspend Confirmation Modal -->
        <div v-if="showSuspendModal" class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg">
                <div class="mt-3 text-center">
                    <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-yellow-600" />
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Suspend User</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to suspend "{{ userToSuspend?.name }}"? They will not be able to access the system until
                            reactivated.
                        </p>
                    </div>
                    <div class="mt-4 flex justify-center space-x-3">
                        <button
                            @click="showSuspendModal = false"
                            class="rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button @click="suspendUser" class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700">
                            Suspend
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { EnvelopeIcon, ExclamationTriangleIcon, UserIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { debounce } from 'lodash';
import { reactive, ref } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const showSuspendModal = ref(false);
const userToSuspend = ref(null);

const searchForm = reactive({
    search: props.filters.search || '',
    role: props.filters.role || '',
});

const getRoleClass = (roleName) => {
    const classes = {
        'super-admin': 'bg-red-100 text-red-800',
        'institution-admin': 'bg-blue-100 text-blue-800',
        employer: 'bg-green-100 text-green-800',
        graduate: 'bg-purple-100 text-purple-800',
    };
    return classes[roleName] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const search = () => {
    router.get(route('super-admin.users'), searchForm, {
        preserveState: true,
        replace: true,
    });
};

const debouncedSearch = debounce(search, 300);

const clearFilters = () => {
    searchForm.search = '';
    searchForm.role = '';
    search();
};

const confirmSuspend = (user) => {
    userToSuspend.value = user;
    showSuspendModal.value = true;
};

const suspendUser = () => {
    if (userToSuspend.value) {
        router.post(
            route('users.suspend', userToSuspend.value.id),
            {},
            {
                onSuccess: () => {
                    showSuspendModal.value = false;
                    userToSuspend.value = null;
                },
            },
        );
    }
};
</script>

