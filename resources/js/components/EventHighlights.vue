<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-900">Event Highlights</h3>
      <div class="flex space-x-2">
        <select
          v-model="filters.type"
          @change="loadHighlights"
          class="px-3 py-2 border border-gray-300 rounded-md text-sm"
        >
          <option value="">All Types</option>
          <option value="photo">Photos</option>
          <option value="video">Videos</option>
          <option value="quote">Quotes</option>
          <option value="moment">Moments</option>
          <option value="achievement">Achievements</option>
        </select>
        <select
          v-model="filters.sort_by"
          @change="loadHighlights"
          class="px-3 py-2 border border-gray-300 rounded-md text-sm"
        >
          <option value="recent">Most Recent</option>
          <option value="popular">Most Popular</option>
          <option value="featured">Featured First</option>
        </select>
        <button
          v-if="canCreateHighlight"
          @click="showCreateModal = true"
          class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
        >
          Add Highlight
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Highlights Grid -->
    <div v-else-if="highlights.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="highlight in highlights"
        :key="highlight.id"
        class="bg-white rounded-lg shadow-md overflow-hidden"
        :class="{ 'ring-2 ring-yellow-400': highlight.is_featured }"
      >
        <!-- Featured Badge -->
        <div v-if="highlight.is_featured" class="bg-yellow-400 text-yellow-900 text-xs font-medium px-2 py-1">
          ⭐ Featured
        </div>

        <!-- Media -->
        <div v-if="highlight.media_urls && highlight.media_urls.length > 0" class="aspect-w-16 aspect-h-9">
          <img
            v-if="highlight.type === 'photo'"
            :src="highlight.media_urls[0]"
            :alt="highlight.title"
            class="w-full h-48 object-cover"
          >
          <video
            v-else-if="highlight.type === 'video'"
            :src="highlight.media_urls[0]"
            class="w-full h-48 object-cover"
            controls
          ></video>
        </div>

        <!-- Content -->
        <div class="p-4">
          <div class="flex items-start justify-between mb-2">
            <h4 class="text-lg font-semibold text-gray-900">{{ highlight.title }}</h4>
            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
              {{ getTypeLabel(highlight.type) }}
            </span>
          </div>

          <p v-if="highlight.description" class="text-gray-600 text-sm mb-3">
            {{ highlight.description }}
          </p>

          <!-- Creator Info -->
          <div class="flex items-center text-sm text-gray-500 mb-3">
            <div class="w-6 h-6 bg-gray-300 rounded-full mr-2"></div>
            <span>{{ highlight.creator?.name || 'Anonymous' }}</span>
            <span class="mx-2">•</span>
            <span>{{ formatDate(highlight.created_at) }}</span>
          </div>

          <!-- Engagement -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <button
                @click="toggleLike(highlight)"
                class="flex items-center space-x-1 text-sm"
                :class="highlight.user_liked ? 'text-red-600' : 'text-gray-500 hover:text-red-600'"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ highlight.likes_count }}</span>
              </button>

              <button
                @click="shareHighlight(highlight)"
                class="flex items-center space-x-1 text-sm text-gray-500 hover:text-blue-600"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                </svg>
                <span>{{ highlight.shares_count }}</span>
              </button>

              <button
                @click="showComments(highlight)"
                class="flex items-center space-x-1 text-sm text-gray-500 hover:text-green-600"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <span>{{ getCommentsCount(highlight) }}</span>
              </button>
            </div>

            <!-- Admin Actions -->
            <div v-if="canManageEvent" class="flex items-center space-x-2">
              <button
                @click="toggleFeature(highlight)"
                class="text-xs px-2 py-1 rounded"
                :class="highlight.is_featured ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600 hover:bg-yellow-100'"
              >
                {{ highlight.is_featured ? 'Unfeature' : 'Feature' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No highlights yet</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ canCreateHighlight ? 'Be the first to share a highlight from this event!' : 'Check back later for event highlights.' }}
      </p>
    </div>

    <!-- Create Highlight Modal -->
    <EventHighlightModal
      :show="showCreateModal"
      :event="event"
      @close="showCreateModal = false"
      @created="onHighlightCreated"
    />

    <!-- Comments Modal -->
    <EventHighlightComments
      :show="showCommentsModal"
      :highlight="selectedHighlight"
      @close="showCommentsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import EventHighlightModal from './EventHighlightModal.vue'
import EventHighlightComments from './EventHighlightComments.vue'

interface Props {
  event: any
  canCreateHighlight?: boolean
  canManageEvent?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canCreateHighlight: false,
  canManageEvent: false
})

const loading = ref(false)
const highlights = ref([])
const showCreateModal = ref(false)
const showCommentsModal = ref(false)
const selectedHighlight = ref(null)

const filters = reactive({
  type: '',
  sort_by: 'recent',
  featured_only: false
})

const loadHighlights = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/events/${props.event.id}/highlights`, {
      params: filters
    })
    highlights.value = response.data
  } catch (error) {
    console.error('Failed to load highlights:', error)
  } finally {
    loading.value = false
  }
}

const toggleLike = async (highlight: any) => {
  try {
    await axios.post(`/api/highlights/${highlight.id}/interact`, {
      type: 'like'
    })
    
    // Update local state
    highlight.user_liked = !highlight.user_liked
    highlight.likes_count += highlight.user_liked ? 1 : -1
  } catch (error) {
    console.error('Failed to toggle like:', error)
  }
}

const shareHighlight = async (highlight: any) => {
  try {
    await axios.post(`/api/highlights/${highlight.id}/interact`, {
      type: 'share',
      metadata: {
        platform: 'internal',
        timestamp: new Date().toISOString()
      }
    })
    
    highlight.shares_count += 1
    
    // Show success message or copy link to clipboard
    if (navigator.clipboard) {
      const url = `${window.location.origin}/events/${props.event.id}/highlights/${highlight.id}`
      await navigator.clipboard.writeText(url)
      alert('Highlight link copied to clipboard!')
    }
  } catch (error) {
    console.error('Failed to share highlight:', error)
  }
}

const showComments = (highlight: any) => {
  selectedHighlight.value = highlight
  showCommentsModal.value = true
}

const toggleFeature = async (highlight: any) => {
  try {
    await axios.post(`/api/highlights/${highlight.id}/toggle-feature`)
    highlight.is_featured = !highlight.is_featured
  } catch (error) {
    console.error('Failed to toggle feature:', error)
  }
}

const onHighlightCreated = () => {
  loadHighlights()
}

const getTypeLabel = (type: string) => {
  const labels = {
    photo: 'Photo',
    video: 'Video',
    quote: 'Quote',
    moment: 'Moment',
    achievement: 'Achievement'
  }
  return labels[type] || type
}

const getCommentsCount = (highlight: any) => {
  return highlight.interactions?.filter(i => i.type === 'comment').length || 0
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadHighlights()
})
</script>