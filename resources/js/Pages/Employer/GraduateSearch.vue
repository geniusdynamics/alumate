<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    graduates: Object,
    courses: Array,
    graduationYears: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const courseId = ref(props.filters.course_id || '');
const graduationYear = ref(props.filters.graduation_year || '');
const employmentStatus = ref(props.filters.employment_status || '');
const skills = ref(props.filters.skills || '');
const location = ref(props.filters.location || '');
const showAdvancedFilters = ref(false);

const applyFilters = () => {
    router.get(route('employer.graduates.search'), {
        search: search.value,
        course_id: courseId.value,
        graduation_year: graduationYear.value,
        employment_status: employmentStatus.value,
        skills: skills.value,
        location: location.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    courseId.value = '';
    graduationYear.value = '';
    employmentStatus.value = '';
    skills.value = '';
    location.value = '';
    applyFilters();
};

const getEmploymentStatusBadge = (status) => {
    const badges = {
        'employed': 'bg-green-100 text-green-800',
        'unemployed': 'bg-red-100 text-red-800',
        'self_employed': 'bg-blue-100 text-blue-800',
        'student': 'bg-purple-100 text-purple-800',
        'other': 'bg-gray-100 text-gray-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const contactGraduate = (graduate) => {
    if (confirm(`Are you sure you want to contact ${graduate.user.name}?`)) {
        // This would typically open a messaging interface or send an email
        alert('Contact functionality would be implemented here');
    }
};

const inviteToApply = (graduate, jobId = null) => {
    const message = jobId 
        ? `Invite ${graduate.user.name} to apply for a specific job?`
        : `Send a general invitation to ${graduate.user.name}?`;
    
    if (confirm(message)) {
        // This would send an invitation
        alert('Invitation functionality would be implemented here');
    }
};

const saveToShortlist = (graduate) => {
    // This would save the graduate to a shortlist for future reference
    alert('Shortlist functionality would be implemented here');
};
</script>

<template>
    <Head title="Graduate Search" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Graduate Search
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Search and Filters -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Search Graduates</h3>
                        <button @click="showAdvancedFilters = !showAdvancedFilters" 
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            {{ showAdvancedFilters ? 'Hide Advanced' : 'Show Advanced' }}
                        </button>
                    </div>
                    
                    <!-- Basic Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input id="search" type="text" v-model="search" 
                                   placeholder="Name or email..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="course" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                            <select id="course" v-model="courseId"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Courses</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.name }}
                                </option>
                            </select>
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
                    </div>
                    
                    <!-- Advanced Filters -->
                    <div v-if="showAdvancedFilters" class="border-t pt-4 mt-4">
                        <h4 class="text-md font-medium text-gray-800 mb-3">Advanced Filters</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
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
                            
                            <div>
                                <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Skills</label>
                                <input id="skills" type="text" v-model="skills" 
                                       placeholder="e.g. JavaScript, Python, Design..."
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <p class="text-xs text-gray-500 mt-1">Separate multiple skills with commas</p>
                            </div>
                            
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input id="location" type="text" v-model="location" 
                                       placeholder="City or region..."
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="applyFilters" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Search Graduates
                        </button>
                        <button @click="clearFilters" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Search Results -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="graduate in graduates.data" :key="graduate.id" 
                         class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ graduate.user?.name }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ graduate.course?.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Graduated {{ graduate.graduation_year }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span v-if="graduate.employment_status?.status" 
                                          :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getEmploymentStatusBadge(graduate.employment_status.status)]">
                                        {{ graduate.employment_status.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Current Employment -->
                            <div v-if="graduate.employment_status?.company" class="mt-3">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Current:</span> 
                                    {{ graduate.employment_status.job_title }} at {{ graduate.employment_status.company }}
                                </p>
                            </div>
                            
                            <!-- Skills -->
                            <div v-if="graduate.skills && graduate.skills.length > 0" class="mt-3">
                                <p class="text-xs text-gray-500 mb-1">Skills:</p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="skill in graduate.skills.slice(0, 4)" :key="skill" 
                                          class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                        {{ skill }}
                                    </span>
                                    <span v-if="graduate.skills.length > 4" 
                                          class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                        +{{ graduate.skills.length - 4 }} more
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Academic Performance -->
                            <div v-if="graduate.academic_records?.gpa" class="mt-3">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">GPA:</span> {{ graduate.academic_records.gpa }}
                                </p>
                            </div>
                            
                            <!-- Contact Information -->
                            <div v-if="graduate.allow_employer_contact" class="mt-3">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Email:</span> {{ graduate.user?.email }}
                                </p>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mt-4 flex gap-2">
                                <button @click="contactGraduate(graduate)"
                                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-3 rounded-md">
                                    Contact
                                </button>
                                <button @click="inviteToApply(graduate)"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-3 rounded-md">
                                    Invite
                                </button>
                                <button @click="saveToShortlist(graduate)"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium py-2 px-3 rounded-md">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="graduates.links" class="bg-white px-4 py-3 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link v-if="graduates.prev_page_url" :href="graduates.prev_page_url" 
                                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </Link>
                            <Link v-if="graduates.next_page_url" :href="graduates.next_page_url" 
                                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing {{ graduates.from }} to {{ graduates.to }} of {{ graduates.total }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link v-for="link in graduates.links" :key="link.label" 
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
                <div v-if="graduates.data.length === 0" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No graduates found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search criteria.' : 'Start searching for graduates using the filters above.' }}
                        </p>
                    </div>
                </div>

                <!-- Search Tips -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Search Tips</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Use specific skills to find candidates with relevant expertise</li>
                                    <li>Filter by graduation year to find candidates with the right experience level</li>
                                    <li>Only graduates who have made their profiles public or allowed employer contact will appear</li>
                                    <li>Contact graduates directly or invite them to apply for specific positions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>