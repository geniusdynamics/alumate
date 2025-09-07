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
    <!-- Demo Container -->
    <div
      :class="demoContainerClasses"
      role="application"
      :aria-label="demoAriaLabel"
      :tabindex="config.accessibility.keyboardNavigation ? 0 : -1"
      @keydown="handleKeydown"
    >
      <!-- Demo Header -->
      <div
        v-if="showDemoHeader"
        class="demo-header flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
      >
        <div class="flex items-center space-x-3">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ demoTitle }}
          </h3>
          <span
            v-if="demoStatus"
            :class="statusClasses"
          >
            {{ demoStatus }}
          </span>
        </div>

        <!-- Demo Controls -->
        <div class="flex items-center space-x-2">
          <button
            v-if="config.demoSettings?.showControls"
            @click="toggleDemo"
            :class="controlButtonClasses"
            :aria-label="isRunning ? 'Pause demo' : 'Start demo'"
          >
            <svg
              v-if="!isRunning"
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M8 5v14l11-7z"/>
            </svg>
            <svg
              v-else
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
            </svg>
          </button>

          <button
            v-if="config.demoSettings?.showControls"
            @click="resetDemo"
            :class="controlButtonClasses"
            :aria-label="'Reset demo'"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
          </button>

          <button
            v-if="allowFullscreen"
            @click="toggleFullscreen"
            :class="controlButtonClasses"
            :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
          >
            <svg
              v-if="!isFullscreen"
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
            </svg>
            <svg
              v-else
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M15 9h4.5M15 9V4.5M15 9l5.5-5.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 15h4.5M15 15v4.5m0 0l5.5-5.5"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Demo Content Area -->
      <div
        ref="demoContentRef"
        :class="demoContentClasses"
        @click="handleDemoClick"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        @mousedown="handleMouseDown"
        @mousemove="handleMouseMove"
        @mouseup="handleMouseUp"
      >
        <!-- Demo Iframe (for external demos) -->
        <iframe
          v-if="demoType === 'iframe' && demoUrl"
          ref="demoIframeRef"
          :src="demoUrl"
          :class="iframeClasses"
          :title="demoTitle"
          :allow="iframePermissions"
          :sandbox="iframeSandbox"
          @load="handleIframeLoad"
          @error="handleIframeError"
        ></iframe>

        <!-- Demo Video (for video-based demos) -->
        <ResponsiveVideo
          v-else-if="demoType === 'video' && demoVideo"
          :src="demoVideo"
          :autoplay="config.demoSettings?.autoStart && isRunning"
          :muted="true"
          :loop="true"
          :show-controls="false"
          :playsinline="true"
          :lazy-load="config.performance.lazyLoading"
          :aspect-ratio="aspectRatio"
          :object-fit="objectFit"
          :track-analytics="trackAnalytics"
          :analytics-id="`${analyticsId}-demo-video`"
          @play="handleVideoPlay"
          @pause="handleVideoPause"
          @ended="handleVideoEnded"
        />

        <!-- Demo Image Sequence (for step-by-step demos) -->
        <div
          v-else-if="demoType === 'sequence' && demoImages.length > 0"
          class="relative w-full h-full"
        >
          <ResponsiveImage
            :src="currentDemoImage"
            :alt="currentDemoImage.alt || `Demo step ${currentStep + 1}`"
            :lazy-load="false"
            :aspect-ratio="aspectRatio"
            :object-fit="objectFit"
            class="w-full h-full"
          />

          <!-- Step Indicators -->
          <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
            <button
              v-for="(_, index) in demoImages"
              :key="`step-${index}`"
              @click="goToStep(index)"
              :class="[
                'w-3 h-3 rounded-full transition-all',
                {
                  'bg-white': index === currentStep,
                  'bg-white bg-opacity-50 hover:bg-opacity-75': index !== currentStep
                }
              ]"
              :aria-label="`Go to step ${index + 1}`"
            ></button>
          </div>

          <!-- Navigation Arrows -->
          <button
            v-if="demoImages.length > 1"
            @click="previousStep"
            class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-2 rounded-full transition-all"
            :aria-label="'Previous step'"
            :disabled="currentStep === 0"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <button
            v-if="demoImages.length > 1"
            @click="nextStep"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-2 rounded-full transition-all"
            :aria-label="'Next step'"
            :disabled="currentStep === demoImages.length - 1"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <!-- Interactive Hotspots -->
        <div
          v-if="config.demoSettings?.enableInteraction && hotspots.length > 0"
          class="absolute inset-0 pointer-events-none"
        >
          <button
            v-for="(hotspot, index) in hotspots"
            :key="`hotspot-${index}`"
            @click="handleHotspotClick(hotspot, index)"
            :class="hotspotClasses(hotspot)"
            :style="hotspotStyles(hotspot)"
            :aria-label="hotspot.label || `Interactive element ${index + 1}`"
            class="pointer-events-auto"
          >
            <div class="w-4 h-4 bg-indigo-600 rounded-full animate-ping"></div>
            <div class="absolute inset-0 w-4 h-4 bg-indigo-600 rounded-full"></div>
          </button>
        </div>

        <!-- Demo Overlay Information -->
        <div
          v-if="showOverlayInfo && overlayInfo"
          class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-6"
        >
          <div class="text-white">
            <h4 class="text-lg font-semibold mb-2">{{ overlayInfo.title }}</h4>
            <p class="text-sm text-gray-200 mb-3">{{ overlayInfo.description }}</p>
            <div class="flex items-center space-x-4 text-xs text-gray-300">
              <span v-if="overlayInfo.duration">Duration: {{ overlayInfo.duration }}</span>
              <span v-if="overlayInfo.steps">Steps: {{ overlayInfo.steps }}</span>
              <span v-if="overlayInfo.difficulty">Difficulty: {{ overlayInfo.difficulty }}</span>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div
          v-if="isDemoLoading"
          class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 dark:bg-gray-900 dark:bg-opacity-90"
        >
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Loading interactive demo...</p>
          </div>
        </div>

        <!-- Error State -->
        <div
          v-if="isDemoError"
          class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-800 p-4"
        >
          <div class="text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Unable to load interactive demo</p>
            <button
              @click="retryDemo"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
            >
              Try Again
            </button>
          </div>
        </div>
      </div>

      <!-- Demo Instructions -->
      <div
        v-if="showInstructions && instructions"
        class="demo-instructions p-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700"
      >
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
          How to interact:
        </h4>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
          <li v-for="instruction in instructions" :key="instruction">
            • {{ instruction }}
          </li>
        </ul>
      </div>
    </div>
  </MediaBase>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { MediaBase } from './index'
import ResponsiveVideo from '@/components/Common/ResponsiveVideo.vue'
import ResponsiveImage from '@/components/Common/ResponsiveImage.vue'
import type { MediaComponentConfig, MediaAsset } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'

interface DemoHotspot {
  x: number // Percentage from left
  y: number // Percentage from top
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
  aspectRatio?: '16:9' | '4:3' | '1:1' | 'auto'
  objectFit?: 'contain' | 'cover' | 'fill'
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
  aspectRatio: '16:9',
  objectFit: 'contain',
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
  demoStart: []
  demoPause: []
  demoReset: []
  demoComplete: []
  hotspotClick: [hotspot: DemoHotspot, index: number]
  stepChange: [step: number]
  fullscreenToggle: [isFullscreen: boolean]
  retry: []
}>()

// Reactive state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const loadingMessage = ref('Loading interactive demo...')

// Demo state
const isRunning = ref(false)
const isDemoLoading = ref(false)
const isDemoError = ref(false)
const isFullscreen = ref(false)
const currentStep = ref(0)
const demoProgress = ref(0)

// Interaction state
const isInteracting = ref(false)
const lastInteractionTime = ref(0)
const interactionCount = ref(0)

// Touch/Mouse state
const touchStartX = ref(0)
const touchStartY = ref(0)
const mouseDown = ref(false)

// Refs
const demoContentRef = ref<HTMLElement>()
const demoIframeRef = ref<HTMLIFrameElement>()

// Analytics
const { trackEvent } = useAnalytics()

// Computed properties
const demoTitle = computed(() => {
  return props.config.title || 'Interactive Demo'
})

const demoStatus = computed(() => {
  if (isDemoLoading.value) return 'Loading...'
  if (isDemoError.value) return 'Error'
  if (isRunning.value) return 'Running'
  return 'Ready'
})

const demoAriaLabel = computed(() => {
  return props.config.accessibility?.ariaLabel || 
         `Interactive demo: ${demoTitle.value}`
})

const currentDemoImage = computed(() => {
  return props.demoImages[currentStep.value] || props.demoImages[0]
})

const iframePermissions = computed(() => {
  const permissions = ['autoplay', 'encrypted-media', 'picture-in-picture']
  
  if (props.config.demoSettings?.mobileCompatible) {
    permissions.push('accelerometer', 'gyroscope')
  }
  
  if (props.config.demoSettings?.touchSupport) {
    permissions.push('touch')
  }
  
  return permissions.join('; ')
})

const iframeSandbox = computed(() => {
  const sandbox = ['allow-scripts', 'allow-same-origin']
  
  if (props.config.demoSettings?.enableInteraction) {
    sandbox.push('allow-forms', 'allow-pointer-lock')
  }
  
  return sandbox.join(' ')
})

const demoContainerClasses = computed(() => [
  'interactive-demo-container',
  'relative overflow-hidden bg-white dark:bg-gray-900',
  {
    'rounded-lg': props.config.theme !== 'full-width',
    'shadow-lg': props.config.theme === 'card',
    'border border-gray-200 dark:border-gray-700': props.config.theme === 'card',
    'h-96': props.aspectRatio === '16:9',
    'h-80': props.aspectRatio === '4:3',
    'aspect-square': props.aspectRatio === '1:1',
    'h-auto': props.aspectRatio === 'auto',
  }
])

const demoContentClasses = computed(() => [
  'demo-content',
  'relative w-full h-full',
  {
    'cursor-pointer': props.config.demoSettings?.enableInteraction,
    'select-none': props.config.demoSettings?.enableInteraction,
  }
])

const iframeClasses = computed(() => [
  'w-full h-full border-0',
  {
    'pointer-events-none': !props.config.demoSettings?.enableInteraction,
    'pointer-events-auto': props.config.demoSettings?.enableInteraction,
  }
])

const controlButtonClasses = computed(() => [
  'inline-flex items-center justify-center w-8 h-8 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors',
  'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
])

const statusClasses = computed(() => [
  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
  {
    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': isRunning.value && !isDemoError.value,
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': isDemoLoading.value,
    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': isDemoError.value,
    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': !isRunning.value && !isDemoLoading.value && !isDemoError.value,
  }
])

// Methods
const toggleDemo = () => {
  if (isRunning.value) {
    pauseDemo()
  } else {
    startDemo()
  }
}

const startDemo = () => {
  isRunning.value = true
  
  if (props.trackAnalytics) {
    trackEvent('interactive_demo_start', {
      demo_id: props.analyticsId,
      demo_type: props.demoType,
      auto_start: props.config.demoSettings?.autoStart
    })
  }
  
  emit('demoStart')
}

const pauseDemo = () => {
  isRunning.value = false
  
  if (props.trackAnalytics) {
    trackEvent('interactive_demo_pause', {
      demo_id: props.analyticsId,
      demo_type: props.demoType,
      progress: demoProgress.value
    })
  }
  
  emit('demoPause')
}

const resetDemo = () => {
  isRunning.value = false
  currentStep.value = 0
  demoProgress.value = 0
  interactionCount.value = 0
  
  if (props.trackAnalytics) {
    trackEvent('interactive_demo_reset', {
      demo_id: props.analyticsId,
      demo_type: props.demoType
    })
  }
  
  emit('demoReset')
}

const toggleFullscreen = async () => {
  if (!props.allowFullscreen || !demoContentRef.value) return
  
  try {
    if (!isFullscreen.value) {
      await demoContentRef.value.requestFullscreen()
      isFullscreen.value = true
    } else {
      await document.exitFullscreen()
      isFullscreen.value = false
    }
    
    if (props.trackAnalytics) {
      trackEvent('interactive_demo_fullscreen', {
        demo_id: props.analyticsId,
        fullscreen: isFullscreen.value
      })
    }
    
    emit('fullscreenToggle', isFullscreen.value)
  } catch (error) {
    console.warn('Fullscreen not supported or failed:', error)
  }
}

const nextStep = () => {
  if (currentStep.value < props.demoImages.length - 1) {
    currentStep.value++
    updateProgress()
    
    if (props.trackAnalytics) {
      trackEvent('interactive_demo_next_step', {
        demo_id: props.analyticsId,
        step: currentStep.value,
        total_steps: props.demoImages.length
      })
    }
    
    emit('stepChange', currentStep.value)
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
    updateProgress()
    
    if (props.trackAnalytics) {
      trackEvent('interactive_demo_previous_step', {
        demo_id: props.analyticsId,
        step: currentStep.value,
        total_steps: props.demoImages.length
      })
    }
    
    emit('stepChange', currentStep.value)
  }
}

const goToStep = (step: number) => {
  if (step >= 0 && step < props.demoImages.length) {
    currentStep.value = step
    updateProgress()
    
    if (props.trackAnalytics) {
      trackEvent('interactive_demo_go_to_step', {
        demo_id: props.analyticsId,
        step: step,
        total_steps: props.demoImages.length
      })
    }
    
    emit('stepChange', step)
  }
}

const updateProgress = () => {
  if (props.demoImages.length > 0) {
    demoProgress.value = ((currentStep.value + 1) / props.demoImages.length) * 100
  }
}

const handleHotspotClick = (hotspot: DemoHotspot, index: number) => {
  interactionCount.value++
  lastInteractionTime.value = Date.now()
  
  if (props.trackAnalytics) {
    trackEvent('interactive_demo_hotspot_click', {
      demo_id: props.analyticsId,
      hotspot_index: index,
      hotspot_label: hotspot.label,
      hotspot_action: hotspot.action,
      interaction_count: interactionCount.value
    })
  }
  
  emit('hotspotClick', hotspot, index)
}

const hotspotClasses = (hotspot: DemoHotspot) => [
  'absolute flex items-center justify-center w-8 h-8 -ml-4 -mt-4',
  'hover:scale-110 transition-transform',
  'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
]

const hotspotStyles = (hotspot: DemoHotspot) => ({
  left: `${hotspot.x}%`,
  top: `${hotspot.y}%`
})

const handleDemoClick = (event: MouseEvent) => {
  if (!props.config.demoSettings?.enableInteraction) return
  
  const rect = demoContentRef.value?.getBoundingClientRect()
  if (!rect) return
  
  const x = ((event.clientX - rect.left) / rect.width) * 100
  const y = ((event.clientY - rect.top) / rect.height) * 100
  
  interactionCount.value++
  lastInteractionTime.value = Date.now()
  
  if (props.trackAnalytics) {
    trackEvent('interactive_demo_click', {
      demo_id: props.analyticsId,
      click_x: x,
      click_y: y,
      interaction_count: interactionCount.value
    })
  }
}

const handleTouchStart = (event: TouchEvent) => {
  if (!props.config.demoSettings?.touchSupport) return
  
  touchStartX.value = event.touches[0].clientX
  touchStartY.value = event.touches[0].clientY
  isInteracting.value = true
}

const handleTouchMove = (event: TouchEvent) => {
  if (!props.config.demoSettings?.touchSupport || !isInteracting.value) return
  
  // Handle touch gestures for demo navigation
  const deltaX = event.touches[0].clientX - touchStartX.value
  const deltaY = event.touches[0].clientY - touchStartY.value
  
  // Prevent default scrolling if horizontal swipe
  if (Math.abs(deltaX) > Math.abs(deltaY)) {
    event.preventDefault()
  }
}

const handleTouchEnd = (event: TouchEvent) => {
  if (!props.config.demoSettings?.touchSupport || !isInteracting.value) return
  
  const deltaX = event.changedTouches[0].clientX - touchStartX.value
  const threshold = 50
  
  if (Math.abs(deltaX) > threshold) {
    if (deltaX > 0) {
      previousStep()
    } else {
      nextStep()
    }
  }
  
  isInteracting.value = false
}

const handleMouseDown = (event: MouseEvent) => {
  if (!props.config.demoSettings?.enableInteraction) return
  mouseDown.value = true
}

const handleMouseMove = (event: MouseEvent) => {
  if (!props.config.demoSettings?.enableInteraction || !mouseDown.value) return
  // Handle mouse drag interactions
}

const handleMouseUp = (event: MouseEvent) => {
  if (!props.config.demoSettings?.enableInteraction) return
  mouseDown.value = false
}

const handleKeydown = (event: KeyboardEvent) => {
  if (!props.config.accessibility?.keyboardNavigation) return
  
  switch (event.key) {
    case 'ArrowLeft':
      event.preventDefault()
      previousStep()
      break
    case 'ArrowRight':
      event.preventDefault()
      nextStep()
      break
    case ' ':
      event.preventDefault()
      toggleDemo()
      break
    case 'r':
      event.preventDefault()
      resetDemo()
      break
    case 'f':
      if (props.allowFullscreen) {
        event.preventDefault()
        toggleFullscreen()
      }
      break
  }
}

const handleIframeLoad = () => {
  isDemoLoading.value = false
  isDemoError.value = false
  
  if (props.trackAnalytics) {
    trackEvent('interactive_demo_iframe_loaded', {
      demo_id: props.analyticsId,
      demo_url: props.demoUrl
    })
  }
}

const handleIframeError = () => {
  isDemoLoading.value = false
  isDemoError.value = true
  
  if (props.trackAnalytics) {
    trackEvent('interactive_demo_iframe_error', {
      demo_id: props.analyticsId,
      demo_url: props.demoUrl
    })
  }
}

const handleVideoPlay = () => {
  isRunning.value = true
}

const handleVideoPause = () => {
  isRunning.value = false
}

const handleVideoEnded = () => {
  isRunning.value = false
  emit('demoComplete')
}

const retryDemo = () => {
  isDemoError.value = false
  isDemoLoading.value = true
  
  // Simulate retry delay
  setTimeout(() => {
    isDemoLoading.value = false
  }, 1000)
  
  emit('retry')
}

const handleRetry = () => {
  isLoading.value = true
  hasError.value = false
  
  // Simulate retry delay
  setTimeout(() => {
    isLoading.value = false
  }, 1000)
  
  emit('retry')
}

// Auto-start demo if configured
watch(() => props.config.demoSettings?.autoStart, (autoStart) => {
  if (autoStart && !isRunning.value) {
    startDemo()
  }
})

// Handle fullscreen changes
const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

// Lifecycle
onMounted(() => {
  // Initialize demo
  if (props.config.demoSettings?.autoStart) {
    startDemo()
  }
  
  // Add fullscreen event listener
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  
  // Initialize progress
  updateProgress()
})

onUnmounted(() => {
  // Clean up event listeners
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
  
  // Exit fullscreen if active
  if (isFullscreen.value) {
    document.exitFullscreen().catch(() => {})
  }
})
</script>

<style scoped>
.interactive-demo-container {
  container-type: inline-size;
}

/* Demo animations */
.demo-content {
  transition: transform 0.3s ease;
}

.demo-content:hover {
  transform: scale(1.01);
}

/* Hotspot animations */
@keyframes ping {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}

.animate-ping {
  animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* Control button hover effects */
.control-button:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .interactive-demo-container {
    border: 2px solid currentColor;
  }
  
  .demo-content {
    border: 1px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .interactive-demo-container *,
  .interactive-demo-container *::before,
  .interactive-demo-container *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  
  .animate-ping {
    animation: none;
  }
}

/* Focus management */
.interactive-demo-container:focus-within {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* Fullscreen styles */
.interactive-demo-container:fullscreen {
  width: 100vw;
  height: 100vh;
  max-width: none;
  max-height: none;
  border-radius: 0;
}

/* Print styles */
@media print {
  .interactive-demo-container {
    break-inside: avoid;
    page-break-inside: avoid;
  }
  
  .demo-content iframe {
    display: none;
  }
  
  .demo-content::after {
    content: "Interactive demo available online";
    display: block;
    text-align: center;
    padding: 2rem;
    background: #f3f4f6;
    color: #6b7280;
  }
}

/* Mobile-specific styles */
@media (max-width: 768px) {
  .interactive-demo-container {
    height: auto;
    min-height: 300px;
  }
  
  .demo-header {
    padding: 0.75rem;
  }
  
  .demo-instructions {
    padding: 0.75rem;
  }
}

/* Touch-friendly hotspots on mobile */
@media (max-width: 768px) {
  .hotspot-button {
    width: 44px;
    height: 44px;
    margin-left: -22px;
    margin-top: -22px;
  }
}
</style>