<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    jobs: Object,
    courses: Array,
    graduate: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const location = ref(props.filters.location || '');
const jobType = ref(props.filters.job_type || '');
const experienceLevel = ref(props.filters.experience_level || '');
const salaryMin = ref(props.filters.salary_min || '');
const courseId = ref(props.filters.course_id || '');
const showAdvancedFilters = ref(false);

const applyFilters = () => {
    router.get(route('graduate.jobs'), {
        search: search.value,
        location: location.value,
        job_type: jobType.value,
        experience_level: experienceLevel.value,
        salary_min: salaryMin.value,
        course_id: courseId.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    location.value = '';
    jobType.value = '';
    experienceLevel.value = '';
    salaryMin.value = '';
    courseId.value = '';
    applyFilters();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatSalary = (job) => {
    if (!job.salary_min && !job.salary_max) return 'Negotiable';
    
    const min = job.salary_min ? Number(job.salary_min).toLocaleString() : '';
    const max = job.salary_max ? Number(job.salary_max).toLocaleString() : '';
    
    if (min && max) {
        return `$${min} - $${max}`;
    } else if (min) {
        return `From $${min}`;
    } else if (max) {
        return `Up to $${max}`;
    }
    
    return 'Negotiable';
};

const getDaysAgo = (date) => {
    const days = Math.floor((new Date() - new Date(date)) / (1000 * 60 * 60 * 24));
    if (days === 0) return 'Today';
    if (days === 1) return '1 day ago';
    return `${days} days ago`;
};

const getJobTypeColor = (type) => {
    const colors = {
        'full_time': 'bg-green-100 text-green-800',
        'part_time': 'bg-blue-100 text-blue-800',
        'contract': 'bg-purple-100 text-purple-800',
        'internship': 'bg-yellow-100 text-yellow-800',
        'freelance': 'bg-pink-100 text-pink-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const getExperienceLevelColor = (level) => {
    const colors = {
        'entry': 'bg-green-100 text-green-800',
        'junior': 'bg-blue-100 text-blue-800',
        'mid': 'bg-purple-100 text-purple-800',
        'senior': 'bg-red-100 text-red-800',
        'lead': 'bg-indigo-100 text-indigo-800',
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
};

const applyToJob = (job) => {
    router.post(route('jobs.apply', job.id));
};

const saveJob = (job) => {
    // Placeholder for save job functionality
    alert('Job saved! (Feature to be implemented)');
};

const hasApplied = (job) => {
    return job.applications && job.applications.some(app => 
        app.graduate_id === props.graduate?.id
    );
};

const isSkillMatch = (job) => {
    if (!props.graduate?.skills || !job.required_skills) return false;
    return job.required_skills.some(skill => 
        props.graduate.skills.includes(skill)
    );
};
</script>

<template>
    <Head title="Browse Jobs" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Browse Jobs
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Search and Filters -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Find Your Next Opportunity</h3>
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
                                   placeholder="Job title, company, or keywords..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <input id="location" type="text" v-model="location" 
                                   placeholder="City, state, or remote..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="job_type" class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                            <select id="job_type" v-model="jobType"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types</option>
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="internship">Internship</option>
                                <option value="freelance">Freelance</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Advanced Filters -->
                    <div v-if="showAdvancedFilters" class="border-t pt-4 mt-4">
                        <h4 class="text-md font-medium text-gray-800 mb-3">Advanced Filters</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="experience_level" class="block text-sm font-medium text-gray-700 mb-1">Experience Level</label>
                                <select id="experience_level" v-model="experienceLevel"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Levels</option>
                                    <option value="entry">Entry Level</option>
                                    <option value="junior">Junior</option>
                                    <option value="mid">Mid Level</option>
                                    <option value="senior">Senior</option>
                                    <option value="lead">Lead/Manager</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="salary_min" class="block text-sm font-medium text-gray-700 mb-1">Minimum Salary</label>
                                <input id="salary_min" type="number" v-model="salaryMin" 
                                       placeholder="e.g. 50000"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="course" class="block text-sm font-medium text-gray-700 mb-1">Related Course</label>
                                <select id="course" v-model="courseId"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="applyFilters" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Search Jobs
                        </button>
                        <button @click="clearFilters" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Results Summary -->
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Showing {{ jobs.from }} to {{ jobs.to }} of {{ jobs.total }} jobs
                        </p>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">Sort by:</span>
                            <select class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option>Most Recent</option>
                                <option>Best Match</option>
                                <option>Salary: High to Low</option>
                                <option>Salary: Low to High</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Job Listings -->
                <div class="space-y-4">
                    <div v-for="job in jobs.data" :key="job.id" 
                         class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            <Link :href="route('jobs.public.show', job.id)" 
                                                  class="hover:text-indigo-600">
                                                {{ job.title }}
                                            </Link>
                                        </h3>
                                        <p class="text-lg text-gray-600">{{ job.employer?.company_name }}</p>
                                        <p class="text-sm text-gray-500">{{ job.location || 'Location not specified' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="isSkillMatch(job)" 
                                              class="inline-flex px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                            Skill Match
                                        </span>
                                        <span v-if="hasApplied(job)" 
                                              class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                            Applied
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3 mb-3">
                                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded', getJobTypeColor(job.job_type)]">
                                        {{ job.job_type?.replace('_', ' ').toUpperCase() }}
                                    </span>
                                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded', getExperienceLevelColor(job.experience_level)]">
                                        {{ job.experience_level?.toUpperCase() }}
                                    </span>
                                    <span class="text-sm text-gray-600">{{ formatSalary(job) }}</span>
                                </div>
                                
                                <p class="text-gray-700 mb-4 line-clamp-3">
                                    {{ job.description }}
                                </p>
                                
                                <!-- Skills -->
                                <div v-if="job.required_skills && job.required_skills.length > 0" class="mb-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Required Skills:</p>
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="skill in job.required_skills.slice(0, 6)" :key="skill" 
                                              :class="[
                                                  'inline-flex px-2 py-1 text-xs rounded',
                                                  graduate?.skills?.includes(skill) 
                                                      ? 'bg-green-100 text-green-800' 
                                                      : 'bg-gray-100 text-gray-700'
                                              ]">
                                            {{ skill }}
                                        </span>
                                        <span v-if="job.required_skills.length > 6" 
                                              class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                            +{{ job.required_skills.length - 6 }} more
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span>Posted {{ getDaysAgo(job.created_at) }}</span>
                                        <span v-if="job.application_deadline">
                                            Deadline: {{ formatDate(job.application_deadline) }}
                                        </span>
                                        <span>{{ job.applications_count || 0 }} applicants</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <button @click="saveJob(job)"
                                                class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                            </svg>
                                        </button>
                                        
                                        <Link :href="route('jobs.public.show', job.id)" 
                                              class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md">
                                            View Details
                                        </Link>
                                        
                                        <button v-if="!hasApplied(job)" 
                                                @click="applyToJob(job)"
                                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                                            Apply Now
                                        </button>
                                        
                                        <span v-else class="bg-green-100 text-green-800 font-medium py-2 px-4 rounded-md">
                                            Applied
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="jobs.links" class="bg-white px-4 py-3 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link v-if="jobs.prev_page_url" :href="jobs.prev_page_url" 
                                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </Link>
                            <Link v-if="jobs.next_page_url" :href="jobs.next_page_url" 
                                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing {{ jobs.from }} to {{ jobs.to }} of {{ jobs.total }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link v-for="link in jobs.links" :key="link.label" 
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
                <div v-if="jobs.data.length === 0" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No jobs found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search criteria.' : 'No jobs are currently available.' }}
                        </p>
                        <button v-if="Object.values(filters).some(f => f)" 
                                @click="clearFilters"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Job Search Tips -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Job Search Tips</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Complete your profile to get better job recommendations</li>
                                    <li>Use specific keywords related to your skills and interests</li>
                                    <li>Set up job alerts to be notified of new opportunities</li>
                                    <li>Apply early - many employers review applications as they come in</li>
                                    <li>Tailor your application to each job posting</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>