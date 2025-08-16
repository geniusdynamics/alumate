<template>
    <div class="card-mobile bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">Recent Activity</h3>
            <ChatBubbleLeftRightIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
        </div>
        
        <div class="space-y-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="flex items-start space-x-3">
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Activity Items -->
            <div v-else-if="activities.length > 0" class="space-y-4">
                <div 
                    v-for="activity in activities" 
                    :key="activity.id"
                    class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                    @click="viewActivity(activity)"
                >
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                            <component 
                                :is="getActivityIcon(activity.type)" 
                                class="h-4 w-4 text-blue-600 dark:text-blue-400" 
                            />
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-medium">{{ activity.user_name }}</span>
                            {{ getActivityText(activity.type) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ formatTimeAgo(activity.created_at) }}
                        </p>
                        <p v-if="activity.content" class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">
                            {{ activity.content }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-else class="text-center py-6">
                <ChatBubbleLeftRightIcon class="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Connect with alumni to see their updates here
                </p>
            </div>
        </div>
        
        <!-- View All Link -->
        <div v-if="activities.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Link 
                :href="route('social.timeline')"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center justify-center space-x-1"
            >
                <span>View Timeline</span>
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
    ChatBubbleLeftRightIcon,
    HeartIcon,
    ChatBubbleOvalLeftIcon,
    ShareIcon,
    UserPlusIcon,
    BriefcaseIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const loading = ref(true)
const activities = ref([])

const props = defineProps({
    limit: {
        type: Number,
        default: 5
    }
})

onMounted(async () => {
    await fetchActivities()
})

const fetchActivities = async () => {
    try {
        loading.value = true
        const response = await fetch(`/api/dashboard/social-activity?limit=${props.limit}`)
        const data = await response.json()
        activities.value = data.activities || []
    } catch (error) {
        console.error('Failed to fetch social activities:', error)
        activities.value = []
    } finally {
        loading.value = false
    }
}

const getActivityIcon = (type) => {
    const icons = {
        'post_created': ChatBubbleOvalLeftIcon,
        'post_liked': HeartIcon,
        'post_commented': ChatBubbleOvalLeftIcon,
        'post_shared': ShareIcon,
        'connection_made': UserPlusIcon,
        'career_updated': BriefcaseIcon
    }
    return icons[type] || ChatBubbleOvalLeftIcon
}

const getActivityText = (type) => {
    const texts = {
        'post_created': 'shared a post',
        'post_liked': 'liked a post',
        'post_commented': 'commented on a post',
        'post_shared': 'shared a post',
        'connection_made': 'connected with someone',
        'career_updated': 'updated their career'
    }
    return texts[type] || 'had some activity'
}

const formatTimeAgo = (timestamp) => {
    const now = new Date()
    const time = new Date(timestamp)
    const diffInSeconds = Math.floor((now - time) / 1000)
    
    if (diffInSeconds < 60) return 'just now'
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
    return `${Math.floor(diffInSeconds / 86400)}d ago`
}

const viewActivity = (activity) => {
    // Navigate to the specific activity or post
    if (activity.post_id) {
        window.location.href = `/social/timeline?post=${activity.post_id}`
    }
}
</script>