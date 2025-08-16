<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Request Coffee Chat</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Recipient Info -->
      <div class="flex items-center space-x-3 mb-6 p-3 bg-gray-50 rounded-lg">
        <img
          :src="recipient.avatar_url || '/default-avatar.png'"
          :alt="recipient.name"
          class="w-10 h-10 rounded-full object-cover"
        >
        <div>
          <h4 class="font-medium text-gray-900">{{ recipient.name }}</h4>
          <p class="text-sm text-gray-600">{{ recipient.title }}</p>
        </div>
      </div>

      <form @submit.prevent="sendRequest">
        <!-- Message -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Personal Message (Optional)
          </label>
          <textarea
            v-model="form.message"
            rows="3"
            placeholder="Hi! I'd love to connect over coffee and learn about your experience in..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
            maxlength="500"
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">
            {{ form.message.length }}/500 characters
          </p>
        </div>

        <!-- Proposed Times -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Proposed Times *
          </label>
          <p class="text-xs text-gray-600 mb-3">
            Suggest 2-3 time slots that work for you
          </p>
          
          <div class="space-y-3">
            <div
              v-for="(time, index) in form.proposed_times"
              :key="index"
              class="flex items-center space-x-2"
            >
              <input
                v-model="form.proposed_times[index]"
                type="datetime-local"
                :min="minDateTime"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              >
              <button
                v-if="form.proposed_times.length > 1"
                @click="removeTimeSlot(index)"
                type="button"
                class="text-red-500 hover:text-red-700"
              >
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          
          <button
            v-if="form.proposed_times.length < 5"
            @click="addTimeSlot"
            type="button"
            class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium"
          >
            <i class="fas fa-plus mr-1"></i>
            Add Another Time Slot
          </button>
        </div>

        <!-- Request Type -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Meeting Type
          </label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="form.type"
                type="radio"
                value="direct_request"
                class="mr-2"
              >
              <span class="text-sm">Direct Request</span>
            </label>
            <label class="flex items-center">
              <input
                v-model="form.type"
                type="radio"
                value="ai_matched"
                class="mr-2"
              >
              <span class="text-sm">AI-Matched Connection</span>
            </label>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
          <button
            @click="$emit('close')"
            type="button"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!canSubmit || submitting"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition-colors"
          >
            <span v-if="submitting">
              <i class="fas fa-spinner fa-spin mr-2"></i>
              Sending...
            </span>
            <span v-else>
              <i class="fas fa-paper-plane mr-2"></i>
              Send Request
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  recipient: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'request-sent'])

// Form state
const form = ref({
  message: '',
  proposed_times: [''],
  type: 'direct_request'
})

const submitting = ref(false)

// Computed
const minDateTime = computed(() => {
  const now = new Date()
  now.setHours(now.getHours() + 1) // Minimum 1 hour from now
  return now.toISOString().slice(0, 16)
})

const canSubmit = computed(() => {
  return form.value.proposed_times.some(time => time.trim() !== '') &&
         form.value.proposed_times.every(time => time === '' || new Date(time) > new Date())
})

// Methods
const addTimeSlot = () => {
  if (form.value.proposed_times.length < 5) {
    form.value.proposed_times.push('')
  }
}

const removeTimeSlot = (index) => {
  if (form.value.proposed_times.length > 1) {
    form.value.proposed_times.splice(index, 1)
  }
}

const sendRequest = async () => {
  if (!canSubmit.value || submitting.value) return
  
  submitting.value = true
  
  try {
    // Filter out empty time slots
    const proposedTimes = form.value.proposed_times.filter(time => time.trim() !== '')
    
    const requestData = {
      recipient_id: props.recipient.id,
      message: form.value.message.trim() || null,
      proposed_times: proposedTimes,
      type: form.value.type
    }
    
    await axios.post('/api/coffee-chat/request', requestData)
    
    emit('request-sent', props.recipient)
    
    // Show success message (you might want to use a toast notification)
    alert('Coffee chat request sent successfully!')
    
  } catch (error) {
    console.error('Error sending coffee chat request:', error)
    
    if (error.response?.data?.message) {
      alert(error.response.data.message)
    } else {
      alert('Failed to send coffee chat request. Please try again.')
    }
  } finally {
    submitting.value = false
  }
}

// Initialize with a default time slot
onMounted(() => {
  // Set default time to tomorrow at 2 PM
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  tomorrow.setHours(14, 0, 0, 0)
  
  form.value.proposed_times[0] = tomorrow.toISOString().slice(0, 16)
})
</script>