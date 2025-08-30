<template>
  <div 
    ref="chartRef"
    :class="[
      'comparison-chart',
      `type-${type}`,
      `theme-${theme}`,
      `size-${size}`
    ]"
    :aria-label="`${title}: Comparison chart showing ${data.length} items`"
    role="img"
  >
    <!-- Chart header -->
    <div v-if="title || description" class="chart-header">
      <h3 v-if="title" class="chart-title">{{ title }}</h3>
      <p v-if="description" class="chart-description">{{ description }}</p>
    </div>

    <!-- Chart legend -->
    <div 
      v-if="showLegend && legend.length > 0" 
      class="chart-legend"
      role="list"
      aria-label="Chart legend"
    >
      <div
        v-for="(item, index) in legend"
        :key="index"
        class="legend-item"
        role="listitem"
      >
        <div 
          class="legend-color"
          :style="{ backgroundColor: item.color }"
          :aria-label="`Color indicator for ${item.label}`"
        />
        <span class="legend-label">{{ item.label }}</span>
      </div>
    </div>

    <!-- Chart container -->
    <div class="chart-container">
      <!-- Bar chart -->
      <div 
        v-if="type === 'bar'" 
        class="bar-chart"
        role="list"
        aria-label="Bar chart data"
      >
        <div
          v-for="(item, index) in processedData"
          :key="item.id || index"
          class="bar-item"
          role="listitem"
          :aria-label="`${item.label}: ${formatValue(item.value)}`"
        >
          <div class="bar-label">{{ item.label }}</div>
          <div class="bar-container">
            <div 
              class="bar-fill"
              :style="{
                width: `${item.percentage}%`,
                backgroundColor: item.color,
                animationDelay: animate ? `${index * animationStagger}ms` : '0ms'
              }"
              :class="{ 'animate-bar': animate && shouldAnimate }"
            />
            <div class="bar-value">{{ formatValue(item.value) }}</div>
          </div>
        </div>
      </div>

      <!-- Before/After comparison -->
      <div 
        v-else-if="type === 'before-after'" 
        class="before-after-chart"
        role="list"
        aria-label="Before and after comparison"
      >
        <div
          v-for="(item, index) in processedData"
          :key="item.id || index"
          class="comparison-item"
          role="listitem"
        >
          <div class="comparison-label">{{ item.label }}</div>
          <div class="comparison-bars">
            <!-- Before bar -->
            <div class="comparison-bar before-bar">
              <div class="bar-label">Before</div>
              <div class="bar-container">
                <div 
                  class="bar-fill before"
                  :style="{
                    width: `${item.beforePercentage}%`,
                    animationDelay: animate ? `${index * animationStagger}ms` : '0ms'
                  }"
                  :class="{ 'animate-bar': animate && shouldAnimate }"
                />
                <div class="bar-value">{{ formatValue(item.beforeValue) }}</div>
              </div>
            </div>
            
            <!-- After bar -->
            <div class="comparison-bar after-bar">
              <div class="bar-label">After</div>
              <div class="bar-container">
                <div 
                  class="bar-fill after"
                  :style="{
                    width: `${item.afterPercentage}%`,
                    animationDelay: animate ? `${(index * animationStagger) + 200}ms` : '0ms'
                  }"
                  :class="{ 'animate-bar': animate && shouldAnimate }"
                />
                <div class="bar-value">{{ formatValue(item.afterValue) }}</div>
              </div>
            </div>

            <!-- Improvement indicator -->
            <div 
              v-if="item.improvement !== undefined"
              :class="[
                'improvement-indicator',
                item.improvement > 0 ? 'positive' : item.improvement < 0 ? 'negative' : 'neutral'
              ]"
            >
              <component :is="getImprovementIcon(item.improvement)" class="improvement-icon" />
              <span class="improvement-value">
                {{ item.improvement > 0 ? '+' : '' }}{{ Math.round(item.improvement) }}%
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Competitive comparison -->
      <div 
        v-else-if="type === 'competitive'" 
        class="competitive-chart"
        role="list"
        aria-label="Competitive comparison chart"
      >
        <div
          v-for="(item, index) in processedData"
          :key="item.id || index"
          :class="[
            'competitive-item',
            { 'highlighted': item.highlighted }
          ]"
          role="listitem"
          :aria-label="`${item.label}: ${formatValue(item.value)} ${item.highlighted ? '(highlighted)' : ''}`"
        >
          <div class="competitive-label">
            {{ item.label }}
            <span v-if="item.highlighted" class="highlight-badge">You</span>
          </div>
          <div class="competitive-bar-container">
            <div 
              class="competitive-bar"
              :style="{
                width: `${item.percentage}%`,
                backgroundColor: item.color,
                animationDelay: animate ? `${index * animationStagger}ms` : '0ms'
              }"
              :class="{ 'animate-bar': animate && shouldAnimate }"
            />
            <div class="competitive-value">{{ formatValue(item.value) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart footer with data source -->
    <div v-if="dataSource" class="chart-footer">
      <p class="data-source">{{ dataSource }}</p>
    </div>

    <!-- Loading overlay -->
    <div 
      v-if="loading" 
      class="chart-loading"
      aria-label="Loading chart data"
    >
      <div class="loading-spinner" />
      <p class="loading-text">Loading chart data...</p>
    </div>

    <!-- Error state -->
    <div 
      v-if="error" 
      class="chart-error"
      role="alert"
      :aria-label="`Error loading chart: ${error}`"
    >
      <component :is="errorIcon" class="error-icon" />
      <div class="error-content">
        <p class="error-title">Failed to load chart data</p>
        <p class="error-message">{{ error }}</p>
        <button 
          v-if="allowRetry"
          @click="$emit('retry')"
          class="retry-button"
          type="button"
        >
          Retry
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useIntersectionObserver } from '@/composables/useIntersectionObserver'
import { useAnalytics } from '@/composables/useAnalytics'

// Icons
const TrendingUpIcon = 'div' // Replace with actual icon component
const TrendingDownIcon = 'div' // Replace with actual icon component
const MinusIcon = 'div' // Replace with actual icon component
const ExclamationTriangleIcon = 'div' // Replace with actual icon component

export interface ChartDataItem {
  id?: string
  label: string
  value: number
  beforeValue?: number // For before/after comparison
  afterValue?: number // For before/after comparison
  color?: string
  highlighted?: boolean // For competitive comparison
  description?: string
}

export interface ChartLegendItem {
  label: string
  color: string
}

interface Props {
  data: ChartDataItem[]
  type: 'bar' | 'before-after' | 'competitive'
  title?: string
  description?: string
  size?: 'sm' | 'md' | 'lg'
  theme?: 'default' | 'minimal' | 'modern'
  format?: 'number' | 'currency' | 'percentage'
  showLegend?: boolean
  legend?: ChartLegendItem[]
  animate?: boolean
  animationDuration?: number
  animationStagger?: number
  dataSource?: string
  loading?: boolean
  error?: string | null
  allowRetry?: boolean
  respectReducedMotion?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'bar',
  size: 'md',
  theme: 'default',
  format: 'number',
  showLegend: false,
  legend: () => [],
  animate: true,
  animationDuration: 800,
  animationStagger: 100,
  loading: false,
  error: null,
  allowRetry: true,
  respectReducedMotion: true
})

const emit = defineEmits<{
  'retry': []
  'item-click': [item: ChartDataItem]
  'animation-complete': []
}>()

// Refs
const chartRef = ref<HTMLElement>()
const { trackEvent } = useAnalytics()

// Intersection observer for scroll-triggered animations
const { isIntersecting } = useIntersectionObserver(chartRef, {
  threshold: 0.3,
  rootMargin: '50px'
})

// Check for reduced motion preference
const prefersReducedMotion = ref(false)

// Computed properties
const shouldAnimate = computed(() => {
  if (!props.animate) return false
  if (props.respectReducedMotion && prefersReducedMotion.value) return false
  return isIntersecting.value
})

const processedData = computed(() => {
  if (!props.data || props.data.length === 0) return []

  const maxValue = getMaxValue()
  
  return props.data.map((item, index) => {
    const baseItem = {
      ...item,
      color: item.color || getDefaultColor(index),
      percentage: maxValue > 0 ? (item.value / maxValue) * 100 : 0
    }

    // Add before/after specific calculations
    if (props.type === 'before-after' && item.beforeValue !== undefined && item.afterValue !== undefined) {
      const maxBeforeAfter = Math.max(item.beforeValue, item.afterValue, maxValue)
      return {
        ...baseItem,
        beforePercentage: maxBeforeAfter > 0 ? (item.beforeValue / maxBeforeAfter) * 100 : 0,
        afterPercentage: maxBeforeAfter > 0 ? (item.afterValue / maxBeforeAfter) * 100 : 0,
        improvement: item.beforeValue > 0 ? ((item.afterValue - item.beforeValue) / item.beforeValue) * 100 : 0
      }
    }

    return baseItem
  })
})

const errorIcon = computed(() => ExclamationTriangleIcon)

// Methods
const getMaxValue = (): number => {
  if (!props.data || props.data.length === 0) return 0
  
  if (props.type === 'before-after') {
    return Math.max(
      ...props.data.flatMap(item => [
        item.value,
        item.beforeValue || 0,
        item.afterValue || 0
      ])
    )
  }
  
  return Math.max(...props.data.map(item => item.value))
}

const getDefaultColor = (index: number): string => {
  const colors = [
    '#3b82f6', // blue
    '#10b981', // emerald
    '#f59e0b', // amber
    '#ef4444', // red
    '#8b5cf6', // violet
    '#06b6d4', // cyan
    '#84cc16', // lime
    '#f97316', // orange
  ]
  return colors[index % colors.length]
}

const formatValue = (value: number): string => {
  switch (props.format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value)
    
    case 'percentage':
      return `${Math.round(value)}%`
    
    case 'number':
    default:
      return new Intl.NumberFormat('en-US').format(value)
  }
}

const getImprovementIcon = (improvement: number) => {
  if (improvement > 0) return TrendingUpIcon
  if (improvement < 0) return TrendingDownIcon
  return MinusIcon
}

// Watchers
watch(isIntersecting, (visible) => {
  if (visible) {
    trackEvent('chart_view', {
      component: 'ComparisonChart',
      type: props.type,
      data_points: props.data.length,
      theme: props.theme
    })
  }
})

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
})
</script>

<style scoped>
.comparison-chart {
  @apply w-full bg-white dark:bg-gray-800 rounded-lg;
}

.size-sm {
  @apply p-4;
}

.size-md {
  @apply p-6;
}

.size-lg {
  @apply p-8;
}

.theme-minimal {
  @apply bg-transparent shadow-none;
}

.theme-modern {
  @apply bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 shadow-xl;
}

.chart-header {
  @apply mb-6;
}

.chart-title {
  @apply text-xl font-bold text-gray-900 dark:text-white mb-2;
}

.size-lg .chart-title {
  @apply text-2xl;
}

.chart-description {
  @apply text-gray-600 dark:text-gray-300;
}

.chart-legend {
  @apply flex flex-wrap gap-4 mb-6 justify-center;
}

.legend-item {
  @apply flex items-center gap-2;
}

.legend-color {
  @apply w-3 h-3 rounded-full;
}

.legend-label {
  @apply text-sm text-gray-700 dark:text-gray-300;
}

.chart-container {
  @apply relative;
}

/* Bar Chart Styles */
.bar-chart {
  @apply space-y-4;
}

.bar-item {
  @apply space-y-2;
}

.bar-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.bar-container {
  @apply relative bg-gray-100 dark:bg-gray-700 rounded-full h-8 flex items-center;
}

.size-sm .bar-container {
  @apply h-6;
}

.size-lg .bar-container {
  @apply h-10;
}

.bar-fill {
  @apply h-full rounded-full transition-all duration-800 ease-out;
}

.animate-bar {
  animation: expandBar var(--animation-duration, 800ms) ease-out forwards;
  width: 0 !important;
}

@keyframes expandBar {
  to {
    width: var(--target-width) !important;
  }
}

.bar-value {
  @apply absolute right-3 text-sm font-semibold text-gray-900 dark:text-white tabular-nums;
  font-variant-numeric: tabular-nums;
}

/* Before/After Chart Styles */
.before-after-chart {
  @apply space-y-6;
}

.comparison-item {
  @apply space-y-3;
}

.comparison-label {
  @apply text-base font-semibold text-gray-900 dark:text-white;
}

.comparison-bars {
  @apply space-y-2 relative;
}

.comparison-bar {
  @apply space-y-1;
}

.comparison-bar .bar-label {
  @apply text-xs font-medium text-gray-500 dark:text-gray-400;
}

.comparison-bar .bar-container {
  @apply h-6;
}

.bar-fill.before {
  @apply bg-gray-400;
}

.bar-fill.after {
  @apply bg-green-500;
}

.improvement-indicator {
  @apply absolute right-0 top-1/2 transform -translate-y-1/2 flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium;
}

.improvement-indicator.positive {
  @apply bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300;
}

.improvement-indicator.negative {
  @apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300;
}

.improvement-indicator.neutral {
  @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300;
}

.improvement-icon {
  @apply w-3 h-3;
}

.improvement-value {
  @apply tabular-nums;
  font-variant-numeric: tabular-nums;
}

/* Competitive Chart Styles */
.competitive-chart {
  @apply space-y-3;
}

.competitive-item {
  @apply flex items-center justify-between p-3 rounded-lg transition-colors duration-200;
}

.competitive-item:hover {
  @apply bg-gray-50 dark:bg-gray-700/50;
}

.competitive-item.highlighted {
  @apply bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800;
}

.competitive-label {
  @apply flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 min-w-0 flex-1;
}

.highlight-badge {
  @apply bg-blue-600 text-white text-xs px-2 py-1 rounded-full;
}

.competitive-bar-container {
  @apply flex items-center gap-3 flex-1 max-w-md;
}

.competitive-bar {
  @apply h-4 bg-blue-600 rounded-full transition-all duration-800 ease-out;
}

.competitive-value {
  @apply text-sm font-semibold text-gray-900 dark:text-white tabular-nums min-w-max;
  font-variant-numeric: tabular-nums;
}

.chart-footer {
  @apply mt-6 pt-4 border-t border-gray-200 dark:border-gray-700;
}

.data-source {
  @apply text-xs text-gray-500 dark:text-gray-400 text-center;
}

.chart-loading {
  @apply absolute inset-0 flex flex-col items-center justify-center bg-white/90 dark:bg-gray-800/90 rounded-lg;
}

.loading-spinner {
  @apply w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mb-3;
}

.loading-text {
  @apply text-sm text-gray-600 dark:text-gray-300;
}

.chart-error {
  @apply absolute inset-0 flex items-center justify-center bg-red-50/90 dark:bg-red-900/20 rounded-lg p-6;
}

.error-icon {
  @apply w-8 h-8 text-red-600 dark:text-red-400 mb-3;
}

.error-content {
  @apply text-center;
}

.error-title {
  @apply text-sm font-semibold text-red-800 dark:text-red-300 mb-1;
}

.error-message {
  @apply text-xs text-red-600 dark:text-red-400 mb-3;
}

.retry-button {
  @apply bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded-md transition-colors duration-200;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .bar-fill,
  .competitive-bar,
  .loading-spinner,
  .animate-bar {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>