<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Mentorship Dashboard</h1>
      <p class="mt-2 text-gray-600">Manage your mentorships and upcoming sessions</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <UserGroupIcon class="h-6 w-6 text-blue-600" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Active Mentorships</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.active_mentorships }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CalendarIcon class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Sessions</dt>
                <dd class="text-lg font-medium text-gray-900">{{ upcomingSessions.length }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="h-6 w-6 text-purple-600" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Completed Sessions</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.completed_sessions }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <StarIcon class="h-6 w-6 text-yellow-600" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Average Rating</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.average_session_rating || 'N/A' }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <nav class="flex space-x-8" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="bg-white shadow rounded-lg">
      <!-- Upcoming Sessions Tab -->
      <div v-if="activeTab === 'sessions'" class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Upcoming Sessions</h3>
          <button
            @click="showScheduler = true"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Schedule Session
          </button>
        </div>

        <div v-if="upcomingSessions.length === 0" class="text-center py-8">
          <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming sessions</h3>
          <p class="mt-1 text-sm text-gray-500">Schedule a session to get started.</p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="session in upcomingSessions"
            :key="session.id"
            class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <img
                    :src="getOtherUser(session).avatar_url || '/default-avatar.png'"
                    :alt="getOtherUser(session).name"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div>
                    <h4 class="text-sm font-medium text-gray-900">
                      {{ getOtherUser(session).name }}
                    </h4>
                    <p class="text-sm text-gray-500">
                      {{ isUserMentor(session) ? 'Mentee' : 'Mentor' }}
                    </p>
                  </div>
                </div>
                <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500">
                  <div class="flex items-center">
                    <CalendarIcon class="w-4 h-4 mr-1" />
                    {{ formatDate(session.scheduled_at) }}
                  </div>
                  <div class="flex items-center">
                    <ClockIcon class="w-4 h-4 mr-1" />
                    {{ session.duration }} minutes
                  </div>
                </div>
                <p v-if="session.notes" class="mt-2 text-sm text-gray-600">
                  {{ session.notes }}
                </p>
              </div>
              <div class="flex space-x-2">
                <button
                  @click="editSession(session)"
                  class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  Edit
                </button>
                <button
                  @click="cancelSession(session)"
                  class="text-red-600 hover:text-red-800 text-sm font-medium"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- As Mentor Tab -->
      <div v-if="activeTab === 'as-mentor'" class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Mentorships (As Mentor)</h3>
        
        <div v-if="mentorships.as_mentor.length === 0" class="text-center py-8">
          <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No mentorships yet</h3>
          <p class="mt-1 text-sm text-gray-500">You haven't accepted any mentees yet.</p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="mentorship in mentorships.as_mentor"
            :key="mentorship.id"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex justify-between items-start">
              <div class="flex items-center space-x-3">
                <img
                  :src="mentorship.mentee.avatar_url || '/default-avatar.png'"
                  :alt="mentorship.mentee.name"
                  class="w-12 h-12 rounded-full object-cover"
                />
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ mentorship.mentee.name }}</h4>
                  <p class="text-sm text-gray-500">{{ mentorship.mentee.title || 'Alumni' }}</p>
                  <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                    <span>Started {{ formatDate(mentorship.created_at) }}</span>
                    <span class="capitalize">{{ mentorship.status }}</span>
                  </div>
                </div>
              </div>
              <div class="flex space-x-2">
                <button
                  v-if="mentorship.status === 'pending'"
                  @click="acceptRequest(mentorship.id)"
                  class="text-green-600 hover:text-green-800 text-sm font-medium"
                >
                  Accept
                </button>
                <button
                  v-if="mentorship.status === 'pending'"
                  @click="declineRequest(mentorship.id)"
                  class="text-red-600 hover:text-red-800 text-sm font-medium"
                >
                  Decline
                </button>
                <button
                  v-if="mentorship.status === 'accepted'"
                  @click="scheduleWithMentorship(mentorship)"
                  class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  Schedule Session
                </button>
              </div>
            </div>
            <p v-if="mentorship.message" class="mt-3 text-sm text-gray-600">
              "{{ mentorship.message }}"
            </p>
            <p v-if="mentorship.goals" class="mt-2 text-sm text-gray-600">
              <strong>Goals:</strong> {{ mentorship.goals }}
            </p>
          </div>
        </div>
      </div>

      <!-- As Mentee Tab -->
      <div v-if="activeTab === 'as-mentee'" class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Mentorships (As Mentee)</h3>
        
        <div v-if="mentorships.as_mentee.length === 0" class="text-center py-8">
          <UserIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No mentorships yet</h3>
          <p class="mt-1 text-sm text-gray-500">Find a mentor to get started.</p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="mentorship in mentorships.as_mentee"
            :key="mentorship.id"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex justify-between items-start">
              <div class="flex items-center space-x-3">
                <img
                  :src="mentorship.mentor.avatar_url || '/default-avatar.png'"
                  :alt="mentorship.mentor.name"
                  class="w-12 h-12 rounded-full object-cover"
                />
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ mentorship.mentor.name }}</h4>
                  <p class="text-sm text-gray-500">{{ mentorship.mentor.title || 'Mentor' }}</p>
                  <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                    <span>Requested {{ formatDate(mentorship.created_at) }}</span>
                    <span class="capitalize">{{ mentorship.status }}</span>
                  </div>
                </div>
              </div>
              <div class="flex space-x-2">
                <button
                  v-if="mentorship.status === 'accepted'"
                  @click="scheduleWithMentorship(mentorship)"
                  class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  Schedule Session
                </button>
              </div>
            </div>
            <p v-if="mentorship.goals" class="mt-3 text-sm text-gray-600">
              <strong>Goals:</strong> {{ mentorship.goals }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Session Scheduler Modal -->
    <SessionScheduler
      v-if="showScheduler"
      :mentorships="availableMentorships"
      @close="showScheduler = false"
      @sessionScheduled="onSessionScheduled"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  UserGroupIcon,
  CalendarIcon,
  CheckCircleIcon,
  StarIcon,
  PlusIcon,
  ClockIcon,
  UserIcon
} from '@heroicons/vue/24/outline'
import SessionScheduler from './SessionScheduler.vue'
import axios from 'axios'

// Reactive data
const activeTab = ref('sessions')
const mentorships = ref({ as_mentor: [], as_mentee: [] })
const upcomingSessions = ref([])
const stats = ref({})
const showScheduler = ref(false)
const loading = ref(false)

// Computed properties
const tabs = computed(() => [
  { id: 'sessions', name: 'Upcoming Sessions' },
  { id: 'as-mentor', name: 'As Mentor' },
  { id: 'as-mentee', name: 'As Mentee' }
])

const availableMentorships = computed(() => {
  return [
    ...mentorships.value.as_mentor.filter(m => m.status === 'accepted'),
    ...mentorships.value.as_mentee.filter(m => m.status === 'accepted')
  ]
})

// Methods
const loadData = async () => {
  loading.value = true
  try {
    const [mentorshipsRes, sessionsRes, analyticsRes] = await Promise.all([
      axios.get('/api/mentorships'),
      axios.get('/api/mentorships/sessions/upcoming'),
      axios.get('/api/mentorships/analytics')
    ])

    mentorships.value = mentorshipsRes.data
    upcomingSessions.value = sessionsRes.data.sessions
    stats.value = analyticsRes.data.analytics
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const acceptRequest = async (requestId) => {
  try {
    await axios.post(`/api/mentorships/requests/${requestId}/accept`)
    await loadData() // Refresh data
  } catch (error) {
    console.error('Failed to accept request:', error)
  }
}

const declineRequest = async (requestId) => {
  try {
    await axios.post(`/api/mentorships/requests/${requestId}/decline`)
    await loadData() // Refresh data
  } catch (error) {
    console.error('Failed to decline request:', error)
  }
}

const scheduleWithMentorship = (mentorship) => {
  showScheduler.value = true
}

const onSessionScheduled = (session) => {
  upcomingSessions.value.push(session)
  showScheduler.value = false
}

const getOtherUser = (session) => {
  const currentUserId = window.Laravel?.user?.id
  return session.mentorship.mentor.id === currentUserId 
    ? session.mentorship.mentee 
    : session.mentorship.mentor
}

const isUserMentor = (session) => {
  const currentUserId = window.Laravel?.user?.id
  return session.mentorship.mentor.id === currentUserId
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const editSession = (session) => {
  // TODO: Implement session editing
  console.log('Edit session:', session)
}

const cancelSession = async (session) => {
  if (confirm('Are you sure you want to cancel this session?')) {
    try {
      // TODO: Implement session cancellation API
      console.log('Cancel session:', session)
    } catch (error) {
      console.error('Failed to cancel session:', error)
    }
  }
}

// Lifecycle
onMounted(() => {
  loadData()
})
</script>