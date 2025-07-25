<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Institution Management" />
        
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Institution Management</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage all institutions in the system</p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('institutions.create')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                            Add Institution
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title="Total Institutions"
                    :value="institutions.length"
                    icon="BuildingOfficeIcon"
                    color="blue"
                />
                <StatCard
                    title="Active Institutions"
                    :value="activeInstitutions"
                    icon="CheckCircleIcon"
                    color="green"
                />
                <StatCard
                    title="Total Users"
                    :value="totalUsers"
                    icon="UsersIcon"
                    color="purple"
                />
                <StatCard
                    title="Total Graduates"
                    :value="totalGraduates"
                    icon="AcademicCapIcon"
                    color="yellow"
                />
            </div>

            <!-- Institutions Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">All Institutions</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Manage and monitor all institutions in the system
                    </p>
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
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            {{ institution.name }}
                                        </p>
                                        <span 
                                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
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
                                        <UsersIcon class="h-4 w-4 mr-1" />
                                        {{ institution.users_count }} users
                                    </div>
                                    <div class="flex items-center">
                                        <AcademicCapIcon class="h-4 w-4 mr-1" />
                                        {{ institution.graduates_count }} graduates
                                    </div>
                                    <div class="flex items-center">
                                        <BookOpenIcon class="h-4 w-4 mr-1" />
                                        {{ institution.courses_count }} courses
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <Link
                                        :href="route('institutions.show', institution.id)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="route('institutions.edit', institution.id)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="confirmDelete(institution)"
                                        class="text-red-600 hover:text-red-900 text-sm font-medium"
                                    >
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
                
                <div v-if="institutions.length === 0" class="text-center py-12">
                    <BuildingOfficeIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No institutions</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new institution.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('institutions.create')"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                            Add Institution
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-600" />
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Delete Institution</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to delete "{{ institutionToDelete?.name }}"? 
                            This action cannot be undone and will affect all associated data.
                        </p>
                    </div>
                    <div class="flex justify-center space-x-3 mt-4">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            @click="deleteInstitution"
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    BuildingOfficeIcon,
    UsersIcon,
    AcademicCapIcon,
    BookOpenIcon,
    PlusIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import StatCard from '@/components/StatCard.vue';
import { format } from 'date-fns';

const props = defineProps({
    institutions: Array,
});

const showDeleteModal = ref(false);
const institutionToDelete = ref(null);

const activeInstitutions = computed(() => {
    return props.institutions.filter(inst => inst.status === 'active').length;
});

const totalUsers = computed(() => {
    return props.institutions.reduce((sum, inst) => sum + inst.users_count, 0);
});

const totalGraduates = computed(() => {
    return props.institutions.reduce((sum, inst) => sum + inst.graduates_count, 0);
});

const getStatusClass = (status) => {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-red-100 text-red-800',
        'suspended': 'bg-yellow-100 text-yellow-800',
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