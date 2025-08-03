<template>
    <AppLayout title="Job Dashboard">
        <Head title="Job Dashboard" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Job Dashboard</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Your personalized job matching and application center</p>
            </div>

            <!-- Application Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <BriefcaseIcon class="h-8 w-8 text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Applications</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.total_applications }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ClockIcon class="h-8 w-8 text-yellow-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.pending_applications }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ChatBubbleLeftRightIcon class="h-8 w-8 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Interviews</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.interview_invitations }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CheckCircleIcon class="h-8 w-8 text-purple-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Offers</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.job_offers }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Job Recommendations -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recommended Jobs</h2>
                                <Link 
                                    :href="route('jobs.recommendations')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    View All
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="recommendations.length === 0" class="text-center py-8">
                                <BriefcaseIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No recommendations yet</h3>
                                <p class="text-gray-500 dark:text-gray-400">Complete your profile to get personalized job recommendations</p>
                            </div>
                            <div v-else class="space-y-4">
                                <JobCard
                                    v-for="job in recommendations"
                                    :key="job.id"
                                    :job="job"
                                    :show-match-score="true"
                                    @applied="handleJobApplied"
                                    @saved="handleJobSaved"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Recent Applications -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Applications</h2>
                        </div>
                        <div class="p-6">
                            <div v-if="applications.length === 0" class="text-center py-8">
                                <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No applications yet</h3>
                                <p class="text-gray-500 dark:text-gray-400">Start applying to jobs to track your progress here</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div 
                                    v-for="application in applications" 
                                    :key="application.id"
                                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ application.job.title }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ application.job.employer.name }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Applied {{ formatTimeAgo(application.created_at) }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span 
                                            :class="getStatusColor(application.status)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{ formatStatus(application.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Job Insights -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Job Insights</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="insights.length === 0" class="text-center py-4">
                                <p class="text-gray-500 dark:text-gray-400">No insights available</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div 
                                    v-for="insight in insights" 
                                    :key="insight.type"
                                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                        {{ insight.title }}
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ insight.message }}
                                    </p>
                                    <button class="text-sm text-blue-600 hover:text-blue-500 font-medium">
                                        {{ insight.action }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Saved Jobs -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Saved Jobs</h3>
                                <Link 
                                    :href="route('jobs.saved')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    View All
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="savedJobs.length === 0" class="text-center py-4">
                                <BookmarkIcon class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">No saved jobs yet</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="job in savedJobs" 
                                    :key="job.id"
                                    class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ job.title }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ job.employer.name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <Link 
                                :href="route('jobs.index')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="w-5 h-5" />
                                <span>Browse All Jobs</span>
                            </Link>
                            <Link 
                                :href="route('career.timeline')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <ChartBarIcon class="w-5 h-5" />
                                <span>Career Timeline</span>
                            </Link>
                            <Link 
                                :href="route('career.mentorship')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <UserGroupIcon class="w-5 h-5" />
                                <span>Find Mentors</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import JobCard from '@/Components/JobCard.vue'
import { formatDistanceToNow } from 'date-fns'
import {
    BriefcaseIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    DocumentTextIcon,
    BookmarkIcon,
    MagnifyingGlassIcon,
    ChartBarIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    recommendations: Array,
    applications: Array,
    savedJobs: Array,
    insights: Array,
    applicationStats: Object,
})

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        interview: 'bg-blue-100 text-blue-800',
        offer: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
    const statuses = {
        pending: 'Pending',
        interview: 'Interview',
        offer: 'Offer',
        rejected: 'Rejected',
    }
    return statuses[status] || status
}

const handleJobApplied = (jobId) => {
    // Handle job application
    console.log('Applied to job:', jobId)
}

const handleJobSaved = (jobId) => {
    // Handle job saving
    console.log('Saved job:', jobId)
}
</script>
