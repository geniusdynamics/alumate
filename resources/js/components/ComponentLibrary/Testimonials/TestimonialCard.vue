<template>
  <article
    :class="cardClasses"
    role="article"
    :aria-labelledby="headingId"
    @click="handleCardClick"
  >
    <!-- Video Thumbnail (for video testimonials) -->
    <div
      v-if="testimonial.content.type === 'video' && testimonial.content.videoAsset"
      class="relative mb-4 group"
    >
      <ResponsiveImage
        :src="videoThumbnail"
        :alt="`Video testimonial by ${testimonial.author.name}`"
        :lazy-load="config.lazyLoad"
        aspect-ratio="16:9"
        class="cursor-pointer transition-transform duration-200 group-hover:scale-105"
        @click="handleVideoClick"
      />
      
      <!-- Play Button Overlay -->
      <button
        v-if="config.showPlayButtonOverlay"
        @click="handleVideoClick"
        class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 hover:bg-opacity-50 transition-all duration-200 rounded-lg group"
        :aria-label="`Play video testimonial by ${testimonial.author.name}`"
      >
        <div class="relative">
          <svg class="w-12 h-12 text-white transition-transform duration-200 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
          <!-- Pulse animation -->
          <div class="absolute inset-0 w-12 h-12 border-2 border-white rounded-full animate-ping opacity-30"></div>
        </div>
      </button>

      <!-- Video Duration Badge -->
      <div
        v-if="videoDuration"
        class="absolute bottom-2 right-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded"
      >
        {{ formatDuration(videoDuration) }}
      </div>

      <!-- Video Quality Badge -->
      <div
        v-if="videoQuality"
        class="absolute top-2 right-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded"
      >
        {{ videoQuality }}
      </div>

      <!-- Captions Available Indicator -->
      <div
        v-if="testimonial.content.videoAsset.captions"
        class="absolute top-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded flex items-center"
        title="Captions available"
      >
        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
          <path d="M19 4H5c-1.11 0-2 .9-2 2v12c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V6c0-1.1-.89-2-2-2zm-8 7H9.5v-.5h-2v3h2V13H11v1c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-4c0-.55.45-1 1-1h3c.55 0 1 .45 1 1v1zm7 0h-1.5v-.5h-2v3h2V13H18v1c0 .55-.45 1-1 1h-3c-.55 0-1-.45-1-1v-4c0-.55.45-1 1-1h3c.55 0 1 .45 1 1v1z"/>
        </svg>
        CC
      </div>
    </div>

    <!-- Card Content -->
    <div class="testimonial-card-content flex-1">
      <!-- Quote -->
      <blockquote class="mb-4">
        <svg
          v-if="theme !== 'minimal'"
          class="w-6 h-6 text-gray-400 dark:text-gray-500 mb-3"
          fill="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/>
        </svg>
        
        <p :class="quoteClasses">
          {{ truncatedQuote }}
        </p>
      </blockquote>

      <!-- Rating -->
      <div
        v-if="config.showRating && testimonial.content.rating"
        class="flex items-center mb-4"
        :aria-label="`Rating: ${testimonial.content.rating} out of 5 stars`"
      >
        <div class="flex space-x-1">
          <svg
            v-for="star in 5"
            :key="`star-${star}`"
            :class="[
              'w-4 h-4',
              star <= testimonial.content.rating
                ? 'text-yellow-400 fill-current'
                : 'text-gray-300 dark:text-gray-600'
            ]"
            viewBox="0 0 20 20"
            :aria-hidden="true"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
        </div>
        <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">
          {{ testimonial.content.rating }}/5
        </span>
      </div>
    </div>

    <!-- Author Information -->
    <footer class="testimonial-card-footer mt-auto pt-4">
      <div class="flex items-center space-x-3">
        <!-- Author Photo -->
        <div
          v-if="config.showAuthorPhoto && testimonial.author.photo"
          class="flex-shrink-0"
        >
          <ResponsiveImage
            :src="testimonial.author.photo"
            :alt="`Photo of ${testimonial.author.name}`"
            :lazy-load="config.lazyLoad"
            class="w-10 h-10 rounded-full object-cover"
          />
        </div>

        <!-- Author Details -->
        <div class="flex-1 min-w-0">
          <cite
            :id="headingId"
            class="block font-medium text-gray-900 dark:text-white text-sm not-italic"
          >
            {{ testimonial.author.name }}
          </cite>
          
          <div class="text-xs text-gray-600 dark:text-gray-400 space-y-0.5">
            <div
              v-if="config.showAuthorTitle && testimonial.author.title"
              class="truncate"
            >
              {{ testimonial.author.title }}
            </div>
            
            <div
              v-if="config.showAuthorCompany && testimonial.author.company"
              class="truncate"
            >
              {{ testimonial.author.company }}
            </div>
            
            <div
              v-if="config.showAuthorGraduationYear && testimonial.author.graduationYear"
              class="text-gray-500 dark:text-gray-500"
            >
              '{{ String(testimonial.author.graduationYear).slice(-2) }}
            </div>
          </div>
        </div>

        <!-- Verification Badge -->
        <div
          v-if="testimonial.content.verified"
          class="flex-shrink-0"
          :title="'Verified testimonial'"
        >
          <svg
            class="w-4 h-4 text-green-500"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-label="Verified"
          >
            <path
              fill-rule="evenodd"
              d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
      </div>

      <!-- Date -->
      <div
        v-if="config.showDate && testimonial.content.dateCreated"
        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
      >
        {{ formatDate(testimonial.content.dateCreated) }}
      </div>
    </footer>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Testimonial, VideoSettings } from '@/types/components'

// Import child components
import ResponsiveImage from '@/components/Common/ResponsiveImage.vue'

interface TestimonialCardConfig {
  showAuthorPhoto?: boolean
  showAuthorTitle?: boolean
  showAuthorCompany?: boolean
  showAuthorGraduationYear?: boolean
  showRating?: boolean
  showDate?: boolean
  lazyLoad?: boolean
  showPlayButtonOverlay?: boolean
  maxQuoteLength?: number
}

interface Props {
  testimonial: Testimonial
  config: TestimonialCardConfig
  videoSettings?: VideoSettings
  theme?: 'default' | 'minimal' | 'modern' | 'classic' | 'card'
  colorScheme?: 'default' | 'primary' | 'secondary' | 'accent'
  clickable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  theme: 'card',
  colorScheme: 'default',
  clickable: false,
  config: () => ({
    showAuthorPhoto: true,
    showAuthorTitle: true,
    showAuthorCompany: true,
    showAuthorGraduationYear: true,
    showRating: true,
    showDate: true,
    lazyLoad: true,
    showPlayButtonOverlay: true,
    maxQuoteLength: 150
  })
})

const emit = defineEmits<{
  testimonialPlay: [testimonial: Testimonial]
  testimonialInteraction: [event: { type: string, testimonial: Testimonial, data?: any }]
}>()

// Computed properties
const headingId = computed(() => `testimonial-card-${props.testimonial.id}-heading`)

const cardClasses = computed(() => [
  'testimonial-card',
  'flex flex-col h-full p-6 transition-all duration-200',
  {
    // Theme-based styling
    'bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg': props.theme === 'card',
    'bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700': props.theme === 'modern',
    'border-l-4 pl-6 bg-white dark:bg-gray-800': props.theme === 'classic',
    'bg-transparent': props.theme === 'minimal',
    
    // Color scheme variations
    'border-indigo-500': props.colorScheme === 'primary' && props.theme === 'classic',
    'border-green-500': props.colorScheme === 'secondary' && props.theme === 'classic',
    'border-purple-500': props.colorScheme === 'accent' && props.theme === 'classic',
    
    // Clickable
    'cursor-pointer hover:scale-105': props.clickable,
  }
])

const quoteClasses = computed(() => [
  'text-gray-700 dark:text-gray-300 leading-relaxed',
  {
    'text-sm': props.theme === 'card',
    'text-base': props.theme === 'modern',
    'italic': props.theme === 'classic',
    'font-medium': props.theme === 'minimal',
  }
])

const videoThumbnail = computed(() => {
  if (!props.testimonial.content.videoAsset) return null
  
  return {
    id: `${props.testimonial.content.videoAsset.id}-thumb`,
    type: 'image' as const,
    url: props.testimonial.content.videoAsset.thumbnail || props.testimonial.content.videoAsset.url,
    alt: `Video testimonial thumbnail for ${props.testimonial.author.name}`
  }
})

const truncatedQuote = computed(() => {
  const maxLength = props.config.maxQuoteLength || 150
  const quote = props.testimonial.content.quote
  
  if (quote.length <= maxLength) return quote
  
  return quote.substring(0, maxLength).trim() + '...'
})

const videoDuration = computed(() => {
  return props.testimonial.content.videoAsset?.duration
})

const videoQuality = computed(() => {
  const asset = props.testimonial.content.videoAsset
  if (!asset) return null
  
  if (asset.height && asset.height >= 1080) return 'HD'
  if (asset.height && asset.height >= 720) return '720p'
  if (asset.height && asset.height >= 480) return '480p'
  
  return null
})

// Methods
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const handleVideoClick = () => {
  if (props.testimonial.content.type === 'video') {
    emit('testimonialPlay', props.testimonial)
  }
}

const handleCardClick = () => {
  if (props.clickable) {
    emit('testimonialInteraction', {
      type: 'view',
      testimonial: props.testimonial,
      data: { source: 'card_click' }
    })
  }
}

const formatDuration = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = Math.floor(seconds % 60)
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}
</script>

<style scoped>
.testimonial-card {
  container-type: inline-size;
}

/* Quote styling */
.testimonial-card blockquote {
  position: relative;
}

/* Hover effects */
.testimonial-card:hover .testimonial-card-content {
  transform: translateY(-1px);
}

/* Video thumbnail hover effects */
.testimonial-card .video-thumbnail:hover {
  transform: scale(1.02);
}

/* Container queries for responsive design */
@container (max-width: 280px) {
  .testimonial-card {
    padding: 1rem;
  }
  
  .testimonial-card .quote-text {
    font-size: 0.875rem;
  }
  
  .testimonial-card .author-photo {
    width: 2rem;
    height: 2rem;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .testimonial-card {
    border: 2px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .testimonial-card,
  .testimonial-card *,
  .testimonial-card *::before,
  .testimonial-card *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus management */
.testimonial-card:focus-within {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* Print styles */
@media print {
  .testimonial-card {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #000;
  }
  
  .testimonial-card button {
    display: none;
  }
}
</style>