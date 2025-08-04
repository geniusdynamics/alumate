<template>
    <div class="active-mentorship-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Mentorship Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center">
                        <UserGroupIcon class="w-6 h-6 text-white" />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ mentorship.title || 'Mentorship Session' }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">with {{ mentorship.mentor_name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span 
                    :class="getStatusClass(mentorship.status)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                    {{ formatStatus(mentorship.status) }}
                </span>
            </div>
        </div>

        <!-- Mentor Info -->
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <UserIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ mentorship.mentor_name }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ mentorship.mentor_position }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ mentorship.mentor_company }}</p>
                </div>
            </div>
        </div>

        <!-- Session Details -->
        <div class="space-y-3 mb-4">
            <!-- Next Session -->
            <div v-if="mentorship.next_session" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <CalendarIcon class="w-4 h-4" />
                <span>Next session: {{ formatDate(mentorship.next_session.scheduled_at) }}</span>
            </div>

            <!-- Duration -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <ClockIcon class="w-4 h-4" />
                <span>{{ mentorship.duration || 60 }} minutes</span>
            </div>

            <!-- Focus Areas -->
            <div v-if="mentorship.focus_areas && mentorship.focus_areas.length > 0" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <TagIcon class="w-4 h-4" />
                <span>{{ mentorship.focus_areas.slice(0, 2).join(', ') }}</span>
                <span v-if="mentorship.focus_areas.length > 2">+{{ mentorship.focus_areas.length - 2 }} more</span>
            </div>

            <!-- Meeting Type -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <VideoCameraIcon v-if="mentorship.meeting_type === 'virtual'" class="w-4 h-4" />
                <MapPinIcon v-else class="w-4 h-4" />
                <span>{{ mentorship.meeting_type === 'virtual' ? 'Virtual Meeting' : 'In-Person' }}</span>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ mentorship.sessions_completed || 0 }}/{{ mentorship.total_sessions || 10 }} sessions</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                <div 
                    class="bg-green-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: getProgressPercentage() + '%' }"
                ></div>
            </div>
        </div>

        <!-- Goals & Objectives -->
        <div v-if="mentorship.goals && mentorship.goals.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Current Goals</h4>
            <ul class="space-y-1">
                <li 
                    v-for="goal in mentorship.goals.slice(0, 3)"
                    :key="goal.id"
                    class="flex items-start text-sm text-gray-600 dark:text-gray-400"
                >
                    <CheckCircleIcon v-if="goal.completed" class="w-4 h-4 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                    <ClockIcon v-else class="w-4 h-4 text-yellow-500 mr-2 flex-shrink-0 mt-0.5" />
                    <span :class="goal.completed ? 'line-through' : ''">{{ goal.description }}</span>
                </li>
                <li v-if="mentorship.goals.length > 3" class="text-sm text-gray-500 dark:text-gray-400">
                    +{{ mentorship.goals.length - 3 }} more goals
                </li>
            </ul>
        </div>

        <!-- Recent Activity -->
        <div v-if="mentorship.recent_activity" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Recent Activity</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ mentorship.recent_activity.description }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(mentorship.recent_activity.date) }}</p>
        </div>

        <!-- Upcoming Tasks -->
        <div v-if="mentorship.upcoming_tasks && mentorship.upcoming_tasks.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Upcoming Tasks</h4>
            <div class="space-y-2">
                <div 
                    v-for="task in mentorship.upcoming_tasks.slice(0, 2)"
                    :key="task.id"
                    class="flex items-center justify-between text-sm"
                >
                    <span class="text-gray-600 dark:text-gray-400">{{ task.title }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(task.due_date) }}</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                v-if="mentorship.next_session"
                @click="joinSession"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                Join Session
            </button>
            
            <button
                @click="viewDetails"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                View Details
            </button>
            
            <button
                @click="sendMessage"
                class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-400 rounded-md text-sm font-medium transition-colors dark:text-blue-400 dark:hover:text-blue-200 dark:border-blue-600"
            >
                <ChatBubbleLeftIcon class="w-4 h-4" />
            </button>
            
            <button
                @click="scheduleSession"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 hover:border-gray-400 rounded-md text-sm font-medium transition-colors dark:text-gray-400 dark:hover:text-gray-200 dark:border-gray-600"
            >
                <CalendarIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Rating & Feedback -->
        <div v-if="mentorship.rating" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Your Rating:</span>
                    <div class="flex items-center">
                        <StarIcon 
                            v-for="star in 5"
                            :key="star"
                            :class="star <= mentorship.rating ? 'text-yellow-400' : 'text-gray-300'"
                            class="w-4 h-4"
                        />
                    </div>
                </div>
                <button
                    @click="provideFeedback"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                >
                    Update Rating
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { format } from 'date-fns'
import {
    UserGroupIcon,
    UserIcon,
    CalendarIcon,
    ClockIcon,
    TagIcon,
    VideoCameraIcon,
    MapPinIcon,
    CheckCircleIcon,
    ChatBubbleLeftIcon,
    StarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    mentorship: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['join-session', 'view-details', 'send-message', 'schedule-session', 'provide-feedback'])

const getStatusClass = (status) => {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'scheduled': 'bg-blue-100 text-blue-800',
        'completed': 'bg-gray-100 text-gray-800',
        'paused': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}

const getProgressPercentage = () => {
    const completed = props.mentorship.sessions_completed || 0
    const total = props.mentorship.total_sessions || 10
    return Math.round((completed / total) * 100)
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy HH:mm')
}

const joinSession = () => {
    emit('join-session', props.mentorship.id)
}

const viewDetails = () => {
    emit('view-details', props.mentorship.id)
}

const sendMessage = () => {
    emit('send-message', props.mentorship.mentor_id)
}

const scheduleSession = () => {
    emit('schedule-session', props.mentorship.id)
}

const provideFeedback = () => {
    emit('provide-feedback', props.mentorship.id)
}
</script>

<style scoped>
.active-mentorship-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.active-mentorship-card:hover {
    transform: translateY(-2px);
}
</style>
