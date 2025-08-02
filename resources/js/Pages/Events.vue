<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Events</h1>
            <p class="mt-1 text-sm text-gray-500">
              Discover and join alumni events in your area
            </p>
          </div>
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Create Event
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filters Sidebar -->
        <div class="lg:w-80 flex-shrink-0">
          <EventFilters
            :filters="filters"
            @update:filters="handleFiltersUpdate"
            :loading="loading"
          />
        </div>

        <!-- Main Content -->
        <div class="flex-1">
          <!-- Quick Actions -->
          <div class="mb-6">
            <div class="flex flex-wrap gap-2">
              <button
                v-for="quickFilter in quickFilters"
                :key="quickFilter.key"
                @click="applyQuickFilter(quickFilter)"
                :class="[
                  'px-3 py-1 rounded-full text-sm font-medium transition-colors',
                  isQuickFilterActive(quickFilter)
                    ? 'bg-blue-100 text-blue-800'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                {{ quickFilter.label }}
              </button>
            </div>
          </div>

          <!-- Upcoming Events for User -->
          <div v-if="upcomingEvents.length > 0" class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Upcoming Events</h2>
            <div class="grid gap-4 md:grid-cols-2">
              <EventCard
                v-for="event in upcomingEvents"
                :key="event.id"
                :event="event"
                :show-registration="false"
                @view="viewEvent"
                class="border-l-4 border-l-green-500"
              />
            </div>
          </div>

          <!-- Recommended Events -->
          <div v-if="recommendedEvents.length > 0" class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recommended for You</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              <EventCard
                v-for="event in recommendedEvents"
                :key="event.id"
                :event="event"
                @view="viewEvent"
                @register="handleRegister"
                class="border-l-4 border-l-blue-500"
              />
            </div>
          </div>

          <!-- All Events -->
          <div>
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold text-gray-900">All Events</h2>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">
                  {{ events.length }} of {{ totalEvents }} events
                </span>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="space-y-4">
              <div v-for="i in 6" :key="i" class="animate-pulse">
                <div class="bg-white rounded-lg shadow p-6">
                  <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                  <div class="h-3 bg-gray-200 rounded w-1/2 mb-4"></div>
                  <div class="h-3 bg-gray-200 rounded w-full mb-2"></div>
                  <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
              </div>
            </div>

            <!-- Events Grid -->
            <div v-else-if="events.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <EventCard
                v-for="event in events"
                :key="event.id"
                :event="event"
                @view="viewEvent"
                @register="handleRegister"
                @edit="editEvent"
              />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3 class="mt-2 text-sm font-medium text-gray-900">No events found</h3>
              <p class="mt-1 text-sm text-gray-500">
                Try adjusting your filters or create a new event.
              </p>
            </div>

            <!-- Load More -->
            <div v-if="hasMoreEvents && !loading" class="mt-8 text-center">
              <button
                @click="loadMoreEvents"
                :disabled="loadingMore"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
              >
                <template v-if="loadingMore">
                  <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Loading...
                </template>
                <template v-else>
                  Load More Events
                </template>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Detail Modal -->
    <EventDetailModal
      v-if="selectedEvent"
      :event="selectedEvent"
      :show="showDetailModal"
      @close="closeDetailModal"
      @register="handleRegister"
      @edit="editEvent"
      @checkin="handleCheckIn"
    />

    <!-- Create/Edit Event Modal -->
    <EventFormModal
      :show="showCreateModal || showEditModal"
      :event="editingEvent"
      @close="closeFormModal"
      @saved="handleEventSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { PlusIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import EventCard from '@/Components/EventCard.vue'
import EventFilters from '@/Components/EventFilters.vue'
import EventDetailModal from '@/Components/EventDetailModal.vue'
import EventFormModal from '@/Components/EventFormModal.vue'
import { useEventsStore } from '@/Stores/eventsStore'

interface Event {
  id: number
  title: string
  description: string
  short_description?: string
  type: string
  format: string
  start_date: string
  end_date: string
  venue_name?: string
  venue_address?: string
  virtual_link?: string
  max_capacity?: number
  current_attendees: number
  organizer: {
    id: number
    name: string
    avatar_url?: string
  }
  institution?: {
    id: number
    name: string
  }
  user_data?: {
    is_registered: boolean
    registration?: any
    is_checked_in: boolean
    can_edit: boolean
  }
}

const eventsStore = useEventsStore()

// State
const events = ref<Event[]>([])
const upcomingEvents = ref<Event[]>([])
const recommendedEvents = ref<Event[]>([])
const selectedEvent = ref<Event | null>(null)
const editingEvent = ref<Event | null>(null)

const loading = ref(false)
const loadingMore = ref(false)
const showDetailModal = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)

const currentPage = ref(1)
const totalEvents = ref(0)
const hasMoreEvents = ref(true)

const filters = reactive({
  type: '',
  format: '',
  date_range: '',
  location: null,
  radius: 50,
  tags: [],
  search: ''
})

const quickFilters = [
  { key: 'upcoming', label: 'Upcoming', filters: { date_range: 'upcoming' } },
  { key: 'this_week', label: 'This Week', filters: { date_range: 'this_week' } },
  { key: 'networking', label: 'Networking', filters: { type: 'networking' } },
  { key: 'virtual', label: 'Virtual', filters: { format: 'virtual' } },
  { key: 'reunions', label: 'Reunions', filters: { type: 'reunion' } }
]

// Computed
const activeQuickFilter = ref<string | null>(null)

// Methods
const loadEvents = async (reset = false) => {
  if (reset) {
    currentPage.value = 1
    events.value = []
  }

  loading.value = reset
  loadingMore.value = !reset

  try {
    const response = await eventsStore.getEvents({
      ...filters,
      page: currentPage.value,
      per_page: 12
    })

    if (reset) {
      events.value = response.data
    } else {
      events.value.push(...response.data)
    }

    totalEvents.value = response.meta.total
    hasMoreEvents.value = currentPage.value < response.meta.last_page
  } catch (error) {
    console.error('Failed to load events:', error)
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const loadUpcomingEvents = async () => {
  try {
    const response = await eventsStore.getUpcomingEvents()
    upcomingEvents.value = response.data
  } catch (error) {
    console.error('Failed to load upcoming events:', error)
  }
}

const loadRecommendedEvents = async () => {
  try {
    const response = await eventsStore.getRecommendedEvents()
    recommendedEvents.value = response.data
  } catch (error) {
    console.error('Failed to load recommended events:', error)
  }
}

const loadMoreEvents = () => {
  currentPage.value++
  loadEvents(false)
}

const handleFiltersUpdate = () => {
  activeQuickFilter.value = null
  loadEvents(true)
}

const applyQuickFilter = (quickFilter: any) => {
  activeQuickFilter.value = quickFilter.key
  Object.assign(filters, quickFilter.filters)
  loadEvents(true)
}

const isQuickFilterActive = (quickFilter: any) => {
  return activeQuickFilter.value === quickFilter.key
}

const viewEvent = (event: Event) => {
  selectedEvent.value = event
  showDetailModal.value = true
}

const editEvent = (event: Event) => {
  editingEvent.value = event
  showEditModal.value = true
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedEvent.value = null
}

const closeFormModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingEvent.value = null
}

const handleEventSaved = (event: Event) => {
  closeFormModal()
  loadEvents(true)
  loadUpcomingEvents()
  loadRecommendedEvents()
}

const handleRegister = async (event: Event, registrationData?: any) => {
  try {
    await eventsStore.registerForEvent(event.id, registrationData)
    
    // Update the event in our lists
    const updateEvent = (eventList: Event[]) => {
      const index = eventList.findIndex(e => e.id === event.id)
      if (index !== -1) {
        eventList[index].user_data = {
          ...eventList[index].user_data,
          is_registered: true
        }
        eventList[index].current_attendees++
      }
    }

    updateEvent(events.value)
    updateEvent(recommendedEvents.value)
    
    if (selectedEvent.value?.id === event.id) {
      selectedEvent.value.user_data = {
        ...selectedEvent.value.user_data,
        is_registered: true
      }
      selectedEvent.value.current_attendees++
    }
  } catch (error) {
    console.error('Failed to register for event:', error)
  }
}

const handleCheckIn = async (event: Event) => {
  try {
    await eventsStore.checkInToEvent(event.id)
    
    // Update the event
    if (selectedEvent.value?.id === event.id) {
      selectedEvent.value.user_data = {
        ...selectedEvent.value.user_data,
        is_checked_in: true
      }
    }
  } catch (error) {
    console.error('Failed to check in to event:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadEvents(true)
  loadUpcomingEvents()
  loadRecommendedEvents()
})
</script>