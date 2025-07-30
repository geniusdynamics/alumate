<template>
  <div class="people-you-may-know">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-900">People You May Know</h2>
      <button
        @click="refreshRecommendations"
        :disabled="loading"
        class="text-sm text-blue-600 hover:text-blue-800 disabled:opacity-50"
      >
        <Icon name="refresh" class="w-4 h-4 inline mr-1" />
        Refresh
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="recommendations.length === 0" class="text-center py-8 text-gray-500">
      <Icon name="users" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
      <p>No new recommendations available right now.</p>
      <p class="text-sm mt-2">Check back later for new suggestions!</p>
    </div>

    <div v-else class="space-y-4">
      <!-- Carousel for larger screens -->
      <div class="hidden md:block">
        <div class="relative">
          <div 
            ref="carousel"
            class="flex space-x-4 overflow-x-auto scrollbar-hide pb-4"
            style="scroll-behavior: smooth;"
          >
            <div
              v-for="recommendation in recommendations"
              :key="recommendation.user.id"
              class="flex-shrink-0 w-80"
            >
              <RecommendationCard
                :recommendation="recommendation"
                @connect="handleConnect"
                @dismiss="handleDismiss"
                @feedback="handleFeedback"
              />
            </div>
          </div>
          
          <!-- Navigation arrows -->
          <button
            v-if="canScrollLeft"
            @click="scrollLeft"
            class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 bg-white rounded-full shadow-lg p-2 hover:bg-gray-50"
          >
            <Icon name="chevron-left" class="w-5 h-5 text-gray-600" />
          </button>
          
          <button
            v-if="canScrollRight"
            @click="scrollRight"
            class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 bg-white rounded-full shadow-lg p-2 hover:bg-gray-50"
          >
            <Icon name="chevron-right" class="w-5 h-5 text-gray-600" />
          </button>
        </div>
      </div>

      <!-- Stack for mobile -->
      <div class="md:hidden space-y-4">
        <RecommendationCard
          v-for="recommendation in recommendations.slice(0, 3)"
          :key="recommendation.user.id"
          :recommendation="recommendation"
          @connect="handleConnect"
          @dismiss="handleDismiss"
          @feedback="handleFeedback"
        />
        
        <button
          v-if="recommendations.length > 3"
          @click="showAll = !showAll"
          class="w-full text-center py-2 text-blue-600 hover:text-blue-800"
        >
          {{ showAll ? 'Show Less' : `Show ${recommendations.length - 3} More` }}
        </button>
        
        <div v-if="showAll" class="space-y-4">
          <RecommendationCard
            v-for="recommendation in recommendations.slice(3)"
            :key="recommendation.user.id"
            :recommendation="recommendation"
            @connect="handleConnect"
            @dismiss="handleDismiss"
            @feedback="handleFeedback"
          />
        </div>
      </div>

      <!-- Bulk actions -->
      <div v-if="selectedRecommendations.length > 0" class="mt-6 p-4 bg-blue-50 rounded-lg">
        <div class="flex items-center justify-between">
          <span class="text-sm text-blue-800">
            {{ selectedRecommendations.length }} selected
          </span>
          <div class="space-x-2">
            <button
              @click="bulkConnect"
              :disabled="bulkActionLoading"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 text-sm"
            >
              Connect All
            </button>
            <button
              @click="clearSelection"
              class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 text-sm"
            >
              Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Feedback Modal -->
    <div v-if="showFeedbackModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Help us improve recommendations</h3>
        <p class="text-gray-600 mb-4">
          Why wasn't this recommendation helpful?
        </p>
        
        <div class="space-y-2 mb-4">
          <label class="flex items-center">
            <input
              v-model="feedbackReason"
              type="radio"
              value="not_relevant"
              class="mr-2"
            />
            Not relevant to my interests
          </label>
          <label class="flex items-center">
            <input
              v-model="feedbackReason"
              type="radio"
              value="already_know"
              class="mr-2"
            />
            I already know this person
          </label>
          <label class="flex items-center">
            <input
              v-model="feedbackReason"
              type="radio"
              value="not_interested"
              class="mr-2"
            />
            Not interested in connecting
          </label>
          <label class="flex items-center">
            <input
              v-model="feedbackReason"
              type="radio"
              value="other"
              class="mr-2"
            />
            Other
          </label>
        </div>
        
        <div class="flex justify-end space-x-2">
          <button
            @click="closeFeedbackModal"
            class="px-4 py-2 text-gray-600 hover:text-gray-800"
          >
            Cancel
          </button>
          <button
            @click="submitFeedback"
            :disabled="!feedbackReason"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            Submit
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import RecommendationCard from './RecommendationCard.vue'
import Icon from './Icon.vue'

const recommendations = ref([])
const loading = ref(false)
const showAll = ref(false)
const selectedRecommendations = ref([])
const bulkActionLoading = ref(false)
const carousel = ref(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

// Feedback modal
const showFeedbackModal = ref(false)
const feedbackReason = ref('')
const feedbackRecommendation = ref(null)

const emit = defineEmits(['recommendationConnected', 'recommendationDismissed'])

onMounted(() => {
  loadRecommendations()
  checkScrollButtons()
})

const loadRecommendations = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/recommendations')
    const data = await response.json()
    recommendations.value = data.data || []
  } catch (error) {
    console.error('Failed to load recommendations:', error)
  } finally {
    loading.value = false
  }
}

const refreshRecommendations = async () => {
  await loadRecommendations()
  await nextTick()
  checkScrollButtons()
}

const handleConnect = async (recommendation) => {
  try {
    const response = await fetch(`/api/connections`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        user_id: recommendation.user.id,
        message: `Hi ${recommendation.user.name}, I'd like to connect with you through our alumni network!`
      })
    })

    if (response.ok) {
      // Remove from recommendations
      recommendations.value = recommendations.value.filter(r => r.user.id !== recommendation.user.id)
      emit('recommendationConnected', recommendation)
      
      // Show success message
      router.visit(window.location.pathname, {
        preserveState: true,
        only: ['flash']
      })
    }
  } catch (error) {
    console.error('Failed to send connection request:', error)
  }
}

const handleDismiss = async (recommendation) => {
  try {
    const response = await fetch(`/api/recommendations/${recommendation.user.id}/dismiss`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      recommendations.value = recommendations.value.filter(r => r.user.id !== recommendation.user.id)
      emit('recommendationDismissed', recommendation)
    }
  } catch (error) {
    console.error('Failed to dismiss recommendation:', error)
  }
}

const handleFeedback = (recommendation) => {
  feedbackRecommendation.value = recommendation
  showFeedbackModal.value = true
}

const closeFeedbackModal = () => {
  showFeedbackModal.value = false
  feedbackReason.value = ''
  feedbackRecommendation.value = null
}

const submitFeedback = async () => {
  if (!feedbackReason.value || !feedbackRecommendation.value) return

  try {
    await fetch(`/api/recommendations/${feedbackRecommendation.value.user.id}/feedback`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        reason: feedbackReason.value
      })
    })

    // Dismiss the recommendation after feedback
    await handleDismiss(feedbackRecommendation.value)
    closeFeedbackModal()
  } catch (error) {
    console.error('Failed to submit feedback:', error)
  }
}

const bulkConnect = async () => {
  if (selectedRecommendations.value.length === 0) return

  bulkActionLoading.value = true
  try {
    const promises = selectedRecommendations.value.map(rec => handleConnect(rec))
    await Promise.all(promises)
    selectedRecommendations.value = []
  } catch (error) {
    console.error('Failed to send bulk connection requests:', error)
  } finally {
    bulkActionLoading.value = false
  }
}

const clearSelection = () => {
  selectedRecommendations.value = []
}

const scrollLeft = () => {
  if (carousel.value) {
    carousel.value.scrollBy({ left: -320, behavior: 'smooth' })
    setTimeout(checkScrollButtons, 300)
  }
}

const scrollRight = () => {
  if (carousel.value) {
    carousel.value.scrollBy({ left: 320, behavior: 'smooth' })
    setTimeout(checkScrollButtons, 300)
  }
}

const checkScrollButtons = () => {
  if (carousel.value) {
    canScrollLeft.value = carousel.value.scrollLeft > 0
    canScrollRight.value = 
      carousel.value.scrollLeft < carousel.value.scrollWidth - carousel.value.clientWidth
  }
}
</script>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>