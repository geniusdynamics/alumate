<template>
  <div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Alumni Success Stories Platform</h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          Discover inspiring journeys of our alumni who are making a difference in their fields and communities
        </p>
      </div>

      <!-- Demo Features -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        <!-- Feature 1: Rich Story Creation -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Rich Story Creation</h3>
          <p class="text-gray-600 text-sm">
            Create compelling success stories with multimedia content, including images, videos, and documents. 
            Categorize by industry, achievement type, and demographics.
          </p>
        </div>

        <!-- Feature 2: Discovery & Filtering -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Smart Discovery</h3>
          <p class="text-gray-600 text-sm">
            Advanced filtering and search capabilities by industry, achievement type, graduation year, and tags. 
            Personalized recommendations based on user profile.
          </p>
        </div>

        <!-- Feature 3: Social Sharing -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Social Sharing</h3>
          <p class="text-gray-600 text-sm">
            Share success stories across social media and marketing channels. 
            Built-in engagement features with likes, views, and share tracking.
          </p>
        </div>
      </div>

      <!-- Sample Stories Section -->
      <div class="mb-12">
        <div class="flex justify-between items-center mb-8">
          <h2 class="text-2xl font-bold text-gray-900">Featured Success Stories</h2>
          <button
            @click="showCreateModal = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
          >
            Share Your Story
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Loading success stories...</p>
        </div>

        <!-- Stories Grid -->
        <div v-else-if="stories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="story in stories"
            :key="story.id"
            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 cursor-pointer"
            @click="viewStory(story)"
          >
            <!-- Featured Badge -->
            <div v-if="story.is_featured" class="absolute top-4 left-4 z-10">
              <span class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                Featured
              </span>
            </div>

            <!-- Placeholder Image -->
            <div class="relative h-48 bg-gradient-to-br from-blue-400 to-purple-500">
              <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white">
                  <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <p class="text-sm font-medium">{{ story.user.name }}</p>
                </div>
              </div>
            </div>

            <!-- Content -->
            <div class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                {{ story.title }}
              </h3>
              <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                {{ story.summary }}
              </p>

              <!-- Achievement Type and Industry -->
              <div class="flex flex-wrap gap-2 mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ formatAchievementType(story.achievement_type) }}
                </span>
                <span v-if="story.industry" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  {{ story.industry }}
                </span>
              </div>

              <!-- Stats -->
              <div class="flex items-center justify-between text-sm text-gray-500">
                <div class="flex items-center space-x-4">
                  <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ story.view_count }}
                  </span>
                  <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    {{ story.like_count }}
                  </span>
                </div>
                <span class="text-xs">{{ formatDate(story.published_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No Success Stories Yet</h3>
          <p class="text-gray-500 mb-4">Be the first to share your inspiring journey!</p>
          <button
            @click="showCreateModal = true"
            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors"
          >
            Share Your Story
          </button>
        </div>
      </div>

      <!-- Analytics Section -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Analytics</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ analytics.total_stories || 0 }}</div>
            <div class="text-sm text-gray-500">Total Stories</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ analytics.featured_stories || 0 }}</div>
            <div class="text-sm text-gray-500">Featured Stories</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">{{ analytics.total_views || 0 }}</div>
            <div class="text-sm text-gray-500">Total Views</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-orange-600">{{ analytics.total_shares || 0 }}</div>
            <div class="text-sm text-gray-500">Total Shares</div>
          </div>
        </div>
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
import { ref, onMounted } from 'vue'
import CreateSuccessStoryModal from './CreateSuccessStoryModal.vue'
import ViewSuccessStoryModal from './ViewSuccessStoryModal.vue'

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

const loading = ref(false)
const showCreateModal = ref(false)
const selectedStory = ref<SuccessStory | null>(null)
const stories = ref<SuccessStory[]>([])
const analytics = ref({
  total_stories: 0,
  featured_stories: 0,
  total_views: 0,
  total_shares: 0
})

onMounted(() => {
  loadFeaturedStories()
  loadAnalytics()
})

const loadFeaturedStories = async () => {
  loading.value = true
  try {
    // Simulate API call with mock data
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    stories.value = [
      {
        id: 1,
        title: "From Student to Tech Entrepreneur",
        summary: "How I built a successful startup after graduation and created 50+ jobs in my community.",
        content: "My journey started during my final year when I identified a gap in the market...",
        featured_image: null,
        media_urls: [],
        industry: "Technology",
        achievement_type: "startup",
        current_role: "CEO & Founder",
        current_company: "TechVenture Inc.",
        graduation_year: "2020",
        degree_program: "Computer Science",
        tags: ["entrepreneurship", "innovation", "leadership"],
        demographics: { gender: "female", ethnicity: "asian" },
        status: "published",
        is_featured: true,
        allow_social_sharing: true,
        view_count: 1250,
        share_count: 89,
        like_count: 156,
        published_at: "2024-01-15T10:00:00Z",
        user: {
          id: 1,
          name: "Sarah Chen",
          avatar_url: null
        }
      },
      {
        id: 2,
        title: "Breaking Barriers in Healthcare",
        summary: "My path to becoming the first in my family to earn a medical degree and establishing a community clinic.",
        content: "Growing up in an underserved community, I witnessed firsthand the healthcare disparities...",
        featured_image: null,
        media_urls: [],
        industry: "Healthcare",
        achievement_type: "community_service",
        current_role: "Chief Medical Officer",
        current_company: "Community Health Center",
        graduation_year: "2018",
        degree_program: "Medicine",
        tags: ["healthcare", "community", "diversity"],
        demographics: { gender: "male", ethnicity: "hispanic", first_generation: true },
        status: "published",
        is_featured: true,
        allow_social_sharing: true,
        view_count: 980,
        share_count: 67,
        like_count: 134,
        published_at: "2024-02-20T14:30:00Z",
        user: {
          id: 2,
          name: "Dr. Miguel Rodriguez",
          avatar_url: null
        }
      },
      {
        id: 3,
        title: "Leading Innovation in Renewable Energy",
        summary: "How my research in sustainable technology led to breakthrough patents and a cleaner future.",
        content: "During my graduate studies, I became passionate about solving the climate crisis through technology...",
        featured_image: null,
        media_urls: [],
        industry: "Engineering",
        achievement_type: "patent",
        current_role: "Senior Research Engineer",
        current_company: "GreenTech Solutions",
        graduation_year: "2019",
        degree_program: "Environmental Engineering",
        tags: ["innovation", "sustainability", "research"],
        demographics: { gender: "non-binary", ethnicity: "black" },
        status: "published",
        is_featured: false,
        allow_social_sharing: true,
        view_count: 756,
        share_count: 45,
        like_count: 98,
        published_at: "2024-03-10T09:15:00Z",
        user: {
          id: 3,
          name: "Alex Johnson",
          avatar_url: null
        }
      }
    ]

    analytics.value = {
      total_stories: 25,
      featured_stories: 8,
      total_views: 15420,
      total_shares: 892
    }
  } catch (error) {
    console.error('Error loading stories:', error)
  } finally {
    loading.value = false
  }
}

const loadAnalytics = async () => {
  // Analytics would be loaded from API in real implementation
}

const viewStory = (story: SuccessStory) => {
  selectedStory.value = story
  // Increment view count
  story.view_count++
}

const shareStory = async (story: SuccessStory) => {
  try {
    // Use Web Share API if available
    if (navigator.share) {
      await navigator.share({
        title: story.title,
        text: story.summary,
        url: `${window.location.origin}/success-stories/${story.id}`
      })
    } else {
      // Fallback to copying URL to clipboard
      await navigator.clipboard.writeText(`${window.location.origin}/success-stories/${story.id}`)
      alert('Story URL copied to clipboard!')
    }
    
    // Increment share count
    story.share_count++
  } catch (error) {
    console.error('Error sharing story:', error)
  }
}

const likeStory = (story: SuccessStory) => {
  story.like_count++
}

const handleStoryCreated = (newStory: SuccessStory) => {
  showCreateModal.value = false
  stories.value.unshift(newStory)
  analytics.value.total_stories++
}

const formatAchievementType = (type: string) => {
  return type.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>