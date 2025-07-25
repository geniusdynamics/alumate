<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    employer: Object,
    statistics: Object,
    recentActivities: Object,
    jobMetrics: Object,
    hiringAnalytics: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatPercentage = (value) => {
    return Math.round(value) + '%';
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'pending_approval': 'bg-yellow-100 text-yellow-800',
        'paused': 'bg-gray-100 text-gray-800',
        'filled': 'bg-blue-100 text-blue-800',
        'expired': 'bg-red-100 text-red-800',
        'cancelled': 'bg-red-100 text-red-800',
        'draft': 'bg-gray-100 text-gray-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'reviewed': 'bg-blue-100 text-blue-800',
        'shortlisted': 'bg-purple-100 text-purple-800',
        'interviewed': 'bg-indigo-100 text-indigo-800',
        'hired': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getVerificationStatusClass = (status) => {
    const classes = {
        'verified': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'under_review': 'bg-blue-100 text-blue-800',
        'rejected': 'bg-red-100 text-red-800',
        'suspended': 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const profileCompletionColor = computed(() => {
    const completion = props.statistics.profile_completion;
    if (completion >= 80) return 'text-green-600';
    if (completion >= 60) return 'text-yellow-600';
    return 'text-red-600';
});

const quickActions = [
    { name: 'Post New Job', href: 'jobs.create', icon: 'plus', color: 'bg-indigo-600 hover:bg-indigo-700' },
    { name: 'View Applications', href: 'employer.applications', icon: 'document-text', color: 'bg-green-600 hover:bg-green-700' },
    { name: 'Search Graduates', href: 'employer.graduates.search', icon: 'search', color: 'bg-purple-600 hover:bg-purple-700' },
    { name: 'Communications', href: 'employer.communications', icon: 'chat', color: 'bg-yellow-600 hover:bg-yellow-700' },
    { name: 'Company Profile', href: 'employer.profile', icon: 'building-office', color: 'bg-blue-600 hover:bg-blue-700' },
    { name: 'Analytics', href: 'employer.analytics', icon: 'chart', color: 'bg-pink-600 hover:bg-pink-700' },
];
</script>

<template>
    <Head title="Employer Dashboard" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Employer Dashboard
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Welcome back, {{ employer.company_name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getVerificationStatusClass(employer.verification_status)]">
                        {{ employer.verification_status?.replace('_', ' ').toUpperCase() }}
                    </span>
                    <span class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                        {{ employer.subscription_plan?.toUpperCase() }} Plan
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Profile Completion Alert -->
                <div v-if="statistics.profile_completion < 100" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Complete Your Profile
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Your profile is {{ formatPercentage(statistics.profile_completion) }} complete. 
                                   <Link :href="route('employer.profile')" class="font-medium underline">
                                       Complete your profile
                                   </Link> to improve your visibility to candidates.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link v-for="action in quickActions" :key="action.name" 
                          :href="route(action.href)"
                          :class="['group relative rounded-lg p-6 text-white transition-colors', action.color]">
                        <div>
                            <span class="rounded-lg inline-flex p-3 bg-white bg-opacity-20">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path v-if="action.icon === 'plus'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    <path v-else-if="action.icon === 'document-text'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    <path v-else-if="action.icon === 'search'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    <path v-else-if="action.icon === 'chat'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    <path v-else-if="action.icon === 'building-office'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    <path v-else-if="action.icon === 'chart'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-8">
                            <h3 class="text-base font-medium">{{ action.name }}</h3>
                        </div>
                    </Link>
                </div>

                <!-- Key Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
                                        <dt class="text-sm font-medium text-gray-500 truncate">Active Jobs</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ statistics.active_jobs }}</dd>
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
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Applications</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ statistics.total_applications }}</dd>
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Shortlisted</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ statistics.shortlisted_candidates }}</dd>
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Hires</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ statistics.total_hires }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Applications -->
                    <div class="lg:col-span-2 bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Applications</h3>
                                <Link :href="route('employer.applications')" 
                                      class="text-sm text-indigo-600 hover:text-indigo-500">
                                    View all
                                </Link>
                            </div>
                            <div v-if="recentActivities.recent_applications.length > 0" class="space-y-3">
                                <div v-for="application in recentActivities.recent_applications" 
                                     :key="application.id" 
                                     class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ application.graduate?.user?.name }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ application.job?.title }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(application.status)]">
                                                    {{ application.status?.toUpperCase() }}
                                                </span>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ formatDate(application.created_at) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <p class="text-gray-500">No recent applications</p>
                            </div>
                        </div>
                    </div>

                    <!-- Account Overview -->
                    <div class="space-y-6">
                        <!-- Profile Completion -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Profile Status</h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Profile Completion</span>
                                            <span :class="['font-medium', profileCompletionColor]">
                                                {{ formatPercentage(statistics.profile_completion) }}
                                            </span>
                                        </div>
                                        <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                            <div :class="['h-2 rounded-full', statistics.profile_completion >= 80 ? 'bg-green-500' : statistics.profile_completion >= 60 ? 'bg-yellow-500' : 'bg-red-500']" 
                                                 :style="`width: ${statistics.profile_completion}%`"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t pt-4">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Job Posts Remaining</span>
                                            <span class="font-medium text-gray-900">{{ statistics.remaining_job_posts }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm mt-2">
                                            <span class="text-gray-600">Subscription</span>
                                            <span class="font-medium text-gray-900">{{ statistics.subscription_plan?.toUpperCase() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hiring Analytics -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Hiring Analytics</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Hire Rate</span>
                                        <span class="font-medium text-gray-900">{{ formatPercentage(hiringAnalytics.hire_rate) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Avg. Time to Hire</span>
                                        <span class="font-medium text-gray-900">{{ hiringAnalytics.average_time_to_hire }} days</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total Hires</span>
                                        <span class="font-medium text-gray-900">{{ hiringAnalytics.total_hires }}</span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <Link :href="route('employer.analytics')" 
                                          class="text-sm text-indigo-600 hover:text-indigo-500">
                                        View detailed analytics →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Jobs -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Job Posts</h3>
                            <Link :href="route('jobs.index')" 
                                  class="text-sm text-indigo-600 hover:text-indigo-500">
                                Manage all jobs
                            </Link>
                        </div>
                        <div v-if="recentActivities.recent_jobs.length > 0" class="space-y-3">
                            <div v-for="job in recentActivities.recent_jobs" 
                                 :key="job.id" 
                                 class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ job.title }}</p>
                                    <p class="text-xs text-gray-500">Posted {{ formatDate(job.created_at) }}</p>
                                </div>
                                <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(job.status)]">
                                    {{ job.status?.replace('_', ' ').toUpperCase() }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-gray-500">No jobs posted yet</p>
                            <Link :href="route('jobs.create')" 
                                  class="mt-2 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500">
                                Post your first job →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
