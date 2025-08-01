<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl font-bold mb-4">Alumni Success Stories</h1>
          <p class="text-xl opacity-90 max-w-3xl mx-auto">
            Discover inspiring journeys of our alumni who are making a difference in their fields and communities
          </p>
        </div>
      </div>
    </div>

    <!-- Featured Stories Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Featured Stories</h2>
        <div v-if="featuredStories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <SuccessStoryCard
            v-for="story in featuredStories"
            :key="story.id"
            :story="story"
            :featured="true"
            @view="viewStory"
            @share="shareStory"
            @like="likeStory"
          />
        </div>
        <div v-else class="text-center py-12">
          <p class="text-gray-500">No featured stories available at the moment.</p>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search stories..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @input="debouncedSearch"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
              <select
                v-model="filters.industry"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="applyFilters"
              >
                <option value="">All Industries</option>
                <option v-for="industry in industries" :key="industry" :value="industry">
                  {{ industry }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Achievement Type</label>
              <select
                v-model="filters.achievement_type"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="applyFilters"
              >
                <option value="">All Types</option>
                <option v-for="type in achievementTypes" :key="type" :value="type">
                  {{ formatAchievementType(type) }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Graduation Year</label>
              <select
                v-model="filters.graduation_year"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="applyFilters"
              >
                <option value="">All Years</option>
                <option v-for="year in graduationYears" :key="year" :value="year">
                  {{ year }}
                </option>
              </select>
            </div>
          </div>
          <div class="flex justify-between items-center">
            <button
              @click="clearFilters"
              class="text-sm text-gray-500 hover:text-gray-700"
            >
              Clear Filters
            </button>
            <button
              @click="showCreateModal = true"
              class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
            >
              Share Your Story
            </button>
          </div>
        </div>
      </div>

      <!-- Stories Grid -->
      <div class="mb-8">
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Loading stories...</p>
        </div>
        <div v-else-if="stories.data && stories.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <SuccessStoryCard
            v-for="story in stories.data"
            :key="story.id"
            :story="story"
            @view="viewStory"
            @share="shareStory"
            @like="likeStory"
          />
        </div>
        <div v-else class="text-center py-12">
          <p class="text-gray-500">No stories found matching your criteria.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="stories.data && stories.data.length > 0" class="flex justify-center">
        <nav class="flex items-center space-x-2">
          <button
            v-if="stories.prev_page_url"
            @click="loadPage(stories.current_page - 1)"
            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Previous
          </button>
          <span class="px-3 py-2 text-sm text-gray-700">
            Page {{ stories.current_page }} of {{ stories.last_page }}
          </span>
          <button
            v-if="stories.next_page_url"
            @click="loadPage(stories.current_page + 1)"
            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Next
          </button>
        </nav>
      </div>
    </div>

    <!-- Create Story Modal -->
    <CreateSuccessStoryModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleStoryCreated"
    />

    <!-- View Story Modal -->
    <ViewSuccessStoryModal
      v-if="selectedStory"
      :story="selectedStory"
      @close="selectedStory = null"
      @share="shareStory"
      @like="likeStory"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { debounce } from 'lodash'
import SuccessStoryCard from '@/Components/SuccessStories/SuccessStoryCard.vue'
import CreateSuccessStoryModal from '@/Components/SuccessStories/CreateSuccessStoryModal.vue'
import ViewSuccessStoryModal from '@/Components/SuccessStories/ViewSuccessStoryModal.vue'

interface SuccessStory {
  id: number
  title: string
  summary: string
  content: string
  featured_image: string | null
  media_urls: string[]
  industry: string | null
  achievement_type: string
  current_role: string | null
  current_company: string | null
  graduation_year: string | null
  degree_program: string | null
  tags: string[]
  demographics: Record<string, any>
  status: string
  is_featured: boolean
  allow_social_sharing: boolean
  view_count: number
  share_count: number
  like_count: number
  published_at: string
  user: {
    id: number
    name: string
    avatar_url: string | null
  }
}

interface PaginatedStories {
  data: SuccessStory[]
  current_page: number
  last_page: number
  prev_page_url: string | null
  next_page_url: string | null
  total: number
}

const loading = ref(false)
const showCreateModal = ref(false)
const selectedStory = ref<SuccessStory | null>(null)
const featuredStories = ref<SuccessStory[]>([])
const stories = ref<PaginatedStories>({
  data: [],
  current_page: 1,
  last_page: 1,
  prev_page_url: null,
  next_page_url: null,
  total: 0
})

const filters = reactive({
  search: '',
  industry: '',
  achievement_type: '',
  graduation_year: '',
  tags: []
})

const industries = [
  'Technology', 'Healthcare', 'Finance', 'Education', 'Marketing',
  'Engineering', 'Consulting', 'Non-profit', 'Government', 'Media'
]

const achievementTypes = [
  'promotion', 'award', 'startup', 'publication', 'patent',
  'leadership', 'community_service', 'innovation', 'research', 'entrepreneurship'
]

const graduationYears = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let year = currentYear; year >= currentYear - 50; year--) {
    years.push(year.toString())
  }
  return years
})

const debouncedSearch = debounce(() => {
  applyFilters()
}, 500)

onMounted(() => {
  loadFeaturedStories()
  loadStories()
})

const loadFeaturedStories = async () => {
  try {
    const response = await fetch('/api/success-stories/featured', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    const data = await response.json()
    if (data.success) {
      featuredStories.value = data.data
    }
  } catch (error) {
    console.error('Error loading featured stories:', error)
  }
}

const loadStories = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: '12',
      ...Object.fromEntries(
        Object.entries(filters).filter(([_, value]) => value !== '' && value !== null)
      )
    })

    const response = await fetch(`/api/success-stories?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    const data = await response.json()
    if (data.success) {
      stories.value = data.data
    }
  } catch (error) {
    console.error('Error loading stories:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  loadStories(1)
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    if (Array.isArray(filters[key])) {
      filters[key] = []
    } else {
      filters[key] = ''
    }
  })
  loadStories(1)
}

const loadPage = (page: number) => {
  loadStories(page)
}

const viewStory = async (story: SuccessStory) => {
  selectedStory.value = story
  
  // Increment view count
  try {
    await fetch(`/api/success-stories/${story.id}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
  } catch (error) {
    console.error('Error viewing story:', error)
  }
}

const shareStory = async (story: SuccessStory) => {
  try {
    const response = await fetch(`/api/success-stories/${story.id}/share`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    const data = await response.json()
    
    if (data.success && data.data.share_data) {
      // Use Web Share API if available
      if (navigator.share) {
        await navigator.share({
          title: data.data.share_data.title,
          text: data.data.share_data.description,
          url: data.data.share_data.url
        })
      } else {
        // Fallback to copying URL to clipboard
        await navigator.clipboard.writeText(data.data.share_data.url)
        alert('Story URL copied to clipboard!')
      }
      
      // Update share count in UI
      story.share_count = data.data.share_count
    }
  } catch (error) {
    console.error('Error sharing story:', error)
  }
}

const likeStory = async (story: SuccessStory) => {
  try {
    const response = await fetch(`/api/success-stories/${story.id}/like`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    const data = await response.json()
    
    if (data.success) {
      story.like_count = data.data.like_count
    }
  } catch (error) {
    console.error('Error liking story:', error)
  }
}

const handleStoryCreated = (newStory: SuccessStory) => {
  showCreateModal.value = false
  // Refresh stories list
  loadStories()
}

const formatAchievementType = (type: string) => {
  return type.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}
</script>