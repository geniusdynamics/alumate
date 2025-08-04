<template>
    <div class="speaking-event-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Event Header -->
        <div class="mb-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ event.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ event.description }}</p>
                </div>
                <div class="ml-4">
                    <span 
                        :class="getEventStatusClass(event.status)"
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                    >
                        {{ formatEventStatus(event.status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Event Details -->
        <div class="space-y-3 mb-4">
            <!-- Date and Time -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <CalendarIcon class="w-4 h-4" />
                <span>{{ formatEventDate(event.event_date) }}</span>
                <span v-if="event.event_time">• {{ event.event_time }}</span>
            </div>

            <!-- Location -->
            <div v-if="event.location" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <MapPinIcon class="w-4 h-4" />
                <span>{{ event.location }}</span>
                <span v-if="event.is_virtual" class="text-blue-600 dark:text-blue-400">(Virtual)</span>
            </div>

            <!-- Event Type -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <TagIcon class="w-4 h-4" />
                <span>{{ formatEventType(event.event_type) }}</span>
            </div>

            <!-- Audience -->
            <div v-if="event.audience_size" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <UsersIcon class="w-4 h-4" />
                <span>{{ event.audience_size }} attendees</span>
                <span v-if="event.audience_type">• {{ event.audience_type }}</span>
            </div>

            <!-- Duration -->
            <div v-if="event.duration" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <ClockIcon class="w-4 h-4" />
                <span>{{ event.duration }} minutes</span>
            </div>
        </div>

        <!-- Speaker Info -->
        <div v-if="event.speaker" class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <UserIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ event.speaker.user.name }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ event.speaker.user.current_position }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ event.speaker.user.current_company }}</p>
                </div>
            </div>
        </div>

        <!-- Event Topics -->
        <div v-if="event.topics && event.topics.length > 0" class="mb-4">
            <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Topics</h5>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="topic in event.topics.slice(0, 3)"
                    :key="topic"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ topic }}
                </span>
                <span
                    v-if="event.topics.length > 3"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ event.topics.length - 3 }} more
                </span>
            </div>
        </div>

        <!-- Organizer Info -->
        <div v-if="event.organizer" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium">Organized by:</span> {{ event.organizer.name }}
            <span v-if="event.organizer.organization"> • {{ event.organizer.organization }}</span>
        </div>

        <!-- Registration Info -->
        <div v-if="event.registration_required" class="mb-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Registration:</span>
                <div class="flex items-center space-x-2">
                    <span 
                        :class="event.registration_open ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                        class="font-medium"
                    >
                        {{ event.registration_open ? 'Open' : 'Closed' }}
                    </span>
                    <span v-if="event.registration_deadline" class="text-gray-500 dark:text-gray-400">
                        (Deadline: {{ formatDate(event.registration_deadline) }})
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                @click="viewDetails"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                View Details
            </button>
            
            <button
                v-if="event.registration_open && event.registration_required"
                @click="register"
                class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-400 rounded-md text-sm font-medium transition-colors dark:text-blue-400 dark:hover:text-blue-200 dark:border-blue-600"
            >
                Register
            </button>
            
            <button
                v-if="event.is_virtual && event.meeting_link"
                @click="joinVirtual"
                class="px-4 py-2 text-green-600 hover:text-green-800 border border-green-300 hover:border-green-400 rounded-md text-sm font-medium transition-colors dark:text-green-400 dark:hover:text-green-200 dark:border-green-600"
            >
                Join Virtual
            </button>
        </div>

        <!-- Additional Info -->
        <div v-if="event.special_requirements" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Special Requirements</h5>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ event.special_requirements }}</p>
        </div>
    </div>
</template>

<script setup>
import { format } from 'date-fns'
import {
    CalendarIcon,
    MapPinIcon,
    TagIcon,
    UsersIcon,
    ClockIcon,
    UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['view-details', 'register', 'join-virtual'])

const getEventStatusClass = (status) => {
    const classes = {
        confirmed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
        completed: 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatEventStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatEventType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatEventDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const viewDetails = () => {
    emit('view-details', props.event.id)
}

const register = () => {
    emit('register', props.event.id)
}

const joinVirtual = () => {
    if (props.event.meeting_link) {
        window.open(props.event.meeting_link, '_blank')
    } else {
        emit('join-virtual', props.event.id)
    }
}
</script>

<style scoped>
.speaking-event-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.speaking-event-card:hover {
    transform: translateY(-2px);
}
</style>
