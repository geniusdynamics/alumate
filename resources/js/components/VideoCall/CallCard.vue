<template>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center">
        <img 
          :src="call.participant.avatar || '/default-avatar.png'" 
          :alt="call.participant.name"
          class="w-12 h-12 rounded-full mr-4"
        >
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ call.participant.name }}</h3>
          <p class="text-sm text-gray-600">{{ call.participant.title }}</p>
        </div>
      </div>
      <div class="flex items-center space-x-2">
        <span :class="statusClasses" class="px-2 py-1 rounded-full text-xs font-medium">
          {{ call.status }}
        </span>
      </div>
    </div>

    <div class="mb-4">
      <div class="flex items-center text-sm text-gray-600 mb-2">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        {{ formatDate(call.scheduledAt) }}
      </div>
      <div class="flex items-center text-sm text-gray-600 mb-2">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        {{ call.duration }} minutes
      </div>
      <div class="flex items-center text-sm text-gray-600">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a2 2 0 012-2z"></path>
        </svg>
        {{ call.type }}
      </div>
    </div>

    <div v-if="call.topic" class="mb-4">
      <p class="text-sm text-gray-700">{{ call.topic }}</p>
    </div>

    <div class="flex justify-between items-center">
      <div class="flex space-x-2">
        <button
          v-if="call.status === 'scheduled'"
          @click="joinCall"
          class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors text-sm"
        >
          Join Call
        </button>
        <button
          v-if="call.status === 'scheduled'"
          @click="rescheduleCall"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm"
        >
          Reschedule
        </button>
        <button
          v-if="call.status === 'completed' && !call.feedback"
          @click="provideFeedback"
          class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors text-sm"
        >
          Provide Feedback
        </button>
      </div>
      <div class="flex space-x-2">
        <button
          @click="viewDetails"
          class="px-3 py-2 text-gray-600 hover:text-gray-800 transition-colors text-sm"
        >
          View Details
        </button>
        <button
          v-if="call.status === 'scheduled'"
          @click="cancelCall"
          class="px-3 py-2 text-red-600 hover:text-red-800 transition-colors text-sm"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Call {
  id: number
  participant: {
    name: string
    title: string
    avatar?: string
  }
  scheduledAt: string
  duration: number
  type: string
  topic?: string
  status: 'scheduled' | 'in-progress' | 'completed' | 'cancelled'
  feedback?: boolean
}

interface Props {
  call: Call
}

const props = defineProps<Props>()

const emit = defineEmits<{
  join: [id: number]
  reschedule: [id: number]
  cancel: [id: number]
  feedback: [id: number]
  viewDetails: [id: number]
}>()

const statusClasses = computed(() => {
  switch (props.call.status) {
    case 'scheduled':
      return 'bg-blue-100 text-blue-800'
    case 'in-progress':
      return 'bg-green-100 text-green-800'
    case 'completed':
      return 'bg-gray-100 text-gray-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const joinCall = () => emit('join', props.call.id)
const rescheduleCall = () => emit('reschedule', props.call.id)
const cancelCall = () => emit('cancel', props.call.id)
const provideFeedback = () => emit('feedback', props.call.id)
const viewDetails = () => emit('viewDetails', props.call.id)
</script>