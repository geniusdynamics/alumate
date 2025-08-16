<template>
    <div class="card-mobile bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">Upcoming Events</h3>
            <CalendarDaysIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
        </div>
        
        <div class="space-y-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="flex space-x-3">
                        <div class="h-12 w-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Events -->
            <div v-else-if="events.length > 0" class="space-y-4">
                <div 
                    v-for="event in events" 
                    :key="event.id"
                    class="flex space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                    @click="viewEvent(event)"
                >
                    <!-- Date Badge -->
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex flex-col items-center justify-center">
                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase">
                                {{ formatMonth(event.start_date) }}
                            </span>
                            <span class="text-sm font-bold text-indigo-800 dark:text-indigo-200">
                                {{ formatDay(event.start_date) }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Event Details -->
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">
                            {{ event.title }}
                        </h4>
                        <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span class="flex items-center space-x-1">
                                <ClockIcon class="h-3 w-3" />
                                <span>{{ formatTime(event.start_date) }}</span>
                            </span>
                            <span v-if="event.location" class="flex items-center space-x-1">
                                <MapPinIcon class="h-3 w-3" />
                                <span class="truncate">{{ event.location }}</span>
                            </span>
                            <span v-else-if="event.is_virtual" class="flex items-center space-x-1">
                                <VideoCameraIcon class="h-3 w-3" />
                                <span>Virtual</span>
                            </span>
                        </div>
                        
                        <!-- RSVP Status -->
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center space-x-2">
                                <span v-if="event.attendee_count" class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ event.attendee_count }} attending
                                </span>
                                <span 
                                    v-if="event.rsvp_status"
                                    :class="[
                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                        event.rsvp_status === 'attending' 
                                            ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200'
                                            : event.rsvp_status === 'maybe'
                                            ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                                    ]"
                                >
                                    {{ event.rsvp_status === 'attending' ? 'Going' : event.rsvp_status === 'maybe' ? 'Maybe' : 'Not Going' }}
                                </span>
                            </div>
                            
                            <button
                                v-if="!event.rsvp_status"
                                @click.stop="rsvpToEvent(event, 'attending')"
                                class="text-xs font-medium px-3 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                            >
                                RSVP
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-else class="text-center py-6">
                <CalendarDaysIcon class="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming events</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Check back later for new events
                </p>
            </div>
        </div>
        
        <!-- View All Link -->
        <div v-if="events.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Link 
                :href="route('events.index')"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center justify-center space-x-1"
            >
                <span>View All Events</span>
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    VideoCameraIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const loading = ref(true)
const events = ref([])

const props = defineProps({
    limit: {
        type: Number,
        default: 3
    }
})

onMounted(async () => {
    await fetchUpcomingEvents()
})

const fetchUpcomingEvents = async () => {
    try {
        loading.value = true
        const response = await fetch(`/api/dashboard/upcoming-events?limit=${props.limit}`)
        const data = await response.json()
        events.value = data.events || []
    } catch (error) {
        console.error('Failed to fetch upcoming events:', error)
        events.value = []
    } finally {
        loading.value = false
    }
}

const formatMonth = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { month: 'short' })
}

const formatDay = (dateString) => {
    const date = new Date(dateString)
    return date.getDate()
}

const formatTime = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    })
}

const viewEvent = (event) => {
    window.location.href = `/events/${event.id}`
}

const rsvpToEvent = async (event, status) => {
    try {
        const response = await fetch(`/api/events/${event.id}/rsvp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status })
        })
        
        if (response.ok) {
            event.rsvp_status = status
            if (status === 'attending') {
                event.attendee_count = (event.attendee_count || 0) + 1
            }
        }
    } catch (error) {
        console.error('Failed to RSVP to event:', error)
    }
}
</script>