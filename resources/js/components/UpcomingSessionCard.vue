<template>
    <div class="upcoming-session-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Session Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <CalendarIcon class="w-6 h-6 text-white" />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ session.title || 'Mentorship Session' }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">with {{ session.mentor_name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span
                    :class="getSessionStatusClass(session.status)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                    {{ formatSessionStatus(session.status) }}
                </span>
            </div>
        </div>

        <!-- Countdown Timer -->
        <div v-if="timeUntilSession" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-900 dark:text-white">Session starts in:</span>
                <div class="flex items-center space-x-2">
                    <ClockIcon class="w-4 h-4 text-blue-600" />
                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ timeUntilSession }}</span>
                </div>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                <div
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: getTimeProgressPercentage() + '%' }"
                ></div>
            </div>
        </div>

        <!-- Session Details -->
        <div class="space-y-3 mb-4">
            <!-- Date and Time -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <CalendarIcon class="w-4 h-4" />
                <span>{{ formatSessionDate(session.scheduled_at) }}</span>
                <span>•</span>
                <span>{{ formatSessionTime(session.scheduled_at) }}</span>
            </div>

            <!-- Duration -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <ClockIcon class="w-4 h-4" />
                <span>{{ session.duration || 60 }} minutes</span>
            </div>

            <!-- Meeting Type -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <VideoCameraIcon v-if="session.meeting_type === 'virtual'" class="w-4 h-4" />
                <MapPinIcon v-else class="w-4 h-4" />
                <span>{{ session.meeting_type === 'virtual' ? 'Virtual Meeting' : 'In-Person' }}</span>
                <span v-if="session.meeting_link" class="text-blue-600 dark:text-blue-400 cursor-pointer" @click="openMeetingLink">
                    (Join Link)
                </span>
            </div>

            <!-- Location (for in-person) -->
            <div v-if="session.meeting_type === 'in_person' && session.location" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <MapPinIcon class="w-4 h-4" />
                <span>{{ session.location }}</span>
            </div>
        </div>

        <!-- Mentor Info -->
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <UserIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ session.mentor_name }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ session.mentor_position }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ session.mentor_company }}</p>
                </div>
            </div>
        </div>

        <!-- Session Goals -->
        <div v-if="session.goals && session.goals.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Session Goals</h4>
            <ul class="space-y-1">
                <li
                    v-for="goal in session.goals.slice(0, 3)"
                    :key="goal"
                    class="flex items-start text-sm text-gray-600 dark:text-gray-400"
                >
                    <ChevronRightIcon class="w-4 h-4 text-blue-500 mr-2 flex-shrink-0 mt-0.5" />
                    {{ goal }}
                </li>
                <li v-if="session.goals.length > 3" class="text-sm text-gray-500 dark:text-gray-400">
                    +{{ session.goals.length - 3 }} more goals
                </li>
            </ul>
        </div>

        <!-- Preparation Notes -->
        <div v-if="session.preparation_notes" class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-md">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                <LightBulbIcon class="w-4 h-4 text-yellow-500 mr-2" />
                Preparation Notes
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ session.preparation_notes }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                v-if="canJoinSession"
                @click="joinSession"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                Join Session
            </button>

            <button
                v-else
                @click="viewDetails"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                View Details
            </button>

            <button
                @click="rescheduleSession"
                class="px-4 py-2 text-orange-600 hover:text-orange-800 border border-orange-300 hover:border-orange-400 rounded-md text-sm font-medium transition-colors dark:text-orange-400 dark:hover:text-orange-200 dark:border-orange-600"
            >
                Reschedule
            </button>

            <button
                @click="messageMentor"
                class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-400 rounded-md text-sm font-medium transition-colors dark:text-blue-400 dark:hover:text-blue-200 dark:border-blue-600"
            >
                <ChatBubbleLeftIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Session Reminders -->
        <div v-if="session.reminders_enabled" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
                    <BellIcon class="w-4 h-4" />
                    <span>Reminders enabled</span>
                </div>
                <button
                    @click="manageReminders"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400"
                >
                    Manage
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { format, formatDistanceToNow, differenceInMinutes, isPast, isBefore, addMinutes } from 'date-fns'
import {
    CalendarIcon,
    ClockIcon,
    VideoCameraIcon,
    MapPinIcon,
    UserIcon,
    ChevronRightIcon,
    LightBulbIcon,
    ChatBubbleLeftIcon,
    BellIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    session: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['join-session', 'view-details', 'reschedule-session', 'message-mentor', 'manage-reminders'])

const currentTime = ref(new Date())
let timeInterval = null

onMounted(() => {
    // Update current time every minute for countdown
    timeInterval = setInterval(() => {
        currentTime.value = new Date()
    }, 60000)
})

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval)
    }
})

const timeUntilSession = computed(() => {
    const sessionDate = new Date(props.session.scheduled_at)
    if (isPast(sessionDate)) return null

    return formatDistanceToNow(sessionDate, { addSuffix: false })
})

const canJoinSession = computed(() => {
    const sessionDate = new Date(props.session.scheduled_at)
    const now = currentTime.value
    const sessionStart = sessionDate
    const joinWindow = addMinutes(sessionStart, -15) // Can join 15 minutes early

    return !isBefore(now, joinWindow) && !isPast(addMinutes(sessionDate, props.session.duration || 60))
})

const getTimeProgressPercentage = () => {
    const sessionDate = new Date(props.session.scheduled_at)
    const now = currentTime.value
    const totalMinutes = differenceInMinutes(sessionDate, now)

    if (totalMinutes <= 0) return 100
    if (totalMinutes >= 1440) return 0 // 24 hours

    return Math.max(0, Math.min(100, ((1440 - totalMinutes) / 1440) * 100))
}

const getSessionStatusClass = (status) => {
    const classes = {
        'scheduled': 'bg-blue-100 text-blue-800',
        'confirmed': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800',
        'completed': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatSessionStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatSessionDate = (dateString) => {
    return format(new Date(dateString), 'EEEE, MMM dd, yyyy')
}

const formatSessionTime = (dateString) => {
    return format(new Date(dateString), 'HH:mm')
}

const joinSession = () => {
    emit('join-session', props.session.id)
}

const viewDetails = () => {
    emit('view-details', props.session.id)
}

const rescheduleSession = () => {
    emit('reschedule-session', props.session.id)
}

const messageMentor = () => {
    emit('message-mentor', props.session.mentor_id)
}

const manageReminders = () => {
    emit('manage-reminders', props.session.id)
}

const openMeetingLink = () => {
    if (props.session.meeting_link) {
        window.open(props.session.meeting_link, '_blank')
    }
}
</script>

<style scoped>
.upcoming-session-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.upcoming-session-card:hover {
    transform: translateY(-2px);
}
</style>