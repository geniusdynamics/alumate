<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Users" />
        
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <Link href="/dashboard" class="text-xl font-semibold text-gray-900">
                            {{ $page.props.app?.name || 'Graduate Tracking' }}
                        </Link>
                        <div class="hidden md:flex space-x-4">
                            <Link href="/dashboard" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                Dashboard
                            </Link>
                            <Link href="/institutions" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                Institutions
                            </Link>
                            <Link href="/users" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium">
                                Users
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ $page.props.auth.user.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">
                                User Management
                            </h2>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('users.export', filters)"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Export
                                </Link>
                                <Link
                                    :href="route('users.create')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Add User
                                </Link>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                            <div class="bg-blue-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">{{ statistics.total }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-blue-600">Total Users</p>
                                        <p class="text-2xl font-bold text-blue-900">{{ statistics.total }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-green-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">{{ statistics.active }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-green-600">Active</p>
                                        <p class="text-2xl font-bold text-green-900">{{ statistics.active }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-red-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">{{ statistics.suspended }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-red-600">Suspended</p>
                                        <p class="text-2xl font-bold text-red-900">{{ statistics.suspended }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">{{ statistics.inactive }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600">Inactive</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ statistics.inactive }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-purple-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">{{ statistics.recent_logins }}</span>
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
                        <div class="mb-6 flex flex-col lg:flex-row gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="form.search"
                                    type="text"
                                    placeholder="Search users by name, email, or phone..."
                                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    @input="debounceSearch"
                                />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <select
                                    v-model="form.role"
                                    class="border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Roles</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.name">
                                        {{ role.name }}
                                    </option>
                                </select>
                                <select
                                    v-model="form.status"
                                    class="border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
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
                                    class="border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                                <select
                                    v-model="form.sort"
                                    class="border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    @change="search"
                                >
                                    <option value="created_at">Sort by Date</option>
                                    <option value="name">Sort by Name</option>
                                    <option value="email">Sort by Email</option>
                                    <option value="last_login_at">Sort by Last Login</option>
                                </select>
                                <button
                                    @click="toggleSortDirection"
                                    class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    {{ form.direction === 'asc' ? '↑' : '↓' }}
                                </button>
                            </div>
                        </div>

                        <!-- Bulk Actions -->
                        <div v-if="selectedUsers.length > 0" class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700">
                                    {{ selectedUsers.length }} user(s) selected
                                </span>
                                <div class="flex space-x-2">
                                    <button
                                        @click="bulkAction('activate')"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700"
                                    >
                                        Activate
                                    </button>
                                    <button
                                        @click="bulkAction('deactivate')"
                                        class="px-3 py-1 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700"
                                    >
                                        Deactivate
                                    </button>
                                    <button
                                        @click="bulkAction('suspend')"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700"
                                    >
                                        Suspend
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Role
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Institution
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Last Login
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="user.id"
                                                v-model="selectedUsers"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full" :src="user.avatar_url" :alt="user.name" />
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                    <div v-if="user.phone" class="text-sm text-gray-500">{{ user.phone }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ user.roles[0]?.name || 'No Role' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ user.institution?.name || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-${user.status_badge.color}-100 text-${user.status_badge.color}-800`"
                                            >
                                                {{ user.status_badge.text }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ user.last_seen }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <Link
                                                    :href="route('users.show', user.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('users.edit', user.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
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
                                                v-for="link in users.links"
                                                :key="link.label"
                                                :href="link.url"
                                                v-html="link.label"
                                                :class="[
                                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                    link.active
                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
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
        <div v-if="showSuspendModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Suspend User</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Please provide a reason for suspending {{ userToSuspend?.name }}:
                        </p>
                        <textarea
                            v-model="suspendReason"
                            class="mt-3 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            rows="3"
                            placeholder="Reason for suspension..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 px-7 py-3">
                        <button
                            @click="showSuspendModal = false"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmSuspend"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
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
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

const props = defineProps({
    users: Object,
    statistics: Object,
    roles: Array,
    institutions: Array,
    filters: Object
})

const form = reactive({
    search: props.filters.search || '',
    role: props.filters.role || '',
    status: props.filters.status || '',
    institution: props.filters.institution || '',
    sort: 'created_at',
    direction: 'desc'
})

const selectedUsers = ref([])
const showSuspendModal = ref(false)
const userToSuspend = ref(null)
const suspendReason = ref('')

const search = () => {
    router.get(route('users.index'), form, {
        preserveState: true,
        replace: true
    })
}

const debounceSearch = debounce(() => {
    search()
}, 300)

const toggleSortDirection = () => {
    form.direction = form.direction === 'asc' ? 'desc' : 'asc'
    search()
}

const toggleSelectAll = () => {
    if (selectedUsers.value.length === props.users.data.length) {
        selectedUsers.value = []
    } else {
        selectedUsers.value = props.users.data.map(user => user.id)
    }
}

const suspendUser = (user) => {
    userToSuspend.value = user
    showSuspendModal.value = true
    suspendReason.value = ''
}

const confirmSuspend = () => {
    if (!suspendReason.value.trim()) {
        alert('Please provide a reason for suspension')
        return
    }

    router.post(route('users.suspend', userToSuspend.value.id), {
        reason: suspendReason.value
    }, {
        onSuccess: () => {
            showSuspendModal.value = false
            userToSuspend.value = null
            suspendReason.value = ''
        }
    })
}

const unsuspendUser = (user) => {
    if (confirm(`Are you sure you want to unsuspend ${user.name}?`)) {
        router.post(route('users.unsuspend', user.id))
    }
}

const bulkAction = (action) => {
    if (selectedUsers.value.length === 0) return

    let reason = ''
    if (action === 'suspend') {
        reason = prompt('Please provide a reason for suspension:')
        if (!reason) return
    }

    const confirmMessage = `Are you sure you want to ${action} ${selectedUsers.value.length} user(s)?`
    if (confirm(confirmMessage)) {
        router.post(route('users.bulk-action'), {
            action: action,
            user_ids: selectedUsers.value,
            reason: reason
        }, {
            onSuccess: () => {
                selectedUsers.value = []
            }
        })
    }
}
</script>
