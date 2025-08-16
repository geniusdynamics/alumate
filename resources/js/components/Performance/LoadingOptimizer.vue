<template>
  <div class="loading-optimizer">
    <!-- Skeleton Screens -->
    <div v-if="showSkeleton" class="skeleton-container">
      <component :is="skeletonComponent" v-bind="skeletonProps" />
    </div>

    <!-- Loading States -->
    <div v-else-if="showLoading" class="loading-container">
      <div class="loading-content">
        <LoadingSpinner :size="loadingSize" />
        <div v-if="loadingMessage" class="loading-message">
          {{ loadingMessage }}
        </div>
        <div v-if="showProgress" class="loading-progress">
          <div class="progress-bar">
            <div 
              class="progress-fill" 
              :style="{ width: `${progress}%` }"
            ></div>
          </div>
          <div class="progress-text">{{ progress }}%</div>
        </div>
      </div>
    </div>

    <!-- Optimized Content -->
    <div v-else class="optimized-content">
      <slot />
    </div>

    <!-- Performance Hints -->
    <div v-if="showHints && performanceHints.length > 0" class="performance-hints">
      <div class="hints-header">
        <LightBulbIcon class="w-4 h-4" />
        <span class="text-sm font-medium">Performance Tips</span>
      </div>
      <div class="hints-list">
        <div 
          v-for="hint in performanceHints" 
          :key="hint.id"
          class="hint-item"
        >
          {{ hint.message }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import LoadingSpinner from '../LoadingSpinner.vue'
import { LightBulbIcon } from '@heroicons/vue/24/outline'
import { recordCustomMetric } from '../../utils/performance-monitor'

// Skeleton Components
import UserListSkeleton from './Skeletons/UserListSkeleton.vue'
import PostListSkeleton from './Skeletons/PostListSkeleton.vue'
import ProfileSkeleton from './Skeletons/ProfileSkeleton.vue'
import DashboardSkeleton from './Skeletons/DashboardSkeleton.vue'
import TableSkeleton from './Skeletons/TableSkeleton.vue'

interface Props {
  loading?: boolean
  skeleton?: 'user-list' | 'post-list' | 'profile' | 'dashboard' | 'table' | 'custom'
  skeletonProps?: Record<string, any>
  loadingMessage?: string
  showProgress?: boolean
  progress?: number
  loadingSize?: 'sm' | 'md' | 'lg'
  showHints?: boolean
  optimizeImages?: boolean
  lazyLoad?: boolean
  preloadCritical?: boolean
}

interface PerformanceHint {
  id: string
  message: string
  type: 'tip' | 'warning' | 'info'
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  skeleton: 'custom',
  loadingMessage: '',
  showProgress: false,
  progress: 0,
  loadingSize: 'md',
  showHints: false,
  optimizeImages: true,
  lazyLoad: true,
  preloadCritical: true
})

const emit = defineEmits<{
  loadingStart: []
  loadingComplete: []
  optimizationApplied: [type: string]
}>()

const loadingStartTime = ref<number | null>(null)
const performanceHints = ref<PerformanceHint[]>([])
const imageOptimizations = ref(new Set<string>())
const lazyLoadObserver = ref<IntersectionObserver | null>(null)

// Computed properties
const showSkeleton = computed(() => props.loading && props.skeleton !== 'custom')
const showLoading = computed(() => props.loading && props.skeleton === 'custom')

const skeletonComponent = computed(() => {
  const components = {
    'user-list': UserListSkeleton,
    'post-list': PostListSkeleton,
    'profile': ProfileSkeleton,
    'dashboard': DashboardSkeleton,
    'table': TableSkeleton
  }
  
  return components[props.skeleton as keyof typeof components] || 'div'
})

// Watch for loading state changes
watch(() => props.loading, (isLoading) => {
  if (isLoading) {
    handleLoadingStart()
  } else {
    handleLoadingComplete()
  }
})

// Handle loading start
const handleLoadingStart = () => {
  loadingStartTime.value = performance.now()
  emit('loadingStart')
  
  // Record loading start metric
  recordCustomMetric('LoadingStart', 0, {
    component: 'LoadingOptimizer',
    skeleton: props.skeleton
  })
}

// Handle loading complete
const handleLoadingComplete = () => {
  if (loadingStartTime.value) {
    const loadingDuration = performance.now() - loadingStartTime.value
    
    // Record loading duration
    recordCustomMetric('LoadingDuration', loadingDuration, {
      component: 'LoadingOptimizer',
      skeleton: props.skeleton
    })
    
    loadingStartTime.value = null
  }
  
  emit('loadingComplete')
  
  // Apply optimizations after content loads
  setTimeout(() => {
    applyOptimizations()
  }, 100)
}

// Apply performance optimizations
const applyOptimizations = () => {
  if (props.optimizeImages) {
    optimizeImages()
  }
  
  if (props.lazyLoad) {
    setupLazyLoading()
  }
  
  if (props.preloadCritical) {
    preloadCriticalResources()
  }
  
  generatePerformanceHints()
}

// Optimize images
const optimizeImages = () => {
  const images = document.querySelectorAll('img:not([data-optimized])')
  
  images.forEach((img: Element) => {
    const imageEl = img as HTMLImageElement
    const src = imageEl.src
    
    if (src && !imageOptimizations.value.has(src)) {
      // Add loading="lazy" if not present
      if (!imageEl.hasAttribute('loading')) {
        imageEl.loading = 'lazy'
      }
      
      // Add proper sizing attributes
      if (!imageEl.hasAttribute('width') || !imageEl.hasAttribute('height')) {
        // Try to get dimensions from computed style
        const computedStyle = window.getComputedStyle(imageEl)
        const width = parseInt(computedStyle.width)
        const height = parseInt(computedStyle.height)
        
        if (width && height) {
          imageEl.width = width
          imageEl.height = height
        }
      }
      
      // Mark as optimized
      imageEl.setAttribute('data-optimized', 'true')
      imageOptimizations.value.add(src)
      
      emit('optimizationApplied', 'image-optimization')
    }
  })
}

// Setup lazy loading for non-critical content
const setupLazyLoading = () => {
  if (!('IntersectionObserver' in window)) return
  
  // Disconnect existing observer
  if (lazyLoadObserver.value) {
    lazyLoadObserver.value.disconnect()
  }
  
  lazyLoadObserver.value = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const element = entry.target as HTMLElement
        
        // Load lazy content
        if (element.hasAttribute('data-lazy-src')) {
          const img = element as HTMLImageElement
          img.src = img.getAttribute('data-lazy-src') || ''
          img.removeAttribute('data-lazy-src')
        }
        
        // Trigger lazy component loading
        if (element.hasAttribute('data-lazy-component')) {
          element.dispatchEvent(new CustomEvent('lazy-load'))
        }
        
        lazyLoadObserver.value?.unobserve(element)
      }
    })
  }, {
    rootMargin: '50px'
  })
  
  // Observe lazy elements
  const lazyElements = document.querySelectorAll('[data-lazy-src], [data-lazy-component]')
  lazyElements.forEach((el) => {
    lazyLoadObserver.value?.observe(el)
  })
  
  if (lazyElements.length > 0) {
    emit('optimizationApplied', 'lazy-loading')
  }
}

// Preload critical resources
const preloadCriticalResources = () => {
  const criticalResources = [
    // Critical CSS
    ...Array.from(document.querySelectorAll('link[rel="stylesheet"][data-critical]')),
    // Critical images
    ...Array.from(document.querySelectorAll('img[data-critical]')),
    // Critical fonts
    ...Array.from(document.querySelectorAll('link[rel="preload"][as="font"]'))
  ]
  
  criticalResources.forEach((resource) => {
    const link = resource as HTMLLinkElement
    if (link.href && !link.hasAttribute('data-preloaded')) {
      // Create preload link
      const preloadLink = document.createElement('link')
      preloadLink.rel = 'preload'
      preloadLink.href = link.href
      
      if (link.tagName === 'IMG') {
        preloadLink.as = 'image'
      } else if (link.type?.includes('font')) {
        preloadLink.as = 'font'
        preloadLink.crossOrigin = 'anonymous'
      } else {
        preloadLink.as = 'style'
      }
      
      document.head.appendChild(preloadLink)
      link.setAttribute('data-preloaded', 'true')
    }
  })
  
  if (criticalResources.length > 0) {
    emit('optimizationApplied', 'resource-preloading')
  }
}

// Generate performance hints
const generatePerformanceHints = () => {
  const hints: PerformanceHint[] = []
  
  // Check for unoptimized images
  const unoptimizedImages = document.querySelectorAll('img:not([data-optimized])')
  if (unoptimizedImages.length > 0) {
    hints.push({
      id: 'unoptimized-images',
      message: `${unoptimizedImages.length} images could be optimized for better performance`,
      type: 'tip'
    })
  }
  
  // Check for missing alt attributes
  const imagesWithoutAlt = document.querySelectorAll('img:not([alt])')
  if (imagesWithoutAlt.length > 0) {
    hints.push({
      id: 'missing-alt',
      message: `${imagesWithoutAlt.length} images are missing alt attributes`,
      type: 'warning'
    })
  }
  
  // Check for large DOM size
  const domSize = document.querySelectorAll('*').length
  if (domSize > 1500) {
    hints.push({
      id: 'large-dom',
      message: `DOM size is large (${domSize} elements). Consider pagination or virtualization`,
      type: 'warning'
    })
  }
  
  // Check for unused CSS
  const stylesheets = document.querySelectorAll('link[rel="stylesheet"]')
  if (stylesheets.length > 5) {
    hints.push({
      id: 'many-stylesheets',
      message: `${stylesheets.length} stylesheets loaded. Consider combining them`,
      type: 'tip'
    })
  }
  
  performanceHints.value = hints
}

// Cleanup
onUnmounted(() => {
  if (lazyLoadObserver.value) {
    lazyLoadObserver.value.disconnect()
  }
})

// Initialize optimizations on mount
onMounted(() => {
  if (!props.loading) {
    applyOptimizations()
  }
})
</script>

<style scoped>
.loading-optimizer {
  @apply relative;
}

.skeleton-container {
  @apply animate-pulse;
}

.loading-container {
  @apply flex items-center justify-center py-12;
}

.loading-content {
  @apply text-center space-y-4;
}

.loading-message {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.loading-progress {
  @apply space-y-2;
}

.progress-bar {
  @apply w-48 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
}

.progress-fill {
  @apply h-full bg-blue-600 transition-all duration-300 ease-out;
}

.progress-text {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.performance-hints {
  @apply mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800;
}

.hints-header {
  @apply flex items-center space-x-2 text-blue-700 dark:text-blue-300 mb-2;
}

.hints-list {
  @apply space-y-1;
}

.hint-item {
  @apply text-xs text-blue-600 dark:text-blue-400;
}

.optimized-content {
  @apply relative;
}

/* Skeleton animations */
@keyframes skeleton-loading {
  0% {
    background-position: -200px 0;
  }
  100% {
    background-position: calc(200px + 100%) 0;
  }
}

.skeleton-container :deep(.skeleton-item) {
  @apply bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-700;
  background-size: 200px 100%;
  animation: skeleton-loading 1.5s infinite linear;
}
</style>