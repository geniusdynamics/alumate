<template>
  <div
    :id="carouselId"
    class="testimonial-carousel"
    role="region"
    :aria-label="`Testimonial carousel showing ${testimonials.length} testimonials`"
  >
    <!-- Carousel Header (optional) -->
    <div v-if="config.title || config.description" class="mb-8 text-center">
      <h3
        v-if="config.title"
        class="text-2xl font-bold text-gray-900 dark:text-white mb-4"
      >
        {{ config.title }}
      </h3>
      <p
        v-if="config.description"
        class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto"
      >
        {{ config.description }}
      </p>
    </div>

    <!-- Carousel Container -->
    <div
      class="relative overflow-hidden"
      :class="{
        'rounded-lg': config.theme === 'card',
        'bg-gray-50 dark:bg-gray-800': config.theme === 'card' && config.colorScheme === 'default',
        'bg-white dark:bg-gray-900 shadow-lg': config.theme === 'card' && config.colorScheme === 'light'
      }"
    >
      <!-- Slides Container -->
      <div
        ref="slidesContainer"
        :class="slidesContainerClasses"
        :style="{ transform: `translateX(-${currentSlide * (100 / slidesToShow)}%)` }"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        @keydown="handleKeydown"
        role="list"
        aria-live="polite"
      >
        <div
          v-for="(testimonial, index) in testimonials"
          :key="`slide-${index}`"
          :class="slideClasses"
          role="listitem"
          :aria-current="index === currentSlide ? 'true' : 'false'"
          tabindex="0"
        >
          <TestimonialCard
            :testimonial="testimonial"
            :config="slideConfig"
            :video-settings="videoSettings"
            :theme="theme"
            :color-scheme="colorScheme"
            @testimonial-play="handleTestimonialPlay"
            @testimonial-interaction="handleTestimonialInteraction"
          />
        </div>
      </div>

      <!-- Navigation Arrows -->
      <button
        v-if="showArrows"
        @click="previousSlide"
        :disabled="!canGoPrevious"
        class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 p-2 rounded-full bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        :aria-label="`Previous testimonial slide (${currentSlide + 1} of ${totalSlides})`"
        :class="{ 'hidden': !canGoPrevious }"
      >
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <button
        v-if="showArrows"
        @click="nextSlide"
        :disabled="!canGoNext"
        class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 p-2 rounded-full bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        :aria-label="`Next testimonial slide (${currentSlide + 1} of ${totalSlides})`"
        :class="{ 'hidden': !canGoNext }"
      >
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <!-- Dots Indicator -->
      <div
        v-if="showDots && totalSlides > 1"
        class="flex justify-center mt-6 space-x-2"
        role="tablist"
        aria-label="Testimonial slides"
      >
        <button
          v-for="(dot, index) in totalSlides"
          :key="`dot-${index}`"
          @click="goToSlide(index)"
          :class="[
            'w-3 h-3 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
            index === currentSlide
              ? 'bg-indigo-600 dark:bg-indigo-500 scale-110'
              : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500'
          ]"
          :aria-label="`Go to testimonial slide ${index + 1} of ${totalSlides}`"
          role="tab"
          :aria-selected="index === currentSlide"
        />
      </div>
    </div>

    <!-- Video Modal Portal Target -->
    <Teleport to="body">
      <VideoModal
        v-if="activeVideoTestimonial"
        :testimonial="activeVideoTestimonial"
        :video-settings="videoSettings"
        @close="closeVideoModal"
        @video-interaction="handleVideoInteraction"
      />
    </Teleport>

    <!-- Screen Reader Announcements -->
    <div
      v-if="announceUpdates"
      :aria-live="announceUpdates ? 'polite' : 'off'"
      :aria-atomic="true"
      class="sr-only"
    >
      {{ screenReaderAnnouncement }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue'
import type { Testimonial, TestimonialCarouselConfig, TestimonialComponentConfig, VideoSettings } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'

// Import child components
import TestimonialCard from './TestimonialCard.vue'
import VideoModal from './VideoModal.vue'

interface Props {
  testimonials: Testimonial[]
  config: TestimonialCarouselConfig
  theme?: 'default' | 'minimal' | 'modern' | 'classic' | 'card'
  colorScheme?: 'default' | 'primary' | 'secondary' | 'accent'
  videoSettings?: VideoSettings
  showAuthorPhoto?: boolean
  showAuthorTitle?: boolean
  showAuthorCompany?: boolean
  showAuthorGraduationYear?: boolean
  showRating?: boolean
  showDate?: boolean
  showPlayButtonOverlay?: boolean
  lazyLoad?: boolean
  startSlide?: number
  onSlideChange?: (slideIndex: number) => void
}

const props = withDefaults(defineProps<Props>(), {
  theme: 'card',
  colorScheme: 'default',
  videoSettings: () => ({
    autoplay: false,
    muted: true,
    showControls: true,
    showCaptions: true,
    preload: 'metadata'
  }),
  showPlayButtonOverlay: true,
  startSlide: 0,
  showAuthorPhoto: true,
  showAuthorTitle: true,
  showAuthorCompany: true,
  showAuthorGraduationYear: true,
  showRating: true,
  showDate: true,
  lazyLoad: true
})

const emit = defineEmits<{
  testimonialInteraction: [event: { type: string, testimonial: Testimonial, data?: any }]
  testimonialPlay: [testimonial: Testimonial]
  slideChange: [slideIndex: number]
}>()

// Analytics
const { trackEvent } = useAnalytics()

// Reactive state
const currentSlide = ref(props.startSlide)
const isTransitioning = ref(false)
const slidesContainer = ref<HTMLElement>()
const carouselId = ref(`testimonial-carousel-${Math.random().toString(36).substr(2, 9)}`)
const activeVideoTestimonial = ref<Testimonial | null>(null)
const screenReaderAnnouncement = ref('')
const announceUpdates = ref(false)
const autoplayTimer = ref<NodeJS.Timeout | null>(null)

// Touch handling
const touchStartX = ref(0)
const touchEndX = ref(0)
const isDragging = ref(false)

// Computed properties
const slidesToShow = computed(() => {
  if (!props.config.slidesToShow) return 1

  // Handle responsive slidesToShow
  if (typeof window === 'undefined') return 1

  const width = window.innerWidth
  const responsive = props.config.responsive || []

  const activeBreakpoint = responsive.find(bp => width <= bp.breakpoint)
  return activeBreakpoint?.settings.slidesToShow || props.config.slidesToShow
})

const slidesToScroll = computed(() => {
  if (!props.config.slidesToScroll) return 1

  // Handle responsive slidesToScroll
  if (typeof window === 'undefined') return 1

  const width = window.innerWidth
  const responsive = props.config.responsive || []

  const activeBreakpoint = responsive.find(bp => width <= bp.breakpoint)
  return activeBreakpoint?.settings.slidesToScroll || props.config.slidesToScroll
})

const totalSlides = computed(() => {
  return Math.ceil(props.testimonials.length / slidesToShow.value)
})

const canGoNext = computed(() => {
  if (props.config.infinite) return true
  return currentSlide.value < totalSlides.value - 1
})

const canGoPrevious = computed(() => {
  return props.config.infinite || currentSlide.value > 0
})

const showArrows = computed(() => props.config.showArrows)
const showDots = computed(() => props.config.showDots)

// Classes
const slidesContainerClasses = computed(() => [
  'flex transition-transform duration-500 ease-in-out',
  {
    'select-none': isTransitioning.value,
    'cursor-grab active:cursor-grabbing': isDragging.value && props.config.swipe
  }
])

const slideClasses = computed(() => {
  const baseClasses = ['flex-shrink-0']
  const widthPercentage = 100 / slidesToShow.value

  let widthClass = ''
  if (widthPercentage % 1 === 0) {
    widthClass = `w-${widthPercentage}/100`
  } else if (widthPercentage === 50) {
    widthClass = 'w-1/2'
  } else if (widthPercentage === 33.3333333333) {
    widthClass = 'w-1/3'
  } else if (widthPercentage === 25) {
    widthClass = 'w-1/4'
  }

  return [baseClasses, widthClass, 'p-4']
})

// Slide configuration for individual cards
const slideConfig = computed(() => ({
  showAuthorPhoto: props.showAuthorPhoto,
  showAuthorTitle: props.showAuthorTitle,
  showAuthorCompany: props.showAuthorCompany,
  showAuthorGraduationYear: props.showAuthorGraduationYear,
  showRating: props.showRating,
  showDate: props.showDate,
  lazyLoad: props.lazyLoad
}))

// Methods
const nextSlide = () => {
  if (!canGoNext.value || isTransitioning.value) return

  const nextIndex = props.config.infinite && currentSlide.value >= totalSlides.value - 1
    ? 0
    : Math.min(currentSlide.value + slidesToScroll.value, totalSlides.value - 1)

  goToSlide(nextIndex)
}

const previousSlide = () => {
  if (!canGoPrevious.value || isTransitioning.value) return

  const prevIndex = props.config.infinite && currentSlide.value <= 0
    ? totalSlides.value - 1
    : Math.max(currentSlide.value - slidesToScroll.value, 0)

  goToSlide(prevIndex)
}

const goToSlide = (slideIndex: number) => {
  if (isTransitioning.value) return

  const oldSlide = currentSlide.value
  currentSlide.value = slideIndex
  isTransitioning.value = true

  // Track slide change
  if (props.config.trackingEnabled) {
    trackEvent('testimonial_carousel_slide_change', {
      from_slide: oldSlide,
      to_slide: slideIndex,
      total_slides: totalSlides.value,
      component_type: 'carousel'
    })
  }

  // Announce slide change for screen readers
  if (props.config.announceSlideChanges) {
    screenReaderAnnouncement.value = `Slide ${slideIndex + 1} of ${totalSlides.value}: ${getSlideAccessibilityDescription(slideIndex)}`
    announceUpdates.value = true

    setTimeout(() => {
      announceUpdates.value = false
    }, 1000)
  }

  emit('slideChange', slideIndex)

  // Reset transition flag
  setTimeout(() => {
    isTransitioning.value = false
  }, 500)
}

const getSlideAccessibilityDescription = (slideIndex: number): string => {
  const startIndex = slideIndex * slidesToShow.value
  const endIndex = Math.min(startIndex + slidesToShow.value, props.testimonials.length)
  const testimonialsInSlide = props.testimonials.slice(startIndex, endIndex)

  return testimonialsInSlide
    .map(testimonial => `${testimonial.author.name}, ${testimonial.author.title}`)
    .join('; ')
}

const handleTestimonialInteraction = (event: { type: string, testimonial: Testimonial, data?: any }) => {
  // Track testimonial interactions
  if (props.config.trackingEnabled) {
    trackEvent(`testimonial_${event.type}`, {
      testimonial_id: event.testimonial.id,
      carousel_slide: currentSlide.value,
      component_type: 'carousel',
      ...event.data
    })
  }

  emit('testimonialInteraction', event)
}

const handleTestimonialPlay = (testimonial: Testimonial) => {
  if (testimonial.content.videoAsset) {
    activeVideoTestimonial.value = testimonial
    emit('testimonialPlay', testimonial)

    // Track video play
    if (props.config.trackingEnabled) {
      trackEvent('testimonial_video_play', {
        testimonial_id: testimonial.id,
        carousel_slide: currentSlide.value,
        component_type: 'carousel'
      })
    }
  }
}

const closeVideoModal = () => {
  activeVideoTestimonial.value = null
}

const handleVideoInteraction = (event: { type: string, data?: any }) => {
  // Track video interactions
  if (props.config.trackingEnabled && activeVideoTestimonial.value) {
    trackEvent(`testimonial_video_${event.type}`, {
      testimonial_id: activeVideoTestimonial.value.id,
      carousel_slide: currentSlide.value,
      component_type: 'carousel',
      ...event.data
    })
  }
}

// Touch handling
const handleTouchStart = (e: TouchEvent) => {
  if (!props.config.swipe) return

  touchStartX.value = e.touches[0].clientX
  isDragging.value = false
}

const handleTouchMove = (e: TouchEvent) => {
  if (!props.config.swipe || touchStartX.value === 0) return

  touchEndX.value = e.touches[0].clientX
  const deltaX = touchStartX.value - touchEndX.value

  if (Math.abs(deltaX) > props.config.touchThreshold || 10) {
    isDragging.value = true
  }
}

const handleTouchEnd = () => {
  if (!props.config.swipe || !isDragging.value) return

  const deltaX = touchStartX.value - touchEndX.value
  const deltaXAbs = Math.abs(deltaX)

  if (deltaXAbs > 50) { // Minimum swipe distance
    if (deltaX > 0) {
      nextSlide()
    } else {
      previousSlide()
    }
  }

  touchStartX.value = 0
  touchEndX.value = 0
  isDragging.value = false
}

// Keyboard handling
const handleKeydown = (e: KeyboardEvent) => {
  switch (e.key) {
    case 'ArrowLeft':
      e.preventDefault()
      previousSlide()
      break
    case 'ArrowRight':
      e.preventDefault()
      nextSlide()
      break
    case 'Home':
      e.preventDefault()
      goToSlide(0)
      break
    case 'End':
      e.preventDefault()
      goToSlide(totalSlides.value - 1)
      break
  }
}

// Autoplay functionality
const startAutoplay = () => {
  if (!props.config.autoplay || autoplayTimer.value) return

  autoplayTimer.value = setInterval(() => {
    if (props.config.pauseOnHover) {
      // Pause on hover logic would be handled by mouse events
      return
    }
    nextSlide()
  }, props.config.autoplaySpeed || 5000)
}

const stopAutoplay = () => {
  if (autoplayTimer.value) {
    clearInterval(autoplayTimer.value)
    autoplayTimer.value = null
  }
}

// Lifecycle
onMounted(async () => {
  await nextTick()

  // Start autoplay if enabled
  if (props.config.autoplay) {
    startAutoplay()
  }

  // Track carousel view
  if (props.config.trackingEnabled) {
    trackEvent('testimonial_carousel_view', {
      testimonial_count: props.testimonials.length,
      autoplay_enabled: props.config.autoplay,
      infinite_enabled: props.config.infinite,
      component_type: 'carousel'
    })
  }

  // Focus management for carousel
  const carouselElement = document.getElementById(carouselId.value)
  if (carouselElement) {
    carouselElement.focus()
  }
})

onUnmounted(() => {
  stopAutoplay()
})
</script>

<style scoped>
.testimonial-carousel {
  container-type: inline-size;
}

/* Container queries for responsive design */
@container (max-width: 640px) {
  .testimonial-carousel {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
}

/* Smooth transitions */
.testimonial-carousel * {
  transition: all 0.3s ease;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .testimonial-carousel .navigation-button {
    border: 2px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .testimonial-carousel .slides-container {
    transition: none;
  }
}

/* Focus management */
.testimonial-carousel:focus {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

.testimonial-carousel .slide:focus {
  outline: 2px solid #6366f1;
  outline-offset: -2px;
}

/* Touch support enhancements */
@media (hover: none) and (pointer: coarse) {
  .testimonial-carousel .navigation-button {
    width: 44px;
    height: 44px;
    /* Ensure touch targets are at least 44px */
  }
}

/* Loading states */
.testimonial-carousel .loading {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>