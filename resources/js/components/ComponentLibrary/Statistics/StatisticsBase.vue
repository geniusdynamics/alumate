<template>
  <div 
    ref="containerRef"
    :class="[
      'statistics-container',
      `statistics-${layout}`,
      `theme-${theme}`,
      { 'reduced-motion': respectReducedMotion && prefersReducedMotion }
    ]"
    :aria-label="ariaLabel || 'Statistics display'"
    role="region"
  >
    <div v-if="title" class="statistics-header">
      <h2 :class="titleClasses">{{ title }}</h2>
      <p v-if="description" class="statistics-description">{{ description }}</p>
    </div>

    <div 
      :class="[
        'statistics-grid',
        `grid-${gridColumns.desktop}-${gridColumns.tablet}-${gridColumns.mobile}`,
        `gap-${spacing}`
      ]"
    >
      <slot 
        :statistics="processedStatistics"
        :is-visible="isVisible"
        :animate="shouldAnimate"
      />
    </div>

    <!-- Error state -->
    <div 
      v-if="hasErrors && showErrors" 
      class="statistics-error"
      role="alert"
      aria-live="polite"
    >
      <p class="error-message">{{ errorMessage }}</p>
      <button 
        v-if="allowRetry"
        @click="retryDataLoad"
        class="retry-button"
        type="button"
      >
        Retry Loading Data
      </button>
    </div>

    <!-- Loading state -->
    <div 
      v-if="isLoading" 
      class="statistics-loading"
      aria-live="polite"
      aria-label="Loading statistics data"
    >
      <div class="loading-skeleton">
        <div 
          v-for="n in skeletonCount" 
          :key="n"
          class="skeleton-item"
          :style="{ animationDelay: `${n * 0.1}s` }"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useIntersectionObserver } from '@/composables/useIntersectionObserver'
import { useAnalytics } from '@/composables/useAnalytics'

export interface StatisticItem {
  id: string
  value: number | string
  label: string
  suffix?: string
  prefix?: string
  format?: 'number' | 'currency' | 'percentage' | 'duration'
  source?: 'manual' | 'api'
  apiEndpoint?: string
  color?: string
  icon?: string
  description?: string
  trend?: {
    direction: 'up' | 'down' | 'neutral'
    value: number
    label: string
  }
}

export interface StatisticsConfig {
  title?: string
  description?: string
  layout: 'grid' | 'row' | 'column'
  theme: 'default' | 'minimal' | 'modern' | 'card'
  spacing: 'compact' | 'default' | 'spacious'
  gridColumns: {
    desktop: number
    tablet: number
    mobile: number
  }
  animation: {
    enabled: boolean
    trigger: 'immediate' | 'scroll' | 'hover'
    duration: number
    delay: number
    easing: string
  }
  accessibility: {
    ariaLabel?: string
    announceUpdates: boolean
    respectReducedMotion: boolean
  }
  dataRefresh: {
    enabled: boolean
    interval: number // milliseconds
    retryAttempts: number
  }
  errorHandling: {
    showErrors: boolean
    errorMessage: string
    allowRetry: boolean
  }
}

interface Props {
  statistics: StatisticItem[]
  config: StatisticsConfig
  loading?: boolean
  error?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: null
})

const emit = defineEmits<{
  'data-load': []
  'retry': []
  'visibility-change': [visible: boolean]
  'animation-complete': [statistic: StatisticItem]
}>()

// Refs
const containerRef = ref<HTMLElement>()
const { trackEvent } = useAnalytics()

// Intersection observer for scroll-triggered animations
const { isIntersecting } = useIntersectionObserver(containerRef, {
  threshold: 0.3,
  rootMargin: '50px'
})

// Computed properties
const layout = computed(() => props.config.layout)
const theme = computed(() => props.config.theme)
const spacing = computed(() => props.config.spacing)
const gridColumns = computed(() => props.config.gridColumns)
const ariaLabel = computed(() => props.config.accessibility?.ariaLabel)
const respectReducedMotion = computed(() => props.config.accessibility?.respectReducedMotion ?? true)
const showErrors = computed(() => props.config.errorHandling?.showErrors ?? true)
const allowRetry = computed(() => props.config.errorHandling?.allowRetry ?? true)
const errorMessage = computed(() => props.error || props.config.errorHandling?.errorMessage || 'Failed to load statistics data')

const titleClasses = computed(() => [
  'statistics-title',
  `theme-${theme.value}`
])

const isLoading = computed(() => props.loading)
const hasErrors = computed(() => !!props.error)
const skeletonCount = computed(() => Math.min(props.statistics.length || 4, 8))

// Check for reduced motion preference
const prefersReducedMotion = ref(false)

// Visibility and animation state
const isVisible = ref(false)
const shouldAnimate = computed(() => {
  if (!props.config.animation.enabled) return false
  if (respectReducedMotion.value && prefersReducedMotion.value) return false
  
  switch (props.config.animation.trigger) {
    case 'immediate':
      return true
    case 'scroll':
      return isVisible.value
    case 'hover':
      return false // Handled by individual components
    default:
      return false
  }
})

// Process statistics data
const processedStatistics = computed(() => {
  return props.statistics.map(stat => ({
    ...stat,
    formattedValue: formatValue(stat.value, stat.format),
    displayValue: `${stat.prefix || ''}${formatValue(stat.value, stat.format)}${stat.suffix || ''}`
  }))
})

// Data refresh interval
let refreshInterval: NodeJS.Timeout | null = null

// Methods
const formatValue = (value: number | string, format?: string): string => {
  if (typeof value === 'string') return value
  
  switch (format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value)
    
    case 'percentage':
      return `${value}%`
    
    case 'duration':
      if (value < 60) return `${value}s`
      if (value < 3600) return `${Math.floor(value / 60)}m`
      return `${Math.floor(value / 3600)}h`
    
    case 'number':
    default:
      return new Intl.NumberFormat('en-US').format(value)
  }
}

const retryDataLoad = () => {
  trackEvent('statistics_retry', {
    component: 'StatisticsBase',
    error: props.error
  })
  emit('retry')
}

const setupDataRefresh = () => {
  if (!props.config.dataRefresh.enabled) return
  
  refreshInterval = setInterval(() => {
    emit('data-load')
  }, props.config.dataRefresh.interval)
}

const cleanupDataRefresh = () => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
}

// Watchers
watch(isIntersecting, (visible) => {
  isVisible.value = visible
  emit('visibility-change', visible)
  
  if (visible) {
    trackEvent('statistics_view', {
      component: 'StatisticsBase',
      statistics_count: props.statistics.length,
      layout: layout.value,
      theme: theme.value
    })
  }
})

watch(() => props.config.dataRefresh, (newConfig) => {
  cleanupDataRefresh()
  if (newConfig.enabled) {
    setupDataRefresh()
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  // Check for reduced motion preference
  if (typeof window !== 'undefined') {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    prefersReducedMotion.value = mediaQuery.matches
    
    mediaQuery.addEventListener('change', (e) => {
      prefersReducedMotion.value = e.matches
    })
  }
  
  // Setup data refresh if enabled
  if (props.config.dataRefresh.enabled) {
    setupDataRefresh()
  }
  
  // Initial data load
  emit('data-load')
})

onUnmounted(() => {
  cleanupDataRefresh()
})
</script>

<style scoped>
.statistics-container {
  @apply w-full;
}

.statistics-header {
  @apply mb-6 text-center;
}

.statistics-title {
  @apply text-2xl font-bold text-gray-900 dark:text-white mb-2;
}

.statistics-title.theme-minimal {
  @apply text-xl font-semibold;
}

.statistics-title.theme-modern {
  @apply text-3xl font-extrabold;
}

.statistics-title.theme-card {
  @apply text-2xl font-bold;
}

.statistics-description {
  @apply text-gray-600 dark:text-gray-300 max-w-2xl mx-auto;
}

.statistics-grid {
  @apply grid w-full;
}

.statistics-grid.grid-4-2-1 {
  @apply grid-cols-1 md:grid-cols-2 lg:grid-cols-4;
}

.statistics-grid.grid-3-2-1 {
  @apply grid-cols-1 md:grid-cols-2 lg:grid-cols-3;
}

.statistics-grid.grid-2-2-1 {
  @apply grid-cols-1 md:grid-cols-2;
}

.statistics-grid.grid-1-1-1 {
  @apply grid-cols-1;
}

.statistics-grid.gap-compact {
  @apply gap-4;
}

.statistics-grid.gap-default {
  @apply gap-6;
}

.statistics-grid.gap-spacious {
  @apply gap-8;
}

.statistics-error {
  @apply bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center;
}

.error-message {
  @apply text-red-700 dark:text-red-300 mb-3;
}

.retry-button {
  @apply bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors duration-200;
}

.statistics-loading {
  @apply w-full;
}

.loading-skeleton {
  @apply grid gap-6;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.skeleton-item {
  @apply bg-gray-200 dark:bg-gray-700 rounded-lg h-24 animate-pulse;
}

.reduced-motion * {
  animation-duration: 0.01ms !important;
  animation-iteration-count: 1 !important;
  transition-duration: 0.01ms !important;
}

/* Layout variations */
.statistics-row .statistics-grid {
  @apply flex flex-wrap justify-center items-center;
}

.statistics-column .statistics-grid {
  @apply flex flex-col items-center;
}

/* Theme variations */
.theme-minimal {
  @apply bg-transparent;
}

.theme-modern {
  @apply bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-8;
}

.theme-card {
  @apply bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6;
}
</style>