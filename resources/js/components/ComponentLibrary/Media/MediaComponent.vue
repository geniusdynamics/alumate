<template>
  <div
    :class="containerClasses"
    role="region"
    :aria-label="containerAriaLabel"
  >
    <!-- Image Gallery -->
    <ImageGallery
      v-if="config.type === 'image-gallery'"
      :config="config"
      :track-analytics="trackAnalytics"
      :analytics-id="analyticsId"
      :aspect-ratio="aspectRatio"
      :object-fit="objectFit"
      :show-captions="showCaptions"
      :image-rounded="imageRounded"
      @image-load="handleImageLoad"
      @image-error="handleImageError"
      @image-click="handleImageClick"
      @lightbox-open="handleLightboxOpen"
      @lightbox-close="handleLightboxClose"
      @retry="handleRetry"
    />

    <!-- Video Embed -->
    <VideoEmbed
      v-else-if="config.type === 'video-embed'"
      :config="config"
      :track-analytics="trackAnalytics"
      :analytics-id="analyticsId"
      :aspect-ratio="aspectRatio"
      :object-fit="objectFit"
      :show-video-info="showVideoInfo"
      :show-playlist="showPlaylist"
      :show-quality-selector="showQualitySelector"
      :show-transcript="showTranscript"
      :autoplay-first="autoplayFirst"
      @video-play="handleVideoPlay"
      @video-pause="handleVideoPause"
      @video-ended="handleVideoEnded"
      @video-error="handleVideoError"
      @video-load-start="handleVideoLoadStart"
      @video-loaded-data="handleVideoLoadedData"
      @video-time-update="handleVideoTimeUpdate"
      @retry="handleRetry"
    />

    <!-- Interactive Demo -->
    <InteractiveDemo
      v-else-if="config.type === 'interactive-demo'"
      :config="config"
      :track-analytics="trackAnalytics"
      :analytics-id="analyticsId"
      :aspect-ratio="aspectRatio"
      :object-fit="objectFit"
      :demo-type="demoType"
      :demo-url="demoUrl"
      :demo-video="demoVideo"
      :demo-images="demoImages"
      :hotspots="hotspots"
      :overlay-info="overlayInfo"
      :instructions="instructions"
      :show-demo-header="showDemoHeader"
      :show-overlay-info="showOverlayInfo"
      :show-instructions="showInstructions"
      :allow-fullscreen="allowFullscreen"
      @demo-start="handleDemoStart"
      @demo-pause="handleDemoPause"
      @demo-reset="handleDemoReset"
      @demo-complete="handleDemoComplete"
      @hotspot-click="handleHotspotClick"
      @step-change="handleStepChange"
      @fullscreen-toggle="handleFullscreenToggle"
      @retry="handleRetry"
    />

    <!-- Fallback for unknown type -->
    <div
      v-else
      class="flex items-center justify-center h-64 bg-gray-100 dark:bg-gray-800 rounded-lg"
    >
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Unsupported media type: {{ config.type }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ImageGallery from './ImageGallery.vue'
import VideoEmbed from './VideoEmbed.vue'
import InteractiveDemo from './InteractiveDemo.vue'
import type { MediaComponentConfig, MediaAsset } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'

interface DemoHotspot {
  x: number
  y: number
  label?: string
  action?: string
  data?: any
}

interface DemoOverlayInfo {
  title: string
  description: string
  duration?: string
  steps?: number
  difficulty?: 'Easy' | 'Medium' | 'Hard'
}

interface Props {
  config: MediaComponentConfig
  trackAnalytics?: boolean
  analyticsId?: string
  
  // Common display options
  aspectRatio?: '16:9' | '4:3' | '1:1' | '3:2' | 'auto'
  objectFit?: 'contain' | 'cover' | 'fill' | 'scale-down'
  
  // Image Gallery specific
  showCaptions?: boolean
  imageRounded?: boolean | 'sm' | 'md' | 'lg' | 'xl' | 'full'
  
  // Video Embed specific
  showVideoInfo?: boolean
  showPlaylist?: boolean
  showQualitySelector?: boolean
  showTranscript?: boolean
  autoplayFirst?: boolean
  
  // Interactive Demo specific
  demoType?: 'iframe' | 'video' | 'sequence'
  demoUrl?: string
  demoVideo?: MediaAsset
  demoImages?: MediaAsset[]
  hotspots?: DemoHotspot[]
  overlayInfo?: DemoOverlayInfo
  instructions?: string[]
  showDemoHeader?: boolean
  showOverlayInfo?: boolean
  showInstructions?: boolean
  allowFullscreen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  trackAnalytics: true,
  aspectRatio: 'auto',
  objectFit: 'cover',
  
  // Image Gallery defaults
  showCaptions: false,
  imageRounded: 'md',
  
  // Video Embed defaults
  showVideoInfo: true,
  showPlaylist: true,
  showQualitySelector: true,
  showTranscript: false,
  autoplayFirst: false,
  
  // Interactive Demo defaults
  demoType: 'iframe',
  hotspots: () => [],
  instructions: () => [],
  showDemoHeader: true,
  showOverlayInfo: true,
  showInstructions: true,
  allowFullscreen: true,
  demoImages: () => []
})

const emit = defineEmits<{
  // Image Gallery events
  imageLoad: [index: number, image: MediaAsset]
  imageError: [index: number, error: Event]
  imageClick: [index: number, image: MediaAsset]
  lightboxOpen: [index: number]
  lightboxClose: []
  
  // Video Embed events
  videoPlay: [index: number, video: MediaAsset]
  videoPause: [index: number, video: MediaAsset]
  videoEnded: [index: number, video: MediaAsset]
  videoError: [index: number, error: Event]
  videoLoadStart: [index: number]
  videoLoadedData: [index: number]
  videoTimeUpdate: [index: number, currentTime: number, duration: number]
  
  // Interactive Demo events
  demoStart: []
  demoPause: []
  demoReset: []
  demoComplete: []
  hotspotClick: [hotspot: DemoHotspot, index: number]
  stepChange: [step: number]
  fullscreenToggle: [isFullscreen: boolean]
  
  // Common events
  retry: []
  error: [error: Error]
  loaded: []
}>()

// Analytics
const { trackEvent } = useAnalytics()

// Computed properties
const containerAriaLabel = computed(() => {
  const typeLabels = {
    'image-gallery': 'Image gallery',
    'video-embed': 'Video collection',
    'interactive-demo': 'Interactive demonstration'
  }
  
  return props.config.accessibility?.ariaLabel || 
         typeLabels[props.config.type] || 
         'Media component'
})

const containerClasses = computed(() => [
  'media-component',
  'w-full',
  {
    // Theme-based styling
    'bg-transparent': props.config.theme === 'default',
    'bg-gray-50 dark:bg-gray-900 rounded-lg': props.config.theme === 'minimal',
    'bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 rounded-lg': props.config.theme === 'modern',
    'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm': props.config.theme === 'card',
    
    // Spacing
    'p-0': props.config.spacing === 'compact' || props.config.theme === 'default',
    'p-4': props.config.spacing === 'default' && props.config.theme !== 'default',
    'p-6': props.config.spacing === 'spacious' && props.config.theme !== 'default',
  }
])

// Event handlers
const handleImageLoad = (index: number, image: MediaAsset) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_image_load', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      image_index: index,
      image_id: image.id
    })
  }
  
  emit('imageLoad', index, image)
  emit('loaded')
}

const handleImageError = (index: number, error: Event) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_image_error', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      image_index: index
    })
  }
  
  emit('imageError', index, error)
  emit('error', new Error(`Failed to load image at index ${index}`))
}

const handleImageClick = (index: number, image: MediaAsset) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_image_click', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      image_index: index,
      image_id: image.id
    })
  }
  
  emit('imageClick', index, image)
}

const handleLightboxOpen = (index: number) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_lightbox_open', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      image_index: index
    })
  }
  
  emit('lightboxOpen', index)
}

const handleLightboxClose = () => {
  if (props.trackAnalytics) {
    trackEvent('media_component_lightbox_close', {
      component_id: props.analyticsId,
      media_type: props.config.type
    })
  }
  
  emit('lightboxClose')
}

const handleVideoPlay = (index: number, video: MediaAsset) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_video_play', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      video_index: index,
      video_id: video.id
    })
  }
  
  emit('videoPlay', index, video)
}

const handleVideoPause = (index: number, video: MediaAsset) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_video_pause', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      video_index: index,
      video_id: video.id
    })
  }
  
  emit('videoPause', index, video)
}

const handleVideoEnded = (index: number, video: MediaAsset) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_video_ended', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      video_index: index,
      video_id: video.id
    })
  }
  
  emit('videoEnded', index, video)
}

const handleVideoError = (index: number, error: Event) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_video_error', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      video_index: index
    })
  }
  
  emit('videoError', index, error)
  emit('error', new Error(`Failed to load video at index ${index}`))
}

const handleVideoLoadStart = (index: number) => {
  emit('videoLoadStart', index)
}

const handleVideoLoadedData = (index: number) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_video_loaded', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      video_index: index
    })
  }
  
  emit('videoLoadedData', index)
  emit('loaded')
}

const handleVideoTimeUpdate = (index: number, currentTime: number, duration: number) => {
  emit('videoTimeUpdate', index, currentTime, duration)
}

const handleDemoStart = () => {
  if (props.trackAnalytics) {
    trackEvent('media_component_demo_start', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      demo_type: props.demoType
    })
  }
  
  emit('demoStart')
}

const handleDemoPause = () => {
  if (props.trackAnalytics) {
    trackEvent('media_component_demo_pause', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      demo_type: props.demoType
    })
  }
  
  emit('demoPause')
}

const handleDemoReset = () => {
  if (props.trackAnalytics) {
    trackEvent('media_component_demo_reset', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      demo_type: props.demoType
    })
  }
  
  emit('demoReset')
}

const handleDemoComplete = () => {
  if (props.trackAnalytics) {
    trackEvent('media_component_demo_complete', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      demo_type: props.demoType
    })
  }
  
  emit('demoComplete')
}

const handleHotspotClick = (hotspot: DemoHotspot, index: number) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_hotspot_click', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      hotspot_index: index,
      hotspot_label: hotspot.label
    })
  }
  
  emit('hotspotClick', hotspot, index)
}

const handleStepChange = (step: number) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_step_change', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      step: step
    })
  }
  
  emit('stepChange', step)
}

const handleFullscreenToggle = (isFullscreen: boolean) => {
  if (props.trackAnalytics) {
    trackEvent('media_component_fullscreen_toggle', {
      component_id: props.analyticsId,
      media_type: props.config.type,
      fullscreen: isFullscreen
    })
  }
  
  emit('fullscreenToggle', isFullscreen)
}

const handleRetry = () => {
  if (props.trackAnalytics) {
    trackEvent('media_component_retry', {
      component_id: props.analyticsId,
      media_type: props.config.type
    })
  }
  
  emit('retry')
}
</script>

<style scoped>
.media-component {
  container-type: inline-size;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .media-component {
    border: 1px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .media-component *,
  .media-component *::before,
  .media-component *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus management */
.media-component:focus-within {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* Print styles */
@media print {
  .media-component {
    break-inside: avoid;
    page-break-inside: avoid;
  }
}
</style>