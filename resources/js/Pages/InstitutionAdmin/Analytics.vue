<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Institution Analytics" />

        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center space-x-8">
                        <Link :href="route('institution-admin.dashboard')" class="text-xl font-semibold text-gray-900 hover:text-gray-700">
                            Institution Admin
                        </Link>
                        <nav class="flex space-x-8">
                            <Link :href="route('institution-admin.dashboard')" class="text-gray-500 hover:text-gray-700">Dashboard</Link>
                            <Link :href="route('institution-admin.analytics')" class="font-medium text-blue-600">Analytics</Link>
                            <Link :href="route('institution-admin.reports')" class="text-gray-500 hover:text-gray-700">Reports</Link>
                            <Link :href="route('institution-admin.staff')" class="text-gray-500 hover:text-gray-700">Staff</Link>
                        </nav>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ $page.props.auth.user.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Institution Analytics</h1>
                    <p class="mt-2 text-gray-600">Comprehensive insights into graduate outcomes and institutional performance</p>
                </div>

                <!-- Graduates by Year Chart -->
                <div class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Graduates by Year</h3>
                        <div class="flex h-64 items-end justify-center space-x-2">
                            <div v-for="yearData in analytics.graduatesByYear" :key="yearData.year" class="flex flex-col items-center">
                                <div
                                    class="rounded-t bg-blue-500"
                                    :style="{
                                        height: (yearData.count / maxGraduatesPerYear) * 200 + 'px',
                                        width: '40px',
                                    }"
                                ></div>
                                <div class="mt-2 text-xs text-gray-600">{{ yearData.year }}</div>
                                <div class="text-sm font-medium text-gray-900">{{ yearData.count }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Rates by Course -->
                <div class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Employment Rates by Course</h3>
                        <div class="space-y-4">
                            <div v-for="course in analytics.employmentRates" :key="course.course" class="flex items-center">
                                <div class="w-1/3">
                                    <div class="text-sm font-medium text-gray-900">{{ course.course }}</div>
                                    <div class="text-xs text-gray-500">{{ course.employed }}/{{ course.total }} employed</div>
                                </div>
                                <div class="ml-4 w-2/3">
                                    <div class="flex items-center">
                                        <div class="mr-4 h-2 w-full rounded-full bg-gray-200">
                                            <div
                                                class="h-2 rounded-full"
                                                :class="{
                                                    'bg-green-500': course.rate >= 70,
                                                    'bg-yellow-500': course.rate >= 50 && course.rate < 70,
                                                    'bg-red-500': course.rate < 50,
                                                }"
                                                :style="{ width: course.rate + '%' }"
                                            ></div>
                                        </div>
                                        <span class="min-w-0 text-sm font-medium text-gray-900">{{ course.rate }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Analytics Cards -->
                <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Time to Employment</h3>
                        <div v-if="analytics.timeToEmployment" class="space-y-2">
                            <p class="text-sm"><strong>Average:</strong> {{ analytics.timeToEmployment.average_days }} days</p>
                            <p class="text-sm"><strong>Median:</strong> {{ analytics.timeToEmployment.median_days }} days</p>
                            <p class="text-sm"><strong>&lt; 6 Months:</strong> {{ analytics.timeToEmployment.under_6_months_percentage }}%</p>
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Salary Progression</h3>
                        <div v-if="analytics.salaryProgression" class="space-y-2">
                            <p class="text-sm"><strong>1 Year Avg:</strong> ${{ analytics.salaryProgression.year_1.average.toLocaleString() }}</p>
                            <p class="text-sm"><strong>3 Year Avg:</strong> ${{ analytics.salaryProgression.year_3.average.toLocaleString() }}</p>
                            <p class="text-sm"><strong>5 Year Avg:</strong> ${{ analytics.salaryProgression.year_5.average.toLocaleString() }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Employment by Location</h3>
                        <div class="space-y-2">
                            <div v-for="location in analytics.employmentByLocation" :key="location.location" class="flex justify-between text-sm">
                                <span>{{ location.location }}</span>
                                <span>{{ location.count }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Ranges and Top Employers -->
                <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white shadow">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Salary Distribution</h3>
                            <div class="space-y-3">
                                <div v-for="salary in analytics.salaryRanges" :key="salary.range" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">{{ formatSalaryRange(salary.range) }}</span>
                                    <div class="flex items-center">
                                        <div class="mr-3 h-2 w-20 rounded-full bg-gray-200">
                                            <div
                                                class="h-2 rounded-full bg-blue-500"
                                                :style="{ width: (salary.count / maxSalaryCount) * 100 + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ salary.count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white shadow">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Top Employers</h3>
                            <div class="space-y-3">
                                <div
                                    v-for="employer in analytics.topEmployers.slice(0, 8)"
                                    :key="employer.company"
                                    class="flex items-center justify-between"
                                >
                                    <span class="truncate text-sm text-gray-900">{{ employer.company }}</span>
                                    <div class="flex items-center">
                                        <div class="mr-3 h-2 w-16 rounded-full bg-gray-200">
                                            <div
                                                class="h-2 rounded-full bg-green-500"
                                                :style="{ width: (employer.count / maxEmployerCount) * 100 + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ employer.count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Outcomes Detail -->
                <div class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Detailed Course Outcomes</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Total Graduates
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Seeking</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unemployed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Avg. Salary</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="course in analytics.courseOutcomes" :key="course.course">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ course.course }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ course.total_graduates }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-green-600">{{ course.employed }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-yellow-600">{{ course.seeking }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-red-600">{{ course.unemployed }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ course.average_salary ? '$' + course.average_salary.toLocaleString() : 'N/A' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Job Application Trends -->
                <div class="rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Job Application Trends (Last 30 Days)</h3>
                        <div class="flex h-48 items-end justify-center space-x-1">
                            <div v-for="trend in analytics.jobApplicationTrends" :key="trend.date" class="flex flex-col items-center">
                                <div
                                    class="rounded-t bg-purple-500"
                                    :style="{
                                        height: (trend.count / maxApplicationsPerDay) * 150 + 'px',
                                        width: '8px',
                                    }"
                                ></div>
                                <div class="mt-1 origin-top-left -rotate-45 transform text-xs text-gray-600">
                                    {{ formatDate(trend.date) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    analytics: Object,
});

const maxGraduatesPerYear = computed(() => {
    return Math.max(...props.analytics.graduatesByYear.map((item) => item.count));
});

const maxSalaryCount = computed(() => {
    return Math.max(...props.analytics.salaryRanges.map((item) => item.count));
});

const maxEmployerCount = computed(() => {
    return Math.max(...props.analytics.topEmployers.map((item) => item.count));
});

const maxApplicationsPerDay = computed(() => {
    return Math.max(...props.analytics.jobApplicationTrends.map((item) => item.count));
});

const formatSalaryRange = (range) => {
    const ranges = {
        below_20k: 'Below $20k',
        '20k_30k': '$20k - $30k',
        '30k_40k': '$30k - $40k',
        '40k_50k': '$40k - $50k',
        '50k_75k': '$50k - $75k',
        '75k_100k': '$75k - $100k',
        above_100k: 'Above $100k',
    };
    return ranges[range] || range;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

