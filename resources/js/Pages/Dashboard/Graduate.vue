<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    graduate: Object,
    statistics: Object,
    recentActivities: Object,
    jobRecommendations: Array,
    classmateConnections: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatPercentage = (value) => {
    return Math.round(value) + '%';
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'reviewed': 'bg-blue-100 text-blue-800',
        'shortlisted': 'bg-purple-100 text-purple-800',
        'interviewed': 'bg-indigo-100 text-indigo-800',
        'hired': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getEmploymentStatusClass = (status) => {
    const classes = {
        'employed': 'bg-green-100 text-green-800',
        'unemployed': 'bg-red-100 text-red-800',
        'self_employed': 'bg-blue-100 text-blue-800',
        'student': 'bg-purple-100 text-purple-800',
        'other': 'bg-gray-100 text-gray-800',
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
    { name: 'Browse Jobs', href: 'graduate.jobs', icon: 'search', color: 'bg-indigo-600 hover:bg-indigo-700' },
    { name: 'My Applications', href: 'graduate.applications', icon: 'document-text', color: 'bg-green-600 hover:bg-green-700' },
    { name: 'Update Profile', href: 'graduate.profile', icon: 'user', color: 'bg-purple-600 hover:bg-purple-700' },
    { name: 'Connect with Classmates', href: 'graduate.classmates', icon: 'users', color: 'bg-blue-600 hover:bg-blue-700' },
    { name: 'Career Progress', href: 'graduate.career', icon: 'chart', color: 'bg-yellow-600 hover:bg-yellow-700' },
    { name: 'Get Assistance', href: 'graduate.assistance', icon: 'support', color: 'bg-pink-600 hover:bg-pink-700' },
];

const applyToJob = (job) => {
    router.post(route('jobs.apply', job.id));
};
</script>

<template>
    <Head title="Graduate Dashboard" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Graduate Dashboard
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Welcome back, {{ graduate.user?.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getEmploymentStatusClass(statistics.employment_status)]">
                        {{ statistics.employment_status?.replace('_', ' ').toUpperCase() }}
                    </span>
                    <span class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                        {{ graduate.course?.name }}
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
                                   <Link :href="route('graduate.profile')" class="font-medium underline">
                                       Complete your profile
                                   </Link> to improve your job match opportunities.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link v-for="action in quickActions" :key="action.name" 
                          :href="route(action.href)"
                          :class="['group relative rounded-lg p-6 text-white transition-colors', action.color]">
                        <div>
                            <span class="rounded-lg inline-flex p-3 bg-white bg-opacity-20">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path v-if="action.icon === 'search'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    <path v-else-if="action.icon === 'document-text'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    <path v-else-if="action.icon === 'user'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    <path v-else-if="action.icon === 'users'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    <path v-else-if="action.icon === 'chart'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    <path v-else-if="action.icon === 'support'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z" />
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
                                    <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Applications</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ statistics.pending_applications }}</dd>
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Interview Invitations</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ statistics.interview_invitations }}</dd>
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Job Offers</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ statistics.job_offers }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Job Recommendations -->
                    <div class="lg:col-span-2 bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Recommended Jobs</h3>
                                <Link :href="route('graduate.jobs')" 
                                      class="text-sm text-indigo-600 hover:text-indigo-500">
                                    Browse all jobs
                                </Link>
                            </div>
                            <div v-if="jobRecommendations.length > 0" class="space-y-4">
                                <div v-for="job in jobRecommendations" 
                                     :key="job.id" 
                                     class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-medium text-gray-900">
                                                <Link :href="route('jobs.public.show', job.id)" class="hover:text-indigo-600">
                                                    {{ job.title }}
                                                </Link>
                                            </h4>
                                            <p class="text-sm text-gray-600">{{ job.employer?.company_name }}</p>
                                            <p class="text-sm text-gray-500 mt-1">{{ job.location || 'Location not specified' }}</p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                    {{ job.job_type?.replace('_', ' ').toUpperCase() }}
                                                </span>
                                                <span class="inline-flex px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                                    {{ job.experience_level?.toUpperCase() }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 ml-4">
                                            <button @click="applyToJob(job)"
                                                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded-md">
                                                Apply Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <p class="text-gray-500">No job recommendations available</p>
                                <Link :href="route('graduate.jobs')" 
                                      class="mt-2 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500">
                                    Browse available jobs →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Profile & Connections -->
                    <div class="space-y-6">
                        <!-- Profile Status -->
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
                                            <span class="text-gray-600">Skills Listed</span>
                                            <span class="font-medium text-gray-900">{{ statistics.skills_count }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm mt-2">
                                            <span class="text-gray-600">Graduation Year</span>
                                            <span class="font-medium text-gray-900">{{ statistics.course_completion_year }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <Link :href="route('graduate.profile')" 
                                          class="text-sm text-indigo-600 hover:text-indigo-500">
                                        Update profile →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Classmate Connections -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Classmates</h3>
                                    <Link :href="route('graduate.classmates')" 
                                          class="text-sm text-indigo-600 hover:text-indigo-500">
                                        View all
                                    </Link>
                                </div>
                                <div v-if="classmateConnections.length > 0" class="space-y-3">
                                    <div v-for="classmate in classmateConnections.slice(0, 4)" 
                                         :key="classmate.id" 
                                         class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700">
                                                    {{ classmate.user?.name?.charAt(0) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ classmate.user?.name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Class of {{ classmate.graduation_year }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4">
                                    <p class="text-sm text-gray-500">No classmates found</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activities</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Recent Applications -->
                            <div>
                                <h4 class="text-md font-medium text-gray-800 mb-3">Recent Applications</h4>
                                <div v-if="recentActivities.recent_applications.length > 0" class="space-y-3">
                                    <div v-for="application in recentActivities.recent_applications" 
                                         :key="application.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ application.job?.title }}</p>
                                            <p class="text-xs text-gray-500">{{ application.job?.employer?.company_name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(application.status)]">
                                                {{ application.status?.toUpperCase() }}
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">{{ formatDate(application.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4">
                                    <p class="text-sm text-gray-500">No recent applications</p>
                                </div>
                            </div>

                            <!-- Recent Job Matches -->
                            <div>
                                <h4 class="text-md font-medium text-gray-800 mb-3">New Job Matches</h4>
                                <div v-if="recentActivities.recent_job_matches.length > 0" class="space-y-3">
                                    <div v-for="job in recentActivities.recent_job_matches" 
                                         :key="job.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ job.title }}</p>
                                            <p class="text-xs text-gray-500">{{ job.employer?.company_name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <Link :href="route('jobs.public.show', job.id)" 
                                                  class="text-xs text-indigo-600 hover:text-indigo-500">
                                                View Job
                                            </Link>
                                            <p class="text-xs text-gray-500 mt-1">{{ formatDate(job.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4">
                                    <p class="text-sm text-gray-500">No new job matches</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>