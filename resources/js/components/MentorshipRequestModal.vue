<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900">
            Request Mentorship
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Mentor Info -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <div class="flex items-center space-x-4">
            <img
              :src="mentor.user.avatar_url || '/default-avatar.png'"
              :alt="mentor.user.name"
              class="w-16 h-16 rounded-full object-cover"
            />
            <div>
              <h4 class="text-lg font-medium text-gray-900">{{ mentor.user.name }}</h4>
              <p class="text-sm text-gray-600">{{ mentor.user.title || 'Professional' }}</p>
              <div class="flex flex-wrap gap-2 mt-2">
                <span
                  v-for="area in mentor.expertise_areas"
                  :key="area"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ area }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitRequest" class="space-y-6">
          <!-- Message -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Personal Message
              <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.message"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Introduce yourself and explain why you'd like this person as your mentor..."
              required
            ></textarea>
            <p class="mt-1 text-sm text-gray-500">Minimum 20 characters</p>
          </div>

          <!-- Goals -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Mentorship Goals
            </label>
            <textarea
              v-model="form.goals"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="What specific goals do you hope to achieve through this mentorship?"
            ></textarea>
          </div>

          <!-- Duration -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Preferred Duration
            </label>
            <select
              v-model="form.duration_months"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="3">3 months</option>
              <option value="6">6 months</option>
              <option value="9">9 months</option>
              <option value="12">12 months</option>
              <option value="18">18 months</option>
              <option value="24">24 months</option>
            </select>
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
                Sending Request...
              </span>
              <span v-else>Send Request</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

// Props
const props = defineProps({
  mentor: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['close', 'requestSent'])

// Reactive data
const form = ref({
  message: '',
  goals: '',
  duration_months: 6
})

const loading = ref(false)
const error = ref('')
const success = ref('')

// Computed properties
const isFormValid = computed(() => {
  return form.value.message.length >= 20
})

// Methods
const submitRequest = async () => {
  if (!isFormValid.value) return

  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await axios.post('/api/mentorships/request', {
      mentor_id: props.mentor.user.id,
      message: form.value.message,
      goals: form.value.goals || null,
      duration_months: form.value.duration_months
    })

    success.value = 'Mentorship request sent successfully!'
    
    // Emit event to parent component
    emit('requestSent', response.data.request)

    // Close modal after delay
    setTimeout(() => {
      emit('close')
    }, 1500)

  } catch (err) {
    console.error('Failed to send mentorship request:', err)
    error.value = err.response?.data?.message || 'Failed to send request. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>