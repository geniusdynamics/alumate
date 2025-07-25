<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    job: Object,
    applications: Object,
    stats: Object,
    filters: Object,
    courses: Array,
});

// Filter refs
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const priority = ref(props.filters.priority || '');
const flagged = ref(props.filters.flagged || false);
const course_id = ref(props.filters.course_id || '');
const gpa_min = ref(props.filters.gpa_min || '');
const sort_by = ref(props.filters.sort_by || 'created_at');
const sort_order = ref(props.filters.sort_order || 'desc');
const selectedApplications = ref([]);
const showFilters = ref(false);

// Watch for filter changes
watch([search, status, priority, flagged, course_id, gpa_min, sort_by, sort_order], 
    ([searchVal, statusVal, priorityVal, flaggedVal, courseVal, gpaVal, sortByVal, sortOrderVal]) => {
        router.get(route('jobs.applications.index', props.job.id), {
            search: searchVal,
            status: statusVal,
            priority: priorityVal,
            flagged: flaggedVal,
            course_id: courseVal,
            gpa_min: gpaVal,
            sort_by: sortByVal,
            sort_order: sortOrderVal,
        }, { 
            preserveState: true, 
            replace: true 
        });
    }
);

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'reviewed': 'bg-blue-100 text-blue-800',
        'shortlisted': 'bg-purple-100 text-purple-800',
        'interview_scheduled': 'bg-indigo-100 text-indigo-800',
        'interviewed': 'bg-indigo-100 text-indigo-800',
        'offer_made': 'bg-orange-100 text-orange-800',
        'offer_accepted': 'bg-green-100 text-green-800',
        'hired': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'withdrawn': 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        'pending': 'Pending Review',
        'reviewed': 'Reviewed',
        'shortlisted': 'Shortlisted',
        'interview_scheduled': 'Interview Scheduled',
        'interviewed': 'Interviewed',
        'offer_made': 'Offer Made',
        'offer_accepted': 'Offer Accepted',
        'hired': 'Hired',
        'rejected': 'Rejected',
        'withdrawn': 'Withdrawn',
    };
    return texts[status] || status;
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'N/A';
};

const getMatchScoreColor = (score) => {
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const toggleApplicationSelection = (applicationId) => {
    const index = selectedApplications.value.indexOf(applicationId);
    if (index > -1) {
        selectedApplications.value.splice(index, 1);
    } else {
        selectedApplications.value.push(applicationId);
    }
};

const selectAllApplications = () => {
    if (selectedApplications.value.length === props.applications.data.length) {
        selectedApplications.value = [];
    } else {
        selectedApplications.value = props.applications.data.map(app => app.id);
    }
};

const bulkAction = (action) => {
    if (selectedApplications.value.length === 0) return;
    
    let reason = '';
    if (action === 'reject' || action === 'flag') {
        reason = prompt(`Please provide a reason for ${action}:`);
        if (!reason) return;
    }
    
    const confirmMessage = `Are you sure you want to ${action} ${selectedApplications.value.length} applications?`;
    
    if (confirm(confirmMessage)) {
        router.post(route('applications.bulk', props.job.id), {
            action: action,
            application_ids: selectedApplications.value,
            reason: reason,
        });
    }
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    priority.value = '';
    flagged.value = false;
    course_id.value = '';
    gpa_min.value = '';
    sort_by.value = 'created_at';
    sort_order.value = 'desc';
};

const quickStatusUpdate = (application, newStatus) => {
    router.patch(route('applications.status.update', application.id), {
        status: newStatus,
    });
};
</script>

<template>
    <Head :title="`Applications - ${job.title}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Applications for "{{ job.title }}"
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ job.employer.company_name }} • {{ stats.total }} applications
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('jobs.applications.analytics', job.id)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                    >
                        View Analytics
                    </Link>
                    <Link
                        :href="route('jobs.show', job.id)"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                    >
                        Back to Job
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-gray-500">Total</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ stats.total }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-yellow-600">Pending</dt>
                                <dd class="text-2xl font-bold text-yellow-900">{{ stats.pending }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-blue-600">Reviewed</dt>
                                <dd class="text-2xl font-bold text-blue-900">{{ stats.reviewed }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-purple-600">Shortlisted</dt>
                                <dd class="text-2xl font-bold text-purple-900">{{ stats.shortlisted }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-indigo-600">Interviewed</dt>
                                <dd class="text-2xl font-bold text-indigo-900">{{ stats.interviewed }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-green-600">Hired</dt>
                                <dd class="text-2xl font-bold text-green-900">{{ stats.hired }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-red-600">Rejected</dt>
                                <dd class="text-2xl font-bold text-red-900">{{ stats.rejected }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-4">
                            <div class="text-center">
                                <dt class="text-sm font-medium text-orange-600">Flagged</dt>
                                <dd class="text-2xl font-bold text-orange-900">{{ stats.flagged }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Filters & Search</h3>
                            <div class="flex items-center space-x-3">
                                <button
                                    @click="showFilters = !showFilters"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    {{ showFilters ? 'Hide' : 'Show' }} Advanced Filters
                                </button>
                                <button
                                    @click="clearFilters"
                                    class="text-sm text-gray-600 hover:text-gray-800"
                                >
                                    Clear All
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Search</label>
                                <input
                                    v-model="search"
                                    type="text"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Search by name or email..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    v-model="status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="reviewed">Reviewed</option>
                                    <option value="shortlisted">Shortlisted</option>
                                    <option value="interview_scheduled">Interview Scheduled</option>
                                    <option value="interviewed">Interviewed</option>
                                    <option value="offer_made">Offer Made</option>
                                    <option value="hired">Hired</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort By</label>
                                <select
                                    v-model="sort_by"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="created_at">Application Date</option>
                                    <option value="match_score">Match Score</option>
                                    <option value="gpa">GPA</option>
                                    <option value="status">Status</option>
                                </select>
                            </div>
                            <div v-if="selectedApplications.length > 0" class="flex items-end">
                                <div class="flex space-x-2">
                                    <button
                                        @click="bulkAction('review')"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                    >
                                        Review ({{ selectedApplications.length }})
                                    </button>
                                    <button
                                        @click="bulkAction('shortlist')"
                                        class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                    >
                                        Shortlist ({{ selectedApplications.length }})
                                    </button>
                                    <button
                                        @click="bulkAction('reject')"
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                    >
                                        Reject ({{ selectedApplications.length }})
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-show="showFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Course</label>
                                <select
                                    v-model="course_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Minimum GPA</label>
                                <input
                                    v-model="gpa_min"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="e.g., 3.0"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Priority</label>
                                <select
                                    v-model="priority"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Priorities</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <input
                                    v-model="flagged"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label class="ml-2 block text-sm text-gray-900">Show only flagged applications</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applications List -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <!-- Bulk Selection Header -->
                    <div v-if="applications.data.length > 0" class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="selectedApplications.length === applications.data.length && applications.data.length > 0"
                                @change="selectAllApplications"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-3 text-sm font-medium text-gray-700">
                                {{ selectedApplications.length > 0 ? `${selectedApplications.length} selected` : 'Select All' }}
                            </span>
                        </div>
                    </div>

                    <ul class="divide-y divide-gray-200">
                        <li v-for="application in applications.data" :key="application.id" class="px-6 py-4">
                            <div class="flex items-start space-x-4">
                                <input
                                    type="checkbox"
                                    :checked="selectedApplications.includes(application.id)"
                                    @change="toggleApplicationSelection(application.id)"
                                    class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    {{ application.graduate.first_name }} {{ application.graduate.last_name }}
                                                </h3>
                                                <span :class="getStatusColor(application.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ getStatusText(application.status) }}
                                                </span>
                                                <span v-if="application.is_flagged" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Flagged
                                                </span>
                                                <span v-if="application.match_score" :class="getMatchScoreColor(application.match_score)" class="text-sm font-medium">
                                                    {{ Math.round(application.match_score) }}% match
                                                </span>
                                            </div>
                                            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                <span>{{ application.graduate.email }}</span>
                                                <span>{{ application.graduate.course?.name }}</span>
                                                <span v-if="application.graduate.gpa">GPA: {{ application.graduate.gpa }}</span>
                                                <span>Applied {{ formatDate(application.created_at) }}</span>
                                            </div>
                                            <div v-if="application.cover_letter" class="mt-2 text-sm text-gray-700 line-clamp-2">
                                                {{ application.cover_letter }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Link
                                        :href="route('applications.show', application.id)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        View Details
                                    </Link>
                                    <div class="relative inline-block text-left">
                                        <button
                                            type="button"
                                            class="text-gray-400 hover:text-gray-600"
                                            @click="$event.target.nextElementSibling.classList.toggle('hidden')"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                        <div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                            <div class="py-1">
                                                <button
                                                    v-if="application.status === 'pending'"
                                                    @click="quickStatusUpdate(application, 'reviewed')"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Mark as Reviewed
                                                </button>
                                                <button
                                                    v-if="['pending', 'reviewed'].includes(application.status)"
                                                    @click="quickStatusUpdate(application, 'shortlisted')"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Shortlist
                                                </button>
                                                <button
                                                    v-if="application.resume_file_path"
                                                    @click="window.open(route('applications.resume.download', application.id))"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Download Resume
                                                </button>
                                                <button
                                                    @click="$inertia.post(route('applications.flag', application.id), { reason: prompt('Flag reason:') })"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Flag Application
                                                </button>
                                                <button
                                                    @click="$inertia.post(route('applications.reject', application.id), { reason: prompt('Rejection reason:') })"
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <div v-if="applications.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="applications.prev_page_url"
                                    :href="applications.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="applications.next_page_url"
                                    :href="applications.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ applications.from }} to {{ applications.to }} of {{ applications.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link
                                            v-for="link in applications.links"
                                            :key="link.label"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-2 py-2 border text-sm font-medium',
                                                link.active
                                                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                            ]"
                                        />
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="applications.data.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No applications found</h3>
                    <p class="mt-1 text-sm text-gray-500">No applications match your current filters.</p>
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
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>