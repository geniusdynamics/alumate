<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Staff Management" />
        
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <Link :href="route('institution-admin.dashboard')" class="text-xl font-semibold text-gray-900 hover:text-gray-700">
                            Institution Admin
                        </Link>
                        <nav class="flex space-x-8">
                            <Link :href="route('institution-admin.dashboard')" class="text-gray-500 hover:text-gray-700">Dashboard</Link>
                            <Link :href="route('institution-admin.analytics')" class="text-gray-500 hover:text-gray-700">Analytics</Link>
                            <Link :href="route('institution-admin.reports')" class="text-gray-500 hover:text-gray-700">Reports</Link>
                            <Link :href="route('institution-admin.staff')" class="text-blue-600 font-medium">Staff</Link>
                        </nav>
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
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Staff Management</h1>
                        <p class="mt-2 text-gray-600">Manage institution staff members and their roles</p>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Staff Member
                    </button>
                </div>

                <!-- Staff List -->
                <div class="bg-white shadow rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="member in staff.data" :key="member.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-gray-700">
                                                            {{ member.name.charAt(0).toUpperCase() }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ member.name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ member.email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                                :class="{
                                                    'bg-blue-100 text-blue-800': member.user_type === 'institution-admin',
                                                    'bg-green-100 text-green-800': member.user_type === 'tutor'
                                                }"
                                            >
                                                {{ member.user_type.replace('-', ' ') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                :class="{
                                                    'bg-green-100 text-green-800': member.status === 'active',
                                                    'bg-red-100 text-red-800': member.status === 'suspended'
                                                }"
                                            >
                                                {{ member.status || 'active' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ member.last_login_at ? formatDate(member.last_login_at) : 'Never' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button
                                                    @click="editStaff(member)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    @click="toggleSuspension(member)"
                                                    class="text-yellow-600 hover:text-yellow-900"
                                                >
                                                    {{ member.status === 'suspended' ? 'Activate' : 'Suspend' }}
                                                </button>
                                                <button
                                                    @click="deleteStaff(member)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6 flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="staff.prev_page_url"
                                    :href="staff.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="staff.next_page_url"
                                    :href="staff.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ staff.from }} to {{ staff.to }} of {{ staff.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link
                                            v-if="staff.prev_page_url"
                                            :href="staff.prev_page_url"
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                        <Link
                                            v-if="staff.next_page_url"
                                            :href="staff.next_page_url"
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Staff Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ showCreateModal ? 'Add Staff Member' : 'Edit Staff Member' }}
                    </h3>
                    <form @submit.prevent="submitStaffForm">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input
                                v-model="staffForm.name"
                                type="text"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                v-model="staffForm.email"
                                type="email"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <div class="mb-4" v-if="showCreateModal">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input
                                v-model="staffForm.password"
                                type="password"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <select
                                v-model="staffForm.user_type"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select Role</option>
                                <option value="institution-admin">Institution Admin</option>
                                <option value="tutor">Tutor</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                {{ showCreateModal ? 'Create' : 'Update' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';

const props = defineProps({
    staff: Object,
    roles: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingStaff = ref(null);

const staffForm = reactive({
    name: '',
    email: '',
    password: '',
    user_type: '',
});

const editStaff = (member) => {
    editingStaff.value = member;
    staffForm.name = member.name;
    staffForm.email = member.email;
    staffForm.user_type = member.user_type;
    staffForm.password = '';
    showEditModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingStaff.value = null;
    Object.keys(staffForm).forEach(key => {
        staffForm[key] = '';
    });
};

const submitStaffForm = () => {
    if (showCreateModal.value) {
        router.post(route('users.store'), staffForm, {
            onSuccess: () => {
                closeModal();
            }
        });
    } else {
        router.put(route('users.update', editingStaff.value.id), {
            name: staffForm.name,
            email: staffForm.email,
            user_type: staffForm.user_type,
        }, {
            onSuccess: () => {
                closeModal();
            }
        });
    }
};

const toggleSuspension = (member) => {
    if (confirm(`Are you sure you want to ${member.status === 'suspended' ? 'activate' : 'suspend'} this staff member?`)) {
        router.post(route('users.suspend', member.id), {}, {
            preserveScroll: true,
        });
    }
};

const deleteStaff = (member) => {
    if (confirm('Are you sure you want to delete this staff member? This action cannot be undone.')) {
        router.delete(route('users.destroy', member.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>