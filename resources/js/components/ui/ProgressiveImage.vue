<template>
  <div 
    ref="containerRef"
    class="progressive-image-container"
    :class="containerClass"
  >
    <!-- Placeholder/Loading state -->
    <div 
      v-if="!isLoaded && !isError"
      class="progressive-image-placeholder"
      :class="placeholderClass"
      :style="placeholderStyle"
    >
      <div v-if="showSkeleton" class="skeleton-loader" />
      <slot name="placeholder" />
    </div>

    <!-- Error state -->
    <div 
      v-if="isError"
      class="progressive-image-error"
      :class="errorClass"
    >
      <slot name="error">
        <div class="flex items-center justify-center h-full text-gray-400">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
      </slot>
    </div>

    <!-- Main image -->
    <picture v-if="!isError">
      <!-- WebP source for modern browsers -->
      <source 
        v-if="webpSrc" 
        :srcset="webpSrc" 
        type="image/webp"
      >
      
      <!-- Fallback image -->
      <img
        ref="imageRef"
        :src="currentSrc"
        :alt="alt"
        :class="[
          'progressive-image',
          imageClass,
          {
            'opacity-0': !isLoaded,
            'opacity-100': isLoaded,
            'transition-opacity duration-300': !disableTransition
          }
        ]"
        :loading="nativeLazyLoading ? 'lazy' : 'eager'"
        @load="handleImageLoad"
        @error="handleImageError"
      />
    </picture>

    <!-- Loading overlay -->
    <div 
      v-if="showLoadingOverlay && !isLoaded && !isError"
      class="progressive-image-loading-overlay"
    >
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useLazyImage } from '@/composables/useLazyLoading'

interface Props {
  src: string
  webpSrc?: string
  alt: string
  placeholder?: string
  containerClass?: string
  imageClass?: string
  placeholderClass?: string
  errorClass?: string
  lazy?: boolean
  nativeLazyLoading?: boolean
  showSkeleton?: boolean
  showLoadingOverlay?: boolean
  disableTransition?: boolean
  aspectRatio?: string
  rootMargin?: string
  threshold?: number
}

const props = withDefaults(defineProps<Props>(), {
  lazy: true,
  nativeLazyLoading: false,
  showSkeleton: true,
  showLoadingOverlay: false,
  disableTransition: false,
  aspectRatio: '16/9',
  rootMargin: '50px',
  threshold: 0.1
})

const emit = defineEmits<{
  load: [event: Event]
  error: [event: Event]
}>()

const containerRef = ref<HTMLElement | null>(null)

// Use lazy loading if enabled
const lazyOptions = {
  rootMargin: props.rootMargin,
  threshold: props.threshold,
  once: true
}

const {
  imageRef,
  currentSrc,
  isLoaded,
  isError
} = props.lazy 
  ? useLazyImage(props.src, lazyOptions)
  : (() => {
      const imageRef = ref<HTMLImageElement | null>(null)
      const isLoaded = ref(false)
      const isError = ref(false)
      const currentSrc = ref(props.src)
      return { imageRef, currentSrc, isLoaded, isError }
    })()

// Set src immediately if not using lazy loading
if (!props.lazy) {
  currentSrc.value = props.src
}

const placeholderStyle = computed(() => ({
  aspectRatio: props.aspectRatio,
  backgroundColor: '#f3f4f6'
}))

const handleImageLoad = (event: Event) => {
  if (!props.lazy) {
    isLoaded.value = true
  }
  emit('load', event)
}

const handleImageError = (event: Event) => {
  if (!props.lazy) {
    isError.value = true
  }
  emit('error', event)
}

// Watch for src changes
watch(() => props.src, (newSrc) => {
  if (!props.lazy) {
    currentSrc.value = newSrc
    isLoaded.value = false
    isError.value = false
  }
})

// Detect WebP support
const supportsWebP = ref(false)

const checkWebPSupport = () => {
  const canvas = document.createElement('canvas')
  canvas.width = 1
  canvas.height = 1
  const dataURL = canvas.toDataURL('image/webp')
  supportsWebP.value = dataURL.indexOf('data:image/webp') === 0
}

if (typeof window !== 'undefined') {
  checkWebPSupport()
}
</script>

<style scoped>
.progressive-image-container {
  @apply relative overflow-hidden;
}

.progressive-image-placeholder {
  @apply absolute inset-0 flex items-center justify-center;
}

.progressive-image-error {
  @apply absolute inset-0 flex items-center justify-center bg-gray-100;
}

.progressive-image {
  @apply w-full h-full object-cover;
}

.progressive-image-loading-overlay {
  @apply absolute inset-0 flex items-center justify-center bg-white bg-opacity-75;
}

.skeleton-loader {
  @apply w-full h-full bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 animate-pulse;
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}
</style>