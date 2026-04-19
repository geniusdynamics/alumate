<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    applications: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(
        route('graduate.applications'),
        {
            search: search.value,
            status: status.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        reviewed: 'bg-blue-100 text-blue-800',
        shortlisted: 'bg-purple-100 text-purple-800',
        interviewed: 'bg-indigo-100 text-indigo-800',
        hired: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const getStatusText = (status) => {
    const texts = {
        pending: 'Under Review',
        reviewed: 'Reviewed',
        shortlisted: 'Shortlisted',
        interviewed: 'Interviewed',
        hired: 'Hired',
        rejected: 'Not Selected',
    };
    return texts[status] || status;
};

const getStatusDescription = (status) => {
    const descriptions = {
        pending: 'Your application is being reviewed by the employer.',
        reviewed: 'The employer has reviewed your application.',
        shortlisted: 'Congratulations! You have been shortlisted for this position.',
        interviewed: 'You have been interviewed for this position.',
        hired: 'Congratulations! You have been selected for this position.',
        rejected: 'Unfortunately, you were not selected for this position.',
    };
    return descriptions[status] || '';
};

const withdrawApplication = (application) => {
    if (confirm('Are you sure you want to withdraw this application?')) {
        router.delete(route('applications.withdraw', application.id));
    }
};
</script>

<template>
    <Head title="My Applications" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My Applications</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Filter Applications</h3>
                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="search" class="mb-1 block text-sm font-medium text-gray-700">Search</label>
                            <input
                                id="search"
                                type="text"
                                v-model="search"
                                placeholder="Job title or company name..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                            <select
                                id="status"
                                v-model="status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Under Review</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="shortlisted">Shortlisted</option>
                                <option value="interviewed">Interviewed</option>
                                <option value="hired">Hired</option>
                                <option value="rejected">Not Selected</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="applyFilters" class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
                            Apply Filters
                        </button>
                        <button @click="clearFilters" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Applications List -->
                <div class="space-y-4">
                    <div
                        v-for="application in applications.data"
                        :key="application.id"
                        class="rounded-lg border border-gray-200 bg-white p-6 transition-shadow hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="mb-4 flex items-start justify-between">
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            <Link :href="route('jobs.public.show', application.job.id)" class="hover:text-indigo-600">
                                                {{ application.job.title }}
                                            </Link>
                                        </h3>
                                        <p class="text-lg text-gray-600">{{ application.job.employer?.company_name }}</p>
                                        <p class="text-sm text-gray-500">{{ application.job.location || 'Location not specified' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                                getStatusBadgeClass(application.status),
                                            ]"
                                        >
                                            {{ getStatusText(application.status) }}
                                        </span>
                                        <p class="mt-1 text-xs text-gray-500">Applied {{ formatDate(application.created_at) }}</p>
                                    </div>
                                </div>

                                <!-- Status Description -->
                                <div class="mb-4 rounded-lg bg-gray-50 p-3">
                                    <p class="text-sm text-gray-700">
                                        {{ getStatusDescription(application.status) }}
                                    </p>
                                </div>

                                <!-- Application Details -->
                                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Job Type</p>
                                        <p class="text-sm text-gray-900">{{ application.job.job_type?.replace('_', ' ').toUpperCase() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Experience Level</p>
                                        <p class="text-sm text-gray-900">{{ application.job.experience_level?.toUpperCase() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Course Match</p>
                                        <p class="text-sm text-gray-900">{{ application.job.course?.name || 'Any Course' }}</p>
                                    </div>
                                </div>

                                <!-- Timeline -->
                                <div v-if="application.status_history" class="mb-4">
                                    <p class="mb-2 text-sm font-medium text-gray-700">Application Timeline</p>
                                    <div class="space-y-2">
                                        <div v-for="(history, index) in application.status_history" :key="index" class="flex items-center text-sm">
                                            <div class="mr-3 h-2 w-2 rounded-full bg-indigo-600"></div>
                                            <span class="text-gray-600">{{ formatDate(history.date) }}</span>
                                            <span class="mx-2">-</span>
                                            <span class="text-gray-900">{{ getStatusText(history.status) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Interview Details -->
                                <div v-if="application.interview_datetime" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
                                    <div class="flex items-center">
                                        <svg class="mr-2 h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V7a2 2 0 012-2h4a2 2 0 012 2v0M9 11h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-blue-900">Interview Scheduled</p>
                                            <p class="text-sm text-blue-700">{{ formatDate(application.interview_datetime) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Job Offer Details -->
                                <div v-if="application.offer_details" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3">
                                    <div class="flex items-center">
                                        <svg class="mr-2 h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Job Offer Received</p>
                                            <p class="text-sm text-green-700">
                                                {{ application.offer_details.salary || 'Salary details in offer letter' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                                    <div class="flex items-center gap-4">
                                        <Link
                                            :href="route('jobs.public.show', application.job.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                        >
                                            View Job Details
                                        </Link>
                                        <Link
                                            :href="route('applications.show', application.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                        >
                                            View Application
                                        </Link>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="application.status === 'pending'"
                                            @click="withdrawApplication(application)"
                                            class="text-sm font-medium text-red-600 hover:text-red-500"
                                        >
                                            Withdraw Application
                                        </button>

                                        <button
                                            v-if="application.offer_details && application.status !== 'hired'"
                                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                        >
                                            Accept Offer
                                        </button>

                                        <button
                                            v-if="application.offer_details && application.status !== 'hired'"
                                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                        >
                                            Decline Offer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="applications.links" class="rounded-lg bg-white px-4 py-3 shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <Link
                                v-if="applications.prev_page_url"
                                :href="applications.prev_page_url"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="applications.next_page_url"
                                :href="applications.next_page_url"
                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing {{ applications.from }} to {{ applications.to }} of {{ applications.total }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                    <Link
                                        v-for="link in applications.links"
                                        :key="link.label"
                                        :href="link.url"
                                        :class="[
                                            'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                            link.active
                                                ? 'z-10 border-indigo-500 bg-indigo-50 text-indigo-600'
                                                : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                        ]"
                                        v-html="link.label"
                                    >
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="applications.data.length === 0" class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6 text-center">
                        <div class="mb-4 text-lg text-gray-500">No applications found</div>
                        <p class="mb-4 text-gray-400">
                            {{
                                Object.values(filters).some((f) => f) ? 'Try adjusting your search filters.' : "You haven't applied to any jobs yet."
                            }}
                        </p>
                        <Link
                            v-if="!Object.values(filters).some((f) => f)"
                            :href="route('graduate.jobs')"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Browse Jobs
                        </Link>
                    </div>
                </div>

                <!-- Application Tips -->
                <div class="rounded-md border border-blue-200 bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Application Tips</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-inside list-disc space-y-1">
                                    <li>Follow up on applications that have been pending for more than a week</li>
                                    <li>Prepare for interviews by researching the company and role</li>
                                    <li>Keep track of application deadlines and requirements</li>
                                    <li>Update your profile regularly to improve your chances</li>
                                    <li>Be professional in all communications with employers</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














