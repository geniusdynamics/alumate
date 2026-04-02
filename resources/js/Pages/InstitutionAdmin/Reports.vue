<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Institution Reports" />

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
                            <Link :href="route('institution-admin.analytics')" class="text-gray-500 hover:text-gray-700">Analytics</Link>
                            <Link :href="route('institution-admin.reports')" class="font-medium text-blue-600">Reports</Link>
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
                    <h1 class="text-3xl font-bold text-gray-900">Institution Reports</h1>
                    <p class="mt-2 text-gray-600">Generate and view detailed reports on graduate outcomes and institutional performance</p>
                </div>

                <!-- Report Controls -->
                <div class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Report Type</label>
                                <select
                                    v-model="selectedReportType"
                                    @change="updateReport"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="employment">Employment Report</option>
                                    <option value="course_performance">Course Performance</option>
                                    <option value="graduate_outcomes">Graduate Outcomes</option>
                                    <option value="job_placement">Job Placement</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Date Range</label>
                                <select
                                    v-model="selectedDateRange"
                                    @change="updateReport"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="1_month">Last Month</option>
                                    <option value="3_months">Last 3 Months</option>
                                    <option value="6_months">Last 6 Months</option>
                                    <option value="1_year">Last Year</option>
                                    <option value="2_years">Last 2 Years</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button
                                    @click="exportReport"
                                    class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    Export Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Report -->
                <div v-if="selectedReportType === 'employment'" class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ reports.employment.title }}</h3>
                        <p class="mb-6 text-sm text-gray-600">Period: {{ formatPeriod(reports.employment.period) }}</p>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div
                                v-for="status in reports.employment.data"
                                :key="status.status"
                                class="rounded-lg p-4 text-center"
                                :class="{
                                    'bg-green-50': status.status === 'employed',
                                    'bg-yellow-50': status.status === 'seeking',
                                    'bg-red-50': status.status === 'unemployed',
                                }"
                            >
                                <div
                                    class="mb-2 text-3xl font-bold"
                                    :class="{
                                        'text-green-600': status.status === 'employed',
                                        'text-yellow-600': status.status === 'seeking',
                                        'text-red-600': status.status === 'unemployed',
                                    }"
                                >
                                    {{ status.count }}
                                </div>
                                <div class="text-sm font-medium capitalize text-gray-900">{{ status.status }}</div>
                                <div class="text-xs text-gray-500">{{ status.percentage }}% of total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Performance Report -->
                <div v-if="selectedReportType === 'course_performance'" class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ reports.course_performance.title }}</h3>
                        <p class="mb-6 text-sm text-gray-600">Period: {{ formatPeriod(reports.course_performance.period) }}</p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Graduates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Employment Rate
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="course in reports.course_performance.data" :key="course.course">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ course.course }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ course.graduates }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-green-600">{{ course.employed }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <div class="mr-3 h-2 w-16 rounded-full bg-gray-200">
                                                    <div
                                                        class="h-2 rounded-full"
                                                        :class="{
                                                            'bg-green-500': course.employment_rate >= 70,
                                                            'bg-yellow-500': course.employment_rate >= 50 && course.employment_rate < 70,
                                                            'bg-red-500': course.employment_rate < 50,
                                                        }"
                                                        :style="{ width: course.employment_rate + '%' }"
                                                    ></div>
                                                </div>
                                                <span>{{ course.employment_rate }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Graduate Outcomes Report -->
                <div v-if="selectedReportType === 'graduate_outcomes'" class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ reports.graduate_outcomes.title }}</h3>
                        <p class="mb-6 text-sm text-gray-600">Period: {{ formatPeriod(reports.graduate_outcomes.period) }}</p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Graduate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Graduation Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Company</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Position</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="graduate in reports.graduate_outcomes.data.slice(0, 50)" :key="graduate.name">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ graduate.name }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ graduate.course }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ graduate.graduation_date ? formatDate(graduate.graduation_date) : 'N/A' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize"
                                                :class="{
                                                    'bg-green-100 text-green-800': graduate.employment_status === 'employed',
                                                    'bg-yellow-100 text-yellow-800': graduate.employment_status === 'seeking',
                                                    'bg-red-100 text-red-800': graduate.employment_status === 'unemployed',
                                                    'bg-gray-100 text-gray-800': graduate.employment_status === 'unknown',
                                                }"
                                            >
                                                {{ graduate.employment_status }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ graduate.company || 'N/A' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ graduate.position || 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="reports.graduate_outcomes.data.length > 50" class="mt-4 text-center text-sm text-gray-500">
                            Showing first 50 results. Export for complete data.
                        </div>
                    </div>
                </div>

                <!-- Job Placement Report -->
                <div v-if="selectedReportType === 'job_placement'" class="mb-8 rounded-lg bg-white shadow">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ reports.job_placement.title }}</h3>
                        <p class="mb-6 text-sm text-gray-600">Period: {{ formatPeriod(reports.job_placement.period) }}</p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Graduate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Job Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Company</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hired Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="placement in reports.job_placement.data" :key="placement.graduate + placement.hired_date">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ placement.graduate }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ placement.course }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ placement.job_title }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ placement.company }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ formatDate(placement.hired_date) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="reports.job_placement.data.length === 0" class="py-8 text-center text-gray-500">
                            No job placements found for the selected period.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reports: Object,
    currentReport: String,
    dateRange: String,
});

const selectedReportType = ref(props.currentReport);
const selectedDateRange = ref(props.dateRange);

const updateReport = () => {
    router.get(
        route('institution-admin.reports'),
        {
            type: selectedReportType.value,
            date_range: selectedDateRange.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const exportReport = () => {
    router.post(route('institution-admin.reports.export'), {
        type: selectedReportType.value,
        date_range: selectedDateRange.value,
    });
};

const formatPeriod = (period) => {
    const periods = {
        '1_month': 'Last Month',
        '3_months': 'Last 3 Months',
        '6_months': 'Last 6 Months',
        '1_year': 'Last Year',
        '2_years': 'Last 2 Years',
    };
    return periods[period] || period;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

