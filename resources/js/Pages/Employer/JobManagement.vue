<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    jobs: Object,
    courses: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const courseId = ref(props.filters.course_id || '');
const selectedJobs = ref([]);

const applyFilters = () => {
    router.get(route('employer.jobs'), {
        search: search.value,
        status: status.value,
        course_id: courseId.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    courseId.value = '';
    applyFilters();
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'pending_approval': 'bg-yellow-100 text-yellow-800',
        'paused': 'bg-gray-100 text-gray-800',
        'filled': 'bg-blue-100 text-blue-800',
        'expired': 'bg-red-100 text-red-800',
        'cancelled': 'bg-red-100 text-red-800',
        'draft': 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const getDaysUntilDeadline = (deadline) => {
    if (!deadline) return null;
    const days = Math.ceil((new Date(deadline) - new Date()) / (1000 * 60 * 60 * 24));
    return days > 0 ? days : 0;
};

const toggleJobSelection = (jobId) => {
    const index = selectedJobs.value.indexOf(jobId);
    if (index > -1) {
        selectedJobs.value.splice(index, 1);
    } else {
        selectedJobs.value.push(jobId);
    }
};

const selectAllJobs = () => {
    if (selectedJobs.value.length === props.jobs.data.length) {
        selectedJobs.value = [];
    } else {
        selectedJobs.value = props.jobs.data.map(job => job.id);
    }
};

const bulkAction = (action) => {
    if (selectedJobs.value.length === 0) {
        alert('Please select jobs first');
        return;
    }

    if (confirm(`Are you sure you want to ${action} ${selectedJobs.value.length} job(s)?`)) {
        router.post(route('jobs.bulk-action'), {
            job_ids: selectedJobs.value,
            action: action,
        }, {
            onSuccess: () => {
                selectedJobs.value = [];
            }
        });
    }
};

const pauseJob = (job) => {
    if (confirm('Are you sure you want to pause this job?')) {
        router.post(route('jobs.pause', job.id));
    }
};

const resumeJob = (job) => {
    if (confirm('Are you sure you want to resume this job?')) {
        router.post(route('jobs.resume', job.id));
    }
};

const markAsFilled = (job) => {
    if (confirm('Are you sure you want to mark this job as filled?')) {
        router.post(route('jobs.mark-filled', job.id));
    }
};

const duplicateJob = (job) => {
    if (confirm('Are you sure you want to duplicate this job?')) {
        router.post(route('jobs.duplicate', job.id));
    }
};
</script>

<template>
    <Head title="Job Management" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Job Management
                </h2>
                <Link :href="route('jobs.create')" 
                      class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                    Post New Job
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Jobs</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input id="search" type="text" v-model="search" 
                                   placeholder="Job title or description..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" v-model="status"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="pending_approval">Pending Approval</option>
                                <option value="paused">Paused</option>
                                <option value="filled">Filled</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="draft">Draft</option>
                            </select>
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
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="applyFilters" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Apply Filters
                        </button>
                        <button @click="clearFilters" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="selectedJobs.length > 0" class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-blue-800">
                            {{ selectedJobs.length }} job(s) selected
                        </span>
                        <div class="flex gap-2">
                            <button @click="bulkAction('pause')" 
                                    class="text-sm bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">
                                Pause Selected
                            </button>
                            <button @click="bulkAction('resume')" 
                                    class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                                Resume Selected
                            </button>
                            <button @click="bulkAction('delete')" 
                                    class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">
                                Delete Selected
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Jobs List -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" 
                                               :checked="selectedJobs.length === jobs.data.length && jobs.data.length > 0"
                                               @change="selectAllJobs"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Job Details
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Applications
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Deadline
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="job in jobs.data" :key="job.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" 
                                               :checked="selectedJobs.includes(job.id)"
                                               @change="toggleJobSelection(job.id)"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <Link :href="route('jobs.show', job.id)" class="hover:text-indigo-600">
                                                        {{ job.title }}
                                                    </Link>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ job.course?.name || 'No course specified' }}
                                                </div>
                                                <div class="text-xs text-gray-400 mt-1">
                                                    Posted {{ formatDate(job.created_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(job.status)]">
                                            {{ job.status?.replace('_', ' ').toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <span class="font-medium">{{ job.applications_count || 0 }}</span>
                                            <Link v-if="job.applications_count > 0" 
                                                  :href="route('jobs.applications.index', job.id)" 
                                                  class="ml-2 text-indigo-600 hover:text-indigo-500 text-xs">
                                                View
                                            </Link>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div v-if="job.application_deadline">
                                            <div class="text-sm">{{ formatDate(job.application_deadline) }}</div>
                                            <div v-if="getDaysUntilDeadline(job.application_deadline) !== null" 
                                                 :class="['text-xs', getDaysUntilDeadline(job.application_deadline) <= 3 ? 'text-red-600' : 'text-gray-500']">
                                                {{ getDaysUntilDeadline(job.application_deadline) }} days left
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400">No deadline</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <Link :href="route('jobs.show', job.id)" 
                                                  class="text-indigo-600 hover:text-indigo-900">
                                                View
                                            </Link>
                                            <Link :href="route('jobs.edit', job.id)" 
                                                  class="text-green-600 hover:text-green-900">
                                                Edit
                                            </Link>
                                            <Link :href="route('jobs.analytics', job.id)" 
                                                  class="text-blue-600 hover:text-blue-900">
                                                Analytics
                                            </Link>
                                            
                                            <!-- Status Actions -->
                                            <button v-if="job.status === 'active'" 
                                                    @click="pauseJob(job)"
                                                    class="text-yellow-600 hover:text-yellow-900">
                                                Pause
                                            </button>
                                            <button v-if="job.status === 'paused'" 
                                                    @click="resumeJob(job)"
                                                    class="text-green-600 hover:text-green-900">
                                                Resume
                                            </button>
                                            <button v-if="['active', 'paused'].includes(job.status)" 
                                                    @click="markAsFilled(job)"
                                                    class="text-blue-600 hover:text-blue-900">
                                                Mark Filled
                                            </button>
                                            <button @click="duplicateJob(job)"
                                                    class="text-purple-600 hover:text-purple-900">
                                                Duplicate
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="jobs.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
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
                </div>

                <!-- Empty State -->
                <div v-if="jobs.data.length === 0" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No jobs found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search filters.' : 'You haven\'t posted any jobs yet.' }}
                        </p>
                        <Link v-if="!Object.values(filters).some(f => f)" 
                              :href="route('jobs.create')" 
                              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Post Your First Job
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>