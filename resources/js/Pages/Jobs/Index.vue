<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    jobs: Object,
    analytics: Object,
    filters: Object,
    courses: Array,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const course_id = ref(props.filters.course_id || '');
const experience_level = ref(props.filters.experience_level || '');
const date_from = ref(props.filters.date_from || '');
const date_to = ref(props.filters.date_to || '');
const selectedJobs = ref([]);
const showFilters = ref(false);

watch(
    [search, status, course_id, experience_level, date_from, date_to],
    ([searchValue, statusValue, courseValue, expValue, dateFromValue, dateToValue]) => {
        router.get(
            route('jobs.index'),
            {
                search: searchValue,
                status: statusValue,
                course_id: courseValue,
                experience_level: expValue,
                date_from: dateFromValue,
                date_to: dateToValue,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    },
);

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        pending_approval: 'bg-yellow-100 text-yellow-800',
        paused: 'bg-gray-100 text-gray-800',
        filled: 'bg-blue-100 text-blue-800',
        expired: 'bg-red-100 text-red-800',
        cancelled: 'bg-red-100 text-red-800',
        draft: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        active: 'Active',
        pending_approval: 'Pending Approval',
        paused: 'Paused',
        filled: 'Filled',
        expired: 'Expired',
        cancelled: 'Cancelled',
        draft: 'Draft',
    };
    return texts[status] || status;
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'N/A';
};

const formatSalary = (job) => {
    if (!job.salary_min && !job.salary_max) return 'Negotiable';

    const min = job.salary_min ? Number(job.salary_min).toLocaleString() : '';
    const max = job.salary_max ? Number(job.salary_max).toLocaleString() : '';

    if (min && max) {
        return `${min} - ${max} (${job.salary_type})`;
    } else if (min) {
        return `From ${min} (${job.salary_type})`;
    } else if (max) {
        return `Up to ${max} (${job.salary_type})`;
    }

    return 'Negotiable';
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
        selectedJobs.value = props.jobs.data.map((job) => job.id);
    }
};

const bulkAction = (action) => {
    if (selectedJobs.value.length === 0) return;

    let confirmMessage = '';
    const additionalData = {};

    switch (action) {
        case 'pause':
            confirmMessage = `Are you sure you want to pause ${selectedJobs.value.length} jobs?`;
            break;
        case 'resume':
            confirmMessage = `Are you sure you want to resume ${selectedJobs.value.length} jobs?`;
            break;
        case 'delete':
            confirmMessage = `Are you sure you want to delete ${selectedJobs.value.length} jobs? This action cannot be undone.`;
            break;
        case 'extend':
            const days = prompt('How many days to extend the deadline?');
            if (!days || isNaN(days)) return;
            confirmMessage = `Extend deadline by ${days} days for ${selectedJobs.value.length} jobs?`;
            additionalData.extension_days = parseInt(days);
            break;
    }

    if (confirm(confirmMessage)) {
        router.post(route('jobs.bulk-action'), {
            action: action,
            job_ids: selectedJobs.value,
            ...additionalData,
        });
    }
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    course_id.value = '';
    experience_level.value = '';
    date_from.value = '';
    date_to.value = '';
};
</script>

<template>
    <Head title="My Jobs" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">My Jobs</h2>
                <Link :href="route('jobs.create')" class="rounded-md bg-indigo-600 px-4 py-2 font-bold text-white hover:bg-indigo-700">
                    Post New Job
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Analytics Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Total Jobs</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.total_jobs }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-green-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Active Jobs</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.active_jobs }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-yellow-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Pending</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.pending_jobs }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-red-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Expiring Soon</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.jobs_expiring_soon }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Applications</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.total_applications }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-purple-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Avg per Job</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ Math.round(analytics.avg_applications_per_job || 0) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Job Alert -->
                <div v-if="analytics.top_performing_job" class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800">
                                <strong>Top Performer:</strong> "{{ analytics.top_performing_job.title }}" with
                                {{ analytics.top_performing_job.total_applications }} applications
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="rounded-lg bg-white shadow">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Filters & Search</h3>
                            <div class="flex items-center space-x-3">
                                <button @click="showFilters = !showFilters" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    {{ showFilters ? 'Hide' : 'Show' }} Advanced Filters
                                </button>
                                <button @click="clearFilters" class="text-sm text-gray-600 hover:text-gray-800">Clear All</button>
                            </div>
                        </div>

                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700">Search Jobs</label>
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Search by title or description..."
                                />
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    id="status"
                                    v-model="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
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
                            <div v-if="selectedJobs.length > 0" class="flex items-end">
                                <div class="flex space-x-2">
                                    <button
                                        @click="bulkAction('pause')"
                                        class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-medium text-white hover:bg-yellow-700"
                                    >
                                        Pause ({{ selectedJobs.length }})
                                    </button>
                                    <button
                                        @click="bulkAction('resume')"
                                        class="rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700"
                                    >
                                        Resume ({{ selectedJobs.length }})
                                    </button>
                                    <button
                                        @click="bulkAction('extend')"
                                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                    >
                                        Extend ({{ selectedJobs.length }})
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-show="showFilters" class="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-4">
                            <div>
                                <label for="course_id" class="block text-sm font-medium text-gray-700">Course</label>
                                <select
                                    id="course_id"
                                    v-model="course_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label for="experience_level" class="block text-sm font-medium text-gray-700">Experience Level</label>
                                <select
                                    id="experience_level"
                                    v-model="experience_level"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Levels</option>
                                    <option value="entry">Entry Level</option>
                                    <option value="junior">Junior</option>
                                    <option value="mid">Mid Level</option>
                                    <option value="senior">Senior</option>
                                    <option value="executive">Executive</option>
                                </select>
                            </div>
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700">Posted From</label>
                                <input
                                    id="date_from"
                                    v-model="date_from"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700">Posted To</label>
                                <input
                                    id="date_to"
                                    v-model="date_to"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jobs List -->
                <div class="overflow-hidden bg-white shadow sm:rounded-md">
                    <!-- Bulk Selection Header -->
                    <div v-if="jobs.data.length > 0" class="border-b border-gray-200 bg-gray-50 px-6 py-3">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="selectedJobs.length === jobs.data.length && jobs.data.length > 0"
                                @change="selectAllJobs"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="ml-3 text-sm font-medium text-gray-700">
                                {{ selectedJobs.length > 0 ? `${selectedJobs.length} selected` : 'Select All' }}
                            </span>
                        </div>
                    </div>

                    <ul class="divide-y divide-gray-200">
                        <li v-for="job in jobs.data" :key="job.id" class="px-6 py-4">
                            <div class="flex items-start space-x-4">
                                <input
                                    type="checkbox"
                                    :checked="selectedJobs.includes(job.id)"
                                    @change="toggleJobSelection(job.id)"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <h3 class="truncate text-lg font-medium text-gray-900">
                                                    {{ job.title }}
                                                </h3>
                                                <span
                                                    :class="getStatusColor(job.status)"
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                >
                                                    {{ getStatusText(job.status) }}
                                                </span>
                                                <span
                                                    v-if="job.application_deadline && getDaysUntilDeadline(job.application_deadline) <= 3"
                                                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800"
                                                >
                                                    {{ getDaysUntilDeadline(job.application_deadline) }} days left
                                                </span>
                                            </div>
                                            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                <span>{{ job.location }}</span>
                                                <span>{{ formatSalary(job) }}</span>
                                                <span>{{ job.course?.name }}</span>
                                                <span>{{ job.experience_level }} level</span>
                                            </div>
                                            <div class="mt-2 flex items-center space-x-6 text-sm text-gray-500">
                                                <span class="flex items-center">
                                                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                                        ></path>
                                                    </svg>
                                                    {{ job.total_applications || 0 }} applications
                                                </span>
                                                <span class="flex items-center">
                                                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                        ></path>
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                        ></path>
                                                    </svg>
                                                    {{ job.view_count || 0 }} views
                                                </span>
                                                <span>Posted {{ formatDate(job.created_at) }}</span>
                                                <span v-if="job.application_deadline">Deadline: {{ formatDate(job.application_deadline) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Link :href="route('jobs.show', job.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                        View
                                    </Link>
                                    <Link :href="route('jobs.edit', job.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </Link>
                                    <Link
                                        :href="route('jobs.applications.index', job.id)"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                    >
                                        Applications ({{ job.total_applications || 0 }})
                                    </Link>
                                    <div class="relative inline-block text-left">
                                        <button
                                            type="button"
                                            class="text-gray-400 hover:text-gray-600"
                                            @click="$event.target.nextElementSibling.classList.toggle('hidden')"
                                        >
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"
                                                ></path>
                                            </svg>
                                        </button>
                                        <div
                                            class="absolute right-0 z-10 mt-2 hidden w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                                        >
                                            <div class="py-1">
                                                <button
                                                    v-if="job.status === 'active'"
                                                    @click="$inertia.post(route('jobs.pause', job.id))"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Pause Job
                                                </button>
                                                <button
                                                    v-if="job.status === 'paused'"
                                                    @click="$inertia.post(route('jobs.resume', job.id))"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Resume Job
                                                </button>
                                                <button
                                                    v-if="['active', 'paused'].includes(job.status)"
                                                    @click="$inertia.post(route('jobs.mark-filled', job.id))"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Mark as Filled
                                                </button>
                                                <button
                                                    v-if="job.status === 'expired'"
                                                    @click="
                                                        () => {
                                                            const days = prompt('Extend deadline by how many days?', '30');
                                                            if (days)
                                                                $inertia.post(route('jobs.renew', job.id), {
                                                                    application_deadline: new Date(Date.now() + days * 24 * 60 * 60 * 1000)
                                                                        .toISOString()
                                                                        .split('T')[0],
                                                                });
                                                        }
                                                    "
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Renew Job
                                                </button>
                                                <button
                                                    @click="$inertia.post(route('jobs.duplicate', job.id))"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Duplicate Job
                                                </button>
                                                <Link
                                                    :href="route('jobs.analytics', job.id)"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    View Analytics
                                                </Link>
                                                <Link
                                                    :href="route('jobs.smart-recommendations', job.id)"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Smart Recommendations
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <div v-if="jobs.links" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <div class="flex items-center justify-between">
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
                                    <p class="text-sm text-gray-700">Showing {{ jobs.from }} to {{ jobs.to }} of {{ jobs.total }} results</p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                        <Link
                                            v-for="link in jobs.links"
                                            :key="link.label"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center border px-2 py-2 text-sm font-medium',
                                                link.active
                                                    ? 'z-10 border-indigo-500 bg-indigo-50 text-indigo-600'
                                                    : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                            ]"
                                        />
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="jobs.data.length === 0" class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No jobs found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by posting your first job.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('jobs.create')"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                        >
                            Post New Job
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>













