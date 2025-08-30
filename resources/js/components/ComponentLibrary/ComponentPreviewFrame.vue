<template>
  <div 
    class="component-preview-frame"
    :class="frameClasses"
    :style="frameStyles"
  >
    <!-- Sandboxed Preview Container -->
    <div 
      ref="previewContainer"
      class="preview-container w-full h-full overflow-auto"
      :class="containerClasses"
    >
      <!-- Component Rendering Area -->
      <div class="component-render-area">
        <!-- Hero Component Preview -->
        <HeroBase
          v-if="component.category === 'hero'"
          :config="mergedConfig"
          :sample-data="sampleData"
          :preview-mode="true"
          @error="handleRenderError"
        />
        
        <!-- Form Component Preview -->
        <FormBase
          v-else-if="component.category === 'forms'"
          :config="mergedConfig"
          :sample-data="sampleData"
          :preview-mode="true"
          @error="handleRenderError"
        />
        
        <!-- Testimonial Component Preview -->
        <TestimonialBase
          v-else-if="component.category === 'testimonials'"
          :config="mergedConfig"
          :sample-data="sampleData"
          :preview-mode="true"
          @error="handleRenderError"
        />
        
        <!-- Statistics Component Preview -->
        <StatisticsBase
          v-else-if="component.category === 'statistics'"
          :config="mergedConfig"
          :sample-data="sampleData"
          :preview-mode="true"
          @error="handleRenderError"
        />
        
        <!-- CTA Component Preview -->
        <CTABase
          v-else-if="component.category === 'ctas'"
          :config="mergedConfig"
          :sample-data="sampleData"
          :preview-mode="true"
          @error="handleRenderError"
        />
        
        <!-- Media Component Preview -->
        <MediaBase
          v-else-if="component.category === 'media'"
          :config="mergedConfig"
          :sample-data="sampleData"
          :preview-mode="true"
          @error="handleRenderError"
        />
        
        <!-- Fallback for unknown component types -->
        <div 
          v-else
          class="flex items-center justify-center h-64 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded-lg"
        >
          <div class="text-center">
            <Icon name="exclamation-triangle" class="h-12 w-12 mx-auto mb-4" />
            <p class="text-lg font-medium mb-2">Preview Not Available</p>
            <p class="text-sm">This component type is not supported in preview mode</p>
          </div>
        </div>
      </div>
      
      <!-- Grid Overlay -->
      <div 
        v-if="showGrid"
        class="grid-overlay absolute inset-0 pointer-events-none z-10"
        aria-hidden="true"
      ></div>
      
      <!-- Rulers -->
      <div 
        v-if="showRulers"
        class="rulers absolute inset-0 pointer-events-none z-10"
        aria-hidden="true"
      >
        <!-- Horizontal Ruler -->
        <div class="horizontal-ruler absolute top-0 left-0 right-0 h-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600">
          <div 
            v-for="i in horizontalMarks"
            :key="`h-${i}`"
            class="absolute top-0 h-full border-l border-gray-400 dark:border-gray-500"
            :style="{ left: `${i * 10}px` }"
          >
            <span 
              v-if="i % 10 === 0"
              class="absolute top-0 left-1 text-xs text-gray-600 dark:text-gray-400"
            >
              {{ i * 10 }}
            </span>
          </div>
        </div>
        
        <!-- Vertical Ruler -->
        <div class="vertical-ruler absolute top-0 left-0 bottom-0 w-4 bg-gray-100 dark:bg-gray-700 border-r border-gray-300 dark:border-gray-600">
          <div 
            v-for="i in verticalMarks"
            :key="`v-${i}`"
            class="absolute left-0 w-full border-t border-gray-400 dark:border-gray-500"
            :style="{ top: `${i * 10}px` }"
          >
            <span 
              v-if="i % 10 === 0"
              class="absolute left-0 top-1 text-xs text-gray-600 dark:text-gray-400 transform -rotate-90 origin-left"
            >
              {{ i * 10 }}
            </span>
          </div>
        </div>
      </div>
      
      <!-- Loading Overlay -->
      <div 
        v-if="isLoading"
        class="loading-overlay absolute inset-0 bg-white bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-20"
      >
        <div class="text-center">
          <Icon name="arrow-path" class="h-8 w-8 animate-spin text-indigo-600 mx-auto mb-2" />
          <p class="text-sm text-gray-600 dark:text-gray-400">Loading preview...</p>
        </div>
      </div>
      
      <!-- Error Overlay -->
      <div 
        v-if="renderError"
        class="error-overlay absolute inset-0 bg-red-50 dark:bg-red-900 bg-opacity-75 flex items-center justify-center z-20"
      >
        <div class="text-center max-w-md mx-auto p-6">
          <Icon name="exclamation-triangle" class="h-12 w-12 text-red-600 dark:text-red-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-red-900 dark:text-red-100 mb-2">
            Preview Error
          </h3>
          <p class="text-sm text-red-700 dark:text-red-300 mb-4">
            {{ renderError }}
          </p>
          <button
            @click="retryRender"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            <Icon name="arrow-path" class="h-4 w-4 mr-2" />
            Retry
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type { Component, AudienceType } from '@/types/components'
import { generateSampleData } from '@/utils/sampleDataGenerator'

// Import component bases for preview
import Icon from '@/components/Common/Icon.vue'
import HeroBase from './Hero/HeroBase.vue'
import FormBase from './Forms/FormBase.vue'
import TestimonialBase from './Testimonials/TestimonialBase.vue'
import StatisticsBase from './Statistics/StatisticsBase.vue'
import CTABase from './CTAs/CTABase.vue'
import MediaBase from './Media/MediaBase.vue'

interface SampleDataConfig {
  audienceType: AudienceType
  variation: 'default' | 'minimal' | 'rich' | 'localized'
  contentLength: 'short' | 'medium' | 'long'
}

interface Props {
  component: Component
  config: Record<string, any>
  sampleData?: SampleDataConfig
  device: 'desktop' | 'tablet' | 'mobile'
  zoom: number
  showGrid?: boolean
  showRulers?: boolean
}

interface Emits {
  (e: 'config-updated', config: Record<string, any>): void
  (e: 'error', error: string): void
  (e: 'loaded'): void
}

const props = withDefaults(defineProps<Props>(), {
  sampleData: () => ({
    audienceType: 'individual',
    variation: 'default',
    contentLength: 'medium'
  }),
  showGrid: false,
  showRulers: false
})

const emit = defineEmits<Emits>()

// Reactive state
const isLoading = ref(true)
const renderError = ref<string | null>(null)
const previewContainer = ref<HTMLElement | null>(null)

// Computed properties
const frameClasses = computed(() => [
  'relative w-full h-full',
  {
    'device-desktop': props.device === 'desktop',
    'device-tablet': props.device === 'tablet',
    'device-mobile': props.device === 'mobile'
  }
])

const frameStyles = computed(() => ({
  transform: `scale(${props.zoom})`,
  transformOrigin: 'top left'
}))

const containerClasses = computed(() => [
  'relative',
  {
    'bg-white dark:bg-gray-900': props.device === 'desktop',
    'bg-gray-50 dark:bg-gray-800': props.device !== 'desktop'
  }
])

const mergedConfig = computed(() => {
  // Merge component config with preview config and sample data
  const baseConfig = { ...props.component.config, ...props.config }
  
  // Apply device-specific overrides
  if (props.device === 'mobile') {
    baseConfig.mobileLayout = baseConfig.mobileLayout || 'stacked'
    baseConfig.mobileOptimized = true
  }
  
  return baseConfig
})

const sampleData = computed(() => {
  if (!props.sampleData) return null
  
  try {
    return generateSampleData(
      props.component.category,
      props.sampleData.audienceType,
      props.sampleData.variation,
      props.sampleData.contentLength
    )
  } catch (error) {
    console.error('Failed to generate sample data:', error)
    return null
  }
})

// Grid and ruler calculations
const horizontalMarks = computed(() => {
  if (!previewContainer.value) return []
  const width = previewContainer.value.clientWidth
  return Array.from({ length: Math.ceil(width / 10) }, (_, i) => i)
})

const verticalMarks = computed(() => {
  if (!previewContainer.value) return []
  const height = previewContainer.value.clientHeight
  return Array.from({ length: Math.ceil(height / 10) }, (_, i) => i)
})

// Methods
const handleRenderError = (error: string | Error) => {
  const errorMessage = typeof error === 'string' ? error : error.message
  renderError.value = errorMessage
  isLoading.value = false
  emit('error', errorMessage)
}

const retryRender = async () => {
  renderError.value = null
  isLoading.value = true
  
  try {
    await nextTick()
    // Simulate retry delay
    await new Promise(resolve => setTimeout(resolve, 500))
    
    isLoading.value = false
    emit('loaded')
  } catch (error) {
    handleRenderError(error as Error)
  }
}

const handleComponentLoad = () => {
  isLoading.value = false
  renderError.value = null
  emit('loaded')
}

// Intersection Observer for performance optimization
let intersectionObserver: IntersectionObserver | null = null

const setupIntersectionObserver = () => {
  if (!previewContainer.value) return
  
  intersectionObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          // Component is visible, ensure it's loaded
          if (isLoading.value) {
            handleComponentLoad()
          }
        }
      })
    },
    {
      threshold: 0.1,
      rootMargin: '50px'
    }
  )
  
  intersectionObserver.observe(previewContainer.value)
}

const cleanupIntersectionObserver = () => {
  if (intersectionObserver) {
    intersectionObserver.disconnect()
    intersectionObserver = null
  }
}

// Resize Observer for responsive updates
let resizeObserver: ResizeObserver | null = null

const setupResizeObserver = () => {
  if (!previewContainer.value || !window.ResizeObserver) return
  
  resizeObserver = new ResizeObserver((entries) => {
    entries.forEach((entry) => {
      // Handle container resize
      const { width, height } = entry.contentRect
      
      // Emit config update if needed for responsive adjustments
      if (width < 768 && props.device === 'desktop') {
        // Auto-switch to mobile layout for small containers
        emit('config-updated', {
          ...mergedConfig.value,
          mobileLayout: 'stacked',
          mobileOptimized: true
        })
      }
    })
  })
  
  resizeObserver.observe(previewContainer.value)
}

const cleanupResizeObserver = () => {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
}

// Error boundary simulation
const handleGlobalError = (event: ErrorEvent) => {
  if (event.filename?.includes('ComponentPreviewFrame')) {
    handleRenderError(`Runtime error: ${event.message}`)
    event.preventDefault()
  }
}

const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
  if (event.reason?.stack?.includes('ComponentPreviewFrame')) {
    handleRenderError(`Promise rejection: ${event.reason.message || event.reason}`)
    event.preventDefault()
  }
}

// Watch for config changes
watch(() => props.config, () => {
  // Debounce config updates to avoid excessive re-renders
  setTimeout(() => {
    emit('config-updated', mergedConfig.value)
  }, 100)
}, { deep: true })

watch(() => props.sampleData, () => {
  // Regenerate sample data when config changes
  if (props.sampleData) {
    // Force re-render with new sample data
    isLoading.value = true
    nextTick(() => {
      handleComponentLoad()
    })
  }
}, { deep: true })

// Lifecycle
onMounted(async () => {
  try {
    // Setup observers
    await nextTick()
    setupIntersectionObserver()
    setupResizeObserver()
    
    // Setup global error handlers
    window.addEventListener('error', handleGlobalError)
    window.addEventListener('unhandledrejection', handleUnhandledRejection)
    
    // Simulate initial load
    setTimeout(() => {
      handleComponentLoad()
    }, 300)
    
  } catch (error) {
    handleRenderError(error as Error)
  }
})

onUnmounted(() => {
  // Cleanup observers
  cleanupIntersectionObserver()
  cleanupResizeObserver()
  
  // Cleanup global error handlers
  window.removeEventListener('error', handleGlobalError)
  window.removeEventListener('unhandledrejection', handleUnhandledRejection)
})
</script>

<style scoped>
.component-preview-frame {
  @apply relative overflow-hidden;
}

.preview-container {
  @apply relative;
}

.component-render-area {
  @apply w-full h-full;
}

/* Device-specific styles */
.device-desktop .preview-container {
  @apply min-h-[400px];
}

.device-tablet .preview-container {
  @apply w-[768px] h-[1024px] max-w-full max-h-full;
}

.device-mobile .preview-container {
  @apply w-[375px] h-[667px] max-w-full max-h-full;
}

/* Grid overlay styles */
.grid-overlay {
  background-image: 
    linear-gradient(rgba(59, 130, 246, 0.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
  background-size: 20px 20px;
}

/* Dark mode grid */
.dark .grid-overlay {
  background-image: 
    linear-gradient(rgba(147, 197, 253, 0.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(147, 197, 253, 0.1) 1px, transparent 1px);
}

/* Ruler styles */
.horizontal-ruler {
  background-image: repeating-linear-gradient(
    90deg,
    transparent,
    transparent 9px,
    rgba(107, 114, 128, 0.3) 9px,
    rgba(107, 114, 128, 0.3) 10px
  );
}

.vertical-ruler {
  background-image: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 9px,
    rgba(107, 114, 128, 0.3) 9px,
    rgba(107, 114, 128, 0.3) 10px
  );
}

/* Loading and error overlay styles */
.loading-overlay,
.error-overlay {
  backdrop-filter: blur(2px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .device-tablet .preview-container,
  .device-mobile .preview-container {
    @apply w-full h-auto min-h-[400px];
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .grid-overlay {
    background-image: 
      linear-gradient(rgba(0, 0, 0, 0.3) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0, 0, 0, 0.3) 1px, transparent 1px);
  }
  
  .dark .grid-overlay {
    background-image: 
      linear-gradient(rgba(255, 255, 255, 0.3) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255, 255, 255, 0.3) 1px, transparent 1px);
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .component-preview-frame *,
  .component-preview-frame *::before,
  .component-preview-frame *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Print styles */
@media print {
  .grid-overlay,
  .rulers,
  .loading-overlay,
  .error-overlay {
    @apply hidden;
  }
}
</style>