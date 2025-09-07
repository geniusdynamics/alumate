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
    <!-- Video Grid/Layout -->
    <div
      :class="videoContainerClasses"
      role="region"
      :aria-label="videoContainerAriaLabel"
    >
      <div
        v-for="(video, index) in optimizedVideos"
        :key="video.id"
        :class="videoItemClasses"
      >
        <!-- Video Player -->
        <ResponsiveVideo
          :src="video"
          :poster="video.thumbnail || video.poster"
          :autoplay="shouldAutoplay(index)"
          :muted="config.videoSettings?.muted ?? true"
          :loop="config.videoSettings?.loop ?? false"
          :show-controls="config.videoSettings?.showControls ?? true"
          :show-captions="config.videoSettings?.showCaptions ?? false"
          :preload="effectivePreload"
          :playsinline="config.videoSettings?.playsinline ?? true"
          :lazy-load="config.performance.lazyLoading"
          :show-play-button="!config.videoSettings?.showControls"
          :aspect-ratio="aspectRatio"
          :object-fit="objectFit"
          :aria-label="video.alt || `Video ${index + 1}`"
          :show-quality-selector="showQualitySelector"
          :enable-bandwidth-detection="config.performance.bandwidthAdaptive"
          :show-transcript="showTranscript"
          :transcript="video.videoAsset?.transcript"
          :chapters="video.videoAsset?.chapters?.map(c => `${c.time} ${c.title}`).join('\n')"
          :caption-tracks="getCaptionTracks(video)"
          :qualities="getVideoQualities(video)"
          :track-analytics="trackAnalytics"
          :analytics-id="`${analyticsId}-video-${index}`"
          @play="handleVideoPlay(index)"
          @pause="handleVideoPause(index)"
          @ended="handleVideoEnded(index)"
          @loadstart="handleVideoLoadStart(index)"
          @loadeddata="handleVideoLoadedData(index)"
          @error="handleVideoError(index, $event)"
          @timeupdate="handleVideoTimeUpdate(index, $event)"
        />

        <!-- Video Overlay Information -->
        <div
          v-if="showVideoInfo && (video.title || video.description)"
          class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black via-black/70 to-transparent p-4"
        >
          <h3
            v-if="video.title"
            class="text-white font-semibold text-lg mb-1"
          >
            {{ video.title }}
          </h3>
          <p
            v-if="video.description"
            class="text-gray-200 text-sm line-clamp-2"
          >
            {{ video.description }}
          </p>
          
          <!-- Video Duration -->
          <div
            v-if="video.videoAsset?.duration"
            class="absolute top-4 right-4 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded"
          >
            {{ formatDuration(video.videoAsset.duration) }}
          </div>
        </div>

        <!-- Video Loading State -->
        <div
          v-if="videoLoadingStates[index]"
          class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50"
        >
          <div class="text-center text-white">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
            <p class="text-sm">Loading video...</p>
          </div>
        </div>

        <!-- Video Error State -->
        <div
          v-if="videoErrorStates[index]"
          class="absolute inset-0 flex items-center justify-center bg-gray-900 text-white p-4"
        >
          <div class="text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm mb-2">Unable to load video</p>
            <button
              @click="retryVideo(index)"
              class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors"
            >
              Retry
            </button>
          </div>
        </div>

        <!-- Accessibility Controls -->
        <div
          v-if="config.accessibility.keyboardNavigation"
          class="sr-only"
          :aria-live="videoPlayingStates[index] ? 'polite' : 'off'"
        >
          Video {{ index + 1 }} is {{ videoPlayingStates[index] ? 'playing' : 'paused' }}
        </div>
      </div>
    </div>

    <!-- Video Playlist (if multiple videos) -->
    <div
      v-if="optimizedVideos.length > 1 && showPlaylist"
      class="mt-6"
    >
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Video Playlist
      </h3>
      <div class="space-y-2">
        <button
          v-for="(video, index) in optimizedVideos"
          :key="`playlist-${video.id}`"
          @click="scrollToVideo(index)"
          :class="[
            'flex items-center space-x-3 w-full p-3 rounded-lg text-left transition-colors',
            {
              'bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800': currentVideoIndex === index,
              'hover:bg-gray-50 dark:hover:bg-gray-800': currentVideoIndex !== index
            }
          ]"
          :aria-label="`Play video ${index + 1}: ${video.title || video.alt}`"
        >
          <!-- Video Thumbnail -->
          <div class="flex-shrink-0 w-16 h-12 bg-gray-200 dark:bg-gray-700 rounded overflow-hidden">
            <ResponsiveImage
              v-if="video.thumbnail"
              :src="{ ...video, url: video.thumbnail }"
              :alt="`Thumbnail for ${video.title || video.alt}`"
              :lazy-load="true"
              aspect-ratio="4:3"
              object-fit="cover"
              class="w-full h-full"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>

          <!-- Video Info -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ video.title || video.alt || `Video ${index + 1}` }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{ video.description || 'No description' }}
            </p>
            <div class="flex items-center space-x-2 mt-1">
              <span
                v-if="video.videoAsset?.duration"
                class="text-xs text-gray-500 dark:text-gray-400"
              >
                {{ formatDuration(video.videoAsset.duration) }}
              </span>
              <span
                v-if="videoPlayingStates[index]"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
              >
                Playing
              </span>
            </div>
          </div>

          <!-- Play/Pause Icon -->
          <div class="flex-shrink-0">
            <svg
              v-if="!videoPlayingStates[index]"
              class="w-5 h-5 text-gray-400"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M8 5v14l11-7z"/>
            </svg>
            <svg
              v-else
              class="w-5 h-5 text-gray-400"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
            </svg>
          </div>
        </button>
      </div>
    </div>
  </MediaBase>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, reactive } from 'vue'
import { MediaBase } from './index'
import ResponsiveVideo from '@/components/Common/ResponsiveVideo.vue'
import ResponsiveImage from '@/components/Common/ResponsiveImage.vue'
import type { MediaComponentConfig, MediaAsset } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'
import { useIntersectionObserver } from '@/composables/useIntersectionObserver'

interface Props {
  config: MediaComponentConfig
  trackAnalytics?: boolean
  analyticsId?: string
  aspectRatio?: '16:9' | '4:3' | '1:1' | 'auto'
  objectFit?: 'contain' | 'cover' | 'fill'
  showVideoInfo?: boolean
  showPlaylist?: boolean
  showQualitySelector?: boolean
  showTranscript?: boolean
  autoplayFirst?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  trackAnalytics: true,
  aspectRatio: '16:9',
  objectFit: 'cover',
  showVideoInfo: true,
  showPlaylist: true,
  showQualitySelector: true,
  showTranscript: false,
  autoplayFirst: false
})

const emit = defineEmits<{
  videoPlay: [index: number, video: MediaAsset]
  videoPause: [index: number, video: MediaAsset]
  videoEnded: [index: number, video: MediaAsset]
  videoError: [index: number, error: Event]
  videoLoadStart: [index: number]
  videoLoadedData: [index: number]
  videoTimeUpdate: [index: number, currentTime: number, duration: number]
  retry: []
}>()

// Reactive state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const loadingMessage = ref('Loading videos...')
const currentVideoIndex = ref(0)

// Video states
const videoLoadingStates = reactive<Record<number, boolean>>({})
const videoErrorStates = reactive<Record<number, boolean>>({})
const videoPlayingStates = reactive<Record<number, boolean>>({})
const videoCurrentTimes = reactive<Record<number, number>>({})
const videoDurations = reactive<Record<number, number>>({})

// Analytics
const { trackEvent } = useAnalytics()

// Computed properties
const optimizedVideos = computed(() => {
  return props.config.mediaAssets.filter(asset => asset.type === 'video').map(asset => ({
    ...asset,
    // Use CDN URL if available and CDN is enabled
    url: props.config.cdnConfig?.enabled && asset.cdnUrl ? asset.cdnUrl : asset.url,
    // Add mobile-specific optimizations
    mobileUrl: asset.mobileUrl || (props.config.mobileOptimized ? generateMobileVideoVariant(asset) : asset.url),
    // Ensure video-specific properties
    videoAsset: asset.videoAsset || {
      duration: asset.videoAsset?.duration,
      transcript: asset.videoAsset?.transcript,
      captions: asset.videoAsset?.captions,
      chapters: asset.videoAsset?.chapters,
      qualities: asset.videoAsset?.qualities
    }
  }))
})

const videoContainerAriaLabel = computed(() => {
  return props.config.accessibility?.ariaLabel || 
         `Video collection with ${optimizedVideos.value.length} videos`
})

const videoContainerClasses = computed(() => [
  'video-embed-container',
  {
    // Layout classes
    'grid': props.config.layout === 'grid',
    'flex flex-col space-y-6': props.config.layout === 'column',
    'flex flex-row space-x-6': props.config.layout === 'row',
    
    // Grid columns
    'grid-cols-1': props.config.gridColumns?.mobile === 1,
    'grid-cols-2': props.config.gridColumns?.mobile === 2,
    'md:grid-cols-2': props.config.gridColumns?.tablet === 2,
    'md:grid-cols-3': props.config.gridColumns?.tablet === 3,
    'lg:grid-cols-2': props.config.gridColumns?.desktop === 2,
    'lg:grid-cols-3': props.config.gridColumns?.desktop === 3,
    'lg:grid-cols-4': props.config.gridColumns?.desktop === 4,
    
    // Gap
    'gap-4': props.config.gridGap === 'sm',
    'gap-6': props.config.gridGap === 'md',
    'gap-8': props.config.gridGap === 'lg',
  }
])

const videoItemClasses = computed(() => [
  'video-embed-item',
  'relative',
  {
    'rounded-lg overflow-hidden': true,
    'shadow-lg': props.config.theme === 'card',
    'border border-gray-200 dark:border-gray-700': props.config.theme === 'card',
  }
])

const effectivePreload = computed(() => {
  // Adjust preload based on performance settings
  if (!props.config.performance.preloading) {
    return 'none'
  }
  
  if (props.config.performance.bandwidthAdaptive) {
    return 'metadata'
  }
  
  return props.config.videoSettings?.preload || 'metadata'
})

// Methods
const generateMobileVideoVariant = (asset: MediaAsset) => {
  // Generate mobile-optimized video URL
  const quality = props.config.optimization.compressionLevel === 'high' ? 'high' : 
                  props.config.optimization.compressionLevel === 'medium' ? 'medium' : 'low'
  
  return `${asset.url}?mobile=true&quality=${quality}`
}

const shouldAutoplay = (index: number) => {
  // Only autoplay the first video if enabled, and respect user preferences
  return index === 0 && 
         props.autoplayFirst && 
         props.config.videoSettings?.autoplay !== false &&
         !window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

const getCaptionTracks = (video: MediaAsset) => {
  const tracks = []
  
  if (video.videoAsset?.captions) {
    tracks.push({
      kind: 'captions' as const,
      src: video.videoAsset.captions,
      srclang: 'en',
      label: 'English captions',
      default: true
    })
  }
  
  if (video.videoAsset?.transcript) {
    tracks.push({
      kind: 'subtitles' as const,
      src: video.videoAsset.transcript,
      srclang: 'en',
      label: 'English subtitles',
      default: false
    })
  }
  
  return tracks
}

const getVideoQualities = (video: MediaAsset) => {
  if (video.videoAsset?.qualities) {
    return video.videoAsset.qualities
  }
  
  // Generate default qualities
  const qualities = []
  
  qualities.push({
    label: 'Auto',
    src: video.url,
    type: video.mimeType || 'video/mp4',
    bandwidth: video.size ? video.size * 8 : undefined,
    width: video.width,
    height: video.height
  })
  
  if (video.mobileUrl) {
    qualities.push({
      label: 'Mobile',
      src: video.mobileUrl,
      type: video.mimeType || 'video/mp4',
      bandwidth: video.size ? video.size * 4 : undefined,
      width: video.width ? Math.floor(video.width * 0.7) : undefined,
      height: video.height ? Math.floor(video.height * 0.7) : undefined
    })
  }
  
  return qualities
}

const formatDuration = (seconds: number) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainingSeconds = Math.floor(seconds % 60)
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
  }
  
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const handleVideoPlay = (index: number) => {
  videoPlayingStates[index] = true
  
  // Pause other videos if only one should play at a time
  if (props.config.videoSettings?.pauseOthersOnPlay !== false) {
    Object.keys(videoPlayingStates).forEach(key => {
      const idx = parseInt(key)
      if (idx !== index) {
        videoPlayingStates[idx] = false
      }
    })
  }
  
  currentVideoIndex.value = index
  
  if (props.trackAnalytics) {
    trackEvent('video_embed_play', {
      embed_id: props.analyticsId,
      video_index: index,
      video_id: optimizedVideos.value[index].id,
      video_title: optimizedVideos.value[index].title
    })
  }
  
  emit('videoPlay', index, optimizedVideos.value[index])
}

const handleVideoPause = (index: number) => {
  videoPlayingStates[index] = false
  
  if (props.trackAnalytics) {
    trackEvent('video_embed_pause', {
      embed_id: props.analyticsId,
      video_index: index,
      video_id: optimizedVideos.value[index].id,
      current_time: videoCurrentTimes[index] || 0
    })
  }
  
  emit('videoPause', index, optimizedVideos.value[index])
}

const handleVideoEnded = (index: number) => {
  videoPlayingStates[index] = false
  
  if (props.trackAnalytics) {
    trackEvent('video_embed_ended', {
      embed_id: props.analyticsId,
      video_index: index,
      video_id: optimizedVideos.value[index].id,
      duration: videoDurations[index] || 0
    })
  }
  
  emit('videoEnded', index, optimizedVideos.value[index])
}

const handleVideoLoadStart = (index: number) => {
  videoLoadingStates[index] = true
  videoErrorStates[index] = false
  
  emit('videoLoadStart', index)
}

const handleVideoLoadedData = (index: number) => {
  videoLoadingStates[index] = false
  
  if (props.trackAnalytics) {
    trackEvent('video_embed_loaded', {
      embed_id: props.analyticsId,
      video_index: index,
      video_id: optimizedVideos.value[index].id
    })
  }
  
  emit('videoLoadedData', index)
}

const handleVideoError = (index: number, error: Event) => {
  videoLoadingStates[index] = false
  videoErrorStates[index] = true
  
  if (props.trackAnalytics) {
    trackEvent('video_embed_error', {
      embed_id: props.analyticsId,
      video_index: index,
      video_id: optimizedVideos.value[index].id
    })
  }
  
  emit('videoError', index, error)
}

const handleVideoTimeUpdate = (index: number, currentTime: number, duration: number) => {
  videoCurrentTimes[index] = currentTime
  videoDurations[index] = duration
  
  emit('videoTimeUpdate', index, currentTime, duration)
}

const retryVideo = (index: number) => {
  videoErrorStates[index] = false
  videoLoadingStates[index] = true
  
  // Simulate retry delay
  setTimeout(() => {
    videoLoadingStates[index] = false
  }, 1000)
}

const scrollToVideo = (index: number) => {
  currentVideoIndex.value = index
  
  // Scroll to video if not in viewport
  const videoElement = document.querySelector(`.video-embed-item:nth-child(${index + 1})`)
  if (videoElement) {
    videoElement.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
  
  if (props.trackAnalytics) {
    trackEvent('video_embed_playlist_click', {
      embed_id: props.analyticsId,
      video_index: index,
      video_id: optimizedVideos.value[index].id
    })
  }
}

const handleRetry = () => {
  isLoading.value = true
  hasError.value = false
  
  // Clear all error states
  Object.keys(videoErrorStates).forEach(key => {
    videoErrorStates[parseInt(key)] = false
  })
  
  // Simulate retry delay
  setTimeout(() => {
    isLoading.value = false
  }, 1000)
  
  emit('retry')
}

// Initialize video states
onMounted(() => {
  optimizedVideos.value.forEach((_, index) => {
    videoLoadingStates[index] = false
    videoErrorStates[index] = false
    videoPlayingStates[index] = false
    videoCurrentTimes[index] = 0
    videoDurations[index] = 0
  })
})
</script>

<style scoped>
.video-embed-container {
  container-type: inline-size;
}

/* Video item hover effects */
.video-embed-item:hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease;
}

/* Playlist item animations */
.video-embed-item .playlist-item {
  transition: all 0.2s ease;
}

.video-embed-item .playlist-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .video-embed-item {
    border: 1px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .video-embed-item,
  .video-embed-item *,
  .playlist-item {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus management */
.video-embed-item:focus-within {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* Print styles */
@media print {
  .video-embed-container {
    display: block !important;
  }
  
  .video-embed-item {
    break-inside: avoid;
    page-break-inside: avoid;
    margin-bottom: 1rem;
  }
}

/* Mobile-specific styles */
@media (max-width: 768px) {
  .video-embed-container.grid {
    grid-template-columns: 1fr;
  }
  
  .video-embed-container.flex-row {
    flex-direction: column;
    space-x: 0;
  }
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

.video-loading {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>