<template>
  <div 
    :class="[
      'lazy-image-container',
      { 'loading': isLoading, 'loaded': isLoaded, 'error': hasError }
    ]"
    :style="{ aspectRatio: aspectRatio }"
  >
    <!-- Placeholder/Skeleton -->
    <div 
      v-if="isLoading && !hasError" 
      class="lazy-image-placeholder"
      :style="placeholderStyle"
    >
      <div class="animate-pulse bg-gray-200 dark:bg-gray-700 w-full h-full rounded" />
      <div v-if="showLoadingIcon" class="absolute inset-0 flex items-center justify-center">
        <svg class="animate-spin h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>

    <!-- Actual Image -->
    <img
      ref="imageRef"
      v-show="isLoaded && !hasError"
      :src="optimizedSrc"
      :alt="alt"
      :class="imageClasses"
      :loading="nativeLoading ? 'lazy' : 'eager'"
      @load="handleLoad"
      @error="handleError"
    />

    <!-- Error State -->
    <div 
      v-if="hasError" 
      class="lazy-image-error flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400"
    >
      <div class="text-center">
        <svg class="mx-auto h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="text-sm">{{ errorMessage }}</p>
        <button 
          v-if="allowRetry"
          @click="retry"
          class="mt-2 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Retry
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { createIntersectionObserver } from '@/utils/lazy-loading'

interface Props {
  src: string
  alt: string
  width?: number
  height?: number
  aspectRatio?: string
  quality?: number
  format?: 'webp' | 'avif' | 'jpg' | 'png'
  sizes?: string
  placeholder?: string
  showLoadingIcon?: boolean
  nativeLoading?: boolean
  allowRetry?: boolean
  errorMessage?: string
  class?: string
  eager?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  quality: 85,
  format: 'webp',
  showLoadingIcon: true,
  nativeLoading: false,
  allowRetry: true,
  errorMessage: 'Failed to load image',
  eager: false
})

const imageRef = ref<HTMLImageElement>()
const isLoading = ref(true)
const isLoaded = ref(false)
const hasError = ref(false)
const retryCount = ref(0)
const maxRetries = 3

let observer: IntersectionObserver | null = null

// Computed properties
const optimizedSrc = computed(() => {
  if (!props.src) return ''
  
  // If it's already an optimized URL or external URL, return as-is
  if (props.src.includes('?') || props.src.startsWith('http')) {
    return props.src
  }
  
  // Build optimization parameters
  const params = new URLSearchParams()
  
  if (props.width) params.set('w', props.width.toString())
  if (props.height) params.set('h', props.height.toString())
  if (props.quality !== 85) params.set('q', props.quality.toString())
  if (props.format !== 'webp') params.set('f', props.format)
  
  const queryString = params.toString()
  return queryString ? `${props.src}?${queryString}` : props.src
})

const placeholderStyle = computed(() => ({
  backgroundColor: props.placeholder || '#f3f4f6',
  aspectRatio: props.aspectRatio || (props.width && props.height ? `${props.width}/${props.height}` : 'auto')
}))

const imageClasses = computed(() => [
  'lazy-image',
  'transition-opacity duration-300',
  props.class
])

// Methods
const handleLoad = () => {
  isLoading.value = false
  isLoaded.value = true
  hasError.value = false
}

const handleError = () => {
  isLoading.value = false
  hasError.value = true
  
  // Auto-retry with exponential backoff
  if (retryCount.value < maxRetries) {
    setTimeout(() => {
      retry()
    }, Math.pow(2, retryCount.value) * 1000)
  }
}

const retry = () => {
  retryCount.value++
  isLoading.value = true
  hasError.value = false
  
  // Force reload by changing src slightly
  if (imageRef.value) {
    const currentSrc = imageRef.value.src
    imageRef.value.src = ''
    setTimeout(() => {
      if (imageRef.value) {
        imageRef.value.src = currentSrc
      }
    }, 100)
  }
}

const startLoading = () => {
  if (!imageRef.value || isLoaded.value) return
  
  // Start loading the image
  isLoading.value = true
}

// Lifecycle
onMounted(() => {
  if (props.eager) {
    startLoading()
    return
  }
  
  // Set up intersection observer for lazy loading
  if (imageRef.value) {
    observer = createIntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          startLoading()
          observer?.unobserve(entry.target)
        }
      })
    })
    
    observer.observe(imageRef.value.parentElement!)
  }
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})

// Watch for src changes
watch(() => props.src, () => {
  isLoading.value = true
  isLoaded.value = false
  hasError.value = false
  retryCount.value = 0
})
</script>

<style scoped>
.lazy-image-container {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lazy-image-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lazy-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lazy-image-error {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.loading .lazy-image {
  opacity: 0;
}

.loaded .lazy-image {
  opacity: 1;
}
</style>