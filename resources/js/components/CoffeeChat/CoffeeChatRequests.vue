<template>
  <div class="coffee-chat-requests">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Coffee Chat Requests</h2>
      <p class="text-gray-600">Manage your incoming and outgoing coffee chat requests</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === tab.key
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ tab.label }}
          <span
            v-if="tab.count > 0"
            :class="[
              'ml-2 py-0.5 px-2 rounded-full text-xs',
              activeTab === tab.key
                ? 'bg-blue-100 text-blue-600'
                : 'bg-gray-100 text-gray-600'
            ]"
          >
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Requests List -->
    <div v-else-if="currentRequests.length > 0" class="space-y-4">
      <div
        v-for="request in currentRequests"
        :key="request.id"
        class="bg-white rounded-lg shadow-sm border p-6"
      >
        <div class="flex items-start justify-between">
          <!-- Request Info -->
          <div class="flex-1">
            <div class="flex items-center space-x-3 mb-3">
              <img
                :src="getRequestUser(request).avatar_url || '/default-avatar.png'"
                :alt="getRequestUser(request).name"
                class="w-10 h-10 rounded-full object-cover"
              >
              <div>
                <h3 class="font-semibold text-gray-900">
                  {{ getRequestUser(request).name }}
                </h3>
                <p class="text-sm text-gray-600">
                  {{ getRequestDirection(request) }} • {{ formatDate(request.created_at) }}
                </p>
              </div>
            </div>

            <!-- Message -->
            <div v-if="request.message" class="mb-4">
              <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">
                "{{ request.message }}"
              </p>
            </div>

            <!-- Proposed Times -->
            <div class="mb-4">
              <h4 class="text-sm font-medium text-gray-700 mb-2">Proposed Times:</h4>
              <div class="space-y-1">
                <div
                  v-for="(time, index) in request.proposed_times"
                  :key="index"
                  :class="[
                    'text-sm px-3 py-1 rounded-full inline-block mr-2 mb-1',
                    request.selected_time === time
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-700'
                  ]"
                >
                  {{ formatDateTime(time) }}
                  <i v-if="request.selected_time === time" class="fas fa-check ml-1"></i>
                </div>
              </div>
            </div>

            <!-- Status -->
            <div class="flex items-center space-x-4">
              <span
                :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  getStatusClass(request.status)
                ]"
              >
                {{ getStatusLabel(request.status) }}
              </span>
              
              <span v-if="request.type !== 'direct_request'" class="text-xs text-gray-500">
                {{ request.type === 'ai_matched' ? 'AI Matched' : 'Open Invitation' }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="ml-6 flex flex-col space-y-2">
            <!-- Pending Received Requests -->
            <template v-if="request.status === 'pending' && isReceivedRequest(request)">
              <button
                @click="showResponseModal(request)"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              >
                <i class="fas fa-reply mr-1"></i>
                Respond
              </button>
            </template>

            <!-- Accepted Requests -->
            <template v-else-if="request.status === 'accepted'">
              <button
                v-if="request.call"
                @click="joinCall(request.call)"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              >
                <i class="fas fa-video mr-1"></i>
                Join Call
              </button>
              
              <button
                @click="markAsCompleted(request)"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              >
                <i class="fas fa-check mr-1"></i>
                Mark Complete
              </button>
            </template>

            <!-- View Details -->
            <button
              @click="viewDetails(request)"
              class="text-gray-600 hover:text-gray-800 px-4 py-2 text-sm font-medium"
            >
              <i class="fas fa-eye mr-1"></i>
              View Details
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <i class="fas fa-coffee text-6xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ getEmptyStateMessage() }}
      </h3>
      <p class="text-gray-600 mb-4">
        {{ getEmptyStateDescription() }}
      </p>
      <router-link
        to="/coffee-chat/suggestions"
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center"
      >
        <i class="fas fa-search mr-2"></i>
        Find Alumni to Connect
      </router-link>
    </div>

    <!-- Response Modal -->
    <CoffeeChatResponseModal
      v-if="showResponseModalFlag"
      :request="selectedRequest"
      @close="closeResponseModal"
      @responded="handleResponse"
    />

    <!-- Details Modal -->
    <CoffeeChatDetailsModal
      v-if="showDetailsModalFlag"
      :request="selectedRequest"
      @close="closeDetailsModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import CoffeeChatResponseModal from './CoffeeChatResponseModal.vue'
import CoffeeChatDetailsModal from './CoffeeChatDetailsModal.vue'

const page = usePage()

// State
const activeTab = ref('received')
const requests = ref([])
const loading = ref(false)
const showResponseModalFlag = ref(false)
const showDetailsModalFlag = ref(false)
const selectedRequest = ref(null)

// Computed
const tabs = computed(() => [
  {
    key: 'received',
    label: 'Received',
    count: requests.value.filter(r => isReceivedRequest(r) && r.status === 'pending').length
  },
  {
    key: 'sent',
    label: 'Sent',
    count: requests.value.filter(r => !isReceivedRequest(r) && r.status === 'pending').length
  },
  {
    key: 'accepted',
    label: 'Accepted',
    count: requests.value.filter(r => r.status === 'accepted').length
  },
  {
    key: 'completed',
    label: 'Completed',
    count: requests.value.filter(r => r.status === 'completed').length
  }
])

const currentRequests = computed(() => {
  switch (activeTab.value) {
    case 'received':
      return requests.value.filter(r => isReceivedRequest(r) && r.status === 'pending')
    case 'sent':
      return requests.value.filter(r => !isReceivedRequest(r) && r.status === 'pending')
    case 'accepted':
      return requests.value.filter(r => r.status === 'accepted')
    case 'completed':
      return requests.value.filter(r => r.status === 'completed')
    default:
      return []
  }
})

// Methods
const loadRequests = async () => {
  loading.value = true
  
  try {
    const response = await axios.get('/api/coffee-chat/my-requests', {
      params: { type: 'all' }
    })
    
    requests.value = response.data.data
  } catch (error) {
    console.error('Error loading requests:', error)
  } finally {
    loading.value = false
  }
}

const isReceivedRequest = (request) => {
  return request.recipient?.id === page.props.auth.user.id
}

const getRequestUser = (request) => {
  return isReceivedRequest(request) ? request.requester : request.recipient
}

const getRequestDirection = (request) => {
  return isReceivedRequest(request) ? 'Received from' : 'Sent to'
}

const getStatusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'accepted':
      return 'bg-green-100 text-green-800'
    case 'declined':
      return 'bg-red-100 text-red-800'
    case 'completed':
      return 'bg-blue-100 text-blue-800'
    case 'expired':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'pending':
      return 'Pending'
    case 'accepted':
      return 'Accepted'
    case 'declined':
      return 'Declined'
    case 'completed':
      return 'Completed'
    case 'expired':
      return 'Expired'
    default:
      return status
  }
}

const getEmptyStateMessage = () => {
  switch (activeTab.value) {
    case 'received':
      return 'No pending requests'
    case 'sent':
      return 'No sent requests'
    case 'accepted':
      return 'No accepted requests'
    case 'completed':
      return 'No completed coffee chats'
    default:
      return 'No requests found'
  }
}

const getEmptyStateDescription = () => {
  switch (activeTab.value) {
    case 'received':
      return 'When alumni send you coffee chat requests, they\'ll appear here'
    case 'sent':
      return 'Coffee chat requests you\'ve sent will appear here'
    case 'accepted':
      return 'Accepted coffee chats ready to be scheduled will appear here'
    case 'completed':
      return 'Your completed coffee chat conversations will appear here'
    default:
      return ''
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString([], {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString([], {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const showResponseModal = (request) => {
  selectedRequest.value = request
  showResponseModalFlag.value = true
}

const closeResponseModal = () => {
  showResponseModalFlag.value = false
  selectedRequest.value = null
}

const handleResponse = () => {
  closeResponseModal()
  loadRequests() // Reload to get updated data
}

const viewDetails = (request) => {
  selectedRequest.value = request
  showDetailsModalFlag.value = true
}

const closeDetailsModal = () => {
  showDetailsModalFlag.value = false
  selectedRequest.value = null
}

const joinCall = (call) => {
  // Navigate to video call interface
  window.location.href = `/video-calls/${call.id}`
}

const markAsCompleted = async (request) => {
  try {
    await axios.post(`/api/coffee-chat/${request.id}/complete`)
    loadRequests()
  } catch (error) {
    console.error('Error marking request as completed:', error)
    alert('Failed to mark request as completed')
  }
}

// Lifecycle
onMounted(() => {
  loadRequests()
})
</script>

<style scoped>
.coffee-chat-requests {
  @apply max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>