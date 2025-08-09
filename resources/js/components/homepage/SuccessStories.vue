<template>
  <section class="success-stories" id="success-stories">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <header class="section-header">
        <h2 class="section-title">Alumni Success Stories</h2>
        <p class="section-subtitle">
          Discover how our platform has transformed careers and opened new opportunities for alumni like you
        </p>
      </header>

      <!-- Featured Story -->
      <div v-if="featuredStory" class="featured-story-container">
        <h3 class="featured-story-title">Featured Success Story</h3>
        <SuccessStoryCard 
          :story="featuredStory"
          @share="handleShare"
          @play-video="handlePlayVideo"
          class="featured-story-card"
        />
      </div>

      <!-- Filters -->
      <SuccessStoryFilters
        :filters="filters"
        :total-count="totalStories"
        :filtered-count="filteredStories.length"
        @filter-change="handleFilterChange"
      />

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading success stories...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <svg class="error-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
        <h3 class="error-title">Unable to load success stories</h3>
        <p class="error-message">{{ error }}</p>
        <button @click="retryLoading" class="retry-button">
          Try Again
        </button>
      </div>

      <!-- Success Stories Grid -->
      <div v-else-if="filteredStories.length > 0" class="stories-grid">
        <SuccessStoryCard
          v-for="story in paginatedStories"
          :key="story.id"
          :story="story"
          @share="handleShare"
          @play-video="handlePlayVideo"
        />
      </div>

      <!-- No Results -->
      <div v-else class="no-results-container">
        <svg class="no-results-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
        <h3 class="no-results-title">No success stories found</h3>
        <p class="no-results-message">
          Try adjusting your filters to see more stories, or 
          <button @click="clearFilters" class="clear-filters-link">clear all filters</button>
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination-container">
        <nav class="pagination-nav" aria-label="Success stories pagination">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="pagination-button prev-button"
            aria-label="Previous page"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
            </svg>
            Previous
          </button>

          <div class="pagination-numbers">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'pagination-number',
                { 'active': page === currentPage }
              ]"
              :aria-label="`Go to page ${page}`"
              :aria-current="page === currentPage ? 'page' : undefined"
            >
              {{ page }}
            </button>
          </div>

          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="pagination-button next-button"
            aria-label="Next page"
          >
            Next
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
            </svg>
          </button>
        </nav>

        <div class="pagination-info">
          <p class="pagination-text">
            Showing {{ startIndex + 1 }}-{{ Math.min(endIndex, filteredStories.length) }} 
            of {{ filteredStories.length }} stories
          </p>
        </div>
      </div>

      <!-- Call to Action -->
      <div class="cta-container">
        <h3 class="cta-title">Ready to Write Your Success Story?</h3>
        <p class="cta-description">
          Join thousands of alumni who have transformed their careers through our platform
        </p>
        <div class="cta-buttons">
          <button @click="handleJoinNow" class="cta-button primary">
            Join Now
          </button>
          <button @click="handleLearnMore" class="cta-button secondary">
            Learn More
          </button>
        </div>
      </div>
    </div>

    <!-- Video Modal -->
    <VideoTestimonialModal
      :is-open="videoModalOpen"
      :video-url="currentVideoUrl"
      :title="currentVideoTitle"
      :description="currentVideoDescription"
      :alumni-name="currentVideoAlumni?.name"
      :alumni-role="currentVideoAlumni?.currentRole"
      :alumni-company="currentVideoAlumni?.currentCompany"
      :alumni-image="currentVideoAlumni?.profileImage"
      @close="closeVideoModal"
      @share="handleVideoShare"
    />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import SuccessStoryCard from './SuccessStoryCard.vue'
import SuccessStoryFilters from './SuccessStoryFilters.vue'
import VideoTestimonialModal from './VideoTestimonialModal.vue'
import type { SuccessStory, StoryFilter, AlumniProfile } from '@/types/homepage'

interface Props {
  stories?: SuccessStory[]
  filters?: StoryFilter[]
  featuredStory?: SuccessStory
  itemsPerPage?: number
}

const props = withDefaults(defineProps<Props>(), {
  stories: () => [],
  filters: () => [],
  itemsPerPage: 9
})

const emit = defineEmits<{
  'join-now': []
  'learn-more': []
  'share-story': [platform: string, story: SuccessStory]
}>()

// State
const loading = ref(false)
const error = ref('')
const currentPage = ref(1)
const activeFilters = ref<Record<string, string>>({})

// Video modal state
const videoModalOpen = ref(false)
const currentVideoUrl = ref('')
const currentVideoTitle = ref('')
const currentVideoDescription = ref('')
const currentVideoAlumni = ref<AlumniProfile | null>(null)

// Computed properties
const totalStories = computed(() => props.stories.length)

const filteredStories = computed(() => {
  let filtered = [...props.stories]

  // Apply filters
  if (activeFilters.value.industry) {
    filtered = filtered.filter(story => story.industry === activeFilters.value.industry)
  }
  
  if (activeFilters.value.graduationYear) {
    filtered = filtered.filter(story => story.graduationYear.toString() === activeFilters.value.graduationYear)
  }
  
  if (activeFilters.value.careerStage) {
    filtered = filtered.filter(story => story.alumniProfile.careerStage === activeFilters.value.careerStage)
  }
  
  if (activeFilters.value.successType) {
    filtered = filtered.filter(story => {
      return story.metrics.some(metric => metric.type === activeFilters.value.successType)
    })
  }

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(filteredStories.value.length / props.itemsPerPage)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * props.itemsPerPage
})

const endIndex = computed(() => {
  return startIndex.value + props.itemsPerPage
})

const paginatedStories = computed(() => {
  return filteredStories.value.slice(startIndex.value, endIndex.value)
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Methods
const handleFilterChange = (filters: Record<string, string>) => {
  activeFilters.value = filters
  currentPage.value = 1 // Reset to first page when filters change
}

const clearFilters = () => {
  activeFilters.value = {}
  currentPage.value = 1
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    // Scroll to top of stories section
    document.getElementById('success-stories')?.scrollIntoView({ 
      behavior: 'smooth',
      block: 'start'
    })
  }
}

const handleShare = (platform: string, story: SuccessStory) => {
  emit('share-story', platform, story)
  
  // Handle different sharing platforms
  const shareUrl = `${window.location.origin}/success-stories/${story.id}`
  const shareText = `Check out this inspiring success story from ${story.alumniProfile.name}: ${story.title}`
  
  switch (platform) {
    case 'linkedin':
      window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`, '_blank')
      break
    case 'twitter':
      window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareUrl)}`, '_blank')
      break
    case 'copy':
      navigator.clipboard.writeText(shareUrl).then(() => {
        // Could show a toast notification here
        console.log('Link copied to clipboard')
      })
      break
  }
}

const handlePlayVideo = (videoUrl: string) => {
  // Find the story that contains this video
  const story = props.stories.find(s => s.testimonialVideo === videoUrl)
  
  currentVideoUrl.value = videoUrl
  currentVideoTitle.value = story ? `${story.alumniProfile.name}'s Success Story` : 'Video Testimonial'
  currentVideoDescription.value = story?.summary || ''
  currentVideoAlumni.value = story?.alumniProfile || null
  videoModalOpen.value = true
}

const closeVideoModal = () => {
  videoModalOpen.value = false
  currentVideoUrl.value = ''
  currentVideoTitle.value = ''
  currentVideoDescription.value = ''
  currentVideoAlumni.value = null
}

const handleVideoShare = (videoUrl: string) => {
  const shareText = 'Check out this inspiring alumni success story video'
  const shareUrl = videoUrl
  
  if (navigator.share) {
    navigator.share({
      title: 'Alumni Success Story Video',
      text: shareText,
      url: shareUrl
    })
  } else {
    // Fallback to copying URL
    navigator.clipboard.writeText(shareUrl)
  }
}

const handleJoinNow = () => {
  emit('join-now')
}

const handleLearnMore = () => {
  emit('learn-more')
}

const retryLoading = () => {
  error.value = ''
  loading.value = true
  // Simulate retry - in real implementation, this would refetch data
  setTimeout(() => {
    loading.value = false
  }, 1000)
}

// Watch for filter changes to reset pagination
watch(() => activeFilters.value, () => {
  currentPage.value = 1
}, { deep: true })

onMounted(() => {
  // Component is ready
})
</script>

<style scoped>
.success-stories {
  @apply py-16 bg-gray-50;
}

.container {
  @apply max-w-7xl;
}

.section-header {
  @apply text-center mb-12;
}

.section-title {
  @apply text-4xl font-bold text-gray-900 mb-4;
}

.section-subtitle {
  @apply text-xl text-gray-600 max-w-3xl mx-auto;
}

.featured-story-container {
  @apply mb-12;
}

.featured-story-title {
  @apply text-2xl font-semibold text-gray-900 mb-6 text-center;
}

.featured-story-card {
  @apply max-w-4xl mx-auto;
}

.loading-container {
  @apply flex flex-col items-center justify-center py-16;
}

.loading-spinner {
  @apply w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4;
}

.loading-text {
  @apply text-gray-600 text-lg;
}

.error-container {
  @apply flex flex-col items-center justify-center py-16 text-center;
}

.error-icon {
  @apply w-16 h-16 text-red-400 mb-4;
}

.error-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.error-message {
  @apply text-gray-600 mb-6;
}

.retry-button {
  @apply px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.stories-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12;
}

.no-results-container {
  @apply flex flex-col items-center justify-center py-16 text-center;
}

.no-results-icon {
  @apply w-16 h-16 text-gray-400 mb-4;
}

.no-results-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.no-results-message {
  @apply text-gray-600;
}

.clear-filters-link {
  @apply text-blue-600 hover:text-blue-800 underline;
}

.pagination-container {
  @apply mb-12;
}

.pagination-nav {
  @apply flex items-center justify-center gap-2 mb-4;
}

.pagination-button {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.pagination-button svg {
  @apply w-5 h-5;
}

.pagination-numbers {
  @apply flex gap-1;
}

.pagination-number {
  @apply w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors;
}

.pagination-number.active {
  @apply bg-blue-600 text-white border-blue-600;
}

.pagination-info {
  @apply text-center;
}

.pagination-text {
  @apply text-gray-600;
}

.cta-container {
  @apply text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200;
}

.cta-title {
  @apply text-3xl font-bold text-gray-900 mb-4;
}

.cta-description {
  @apply text-xl text-gray-600 mb-8 max-w-2xl mx-auto;
}

.cta-buttons {
  @apply flex flex-col sm:flex-row gap-4 justify-center;
}

.cta-button {
  @apply px-8 py-4 rounded-lg font-semibold text-lg transition-colors;
}

.cta-button.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.cta-button.secondary {
  @apply bg-white text-blue-600 border-2 border-blue-600 hover:bg-blue-50;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .success-stories {
    @apply py-12;
  }
  
  .section-title {
    @apply text-3xl;
  }
  
  .section-subtitle {
    @apply text-lg;
  }
  
  .stories-grid {
    @apply grid-cols-1 gap-6;
  }
  
  .pagination-nav {
    @apply flex-wrap;
  }
  
  .pagination-button {
    @apply px-3 py-2 text-sm;
  }
  
  .pagination-number {
    @apply w-8 h-8 text-sm;
  }
  
  .cta-title {
    @apply text-2xl;
  }
  
  .cta-description {
    @apply text-lg;
  }
  
  .cta-button {
    @apply px-6 py-3 text-base;
  }
}
</style>