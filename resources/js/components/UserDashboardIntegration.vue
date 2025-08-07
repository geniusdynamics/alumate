<template>
    <div class="user-dashboard-integration">
        <!-- Quick Actions Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="p-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <button
                        @click="createPost"
                        class="flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors"
                    >
                        <PlusIcon class="w-4 h-4" />
                        <span>Create Post</span>
                    </button>
                    
                    <button
                        @click="findAlumni"
                        class="flex items-center space-x-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors"
                    >
                        <UsersIcon class="w-4 h-4" />
                        <span>Find Alumni</span>
                    </button>
                    
                    <button
                        @click="browseJobs"
                        class="flex items-center space-x-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors"
                    >
                        <BriefcaseIcon class="w-4 h-4" />
                        <span>Browse Jobs</span>
                    </button>
                    
                    <button
                        @click="updateCareer"
                        class="flex items-center space-x-2 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md transition-colors"
                    >
                        <ChartBarIcon class="w-4 h-4" />
                        <span>Update Career</span>
                    </button>
                    
                    <button
                        @click="findEvents"
                        class="flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition-colors"
                    >
                        <CalendarIcon class="w-4 h-4" />
                        <span>Find Events</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Integrated Activity Feed -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Recent Activity -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="recentActivities.length === 0" class="text-center py-8">
                            <ClockIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No recent activity</h4>
                            <p class="text-gray-500 dark:text-gray-400">Start engaging with the platform to see your activity here</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div
                                v-for="activity in recentActivities"
                                :key="activity.id"
                                class="flex items-start space-x-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                        <component :is="getActivityIcon(activity.type)" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        {{ activity.description }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ formatTimeAgo(activity.created_at) }}
                                    </p>
                                    <div v-if="activity.actionable" class="mt-2">
                                        <button
                                            @click="handleActivityAction(activity)"
                                            class="text-xs text-blue-600 hover:text-blue-500 font-medium"
                                        >
                                            {{ activity.action_text }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendations Sidebar -->
            <div class="space-y-6">
                <!-- Connection Suggestions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">People You May Know</h4>
                    </div>
                    <div class="p-4">
                        <div v-if="connectionSuggestions.length === 0" class="text-center py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No suggestions available</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="suggestion in connectionSuggestions.slice(0, 3)"
                                :key="suggestion.id"
                                class="flex items-center space-x-3"
                            >
                                <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <UserIcon class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ suggestion.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ suggestion.current_position || 'Alumni' }}
                                    </p>
                                </div>
                                <button
                                    @click="connectWithUser(suggestion)"
                                    class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700"
                                >
                                    Connect
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Recommended Jobs</h4>
                    </div>
                    <div class="p-4">
                        <div v-if="jobRecommendations.length === 0" class="text-center py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No recommendations available</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="job in jobRecommendations.slice(0, 2)"
                                :key="job.id"
                                class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                            >
                                <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ job.title }}
                                </h5>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ job.employer.name }}
                                </p>
                                <div class="mt-2 flex space-x-2">
                                    <button
                                        @click="viewJob(job)"
                                        class="text-xs text-blue-600 hover:text-blue-500"
                                    >
                                        View
                                    </button>
                                    <button
                                        @click="saveJob(job)"
                                        class="text-xs text-gray-600 hover:text-gray-500"
                                    >
                                        Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Upcoming Events</h4>
                    </div>
                    <div class="p-4">
                        <div v-if="upcomingEvents.length === 0" class="text-center py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming events</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="event in upcomingEvents.slice(0, 2)"
                                :key="event.id"
                                class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                            >
                                <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ event.title }}
                                </h5>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(event.start_date) }}
                                </p>
                                <div class="mt-2">
                                    <button
                                        @click="viewEvent(event)"
                                        class="text-xs text-blue-600 hover:text-blue-500"
                                    >
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cross-feature Integration Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Career Progress Widget -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Career Progress</h4>
                        <ChartBarIcon class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Profile Completion</span>
                            <span class="font-medium">{{ careerProgress.profile_completion }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="bg-blue-600 h-2 rounded-full"
                                :style="{ width: careerProgress.profile_completion + '%' }"
                            ></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Network Size</span>
                            <span class="font-medium">{{ careerProgress.network_size }} connections</span>
                        </div>
                        <button
                            @click="updateCareer"
                            class="w-full text-sm bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700"
                        >
                            Update Career Timeline
                        </button>
                    </div>
                </div>
            </div>

            <!-- Network Insights Widget -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Network Insights</h4>
                        <UsersIcon class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="space-y-3">
                        <div class="text-sm">
                            <p class="text-gray-600 dark:text-gray-400">Most Connected Industry</p>
                            <p class="font-medium">{{ networkInsights.top_industry || 'Technology' }}</p>
                        </div>
                        <div class="text-sm">
                            <p class="text-gray-600 dark:text-gray-400">Mutual Connections</p>
                            <p class="font-medium">{{ networkInsights.mutual_connections || 0 }} potential introductions</p>
                        </div>
                        <button
                            @click="findAlumni"
                            class="w-full text-sm bg-green-600 text-white py-2 rounded-md hover:bg-green-700"
                        >
                            Expand Network
                        </button>
                    </div>
                </div>
            </div>

            <!-- Engagement Summary Widget -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Platform Engagement</h4>
                        <HeartIcon class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Posts This Month</span>
                            <span class="font-medium">{{ engagementSummary.posts_count || 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Connections Made</span>
                            <span class="font-medium">{{ engagementSummary.connections_count || 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Events Attended</span>
                            <span class="font-medium">{{ engagementSummary.events_count || 0 }}</span>
                        </div>
                        <button
                            @click="createPost"
                            class="w-full text-sm bg-purple-600 text-white py-2 rounded-md hover:bg-purple-700"
                        >
                            Share an Update
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <PostCreator
            v-if="showPostModal"
            @close="showPostModal = false"
            @post-created="handlePostCreated"
        />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import PostCreator from '@/components/PostCreator.vue'
import userFlowIntegration from '@/services/UserFlowIntegration'
import { formatDistanceToNow, format } from 'date-fns'
import {
    PlusIcon,
    UsersIcon,
    BriefcaseIcon,
    ChartBarIcon,
    CalendarIcon,
    ClockIcon,
    UserIcon,
    HeartIcon,
    ChatBubbleLeftIcon,
    UserPlusIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const showPostModal = ref(false)

// Reactive data
const recentActivities = reactive([])
const connectionSuggestions = reactive([])
const jobRecommendations = reactive([])
const upcomingEvents = reactive([])

const careerProgress = reactive({
    profile_completion: 0,
    network_size: 0
})

const networkInsights = reactive({
    top_industry: '',
    mutual_connections: 0
})

const engagementSummary = reactive({
    posts_count: 0,
    connections_count: 0,
    events_count: 0
})

onMounted(async () => {
    await loadDashboardData()
    
    // Set up user flow integration callbacks
    userFlowIntegration.on('postCreated', (post) => {
        addActivity({
            type: 'post_created',
            description: 'You shared a new post',
            created_at: new Date(),
            actionable: true,
            action_text: 'View Post'
        })
        engagementSummary.posts_count++
    })
    
    userFlowIntegration.on('connectionAccepted', (connection) => {
        addActivity({
            type: 'connection_accepted',
            description: `${connection.user.name} accepted your connection request`,
            created_at: new Date(),
            actionable: true,
            action_text: 'View Profile'
        })
        engagementSummary.connections_count++
        careerProgress.network_size++
    })
    
    userFlowIntegration.on('jobApplicationSubmitted', (application) => {
        addActivity({
            type: 'job_applied',
            description: `You applied for ${application.job.title}`,
            created_at: new Date(),
            actionable: true,
            action_text: 'View Application'
        })
    })
    
    userFlowIntegration.on('eventRegistered', (registration) => {
        addActivity({
            type: 'event_registered',
            description: `You registered for ${registration.event.title}`,
            created_at: new Date(),
            actionable: true,
            action_text: 'View Event'
        })
        engagementSummary.events_count++
    })
})

const loadDashboardData = async () => {
    try {
        // Load integrated dashboard data
        const response = await fetch('/api/dashboard/integrated-data', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const data = await response.json()
        
        if (data.success) {
            // Update reactive data
            recentActivities.splice(0, recentActivities.length, ...data.data.recent_activities)
            connectionSuggestions.splice(0, connectionSuggestions.length, ...data.data.connection_suggestions)
            jobRecommendations.splice(0, jobRecommendations.length, ...data.data.job_recommendations)
            upcomingEvents.splice(0, upcomingEvents.length, ...data.data.upcoming_events)
            
            Object.assign(careerProgress, data.data.career_progress)
            Object.assign(networkInsights, data.data.network_insights)
            Object.assign(engagementSummary, data.data.engagement_summary)
        }
    } catch (error) {
        console.error('Failed to load dashboard data:', error)
    }
}

const addActivity = (activity) => {
    const newActivity = {
        id: Date.now() + Math.random(),
        ...activity
    }
    
    recentActivities.unshift(newActivity)
    
    // Keep only last 10 activities
    if (recentActivities.length > 10) {
        recentActivities.splice(10)
    }
}

// Quick action handlers
const createPost = () => {
    showPostModal.value = true
}

const findAlumni = () => {
    router.visit('/alumni/directory')
}

const browseJobs = () => {
    router.visit('/jobs/dashboard')
}

const updateCareer = () => {
    router.visit('/career/timeline')
}

const findEvents = () => {
    router.visit('/events/discovery')
}

// Interaction handlers
const connectWithUser = async (user) => {
    try {
        await userFlowIntegration.sendConnectionRequestAndUpdate(user.id)
        
        // Remove from suggestions
        const index = connectionSuggestions.findIndex(s => s.id === user.id)
        if (index > -1) {
            connectionSuggestions.splice(index, 1)
        }
    } catch (error) {
        console.error('Failed to send connection request:', error)
    }
}

const viewJob = (job) => {
    router.visit(`/jobs/${job.id}`)
}

const saveJob = async (job) => {
    try {
        await userFlowIntegration.saveJobAndUpdate(job.id)
    } catch (error) {
        console.error('Failed to save job:', error)
    }
}

const viewEvent = (event) => {
    router.visit(`/events/${event.id}`)
}

const handleActivityAction = (activity) => {
    switch (activity.type) {
        case 'post_created':
            router.visit('/social/timeline')
            break
        case 'connection_accepted':
            router.visit('/alumni/connections')
            break
        case 'job_applied':
            router.visit('/graduate/applications')
            break
        case 'event_registered':
            router.visit('/events/my-events')
            break
        default:
            console.log('Unknown activity type:', activity.type)
    }
}

const handlePostCreated = (post) => {
    showPostModal.value = false
    userFlowIntegration.triggerCallback('postCreated', post)
}

// Utility functions
const getActivityIcon = (type) => {
    const icons = {
        post_created: ChatBubbleLeftIcon,
        connection_accepted: UserPlusIcon,
        job_applied: BriefcaseIcon,
        event_registered: CalendarIcon,
        mentorship_request: AcademicCapIcon
    }
    return icons[type] || ClockIcon
}

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM d, yyyy')
}
</script>

<style scoped>
.user-dashboard-integration {
    position: relative;
}
</style>