<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    classmates: Object,
    graduationYears: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const graduationYear = ref(props.filters.graduation_year || '');
const employmentStatus = ref(props.filters.employment_status || '');

const applyFilters = () => {
    router.get(route('graduate.classmates'), {
        search: search.value,
        graduation_year: graduationYear.value,
        employment_status: employmentStatus.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    graduationYear.value = '';
    employmentStatus.value = '';
    applyFilters();
};

const getEmploymentStatusClass = (status) => {
    const classes = {
        'employed': 'bg-green-100 text-green-800',
        'unemployed': 'bg-red-100 text-red-800',
        'self_employed': 'bg-blue-100 text-blue-800',
        'student': 'bg-purple-100 text-purple-800',
        'other': 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'Not provided';
};

const connectWithClassmate = (classmate) => {
    // Placeholder for connection functionality
    alert(`Connection request sent to ${classmate.user.name}! (Feature to be implemented)`);
};

const sendMessage = (classmate) => {
    // Placeholder for messaging functionality
    alert(`Message feature with ${classmate.user.name} to be implemented`);
};
</script>

<template>
    <Head title="My Classmates" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Classmates
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Search and Filters -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Find Your Classmates</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input id="search" type="text" v-model="search" 
                                   placeholder="Name or email..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="graduation_year" class="block text-sm font-medium text-gray-700 mb-1">Graduation Year</label>
                            <select id="graduation_year" v-model="graduationYear"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Years</option>
                                <option v-for="year in graduationYears" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="employment_status" class="block text-sm font-medium text-gray-700 mb-1">Employment Status</label>
                            <select id="employment_status" v-model="employmentStatus"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="employed">Employed</option>
                                <option value="unemployed">Unemployed</option>
                                <option value="self_employed">Self-Employed</option>
                                <option value="student">Student</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="applyFilters" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Search Classmates
                        </button>
                        <button @click="clearFilters" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Classmates Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="classmate in classmates.data" :key="classmate.id" 
                         class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-lg font-medium text-gray-700">
                                            {{ classmate.user?.name?.charAt(0) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ classmate.user?.name }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Class of {{ classmate.graduation_year }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Employment Status -->
                            <div class="mt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Employment Status</span>
                                    <span v-if="classmate.employment_status?.status" 
                                          :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getEmploymentStatusClass(classmate.employment_status.status)]">
                                        {{ classmate.employment_status.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                    <span v-else class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">
                                        NOT SPECIFIED
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Current Employment -->
                            <div v-if="classmate.employment_status?.company" class="mt-3">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Current:</span> 
                                    {{ classmate.employment_status.job_title }} at {{ classmate.employment_status.company }}
                                </p>
                            </div>
                            
                            <!-- Skills -->
                            <div v-if="classmate.skills && classmate.skills.length > 0" class="mt-3">
                                <p class="text-xs text-gray-500 mb-1">Skills:</p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="skill in classmate.skills.slice(0, 3)" :key="skill" 
                                          class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                        {{ skill }}
                                    </span>
                                    <span v-if="classmate.skills.length > 3" 
                                          class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                        +{{ classmate.skills.length - 3 }} more
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Academic Performance -->
                            <div v-if="classmate.academic_records?.gpa" class="mt-3">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">GPA:</span> {{ classmate.academic_records.gpa }}
                                </p>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mt-4 flex gap-2">
                                <button @click="connectWithClassmate(classmate)"
                                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-3 rounded-md">
                                    Connect
                                </button>
                                <button @click="sendMessage(classmate)"
                                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium py-2 px-3 rounded-md">
                                    Message
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="classmates.links" class="bg-white px-4 py-3 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link v-if="classmates.prev_page_url" :href="classmates.prev_page_url" 
                                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </Link>
                            <Link v-if="classmates.next_page_url" :href="classmates.next_page_url" 
                                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing {{ classmates.from }} to {{ classmates.to }} of {{ classmates.total }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link v-for="link in classmates.links" :key="link.label" 
                                          :href="link.url" 
                                          :class="[
                                              'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                              link.active 
                                                  ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                                                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                          ]"
                                          v-html="link.label">
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="classmates.data.length === 0" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No classmates found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search criteria.' : 'No classmates have made their profiles public yet.' }}
                        </p>
                    </div>
                </div>

                <!-- Networking Tips -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Networking Tips</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Connect with classmates in your field of interest</li>
                                    <li>Share job opportunities and career advice</li>
                                    <li>Collaborate on projects and skill development</li>
                                    <li>Maintain professional relationships for future opportunities</li>
                                    <li>Attend alumni events and networking sessions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>