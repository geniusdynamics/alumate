<template>
  <div 
    class="statistics-display"
    :class="{
      'loading': isAnyLoading,
      'has-error': hasAnyError,
      [`layout-${layout}`]: true,
      [`size-${size}`]: true
    }"
    role="region"
    :aria-label="accessibilityLabel"
  >
    <!-- Loading State for All Statistics -->
    <div v-if="isAnyLoading && !hasAnyData" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner" aria-hidden="true">
          <svg class="animate-spin" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
        </div>
        <p class="loading-text">{{ loadingMessage }}</p>
        <div class="loading-progress">
          <div 
            class="progress-bar" 
            :style="{ width: `${loadingProgress}%` }"
            :aria-valuenow="loadingProgress"
            aria-valuemin="0"
            aria-valuemax="100"
            role="progressbar"
          ></div>
        </div>
      </div>
    </div>

    <!-- Statistics Grid -->
    <div v-else class="statistics-grid">
      <div
        v-for="statistic in statistics"
        :key="statistic.id"
        class="statistic-item"
        :class="{
          'has-error': getStatisticState(statistic.id)?.error,
          'loading': getStatisticState(statistic.id)?.isLoading
        }"
      >
        <!-- Statistic Content -->
        <div class="statistic-content">
          <!-- Value with AnimatedCounter -->
          <div class="statistic-value">
            <AnimatedCounter
              :statistic="statistic"
              :duration="animationDuration"
              :delay="getAnimationDelay(statistic.id)"
              :easing="animationEasing"
              :locale="locale"
              :refresh-interval="refreshInterval"
              :retry-attempts="retryAttempts"
              :respect-reduced-motion="respectReducedMotion"
              @animation-start="handleAnimationStart(statistic.id)"
              @animation-complete="handleAnimationComplete(statistic.id)"
              @data-loaded="handleDataLoaded(statistic.id, $event)"
              @data-error="handleDataError(statistic.id, $event)"
              @retry-attempt="handleRetryAttempt(statistic.id, $event)"
            />
          </div>

          <!-- Label -->
          <div class="statistic-label">
            {{ statistic.label }}
          </div>

          <!-- Additional Info -->
          <div v-if="showLastUpdated && getLastUpdated(statistic.id)" class="statistic-meta">
            <time 
              :datetime="getLastUpdated(statistic.id)?.toISOString()"
              class="last-updated"
            >
              Updated {{ formatRelativeTime(getLastUpdated(statistic.id)!) }}
            </time>
          </div>
        </div>

        <!-- Error State for Individual Statistic -->
        <div 
          v-if="getStatisticState(statistic.id)?.error" 
          class="statistic-error"
          role="alert"
        >
          <svg class="error-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span class="error-message">{{ formatError(getStatisticState(statistic.id)?.error) }}</span>
        </div>
      </div>
    </div>

    <!-- Global Actions -->
    <div v-if="showActions && (hasAnyError || showRefresh)" class="statistics-actions">
      <button
        v-if="hasAnyError"
        @click="retryFailed"
        :disabled="isAnyLoading"
        class="action-button retry-button"
        type="button"
      >
        <svg class="button-icon" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
        Retry Failed
      </button>

      <button
        v-if="showRefresh"
        @click="refresh"
        :disabled="isAnyLoading"
        class="action-button refresh-button"
        type="button"
      >
        <svg class="button-icon" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
        Refresh
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import AnimatedCounter from './AnimatedCounter.vue'
import { useStatistics } from '@/composables/useStatistics'
import type { StatisticCounter } from '@/types/components'

interface Props {
  statistics: StatisticCounter[]
  layout?: 'horizontal' | 'vertical' | 'grid' | 'compact'
  size?: 'sm' | 'md' | 'lg'
  animationDuration?: number
  animationEasing?: 'linear' | 'ease-in' | 'ease-out' | 'ease-in-out'
  staggerDelay?: number
  locale?: string
  refreshInterval?: number
  retryAttempts?: number
  respectReducedMotion?: boolean
  showLastUpdated?: boolean
  showActions?: boolean
  showRefresh?: boolean
  enableRealTime?: boolean
  cacheEnabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  layout: 'horizontal',
  size: 'md',
  animationDuration: 2000,
  animationEasing: 'ease-out',
  staggerDelay: 200,
  locale: 'en-US',
  refreshInterval: 300000, // 5 minutes
  retryAttempts: 3,
  respectReducedMotion: true,
  showLastUpdated: false,
  showActions: true,
  showRefresh: false,
  enableRealTime: true,
  cacheEnabled: true
})

const emit = defineEmits<{
  animationStart: [statisticId: string]
  animationComplete: [statisticId: string]
  dataLoaded: [statisticId: string, value: number]
  dataError: [statisticId: string, error: Error]
  retryAttempt: [statisticId: string, attempt: number]
  allAnimationsComplete: []
}>()

// Use statistics composable
const {
  statisticStates,
  isAnyLoading,
  hasAnyError,
  loadingProgress,
  getStatisticState,
  refresh,
  retryFailed,
  formatError
} = useStatistics(props.statistics, {
  refreshInterval: props.refreshInterval,
  enableRealTime: props.enableRealTime,
  retryAttempts: props.retryAttempts,
  cacheEnabled: props.cacheEnabled
})

// Animation tracking
const animatingStatistics = ref(new Set<string>())
const completedAnimations = ref(new Set<string>())

// Computed properties
const accessibilityLabel = computed(() => 
  `Statistics display with ${props.statistics.length} metrics`
)

const loadingMessage = computed(() => 
  `Loading ${props.statistics.length} statistics...`
)

const hasAnyData = computed(() => 
  Array.from(statisticStates.value.values()).some(state => state.data !== null)
)

// Get animation delay with stagger effect
const getAnimationDelay = (statisticId: string): number => {
  const index = props.statistics.findIndex(s => s.id === statisticId)
  return index * props.staggerDelay
}

// Get last updated time for a statistic
const getLastUpdated = (statisticId: string): Date | null => {
  return getStatisticState(statisticId)?.lastUpdated || null
}

// Format relative time
const formatRelativeTime = (date: Date): string => {
  try {
    const rtf = new Intl.RelativeTimeFormat(props.locale, { numeric: 'auto' })
    const diff = Date.now() - date.getTime()
    const seconds = Math.floor(diff / 1000)
    const minutes = Math.floor(seconds / 60)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)

    if (days > 0) {
      return rtf.format(-days, 'day')
    } else if (hours > 0) {
      return rtf.format(-hours, 'hour')
    } else if (minutes > 0) {
      return rtf.format(-minutes, 'minute')
    } else {
      return rtf.format(-seconds, 'second')
    }
  } catch (error) {
    // Fallback for unsupported locales
    const diff = Date.now() - date.getTime()
    const minutes = Math.floor(diff / 60000)
    if (minutes < 1) return 'just now'
    if (minutes < 60) return `${minutes}m ago`
    const hours = Math.floor(minutes / 60)
    if (hours < 24) return `${hours}h ago`
    const days = Math.floor(hours / 24)
    return `${days}d ago`
  }
}

// Event handlers
const handleAnimationStart = (statisticId: string) => {
  animatingStatistics.value.add(statisticId)
  emit('animationStart', statisticId)
}

const handleAnimationComplete = (statisticId: string) => {
  animatingStatistics.value.delete(statisticId)
  completedAnimations.value.add(statisticId)
  emit('animationComplete', statisticId)

  // Check if all animations are complete
  if (completedAnimations.value.size === props.statistics.length) {
    emit('allAnimationsComplete')
  }
}

const handleDataLoaded = (statisticId: string, value: number) => {
  emit('dataLoaded', statisticId, value)
}

const handleDataError = (statisticId: string, error: Error) => {
  emit('dataError', statisticId, error)
}

const handleRetryAttempt = (statisticId: string, attempt: number) => {
  emit('retryAttempt', statisticId, attempt)
}
</script>

<style scoped>
.statistics-display {
  @apply relative;
}

/* Loading Overlay */
.loading-overlay {
  @apply absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg;
}

.loading-content {
  @apply text-center;
}

.loading-spinner svg {
  @apply w-8 h-8 text-blue-600 dark:text-blue-400 mx-auto mb-4;
}

.loading-text {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-2;
}

.loading-progress {
  @apply w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
}

.progress-bar {
  @apply h-full bg-blue-600 dark:bg-blue-400 transition-all duration-300 ease-out;
}

/* Statistics Grid Layouts */
.statistics-grid {
  @apply grid gap-6;
}

.layout-horizontal .statistics-grid {
  @apply grid-cols-2 md:grid-cols-4;
}

.layout-vertical .statistics-grid {
  @apply grid-cols-1 gap-4;
}

.layout-grid .statistics-grid {
  @apply grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4;
}

.layout-compact .statistics-grid {
  @apply grid-cols-2 sm:grid-cols-4 gap-4;
}

/* Statistic Items */
.statistic-item {
  @apply relative text-center;
}

.statistic-item.loading {
  @apply opacity-75;
}

.statistic-item.has-error {
  @apply opacity-60;
}

.statistic-content {
  @apply space-y-2;
}

/* Statistic Values */
.statistic-value {
  @apply text-3xl font-bold text-gray-900 dark:text-white;
}

.size-sm .statistic-value {
  @apply text-2xl;
}

.size-lg .statistic-value {
  @apply text-4xl;
}

/* Statistic Labels */
.statistic-label {
  @apply text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide;
}

.size-sm .statistic-label {
  @apply text-xs;
}

.size-lg .statistic-label {
  @apply text-base;
}

/* Statistic Meta */
.statistic-meta {
  @apply text-xs text-gray-500 dark:text-gray-500;
}

.last-updated {
  @apply font-mono;
}

/* Error States */
.statistic-error {
  @apply flex items-center justify-center gap-1 mt-2 text-red-600 dark:text-red-400 text-xs;
}

.error-icon {
  @apply w-4 h-4 flex-shrink-0;
}

.error-message {
  @apply truncate;
}

/* Actions */
.statistics-actions {
  @apply flex justify-center gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700;
}

.action-button {
  @apply inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md
         transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.retry-button {
  @apply text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500
         dark:text-red-400 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:focus:ring-red-400;
}

.refresh-button {
  @apply text-blue-700 bg-blue-50 hover:bg-blue-100 focus:ring-blue-500
         dark:text-blue-400 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:focus:ring-blue-400;
}

.action-button:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.button-icon {
  @apply w-4 h-4;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .layout-horizontal .statistics-grid {
    @apply grid-cols-1 gap-4;
  }
  
  .layout-compact .statistics-grid {
    @apply grid-cols-1 gap-3;
  }
  
  .statistic-value {
    @apply text-2xl;
  }
  
  .size-lg .statistic-value {
    @apply text-3xl;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .statistic-item {
    @apply border border-gray-300 dark:border-gray-600 rounded-lg p-4;
  }
  
  .statistic-error {
    @apply border border-red-600 rounded p-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .statistics-display * {
    @apply transition-none;
  }
  
  .loading-spinner svg {
    @apply animate-none;
  }
}
</style>