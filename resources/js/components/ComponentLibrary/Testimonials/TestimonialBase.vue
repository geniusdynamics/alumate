<template>
  <section
    :class="testimonialSectionClasses"
    role="region"
    :aria-labelledby="sectionId"
    :aria-label="config.ariaLabel || 'Customer testimonials'"
  >
    <!-- Section Header (optional) -->
    <div v-if="config.title || config.description" class="mb-8 text-center">
      <h2
        v-if="config.title"
        :id="sectionId"
        class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
      >
        {{ config.title }}
      </h2>
      <p
        v-if="config.description"
        class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto"
      >
        {{ config.description }}
      </p>
    </div>

    <!-- Filters -->
    <TestimonialFilters
      v-if="config.enableFiltering && config.filterConfig"
      :filter-config="config.filterConfig"
      :active-filters="activeFilters"
      :testimonials="config.testimonials"
      @filter-change="handleFilterChange"
      class="mb-8"
    />

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="grid gap-6"
      :class="getGridClasses()"
      role="status"
      aria-label="Loading testimonials"
    >
      <TestimonialSkeleton
        v-for="n in getSkeletonCount()"
        :key="`skeleton-${n}`"
        :layout="config.layout"
        :theme="config.theme"
      />
    </div>

    <!-- Testimonials Content -->
    <div v-else-if="filteredTestimonials.length > 0">
      <!-- Single Layout -->
      <TestimonialSingle
        v-if="config.layout === 'single'"
        :testimonial="featuredTestimonial"
        :config="singleLayoutConfig"
        :theme="config.theme"
        :color-scheme="config.colorScheme"
        @testimonial-interaction="handleTestimonialInteraction"
      />

      <!-- Carousel Layout -->
      <TestimonialCarousel
        v-else-if="config.layout === 'carousel'"
        :testimonials="paginatedTestimonials"
        :config="config.carouselConfig"
        :theme="config.theme"
        :color-scheme="config.colorScheme"
        :video-settings="config.videoSettings"
        :show-author-photo="config.showAuthorPhoto"
        :show-author-title="config.showAuthorTitle"
        :show-author-company="config.showAuthorCompany"
        :show-graduation-year="config.showGraduationYear"
        :show-rating="config.showRating"
        :show-date="config.showDate"
        :lazy-load="config.lazyLoad"
        :respect-reduced-motion="config.respectReducedMotion"
        @testimonial-interaction="handleTestimonialInteraction"
        @slide-change="handleSlideChange"
      />

      <!-- Grid Layout -->
      <TestimonialGrid
        v-else-if="config.layout === 'grid'"
        :testimonials="paginatedTestimonials"
        :config="config.gridConfig"
        :theme="config.theme"
        :color-scheme="config.colorScheme"
        :video-settings="config.videoSettings"
        :show-author-photo="config.showAuthorPhoto"
        :show-author-title="config.showAuthorTitle"
        :show-author-company="config.showAuthorCompany"
        :show-graduation-year="config.showGraduationYear"
        :show-rating="config.showRating"
        :show-date="config.showDate"
        :lazy-load="config.lazyLoad"
        @testimonial-interaction="handleTestimonialInteraction"
      />

      <!-- Masonry Layout -->
      <TestimonialMasonry
        v-else-if="config.layout === 'masonry'"
        :testimonials="paginatedTestimonials"
        :config="config.gridConfig"
        :theme="config.theme"
        :color-scheme="config.colorScheme"
        :video-settings="config.videoSettings"
        :show-author-photo="config.showAuthorPhoto"
        :show-author-title="config.showAuthorTitle"
        :show-author-company="config.showAuthorCompany"
        :show-graduation-year="config.showGraduationYear"
        :show-rating="config.showRating"
        :show-date="config.showDate"
        :lazy-load="config.lazyLoad"
        @testimonial-interaction="handleTestimonialInteraction"
      />
    </div>

    <!-- Empty State -->
    <div
      v-else
      class="text-center py-12"
      role="status"
      aria-label="No testimonials found"
    >
      <div class="max-w-md mx-auto">
        <svg
          class="mx-auto h-12 w-12 text-gray-400 mb-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          No testimonials found
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
          {{ hasActiveFilters ? 'Try adjusting your filters to see more testimonials.' : 'No testimonials are available at this time.' }}
        </p>
        <button
          v-if="hasActiveFilters"
          @click="clearAllFilters"
          class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:text-indigo-400 dark:bg-indigo-900 dark:hover:bg-indigo-800"
        >
          Clear filters
        </button>
      </div>
    </div>

    <!-- Pagination -->
    <TestimonialPagination
      v-if="showPagination"
      :current-page="currentPage"
      :total-pages="totalPages"
      :total-items="filteredTestimonials.length"
      :items-per-page="config.itemsPerPage || 12"
      @page-change="handlePageChange"
      class="mt-8"
    />

    <!-- Load More Button (for infinite scroll alternative) -->
    <div
      v-if="config.enableInfiniteScroll && hasMoreItems"
      class="text-center mt-8"
    >
      <button
        @click="loadMoreItems"
        :disabled="isLoadingMore"
        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg
          v-if="isLoadingMore"
          class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        {{ isLoadingMore ? 'Loading...' : 'Load More' }}
      </button>
    </div>

    <!-- Screen Reader Announcements -->
    <div
      v-if="config.announceUpdates"
      :aria-live="announceUpdates ? 'polite' : 'off'"
      :aria-atomic="true"
      class="sr-only"
    >
      {{ screenReaderAnnouncement }}
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch, nextTick } from 'vue'
import type { 
  TestimonialComponentConfig, 
  Testimonial, 
  TestimonialFilterConfig 
} from '@/types/components'
import { useLazyLoading } from '@/composables/useLazyLoading'
import { useScrollTracking } from '@/composables/useScrollTracking'
import { useAnalytics } from '@/composables/useAnalytics'

// Import child components
import TestimonialFilters from './TestimonialFilters.vue'
import TestimonialSingle from './TestimonialSingle.vue'
import TestimonialCarousel from './TestimonialCarousel.vue'
import TestimonialGrid from './TestimonialGrid.vue'
import TestimonialMasonry from './TestimonialMasonry.vue'
import TestimonialSkeleton from './TestimonialSkeleton.vue'
import TestimonialPagination from './TestimonialPagination.vue'

interface Props {
  config: TestimonialComponentConfig
  sampleData?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  sampleData: false
})

// Composables
const { trackEvent } = useAnalytics()
const { isIntersecting } = useScrollTracking()

// Reactive state
const isLoading = ref(true)
const isLoadingMore = ref(false)
const currentPage = ref(1)
const activeFilters = ref<Partial<TestimonialFilterConfig>>({})
const screenReaderAnnouncement = ref('')
const announceUpdates = ref(false)

// Computed properties
const sectionId = computed(() => `testimonials-${Math.random().toString(36).substr(2, 9)}`)

const testimonialSectionClasses = computed(() => [
  'testimonial-section',
  'py-12 px-4 sm:px-6 lg:px-8',
  {
    'bg-gray-50 dark:bg-gray-900': props.config.theme === 'default',
    'bg-white dark:bg-gray-800': props.config.theme === 'minimal',
    'bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800': props.config.theme === 'modern',
  }
])

const filteredTestimonials = computed(() => {
  let testimonials = [...props.config.testimonials]

  // Apply active filters
  if (Object.keys(activeFilters.value).length > 0) {
    testimonials = testimonials.filter(testimonial => {
      // Audience type filter
      if (activeFilters.value.audienceType?.length) {
        if (!testimonial.audienceType || !activeFilters.value.audienceType.includes(testimonial.audienceType)) {
          return false
        }
      }

      // Industry filter
      if (activeFilters.value.industry?.length) {
        if (!testimonial.industry || !activeFilters.value.industry.includes(testimonial.industry)) {
          return false
        }
      }

      // Graduation year filter
      if (activeFilters.value.graduationYear) {
        const { min, max } = activeFilters.value.graduationYear
        if (testimonial.graduationYear) {
          if (min && testimonial.graduationYear < min) return false
          if (max && testimonial.graduationYear > max) return false
        }
      }

      // Tags filter
      if (activeFilters.value.tags?.length) {
        if (!testimonial.tags?.some(tag => activeFilters.value.tags?.includes(tag))) {
          return false
        }
      }

      // Rating filter
      if (activeFilters.value.rating) {
        const { min, max } = activeFilters.value.rating
        if (testimonial.content.rating) {
          if (min && testimonial.content.rating < min) return false
          if (max && testimonial.content.rating > max) return false
        }
      }

      // Type filter
      if (activeFilters.value.type?.length) {
        if (!activeFilters.value.type.includes(testimonial.content.type)) {
          return false
        }
      }

      // Featured filter
      if (activeFilters.value.featured !== undefined) {
        if (testimonial.featured !== activeFilters.value.featured) {
          return false
        }
      }

      return true
    })
  }

  // Sort testimonials (featured first, then by priority, then by date)
  return testimonials.sort((a, b) => {
    if (a.featured && !b.featured) return -1
    if (!a.featured && b.featured) return 1
    
    if (a.priority !== undefined && b.priority !== undefined) {
      if (a.priority !== b.priority) return b.priority - a.priority
    }
    
    return new Date(b.content.dateCreated).getTime() - new Date(a.content.dateCreated).getTime()
  })
})

const paginatedTestimonials = computed(() => {
  if (!props.config.itemsPerPage) return filteredTestimonials.value
  
  const startIndex = (currentPage.value - 1) * props.config.itemsPerPage
  const endIndex = startIndex + props.config.itemsPerPage
  
  return filteredTestimonials.value.slice(0, endIndex)
})

const featuredTestimonial = computed(() => {
  return filteredTestimonials.value.find(t => t.featured) || filteredTestimonials.value[0]
})

const totalPages = computed(() => {
  if (!props.config.itemsPerPage) return 1
  return Math.ceil(filteredTestimonials.value.length / props.config.itemsPerPage)
})

const showPagination = computed(() => {
  return props.config.itemsPerPage && 
         !props.config.enableInfiniteScroll && 
         totalPages.value > 1
})

const hasMoreItems = computed(() => {
  if (!props.config.itemsPerPage) return false
  return paginatedTestimonials.value.length < filteredTestimonials.value.length
})

const hasActiveFilters = computed(() => {
  return Object.keys(activeFilters.value).length > 0
})

const singleLayoutConfig = computed(() => ({
  showAuthorPhoto: props.config.showAuthorPhoto,
  showAuthorTitle: props.config.showAuthorTitle,
  showAuthorCompany: props.config.showAuthorCompany,
  showGraduationYear: props.config.showGraduationYear,
  showRating: props.config.showRating,
  showDate: props.config.showDate,
  videoSettings: props.config.videoSettings,
  lazyLoad: props.config.lazyLoad
}))

// Methods
const getGridClasses = () => {
  const columns = props.config.gridConfig?.columns || { desktop: 3, tablet: 2, mobile: 1 }
  return [
    `grid-cols-${columns.mobile}`,
    `md:grid-cols-${columns.tablet}`,
    `lg:grid-cols-${columns.desktop}`
  ]
}

const getSkeletonCount = () => {
  if (props.config.layout === 'single') return 1
  if (props.config.layout === 'carousel') return props.config.carouselConfig?.slidesToShow || 3
  
  const columns = props.config.gridConfig?.columns || { desktop: 3, tablet: 2, mobile: 1 }
  return columns.desktop * 2 // Show 2 rows worth of skeletons
}

const handleFilterChange = (filters: Partial<TestimonialFilterConfig>) => {
  activeFilters.value = filters
  currentPage.value = 1
  
  // Track filter usage
  if (props.config.trackingEnabled) {
    trackEvent('testimonial_filter_applied', {
      filters: Object.keys(filters),
      component_type: 'testimonials',
      layout: props.config.layout
    })
  }
  
  // Announce filter changes to screen readers
  if (props.config.announceUpdates) {
    const filterCount = Object.keys(filters).length
    screenReaderAnnouncement.value = filterCount > 0 
      ? `Filters applied. Showing ${filteredTestimonials.value.length} testimonials.`
      : `Filters cleared. Showing all ${filteredTestimonials.value.length} testimonials.`
    announceUpdates.value = true
    
    setTimeout(() => {
      announceUpdates.value = false
    }, 1000)
  }
}

const clearAllFilters = () => {
  activeFilters.value = {}
  currentPage.value = 1
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  
  // Scroll to top of testimonials section
  const section = document.getElementById(sectionId.value)
  if (section) {
    section.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
  
  // Track pagination
  if (props.config.trackingEnabled) {
    trackEvent('testimonial_page_change', {
      page,
      component_type: 'testimonials',
      layout: props.config.layout
    })
  }
}

const loadMoreItems = async () => {
  if (isLoadingMore.value || !hasMoreItems.value) return
  
  isLoadingMore.value = true
  
  // Simulate loading delay for better UX
  await new Promise(resolve => setTimeout(resolve, 500))
  
  const newItemsPerPage = (props.config.itemsPerPage || 12) + 12
  props.config.itemsPerPage = newItemsPerPage
  
  isLoadingMore.value = false
  
  // Track load more action
  if (props.config.trackingEnabled) {
    trackEvent('testimonial_load_more', {
      items_loaded: 12,
      total_items: paginatedTestimonials.value.length,
      component_type: 'testimonials',
      layout: props.config.layout
    })
  }
}

const handleTestimonialInteraction = (event: {
  type: 'view' | 'like' | 'share' | 'play' | 'pause'
  testimonial: Testimonial
  data?: any
}) => {
  // Track testimonial interactions
  if (props.config.trackingEnabled) {
    trackEvent(`testimonial_${event.type}`, {
      testimonial_id: event.testimonial.id,
      author_name: event.testimonial.author.name,
      content_type: event.testimonial.content.type,
      audience_type: event.testimonial.audienceType,
      component_type: 'testimonials',
      layout: props.config.layout,
      ...event.data
    })
  }
  
  // Handle specific interaction types
  switch (event.type) {
    case 'view':
      // Increment view count (would typically be handled by API)
      if (event.testimonial.content.viewCount !== undefined) {
        event.testimonial.content.viewCount++
      }
      break
    case 'like':
      // Handle like action (would typically be handled by API)
      if (event.testimonial.content.likeCount !== undefined) {
        event.testimonial.content.likeCount++
      }
      break
    case 'share':
      // Handle share action
      if (event.testimonial.content.shareCount !== undefined) {
        event.testimonial.content.shareCount++
      }
      break
  }
}

const handleSlideChange = (slideIndex: number) => {
  // Track carousel slide changes
  if (props.config.trackingEnabled) {
    trackEvent('testimonial_slide_change', {
      slide_index: slideIndex,
      component_type: 'testimonials',
      layout: 'carousel'
    })
  }
}

// Lifecycle
onMounted(async () => {
  await nextTick()
  
  // Apply default filters if provided
  if (props.config.defaultFilters) {
    activeFilters.value = { ...props.config.defaultFilters }
  }
  
  // Simulate loading for better UX
  setTimeout(() => {
    isLoading.value = false
  }, 300)
  
  // Track component view
  if (props.config.trackingEnabled) {
    trackEvent('testimonial_component_view', {
      layout: props.config.layout,
      testimonial_count: props.config.testimonials.length,
      component_type: 'testimonials'
    })
  }
})

// Watch for reduced motion preference
watch(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches, (prefersReducedMotion) => {
  if (prefersReducedMotion && props.config.respectReducedMotion) {
    // Disable animations and autoplay
    if (props.config.carouselConfig) {
      props.config.carouselConfig.autoplay = false
    }
    if (props.config.videoSettings) {
      props.config.videoSettings.autoplay = false
    }
  }
}, { immediate: true })
</script>

<style scoped>
.testimonial-section {
  container-type: inline-size;
}

/* Container queries for responsive design */
@container (max-width: 640px) {
  .testimonial-section {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

@container (min-width: 1024px) {
  .testimonial-section {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

/* Smooth transitions for filter changes */
.testimonial-section * {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .testimonial-section {
    border: 2px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .testimonial-section *,
  .testimonial-section *::before,
  .testimonial-section *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>