<template>
  <div class="coffee-chat-suggestions">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Coffee Chat Suggestions</h2>
      <p class="text-gray-600">Connect with fellow alumni for meaningful conversations</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
          <select
            v-model="filters.industry"
            @change="loadSuggestions"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Industries</option>
            <option value="technology">Technology</option>
            <option value="finance">Finance</option>
            <option value="healthcare">Healthcare</option>
            <option value="education">Education</option>
            <option value="consulting">Consulting</option>
            <option value="marketing">Marketing</option>
            <option value="other">Other</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
          <input
            v-model="filters.location"
            @input="debounceLoadSuggestions"
            type="text"
            placeholder="City, State or Country"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Interests</label>
          <input
            v-model="interestsInput"
            @input="handleInterestsInput"
            type="text"
            placeholder="Separate with commas"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Suggestions Grid -->
    <div v-else-if="suggestions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="suggestion in suggestions"
        :key="suggestion.id"
        class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow"
      >
        <div class="p-6">
          <!-- User Info -->
          <div class="flex items-center space-x-4 mb-4">
            <img
              :src="suggestion.avatar_url || '/default-avatar.png'"
              :alt="suggestion.name"
              class="w-12 h-12 rounded-full object-cover"
            >
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900">{{ suggestion.name }}</h3>
              <p class="text-sm text-gray-600">{{ suggestion.title }}</p>
              <p class="text-sm text-gray-500">{{ suggestion.company }}</p>
            </div>
          </div>

          <!-- Match Score -->
          <div class="mb-4">
            <div class="flex items-center justify-between mb-1">
              <span class="text-sm font-medium text-gray-700">Match Score</span>
              <span class="text-sm font-semibold text-blue-600">
                {{ Math.round(suggestion.matching_score * 100) }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${suggestion.matching_score * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- Details -->
          <div class="space-y-2 mb-4">
            <div v-if="suggestion.industry" class="flex items-center text-sm text-gray-600">
              <i class="fas fa-briefcase w-4 mr-2"></i>
              {{ suggestion.industry }}
            </div>
            <div v-if="suggestion.location" class="flex items-center text-sm text-gray-600">
              <i class="fas fa-map-marker-alt w-4 mr-2"></i>
              {{ suggestion.location }}
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <i class="fas fa-coffee w-4 mr-2"></i>
              {{ suggestion.coffee_chats_completed }} coffee chats completed
            </div>
          </div>

          <!-- Action Button -->
          <button
            @click="requestCoffeeChat(suggestion)"
            :disabled="requestingUsers.has(suggestion.id)"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            <span v-if="requestingUsers.has(suggestion.id)">
              <i class="fas fa-spinner fa-spin mr-2"></i>
              Sending Request...
            </span>
            <span v-else>
              <i class="fas fa-coffee mr-2"></i>
              Request Coffee Chat
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <i class="fas fa-coffee text-6xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No suggestions found</h3>
      <p class="text-gray-600 mb-4">Try adjusting your filters to find more alumni to connect with</p>
      <button
        @click="clearFilters"
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg"
      >
        Clear Filters
      </button>
    </div>

    <!-- Coffee Chat Request Modal -->
    <CoffeeChatRequestModal
      v-if="showRequestModal"
      :recipient="selectedRecipient"
      @close="closeRequestModal"
      @request-sent="handleRequestSent"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import CoffeeChatRequestModal from './CoffeeChatRequestModal.vue'

// State
const suggestions = ref([])
const loading = ref(false)
const requestingUsers = ref(new Set())
const showRequestModal = ref(false)
const selectedRecipient = ref(null)

// Filters
const filters = ref({
  industry: '',
  location: '',
  interests: []
})

const interestsInput = ref('')

// Methods
const loadSuggestions = async () => {
  loading.value = true
  
  try {
    const response = await axios.get('/api/coffee-chat/suggestions', {
      params: {
        industry: filters.value.industry || undefined,
        location: filters.value.location || undefined,
        interests: filters.value.interests.length > 0 ? filters.value.interests : undefined
      }
    })
    
    suggestions.value = response.data.data
  } catch (error) {
    console.error('Error loading suggestions:', error)
  } finally {
    loading.value = false
  }
}

const debounceLoadSuggestions = debounce(loadSuggestions, 500)

const handleInterestsInput = () => {
  filters.value.interests = interestsInput.value
    .split(',')
    .map(interest => interest.trim())
    .filter(interest => interest.length > 0)
  
  debounceLoadSuggestions()
}

const requestCoffeeChat = (recipient) => {
  selectedRecipient.value = recipient
  showRequestModal.value = true
}

const closeRequestModal = () => {
  showRequestModal.value = false
  selectedRecipient.value = null
}

const handleRequestSent = (recipient) => {
  // Remove the recipient from suggestions since request was sent
  suggestions.value = suggestions.value.filter(s => s.id !== recipient.id)
  closeRequestModal()
}

const clearFilters = () => {
  filters.value = {
    industry: '',
    location: '',
    interests: []
  }
  interestsInput.value = ''
  loadSuggestions()
}

// Lifecycle
onMounted(() => {
  loadSuggestions()
})
</script>

<style scoped>
.coffee-chat-suggestions {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>