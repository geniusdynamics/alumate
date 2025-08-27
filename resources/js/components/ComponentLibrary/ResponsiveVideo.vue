<template>
  <div 
    ref="containerRef"
    :class="containerClasses"
    :style="containerStyles"
  >
    <!-- Video Element -->
    <video
      v-if="shouldLoadVideo"
      ref="videoRef"
      :class="videoClasses"
      v-bind="videoAttributes"
      @loadstart="handleLoadStart"
      @loadedmetadata="handleLoadedMetadata"
      @canplay="handleCanPlay"
      @play="handlePlay"
      @pause="handlePause"
      @error="handleError"
      @progress="handleProgress"
    >
      <source
        v-for="source in videoSources"
        :key="source.src"
        :src="source.src"
        :type="source.type"
      >
      
      <!-- Fallback content -->
      <p class="sr-only">
        Your browser does not support the video tag.
        <a :href="fallbackImageSrc" target="_blank" rel="noopener">
          View static image instead
        </a>
      </p>
    </video>

    <!-- Poster/Placeholder Image -->
    <ResponsiveImage
      v-if="showPoster"
      :src="posterConfig"
      :alt="posterAlt"
      :lazy-load="lazyLoad"
      :priority="priority"
      class="absolute inset-0"
      object-fit="cover"
    >
      <!-- Play Button Overlay -->
      <template #overlay>
        <button
          v-if="showPlayButton"
          @click="playVideo"
          class="bg-black/50 hover:bg-black/70 text-white rounded-full p-4 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-black/50"
          :aria-label="playButtonLabel"
        >
          <svg class="w-8 h-8 ml-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
          </svg>
        </button>
      </template>
    </ResponsiveImage>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="absolute inset-0 bg-gray-900/50 flex items-center justify-center"
      role="status"
      :aria-label="loadingMessage"
    >
      <div class="bg-black/50 rounded-lg p-4 text-white text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white mx-auto mb-2"></div>
        <p class="text-sm">{{ loadingMessage }}</p>
        <div v-if="loadingProgress > 0" class="mt-2 bg-gray-700 rounded-full h-1">
          <div 
            class="bg-white h-1 rounded-full transition-all duration-300"
            :style="{ width: `${loadingProgress}%` }"
          />
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="hasError"
      class="absolute inset-0 bg-gray-100 dark:bg-gray-800 flex items-center justify-center"
      role="alert"
      :aria-label="errorMessage"
    >
      <div class="text-center text-gray-500 dark:text-gray-400 p-4">
        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <p class="text-sm mb-2">{{ errorMessage }}</p>
        <button
          v-if="fallbackImageSrc"
          @click="showFallbackImage"
          class="text-blue-500 hover:text-blue-600 text-sm underline"
        >
          Show static image instead
        </button>
      </div>
    </div>

    <!-- Controls Overlay (for custom controls) -->
    <div
      v-if="showCustomControls && !hasError"
      class="absolute bottom-4 left-4 right-4 flex items-center justify-between bg-black/50 rounded-lg p-2"
    >
      <button
        @click="togglePlayPause"
        class="text-white hover:text-gray-300 p-2"
        :aria-label="isPlaying ? 'Pause video' : 'Play video'"
      >
        <svg v-if="isPlaying" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
        </svg>
      </button>

      <button
        @click="toggleMute"
        class="text-white hover:text-gray-300 p-2"
        :aria-label="isMuted ? 'Unmute video' : 'Mute video'"
      >
        <svg v-if="isMuted" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.793L4.828 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.828l3.555-3.793A1 1 0 019.383 3.076zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
        <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.793L4.828 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.828l3.555-3.793A1 1 0 019.383 3.076zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Bandwidth Warning -->
    <div
      v-if="showBandwidthWarning"
      class="absolute top-4 left-4 right-4 bg-yellow-500/90 text-yellow-900 p-3 rounded-lg text-sm"
      role="alert"
    >
      <p class="font-medium">Slow connection detected</p>
      <p>Video quality has been reduced to improve loading time.</p>
      <button
        @click="dismissBandwidthWarning"
        class="mt-1 text-yellow-800 hover:text-yellow-900 underline"
      >
        Dismiss
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type { MediaAsset } from '@/types/components'
import { generateVideoSources, createLazyLoadObserver } from '@/utils/mediaOptimization'
import ResponsiveImage from './ResponsiveImage.vue'

interface Props {
  // Video source configuration
  src: string | (MediaAsset & {
    autoplay?: boolean
    muted?: boolean
    loop?: boolean
    poster?: string
    preload?: 'none' | 'metadata' | 'auto'
    mobileVideo?: MediaAsset
    disableOnMobile?: boolean
    quality?: 'low' | 'medium' | 'high' | 'auto'
  })
  
  // Poster image
  poster?: string | MediaAsset
  posterAlt?: string
  
  // Playback behavior
  autoplay?: boolean
  muted?: boolean
  loop?: boolean
  controls?: boolean
  preload?: 'none' | 'metadata' | 'auto'
  
  // Loading behavior
  lazyLoad?: boolean
  priority?: boolean
  
  // Mobile optimization
  disableOnMobile?: boolean
  mobileQuality?: 'low' | 'medium' | 'high'
  
  // Styling
  objectFit?: 'cover' | 'contain' | 'fill'
  aspectRatio?: string
  
  // Accessibility
  playButtonLabel?: string
  loadingMessage?: string
  errorMessage?: string
  
  // Custom controls
  customControls?: boolean
  
  // Bandwidth handling
  adaptiveBitrate?: boolean
  showBandwidthWarning?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: true,
  muted: true,
  loop: true,
  controls: false,
  preload: 'metadata',
  lazyLoad: true,
  priority: false,
  disableOnMobile: false,
  mobileQuality: 'medium',
  objectFit: 'cover',
  posterAlt: 'Video poster image',
  playButtonLabel: 'Play video',
  loadingMessage: 'Loading video...',
  errorMessage: 'Failed to load video',
  customControls: false,
  adaptiveBitrate: true,
  showBandwidthWarning: true
})

// Refs
const containerRef = ref<HTMLElement>()
const videoRef = ref<HTMLVideoElement>()
const isLoaded = ref(false)
const isLoading = ref(false)
const hasError = ref(false)
const isPlaying = ref(false)
const isMuted = ref(props.muted)
const isIntersecting = ref(false)
const loadingProgress = ref(0)
const showBandwidthWarningState = ref(false)
const observer = ref<IntersectionObserver | null>(null)

// Computed properties
const mediaAsset = computed(() => {
  if (typeof props.src === 'string') {
    return {
      id: 'inline-video',
      type: 'video' as const,
      url: props.src,
      autoplay: props.autoplay,
      muted: props.muted,
      loop: props.loop,
      poster: props.poster as string,
      preload: props.preload,
      disableOnMobile: props.disableOnMobile,
      quality: props.mobileQuality
    }
  }
  return props.src
})

const shouldLoadVideo = computed(() => {
  if (props.priority) return true
  if (!props.lazyLoad) return true
  return isIntersecting.value && !hasError.value
})

const videoConfig = computed(() => {
  if (!mediaAsset.value) return null
  return generateVideoSources(mediaAsset.value)
})

const videoSources = computed(() => {
  return videoConfig.value?.sources || []
})

const videoAttributes = computed(() => {
  const attrs = videoConfig.value?.attributes || {}
  
  return {
    ...attrs,
    poster: posterSrc.value,
    controls: props.controls && !props.customControls,
    'aria-label': `Video: ${props.posterAlt}`,
    'data-object-fit': props.objectFit
  }
})

const posterSrc = computed(() => {
  if (typeof props.poster === 'string') return props.poster
  if (props.poster?.url) return props.poster.url
  return videoConfig.value?.poster
})

const posterConfig = computed(() => {
  if (typeof props.poster === 'string') {
    return {
      id: 'video-poster',
      type: 'image' as const,
      url: props.poster,
      alt: props.posterAlt
    }
  }
  return props.poster || {
    id: 'video-poster',
    type: 'image' as const,
    url: posterSrc.value || '',
    alt: props.posterAlt
  }
})

const fallbackImageSrc = computed(() => {
  return posterSrc.value || ''
})

const showPoster = computed(() => {
  return !shouldLoadVideo.value || (!isLoaded.value && posterSrc.value) || hasError.value
})

const showPlayButton = computed(() => {
  return showPoster.value && !props.autoplay && !isLoading.value && !hasError.value
})

const showCustomControls = computed(() => {
  return props.customControls && isLoaded.value && !showPoster.value
})

const containerClasses = computed(() => [
  'relative overflow-hidden bg-gray-900',
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

const videoClasses = computed(() => [
  'w-full h-full',
  {
    'object-cover': props.objectFit === 'cover',
    'object-contain': props.objectFit === 'contain',
    'object-fill': props.objectFit === 'fill',
  }
])

// Methods
const playVideo = async () => {
  if (!videoRef.value) return
  
  try {
    await videoRef.value.play()
  } catch (error) {
    console.error('Failed to play video:', error)
    handleError()
  }
}

const pauseVideo = () => {
  if (!videoRef.value) return
  videoRef.value.pause()
}

const togglePlayPause = () => {
  if (isPlaying.value) {
    pauseVideo()
  } else {
    playVideo()
  }
}

const toggleMute = () => {
  if (!videoRef.value) return
  
  videoRef.value.muted = !videoRef.value.muted
  isMuted.value = videoRef.value.muted
}

const showFallbackImage = () => {
  hasError.value = false
  // This would show the poster image instead
}

const dismissBandwidthWarning = () => {
  showBandwidthWarningState.value = false
}

// Event handlers
const handleLoadStart = () => {
  isLoading.value = true
  loadingProgress.value = 0
}

const handleLoadedMetadata = () => {
  isLoaded.value = true
}

const handleCanPlay = () => {
  isLoading.value = false
  loadingProgress.value = 100
  
  // Check if we should show bandwidth warning
  if (props.showBandwidthWarning && props.adaptiveBitrate) {
    checkBandwidthAndWarn()
  }
}

const handlePlay = () => {
  isPlaying.value = true
}

const handlePause = () => {
  isPlaying.value = false
}

const handleError = () => {
  hasError.value = true
  isLoading.value = false
  console.error('Video failed to load')
}

const handleProgress = () => {
  if (!videoRef.value) return
  
  const buffered = videoRef.value.buffered
  if (buffered.length > 0) {
    const loadedEnd = buffered.end(buffered.length - 1)
    const duration = videoRef.value.duration
    if (duration > 0) {
      loadingProgress.value = (loadedEnd / duration) * 100
    }
  }
}

const checkBandwidthAndWarn = () => {
  // Check connection speed and show warning if needed
  if (typeof navigator !== 'undefined' && 'connection' in navigator) {
    const connection = (navigator as any).connection
    if (connection && connection.effectiveType && 
        ['slow-2g', '2g', '3g'].includes(connection.effectiveType)) {
      showBandwidthWarningState.value = true
    }
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

const handleReducedMotion = () => {
  if (typeof window === 'undefined') return
  
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  if (prefersReducedMotion && videoRef.value) {
    videoRef.value.pause()
  }
}

// Watchers
watch(shouldLoadVideo, async (newValue) => {
  if (newValue) {
    await nextTick()
    handleReducedMotion()
  }
})

// Lifecycle
onMounted(() => {
  setupIntersectionObserver()
  
  if (props.priority) {
    isIntersecting.value = true
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

/* Video object-fit polyfill for older browsers */
video[data-object-fit="cover"] {
  object-fit: cover;
  object-position: center;
}

video[data-object-fit="contain"] {
  object-fit: contain;
  object-position: center;
}

video[data-object-fit="fill"] {
  object-fit: fill;
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
  .transition-colors {
    transition: none;
  }
  
  .animate-spin {
    animation: none;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .bg-black\/50 {
    background-color: rgba(0, 0, 0, 0.8);
  }
  
  .text-white {
    color: white;
  }
}
</style>