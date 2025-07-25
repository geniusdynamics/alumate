<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Institution Reports" />
        
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <Link :href="route('institution-admin.dashboard')" class="text-xl font-semibold text-gray-900 hover:text-gray-700">
                            Institution Admin
                        </Link>
                        <nav class="flex space-x-8">
                            <Link :href="route('institution-admin.dashboard')" class="text-gray-500 hover:text-gray-700">Dashboard</Link>
                            <Link :href="route('institution-admin.analytics')" class="text-gray-500 hover:text-gray-700">Analytics</Link>
                            <Link :href="route('institution-admin.reports')" class="text-blue-600 font-medium">Reports</Link>
                            <Link :href="route('institution-admin.staff')" class="text-gray-500 hover:text-gray-700">Staff</Link>
                        </nav>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ $page.props.auth.user.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Institution Reports</h1>
                    <p class="mt-2 text-gray-600">Generate and view detailed reports on graduate outcomes and institutional performance</p>
                </div>

                <!-- Report Controls -->
                <div class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
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
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Export Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Report -->
                <div v-if="selectedReportType === 'employment'" class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ reports.employment.title }}</h3>
                        <p class="text-sm text-gray-600 mb-6">Period: {{ formatPeriod(reports.employment.period) }}</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div 
                                v-for="status in reports.employment.data" 
                                :key="status.status"
                                class="text-center p-4 rounded-lg"
                                :class="{
                                    'bg-green-50': status.status === 'employed',
                                    'bg-yellow-50': status.status === 'seeking',
                                    'bg-red-50': status.status === 'unemployed'
                                }"
                            >
                                <div 
                                    class="text-3xl font-bold mb-2"
                                    :class="{
                                        'text-green-600': status.status === 'employed',
                                        'text-yellow-600': status.status === 'seeking',
                                        'text-red-600': status.status === 'unemployed'
                                    }"
                                >
                                    {{ status.count }}
                                </div>
                                <div class="text-sm font-medium text-gray-900 capitalize">{{ status.status }}</div>
                                <div class="text-xs text-gray-500">{{ status.percentage }}% of total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Performance Report -->
                <div v-if="selectedReportType === 'course_performance'" class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ reports.course_performance.title }}</h3>
                        <p class="text-sm text-gray-600 mb-6">Period: {{ formatPeriod(reports.course_performance.period) }}</p>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="course in reports.course_performance.data" :key="course.course">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ course.course }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ course.graduates }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ course.employed }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                                    <div 
                                                        class="h-2 rounded-full"
                                                        :class="{
                                                            'bg-green-500': course.employment_rate >= 70,
                                                            'bg-yellow-500': course.employment_rate >= 50 && course.employment_rate < 70,
                                                            'bg-red-500': course.employment_rate < 50
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
                <div v-if="selectedReportType === 'graduate_outcomes'" class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ reports.graduate_outcomes.title }}</h3>
                        <p class="text-sm text-gray-600 mb-6">Period: {{ formatPeriod(reports.graduate_outcomes.period) }}</p>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduation Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="graduate in reports.graduate_outcomes.data.slice(0, 50)" :key="graduate.name">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ graduate.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ graduate.course }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ graduate.graduation_date ? formatDate(graduate.graduation_date) : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                                :class="{
                                                    'bg-green-100 text-green-800': graduate.employment_status === 'employed',
                                                    'bg-yellow-100 text-yellow-800': graduate.employment_status === 'seeking',
                                                    'bg-red-100 text-red-800': graduate.employment_status === 'unemployed',
                                                    'bg-gray-100 text-gray-800': graduate.employment_status === 'unknown'
                                                }"
                                            >
                                                {{ graduate.employment_status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ graduate.company || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ graduate.position || 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="reports.graduate_outcomes.data.length > 50" class="mt-4 text-sm text-gray-500 text-center">
                            Showing first 50 results. Export for complete data.
                        </div>
                    </div>
                </div>

                <!-- Job Placement Report -->
                <div v-if="selectedReportType === 'job_placement'" class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ reports.job_placement.title }}</h3>
                        <p class="text-sm text-gray-600 mb-6">Period: {{ formatPeriod(reports.job_placement.period) }}</p>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hired Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="placement in reports.job_placement.data" :key="placement.graduate + placement.hired_date">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ placement.graduate }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ placement.course }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ placement.job_title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ placement.company }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(placement.hired_date) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="reports.job_placement.data.length === 0" class="text-center py-8 text-gray-500">
                            No job placements found for the selected period.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
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
    router.get(route('institution-admin.reports'), {
        type: selectedReportType.value,
        date_range: selectedDateRange.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
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
        day: 'numeric' 
    });
};
</script>