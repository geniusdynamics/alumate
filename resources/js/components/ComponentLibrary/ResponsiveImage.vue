<template>
  <div 
    ref="containerRef"
    :class="containerClasses"
    :style="containerStyles"
  >
    <!-- Placeholder/Loading State -->
    <div
      v-if="showPlaceholder"
      class="absolute inset-0 bg-gray-200 dark:bg-gray-700 animate-pulse"
      :style="placeholderStyles"
      role="img"
      :aria-label="alt || 'Loading image'"
    />

    <!-- Progressive Enhancement: Picture Element with Multiple Sources -->
    <picture
      v-if="!showPlaceholder && responsiveConfig"
      class="w-full h-full"
    >
      <!-- Modern format sources (AVIF, WebP) -->
      <source
        v-for="source in modernSources"
        :key="source.type"
        :type="source.type"
        :srcset="source.srcset"
        :sizes="source.sizes"
        :media="source.media"
      >
      
      <!-- Fallback image -->
      <img
        ref="imageRef"
        :src="responsiveConfig.src"
        :srcset="responsiveConfig.srcSet"
        :sizes="responsiveConfig.sizes"
        :alt="alt"
        :class="imageClasses"
        :loading="lazyLoad ? 'lazy' : 'eager'"
        :decoding="async ? 'async' : 'sync'"
        @load="handleImageLoad"
        @error="handleImageError"
      >
    </picture>

    <!-- Fallback for non-responsive images -->
    <img
      v-else-if="!showPlaceholder"
      ref="imageRef"
      :src="src"
      :alt="alt"
      :class="imageClasses"
      :loading="lazyLoad ? 'lazy' : 'eager'"
      :decoding="async ? 'async' : 'sync'"
      @load="handleImageLoad"
      @error="handleImageError"
    >

    <!-- Error State -->
    <div
      v-if="hasError"
      class="absolute inset-0 bg-gray-100 dark:bg-gray-800 flex items-center justify-center"
      role="img"
      :aria-label="errorMessage"
    >
      <div class="text-center text-gray-500 dark:text-gray-400 p-4">
        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="text-sm">{{ errorMessage }}</p>
      </div>
    </div>

    <!-- Overlay Content Slot -->
    <div
      v-if="$slots.overlay"
      class="absolute inset-0 flex items-center justify-center"
    >
      <slot name="overlay" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import type { MediaAsset } from '@/types/components'
import { generateResponsiveImageSources, createLazyLoadObserver, preloadImage } from '@/utils/mediaOptimization'

interface Props {
  // Image source - can be URL string or MediaAsset object
  src: string | MediaAsset
  alt: string
  
  // Responsive image configuration
  responsive?: boolean
  breakpoints?: number[]
  formats?: ('webp' | 'avif' | 'jpeg' | 'png')[]
  quality?: number
  
  // Loading behavior
  lazyLoad?: boolean
  preload?: boolean
  async?: boolean
  
  // Styling
  objectFit?: 'cover' | 'contain' | 'fill' | 'scale-down' | 'none'
  objectPosition?: string
  aspectRatio?: string
  
  // Error handling
  fallbackSrc?: string
  errorMessage?: string
  
  // Performance
  priority?: boolean
  placeholder?: string | boolean
}

const props = withDefaults(defineProps<Props>(), {
  responsive: true,
  breakpoints: () => [320, 640, 768, 1024, 1280, 1920],
  formats: () => ['webp', 'jpeg'],
  quality: 85,
  lazyLoad: true,
  preload: false,
  async: true,
  objectFit: 'cover',
  objectPosition: 'center',
  priority: false,
  errorMessage: 'Failed to load image',
  placeholder: true
})

// Refs
const containerRef = ref<HTMLElement>()
const imageRef = ref<HTMLImageElement>()
const isLoaded = ref(false)
const hasError = ref(false)
const isIntersecting = ref(false)
const observer = ref<IntersectionObserver | null>(null)

// Computed properties
const mediaAsset = computed((): MediaAsset | null => {
  if (typeof props.src === 'string') {
    return {
      id: 'inline-image',
      type: 'image',
      url: props.src,
      alt: props.alt
    }
  }
  return props.src
})

const shouldLoad = computed(() => {
  if (props.priority || props.preload) return true
  if (!props.lazyLoad) return true
  return isIntersecting.value
})

const showPlaceholder = computed(() => {
  if (hasError.value) return false
  if (!props.placeholder) return false
  return !isLoaded.value && shouldLoad.value
})

const responsiveConfig = computed(() => {
  if (!props.responsive || !mediaAsset.value) return null
  
  return generateResponsiveImageSources(mediaAsset.value, {
    breakpoints: props.breakpoints,
    formats: props.formats,
    quality: props.quality,
    enableLazyLoading: props.lazyLoad
  })
})

const modernSources = computed(() => {
  if (!responsiveConfig.value) return []
  
  const sources = []
  
  // Generate sources for modern formats
  for (const format of props.formats) {
    if (format === 'jpeg' || format === 'png') continue // Skip fallback formats
    
    const srcset = props.breakpoints
      .map(width => `${getOptimizedUrl(format, width)} ${width}w`)
      .join(', ')
    
    sources.push({
      type: `image/${format}`,
      srcset,
      sizes: responsiveConfig.value.sizes
    })
  }
  
  return sources
})

const containerClasses = computed(() => [
  'relative overflow-hidden',
  {
    'w-full h-full': !props.aspectRatio,
  }
])

const containerStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.aspectRatio) {
    styles.aspectRatio = props.aspectRatio
  }
  
  return styles
})

const imageClasses = computed(() => [
  'w-full h-full transition-opacity duration-300',
  {
    'object-cover': props.objectFit === 'cover',
    'object-contain': props.objectFit === 'contain',
    'object-fill': props.objectFit === 'fill',
    'object-scale-down': props.objectFit === 'scale-down',
    'object-none': props.objectFit === 'none',
    'opacity-0': !isLoaded.value,
    'opacity-100': isLoaded.value,
  }
])

const placeholderStyles = computed(() => {
  if (typeof props.placeholder === 'string') {
    return {
      backgroundImage: `url(${props.placeholder})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center'
    }
  }
  return {}
})

// Methods
const getOptimizedUrl = (format: string, width: number): string => {
  if (!mediaAsset.value) return ''
  
  // This would integrate with your CDN service
  // For now, return the original URL
  return mediaAsset.value.url
}

const handleImageLoad = () => {
  isLoaded.value = true
  hasError.value = false
}

const handleImageError = () => {
  hasError.value = true
  isLoaded.value = false
  
  // Try fallback source if available
  if (props.fallbackSrc && imageRef.value) {
    imageRef.value.src = props.fallbackSrc
  }
}

const setupIntersectionObserver = () => {
  if (!props.lazyLoad || props.priority) return
  
  observer.value = createLazyLoadObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        isIntersecting.value = true
        observer.value?.disconnect()
      }
    })
  })
  
  if (observer.value && containerRef.value) {
    observer.value.observe(containerRef.value)
  }
}

const preloadImageIfNeeded = async () => {
  if (!props.preload || !mediaAsset.value) return
  
  try {
    await preloadImage(mediaAsset.value.url)
  } catch (error) {
    console.warn('Failed to preload image:', error)
  }
}

// Watchers
watch(shouldLoad, (newValue) => {
  if (newValue && props.preload) {
    preloadImageIfNeeded()
  }
})

// Lifecycle
onMounted(() => {
  setupIntersectionObserver()
  
  if (props.priority) {
    isIntersecting.value = true
    preloadImageIfNeeded()
  }
})

onUnmounted(() => {
  observer.value?.disconnect()
})
</script>

<style scoped>
/* Ensure proper aspect ratio handling */
[style*="aspect-ratio"] {
  @supports not (aspect-ratio: 1) {
    position: relative;
  }
  
  @supports not (aspect-ratio: 1) {
    &::before {
      content: '';
      display: block;
      padding-bottom: calc(100% / var(--aspect-ratio, 1));
    }
  }
  
  @supports not (aspect-ratio: 1) {
    > * {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }
  }
}

/* Optimize for high-DPI displays */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
  img {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
  }
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
  .transition-opacity {
    transition: none;
  }
  
  .animate-pulse {
    animation: none;
  }
}
</style>