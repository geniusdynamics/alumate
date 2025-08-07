<template>
    <div class="real-time-updates">
        <!-- Live Activity Feed -->
        <div v-if="showActivityFeed" class="activity-feed">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Live Activity
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="activity in recentActivities"
                        :key="activity.id"
                        class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                    >
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <component :is="getActivityIcon(activity.type)" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white">
                                {{ activity.message }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatTimeAgo(activity.timestamp) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Post Updates -->
        <div v-if="showPostUpdates" class="post-updates">
            <div
                v-for="update in postUpdates"
                :key="update.id"
                class="post-update-notification"
                :class="getUpdateClass(update.type)"
            >
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <CheckCircleIcon class="w-4 h-4 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ update.message }}
                        </p>
                    </div>
                    <button
                        @click="dismissUpdate(update.id)"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Connection Status Indicators -->
        <div v-if="showConnectionStatus" class="connection-status">
            <div class="flex items-center space-x-2">
                <div
                    :class="connectionStatusClass"
                    class="w-3 h-3 rounded-full"
                ></div>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ connectionStatusText }}
                </span>
            </div>
        </div>

        <!-- Live Engagement Counters -->
        <div v-if="showEngagementCounters" class="engagement-counters">
            <div class="flex space-x-4">
                <div
                    v-for="counter in engagementCounters"
                    :key="counter.type"
                    class="flex items-center space-x-2"
                >
                    <component :is="getEngagementIcon(counter.type)" class="w-4 h-4 text-gray-500" />
                    <span
                        :class="{ 'animate-pulse text-blue-600': counter.isUpdating }"
                        class="text-sm font-medium"
                    >
                        {{ counter.count }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Event Registration Updates -->
        <div v-if="showEventUpdates" class="event-updates">
            <div
                v-for="eventUpdate in eventUpdates"
                :key="eventUpdate.id"
                class="event-update-notification bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4"
            >
                <div class="flex items-start space-x-3">
                    <CalendarIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">
                            {{ eventUpdate.title }}
                        </h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                            {{ eventUpdate.message }}
                        </p>
                        <div class="mt-2 flex space-x-2">
                            <button
                                v-if="eventUpdate.actionable"
                                @click="handleEventAction(eventUpdate)"
                                class="text-xs bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700"
                            >
                                {{ eventUpdate.actionText }}
                            </button>
                            <button
                                @click="dismissEventUpdate(eventUpdate.id)"
                                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Application Status Updates -->
        <div v-if="showJobUpdates" class="job-updates">
            <div
                v-for="jobUpdate in jobUpdates"
                :key="jobUpdate.id"
                class="job-update-notification bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4"
            >
                <div class="flex items-start space-x-3">
                    <BriefcaseIcon class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" />
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-green-900 dark:text-green-100">
                            {{ jobUpdate.title }}
                        </h4>
                        <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                            {{ jobUpdate.message }}
                        </p>
                        <div class="mt-2 flex space-x-2">
                            <button
                                v-if="jobUpdate.actionable"
                                @click="handleJobAction(jobUpdate)"
                                class="text-xs bg-green-600 text-white px-3 py-1 rounded-md hover:bg-green-700"
                            >
                                {{ jobUpdate.actionText }}
                            </button>
                            <button
                                @click="dismissJobUpdate(jobUpdate.id)"
                                class="text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates'
import userFlowIntegration from '@/services/UserFlowIntegration'
import { formatDistanceToNow } from 'date-fns'
import {
    CheckCircleIcon,
    XMarkIcon,
    CalendarIcon,
    BriefcaseIcon,
    ChatBubbleLeftIcon,
    HeartIcon,
    ShareIcon,
    UserPlusIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    showActivityFeed: {
        type: Boolean,
        default: true
    },
    showPostUpdates: {
        type: Boolean,
        default: true
    },
    showConnectionStatus: {
        type: Boolean,
        default: true
    },
    showEngagementCounters: {
        type: Boolean,
        default: true
    },
    showEventUpdates: {
        type: Boolean,
        default: true
    },
    showJobUpdates: {
        type: Boolean,
        default: true
    }
})

const realTimeUpdates = useRealTimeUpdates()

// Reactive data
const recentActivities = reactive([])
const postUpdates = reactive([])
const eventUpdates = reactive([])
const jobUpdates = reactive([])
const engagementCounters = reactive([
    { type: 'likes', count: 0, isUpdating: false },
    { type: 'comments', count: 0, isUpdating: false },
    { type: 'shares', count: 0, isUpdating: false }
])

const connectionStatus = ref('connected')
const isRealTimeConnected = ref(false)

// Computed properties
const connectionStatusClass = computed(() => {
    return {
        'bg-green-500': connectionStatus.value === 'connected',
        'bg-yellow-500': connectionStatus.value === 'connecting',
        'bg-red-500': connectionStatus.value === 'disconnected'
    }
})

const connectionStatusText = computed(() => {
    const statuses = {
        connected: 'Live updates active',
        connecting: 'Connecting...',
        disconnected: 'Connection lost'
    }
    return statuses[connectionStatus.value] || 'Unknown status'
})

onMounted(() => {
    // Set up real-time event listeners
    realTimeUpdates.onPostCreated((post) => {
        addActivity({
            type: 'post_created',
            message: `${post.user.name} shared a new post`,
            timestamp: new Date(),
            data: post
        })
        
        addPostUpdate({
            type: 'new_post',
            message: 'New post from your network',
            post: post
        })
    })
    
    realTimeUpdates.onPostUpdated((post) => {
        addPostUpdate({
            type: 'post_updated',
            message: 'A post you interacted with was updated',
            post: post
        })
    })
    
    realTimeUpdates.onPostEngagement((postId, engagement) => {
        updateEngagementCounters(engagement)
        
        addActivity({
            type: 'post_engagement',
            message: `Someone ${engagement.type} your post`,
            timestamp: new Date(),
            data: { postId, engagement }
        })
    })
    
    realTimeUpdates.onCommentAdded((postId, comment) => {
        addActivity({
            type: 'comment_added',
            message: `${comment.user.name} commented on a post`,
            timestamp: new Date(),
            data: { postId, comment }
        })
        
        // Update comment counter
        const commentCounter = engagementCounters.find(c => c.type === 'comments')
        if (commentCounter) {
            commentCounter.count++
            commentCounter.isUpdating = true
            setTimeout(() => {
                commentCounter.isUpdating = false
            }, 1000)
        }
    })
    
    realTimeUpdates.onConnectionRequest((connection) => {
        addActivity({
            type: 'connection_request',
            message: `${connection.user.name} sent you a connection request`,
            timestamp: new Date(),
            data: connection
        })
    })
    
    realTimeUpdates.onMentorshipRequest((request) => {
        addActivity({
            type: 'mentorship_request',
            message: `${request.mentee.name} requested mentorship`,
            timestamp: new Date(),
            data: request
        })
    })
    
    realTimeUpdates.onEventUpdate((event) => {
        addEventUpdate({
            title: 'Event Update',
            message: `"${event.title}" has been updated`,
            actionable: true,
            actionText: 'View Event',
            data: event
        })
    })
    
    // Set up user flow integration callbacks
    userFlowIntegration.on('jobApplicationSubmitted', (application) => {
        addJobUpdate({
            title: 'Application Submitted',
            message: `Your application for "${application.job.title}" has been submitted`,
            actionable: true,
            actionText: 'View Application',
            data: application
        })
    })
    
    userFlowIntegration.on('jobSaved', (jobId) => {
        addJobUpdate({
            title: 'Job Saved',
            message: 'Job has been added to your saved jobs',
            actionable: true,
            actionText: 'View Saved Jobs',
            data: { jobId }
        })
    })
    
    userFlowIntegration.on('eventRegistered', (registration) => {
        addEventUpdate({
            title: 'Event Registration Confirmed',
            message: `You're registered for "${registration.event.title}"`,
            actionable: true,
            actionText: 'View Event',
            data: registration
        })
    })
    
    // Monitor connection status
    if (realTimeUpdates.isConnected) {
        connectionStatus.value = 'connected'
        isRealTimeConnected.value = true
    } else {
        connectionStatus.value = 'connecting'
        // Try to reconnect
        setTimeout(() => {
            if (!realTimeUpdates.isConnected.value) {
                connectionStatus.value = 'disconnected'
            }
        }, 5000)
    }
})

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

const addPostUpdate = (update) => {
    const newUpdate = {
        id: Date.now() + Math.random(),
        timestamp: new Date(),
        ...update
    }
    
    postUpdates.unshift(newUpdate)
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        dismissUpdate(newUpdate.id)
    }, 10000)
}

const addEventUpdate = (update) => {
    const newUpdate = {
        id: Date.now() + Math.random(),
        timestamp: new Date(),
        ...update
    }
    
    eventUpdates.unshift(newUpdate)
}

const addJobUpdate = (update) => {
    const newUpdate = {
        id: Date.now() + Math.random(),
        timestamp: new Date(),
        ...update
    }
    
    jobUpdates.unshift(newUpdate)
}

const updateEngagementCounters = (engagement) => {
    const counter = engagementCounters.find(c => c.type === engagement.type + 's')
    if (counter) {
        counter.count = engagement.count
        counter.isUpdating = true
        setTimeout(() => {
            counter.isUpdating = false
        }, 1000)
    }
}

const dismissUpdate = (id) => {
    const index = postUpdates.findIndex(u => u.id === id)
    if (index > -1) {
        postUpdates.splice(index, 1)
    }
}

const dismissEventUpdate = (id) => {
    const index = eventUpdates.findIndex(u => u.id === id)
    if (index > -1) {
        eventUpdates.splice(index, 1)
    }
}

const dismissJobUpdate = (id) => {
    const index = jobUpdates.findIndex(u => u.id === id)
    if (index > -1) {
        jobUpdates.splice(index, 1)
    }
}

const handleEventAction = (eventUpdate) => {
    if (eventUpdate.data && eventUpdate.data.id) {
        window.location.href = `/events/${eventUpdate.data.id}`
    }
}

const handleJobAction = (jobUpdate) => {
    if (jobUpdate.actionText === 'View Saved Jobs') {
        window.location.href = '/jobs/saved'
    } else if (jobUpdate.actionText === 'View Application') {
        window.location.href = '/graduate/applications'
    }
}

const getActivityIcon = (type) => {
    const icons = {
        post_created: ChatBubbleLeftIcon,
        post_engagement: HeartIcon,
        comment_added: ChatBubbleLeftIcon,
        connection_request: UserPlusIcon,
        mentorship_request: AcademicCapIcon
    }
    return icons[type] || ChatBubbleLeftIcon
}

const getEngagementIcon = (type) => {
    const icons = {
        likes: HeartIcon,
        comments: ChatBubbleLeftIcon,
        shares: ShareIcon
    }
    return icons[type] || HeartIcon
}

const getUpdateClass = (type) => {
    const classes = {
        new_post: 'bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700',
        post_updated: 'bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700'
    }
    return classes[type] || 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700'
}

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}
</script>

<style scoped>
.real-time-updates {
    position: relative;
}

.post-update-notification,
.event-update-notification,
.job-update-notification {
    margin-bottom: 0.75rem;
    padding: 1rem;
    border-radius: 0.5rem;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.connection-status {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 40;
}

.dark .connection-status {
    background: #1f2937;
}
</style>