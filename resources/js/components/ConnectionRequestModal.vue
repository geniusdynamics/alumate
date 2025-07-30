<template>
  <div class="connection-modal-overlay fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="handleOverlayClick">
    <div class="connection-modal relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
      <!-- Modal Header -->
      <div class="modal-header flex justify-between items-center pb-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
          Send Connection Request
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Alumni Info -->
      <div class="alumni-info py-4 border-b border-gray-200">
        <div class="flex items-center space-x-4">
          <img
            :src="alumni.avatar_url || '/images/default-avatar.png'"
            :alt="alumni.name"
            class="w-12 h-12 rounded-full object-cover border-2 border-gray-200"
          />
          <div>
            <h4 class="text-lg font-medium text-gray-900">{{ alumni.name }}</h4>
            <p v-if="alumni.current_position" class="text-sm text-gray-600">
              {{ alumni.current_position.title }}
              <span v-if="alumni.current_position.company">
                at {{ alumni.current_position.company }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- Connection Reasons -->
      <div v-if="connectionReasons.length > 0" class="connection-reasons py-4 border-b border-gray-200">
        <h5 class="text-sm font-medium text-gray-700 mb-3">Why connect with {{ alumni.name.split(' ')[0] }}?</h5>
        <div class="reasons-list space-y-2">
          <div
            v-for="reason in connectionReasons"
            :key="reason.type"
            class="reason-item flex items-center text-sm text-gray-600"
          >
            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ reason.text }}</span>
          </div>
        </div>
      </div>

      <!-- Message Form -->
      <div class="message-form py-4">
        <label for="connection-message" class="block text-sm font-medium text-gray-700 mb-2">
          Add a personal message (optional)
        </label>
        <textarea
          id="connection-message"
          v-model="message"
          rows="4"
          :placeholder="messagePlaceholder"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          :maxlength="500"
        ></textarea>
        <div class="text-right mt-1">
          <span class="text-xs text-gray-500">{{ message.length }}/500</span>
        </div>

        <!-- Suggested Messages -->
        <div v-if="suggestedMessages.length > 0" class="suggested-messages mt-3">
          <p class="text-xs text-gray-600 mb-2">Quick suggestions:</p>
          <div class="suggestions-grid grid grid-cols-1 gap-2">
            <button
              v-for="suggestion in suggestedMessages"
              :key="suggestion"
              @click="message = suggestion"
              class="text-left p-2 text-xs text-gray-700 bg-gray-50 rounded border hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            >
              "{{ suggestion }}"
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Actions -->
      <div class="modal-actions flex justify-end space-x-3 pt-4 border-t border-gray-200">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
        >
          Cancel
        </button>
        <button
          @click="sendRequest"
          :disabled="sending"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="sending" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sending...
          </span>
          <span v-else>Send Request</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'

export default {
  name: 'ConnectionRequestModal',
  props: {
    alumni: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'send'],
  setup(props, { emit }) {
    // Reactive data
    const message = ref('')
    const sending = ref(false)

    // Computed properties
    const messagePlaceholder = computed(() => {
      const firstName = props.alumni.name.split(' ')[0]
      return `Hi ${firstName}, I'd like to connect with you on our alumni platform...`
    })

    const connectionReasons = computed(() => {
      const reasons = []
      
      // Mutual connections
      if (props.alumni.mutual_connections && props.alumni.mutual_connections.length > 0) {
        const count = props.alumni.mutual_connections.length
        reasons.push({
          type: 'mutual_connections',
          text: `You have ${count} mutual connection${count !== 1 ? 's' : ''}`
        })
      }
      
      // Shared circles
      if (props.alumni.shared_circles && props.alumni.shared_circles.length > 0) {
        const circles = props.alumni.shared_circles.slice(0, 2).map(c => c.name).join(', ')
        reasons.push({
          type: 'shared_circles',
          text: `You're both in ${circles}${props.alumni.shared_circles.length > 2 ? ' and more' : ''}`
        })
      }
      
      // Shared groups
      if (props.alumni.shared_groups && props.alumni.shared_groups.length > 0) {
        const groups = props.alumni.shared_groups.slice(0, 2).map(g => g.name).join(', ')
        reasons.push({
          type: 'shared_groups',
          text: `You're both members of ${groups}${props.alumni.shared_groups.length > 2 ? ' and more' : ''}`
        })
      }
      
      // Same location
      if (props.alumni.location) {
        reasons.push({
          type: 'location',
          text: `You're both in ${props.alumni.location}`
        })
      }
      
      // Same industry (if available)
      if (props.alumni.current_position && props.alumni.current_position.industry) {
        reasons.push({
          type: 'industry',
          text: `You both work in ${props.alumni.current_position.industry}`
        })
      }
      
      return reasons.slice(0, 3) // Limit to 3 reasons
    })

    const suggestedMessages = computed(() => {
      const firstName = props.alumni.name.split(' ')[0]
      const suggestions = []
      
      // Basic connection message
      suggestions.push(`Hi ${firstName}, I'd like to connect with you on our alumni platform. Looking forward to staying in touch!`)
      
      // Mutual connections message
      if (props.alumni.mutual_connections && props.alumni.mutual_connections.length > 0) {
        const mutualName = props.alumni.mutual_connections[0].name
        suggestions.push(`Hi ${firstName}, I noticed we have ${mutualName} as a mutual connection. I'd love to connect and expand our network!`)
      }
      
      // Shared community message
      if (props.alumni.shared_circles && props.alumni.shared_circles.length > 0) {
        const circleName = props.alumni.shared_circles[0].name
        suggestions.push(`Hi ${firstName}, I see we're both part of ${circleName}. Would love to connect and share experiences!`)
      }
      
      // Professional interest message
      if (props.alumni.current_position) {
        suggestions.push(`Hi ${firstName}, I'm interested in your work at ${props.alumni.current_position.company}. Would love to connect and learn more about your experience!`)
      }
      
      return suggestions.slice(0, 3) // Limit to 3 suggestions
    })

    // Methods
    const sendRequest = async () => {
      if (sending.value) return
      
      sending.value = true
      
      try {
        await emit('send', message.value.trim())
      } catch (error) {
        console.error('Error sending connection request:', error)
      } finally {
        sending.value = false
      }
    }

    const handleOverlayClick = (event) => {
      if (event.target === event.currentTarget) {
        emit('close')
      }
    }

    // Handle escape key
    const handleKeydown = (event) => {
      if (event.key === 'Escape') {
        emit('close')
      }
    }

    // Lifecycle
    onMounted(() => {
      document.addEventListener('keydown', handleKeydown)
      
      // Focus on textarea when modal opens
      setTimeout(() => {
        const textarea = document.getElementById('connection-message')
        if (textarea) {
          textarea.focus()
        }
      }, 100)
    })

    // Cleanup
    const cleanup = () => {
      document.removeEventListener('keydown', handleKeydown)
    }

    return {
      message,
      sending,
      messagePlaceholder,
      connectionReasons,
      suggestedMessages,
      sendRequest,
      handleOverlayClick,
      cleanup
    }
  },
  beforeUnmount() {
    this.cleanup()
  }
}
</script>

<style scoped>
.connection-modal-overlay {
  backdrop-filter: blur(2px);
}

.connection-modal {
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.suggestions-grid button {
  transition: all 0.15s ease-in-out;
}

.suggestions-grid button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>