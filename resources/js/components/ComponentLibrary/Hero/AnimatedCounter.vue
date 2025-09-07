<template>
  <div
    ref="counterRef"
    class="animated-counter"
    :class="{
      'loading': isLoading,
      'error': hasError,
      'reduced-motion': prefersReducedMotion
    }"
  >
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state" :aria-label="loadingMessage">
      <div class="skeleton-counter tabular-nums">
        {{ placeholderValue }}
      </div>
      <div class="loading-indicator" aria-hidden="true">
        <div class="pulse"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="error-state" role="alert">
      <span class="error-value tabular-nums" :aria-label="errorMessage">
        {{ fallbackDisplayValue }}
      </span>
      <button
        v-if="canRetry"
        @click="retryDataFetch"
        class="retry-button"
        :aria-label="retryMessage"
        type="button"
      >
        <svg class="retry-icon" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Success State -->
    <span
      v-else
      class="counter-value tabular-nums"
      :aria-label="accessibilityLabel"
      :aria-live="isAnimating ? 'polite' : 'off'"
      :aria-atomic="true"
    >
      {{ displayValue }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import type { StatisticCounter } from '@/types/components'

interface Props {
  statistic: StatisticCounter
  duration?: number
  delay?: number
  easing?: 'linear' | 'ease-in' | 'ease-out' | 'ease-in-out'
  locale?: string
  refreshInterval?: number // in milliseconds
  retryAttempts?: number
  respectReducedMotion?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  duration: 2000,
  delay: 0,
  easing: 'ease-out',
  locale: 'en-US',
  refreshInterval: 300000, // 5 minutes
  retryAttempts: 3,
  respectReducedMotion: true
})

const emit = defineEmits<{
  animationStart: []
  animationComplete: []
  dataLoaded: [value: number]
  dataError: [error: Error]
  retryAttempt: [attempt: number]
}>()

// Refs
const counterRef = ref<HTMLElement>()
const currentValue = ref(0)
const actualValue = ref<number>(0)
const isVisible = ref(false)
const isLoading = ref(false)
const hasError = ref(false)
const isAnimating = ref(false)
const animationId = ref<number>()
const refreshTimer = ref<number>()
const retryCount = ref(0)

// Motion preference detection
const prefersReducedMotion = ref(false)

// Computed properties
const displayValue = computed(() => {
  const value = Math.round(currentValue.value)
  const formatted = formatNumber(value)
  return `${props.statistic.prefix || ''}${formatted}${props.statistic.suffix || ''}`
})

const fallbackDisplayValue = computed(() => {
  const value = typeof props.statistic.value === 'number' ? props.statistic.value : 0
  const formatted = formatNumber(value)
  return `${props.statistic.prefix || ''}${formatted}${props.statistic.suffix || ''}`
})

const placeholderValue = computed(() => {
  // Generate placeholder with similar character count
  const targetLength = fallbackDisplayValue.value.replace(/[^\d]/g, '').length
  const placeholder = '0'.repeat(Math.max(1, targetLength))
  return `${props.statistic.prefix || ''}${placeholder}${props.statistic.suffix || ''}`
})

const accessibilityLabel = computed(() => {
  const value = Math.round(currentValue.value)
  const label = props.statistic.label
  return `${label}: ${displayValue.value}`
})

const loadingMessage = computed(() => `Loading ${props.statistic.label}`)
const errorMessage = computed(() => `Error loading ${props.statistic.label}. Showing fallback value.`)
const retryMessage = computed(() => `Retry loading ${props.statistic.label}`)

const canRetry = computed(() => {
  return props.statistic.source === 'api' && 
         props.statistic.apiEndpoint && 
         retryCount.value < props.retryAttempts
})

// Number formatting with internationalization
const formatNumber = (value: number): string => {
  try {
    // Handle large numbers with appropriate suffixes
    if (value >= 1000000000) {
      return (value / 1000000000).toLocaleString(props.locale, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1
      }) + 'B'
    } else if (value >= 1000000) {
      return (value / 1000000).toLocaleString(props.locale, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1
      }) + 'M'
    } else if (value >= 1000) {
      return (value / 1000).toLocaleString(props.locale, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1
      }) + 'K'
    } else {
      return value.toLocaleString(props.locale)
    }
  } catch (error) {
    // Fallback to basic formatting if locale is not supported
    console.warn('Locale not supported, using fallback formatting:', error)
    return value.toString()
  }
}

// Easing functions
const easingFunctions = {
  linear: (t: number) => t,
  'ease-in': (t: number) => t * t,
  'ease-out': (t: number) => t * (2 - t),
  'ease-in-out': (t: number) => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t
}

// Data fetching
const fetchRealTimeData = async (): Promise<number> => {
  if (!props.statistic.apiEndpoint) {
    throw new Error('No API endpoint provided')
  }

  try {
    const response = await fetch(props.statistic.apiEndpoint, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()
    
    // Assume the API returns { value: number } or just a number
    const value = typeof data === 'number' ? data : data.value
    
    if (typeof value !== 'number' || isNaN(value)) {
      throw new Error('Invalid data format received from API')
    }

    return value
  } catch (error) {
    console.error('Failed to fetch real-time data:', error)
    throw error
  }
}

// Animation logic
const animate = () => {
  if (prefersReducedMotion.value && props.respectReducedMotion) {
    // Skip animation for users who prefer reduced motion
    currentValue.value = actualValue.value
    isAnimating.value = false
    emit('animationComplete')
    return
  }

  const startTime = performance.now()
  const startValue = currentValue.value
  const endValue = actualValue.value
  const duration = props.duration

  isAnimating.value = true
  emit('animationStart')

  const step = (currentTime: number) => {
    const elapsed = currentTime - startTime
    const progress = Math.min(elapsed / duration, 1)
    
    const easedProgress = easingFunctions[props.easing](progress)
    currentValue.value = startValue + (endValue - startValue) * easedProgress

    if (progress < 1) {
      animationId.value = requestAnimationFrame(step)
    } else {
      isAnimating.value = false
      emit('animationComplete')
    }
  }

  if (props.delay > 0) {
    setTimeout(() => {
      animationId.value = requestAnimationFrame(step)
    }, props.delay)
  } else {
    animationId.value = requestAnimationFrame(step)
  }
}

// Load data based on source
const loadData = async () => {
  hasError.value = false
  
  if (props.statistic.source === 'api' && props.statistic.apiEndpoint) {
    isLoading.value = true
    
    try {
      const value = await fetchRealTimeData()
      actualValue.value = value
      emit('dataLoaded', value)
      retryCount.value = 0 // Reset retry count on success
    } catch (error) {
      console.error('Error loading real-time data:', error)
      hasError.value = true
      actualValue.value = typeof props.statistic.value === 'number' ? props.statistic.value : 0
      emit('dataError', error as Error)
    } finally {
      isLoading.value = false
    }
  } else {
    // Use manual value
    actualValue.value = typeof props.statistic.value === 'number' ? props.statistic.value : 0
  }
}

// Retry mechanism
const retryDataFetch = async () => {
  if (retryCount.value >= props.retryAttempts) {
    return
  }
  
  retryCount.value++
  emit('retryAttempt', retryCount.value)
  
  // Exponential backoff
  const delay = Math.pow(2, retryCount.value - 1) * 1000
  await new Promise(resolve => setTimeout(resolve, delay))
  
  await loadData()
}

// Intersection Observer
const handleIntersection = (entries: IntersectionObserverEntry[]) => {
  entries.forEach(entry => {
    if (entry.isIntersecting && !isVisible.value) {
      isVisible.value = true
      if (props.statistic.animated !== false) {
        animate()
      } else {
        currentValue.value = actualValue.value
      }
    }
  })
}

// Setup refresh timer for real-time data
const setupRefreshTimer = () => {
  if (props.statistic.source === 'api' && props.refreshInterval > 0) {
    refreshTimer.value = window.setInterval(async () => {
      if (!isLoading.value && !hasError.value) {
        await loadData()
        if (isVisible.value && props.statistic.animated !== false) {
          animate()
        }
      }
    }, props.refreshInterval)
  }
}

// Detect motion preferences
const detectMotionPreference = () => {
  if (typeof window !== 'undefined' && window.matchMedia) {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    prefersReducedMotion.value = mediaQuery.matches
    
    mediaQuery.addEventListener('change', (e) => {
      prefersReducedMotion.value = e.matches
    })
  }
}

let observer: IntersectionObserver | null = null

// Watch for statistic changes
watch(() => props.statistic, async () => {
  await loadData()
  if (isVisible.value && props.statistic.animated !== false) {
    animate()
  }
}, { deep: true })

onMounted(async () => {
  detectMotionPreference()
  
  // Load initial data
  await loadData()
  
  if (counterRef.value) {
    // Use Intersection Observer to trigger animation when element comes into view
    observer = new IntersectionObserver(handleIntersection, {
      threshold: 0.1,
      rootMargin: '50px'
    })
    
    observer.observe(counterRef.value)
  }
  
  // Setup refresh timer for real-time updates
  setupRefreshTimer()
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
  
  if (animationId.value) {
    cancelAnimationFrame(animationId.value)
  }
  
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
  }
})
</script>

<style scoped>
.animated-counter {
  @apply relative inline-block;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum";
}

.counter-value {
  @apply text-inherit font-bold;
}

/* Loading State */
.loading-state {
  @apply relative;
}

.skeleton-counter {
  @apply text-gray-300 dark:text-gray-600;
}

.loading-indicator {
  @apply absolute inset-0 flex items-center justify-center;
}

.pulse {
  @apply w-2 h-2 bg-blue-500 rounded-full animate-pulse;
}

/* Error State */
.error-state {
  @apply flex items-center gap-2;
}

.error-value {
  @apply text-red-600 dark:text-red-400;
}

.retry-button {
  @apply p-1 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 
         transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 
         focus:ring-offset-2 rounded;
}

.retry-icon {
  @apply w-4 h-4;
}

/* Reduced Motion */
.reduced-motion .counter-value {
  transition: none !important;
  animation: none !important;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .animated-counter * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .error-value {
    @apply border border-red-600;
  }
  
  .retry-button {
    @apply border border-red-600;
  }
}

/* Focus indicators for keyboard navigation */
.retry-button:focus-visible {
  @apply ring-2 ring-red-500 ring-offset-2;
}
</style>