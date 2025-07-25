<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    analytics: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatPercentage = (value) => {
    return Math.round(value) + '%';
};

const chartData = computed(() => {
    return props.analytics.application_trends.map(trend => ({
        date: trend.date,
        applications: trend.count,
        formattedDate: formatDate(trend.date)
    }));
});

const maxApplications = computed(() => {
    return Math.max(...props.analytics.application_trends.map(t => t.count), 1);
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

const hiringFunnelData = computed(() => {
    const metrics = props.analytics.hiring_metrics;
    const total = metrics.total_applications || 1;
    
    return [
        { stage: 'Applications', count: metrics.total_applications, percentage: 100 },
        { stage: 'Reviewed', count: metrics.reviewed, percentage: (metrics.reviewed / total) * 100 },
        { stage: 'Shortlisted', count: metrics.shortlisted, percentage: (metrics.shortlisted / total) * 100 },
        { stage: 'Interviewed', count: metrics.interviewed, percentage: (metrics.interviewed / total) * 100 },
        { stage: 'Hired', count: metrics.hired, percentage: (metrics.hired / total) * 100 },
    ];
});
</script>

<template>
    <Head title="Hiring Analytics" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Hiring Analytics
                </h2>
                <Link :href="route('employer.dashboard')" 
                      class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.5" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Jobs Posted (30 days)</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.overview.jobs_posted_last_30_days }}</dd>
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Applications (30 days)</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.overview.applications_last_30_days }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Hires (30 days)</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ analytics.overview.hires_last_30_days }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Conversion Rate</dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ analytics.overview.applications_last_30_days > 0 ? formatPercentage((analytics.overview.hires_last_30_days / analytics.overview.applications_last_30_days) * 100) : '0%' }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Application Trends Chart -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Application Trends (Last 30 Days)</h3>
                            <div v-if="chartData.length > 0" class="space-y-2">
                                <div v-for="trend in chartData" :key="trend.date" class="flex items-center">
                                    <div class="w-20 text-xs text-gray-600">{{ trend.formattedDate }}</div>
                                    <div class="flex-1 ml-4">
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-blue-600 h-2 rounded-full" 
                                                     :style="`width: ${(trend.applications / maxApplications) * 100}%`"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 w-8">{{ trend.applications }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                No application data available
                            </div>
                        </div>
                    </div>

                    <!-- Hiring Funnel -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Hiring Funnel</h3>
                            <div class="space-y-4">
                                <div v-for="(stage, index) in hiringFunnelData" :key="stage.stage" class="relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">{{ stage.stage }}</span>
                                        <span class="text-sm text-gray-600">{{ stage.count }} ({{ formatPercentage(stage.percentage) }})</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div :class="[
                                            'h-3 rounded-full transition-all duration-300',
                                            index === 0 ? 'bg-blue-600' :
                                            index === 1 ? 'bg-indigo-600' :
                                            index === 2 ? 'bg-purple-600' :
                                            index === 3 ? 'bg-pink-600' : 'bg-green-600'
                                        ]" :style="`width: ${stage.percentage}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Performance -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Top Performing Jobs</h3>
                        <div v-if="analytics.job_performance.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Job Title
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Applications
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Posted Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Performance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="job in analytics.job_performance" :key="job.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <Link :href="route('jobs.show', job.id)" class="hover:text-indigo-600">
                                                    {{ job.title }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                job.status === 'active' ? 'bg-green-100 text-green-800' :
                                                job.status === 'filled' ? 'bg-blue-100 text-blue-800' :
                                                job.status === 'expired' ? 'bg-red-100 text-red-800' :
                                                'bg-gray-100 text-gray-800'
                                            ]">
                                                {{ job.status?.replace('_', ' ').toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ job.applications_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(job.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div :class="[
                                                        'h-2 rounded-full',
                                                        job.applications_count >= 20 ? 'bg-green-600' :
                                                        job.applications_count >= 10 ? 'bg-yellow-600' : 'bg-red-600'
                                                    ]" :style="`width: ${Math.min((job.applications_count / 30) * 100, 100)}%`"></div>
                                                </div>
                                                <span :class="[
                                                    'text-xs font-medium',
                                                    job.applications_count >= 20 ? 'text-green-600' :
                                                    job.applications_count >= 10 ? 'text-yellow-600' : 'text-red-600'
                                                ]">
                                                    {{ job.applications_count >= 20 ? 'High' :
                                                       job.applications_count >= 10 ? 'Medium' : 'Low' }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            No job performance data available
                        </div>
                    </div>
                </div>

                <!-- Candidate Insights -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Courses -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Top Courses (Applicants)</h3>
                            <div v-if="Object.keys(analytics.candidate_insights.top_courses).length > 0" class="space-y-3">
                                <div v-for="(count, course) in analytics.candidate_insights.top_courses" :key="course" 
                                     class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">{{ course || 'Unknown' }}</span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-indigo-600 h-2 rounded-full" 
                                                 :style="`width: ${(count / Math.max(...Object.values(analytics.candidate_insights.top_courses))) * 100}%`"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                No course data available
                            </div>
                        </div>
                    </div>

                    <!-- Graduation Years -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Graduation Years (Applicants)</h3>
                            <div v-if="Object.keys(analytics.candidate_insights.graduation_years).length > 0" class="space-y-3">
                                <div v-for="(count, year) in analytics.candidate_insights.graduation_years" :key="year" 
                                     class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">{{ year || 'Unknown' }}</span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-600 h-2 rounded-full" 
                                                 :style="`width: ${(count / Math.max(...Object.values(analytics.candidate_insights.graduation_years))) * 100}%`"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                No graduation year data available
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Hiring Recommendations</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li v-if="analytics.overview.applications_last_30_days < 5">
                                        Consider improving your job descriptions or expanding your reach to attract more candidates
                                    </li>
                                    <li v-if="(analytics.overview.hires_last_30_days / Math.max(analytics.overview.applications_last_30_days, 1)) < 0.1">
                                        Your conversion rate is low. Review your hiring criteria and process efficiency
                                    </li>
                                    <li v-if="analytics.hiring_metrics.pending > analytics.hiring_metrics.reviewed">
                                        You have many pending applications. Consider reviewing them promptly to improve candidate experience
                                    </li>
                                    <li>
                                        Use the graduate search feature to proactively find candidates that match your requirements
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>