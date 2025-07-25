<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Institution Analytics" />
        
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
                            <Link :href="route('institution-admin.analytics')" class="text-blue-600 font-medium">Analytics</Link>
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
                    <h1 class="text-3xl font-bold text-gray-900">Institution Analytics</h1>
                    <p class="mt-2 text-gray-600">Comprehensive insights into graduate outcomes and institutional performance</p>
                </div>

                <!-- Graduates by Year Chart -->
                <div class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Graduates by Year</h3>
                        <div class="h-64 flex items-end justify-center space-x-2">
                            <div 
                                v-for="yearData in analytics.graduatesByYear" 
                                :key="yearData.year"
                                class="flex flex-col items-center"
                            >
                                <div 
                                    class="bg-blue-500 rounded-t"
                                    :style="{ 
                                        height: (yearData.count / maxGraduatesPerYear * 200) + 'px',
                                        width: '40px'
                                    }"
                                ></div>
                                <div class="text-xs text-gray-600 mt-2">{{ yearData.year }}</div>
                                <div class="text-sm font-medium text-gray-900">{{ yearData.count }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Rates by Course -->
                <div class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Employment Rates by Course</h3>
                        <div class="space-y-4">
                            <div 
                                v-for="course in analytics.employmentRates" 
                                :key="course.course"
                                class="flex items-center"
                            >
                                <div class="w-1/3">
                                    <div class="text-sm font-medium text-gray-900">{{ course.course }}</div>
                                    <div class="text-xs text-gray-500">{{ course.employed }}/{{ course.total }} employed</div>
                                </div>
                                <div class="w-2/3 ml-4">
                                    <div class="flex items-center">
                                        <div class="w-full bg-gray-200 rounded-full h-2 mr-4">
                                            <div 
                                                class="h-2 rounded-full"
                                                :class="{
                                                    'bg-green-500': course.rate >= 70,
                                                    'bg-yellow-500': course.rate >= 50 && course.rate < 70,
                                                    'bg-red-500': course.rate < 50
                                                }"
                                                :style="{ width: course.rate + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 min-w-0">{{ course.rate }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Ranges and Top Employers -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Salary Distribution</h3>
                            <div class="space-y-3">
                                <div 
                                    v-for="salary in analytics.salaryRanges" 
                                    :key="salary.range"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-sm text-gray-600">{{ formatSalaryRange(salary.range) }}</span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-3">
                                            <div 
                                                class="bg-blue-500 h-2 rounded-full"
                                                :style="{ width: (salary.count / maxSalaryCount * 100) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ salary.count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Top Employers</h3>
                            <div class="space-y-3">
                                <div 
                                    v-for="employer in analytics.topEmployers.slice(0, 8)" 
                                    :key="employer.company"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-sm text-gray-900 truncate">{{ employer.company }}</span>
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                            <div 
                                                class="bg-green-500 h-2 rounded-full"
                                                :style="{ width: (employer.count / maxEmployerCount * 100) + '%' }"
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
                <div class="bg-white shadow rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Detailed Course Outcomes</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Graduates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seeking</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unemployed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Salary</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="course in analytics.courseOutcomes" :key="course.course">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ course.course }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ course.total_graduates }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ course.employed }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">{{ course.seeking }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">{{ course.unemployed }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ course.average_salary ? '$' + course.average_salary.toLocaleString() : 'N/A' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Job Application Trends -->
                <div class="bg-white shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Job Application Trends (Last 30 Days)</h3>
                        <div class="h-48 flex items-end justify-center space-x-1">
                            <div 
                                v-for="trend in analytics.jobApplicationTrends" 
                                :key="trend.date"
                                class="flex flex-col items-center"
                            >
                                <div 
                                    class="bg-purple-500 rounded-t"
                                    :style="{ 
                                        height: (trend.count / maxApplicationsPerDay * 150) + 'px',
                                        width: '8px'
                                    }"
                                ></div>
                                <div class="text-xs text-gray-600 mt-1 transform -rotate-45 origin-top-left">
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

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    analytics: Object,
});

const maxGraduatesPerYear = computed(() => {
    return Math.max(...props.analytics.graduatesByYear.map(item => item.count));
});

const maxSalaryCount = computed(() => {
    return Math.max(...props.analytics.salaryRanges.map(item => item.count));
});

const maxEmployerCount = computed(() => {
    return Math.max(...props.analytics.topEmployers.map(item => item.count));
});

const maxApplicationsPerDay = computed(() => {
    return Math.max(...props.analytics.jobApplicationTrends.map(item => item.count));
});

const formatSalaryRange = (range) => {
    const ranges = {
        'below_20k': 'Below $20k',
        '20k_30k': '$20k - $30k',
        '30k_40k': '$30k - $40k',
        '40k_50k': '$40k - $50k',
        '50k_75k': '$50k - $75k',
        '75k_100k': '$75k - $100k',
        'above_100k': 'Above $100k',
    };
    return ranges[range] || range;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>