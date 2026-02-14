<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Users" />

        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center space-x-8">
                        <Link href="/dashboard" class="text-xl font-semibold text-gray-900">
                            {{ $page.props.app?.name || 'Graduate Tracking' }}
                        </Link>
                        <div class="hidden space-x-4 md:flex">
                            <Link href="/dashboard" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                Dashboard
                            </Link>
                            <Link href="/institutions" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                Institutions
                            </Link>
                            <Link href="/users" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white"> Users </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ $page.props.auth.user.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('users.export', filters)"
                                    class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    Export
                                </Link>
                                <Link
                                    :href="route('users.create')"
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-700"
                                >
                                    Add User
                                </Link>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-5">
                            <div class="rounded-lg bg-blue-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500">
                                            <span class="text-sm font-bold text-white">{{ statistics.total }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-blue-600">Total Users</p>
                                        <p class="text-2xl font-bold text-blue-900">{{ statistics.total }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-green-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500">
                                            <span class="text-sm font-bold text-white">{{ statistics.active }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-green-600">Active</p>
                                        <p class="text-2xl font-bold text-green-900">{{ statistics.active }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-red-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500">
                                            <span class="text-sm font-bold text-white">{{ statistics.suspended }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-red-600">Suspended</p>
                                        <p class="text-2xl font-bold text-red-900">{{ statistics.suspended }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500">
                                            <span class="text-sm font-bold text-white">{{ statistics.inactive }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600">Inactive</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ statistics.inactive }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-purple-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-500">
                                            <span class="text-sm font-bold text-white">{{ statistics.recent_logins }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-purple-600">Recent Logins</p>
                                        <p class="text-2xl font-bold text-purple-900">{{ statistics.recent_logins }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search and Filters -->
                        <div class="mb-6 flex flex-col gap-4 lg:flex-row">
                            <div class="flex-1">
                                <input
                                    v-model="form.search"
                                    type="text"
                                    placeholder="Search users by name, email, or phone..."
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                    @input="debounceSearch"
                                />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <select
                                    v-model="form.role"
                                    class="rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Roles</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.name">
                                        {{ role.name }}
                                    </option>
                                </select>
                                <select
                                    v-model="form.status"
                                    class="rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                                <select
                                    v-if="$page.props.auth.user.roles[0].name === 'super-admin'"
                                    v-model="form.institution"
                                    class="rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                                <select
                                    v-model="form.sort"
                                    class="rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="created_at">Sort by Date</option>
                                    <option value="name">Sort by Name</option>
                                    <option value="email">Sort by Email</option>
                                    <option value="last_login_at">Sort by Last Login</option>
                                </select>
                                <button @click="toggleSortDirection" class="rounded-md border border-gray-300 px-3 py-2 hover:bg-gray-50">
                                    {{ form.direction === 'asc' ? 'â†‘' : 'â†“' }}
                                </button>
                            </div>
                        </div>

                        <!-- Bulk Actions -->
                        <div v-if="selectedUsers.length > 0" class="mb-4 rounded-lg bg-blue-50 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700"> {{ selectedUsers.length }} user(s) selected </span>
                                <div class="flex space-x-2">
                                    <button
                                        @click="bulkAction('activate')"
                                        class="rounded bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700"
                                    >
                                        Activate
                                    </button>
                                    <button
                                        @click="bulkAction('deactivate')"
                                        class="rounded bg-yellow-600 px-3 py-1 text-sm text-white hover:bg-yellow-700"
                                    >
                                        Deactivate
                                    </button>
                                    <button @click="bulkAction('suspend')" class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">
                                        Suspend
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="overflow-hidden rounded-lg bg-white shadow">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                @change="toggleSelectAll"
                                                :checked="selectedUsers.length === users.data.length"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Institution</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Login</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="user.id"
                                                v-model="selectedUsers"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <img class="h-10 w-10 rounded-full" :src="user.avatar_url" :alt="user.name" />
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                    <div v-if="user.phone" class="text-sm text-gray-500">{{ user.phone }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">
                                                {{ user.roles[0]?.name || 'No Role' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {{ user.institution?.name || 'N/A' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                :class="`inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-${user.status_badge.color}-100 text-${user.status_badge.color}-800`"
                                            >
                                                {{ user.status_badge.text }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ user.last_seen }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <Link :href="route('users.show', user.id)" class="text-indigo-600 hover:text-indigo-900"> View </Link>
                                                <Link :href="route('users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900"> Edit </Link>
                                                <button
                                                    v-if="!user.is_suspended && user.id !== $page.props.auth.user.id"
                                                    @click="suspendUser(user)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Suspend
                                                </button>
                                                <button
                                                    v-if="user.is_suspended"
                                                    @click="unsuspendUser(user)"
                                                    class="text-green-600 hover:text-green-900"
                                                >
                                                    Unsuspend
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            <nav class="flex items-center justify-between">
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
                                                v-for="link in users.links"
                                                :key="link.label"
                                                :href="link.url"
                                                v-html="link.label"
                                                :class="[
                                                    'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                                    link.active
                                                        ? 'z-10 border-indigo-500 bg-indigo-50 text-indigo-600'
                                                        : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                                ]"
                                            />
                                        </nav>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suspend User Modal -->
        <div v-if="showSuspendModal" class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg">
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Suspend User</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">Please provide a reason for suspending {{ userToSuspend?.name }}:</p>
                        <textarea
                            v-model="suspendReason"
                            class="mt-3 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                            rows="3"
                            placeholder="Reason for suspension..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 px-7 py-3">
                        <button @click="showSuspendModal = false" class="rounded-md bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400">
                            Cancel
                        </button>
                        <button @click="confirmSuspend" class="rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700">Suspend</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { reactive, ref } from 'vue';

const props = defineProps({
    users: Object,
    statistics: Object,
    roles: Array,
    institutions: Array,
    filters: Object,
});

const form = reactive({
    search: props.filters.search || '',
    role: props.filters.role || '',
    status: props.filters.status || '',
    institution: props.filters.institution || '',
    sort: 'created_at',
    direction: 'desc',
});

const selectedUsers = ref([]);
const showSuspendModal = ref(false);
const userToSuspend = ref(null);
const suspendReason = ref('');

const search = () => {
    router.get(route('users.index'), form, {
        preserveState: true,
        replace: true,
    });
};

const debounceSearch = debounce(() => {
    search();
}, 300);

const toggleSortDirection = () => {
    form.direction = form.direction === 'asc' ? 'desc' : 'asc';
    search();
};

const toggleSelectAll = () => {
    if (selectedUsers.value.length === props.users.data.length) {
        selectedUsers.value = [];
    } else {
        selectedUsers.value = props.users.data.map((user) => user.id);
    }
};

const suspendUser = (user) => {
    userToSuspend.value = user;
    showSuspendModal.value = true;
    suspendReason.value = '';
};

const confirmSuspend = () => {
    if (!suspendReason.value.trim()) {
        alert('Please provide a reason for suspension');
        return;
    }

    router.post(
        route('users.suspend', userToSuspend.value.id),
        {
            reason: suspendReason.value,
        },
        {
            onSuccess: () => {
                showSuspendModal.value = false;
                userToSuspend.value = null;
                suspendReason.value = '';
            },
        },
    );
};

const unsuspendUser = (user) => {
    if (confirm(`Are you sure you want to unsuspend ${user.name}?`)) {
        router.post(route('users.unsuspend', user.id));
    }
};

const bulkAction = (action) => {
    if (selectedUsers.value.length === 0) return;

    let reason = '';
    if (action === 'suspend') {
        reason = prompt('Please provide a reason for suspension:');
        if (!reason) return;
    }

    const confirmMessage = `Are you sure you want to ${action} ${selectedUsers.value.length} user(s)?`;
    if (confirm(confirmMessage)) {
        router.post(
            route('users.bulk-action'),
            {
                action: action,
                user_ids: selectedUsers.value,
                reason: reason,
            },
            {
                onSuccess: () => {
                    selectedUsers.value = [];
                },
            },
        );
    }
};
</script>

