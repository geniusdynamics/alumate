<template>
    <AppLayout title="Job Dashboard">
        <Head title="Job Dashboard" />

        <!-- Mobile Hamburger Menu -->
        <MobileHamburgerMenu class="lg:hidden" />
        
        <!-- Pull to Refresh -->
        <PullToRefresh @refresh="refreshDashboard" class="min-h-screen theme-bg-secondary">
            <!-- Mobile Header -->
            <div class="lg:hidden bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 safe-area-top">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Job Dashboard</h1>
                    <div class="flex items-center space-x-2">
                        <ThemeToggle variant="simple" />
                        <button class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 touch-target">
                            <MagnifyingGlassIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="mobile-container lg:max-w-7xl lg:mx-auto lg:py-6 lg:px-4 lg:sm:px-6 lg:lg:px-8">
                <!-- Desktop Header -->
                <div class="hidden lg:block mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Job Dashboard</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Your personalized job matching and application center</p>
                </div>

                <!-- Application Statistics -->
                <div class="mobile-grid sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <div class="card-mobile lg:bg-white lg:dark:bg-gray-800 lg:rounded-lg lg:shadow lg:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <BriefcaseIcon class="h-6 w-6 lg:h-8 lg:w-8 text-blue-600" />
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500 dark:text-gray-400">Total Applications</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.total_applications }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-mobile lg:bg-white lg:dark:bg-gray-800 lg:rounded-lg lg:shadow lg:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <ClockIcon class="h-6 w-6 lg:h-8 lg:w-8 text-yellow-600" />
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.pending_applications }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-mobile lg:bg-white lg:dark:bg-gray-800 lg:rounded-lg lg:shadow lg:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <ChatBubbleLeftRightIcon class="h-6 w-6 lg:h-8 lg:w-8 text-green-600" />
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500 dark:text-gray-400">Interviews</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.interview_invitations }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-mobile lg:bg-white lg:dark:bg-gray-800 lg:rounded-lg lg:shadow lg:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <CheckCircleIcon class="h-6 w-6 lg:h-8 lg:w-8 text-purple-600" />
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500 dark:text-gray-400">Offers</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900 dark:text-white">{{ applicationStats.job_offers }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="space-y-6 lg:space-y-8">
                    <!-- Job Recommendations -->
                    <div class="card-mobile lg:bg-white lg:dark:bg-gray-800 lg:rounded-lg lg:shadow">
                        <div class="card-mobile-header lg:px-6 lg:py-4 lg:border-b lg:border-gray-200 lg:dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="card-mobile-title lg:text-lg lg:font-medium">Recommended Jobs</h2>
                                <Link 
                                    :href="route('jobs.recommendations')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    View All
                                </Link>
                            </div>
                        </div>
                        <div class="lg:p-6">
                            <!-- Loading State -->
                            <SmartLoader 
                                v-if="recommendationsLoading.isLoading.value"
                                :loading="true"
                                context="jobs"
                                skeleton-variant="list"
                                list-variant="detailed"
                                :skeleton-count="3"
                                :show-actions="true"
                                :action-count="2"
                                :show-secondary="true"
                                :show-tertiary="false"
                            />
                            
                            <!-- Empty State -->
                            <div v-else-if="recommendations.length === 0" class="text-center py-8">
                                <BriefcaseIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No recommendations yet</h3>
                                <p class="text-gray-500 dark:text-gray-400">Complete your profile to get personalized job recommendations</p>
                            </div>
                            
                            <!-- Job Recommendations -->
                            <div v-else class="space-y-4">
                                <div v-for="job in jobRecommendations" :key="job.id" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ job.title }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ job.company }}</p>
                                    <div class="mt-2 flex space-x-2">
                                        <button 
                                            @click="startApplication(job)" 
                                            class="btn-mobile-primary text-sm"
                                            :disabled="applicationLoading.isLoading.value"
                                        >
                                            {{ applicationLoading.isLoading.value ? 'Applying...' : 'Apply' }}
                                        </button>
                                        <button @click="handleJobSaved(job.id)" class="btn-mobile-secondary text-sm">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Flow Integration -->
            <UserFlowIntegration />
            
            <!-- Real-time Updates -->
            <RealTimeUpdates 
                :show-job-updates="true"
                :show-activity-feed="true"
            />
            
            <!-- Cross-feature Connections -->
            <CrossFeatureConnections 
                context="job-dashboard"
                :context-data="{ recommendations: jobRecommendations, applications: userApplications }"
            />
        </PullToRefresh>
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import MobileHamburgerMenu from '@/components/MobileHamburgerMenu.vue'
import PullToRefresh from '@/components/PullToRefresh.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import UserFlowIntegration from '@/components/UserFlowIntegration.vue'
import RealTimeUpdates from '@/components/RealTimeUpdates.vue'
import CrossFeatureConnections from '@/components/CrossFeatureConnections.vue'
import SmartLoader from '@/components/ui/SmartLoader.vue'
import SkeletonCard from '@/components/ui/SkeletonCard.vue'
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates'
import { useSpecificLoading, LoadingPresets } from '@/composables/useLoadingStates'
import userFlowIntegration from '@/services/UserFlowIntegration'
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

const showJobModal = ref(false)
const showApplicationModal = ref(false)
const showIntroductionModal = ref(false)
const selectedJob = ref(null)
const jobRecommendations = reactive([...props.recommendations])
const userApplications = reactive([...props.applications])
const userSavedJobs = reactive([...props.savedJobs])

// Loading states
const dashboardLoading = useSpecificLoading('dashboard', 'fetchingJobs')
const recommendationsLoading = useSpecificLoading('recommendations', 'fetchingJobs')
const applicationLoading = useSpecificLoading('application')

// Real-time updates
const realTimeUpdates = useRealTimeUpdates()

// Refresh dashboard data
const refreshDashboard = async () => {
    await dashboardLoading.withLoading(async () => {
        // Simulate refresh delay
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        // In a real app, you would reload data here
        window.location.reload()
    }, LoadingPresets.fetchingJobs)
}

onMounted(() => {
    // Set up user flow integration callbacks
    userFlowIntegration.on('jobSaved', (jobId) => {
        // Update saved jobs list
        const job = jobRecommendations.find(j => j.id === jobId)
        if (job && !userSavedJobs.find(sj => sj.id === jobId)) {
            userSavedJobs.unshift(job)
        }
    })
    
    userFlowIntegration.on('jobApplicationSubmitted', (application) => {
        // Add to applications list
        userApplications.unshift(application)
        
        // Update application stats
        props.applicationStats.total_applications++
        props.applicationStats.pending_applications++
    })
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

const handleJobClicked = (job) => {
    selectedJob.value = job
    showJobModal.value = true
}

const handleJobApplied = async (job, applicationData) => {
    await applicationLoading.withLoading(async () => {
        await userFlowIntegration.applyToJobAndTrack(job.id, applicationData)
        showApplicationModal.value = false
        selectedJob.value = null
    }, {
        type: 'contextual',
        context: 'submitting',
        message: 'Submitting your job application...'
    })
}

const handleJobSaved = async (jobId) => {
    try {
        await userFlowIntegration.saveJobAndUpdate(jobId)
    } catch (error) {
        console.error('Failed to save job:', error)
    }
}

const handleIntroductionRequest = (job) => {
    selectedJob.value = job
    showIntroductionModal.value = true
}

const sendIntroductionRequest = async (connectionId, message) => {
    try {
        const response = await fetch('/api/introductions/request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                connection_id: connectionId,
                job_id: selectedJob.value.id,
                message: message
            })
        })
        
        const result = await response.json()
        
        if (result.success) {
            userFlowIntegration.showNotification('Introduction request sent!', 'success')
            showIntroductionModal.value = false
            selectedJob.value = null
        } else {
            throw new Error(result.message || 'Failed to send introduction request')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to send introduction request: ' + error.message, 'error')
    }
}

const startApplication = (job) => {
    selectedJob.value = job
    showApplicationModal.value = true
}

const closeModals = () => {
    showJobModal.value = false
    showApplicationModal.value = false
    showIntroductionModal.value = false
    selectedJob.value = null
}
</script>