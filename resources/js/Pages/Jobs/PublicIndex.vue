<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    jobs: Object,
    courses: Array,
    locations: Array,
    recommendations: Array,
    filters: Object,
    filter_options: Object,
});

// Filter refs
const search = ref(props.filters.search || '');
const location = ref(props.filters.location || '');
const course_id = ref(props.filters.course_id || '');
const experience_level = ref(props.filters.experience_level || '');
const job_type = ref(props.filters.job_type || '');
const work_arrangement = ref(props.filters.work_arrangement || '');
const salary_min = ref(props.filters.salary_min || '');
const salary_max = ref(props.filters.salary_max || '');
const sort_by = ref(props.filters.sort_by || 'created_at');
const sort_order = ref(props.filters.sort_order || 'desc');

const showFilters = ref(false);

// Watch for filter changes and update URL
watch([search, location, course_id, experience_level, job_type, work_arrangement, salary_min, salary_max, sort_by, sort_order], 
    ([searchVal, locationVal, courseVal, expVal, jobTypeVal, workVal, salMinVal, salMaxVal, sortByVal, sortOrderVal]) => {
        router.get(route('jobs.public.index'), {
            search: searchVal,
            location: locationVal,
            course_id: courseVal,
            experience_level: expVal,
            job_type: jobTypeVal,
            work_arrangement: workVal,
            salary_min: salMinVal,
            salary_max: salMaxVal,
            sort_by: sortByVal,
            sort_order: sortOrderVal,
        }, { 
            preserveState: true, 
            replace: true 
        });
    }
);

const clearFilters = () => {
    search.value = '';
    location.value = '';
    course_id.value = '';
    experience_level.value = '';
    job_type.value = '';
    work_arrangement.value = '';
    salary_min.value = '';
    salary_max.value = '';
    sort_by.value = 'created_at';
    sort_order.value = 'desc';
};

const formatSalary = (job) => {
    if (!job.salary_min && !job.salary_max) return 'Negotiable';
    
    const min = job.salary_min ? Number(job.salary_min).toLocaleString() : '';
    const max = job.salary_max ? Number(job.salary_max).toLocaleString() : '';
    
    if (min && max) {
        return `${min} - ${max}`;
    } else if (min) {
        return `From ${min}`;
    } else if (max) {
        return `Up to ${max}`;
    }
    
    return 'Negotiable';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const getDaysUntilDeadline = (deadline) => {
    if (!deadline) return null;
    const days = Math.ceil((new Date(deadline) - new Date()) / (1000 * 60 * 60 * 24));
    return days > 0 ? days : 0;
};

const getJobTypeColor = (type) => {
    const colors = {
        'full_time': 'bg-green-100 text-green-800',
        'part_time': 'bg-blue-100 text-blue-800',
        'contract': 'bg-purple-100 text-purple-800',
        'internship': 'bg-yellow-100 text-yellow-800',
        'temporary': 'bg-gray-100 text-gray-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const getWorkArrangementIcon = (arrangement) => {
    const icons = {
        'remote': '🏠',
        'hybrid': '🔄',
        'on_site': '🏢',
    };
    return icons[arrangement] || '🏢';
};
</script>

<template>
    <Head title="Browse Jobs" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Browse Jobs
                </h2>
                <div class="text-sm text-gray-600">
                    {{ jobs.total }} jobs available
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Sidebar Filters -->
                    <div class="lg:w-1/4">
                        <div class="bg-white shadow rounded-lg p-6 sticky top-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                                <button
                                    @click="clearFilters"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    Clear All
                                </button>
                            </div>

                            <div class="space-y-6">
                                <!-- Search -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                                    <input
                                        v-model="search"
                                        type="text"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Job title, company, skills..."
                                    />
                                </div>

                                <!-- Location -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                    <select
                                        v-model="location"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Any Location</option>
                                        <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}</option>
                                    </select>
                                </div>

                                <!-- Course -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                                    <select
                                        v-model="course_id"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Any Course</option>
                                        <option v-for="course in courses" :key="course.id" :value="course.id">
                                            {{ course.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Experience Level -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level</label>
                                    <select
                                        v-model="experience_level"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Any Level</option>
                                        <option v-for="(label, value) in filter_options.experience_levels" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Job Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Job Type</label>
                                    <select
                                        v-model="job_type"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Any Type</option>
                                        <option v-for="(label, value) in filter_options.job_types" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Work Arrangement -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Work Arrangement</label>
                                    <select
                                        v-model="work_arrangement"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Any Arrangement</option>
                                        <option v-for="(label, value) in filter_options.work_arrangements" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Salary Range -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Salary Range</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="salary_min"
                                            type="number"
                                            class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Min"
                                        />
                                        <input
                                            v-model="salary_max"
                                            type="number"
                                            class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Max"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="lg:w-3/4">
                        <!-- Recommendations -->
                        <div v-if="recommendations.length > 0" class="bg-indigo-50 border border-indigo-200 rounded-lg p-6 mb-6">
                            <h3 class="text-lg font-medium text-indigo-900 mb-4">Recommended for You</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="rec in recommendations" :key="rec.job.id" class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900">{{ rec.job.title }}</h4>
                                        <span class="text-sm font-medium text-indigo-600">{{ rec.match_score }}% match</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">{{ rec.job.employer.company_name }}</p>
                                    <Link
                                        :href="route('jobs.public.show', rec.job.id)"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                    >
                                        View Details →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Sort Options -->
                        <div class="bg-white shadow rounded-lg p-4 mb-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-700">Sort by:</span>
                                    <select
                                        v-model="sort_by"
                                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                        <option value="created_at">Date Posted</option>
                                        <option value="salary">Salary</option>
                                        <option value="deadline">Application Deadline</option>
                                        <option value="relevance">Relevance</option>
                                    </select>
                                    <select
                                        v-model="sort_order"
                                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                        <option value="desc">Descending</option>
                                        <option value="asc">Ascending</option>
                                    </select>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Showing {{ jobs.from }}-{{ jobs.to }} of {{ jobs.total }} jobs
                                </div>
                            </div>
                        </div>

                        <!-- Jobs List -->
                        <div class="space-y-6">
                            <div v-for="job in jobs.data" :key="job.id" class="bg-white shadow rounded-lg overflow-hidden">
                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <h3 class="text-xl font-semibold text-gray-900">{{ job.title }}</h3>
                                                <span :class="getJobTypeColor(job.job_type)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ filter_options.job_types[job.job_type] }}
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center space-x-4 text-sm text-gray-600 mb-3">
                                                <span class="font-medium">{{ job.employer.company_name }}</span>
                                                <span>{{ getWorkArrangementIcon(job.work_arrangement) }} {{ job.location }}</span>
                                                <span>{{ formatSalary(job) }}</span>
                                                <span v-if="job.course">{{ job.course.name }}</span>
                                            </div>

                                            <p class="text-gray-700 mb-4 line-clamp-3">{{ job.description }}</p>

                                            <!-- Skills -->
                                            <div v-if="job.required_skills && job.required_skills.length > 0" class="mb-4">
                                                <div class="flex flex-wrap gap-2">
                                                    <span
                                                        v-for="skill in job.required_skills.slice(0, 5)"
                                                        :key="skill"
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                                    >
                                                        {{ skill }}
                                                    </span>
                                                    <span v-if="job.required_skills.length > 5" class="text-xs text-gray-500">
                                                        +{{ job.required_skills.length - 5 }} more
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                    <span>Posted {{ formatDate(job.created_at) }}</span>
                                                    <span v-if="job.application_deadline">
                                                        {{ getDaysUntilDeadline(job.application_deadline) }} days left
                                                    </span>
                                                    <span>{{ job.total_applications || 0 }} applicants</span>
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <Link
                                                        :href="route('jobs.public.show', job.id)"
                                                        class="text-indigo-600 hover:text-indigo-800 font-medium"
                                                    >
                                                        View Details
                                                    </Link>
                                                    <Link
                                                        :href="route('jobs.apply', job.id)"
                                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                                                    >
                                                        Apply Now
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="jobs.links" class="mt-8">
                            <nav class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-lg shadow">
                                <div class="flex flex-1 justify-between sm:hidden">
                                    <Link
                                        v-if="jobs.prev_page_url"
                                        :href="jobs.prev_page_url"
                                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        v-if="jobs.next_page_url"
                                        :href="jobs.next_page_url"
                                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing <span class="font-medium">{{ jobs.from }}</span> to <span class="font-medium">{{ jobs.to }}</span> of <span class="font-medium">{{ jobs.total }}</span> results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                            <Link
                                                v-for="link in jobs.links"
                                                :key="link.label"
                                                :href="link.url"
                                                v-html="link.label"
                                                :class="[
                                                    'relative inline-flex items-center px-2 py-2 text-sm font-medium',
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

                        <!-- Empty State -->
                        <div v-if="jobs.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No jobs found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria or filters.</p>
                            <div class="mt-6">
                                <button
                                    @click="clearFilters"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                                >
                                    Clear Filters
                                </button>
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
