<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    pending_jobs: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedJobs = ref([]);

watch(search, (value) => {
    router.get(route('admin.job-approval.index'), { search: value }, { 
        preserveState: true, 
        replace: true 
    });
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
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

const toggleJobSelection = (jobId) => {
    const index = selectedJobs.value.indexOf(jobId);
    if (index > -1) {
        selectedJobs.value.splice(index, 1);
    } else {
        selectedJobs.value.push(jobId);
    }
};

const selectAllJobs = () => {
    if (selectedJobs.value.length === props.pending_jobs.data.length) {
        selectedJobs.value = [];
    } else {
        selectedJobs.value = props.pending_jobs.data.map(job => job.id);
    }
};

const bulkApprove = () => {
    if (selectedJobs.value.length === 0) return;
    
    if (confirm(`Are you sure you want to approve ${selectedJobs.value.length} jobs?`)) {
        router.post(route('admin.job-approval.bulk-approve'), {
            job_ids: selectedJobs.value
        });
    }
};

const bulkReject = () => {
    if (selectedJobs.value.length === 0) return;
    
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        router.post(route('admin.job-approval.bulk-reject'), {
            job_ids: selectedJobs.value,
            reason: reason
        });
    }
};
</script>

<template>
    <Head title="Job Approval Queue" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Job Approval Queue
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Approval</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.pending_count }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Approved Today</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.approved_today }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Rejected Today</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.rejected_today }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Bulk Actions -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                        <div class="flex-1 max-w-lg">
                            <input
                                v-model="search"
                                type="text"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Search jobs or companies..."
                            />
                        </div>
                        <div v-if="selectedJobs.length > 0" class="flex items-center space-x-3">
                            <span class="text-sm text-gray-600">{{ selectedJobs.length }} selected</span>
                            <button
                                @click="bulkApprove"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                            >
                                Bulk Approve
                            </button>
                            <button
                                @click="bulkReject"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                            >
                                Bulk Reject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Jobs List -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div v-if="pending_jobs.data.length > 0" class="divide-y divide-gray-200">
                        <!-- Header -->
                        <div class="px-6 py-3 bg-gray-50 flex items-center">
                            <input
                                type="checkbox"
                                :checked="selectedJobs.length === pending_jobs.data.length && pending_jobs.data.length > 0"
                                @change="selectAllJobs"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-3 text-sm font-medium text-gray-700">Select All</span>
                        </div>

                        <!-- Job Items -->
                        <div v-for="job in pending_jobs.data" :key="job.id" class="px-6 py-4">
                            <div class="flex items-start space-x-4">
                                <input
                                    type="checkbox"
                                    :checked="selectedJobs.includes(job.id)"
                                    @change="toggleJobSelection(job.id)"
                                    class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-medium text-gray-900">{{ job.title }}</h3>
                                            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                <span class="font-medium">{{ job.employer.company_name }}</span>
                                                <span>{{ job.location }}</span>
                                                <span>{{ formatSalary(job) }}</span>
                                                <span>{{ job.course?.name }}</span>
                                            </div>
                                            <p class="mt-2 text-sm text-gray-700 line-clamp-2">{{ job.description }}</p>
                                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                                <span>Posted {{ formatDate(job.created_at) }}</span>
                                                <span v-if="job.application_deadline">Deadline: {{ formatDate(job.application_deadline) }}</span>
                                                <span>{{ job.experience_level }} level</span>
                                                <span>{{ job.job_type.replace('_', ' ') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Link
                                        :href="route('admin.job-approval.show', job.id)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        Review
                                    </Link>
                                    <button
                                        @click="$inertia.post(route('admin.job-approval.approve', job.id))"
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        @click="() => {
                                            const reason = prompt('Reason for rejection:');
                                            if (reason) $inertia.post(route('admin.job-approval.reject', job.id), { reason });
                                        }"
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm font-medium"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No jobs pending approval</h3>
                        <p class="mt-1 text-sm text-gray-500">All jobs have been reviewed.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pending_jobs.links && pending_jobs.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="pending_jobs.prev_page_url"
                                    :href="pending_jobs.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="pending_jobs.next_page_url"
                                    :href="pending_jobs.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ pending_jobs.from }} to {{ pending_jobs.to }} of {{ pending_jobs.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link
                                            v-for="link in pending_jobs.links"
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