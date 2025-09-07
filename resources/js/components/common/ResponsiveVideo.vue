<template>
  <div
    :class="containerClasses"
    role="region"
    :aria-label="ariaLabel"
  >
    <!-- Video Element -->
    <video
      ref="videoElement"
      :class="videoClasses"
      :poster="poster"
      :autoplay="autoplay && !prefersReducedMotion && shouldAutoplay"
      :muted="muted"
      :loop="loop"
      :controls="showControls"
      :preload="effectivePreload"
      :playsinline="playsinline"
      @play="handlePlay"
      @pause="handlePause"
      @ended="handleEnded"
      @loadstart="handleLoadStart"
      @loadeddata="handleLoadedData"
      @loadedmetadata="handleLoadedMetadata"
      @timeupdate="handleTimeUpdate"
      @progress="handleProgress"
      @error="handleError"
      @click="handleVideoClick"
      @volumechange="handleVolumeChange"
      @seeking="handleSeeking"
      @seeked="handleSeeked"
    >
      <!-- Video Sources with quality selection -->
      <source
        v-for="source in optimizedVideoSources"
        :key="source.src"
        :src="source.src"
        :type="source.type"
        :media="source.media"
      />
      
      <!-- Captions/Subtitles -->
      <track
        v-for="caption in captionTracks"
        :key="caption.src"
        :kind="caption.kind"
        :src="caption.src"
        :srclang="caption.srclang"
        :label="caption.label"
        :default="caption.default"
      />
      
      <!-- Chapters -->
      <track
        v-if="chapters"
        kind="chapters"
        :src="chapters"
        srclang="en"
        label="Chapters"
      />
      
      <!-- Fallback content -->
      <p class="text-gray-600 dark:text-gray-400 p-4">
        Your browser doesn't support HTML5 video. 
        <a :href="src.url" class="text-indigo-600 hover:text-indigo-500">
          Download the video
        </a>
      </p>
    </video>

    <!-- Custom Play Button Overlay -->
    <button
      v-if="showPlayButton && !isPlaying && !showControls"
      @click="togglePlay"
      :class="playButtonClasses"
      :aria-label="isPlaying ? 'Pause video' : 'Play video'"
    >
      <svg
        v-if="!isPlaying"
        class="w-12 h-12 text-white"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path d="M8 5v14l11-7z"/>
      </svg>
      <svg
        v-else
        class="w-12 h-12 text-white"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
      </svg>
    </button>

    <!-- Quality Selection Menu -->
    <div
      v-if="showQualitySelector && availableQualities.length > 1"
      class="absolute top-4 right-4 z-10"
    >
      <div class="relative">
        <button
          @click="toggleQualityMenu"
          class="px-3 py-1 bg-black bg-opacity-70 text-white text-sm rounded hover:bg-opacity-90 transition-all"
          :aria-label="'Video quality settings'"
        >
          {{ currentQuality }}
          <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        
        <div
          v-if="showQualityMenu"
          class="absolute top-full right-0 mt-1 bg-black bg-opacity-90 rounded shadow-lg min-w-24"
        >
          <button
            v-for="quality in availableQualities"
            :key="quality.label"
            @click="selectQuality(quality)"
            :class="[
              'block w-full px-3 py-2 text-left text-sm text-white hover:bg-gray-700 transition-colors',
              { 'bg-gray-700': quality.label === currentQuality }
            ]"
          >
            {{ quality.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Bandwidth Warning -->
    <div
      v-if="showBandwidthWarning"
      class="absolute bottom-16 left-4 right-4 bg-yellow-600 text-white p-3 rounded text-sm"
    >
      <div class="flex items-center justify-between">
        <span>Slow connection detected. Switch to lower quality?</span>
        <div class="flex space-x-2">
          <button
            @click="acceptLowerQuality"
            class="px-3 py-1 bg-yellow-700 rounded hover:bg-yellow-800 transition-colors"
          >
            Yes
          </button>
          <button
            @click="dismissBandwidthWarning"
            class="px-3 py-1 bg-yellow-700 rounded hover:bg-yellow-800 transition-colors"
          >
            No
          </button>
        </div>
      </div>
    </div>

    <!-- Loading Indicator -->
    <div
      v-if="isLoading"
      class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="text-center text-white">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
        <p class="text-sm">{{ loadingMessage }}</p>
        <div v-if="loadingProgress > 0" class="w-32 bg-gray-700 rounded-full h-2 mx-auto mt-2">
          <div 
            class="bg-white h-2 rounded-full transition-all duration-300"
            :style="{ width: `${loadingProgress}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="hasError"
      class="absolute inset-0 flex items-center justify-center bg-gray-900 text-white p-4"
    >
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm mb-2">{{ errorMessage }}</p>
        <div class="flex space-x-2 justify-center">
          <button
            @click="retryLoad"
            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors"
          >
            Retry
          </button>
          <button
            v-if="availableQualities.length > 1"
            @click="tryLowerQuality"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
          >
            Try Lower Quality
          </button>
        </div>
      </div>
    </div>

    <!-- Transcript Panel -->
    <div
      v-if="showTranscriptPanel && transcript"
      class="absolute inset-x-0 bottom-0 bg-black bg-opacity-90 text-white p-4 max-h-32 overflow-y-auto"
    >
      <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-medium">Transcript</h4>
        <button
          @click="toggleTranscript"
          class="text-gray-400 hover:text-white"
          aria-label="Close transcript"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="text-sm leading-relaxed">
        {{ transcript }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type { MediaAsset } from '@/types/components'
import { useIntersectionObserver } from '@/composables/useIntersectionObserver'
import { useAnalytics } from '@/composables/useAnalytics'

interface VideoQuality {
  label: string
  src: string
  type: string
  bandwidth?: number
  width?: number
  height?: number
}

interface CaptionTrack {
  kind: 'captions' | 'subtitles' | 'descriptions'
  src: string
  srclang: string
  label: string
  default?: boolean
}

interface Props {
  src: MediaAsset
  poster?: string
  autoplay?: boolean
  muted?: boolean
  loop?: boolean
  showControls?: boolean
  showCaptions?: boolean
  preload?: 'none' | 'metadata' | 'auto'
  playsinline?: boolean
  lazyLoad?: boolean
  showPlayButton?: boolean
  aspectRatio?: '16:9' | '4:3' | '1:1' | 'auto'
  objectFit?: 'contain' | 'cover' | 'fill'
  ariaLabel?: string
  // Enhanced video features
  showQualitySelector?: boolean
  enableBandwidthDetection?: boolean
  showTranscript?: boolean
  transcript?: string
  chapters?: string
  captionTracks?: CaptionTrack[]
  qualities?: VideoQuality[]
  trackAnalytics?: boolean
  analyticsId?: string
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: false,
  muted: true,
  loop: false,
  showControls: true,
  showCaptions: false,
  preload: 'metadata',
  playsinline: true,
  lazyLoad: true,
  showPlayButton: true,
  aspectRatio: '16:9',
  objectFit: 'cover',
  showQualitySelector: true,
  enableBandwidthDetection: true,
  showTranscript: false,
  trackAnalytics: true,
  captionTracks: () => [],
  qualities: () => []
})

const emit = defineEmits<{
  play: []
  pause: []
  ended: []
  loadstart: []
  loadeddata: []
  loadedmetadata: []
  timeupdate: [currentTime: number, duration: number]
  progress: [buffered: number]
  error: [error: Event]
  qualitychange: [quality: VideoQuality]
  seeking: [time: number]
  seeked: [time: number]
  volumechange: [volume: number, muted: boolean]
}>()

// Reactive state
const videoElement = ref<HTMLVideoElement>()
const isPlaying = ref(false)
const isLoading = ref(false)
const hasError = ref(false)
const hasLoaded = ref(false)
const loadingProgress = ref(0)
const loadingMessage = ref('Loading video...')
const errorMessage = ref('Unable to load video')

// Quality and bandwidth management
const currentQuality = ref('Auto')
const showQualityMenu = ref(false)
const showBandwidthWarning = ref(false)
const connectionSpeed = ref<number | null>(null)
const shouldAutoplay = ref(true)

// Analytics tracking
const playStartTime = ref<number | null>(null)
const totalWatchTime = ref(0)
const lastTimeUpdate = ref(0)
const viewMilestones = ref<Set<number>>(new Set())

// Transcript
const showTranscriptPanel = ref(false)

// Intersection observer for lazy loading
const { isIntersecting } = useIntersectionObserver(videoElement, {
  threshold: 0.1,
  rootMargin: '50px'
})

// Analytics
const { trackEvent } = useAnalytics()

// Computed properties
const prefersReducedMotion = computed(() => {
  if (typeof window === 'undefined') return false
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
})

const availableQualities = computed(() => {
  if (props.qualities && props.qualities.length > 0) {
    return props.qualities
  }

  // Generate qualities from MediaAsset
  const qualities: VideoQuality[] = []
  
  // Add main quality
  qualities.push({
    label: 'Auto',
    src: props.src.url,
    type: props.src.mimeType || 'video/mp4',
    bandwidth: props.src.size ? props.src.size * 8 : undefined,
    width: props.src.width,
    height: props.src.height
  })

  // Add mobile quality if available
  if (props.src.mobileUrl) {
    qualities.push({
      label: 'Mobile',
      src: props.src.mobileUrl,
      type: props.src.mimeType || 'video/mp4',
      bandwidth: props.src.size ? props.src.size * 4 : undefined,
      width: props.src.width ? Math.floor(props.src.width * 0.7) : undefined,
      height: props.src.height ? Math.floor(props.src.height * 0.7) : undefined
    })
  }

  return qualities
})

const optimizedVideoSources = computed(() => {
  const selectedQuality = availableQualities.value.find(q => q.label === currentQuality.value)
  
  if (selectedQuality && currentQuality.value !== 'Auto') {
    return [{
      src: selectedQuality.src,
      type: selectedQuality.type,
      media: selectedQuality.width ? `(max-width: ${selectedQuality.width}px)` : undefined
    }]
  }

  // Auto quality - return all sources for browser to choose
  return availableQualities.value.map(quality => ({
    src: quality.src,
    type: quality.type,
    media: quality.width ? `(max-width: ${quality.width}px)` : undefined
  }))
})

const effectivePreload = computed(() => {
  // Adjust preload based on connection speed and user preferences
  if (connectionSpeed.value && connectionSpeed.value < 1) {
    return 'none' // Slow connection
  }
  
  if (props.enableBandwidthDetection && connectionSpeed.value && connectionSpeed.value < 5) {
    return 'metadata' // Medium connection
  }
  
  return props.preload
})

const captionTracks = computed(() => {
  const tracks: CaptionTrack[] = []
  
  // Add provided caption tracks
  if (props.captionTracks) {
    tracks.push(...props.captionTracks)
  }
  
  // Add transcript as captions if available
  if (props.showCaptions && props.src.videoAsset?.transcript) {
    tracks.push({
      kind: 'captions',
      src: props.src.videoAsset.transcript,
      srclang: 'en',
      label: 'English captions',
      default: true
    })
  }
  
  return tracks
})

const chapters = computed(() => {
  return props.chapters || props.src.videoAsset?.chapters?.map(chapter => 
    `${chapter.time} ${chapter.title}`
  ).join('\n')
})

const transcript = computed(() => {
  return props.transcript || props.src.videoAsset?.transcript
})

const containerClasses = computed(() => [
  'responsive-video-container',
  'relative overflow-hidden bg-black',
  {
    'aspect-video': props.aspectRatio === '16:9',
    'aspect-[4/3]': props.aspectRatio === '4:3',
    'aspect-square': props.aspectRatio === '1:1',
    'rounded-lg': true
  }
])

const videoClasses = computed(() => [
  'w-full h-full',
  {
    'object-contain': props.objectFit === 'contain',
    'object-cover': props.objectFit === 'cover',
    'object-fill': props.objectFit === 'fill'
  }
])

const playButtonClasses = computed(() => [
  'absolute inset-0 flex items-center justify-center',
  'bg-black bg-opacity-30 hover:bg-opacity-50',
  'transition-all duration-200',
  'focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50',
  'group'
])

// Methods
const togglePlay = async () => {
  if (!videoElement.value) return

  try {
    if (isPlaying.value) {
      await videoElement.value.pause()
    } else {
      await videoElement.value.play()
    }
  } catch (error) {
    console.error('Error toggling video playback:', error)
    hasError.value = true
    errorMessage.value = 'Failed to play video'
  }
}

const toggleQualityMenu = () => {
  showQualityMenu.value = !showQualityMenu.value
}

const selectQuality = (quality: VideoQuality) => {
  const wasPlaying = isPlaying.value
  const currentTime = videoElement.value?.currentTime || 0
  
  currentQuality.value = quality.label
  showQualityMenu.value = false
  
  // Track quality change
  if (props.trackAnalytics) {
    trackEvent('video_quality_change', {
      video_id: props.analyticsId || props.src.id,
      from_quality: currentQuality.value,
      to_quality: quality.label,
      current_time: currentTime
    })
  }
  
  emit('qualitychange', quality)
  
  // Resume playback at same time if was playing
  if (wasPlaying && videoElement.value) {
    videoElement.value.currentTime = currentTime
    videoElement.value.play()
  }
}

const detectBandwidth = async () => {
  if (!props.enableBandwidthDetection || typeof navigator === 'undefined') return
  
  try {
    // Use Network Information API if available
    if ('connection' in navigator) {
      const connection = (navigator as any).connection
      if (connection && connection.effectiveType) {
        const speedMap = {
          'slow-2g': 0.5,
          '2g': 1,
          '3g': 5,
          '4g': 20
        }
        connectionSpeed.value = speedMap[connection.effectiveType as keyof typeof speedMap] || 10
      }
    }
    
    // Show bandwidth warning if connection is slow and high quality is selected
    if (connectionSpeed.value && connectionSpeed.value < 2 && currentQuality.value === 'Auto') {
      showBandwidthWarning.value = true
    }
  } catch (error) {
    console.warn('Failed to detect bandwidth:', error)
  }
}

const acceptLowerQuality = () => {
  const lowerQuality = availableQualities.value.find(q => q.label === 'Mobile') || 
                      availableQualities.value[availableQualities.value.length - 1]
  
  if (lowerQuality) {
    selectQuality(lowerQuality)
  }
  
  showBandwidthWarning.value = false
}

const dismissBandwidthWarning = () => {
  showBandwidthWarning.value = false
}

const tryLowerQuality = () => {
  const currentIndex = availableQualities.value.findIndex(q => q.label === currentQuality.value)
  const lowerQuality = availableQualities.value[currentIndex + 1] || 
                      availableQualities.value[availableQualities.value.length - 1]
  
  if (lowerQuality) {
    selectQuality(lowerQuality)
    retryLoad()
  }
}

const toggleTranscript = () => {
  showTranscriptPanel.value = !showTranscriptPanel.value
}

const handlePlay = () => {
  isPlaying.value = true
  playStartTime.value = Date.now()
  
  // Track play event
  if (props.trackAnalytics) {
    trackEvent('video_play', {
      video_id: props.analyticsId || props.src.id,
      quality: currentQuality.value,
      current_time: videoElement.value?.currentTime || 0,
      autoplay: props.autoplay
    })
  }
  
  emit('play')
}

const handlePause = () => {
  isPlaying.value = false
  
  // Update total watch time
  if (playStartTime.value) {
    totalWatchTime.value += Date.now() - playStartTime.value
    playStartTime.value = null
  }
  
  // Track pause event
  if (props.trackAnalytics) {
    trackEvent('video_pause', {
      video_id: props.analyticsId || props.src.id,
      current_time: videoElement.value?.currentTime || 0,
      watch_time: totalWatchTime.value
    })
  }
  
  emit('pause')
}

const handleEnded = () => {
  isPlaying.value = false
  
  // Update total watch time
  if (playStartTime.value) {
    totalWatchTime.value += Date.now() - playStartTime.value
    playStartTime.value = null
  }
  
  // Track completion
  if (props.trackAnalytics) {
    trackEvent('video_complete', {
      video_id: props.analyticsId || props.src.id,
      total_watch_time: totalWatchTime.value,
      quality: currentQuality.value
    })
  }
  
  emit('ended')
}

const handleLoadStart = () => {
  isLoading.value = true
  hasError.value = false
  loadingMessage.value = 'Loading video...'
  loadingProgress.value = 0
  emit('loadstart')
}

const handleLoadedData = () => {
  isLoading.value = false
  hasLoaded.value = true
  loadingProgress.value = 100
  
  // Track video loaded
  if (props.trackAnalytics) {
    trackEvent('video_loaded', {
      video_id: props.analyticsId || props.src.id,
      quality: currentQuality.value,
      duration: videoElement.value?.duration || 0
    })
  }
  
  emit('loadeddata')
}

const handleLoadedMetadata = () => {
  // Track metadata loaded
  if (props.trackAnalytics && videoElement.value) {
    trackEvent('video_metadata_loaded', {
      video_id: props.analyticsId || props.src.id,
      duration: videoElement.value.duration,
      width: videoElement.value.videoWidth,
      height: videoElement.value.videoHeight
    })
  }
  
  emit('loadedmetadata')
}

const handleTimeUpdate = () => {
  if (!videoElement.value) return
  
  const currentTime = videoElement.value.currentTime
  const duration = videoElement.value.duration
  
  // Track viewing milestones (25%, 50%, 75%)
  if (props.trackAnalytics && duration > 0) {
    const progress = (currentTime / duration) * 100
    const milestones = [25, 50, 75]
    
    milestones.forEach(milestone => {
      if (progress >= milestone && !viewMilestones.value.has(milestone)) {
        viewMilestones.value.add(milestone)
        trackEvent('video_progress', {
          video_id: props.analyticsId || props.src.id,
          milestone: milestone,
          current_time: currentTime,
          duration: duration
        })
      }
    })
  }
  
  lastTimeUpdate.value = currentTime
  emit('timeupdate', currentTime, duration)
}

const handleProgress = () => {
  if (!videoElement.value) return
  
  const buffered = videoElement.value.buffered
  if (buffered.length > 0) {
    const bufferedEnd = buffered.end(buffered.length - 1)
    const duration = videoElement.value.duration
    const bufferedPercent = duration > 0 ? (bufferedEnd / duration) * 100 : 0
    
    loadingProgress.value = Math.min(bufferedPercent, 100)
    emit('progress', bufferedPercent)
  }
}

const handleError = (error: Event) => {
  isLoading.value = false
  hasError.value = true
  
  const target = error.target as HTMLVideoElement
  let message = 'Unable to load video'
  
  if (target && target.error) {
    switch (target.error.code) {
      case MediaError.MEDIA_ERR_ABORTED:
        message = 'Video loading was aborted'
        break
      case MediaError.MEDIA_ERR_NETWORK:
        message = 'Network error occurred'
        break
      case MediaError.MEDIA_ERR_DECODE:
        message = 'Video format not supported'
        break
      case MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED:
        message = 'Video source not supported'
        break
    }
  }
  
  errorMessage.value = message
  
  // Track error
  if (props.trackAnalytics) {
    trackEvent('video_error', {
      video_id: props.analyticsId || props.src.id,
      error_code: target?.error?.code,
      error_message: message,
      quality: currentQuality.value
    })
  }
  
  emit('error', error)
}

const handleSeeking = () => {
  if (!videoElement.value) return
  
  const seekTime = videoElement.value.currentTime
  
  // Track seeking
  if (props.trackAnalytics) {
    trackEvent('video_seek', {
      video_id: props.analyticsId || props.src.id,
      from_time: lastTimeUpdate.value,
      to_time: seekTime
    })
  }
  
  emit('seeking', seekTime)
}

const handleSeeked = () => {
  if (!videoElement.value) return
  
  const seekTime = videoElement.value.currentTime
  emit('seeked', seekTime)
}

const handleVolumeChange = () => {
  if (!videoElement.value) return
  
  const volume = videoElement.value.volume
  const muted = videoElement.value.muted
  
  // Track volume changes
  if (props.trackAnalytics) {
    trackEvent('video_volume_change', {
      video_id: props.analyticsId || props.src.id,
      volume: volume,
      muted: muted
    })
  }
  
  emit('volumechange', volume, muted)
}

const handleVideoClick = () => {
  if (!props.showControls) {
    togglePlay()
  }
}

const retryLoad = () => {
  if (videoElement.value) {
    hasError.value = false
    videoElement.value.load()
  }
}

// Lazy loading logic
watch(isIntersecting, (intersecting) => {
  if (intersecting && props.lazyLoad && !hasLoaded.value && videoElement.value) {
    videoElement.value.load()
  }
})

// Lifecycle
onMounted(async () => {
  await nextTick()
  
  // Detect bandwidth
  await detectBandwidth()
  
  if (!props.lazyLoad && videoElement.value) {
    videoElement.value.load()
  }
})

onUnmounted(() => {
  if (videoElement.value) {
    videoElement.value.pause()
  }
})
</script>

<style scoped>
.responsive-video-container {
  container-type: inline-size;
}

/* Ensure video fills container properly */
.responsive-video-container video {
  display: block;
}

/* Custom play button hover effects */
.responsive-video-container .play-button:hover svg {
  transform: scale(1.1);
}

/* Loading animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .responsive-video-container {
    border: 2px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .responsive-video-container *,
  .responsive-video-container *::before,
  .responsive-video-container *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus management */
.responsive-video-container:focus-within {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}
</style>