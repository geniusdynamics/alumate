<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900">
            Schedule Mentorship Session
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="scheduleSession" class="space-y-6">
          <!-- Select Mentorship -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Select Mentorship
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.mentorship_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Choose a mentorship...</option>
              <option
                v-for="mentorship in availableMentorships"
                :key="mentorship.id"
                :value="mentorship.id"
              >
                {{ mentorship.mentor.name }} - {{ mentorship.mentee.name }}
              </option>
            </select>
          </div>

          <!-- Date and Time -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Date
                <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.date"
                type="date"
                :min="minDate"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Time
                <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.time"
                type="time"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
          </div>

          <!-- Duration -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Duration (minutes)
            </label>
            <select
              v-model="form.duration"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="30">30 minutes</option>
              <option value="45">45 minutes</option>
              <option value="60">60 minutes</option>
              <option value="90">90 minutes</option>
              <option value="120">2 hours</option>
            </select>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Session Notes (Optional)
            </label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Add any notes about the session agenda or topics to discuss..."
            ></textarea>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-600">{{ error }}</p>
          </div>

          <!-- Success Message -->
          <div v-if="success" class="bg-green-50 border border-green-200 rounded-md p-3">
            <p class="text-sm text-green-600">{{ success }}</p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading || !isFormValid"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Scheduling...
              </span>
              <span v-else>Schedule Session</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

// Props
const props = defineProps({
  mentorships: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'sessionScheduled'])

// Reactive data
const form = ref({
  mentorship_id: '',
  date: '',
  time: '',
  duration: 60,
  notes: ''
})

const availableMentorships = ref([])
const loading = ref(false)
const error = ref('')
const success = ref('')

// Computed properties
const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})

const isFormValid = computed(() => {
  return form.value.mentorship_id && 
         form.value.date && 
         form.value.time
})

// Methods
const loadMentorships = async () => {
  try {
    if (props.mentorships && props.mentorships.length > 0) {
      availableMentorships.value = props.mentorships
    } else {
      const response = await axios.get('/api/mentorships')
      // Filter for accepted mentorships only
      availableMentorships.value = [
        ...response.data.as_mentor.filter(m => m.status === 'accepted'),
        ...response.data.as_mentee.filter(m => m.status === 'accepted')
      ]
    }
  } catch (err) {
    console.error('Failed to load mentorships:', err)
    error.value = 'Failed to load available mentorships'
  }
}

const scheduleSession = async () => {
  if (!isFormValid.value) return

  loading.value = true
  error.value = ''
  success.value = ''

  try {
    // Combine date and time into a datetime string
    const scheduledAt = `${form.value.date}T${form.value.time}:00`

    const response = await axios.post('/api/mentorships/sessions', {
      mentorship_id: form.value.mentorship_id,
      scheduled_at: scheduledAt,
      duration: form.value.duration,
      notes: form.value.notes || null
    })

    success.value = 'Session scheduled successfully!'
    
    // Emit event to parent component
    emit('sessionScheduled', response.data.session)

    // Reset form
    setTimeout(() => {
      resetForm()
      emit('close')
    }, 1500)

  } catch (err) {
    console.error('Failed to schedule session:', err)
    error.value = err.response?.data?.message || 'Failed to schedule session. Please try again.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    mentorship_id: '',
    date: '',
    time: '',
    duration: 60,
    notes: ''
  }
  error.value = ''
  success.value = ''
}

// Lifecycle
onMounted(() => {
  loadMentorships()
})
</script>