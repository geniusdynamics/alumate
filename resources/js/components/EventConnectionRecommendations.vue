<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Networking Recommendations</h3>
        <p class="text-sm text-gray-600">Connect with fellow attendees who share your interests</p>
      </div>
      <button
        @click="generateRecommendations"
        :disabled="loading"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50"
      >
        {{ loading ? 'Generating...' : 'Refresh Recommendations' }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Recommendations List -->
    <div v-else-if="recommendations.length > 0" class="space-y-4">
      <div
        v-for="recommendation in recommendations"
        :key="recommendation.id"
        class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <!-- User Info -->
          <div class="flex items-start space-x-4 flex-1">
            <!-- Avatar -->
            <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
              <span class="text-lg font-semibold text-gray-600">
                {{ getInitials(recommendation.recommended_user.name) }}
              </span>
            </div>

            <!-- Details -->
            <div class="flex-1">
              <div class="flex items-center space-x-2 mb-2">
                <h4 class="text-lg font-semibold text-gray-900">
                  {{ recommendation.recommended_user.name }}
                </h4>
                <div class="flex items-center space-x-1">
                  <div
                    class="w-3 h-3 rounded-full"
                    :class="getMatchLevelColor(recommendation.match_score)"
                  ></div>
                  <span class="text-sm font-medium text-gray-600">
                    {{ recommendation.match_score }}% match
                  </span>
                </div>
              </div>

              <!-- User Details -->
              <div class="text-sm text-gray-600 space-y-1">
                <p v-if="recommendation.recommended_user.title">
                  {{ recommendation.recommended_user.title }}
                </p>
                <p v-if="recommendation.recommended_user.company">
                  {{ recommendation.recommended_user.company }}
                </p>
                <p v-if="recommendation.recommended_user.location">
                  📍 {{ recommendation.recommended_user.location }}
                </p>
              </div>

              <!-- Match Reasons -->
              <div class="mt-3">
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="reason in recommendation.match_reasons.slice(0, 3)"
                    :key="reason"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    {{ reason }}
                  </span>
                  <span
                    v-if="recommendation.match_reasons.length > 3"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
                  >
                    +{{ recommendation.match_reasons.length - 3 }} more
                  </span>
                </div>
              </div>

              <!-- Shared Attributes -->
              <div v-if="hasSharedAttributes(recommendation)" class="mt-3">
                <h5 class="text-sm font-medium text-gray-700 mb-2">You have in common:</h5>
                <div class="space-y-1 text-sm text-gray-600">
                  <p v-if="recommendation.shared_attributes.institution">
                    🎓 {{ recommendation.shared_attributes.institution }}
                  </p>
                  <p v-if="recommendation.shared_attributes.graduation_year">
                    📅 Class of {{ recommendation.shared_attributes.graduation_year }}
                  </p>
                  <p v-if="recommendation.shared_attributes.industry">
                    💼 {{ recommendation.shared_attributes.industry }}
                  </p>
                  <p v-if="recommendation.shared_attributes.location">
                    📍 {{ recommendation.shared_attributes.location }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col space-y-2 ml-4">
            <button
              @click="connectWithUser(recommendation)"
              :disabled="recommendation.status !== 'pending' && recommendation.status !== 'viewed'"
              class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ getConnectButtonText(recommendation.status) }}
            </button>
            
            <button
              @click="dismissRecommendation(recommendation)"
              :disabled="recommendation.status === 'dismissed'"
              class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 disabled:opacity-50"
            >
              {{ recommendation.status === 'dismissed' ? 'Dismissed' : 'Not interested' }}
            </button>

            <button
              @click="viewProfile(recommendation.recommended_user)"
              class="px-4 py-2 bg-blue-100 text-blue-700 text-sm font-medium rounded-md hover:bg-blue-200"
            >
              View Profile
            </button>
          </div>
        </div>

        <!-- Status Indicator -->
        <div v-if="recommendation.status !== 'pending'" class="mt-4 pt-4 border-t border-gray-100">
          <div class="flex items-center text-sm">
            <div
              class="w-2 h-2 rounded-full mr-2"
              :class="getStatusColor(recommendation.status)"
            ></div>
            <span class="text-gray-600">
              {{ getStatusText(recommendation.status) }}
              <span v-if="recommendation.acted_on_at" class="text-gray-500">
                on {{ formatDate(recommendation.acted_on_at) }}
              </span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No recommendations available</h3>
      <p class="mt-1 text-sm text-gray-500">
        We'll generate personalized recommendations based on your profile and event attendance.
      </p>
      <button
        @click="generateRecommendations"
        class="mt-4 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
      >
        Generate Recommendations
      </button>
    </div>

    <!-- Connection Modal -->
    <EventConnectionModal
      :show="showConnectionModal"
      :event="event"
      :recommended-user="selectedUser"
      @close="showConnectionModal = false"
      @connected="onConnectionCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import EventConnectionModal from './EventConnectionModal.vue'

interface Props {
  event: any
}

const props = defineProps<Props>()

const loading = ref(false)
const recommendations = ref([])
const showConnectionModal = ref(false)
const selectedUser = ref(null)

const loadRecommendations = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/events/${props.event.id}/recommendations`)
    recommendations.value = response.data
    
    // Mark recommendations as viewed
    for (const rec of recommendations.value) {
      if (rec.status === 'pending') {
        markAsViewed(rec)
      }
    }
  } catch (error) {
    console.error('Failed to load recommendations:', error)
  } finally {
    loading.value = false
  }
}

const generateRecommendations = async () => {
  loading.value = true
  try {
    await axios.post(`/api/events/${props.event.id}/generate-recommendations`)
    await loadRecommendations()
  } catch (error) {
    console.error('Failed to generate recommendations:', error)
    alert('Failed to generate recommendations. Please try again.')
  } finally {
    loading.value = false
  }
}

const markAsViewed = async (recommendation: any) => {
  try {
    await axios.post(`/api/recommendations/${recommendation.id}/viewed`)
    recommendation.status = 'viewed'
  } catch (error) {
    console.error('Failed to mark as viewed:', error)
  }
}

const connectWithUser = (recommendation: any) => {
  selectedUser.value = recommendation.recommended_user
  showConnectionModal.value = true
}

const dismissRecommendation = async (recommendation: any) => {
  try {
    await axios.post(`/api/recommendations/${recommendation.id}/act`, {
      action: 'dismiss'
    })
    recommendation.status = 'dismissed'
    recommendation.acted_on_at = new Date().toISOString()
  } catch (error) {
    console.error('Failed to dismiss recommendation:', error)
  }
}

const viewProfile = (user: any) => {
  // Navigate to user profile or open profile modal
  window.open(`/alumni/${user.id}`, '_blank')
}

const onConnectionCreated = async (recommendation: any) => {
  // Mark recommendation as connected
  try {
    await axios.post(`/api/recommendations/${recommendation.id}/act`, {
      action: 'connect'
    })
    
    // Find and update the recommendation in our list
    const rec = recommendations.value.find(r => r.recommended_user.id === recommendation.recommended_user.id)
    if (rec) {
      rec.status = 'connected'
      rec.acted_on_at = new Date().toISOString()
    }
  } catch (error) {
    console.error('Failed to update recommendation status:', error)
  }
}

const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getMatchLevelColor = (score: number) => {
  if (score >= 90) return 'bg-green-500'
  if (score >= 80) return 'bg-blue-500'
  if (score >= 70) return 'bg-yellow-500'
  if (score >= 60) return 'bg-orange-500'
  return 'bg-red-500'
}

const getConnectButtonText = (status: string) => {
  switch (status) {
    case 'connected': return 'Connected'
    case 'dismissed': return 'Dismissed'
    default: return 'Connect'
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'connected': return 'bg-green-500'
    case 'dismissed': return 'bg-gray-500'
    case 'viewed': return 'bg-blue-500'
    default: return 'bg-gray-300'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'connected': return 'Connected'
    case 'dismissed': return 'Dismissed'
    case 'viewed': return 'Viewed'
    default: return 'Pending'
  }
}

const hasSharedAttributes = (recommendation: any) => {
  const attrs = recommendation.shared_attributes || {}
  return Object.keys(attrs).length > 0
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  loadRecommendations()
})
</script>