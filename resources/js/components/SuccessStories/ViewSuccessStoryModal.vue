<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="relative">
        <!-- Close Button -->
        <button
          @click="$emit('close')"
          class="absolute top-4 right-4 z-10 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Featured Image -->
        <div v-if="story.featured_image" class="relative h-64 md:h-80 bg-gray-200">
          <img
            :src="getImageUrl(story.featured_image)"
            :alt="story.title"
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
          
          <!-- Featured Badge -->
          <div v-if="story.is_featured" class="absolute top-4 left-4">
            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
              Featured Story
            </span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6 md:p-8">
          <!-- Header -->
          <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ story.title }}</h1>
            
            <!-- Author Info -->
            <div class="flex items-center mb-4">
              <img
                v-if="story.user.avatar_url"
                :src="story.user.avatar_url"
                :alt="story.user.name"
                class="w-12 h-12 rounded-full mr-4"
              />
              <div v-else class="w-12 h-12 rounded-full bg-gray-300 mr-4 flex items-center justify-center">
                <span class="text-gray-600 font-medium">
                  {{ story.user.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div>
                <p class="font-semibold text-gray-900">{{ story.user.name }}</p>
                <p class="text-gray-600">
                  {{ story.current_role }}
                  <span v-if="story.current_company"> at {{ story.current_company }}</span>
                </p>
                <p v-if="story.graduation_year || story.degree_program" class="text-sm text-gray-500">
                  <span v-if="story.degree_program">{{ story.degree_program }}</span>
                  <span v-if="story.graduation_year">
                    <span v-if="story.degree_program"> • </span>
                    Class of {{ story.graduation_year }}
                  </span>
                </p>
              </div>
            </div>

            <!-- Achievement Type and Industry -->
            <div class="flex flex-wrap gap-2 mb-4">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                {{ formatAchievementType(story.achievement_type) }}
              </span>
              <span v-if="story.industry" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                {{ story.industry }}
              </span>
            </div>

            <!-- Summary -->
            <p class="text-lg text-gray-700 leading-relaxed mb-6">{{ story.summary }}</p>
          </div>

          <!-- Story Content -->
          <div class="prose prose-lg max-w-none mb-8">
            <div v-html="formatContent(story.content)"></div>
          </div>

          <!-- Additional Media -->
          <div v-if="story.media_urls && story.media_urls.length > 0" class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Media</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="(mediaUrl, index) in story.media_urls"
                :key="index"
                class="relative"
              >
                <img
                  v-if="isImage(mediaUrl)"
                  :src="getImageUrl(mediaUrl)"
                  :alt="`Media ${index + 1}`"
                  class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                  @click="openMediaModal(mediaUrl)"
                />
                <div
                  v-else
                  class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center cursor-pointer hover:bg-gray-200 transition-colors"
                  @click="openMedia(mediaUrl)"
                >
                  <div class="text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-sm text-gray-600">{{ getFileName(mediaUrl) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="story.tags && story.tags.length > 0" class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Tags</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="tag in story.tags"
                :key="tag"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-800"
              >
                #{{ tag }}
              </span>
            </div>
          </div>

          <!-- Engagement Stats and Actions -->
          <div class="border-t border-gray-200 pt-6">
            <div class="flex items-center justify-between">
              <!-- Stats -->
              <div class="flex items-center space-x-6 text-gray-600">
                <span class="flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  {{ story.view_count }} views
                </span>
                <span class="flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  {{ story.like_count }} likes
                </span>
                <span class="flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                  </svg>
                  {{ story.share_count }} shares
                </span>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center space-x-3">
                <button
                  @click="$emit('like', story)"
                  class="flex items-center px-4 py-2 text-gray-600 hover:text-red-500 hover:bg-red-50 rounded-md transition-colors"
                >
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  Like
                </button>
                <button
                  v-if="story.allow_social_sharing"
                  @click="$emit('share', story)"
                  class="flex items-center px-4 py-2 text-gray-600 hover:text-blue-500 hover:bg-blue-50 rounded-md transition-colors"
                >
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                  </svg>
                  Share
                </button>
              </div>
            </div>
          </div>

          <!-- Published Date -->
          <div class="mt-4 text-sm text-gray-500 text-center">
            Published on {{ formatDate(story.published_at) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Media Modal -->
    <div v-if="selectedMedia" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-60" @click="selectedMedia = null">
      <div class="max-w-4xl max-h-full">
        <img
          :src="getImageUrl(selectedMedia)"
          :alt="'Full size media'"
          class="max-w-full max-h-full object-contain"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

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

interface Props {
  story: SuccessStory
}

defineProps<Props>()

defineEmits<{
  close: []
  share: [story: SuccessStory]
  like: [story: SuccessStory]
}>()

const selectedMedia = ref<string | null>(null)

const getImageUrl = (path: string) => {
  return `/storage/${path}`
}

const formatAchievementType = (type: string) => {
  return type.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const formatContent = (content: string) => {
  // Simple formatting - convert line breaks to paragraphs
  return content
    .split('\n\n')
    .map(paragraph => `<p class="mb-4">${paragraph.replace(/\n/g, '<br>')}</p>`)
    .join('')
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const isImage = (url: string) => {
  return /\.(jpg|jpeg|png|gif|webp)$/i.test(url)
}

const getFileName = (url: string) => {
  return url.split('/').pop() || 'File'
}

const openMediaModal = (mediaUrl: string) => {
  selectedMedia.value = mediaUrl
}

const openMedia = (mediaUrl: string) => {
  window.open(getImageUrl(mediaUrl), '_blank')
}
</script>

<style scoped>
.prose {
  color: #374151;
  line-height: 1.75;
}

.prose p {
  margin-bottom: 1rem;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
  color: #111827;
  font-weight: 600;
  margin-top: 2rem;
  margin-bottom: 1rem;
}

.prose h1 { font-size: 2.25rem; }
.prose h2 { font-size: 1.875rem; }
.prose h3 { font-size: 1.5rem; }

.prose ul, .prose ol {
  margin: 1rem 0;
  padding-left: 1.5rem;
}

.prose li {
  margin: 0.5rem 0;
}

.prose blockquote {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1.5rem 0;
  font-style: italic;
  color: #6b7280;
}

.prose strong {
  font-weight: 600;
  color: #111827;
}

.prose em {
  font-style: italic;
}

.prose a {
  color: #2563eb;
  text-decoration: underline;
}

.prose a:hover {
  color: #1d4ed8;
}
</style>