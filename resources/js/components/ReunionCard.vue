<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200">
    <!-- Header with milestone badge -->
    <div class="relative">
      <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-white">{{ reunion.title }}</h3>
            <p class="text-blue-100 text-sm">{{ reunion.class_identifier || `Class of ${reunion.graduation_year}` }}</p>
          </div>
          <div v-if="reunion.reunion_year_milestone" class="bg-white bg-opacity-20 rounded-full px-3 py-1">
            <span class="text-white text-sm font-medium">{{ reunion.reunion_year_milestone }} Year</span>
          </div>
        </div>
      </div>
      
      <!-- Theme banner -->
      <div v-if="reunion.reunion_theme" class="bg-blue-50 px-6 py-2 border-b">
        <p class="text-blue-800 text-sm italic">"{{ reunion.reunion_theme }}"</p>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <!-- Date and venue -->
      <div class="mb-4">
        <div class="flex items-center text-gray-600 mb-2">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11m-6 0h8m-8 0V7a2 2 0 012-2h4a2 2 0 012 2v4" />
          </svg>
          <span class="text-sm">{{ formatDate(reunion.start_date) }}</span>
        </div>
        
        <div v-if="reunion.venue_name" class="flex items-center text-gray-600">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span class="text-sm">{{ reunion.venue_name }}</span>
        </div>
      </div>

      <!-- Description -->
      <p class="text-gray-700 text-sm mb-4 line-clamp-3">{{ reunion.description }}</p>

      <!-- Features -->
      <div class="flex items-center space-x-4 mb-4">
        <div v-if="reunion.enable_photo_sharing" class="flex items-center text-green-600">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="text-xs">Photo Sharing</span>
        </div>
        
        <div v-if="reunion.enable_memory_wall" class="flex items-center text-purple-600">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <span class="text-xs">Memory Wall</span>
        </div>
      </div>

      <!-- Attendance info -->
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-600">
          <span class="font-medium">{{ reunion.current_attendees }}</span>
          <span v-if="reunion.max_capacity"> / {{ reunion.max_capacity }}</span>
          attending
        </div>
        
        <div v-if="reunion.ticket_price" class="text-sm font-medium text-gray-900">
          ${{ reunion.ticket_price }}
        </div>
      </div>

      <!-- Organizer -->
      <div class="flex items-center mb-4">
        <img
          :src="reunion.organizer.avatar_url || '/default-avatar.png'"
          :alt="reunion.organizer.name"
          class="w-6 h-6 rounded-full mr-2"
        />
        <span class="text-sm text-gray-600">Organized by {{ reunion.organizer.name }}</span>
      </div>

      <!-- Status indicator -->
      <div class="mb-4">
        <span
          :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
            getStatusClasses(reunion.status)
          ]"
        >
          {{ getStatusText(reunion.status) }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-between">
      <button
        @click="$emit('view', reunion)"
        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
      >
        View Details
      </button>
      
      <div class="flex space-x-2">
        <button
          v-if="canRegister"
          @click="$emit('register', reunion)"
          class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          Register
        </button>
        
        <button
          v-else-if="isUpcoming"
          disabled
          class="px-4 py-2 bg-gray-300 text-gray-500 text-sm rounded-md cursor-not-allowed"
        >
          {{ getRegistrationStatus() }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Reunion {
  id: number
  title: string
  description: string
  graduation_year: number
  class_identifier: string
  reunion_year_milestone: number
  reunion_theme: string
  start_date: string
  end_date: string
  venue_name: string
  venue_address: string
  organizer: {
    id: number
    name: string
    avatar_url: string
  }
  institution: {
    id: number
    name: string
  }
  current_attendees: number
  max_capacity: number
  ticket_price: number
  status: string
  registration_status: string
  enable_photo_sharing: boolean
  enable_memory_wall: boolean
}

interface Props {
  reunion: Reunion
}

const props = defineProps<Props>()

defineEmits<{
  view: [reunion: Reunion]
  register: [reunion: Reunion]
}>()

const isUpcoming = computed(() => {
  return new Date(props.reunion.start_date) > new Date()
})

const canRegister = computed(() => {
  return isUpcoming.value && 
         props.reunion.status === 'published' && 
         props.reunion.registration_status === 'open' &&
         (!props.reunion.max_capacity || props.reunion.current_attendees < props.reunion.max_capacity)
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getStatusClasses = (status: string) => {
  switch (status) {
    case 'published':
      return 'bg-green-100 text-green-800'
    case 'draft':
      return 'bg-yellow-100 text-yellow-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    case 'completed':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'published':
      return 'Open'
    case 'draft':
      return 'Draft'
    case 'cancelled':
      return 'Cancelled'
    case 'completed':
      return 'Completed'
    default:
      return status
  }
}

const getRegistrationStatus = () => {
  if (props.reunion.registration_status === 'closed') {
    return 'Registration Closed'
  }
  if (props.reunion.registration_status === 'waitlist') {
    return 'Join Waitlist'
  }
  if (props.reunion.max_capacity && props.reunion.current_attendees >= props.reunion.max_capacity) {
    return 'Full'
  }
  return 'Unavailable'
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>