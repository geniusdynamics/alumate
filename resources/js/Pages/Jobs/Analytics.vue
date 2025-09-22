<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    job: Object,
    analytics: Object,
    application_trends: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const chartData = computed(() => {
    return props.application_trends.map((trend) => ({
        date: trend.date,
        applications: trend.count,
        formattedDate: formatDate(trend.date),
    }));
});

const maxApplications = computed(() => {
    return Math.max(...props.application_trends.map((t) => t.count), 1);
});

const getPerformanceColor = (rate) => {
    if (rate >= 80) return 'text-green-600';
    if (rate >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getPerformanceText = (rate) => {
    if (rate >= 80) return 'Excellent';
    if (rate >= 60) return 'Good';
    if (rate >= 40) return 'Average';
    return 'Needs Improvement';
};
</script>

<template>
    <Head :title="`Analytics - ${job.title}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Job Analytics</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ job.title }} • {{ job.employer.company_name }}</p>
                </div>
                <Link
                    :href="route('jobs.show', job.id)"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Back to Job
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Key Metrics -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            ></path>
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Total Views</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.views }}</dd>
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
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Applications</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.applications }}</dd>
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
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Application Rate</dt>
                                        <dd :class="['text-lg font-medium', getPerformanceColor(analytics.application_rate)]">
                                            {{ analytics.application_rate }}%
                                        </dd>
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
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500">Days Active</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.days_active }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Funnel -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-6 text-lg font-medium text-gray-900">Application Funnel</h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">{{ analytics.views }}</div>
                            <div class="mt-1 text-sm text-gray-600">Views</div>
                            <div class="mt-2 h-2 rounded-full bg-blue-200">
                                <div class="h-2 w-full rounded-full bg-blue-600"></div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">{{ analytics.applications }}</div>
                            <div class="mt-1 text-sm text-gray-600">Applications</div>
                            <div class="mt-2 h-2 rounded-full bg-green-200">
                                <div
                                    class="h-2 rounded-full bg-green-600"
                                    :style="{ width: analytics.views > 0 ? (analytics.applications / analytics.views) * 100 + '%' : '0%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-yellow-600">{{ analytics.viewed_applications }}</div>
                            <div class="mt-1 text-sm text-gray-600">Reviewed</div>
                            <div class="mt-2 h-2 rounded-full bg-yellow-200">
                                <div
                                    class="h-2 rounded-full bg-yellow-600"
                                    :style="{
                                        width:
                                            analytics.applications > 0 ? (analytics.viewed_applications / analytics.applications) * 100 + '%' : '0%',
                                    }"
                                ></div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-purple-600">{{ analytics.shortlisted_applications }}</div>
                            <div class="mt-1 text-sm text-gray-600">Shortlisted</div>
                            <div class="mt-2 h-2 rounded-full bg-purple-200">
                                <div
                                    class="h-2 rounded-full bg-purple-600"
                                    :style="{
                                        width:
                                            analytics.viewed_applications > 0
                                                ? (analytics.shortlisted_applications / analytics.viewed_applications) * 100 + '%'
                                                : '0%',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Trends Chart -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-6 text-lg font-medium text-gray-900">Application Trends (Last 30 Days)</h3>
                    <div v-if="application_trends.length > 0" class="space-y-4">
                        <div class="flex h-64 items-end space-x-2">
                            <div v-for="trend in application_trends" :key="trend.date" class="flex flex-1 flex-col items-center">
                                <div
                                    class="min-h-[4px] w-full rounded-t bg-indigo-500 transition-all duration-300 hover:bg-indigo-600"
                                    :style="{ height: (trend.count / maxApplications) * 200 + 'px' }"
                                    :title="`${trend.count} applications on ${formatDate(trend.date)}`"
                                ></div>
                                <div class="mt-2 origin-left -rotate-45 transform text-xs text-gray-500">
                                    {{ formatDate(trend.date).split('/').slice(0, 2).join('/') }}
                                </div>
                            </div>
                        </div>
                        <div class="text-center text-sm text-gray-600">
                            Total applications in the last 30 days: {{ application_trends.reduce((sum, t) => sum + t.count, 0) }}
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-gray-500">No application data available for the last 30 days.</div>
                </div>

                <!-- Performance Insights -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Performance Insights</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Application Rate</span>
                                <div class="flex items-center space-x-2">
                                    <span :class="['text-sm font-medium', getPerformanceColor(analytics.application_rate)]">
                                        {{ getPerformanceText(analytics.application_rate) }}
                                    </span>
                                    <span class="text-sm text-gray-500">({{ analytics.application_rate }}%)</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Review Rate</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{
                                            analytics.applications > 0
                                                ? Math.round((analytics.viewed_applications / analytics.applications) * 100)
                                                : 0
                                        }}%
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Shortlist Rate</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{
                                            analytics.viewed_applications > 0
                                                ? Math.round((analytics.shortlisted_applications / analytics.viewed_applications) * 100)
                                                : 0
                                        }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Time Information</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Days Active</span>
                                <span class="text-sm font-medium text-gray-900">{{ analytics.days_active }}</span>
                            </div>
                            <div v-if="analytics.days_until_deadline !== null" class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Days Until Deadline</span>
                                <span
                                    :class="[
                                        'text-sm font-medium',
                                        analytics.days_until_deadline <= 3
                                            ? 'text-red-600'
                                            : analytics.days_until_deadline <= 7
                                              ? 'text-yellow-600'
                                              : 'text-green-600',
                                    ]"
                                >
                                    {{ analytics.days_until_deadline }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Avg. Applications/Day</span>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ analytics.days_active > 0 ? Math.round((analytics.applications / analytics.days_active) * 10) / 10 : 0 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-6">
                    <h3 class="mb-4 text-lg font-medium text-blue-900">Recommendations</h3>
                    <div class="space-y-3 text-sm">
                        <div v-if="analytics.application_rate < 5" class="flex items-start space-x-2">
                            <svg class="mt-0.5 h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <div>
                                <p class="font-medium text-blue-900">Low Application Rate</p>
                                <p class="text-blue-700">
                                    Consider revising your job description, salary range, or requirements to attract more candidates.
                                </p>
                            </div>
                        </div>
                        <div v-if="analytics.views > 50 && analytics.applications < 5" class="flex items-start space-x-2">
                            <svg class="mt-0.5 h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <div>
                                <p class="font-medium text-blue-900">High Views, Low Applications</p>
                                <p class="text-blue-700">
                                    Your job is getting attention but not converting. Review your requirements and application process.
                                </p>
                            </div>
                        </div>
                        <div v-if="analytics.days_until_deadline !== null && analytics.days_until_deadline <= 7" class="flex items-start space-x-2">
                            <svg class="mt-0.5 h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <div>
                                <p class="font-medium text-yellow-900">Deadline Approaching</p>
                                <p class="text-yellow-700">
                                    Your application deadline is in {{ analytics.days_until_deadline }} days. Consider extending if you need more
                                    candidates.
                                </p>
                            </div>
                        </div>
                        <div v-if="analytics.application_rate >= 10" class="flex items-start space-x-2">
                            <svg class="mt-0.5 h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <div>
                                <p class="font-medium text-green-900">Great Performance!</p>
                                <p class="text-green-700">
                                    Your job posting is performing well with a {{ analytics.application_rate }}% application rate.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>













