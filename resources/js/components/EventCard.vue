<template>
  <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-200">
    <!-- Event Image/Media -->
    <div v-if="event.media_urls && event.media_urls.length > 0" class="relative">
      <img
        :src="event.media_urls[0]"
        :alt="event.title"
        class="w-full h-32 sm:h-40 md:h-48 object-cover rounded-t-lg"
      />
      <div class="absolute top-3 left-3">
        <span :class="formatBadgeClass" class="px-2 py-1 text-xs font-medium rounded-full">
          {{ formatLabel }}
        </span>
      </div>
      <div class="absolute top-3 right-3">
        <span :class="typeBadgeClass" class="px-2 py-1 text-xs font-medium rounded-full">
          {{ typeLabel }}
        </span>
      </div>
    </div>

    <!-- Event Content -->
    <div class="p-4 sm:p-6">
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex-1">
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 line-clamp-2 mb-1">
            {{ event.title }}
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 line-clamp-2">
            {{ event.short_description || event.description }}
          </p>
        </div>
        <div v-if="event.user_data?.can_edit" class="ml-2">
          <button
            @click.stop="$emit('edit', event)"
            class="p-1 text-gray-400 hover:text-gray-600 rounded"
          >
            <PencilIcon class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- Date and Time -->
      <div class="flex items-center text-sm text-gray-600 mb-3">
        <CalendarIcon class="h-4 w-4 mr-2 flex-shrink-0" />
        <div>
          <div class="font-medium">{{ formatDate(event.start_date) }}</div>
          <div class="text-xs">{{ formatTime(event.start_date, event.end_date) }}</div>
        </div>
      </div>

      <!-- Location -->
      <div class="flex items-center text-sm text-gray-600 mb-4">
        <MapPinIcon class="h-4 w-4 mr-2 flex-shrink-0" />
        <span class="truncate">
          <template v-if="event.format === 'virtual'">
            Virtual Event
          </template>
          <template v-else-if="event.format === 'hybrid'">
            {{ event.venue_name || 'Hybrid Event' }}
          </template>
          <template v-else>
            {{ event.venue_name || event.venue_address || 'TBD' }}
          </template>
        </span>
      </div>

      <!-- Organizer -->
      <div class="flex items-center text-sm text-gray-600 mb-4">
        <div class="flex items-center">
          <img
            v-if="event.organizer.avatar_url"
            :src="event.organizer.avatar_url"
            :alt="event.organizer.name"
            class="h-6 w-6 rounded-full mr-2"
          />
          <div v-else class="h-6 w-6 bg-gray-300 rounded-full mr-2 flex items-center justify-center">
            <span class="text-xs font-medium text-gray-600">
              {{ event.organizer.name.charAt(0).toUpperCase() }}
            </span>
          </div>
          <span>{{ event.organizer.name }}</span>
        </div>
      </div>

      <!-- Capacity Info -->
      <div v-if="event.max_capacity" class="mb-4">
        <div class="flex justify-between text-sm text-gray-600 mb-1">
          <span>Attendees</span>
          <span>{{ event.current_attendees }} / {{ event.max_capacity }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${Math.min((event.current_attendees / event.max_capacity) * 100, 100)}%` }"
          ></div>
        </div>
      </div>

      <!-- Registration Status -->
      <div v-if="event.user_data?.is_registered" class="mb-4">
        <div class="flex items-center text-sm">
          <CheckCircleIcon class="h-4 w-4 text-green-500 mr-2" />
          <span class="text-green-700 font-medium">
            <template v-if="event.user_data.is_checked_in">
              Checked In
            </template>
            <template v-else>
              Registered
            </template>
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t">
        <button
          @click="$emit('view', event)"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          View Details
        </button>

        <div class="flex items-center space-x-2">
          <!-- Check-in Button -->
          <button
            v-if="event.user_data?.is_registered && !event.user_data.is_checked_in && canCheckIn"
            @click.stop="$emit('checkin', event)"
            class="px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors"
          >
            Check In
          </button>

          <!-- Register Button -->
          <button
            v-else-if="!event.user_data?.is_registered && showRegistration"
            @click.stop="$emit('register', event)"
            :disabled="!canRegister"
            :class="[
              'px-3 py-1 text-xs font-medium rounded-md transition-colors',
              canRegister
                ? 'text-white bg-blue-600 hover:bg-blue-700'
                : 'text-gray-500 bg-gray-200 cursor-not-allowed'
            ]"
          >
            <template v-if="!canRegister">
              {{ registrationStatusText }}
            </template>
            <template v-else>
              Register
            </template>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  CalendarIcon,
  MapPinIcon,
  CheckCircleIcon,
  PencilIcon
} from '@heroicons/vue/24/outline'
import { format, parseISO, isAfter, isBefore, addHours } from 'date-fns'

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
  registration_status: string
  registration_deadline?: string
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
  media_urls?: string[]
}

interface Props {
  event: Event
  showRegistration?: boolean
}

interface Emits {
  (e: 'view', event: Event): void
  (e: 'register', event: Event): void
  (e: 'edit', event: Event): void
  (e: 'checkin', event: Event): void
}

const props = withDefaults(defineProps<Props>(), {
  showRegistration: true
})

defineEmits<Emits>()

// Computed properties
const formatLabel = computed(() => {
  const formats = {
    'in_person': 'In Person',
    'virtual': 'Virtual',
    'hybrid': 'Hybrid'
  }
  return formats[props.event.format as keyof typeof formats] || props.event.format
})

const formatBadgeClass = computed(() => {
  const classes = {
    'in_person': 'bg-green-100 text-green-800',
    'virtual': 'bg-blue-100 text-blue-800',
    'hybrid': 'bg-purple-100 text-purple-800'
  }
  return classes[props.event.format as keyof typeof classes] || 'bg-gray-100 text-gray-800'
})

const typeLabel = computed(() => {
  const types = {
    'networking': 'Networking',
    'reunion': 'Reunion',
    'webinar': 'Webinar',
    'workshop': 'Workshop',
    'social': 'Social',
    'professional': 'Professional',
    'fundraising': 'Fundraising',
    'other': 'Other'
  }
  return types[props.event.type as keyof typeof types] || props.event.type
})

const typeBadgeClass = computed(() => {
  const classes = {
    'networking': 'bg-orange-100 text-orange-800',
    'reunion': 'bg-pink-100 text-pink-800',
    'webinar': 'bg-indigo-100 text-indigo-800',
    'workshop': 'bg-yellow-100 text-yellow-800',
    'social': 'bg-green-100 text-green-800',
    'professional': 'bg-blue-100 text-blue-800',
    'fundraising': 'bg-red-100 text-red-800',
    'other': 'bg-gray-100 text-gray-800'
  }
  return classes[props.event.type as keyof typeof classes] || 'bg-gray-100 text-gray-800'
})

const canRegister = computed(() => {
  if (props.event.registration_status !== 'open') {
    return false
  }

  if (props.event.registration_deadline) {
    const deadline = parseISO(props.event.registration_deadline)
    if (isAfter(new Date(), deadline)) {
      return false
    }
  }

  if (props.event.max_capacity && props.event.current_attendees >= props.event.max_capacity) {
    return false
  }

  return true
})

const canCheckIn = computed(() => {
  const now = new Date()
  const startDate = parseISO(props.event.start_date)
  const endDate = parseISO(props.event.end_date)
  
  // Allow check-in 2 hours before event starts and until event ends
  const checkInStart = addHours(startDate, -2)
  
  return isAfter(now, checkInStart) && isBefore(now, endDate)
})

const registrationStatusText = computed(() => {
  if (props.event.registration_status === 'closed') {
    return 'Closed'
  }
  
  if (props.event.registration_status === 'waitlist') {
    return 'Join Waitlist'
  }
  
  if (props.event.max_capacity && props.event.current_attendees >= props.event.max_capacity) {
    return 'Full'
  }
  
  if (props.event.registration_deadline) {
    const deadline = parseISO(props.event.registration_deadline)
    if (isAfter(new Date(), deadline)) {
      return 'Deadline Passed'
    }
  }
  
  return 'Register'
})

// Helper methods
const formatDate = (dateString: string) => {
  const date = parseISO(dateString)
  return format(date, 'MMM d, yyyy')
}

const formatTime = (startString: string, endString: string) => {
  const start = parseISO(startString)
  const end = parseISO(endString)
  return `${format(start, 'h:mm a')} - ${format(end, 'h:mm a')}`
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>