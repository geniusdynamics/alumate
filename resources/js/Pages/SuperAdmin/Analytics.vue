<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="System Analytics" />
        
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">System Analytics</h1>
                        <p class="mt-1 text-sm text-gray-600">Comprehensive system-wide analytics and insights</p>
                    </div>
                    <div class="flex space-x-3">
                        <select
                            v-model="selectedTimeframe"
                            @change="updateTimeframe"
                            class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        >
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                        <Link
                            :href="route('super-admin.dashboard')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <ArrowLeftIcon class="-ml-1 mr-2 h-5 w-5" />
                            Back to Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- User Growth Chart -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">User Growth</h2>
                    <p class="text-sm text-gray-500">Daily user registrations over time</p>
                </div>
                <div class="p-6">
                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                        <div class="text-center">
                            <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-sm text-gray-500">
                                Chart showing {{ analytics.user_growth?.length || 0 }} data points
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Institution Performance -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Institution Performance</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="institution in analytics.institution_performance" :key="institution.institution" class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">{{ institution.institution }}</div>
                                    <div class="text-sm text-gray-500">{{ institution.performance.graduate_count }} graduates</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium" :class="getEmploymentRateColor(institution.performance.employment_rate)">
                                        {{ Math.round(institution.performance.employment_rate) }}% employed
                                    </div>
                                    <div class="text-xs text-gray-500">{{ institution.performance.active_jobs }} active jobs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Trends -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Employment Trends</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="trend in analytics.employment_trends" :key="trend.employment_status" class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3" :class="getEmploymentStatusColor(trend.employment_status)"></div>
                                    <span class="text-sm font-medium text-gray-900 capitalize">{{ trend.employment_status.replace('_', ' ') }}</span>
                                </div>
                                <span class="text-sm text-gray-500">{{ trend.count }} graduates</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Market Analysis -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Job Market Analysis</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Jobs by Type -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Jobs by Type</h3>
                            <div class="space-y-2">
                                <div v-for="job in analytics.job_market_analysis.jobs_by_type" :key="job.job_type" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">{{ job.job_type || 'Other' }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ job.count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Jobs by Location -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Top Locations</h3>
                            <div class="space-y-2">
                                <div v-for="location in analytics.job_market_analysis.jobs_by_location" :key="location.location" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">{{ location.location }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ location.count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Salary Ranges -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Salary Ranges</h3>
                            <div class="space-y-2">
                                <div v-for="salary in analytics.job_market_analysis.salary_ranges" :key="salary.range" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">{{ salary.range }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ salary.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Usage -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">System Usage</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Daily Logins Chart -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Daily Active Users</h3>
                            <div class="h-32 flex items-center justify-center bg-gray-50 rounded-lg">
                                <div class="text-center">
                                    <ChartBarIcon class="mx-auto h-8 w-8 text-gray-400" />
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ analytics.system_usage?.daily_logins?.length || 0 }} data points
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Feature Usage -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Feature Usage</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Job Applications</span>
                                    <span class="text-sm font-medium text-gray-900">{{ analytics.system_usage?.feature_usage?.job_applications || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Profile Updates</span>
                                    <span class="text-sm font-medium text-gray-900">{{ analytics.system_usage?.feature_usage?.profile_updates || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Job Posts</span>
                                    <span class="text-sm font-medium text-gray-900">{{ analytics.system_usage?.feature_usage?.job_posts || 0 }}</span>
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
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ChartBarIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    analytics: Object,
    timeframe: String,
});

const selectedTimeframe = ref(props.timeframe);

const getEmploymentRateColor = (rate) => {
    if (rate >= 80) return 'text-green-600';
    if (rate >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getEmploymentStatusColor = (status) => {
    const colors = {
        'employed': 'bg-green-500',
        'self_employed': 'bg-blue-500',
        'unemployed': 'bg-red-500',
        'seeking_employment': 'bg-yellow-500',
        'further_study': 'bg-purple-500',
    };
    return colors[status] || 'bg-gray-500';
};

const updateTimeframe = () => {
    router.get(route('super-admin.analytics'), { timeframe: selectedTimeframe.value }, {
        preserveState: true,
        replace: true,
    });
};
</script>