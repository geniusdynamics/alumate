<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Institution Management" />

        <!-- Header -->
        <div class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Institution Management</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage all institutions in the system</p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('institutions.create')"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                            Add Institution
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
                <StatCard title="Total Institutions" :value="institutions.length" icon="BuildingOfficeIcon" color="blue" />
                <StatCard title="Active Institutions" :value="activeInstitutions" icon="CheckCircleIcon" color="green" />
                <StatCard title="Total Users" :value="totalUsers" icon="UsersIcon" color="purple" />
                <StatCard title="Total Graduates" :value="totalGraduates" icon="AcademicCapIcon" color="yellow" />
            </div>

            <!-- Institutions Table -->
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">All Institutions</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage and monitor all institutions in the system</p>
                </div>

                <ul class="divide-y divide-gray-200">
                    <li v-for="institution in institutions" :key="institution.id" class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <BuildingOfficeIcon class="h-10 w-10 text-gray-400" />
                                </div>
                                <div class="ml-4">
                                    <div class="flex items-center">
                                        <p class="truncate text-sm font-medium text-indigo-600">
                                            {{ institution.name }}
                                        </p>
                                        <span
                                            class="ml-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusClass(institution.status)"
                                        >
                                            {{ institution.status }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center text-sm text-gray-500">
                                        <p>{{ institution.domains.join(', ') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <!-- Stats -->
                                <div class="flex space-x-4 text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <UsersIcon class="mr-1 h-4 w-4" />
                                        {{ institution.users_count }} users
                                    </div>
                                    <div class="flex items-center">
                                        <AcademicCapIcon class="mr-1 h-4 w-4" />
                                        {{ institution.graduates_count }} graduates
                                    </div>
                                    <div class="flex items-center">
                                        <BookOpenIcon class="mr-1 h-4 w-4" />
                                        {{ institution.courses_count }} courses
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <Link
                                        :href="route('institutions.show', institution.id)"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="route('institutions.edit', institution.id)"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                    >
                                        Edit
                                    </Link>
                                    <button @click="confirmDelete(institution)" class="text-sm font-medium text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="mt-3 text-sm text-gray-500">
                            <p>Created: {{ formatDate(institution.created_at) }}</p>
                        </div>
                    </li>
                </ul>

                <div v-if="institutions.length === 0" class="py-12 text-center">
                    <BuildingOfficeIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No institutions</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new institution.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('institutions.create')"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                        >
                            <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                            Add Institution
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg">
                <div class="mt-3 text-center">
                    <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-600" />
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Delete Institution</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to delete "{{ institutionToDelete?.name }}"? This action cannot be undone and will affect all
                            associated data.
                        </p>
                    </div>
                    <div class="mt-4 flex justify-center space-x-3">
                        <button
                            @click="showDeleteModal = false"
                            class="rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button @click="deleteInstitution" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import StatCard from '@/Components/StatCard.vue';
import { AcademicCapIcon, BookOpenIcon, BuildingOfficeIcon, ExclamationTriangleIcon, PlusIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { computed, ref } from 'vue';

const props = defineProps({
    institutions: Array,
});

const showDeleteModal = ref(false);
const institutionToDelete = ref(null);

const activeInstitutions = computed(() => {
    return props.institutions.filter((inst) => inst.status === 'active').length;
});

const totalUsers = computed(() => {
    return props.institutions.reduce((sum, inst) => sum + inst.users_count, 0);
});

const totalGraduates = computed(() => {
    return props.institutions.reduce((sum, inst) => sum + inst.graduates_count, 0);
});

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-red-100 text-red-800',
        suspended: 'bg-yellow-100 text-yellow-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const confirmDelete = (institution) => {
    institutionToDelete.value = institution;
    showDeleteModal.value = true;
};

const deleteInstitution = () => {
    if (institutionToDelete.value) {
        router.delete(route('institutions.destroy', institutionToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                institutionToDelete.value = null;
            },
        });
    }
};
</script>

