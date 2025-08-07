<template>
    <AppLayout title="Events Discovery">
        <Head title="Events Discovery" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Events Discovery</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Discover and join alumni events, reunions, and networking opportunities</p>
                    </div>
                    <div class="flex space-x-3">
                        <Link 
                            :href="route('events.create')"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            Create Event
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-6">
                    <EventFilters 
                        :filters="filters"
                        :event-types="eventTypes"
                        :locations="locations"
                        @filters-changed="handleFiltersChanged"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Featured Events -->
                    <div v-if="featuredEvents.length > 0" class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Featured Events</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <EventCard
                                v-for="event in featuredEvents"
                                :key="event.id"
                                :event="event"
                                :featured="true"
                                @register="handleEventRegister"
                                @favorite="handleEventFavorite"
                                @view-details="handleViewDetails"
                            />
                        </div>
                    </div>

                    <!-- All Events -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                    All Events ({{ events.total }})
                                </h2>
                                <div class="flex items-center space-x-2">
                                    <select
                                        v-model="sortBy"
                                        @change="applySorting"
                                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="date">Sort by Date</option>
                                        <option value="popularity">Sort by Popularity</option>
                                        <option value="relevance">Sort by Relevance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="events.data.length === 0" class="text-center py-12">
                                <CalendarIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No events found</h3>
                                <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters or check back later</p>
                            </div>
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <EventCard
                                    v-for="event in eventsList"
                                    :key="event.id"
                                    :event="event"
                                    @register="handleEventRegister"
                                    @favorite="handleEventFavorite"
                                    @view-details="handleViewDetails"
                                    @attended="handleEventAttended"
                                    @networking="handleEventNetworking"
                                />
                            </div>

                            <!-- Pagination -->
                            <div v-if="events.last_page > 1" class="mt-8">
                                <Pagination :links="events.links" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- My Events -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">My Events</h3>
                                <Link 
                                    :href="route('events.my-events')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    View All
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="myEvents.length === 0" class="text-center py-4">
                                <CalendarIcon class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">No registered events</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="event in myEvents" 
                                    :key="event.id"
                                    class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ event.title }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ formatDate(event.start_date) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Reunions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming Reunions</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="upcomingReunions.length === 0" class="text-center py-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming reunions</p>
                            </div>
                            <div v-else class="space-y-3">
                                <ReunionCard
                                    v-for="reunion in upcomingReunions"
                                    :key="reunion.id"
                                    :reunion="reunion"
                                    :compact="true"
                                    @rsvp="handleReunionRSVP"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Event Categories -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Event Categories</h3>
                        </div>
                        <div class="p-6 space-y-2">
                            <button
                                v-for="category in eventCategories"
                                :key="category.name"
                                @click="filterByCategory(category.name)"
                                class="flex items-center justify-between w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
                            >
                                <span>{{ category.name }}</span>
                                <span class="text-xs text-gray-500 bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded-full">
                                    {{ category.count }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <Link 
                                :href="route('events.create')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Create Event</span>
                            </Link>
                            <Link 
                                :href="route('events.my-events')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <CalendarIcon class="w-5 h-5" />
                                <span>My Events</span>
                            </Link>
                            <button
                                @click="showVirtualEvents = !showVirtualEvents"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <VideoCameraIcon class="w-5 h-5" />
                                <span>Virtual Events</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Detail Modal -->
        <EventDetailModal 
            v-if="selectedEvent && !showRegistrationModal && !showFeedbackModal && !showConnectionRecommendations"
            :event="selectedEvent"
            @close="closeModals"
            @register="handleEventRegister"
        />

        <!-- Event Registration Modal -->
        <EventRegistrationModal
            v-if="showRegistrationModal"
            :event="selectedEvent"
            @confirm="confirmEventRegistration"
            @close="closeModals"
        />

        <!-- Event Feedback Modal -->
        <EventFeedbackModal
            v-if="showFeedbackModal"
            :event="selectedEvent"
            @submit="submitEventFeedback"
            @close="closeModals"
        />

        <!-- Event Connection Recommendations -->
        <EventConnectionRecommendations
            v-if="showConnectionRecommendations"
            :event="selectedEvent"
            @close="closeModals"
        />

        <!-- Virtual Event Viewer -->
        <VirtualEventViewer 
            v-if="showVirtualEventViewer"
            :event="virtualEvent"
            @close="closeModals"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import EventCard from '@/components/EventCard.vue'
import EventFilters from '@/components/EventFilters.vue'
import EventDetailModal from '@/components/EventDetailModal.vue'
import EventRegistrationModal from '@/components/EventRegistrationModal.vue'
import EventFeedbackModal from '@/components/EventFeedbackModal.vue'
import EventConnectionRecommendations from '@/components/EventConnectionRecommendations.vue'
import VirtualEventViewer from '@/components/VirtualEventViewer.vue'
import ReunionCard from '@/components/ReunionCard.vue'
import Pagination from '@/components/Pagination.vue'
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates'
import userFlowIntegration from '@/services/UserFlowIntegration'
import { format } from 'date-fns'
import {
    CalendarIcon,
    PlusIcon,
    VideoCameraIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    events: Object,
    featuredEvents: Array,
    myEvents: Array,
    upcomingReunions: Array,
    eventTypes: Array,
    locations: Array,
    eventCategories: Array,
    currentFilters: Object,
})

const filters = reactive({
    type: props.currentFilters?.type || '',
    location: props.currentFilters?.location || '',
    date_range: props.currentFilters?.date_range || '',
    virtual_only: props.currentFilters?.virtual_only || false,
})

const sortBy = ref('date')
const selectedEvent = ref(null)
const showVirtualEventViewer = ref(false)
const showRegistrationModal = ref(false)
const showFeedbackModal = ref(false)
const showConnectionRecommendations = ref(false)
const virtualEvent = ref(null)
const showVirtualEvents = ref(false)
const eventsList = reactive([...props.events.data])
const userEvents = reactive([...props.myEvents])

// Real-time updates
const realTimeUpdates = useRealTimeUpdates()

onMounted(() => {
    // Set up real-time event updates
    realTimeUpdates.onEventUpdate((event) => {
        // Update event in the list
        const index = eventsList.findIndex(e => e.id === event.id)
        if (index > -1) {
            eventsList[index] = event
        }
        userFlowIntegration.showNotification('Event updated: ' + event.title, 'info')
    })
    
    // Set up user flow integration callbacks
    userFlowIntegration.on('eventRegistered', (registration) => {
        // Add to user events
        const event = eventsList.find(e => e.id === registration.event_id)
        if (event && !userEvents.find(ue => ue.id === event.id)) {
            userEvents.unshift(event)
        }
        
        // Update event registration status
        if (event) {
            event.is_registered = true
            event.attendees_count = (event.attendees_count || 0) + 1
        }
    })
    
    userFlowIntegration.on('eventFeedbackSubmitted', (feedback) => {
        // Handle feedback submission
        showFeedbackModal.value = false
        selectedEvent.value = null
    })
})

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const handleFiltersChanged = (newFilters) => {
    Object.assign(filters, newFilters)
    applyFilters()
}

const applyFilters = () => {
    router.get(route('events.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

const applySorting = () => {
    router.get(route('events.index'), { ...filters, sort: sortBy.value }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const filterByCategory = (category) => {
    filters.type = category
    applyFilters()
}

const handleEventRegister = async (event) => {
    selectedEvent.value = event
    showRegistrationModal.value = true
}

const confirmEventRegistration = async (registrationData) => {
    if (selectedEvent.value) {
        try {
            await userFlowIntegration.registerForEventAndUpdate(selectedEvent.value.id)
            showRegistrationModal.value = false
            selectedEvent.value = null
        } catch (error) {
            console.error('Failed to register for event:', error)
        }
    }
}

const handleEventFavorite = async (eventId) => {
    try {
        const response = await fetch(`/api/events/${eventId}/favorite`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const result = await response.json()
        
        if (result.success) {
            // Update event favorite status
            const event = eventsList.find(e => e.id === eventId)
            if (event) {
                event.is_favorited = !event.is_favorited
            }
            userFlowIntegration.showNotification('Event favorite status updated!', 'success')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to update favorite status', 'error')
    }
}

const handleViewDetails = (event) => {
    selectedEvent.value = event
    
    if (event.is_virtual) {
        virtualEvent.value = event
        showVirtualEventViewer.value = true
    }
}

const handleEventAttended = (event) => {
    selectedEvent.value = event
    showFeedbackModal.value = true
}

const handleEventNetworking = (event) => {
    selectedEvent.value = event
    showConnectionRecommendations.value = true
}

const submitEventFeedback = async (feedbackData) => {
    if (selectedEvent.value) {
        try {
            await userFlowIntegration.submitEventFeedbackAndUpdate(selectedEvent.value.id, feedbackData)
        } catch (error) {
            console.error('Failed to submit feedback:', error)
        }
    }
}

const handleReunionRSVP = async (reunionId) => {
    try {
        const response = await fetch(`/api/reunions/${reunionId}/rsvp`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const result = await response.json()
        
        if (result.success) {
            userFlowIntegration.showNotification('RSVP confirmed!', 'success')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to RSVP', 'error')
    }
}

const closeModals = () => {
    showRegistrationModal.value = false
    showFeedbackModal.value = false
    showConnectionRecommendations.value = false
    showVirtualEventViewer.value = false
    selectedEvent.value = null
    virtualEvent.value = null
}
</script>