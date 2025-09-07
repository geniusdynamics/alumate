<template>
  <MediaBase
    :config="config"
    :is-loading="isLoading"
    :has-error="hasError"
    :error-message="errorMessage"
    :loading-message="loadingMessage"
    :track-analytics="trackAnalytics"
    :analytics-id="analyticsId"
    @retry="handleRetry"
  >
    <!-- Gallery Grid -->
    <div
      :class="galleryClasses"
      role="region"
      :aria-label="galleryAriaLabel"
    >
      <div
        v-for="(image, index) in optimizedImages"
        :key="image.id"
        :class="imageItemClasses"
        @click="openLightbox(index)"
        @keydown.enter="openLightbox(index)"
        @keydown.space.prevent="openLightbox(index)"
        :tabindex="config.lightbox?.enabled ? 0 : -1"
        role="button"
        :aria-label="`View image ${index + 1}: ${image.alt || 'Gallery image'}`"
      >
        <ResponsiveImage
          :src="image"
          :alt="image.alt || `Gallery image ${index + 1}`"
          :lazy-load="config.performance.lazyLoading"
          :aspect-ratio="aspectRatio"
          :object-fit="objectFit"
          :clickable="config.lightbox?.enabled"
          :rounded="imageRounded"
          @load="handleImageLoad(index)"
          @error="handleImageError(index, $event)"
          @click="handleImageClick(index, $event)"
        >
          <!-- Image Overlay -->
          <template #overlay v-if="config.lightbox?.enabled">
            <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center opacity-0 hover:opacity-100">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
              </svg>
            </div>
          </template>
        </ResponsiveImage>

        <!-- Image Caption -->
        <div
          v-if="showCaptions && (image.alt || image.caption)"
          class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4"
        >
          <p class="text-white text-sm font-medium">
            {{ image.caption || image.alt }}
          </p>
        </div>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <div
        v-if="lightboxOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90"
        @click="closeLightbox"
        @keydown.esc="closeLightbox"
        role="dialog"
        aria-modal="true"
        :aria-label="`Image ${currentImageIndex + 1} of ${optimizedImages.length}`"
      >
        <!-- Lightbox Content -->
        <div
          class="relative max-w-7xl max-h-full mx-4"
          @click.stop
        >
          <!-- Main Image -->
          <div class="relative">
            <ResponsiveImage
              v-if="currentImage"
              :src="currentImage"
              :alt="currentImage.alt || `Gallery image ${currentImageIndex + 1}`"
              :lazy-load="false"
              aspect-ratio="auto"
              object-fit="contain"
              class="max-h-[90vh] w-auto"
            />

            <!-- Loading State -->
            <div
              v-if="lightboxLoading"
              class="absolute inset-0 flex items-center justify-center"
            >
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
            </div>
          </div>

          <!-- Navigation Controls -->
          <button
            v-if="optimizedImages.length > 1"
            @click="previousImage"
            class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-3 rounded-full transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
            :aria-label="'Previous image'"
            :disabled="currentImageIndex === 0 && !config.lightbox?.autoplay"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <button
            v-if="optimizedImages.length > 1"
            @click="nextImage"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-3 rounded-full transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
            :aria-label="'Next image'"
            :disabled="currentImageIndex === optimizedImages.length - 1 && !config.lightbox?.autoplay"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>

          <!-- Close Button -->
          <button
            @click="closeLightbox"
            class="absolute top-4 right-4 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-2 rounded-full transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
            :aria-label="'Close lightbox'"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>

          <!-- Image Counter -->
          <div
            v-if="config.lightbox?.showCounter && optimizedImages.length > 1"
            class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-4 py-2 rounded-full text-sm"
          >
            {{ currentImageIndex + 1 }} / {{ optimizedImages.length }}
          </div>

          <!-- Image Caption -->
          <div
            v-if="config.lightbox?.showCaptions && currentImage && (currentImage.alt || currentImage.caption)"
            class="absolute bottom-4 left-4 right-4 text-center"
          >
            <div class="bg-black bg-opacity-50 text-white p-4 rounded-lg">
              <p class="text-sm">{{ currentImage.caption || currentImage.alt }}</p>
            </div>
          </div>

          <!-- Thumbnails -->
          <div
            v-if="config.lightbox?.showThumbnails && optimizedImages.length > 1"
            class="absolute bottom-16 left-1/2 transform -translate-x-1/2 flex space-x-2 bg-black bg-opacity-50 p-2 rounded-lg max-w-full overflow-x-auto"
          >
            <button
              v-for="(image, index) in optimizedImages"
              :key="`thumb-${image.id}`"
              @click="goToImage(index)"
              :class="[
                'flex-shrink-0 w-16 h-16 rounded overflow-hidden border-2 transition-all',
                {
                  'border-white': index === currentImageIndex,
                  'border-transparent hover:border-gray-300': index !== currentImageIndex
                }
              ]"
              :aria-label="`Go to image ${index + 1}`"
            >
              <ResponsiveImage
                :src="image"
                :alt="image.alt || `Thumbnail ${index + 1}`"
                :lazy-load="false"
                aspect-ratio="1:1"
                object-fit="cover"
                class="w-full h-full"
              />
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </MediaBase>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { MediaBase } from './index'
import ResponsiveImage from '@/components/Common/ResponsiveImage.vue'
import type { MediaComponentConfig, MediaAsset } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'

interface Props {
  config: MediaComponentConfig
  trackAnalytics?: boolean
  analyticsId?: string
  aspectRatio?: '16:9' | '4:3' | '1:1' | '3:2' | 'auto'
  objectFit?: 'contain' | 'cover' | 'fill' | 'scale-down'
  showCaptions?: boolean
  imageRounded?: boolean | 'sm' | 'md' | 'lg' | 'xl' | 'full'
}

const props = withDefaults(defineProps<Props>(), {
  trackAnalytics: true,
  aspectRatio: '1:1',
  objectFit: 'cover',
  showCaptions: false,
  imageRounded: 'md'
})

const emit = defineEmits<{
  imageLoad: [index: number, image: MediaAsset]
  imageError: [index: number, error: Event]
  imageClick: [index: number, image: MediaAsset]
  lightboxOpen: [index: number]
  lightboxClose: []
  retry: []
}>()

// Reactive state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const loadingMessage = ref('Loading gallery...')
const loadedImages = ref<Set<number>>(new Set())
const failedImages = ref<Set<number>>(new Set())

// Lightbox state
const lightboxOpen = ref(false)
const currentImageIndex = ref(0)
const lightboxLoading = ref(false)
const autoplayTimer = ref<number | null>(null)

// Touch gesture state
const touchStartX = ref(0)
const touchStartY = ref(0)
const touchEndX = ref(0)
const touchEndY = ref(0)

// Analytics
const { trackEvent } = useAnalytics()

// Computed properties
const optimizedImages = computed(() => {
  return props.config.mediaAssets.map(asset => ({
    ...asset,
    // Use CDN URL if available and CDN is enabled
    url: props.config.cdnConfig?.enabled && asset.cdnUrl ? asset.cdnUrl : asset.url,
    // Generate responsive variants if not present
    srcSet: asset.srcSet || generateResponsiveVariants(asset),
    // Add mobile-specific optimizations
    mobileUrl: asset.mobileUrl || (props.config.mobileOptimized ? generateMobileVariant(asset) : asset.url)
  }))
})

const currentImage = computed(() => {
  return optimizedImages.value[currentImageIndex.value]
})

const galleryAriaLabel = computed(() => {
  return props.config.accessibility?.ariaLabel || 
         `Image gallery with ${optimizedImages.value.length} images`
})

const galleryClasses = computed(() => [
  'image-gallery',
  {
    // Layout classes
    'grid': props.config.layout === 'grid',
    'columns-1 md:columns-2 lg:columns-3': props.config.layout === 'masonry',
    
    // Grid columns
    'grid-cols-1': props.config.gridColumns?.mobile === 1,
    'grid-cols-2': props.config.gridColumns?.mobile === 2,
    'md:grid-cols-2': props.config.gridColumns?.tablet === 2,
    'md:grid-cols-3': props.config.gridColumns?.tablet === 3,
    'md:grid-cols-4': props.config.gridColumns?.tablet === 4,
    'lg:grid-cols-2': props.config.gridColumns?.desktop === 2,
    'lg:grid-cols-3': props.config.gridColumns?.desktop === 3,
    'lg:grid-cols-4': props.config.gridColumns?.desktop === 4,
    'lg:grid-cols-5': props.config.gridColumns?.desktop === 5,
    'lg:grid-cols-6': props.config.gridColumns?.desktop === 6,
    
    // Gap
    'gap-2': props.config.gridGap === 'sm',
    'gap-4': props.config.gridGap === 'md',
    'gap-6': props.config.gridGap === 'lg',
  }
])

const imageItemClasses = computed(() => [
  'image-gallery-item',
  'relative overflow-hidden',
  {
    'cursor-pointer': props.config.lightbox?.enabled,
    'break-inside-avoid': props.config.layout === 'masonry',
    'mb-4': props.config.layout === 'masonry',
    'hover:scale-105 transition-transform duration-200': props.config.lightbox?.enabled,
    'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2': props.config.lightbox?.enabled,
  }
])

// Methods
const generateResponsiveVariants = (asset: MediaAsset) => {
  if (!asset.width) return []
  
  const variants = []
  const breakpoints = [480, 768, 1024, 1280, 1920]
  
  breakpoints.forEach(width => {
    if (width <= asset.width!) {
      variants.push({
        url: `${asset.url}?w=${width}&q=${props.config.optimization.compressionLevel === 'high' ? 85 : props.config.optimization.compressionLevel === 'medium' ? 75 : 65}`,
        width,
        format: props.config.optimization.webpSupport ? 'webp' : 'jpeg'
      })
    }
  })
  
  return variants
}

const generateMobileVariant = (asset: MediaAsset) => {
  const maxMobileWidth = 768
  const quality = props.config.optimization.compressionLevel === 'high' ? 80 : 
                  props.config.optimization.compressionLevel === 'medium' ? 70 : 60
  
  return `${asset.url}?w=${maxMobileWidth}&q=${quality}&format=${props.config.optimization.webpSupport ? 'webp' : 'jpeg'}`
}

const handleImageLoad = (index: number) => {
  loadedImages.value.add(index)
  
  if (props.trackAnalytics) {
    trackEvent('gallery_image_load', {
      gallery_id: props.analyticsId,
      image_index: index,
      image_id: optimizedImages.value[index].id
    })
  }
  
  emit('imageLoad', index, optimizedImages.value[index])
}

const handleImageError = (index: number, error: Event) => {
  failedImages.value.add(index)
  
  if (props.trackAnalytics) {
    trackEvent('gallery_image_error', {
      gallery_id: props.analyticsId,
      image_index: index,
      image_id: optimizedImages.value[index].id
    })
  }
  
  emit('imageError', index, error)
}

const handleImageClick = (index: number, event: MouseEvent) => {
  if (props.config.lightbox?.enabled) {
    openLightbox(index)
  }
  
  if (props.trackAnalytics) {
    trackEvent('gallery_image_click', {
      gallery_id: props.analyticsId,
      image_index: index,
      image_id: optimizedImages.value[index].id,
      lightbox_enabled: props.config.lightbox?.enabled
    })
  }
  
  emit('imageClick', index, optimizedImages.value[index])
}

const openLightbox = (index: number) => {
  if (!props.config.lightbox?.enabled) return
  
  currentImageIndex.value = index
  lightboxOpen.value = true
  lightboxLoading.value = true
  
  // Disable body scroll
  document.body.style.overflow = 'hidden'
  
  // Start autoplay if enabled
  if (props.config.lightbox.autoplay) {
    startAutoplay()
  }
  
  // Track lightbox open
  if (props.trackAnalytics) {
    trackEvent('gallery_lightbox_open', {
      gallery_id: props.analyticsId,
      image_index: index,
      image_id: optimizedImages.value[index].id
    })
  }
  
  emit('lightboxOpen', index)
  
  // Hide loading after a short delay
  setTimeout(() => {
    lightboxLoading.value = false
  }, 500)
}

const closeLightbox = () => {
  lightboxOpen.value = false
  lightboxLoading.value = false
  
  // Re-enable body scroll
  document.body.style.overflow = ''
  
  // Stop autoplay
  stopAutoplay()
  
  // Track lightbox close
  if (props.trackAnalytics) {
    trackEvent('gallery_lightbox_close', {
      gallery_id: props.analyticsId,
      image_index: currentImageIndex.value,
      image_id: optimizedImages.value[currentImageIndex.value].id
    })
  }
  
  emit('lightboxClose')
}

const nextImage = () => {
  if (currentImageIndex.value < optimizedImages.value.length - 1) {
    currentImageIndex.value++
  } else if (props.config.lightbox?.autoplay) {
    currentImageIndex.value = 0
  }
  
  lightboxLoading.value = true
  setTimeout(() => {
    lightboxLoading.value = false
  }, 300)
  
  // Track navigation
  if (props.trackAnalytics) {
    trackEvent('gallery_lightbox_next', {
      gallery_id: props.analyticsId,
      image_index: currentImageIndex.value,
      image_id: optimizedImages.value[currentImageIndex.value].id
    })
  }
}

const previousImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
  } else if (props.config.lightbox?.autoplay) {
    currentImageIndex.value = optimizedImages.value.length - 1
  }
  
  lightboxLoading.value = true
  setTimeout(() => {
    lightboxLoading.value = false
  }, 300)
  
  // Track navigation
  if (props.trackAnalytics) {
    trackEvent('gallery_lightbox_previous', {
      gallery_id: props.analyticsId,
      image_index: currentImageIndex.value,
      image_id: optimizedImages.value[currentImageIndex.value].id
    })
  }
}

const goToImage = (index: number) => {
  currentImageIndex.value = index
  lightboxLoading.value = true
  
  setTimeout(() => {
    lightboxLoading.value = false
  }, 300)
  
  // Track thumbnail navigation
  if (props.trackAnalytics) {
    trackEvent('gallery_lightbox_thumbnail', {
      gallery_id: props.analyticsId,
      image_index: index,
      image_id: optimizedImages.value[index].id
    })
  }
}

const startAutoplay = () => {
  if (!props.config.lightbox?.autoplay) return
  
  stopAutoplay() // Clear any existing timer
  
  autoplayTimer.value = window.setInterval(() => {
    nextImage()
  }, props.config.lightbox.autoplaySpeed || 5000)
}

const stopAutoplay = () => {
  if (autoplayTimer.value) {
    clearInterval(autoplayTimer.value)
    autoplayTimer.value = null
  }
}

const handleRetry = () => {
  isLoading.value = true
  hasError.value = false
  failedImages.value.clear()
  
  // Simulate retry delay
  setTimeout(() => {
    isLoading.value = false
  }, 1000)
  
  emit('retry')
}

// Touch gesture handlers
const handleTouchStart = (event: TouchEvent) => {
  if (!props.config.touchGestures?.swipeEnabled) return
  
  touchStartX.value = event.touches[0].clientX
  touchStartY.value = event.touches[0].clientY
}

const handleTouchEnd = (event: TouchEvent) => {
  if (!props.config.touchGestures?.swipeEnabled) return
  
  touchEndX.value = event.changedTouches[0].clientX
  touchEndY.value = event.changedTouches[0].clientY
  
  handleSwipeGesture()
}

const handleSwipeGesture = () => {
  const deltaX = touchEndX.value - touchStartX.value
  const deltaY = touchEndY.value - touchStartY.value
  const threshold = props.config.touchGestures?.swipeThreshold || 50
  
  // Only process horizontal swipes that are longer than vertical swipes
  if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > threshold) {
    if (deltaX > 0) {
      // Swipe right - previous image
      previousImage()
    } else {
      // Swipe left - next image
      nextImage()
    }
  }
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  if (!lightboxOpen.value || !props.config.accessibility?.keyboardNavigation) return
  
  switch (event.key) {
    case 'ArrowLeft':
      event.preventDefault()
      previousImage()
      break
    case 'ArrowRight':
      event.preventDefault()
      nextImage()
      break
    case 'Escape':
      event.preventDefault()
      closeLightbox()
      break
    case 'Home':
      event.preventDefault()
      goToImage(0)
      break
    case 'End':
      event.preventDefault()
      goToImage(optimizedImages.value.length - 1)
      break
  }
}

// Lifecycle
onMounted(() => {
  // Add keyboard event listeners
  if (props.config.accessibility?.keyboardNavigation) {
    document.addEventListener('keydown', handleKeydown)
  }
  
  // Add touch event listeners for lightbox
  if (props.config.touchGestures?.swipeEnabled) {
    document.addEventListener('touchstart', handleTouchStart, { passive: true })
    document.addEventListener('touchend', handleTouchEnd, { passive: true })
  }
})

onUnmounted(() => {
  // Clean up event listeners
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('touchstart', handleTouchStart)
  document.removeEventListener('touchend', handleTouchEnd)
  
  // Stop autoplay
  stopAutoplay()
  
  // Re-enable body scroll if lightbox was open
  if (lightboxOpen.value) {
    document.body.style.overflow = ''
  }
})

// Watch for lightbox state changes
watch(lightboxOpen, (isOpen) => {
  if (isOpen && props.config.lightbox?.autoplay) {
    startAutoplay()
  } else {
    stopAutoplay()
  }
})
</script>

<style scoped>
.image-gallery {
  container-type: inline-size;
}

/* Masonry layout support */
.image-gallery.columns-1,
.image-gallery.columns-2,
.image-gallery.columns-3 {
  column-fill: balance;
}

/* Lightbox animations */
.lightbox-enter-active,
.lightbox-leave-active {
  transition: opacity 0.3s ease;
}

.lightbox-enter-from,
.lightbox-leave-to {
  opacity: 0;
}

/* Image hover effects */
.image-gallery-item:hover .responsive-image-container {
  transform: scale(1.02);
}

/* Focus styles for accessibility */
.image-gallery-item:focus {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .image-gallery-item {
    border: 1px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .image-gallery-item,
  .image-gallery-item *,
  .lightbox-enter-active,
  .lightbox-leave-active {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Print styles */
@media print {
  .image-gallery {
    display: block !important;
    columns: 1 !important;
  }
  
  .image-gallery-item {
    break-inside: avoid;
    page-break-inside: avoid;
    margin-bottom: 1rem;
  }
}

/* Mobile-specific styles */
@media (max-width: 768px) {
  .image-gallery.grid {
    grid-template-columns: repeat(var(--mobile-columns, 1), 1fr);
  }
}

/* Tablet-specific styles */
@media (min-width: 768px) and (max-width: 1024px) {
  .image-gallery.grid {
    grid-template-columns: repeat(var(--tablet-columns, 2), 1fr);
  }
}

/* Desktop-specific styles */
@media (min-width: 1024px) {
  .image-gallery.grid {
    grid-template-columns: repeat(var(--desktop-columns, 3), 1fr);
  }
}
</style>