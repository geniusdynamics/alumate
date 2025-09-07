<template>
  <div
    :class="containerClasses"
    role="img"
    :aria-label="alt"
  >
    <!-- Main Image -->
    <img
      ref="imageElement"
      :class="imageClasses"
      :src="shouldLoad ? imageSrc : placeholder"
      :alt="alt"
      :loading="lazyLoad ? 'lazy' : 'eager'"
      :decoding="decoding"
      :sizes="sizes"
      :srcset="shouldLoad ? srcSet : undefined"
      @load="handleLoad"
      @error="handleError"
      @click="handleClick"
    />

    <!-- Loading State -->
    <div
      v-if="isLoading && !hasError"
      :class="loadingClasses"
    >
      <div class="animate-pulse bg-gray-300 dark:bg-gray-600 w-full h-full"></div>
    </div>

    <!-- Error State -->
    <div
      v-if="hasError"
      :class="errorClasses"
    >
      <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <p class="text-xs text-gray-500">Image unavailable</p>
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
import { computed, ref, onMounted, watch } from 'vue'
import type { MediaAsset } from '@/types/components'
import { useIntersectionObserver } from '@/composables/useIntersectionObserver'

interface Props {
  src: MediaAsset
  alt: string
  lazyLoad?: boolean
  aspectRatio?: '16:9' | '4:3' | '1:1' | '3:2' | 'auto'
  objectFit?: 'contain' | 'cover' | 'fill' | 'scale-down'
  sizes?: string
  decoding?: 'sync' | 'async' | 'auto'
  placeholder?: string
  fallbackSrc?: string
  clickable?: boolean
  rounded?: boolean | 'sm' | 'md' | 'lg' | 'xl' | 'full'
}

const props = withDefaults(defineProps<Props>(), {
  lazyLoad: true,
  aspectRatio: 'auto',
  objectFit: 'cover',
  decoding: 'async',
  clickable: false,
  rounded: false
})

const emit = defineEmits<{
  load: [event: Event]
  error: [event: Event]
  click: [event: MouseEvent]
}>()

// Reactive state
const imageElement = ref<HTMLImageElement>()
const isLoading = ref(true)
const hasError = ref(false)
const hasLoaded = ref(false)

// Intersection observer for lazy loading
const { isIntersecting } = useIntersectionObserver(imageElement, {
  threshold: 0.1,
  rootMargin: '50px'
})

// Computed properties
const shouldLoad = computed(() => {
  return !props.lazyLoad || isIntersecting.value || hasLoaded.value
})

const imageSrc = computed(() => {
  // Use CDN URL if available, otherwise fallback to regular URL
  return props.src.cdnUrl || props.src.url
})

const srcSet = computed(() => {
  if (!props.src.srcSet?.length) return undefined
  
  return props.src.srcSet
    .map(source => `${source.url} ${source.width}w`)
    .join(', ')
})

const containerClasses = computed(() => [
  'responsive-image-container',
  'relative overflow-hidden bg-gray-100 dark:bg-gray-800',
  {
    // Aspect ratios
    'aspect-video': props.aspectRatio === '16:9',
    'aspect-[4/3]': props.aspectRatio === '4:3',
    'aspect-square': props.aspectRatio === '1:1',
    'aspect-[3/2]': props.aspectRatio === '3:2',
    
    // Rounded corners
    'rounded-sm': props.rounded === 'sm',
    'rounded': props.rounded === true || props.rounded === 'md',
    'rounded-lg': props.rounded === 'lg',
    'rounded-xl': props.rounded === 'xl',
    'rounded-full': props.rounded === 'full',
    
    // Clickable
    'cursor-pointer hover:opacity-90 transition-opacity': props.clickable
  }
])

const imageClasses = computed(() => [
  'w-full h-full transition-opacity duration-300',
  {
    'object-contain': props.objectFit === 'contain',
    'object-cover': props.objectFit === 'cover',
    'object-fill': props.objectFit === 'fill',
    'object-scale-down': props.objectFit === 'scale-down',
    'opacity-0': isLoading.value && !hasError.value,
    'opacity-100': !isLoading.value || hasError.value
  }
])

const loadingClasses = computed(() => [
  'absolute inset-0 flex items-center justify-center',
  {
    'hidden': !isLoading.value || hasError.value
  }
])

const errorClasses = computed(() => [
  'absolute inset-0 flex flex-col items-center justify-center text-gray-400',
  {
    'hidden': !hasError.value
  }
])

// Generate placeholder (low-quality image placeholder)
const placeholder = computed(() => {
  if (props.placeholder) return props.placeholder
  if (props.src.placeholder) return props.src.placeholder
  
  // Generate a simple gray placeholder
  return `data:image/svg+xml;base64,${btoa(`
    <svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
      <rect width="100%" height="100%" fill="#f3f4f6"/>
    </svg>
  `)}`
})

// Methods
const handleLoad = (event: Event) => {
  isLoading.value = false
  hasLoaded.value = true
  hasError.value = false
  emit('load', event)
}

const handleError = (event: Event) => {
  isLoading.value = false
  hasError.value = true
  
  // Try fallback source if available
  if (props.fallbackSrc && imageElement.value) {
    imageElement.value.src = props.fallbackSrc
    return
  }
  
  // Try the regular URL if CDN failed
  if (props.src.cdnUrl && props.src.url && imageElement.value) {
    imageElement.value.src = props.src.url
    return
  }
  
  emit('error', event)
}

const handleClick = (event: MouseEvent) => {
  if (props.clickable) {
    emit('click', event)
  }
}

// Watch for intersection changes to trigger loading
watch(shouldLoad, (should) => {
  if (should && !hasLoaded.value && imageElement.value) {
    // Force reload if lazy loading is triggered
    const currentSrc = imageElement.value.src
    imageElement.value.src = ''
    imageElement.value.src = currentSrc
  }
})

// Lifecycle
onMounted(() => {
  // If not lazy loading, start loading immediately
  if (!props.lazyLoad) {
    isLoading.value = true
  }
})
</script>

<style scoped>
.responsive-image-container {
  container-type: inline-size;
}

/* Ensure image fills container properly */
.responsive-image-container img {
  display: block;
}

/* Loading animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .responsive-image-container {
    border: 1px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .responsive-image-container *,
  .responsive-image-container *::before,
  .responsive-image-container *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus management */
.responsive-image-container:focus-within {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* Print styles */
@media print {
  .responsive-image-container {
    break-inside: avoid;
  }
}
</style>