<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    course: Object,
    analytics: Object,
});

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString();
};

const getEmploymentRateColor = (rate) => {
    if (rate >= 80) return 'text-green-600';
    if (rate >= 60) return 'text-yellow-600';
    return 'text-red-600';
};
</script>

<template>
    <Head :title="`${course.name} - Analytics`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Course Analytics: {{ course.name }}</h2>
                <div class="flex gap-2">
                    <Link :href="route('courses.show', course.id)" class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">
                        Course Details
                    </Link>
                    <Link :href="route('courses.index')" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                        Back to Courses
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Overview Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600">Total Graduates</p>
                                    <p class="text-2xl font-bold text-blue-900">{{ analytics.graduate_statistics.total_graduates }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600">Employed</p>
                                    <p class="text-2xl font-bold text-green-900">{{ analytics.graduate_statistics.employed_graduates }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-yellow-600">Job Searching</p>
                                    <p class="text-2xl font-bold text-yellow-900">{{ analytics.graduate_statistics.job_search_active }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        :class="[
                                            'flex h-8 w-8 items-center justify-center rounded-full',
                                            course.employment_rate >= 80
                                                ? 'bg-green-500'
                                                : course.employment_rate >= 60
                                                  ? 'bg-yellow-500'
                                                  : 'bg-red-500',
                                        ]"
                                    >
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Employment Rate</p>
                                    <p :class="['text-2xl font-bold', getEmploymentRateColor(course.employment_rate || 0)]">
                                        {{ Math.round(course.employment_rate || 0) }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Trends Chart -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-6 text-lg font-medium text-gray-900">Employment Trends Over Time</h3>
                        <div class="space-y-4">
                            <div
                                v-for="(trend, year) in analytics.employment_trends"
                                :key="year"
                                class="flex items-center justify-between rounded-lg bg-gray-50 p-4"
                            >
                                <div class="flex items-center space-x-4">
                                    <div class="text-lg font-semibold text-gray-900">{{ year }}</div>
                                    <div class="text-sm text-gray-600">{{ trend.employed }}/{{ trend.total }} graduates employed</div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="h-3 w-32 rounded-full bg-gray-200">
                                        <div
                                            :class="[
                                                'h-3 rounded-full transition-all duration-300',
                                                trend.rate >= 80 ? 'bg-green-500' : trend.rate >= 60 ? 'bg-yellow-500' : 'bg-red-500',
                                            ]"
                                            :style="`width: ${trend.rate}%`"
                                        ></div>
                                    </div>
                                    <div :class="['text-lg font-semibold', getEmploymentRateColor(trend.rate)]">{{ trend.rate }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Salary Statistics -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="mb-6 text-lg font-medium text-gray-900">Salary Statistics</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Average Salary</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ formatCurrency(analytics.salary_statistics.average_salary) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Median Salary</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ formatCurrency(analytics.salary_statistics.median_salary) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="analytics.salary_statistics.salary_ranges" class="mt-6">
                                <h4 class="text-md mb-4 font-medium text-gray-800">Salary Distribution</h4>
                                <div class="space-y-3">
                                    <div
                                        v-for="(count, range) in analytics.salary_statistics.salary_ranges"
                                        :key="range"
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-600">{{ range }}</span>
                                        <div class="flex items-center space-x-2">
                                            <div class="h-2 w-20 rounded-full bg-gray-200">
                                                <div
                                                    class="h-2 rounded-full bg-blue-500"
                                                    :style="`width: ${Math.max((count / Math.max(...Object.values(analytics.salary_statistics.salary_ranges))) * 100, 5)}%`"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ count }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Job Matching -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="mb-6 text-lg font-medium text-gray-900">Job Market Analysis</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Total Related Jobs</span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ analytics.job_matching.total_jobs }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Currently Active</span>
                                    <span class="text-lg font-semibold text-green-600">
                                        {{ analytics.job_matching.active_jobs }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="analytics.job_matching.recent_jobs && analytics.job_matching.recent_jobs.length > 0" class="mt-6">
                                <h4 class="text-md mb-4 font-medium text-gray-800">Recent Job Postings</h4>
                                <div class="space-y-3">
                                    <div v-for="job in analytics.job_matching.recent_jobs" :key="job.id" class="rounded-lg bg-gray-50 p-3">
                                        <div class="font-medium text-gray-900">{{ job.title }}</div>
                                        <div class="text-sm text-gray-600">{{ job.employer?.company_name }}</div>
                                        <div class="text-xs text-gray-500">{{ formatDate(job.created_at) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills Analysis -->
                <div
                    v-if="analytics.skills_analysis && Object.keys(analytics.skills_analysis).length > 0"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <h3 class="mb-6 text-lg font-medium text-gray-900">Skills Analysis</h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div v-for="(data, skill) in analytics.skills_analysis" :key="skill" class="rounded-lg bg-gray-50 p-4">
                                <div class="mb-2 font-medium text-gray-900">{{ skill }}</div>
                                <div class="text-sm text-gray-600">{{ data.graduates_with_skill }} graduates have this skill</div>
                                <div class="mt-2">
                                    <div class="h-2 w-full rounded-full bg-gray-200">
                                        <div
                                            class="h-2 rounded-full bg-green-500"
                                            :style="`width: ${Math.min((data.graduates_with_skill / analytics.graduate_statistics.total_graduates) * 100, 100)}%`"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Export Analytics</h3>
                        <div class="flex gap-4">
                            <button @click="window.print()" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                                Print Report
                            </button>
                            <Link
                                :href="route('courses.export', { course_id: course.id, format: 'csv' })"
                                class="rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700"
                            >
                                Export to CSV
                            </Link>
                            <Link
                                :href="route('graduates.export', { course_id: course.id, format: 'csv' })"
                                class="rounded-md bg-purple-600 px-4 py-2 text-white hover:bg-purple-700"
                            >
                                Export Graduates
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














