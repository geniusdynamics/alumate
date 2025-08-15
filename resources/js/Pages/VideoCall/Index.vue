<template>
  <div class="video-calls-index">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Video Calls</h1>
              <p class="mt-2 text-gray-600">Connect with fellow alumni through video conversations</p>
            </div>
            <div class="flex items-center space-x-4">
              <button
                @click="showScheduleModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
              >
                <VideoCameraIcon class="h-4 w-4 mr-2" />
                Schedule Call
              </button>
              <button
                @click="showCoffeeChatModal = true"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                <CoffeeIcon class="h-4 w-4 mr-2" />
                Coffee Chat
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === tab.key
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ tab.label }}
            <span v-if="tab.count" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Upcoming Calls -->
      <div v-if="activeTab === 'upcoming'" class="space-y-6">
        <div v-if="loading" class="space-y-4">
          <CallSkeleton v-for="i in 3" :key="i" />
        </div>
        
        <div v-else-if="upcomingCalls.length === 0" class="text-center py-12">
          <VideoCameraIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming calls</h3>
          <p class="mt-1 text-sm text-gray-500">Schedule your first video call or coffee chat.</p>
        </div>

        <div v-else class="space-y-4">
          <CallCard
            v-for="call in upcomingCalls"
            :key="call.id"
            :call="call"
            @join="joinCall"
            @edit="editCall"
            @cancel="cancelCall"
          />
        </div>
      </div>

      <!-- Active Calls -->
      <div v-if="activeTab === 'active'" class="space-y-6">
        <div v-if="activeCalls.length === 0" class="text-center py-12">
          <VideoCameraIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No active calls</h3>
          <p class="mt-1 text-sm text-gray-500">Join an existing call or start a new one.</p>
        </div>

        <div v-else class="space-y-4">
          <CallCard
            v-for="call in activeCalls"
            :key="call.id"
            :call="call"
            :is-active="true"
            @join="joinCall"
            @leave="leaveCall"
            @end="endCall"
          />
        </div>
      </div>

      <!-- Coffee Chat Requests -->
      <div v-if="activeTab === 'coffee-chats'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Received Requests -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Received Requests</h3>
            <div v-if="receivedRequests.length === 0" class="text-center py-8">
              <CoffeeIcon class="mx-auto h-8 w-8 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">No coffee chat requests received</p>
            </div>
            <div v-else class="space-y-3">
              <CoffeeChatRequestCard
                v-for="request in receivedRequests"
                :key="request.id"
                :request="request"
                type="received"
                @respond="respondToRequest"
              />
            </div>
          </div>

          <!-- Sent Requests -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Sent Requests</h3>
            <div v-if="sentRequests.length === 0" class="text-center py-8">
              <CoffeeIcon class="mx-auto h-8 w-8 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">No coffee chat requests sent</p>
            </div>
            <div v-else class="space-y-3">
              <CoffeeChatRequestCard
                v-for="request in sentRequests"
                :key="request.id"
                :request="request"
                type="sent"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Call History -->
      <div v-if="activeTab === 'history'" class="space-y-6">
        <div v-if="callHistory.length === 0" class="text-center py-12">
          <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No call history</h3>
          <p class="mt-1 text-sm text-gray-500">Your completed calls will appear here.</p>
        </div>

        <div v-else class="space-y-4">
          <CallCard
            v-for="call in callHistory"
            :key="call.id"
            :call="call"
            :is-history="true"
            @view-analytics="viewAnalytics"
            @view-recording="viewRecording"
          />
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ScheduleCallModal
      v-if="showScheduleModal"
      @close="showScheduleModal = false"
      @scheduled="handleCallScheduled"
    />

    <CoffeeChatModal
      v-if="showCoffeeChatModal"
      @close="showCoffeeChatModal = false"
      @requested="handleCoffeeChatRequested"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  VideoCameraIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

import CallCard from '@/Components/VideoCall/CallCard.vue'
import CallSkeleton from '@/Components/VideoCall/CallSkeleton.vue'
import CoffeeChatRequestCard from '@/Components/VideoCall/CoffeeChatRequestCard.vue'
import ScheduleCallModal from '@/Components/VideoCall/ScheduleCallModal.vue'
import CoffeeChatModal from '@/Components/VideoCall/CoffeeChatModal.vue'

// Custom coffee icon component
const CoffeeIcon = {
  template: '<span class="text-2xl">☕</span>'
}

// Props
const props = defineProps({
  user: Object,
})

// Reactive data
const activeTab = ref('upcoming')
const loading = ref(true)
const calls = ref([])
const coffeeChatRequests = ref([])
const showScheduleModal = ref(false)
const showCoffeeChatModal = ref(false)

// Computed
const tabs = computed(() => [
  { key: 'upcoming', label: 'Upcoming', count: upcomingCalls.value.length },
  { key: 'active', label: 'Active', count: activeCalls.value.length },
  { key: 'coffee-chats', label: 'Coffee Chats', count: pendingRequests.value.length },
  { key: 'history', label: 'History', count: null },
])

const upcomingCalls = computed(() => {
  return calls.value.filter(call => call.status === 'scheduled')
})

const activeCalls = computed(() => {
  return calls.value.filter(call => call.status === 'active')
})

const callHistory = computed(() => {
  return calls.value.filter(call => ['ended', 'cancelled'].includes(call.status))
})

const receivedRequests = computed(() => {
  return coffeeChatRequests.value.filter(req => 
    req.recipient_id === props.user?.id && req.status === 'pending'
  )
})

const sentRequests = computed(() => {
  return coffeeChatRequests.value.filter(req => 
    req.requester_id === props.user?.id
  )
})

const pendingRequests = computed(() => {
  return coffeeChatRequests.value.filter(req => req.status === 'pending')
})

// Methods
const loadCalls = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/video-calls')
    const data = await response.json()
    
    if (data.success) {
      calls.value = data.data
    }
  } catch (error) {
    console.error('Error loading calls:', error)
  } finally {
    loading.value = false
  }
}

const loadCoffeeChatRequests = async () => {
  try {
    const [receivedResponse, sentResponse] = await Promise.all([
      fetch('/api/coffee-chats/received-requests'),
      fetch('/api/coffee-chats/my-requests?type=sent')
    ])
    
    const [receivedData, sentData] = await Promise.all([
      receivedResponse.json(),
      sentResponse.json()
    ])
    
    if (receivedData.success && sentData.success) {
      coffeeChatRequests.value = [...receivedData.data, ...sentData.data]
    }
  } catch (error) {
    console.error('Error loading coffee chat requests:', error)
  }
}

const joinCall = (call) => {
  router.visit(`/video-calls/${call.id}/join`)
}

const editCall = (call) => {
  // Open edit modal or navigate to edit page
  console.log('Edit call:', call)
}

const cancelCall = async (call) => {
  if (confirm('Are you sure you want to cancel this call?')) {
    try {
      const response = await fetch(`/api/video-calls/${call.id}`, {
        method: 'DELETE',
      })
      
      if (response.ok) {
        calls.value = calls.value.filter(c => c.id !== call.id)
      }
    } catch (error) {
      console.error('Error cancelling call:', error)
    }
  }
}

const leaveCall = async (call) => {
  try {
    const response = await fetch(`/api/video-calls/${call.id}/leave`, {
      method: 'POST',
    })
    
    if (response.ok) {
      loadCalls() // Refresh the calls list
    }
  } catch (error) {
    console.error('Error leaving call:', error)
  }
}

const endCall = async (call) => {
  if (confirm('Are you sure you want to end this call for all participants?')) {
    try {
      const response = await fetch(`/api/video-calls/${call.id}/end`, {
        method: 'POST',
      })
      
      if (response.ok) {
        loadCalls() // Refresh the calls list
      }
    } catch (error) {
      console.error('Error ending call:', error)
    }
  }
}

const respondToRequest = async (request, action, selectedTime = null) => {
  try {
    const response = await fetch(`/api/coffee-chats/${request.id}/respond`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action,
        selected_time: selectedTime,
      }),
    })
    
    const data = await response.json()
    
    if (data.success) {
      loadCoffeeChatRequests() // Refresh requests
      if (action === 'accept') {
        loadCalls() // Refresh calls to show the new scheduled call
      }
    }
  } catch (error) {
    console.error('Error responding to request:', error)
  }
}

const handleCallScheduled = (call) => {
  calls.value.unshift(call)
  showScheduleModal.value = false
}

const handleCoffeeChatRequested = (request) => {
  coffeeChatRequests.value.unshift(request)
  showCoffeeChatModal.value = false
}

const viewAnalytics = (call) => {
  router.visit(`/video-calls/${call.id}/analytics`)
}

const viewRecording = (call) => {
  router.visit(`/video-calls/${call.id}/recordings`)
}

// Lifecycle
onMounted(() => {
  loadCalls()
  loadCoffeeChatRequests()
})
</script>

<style scoped>
.video-calls-index {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>