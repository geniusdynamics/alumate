<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="User Management" />
        
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage users across all institutions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filters -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by name or email..."
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                @input="debouncedSearch"
                            />
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select
                                id="role"
                                v-model="searchForm.role"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Users ({{ users.total }})
                    </h3>
                </div>
                
                <ul class="divide-y divide-gray-200">
                    <li v-for="user in users.data" :key="user.id" class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
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
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="getRoleClass(role.name)"
                                            >
                                                {{ role.name }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-1 flex items-center text-sm text-gray-500">
                                        <EnvelopeIcon class="h-4 w-4 mr-1" />
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-6">
                                <!-- User Info -->
                                <div class="text-sm text-gray-500">
                                    <div>Joined: {{ formatDate(user.created_at) }}</div>
                                    <div v-if="user.last_login_at">
                                        Last login: {{ formatDate(user.last_login_at) }}
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <Link
                                        :href="route('users.show', user.id)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="route('users.edit', user.id)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="confirmSuspend(user)"
                                        class="text-yellow-600 hover:text-yellow-900 text-sm font-medium"
                                    >
                                        Suspend
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                
                <div v-if="users.data.length === 0" class="text-center py-12">
                    <UserIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users.data.length > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6 rounded-lg shadow">
                <div class="flex-1 flex justify-between sm:hidden">
                    <Link
                        v-if="users.prev_page_url"
                        :href="users.prev_page_url"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="users.next_page_url"
                        :href="users.next_page_url"
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Next
                    </Link>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <Link
                                v-if="users.prev_page_url"
                                :href="users.prev_page_url"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="users.next_page_url"
                                :href="users.next_page_url"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suspend Confirmation Modal -->
        <div v-if="showSuspendModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-yellow-600" />
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Suspend User</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to suspend "{{ userToSuspend?.name }}"? 
                            They will not be able to access the system until reactivated.
                        </p>
                    </div>
                    <div class="flex justify-center space-x-3 mt-4">
                        <button
                            @click="showSuspendModal = false"
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            @click="suspendUser"
                            class="px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700"
                        >
                            Suspend
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import {
    UserIcon,
    EnvelopeIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';
import { debounce } from 'lodash';

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
        'employer': 'bg-green-100 text-green-800',
        'graduate': 'bg-purple-100 text-purple-800',
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
        router.post(route('users.suspend', userToSuspend.value.id), {}, {
            onSuccess: () => {
                showSuspendModal.value = false;
                userToSuspend.value = null;
            },
        });
    }
};
</script>