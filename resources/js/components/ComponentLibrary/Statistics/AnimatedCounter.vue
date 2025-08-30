<template>
  <div 
    ref="counterRef"
    :class="[
      'animated-counter',
      `size-${size}`,
      `theme-${theme}`,
      { 'has-icon': !!icon, 'has-trend': !!trend }
    ]"
    :aria-label="`${label}: ${displayValue}`"
    role="img"
  >
    <!-- Icon -->
    <div v-if="icon" class="counter-icon">
      <component :is="iconComponent" class="icon" />
    </div>

    <!-- Main counter display -->
    <div class="counter-content">
      <!-- Value -->
      <div class="counter-value-container">
        <span 
          ref="valueRef"
          class="counter-value"
          :aria-label="`Current value: ${displayValue}`"
        >
          {{ animatedDisplayValue }}
        </span>
        
        <!-- Trend indicator -->
        <div 
          v-if="trend" 
          :class="[
            'trend-indicator',
            `trend-${trend.direction}`
          ]"
          :aria-label="`Trend: ${trend.direction} by ${trend.value}${trend.label ? ' ' + trend.label : ''}`"
        >
          <component :is="trendIcon" class="trend-icon" />
          <span class="trend-value">{{ trend.value }}{{ trend.label }}</span>
        </div>
      </div>

      <!-- Label -->
      <div class="counter-label">{{ label }}</div>
      
      <!-- Description -->
      <div v-if="description" class="counter-description">{{ description }}</div>
    </div>

    <!-- Loading overlay -->
    <div 
      v-if="loading" 
      class="counter-loading"
      aria-label="Loading counter data"
    >
      <div class="loading-spinner" />
    </div>

    <!-- Error state -->
    <div 
      v-if="error" 
      class="counter-error"
      role="alert"
      :aria-label="`Error loading ${label}: ${error}`"
    >
      <component :is="errorIcon" class="error-icon" />
      <span class="error-text">{{ error }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useIntersectionObserver } from '@/composables/useIntersectionObserver'
import { useAnalytics } from '@/composables/useAnalytics'

// Icons (you can replace these with your preferred icon library)
const ChartBarIcon = 'div' // Replace with actual icon component
const TrendingUpIcon = 'div' // Replace with actual icon component  
const TrendingDownIcon = 'div' // Replace with actual icon component
const MinusIcon = 'div' // Replace with actual icon component
const ExclamationTriangleIcon = 'div' // Replace with actual icon component

export interface CounterTrend {
  direction: 'up' | 'down' | 'neutral'
  value: number | string
  label?: string
}

interface Props {
  value: number | string
  label: string
  description?: string
  prefix?: string
  suffix?: string
  format?: 'number' | 'currency' | 'percentage' | 'duration'
  decimals?: number
  size?: 'sm' | 'md' | 'lg' | 'xl'
  theme?: 'default' | 'minimal' | 'modern' | 'card'
  color?: string
  icon?: string
  trend?: CounterTrend
  animate?: boolean
  animationDuration?: number
  animationDelay?: number
  animationEasing?: string
  loading?: boolean
  error?: string | null
  respectReducedMotion?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  format: 'number',
  decimals: 0,
  size: 'md',
  theme: 'default',
  animate: true,
  animationDuration: 2000,
  animationDelay: 0,
  animationEasing: 'ease-out',
  loading: false,
  error: null,
  respectReducedMotion: true
})

const emit = defineEmits<{
  'animation-start': []
  'animation-complete': []
  'animation-update': [progress: number]
}>()

// Refs
const counterRef = ref<HTMLElement>()
const valueRef = ref<HTMLElement>()
const { trackEvent } = useAnalytics()

// Animation state
const animatedValue = ref<number>(0)
const isAnimating = ref(false)
const animationId = ref<number | null>(null)

// Intersection observer for scroll-triggered animations
const { isIntersecting } = useIntersectionObserver(counterRef, {
  threshold: 0.5,
  rootMargin: '20px'
})

// Computed properties
const numericValue = computed(() => {
  const val = typeof props.value === 'string' ? parseFloat(props.value) : props.value
  return isNaN(val) ? 0 : val
})

const displayValue = computed(() => {
  return formatValue(numericValue.value)
})

const animatedDisplayValue = computed(() => {
  return formatValue(animatedValue.value)
})

const iconComponent = computed(() => {
  // Map icon names to components - replace with your icon system
  const iconMap: Record<string, any> = {
    'chart-bar': ChartBarIcon,
    // Add more icon mappings as needed
  }
  return iconMap[props.icon || ''] || ChartBarIcon
})

const trendIcon = computed(() => {
  switch (props.trend?.direction) {
    case 'up':
      return TrendingUpIcon
    case 'down':
      return TrendingDownIcon
    case 'neutral':
    default:
      return MinusIcon
  }
})

const errorIcon = computed(() => ExclamationTriangleIcon)

const shouldAnimate = computed(() => {
  if (!props.animate) return false
  if (props.respectReducedMotion && prefersReducedMotion.value) return false
  return isIntersecting.value
})

// Check for reduced motion preference
const prefersReducedMotion = ref(false)

// Methods
const formatValue = (value: number): string => {
  const formattedNumber = formatNumber(value)
  return `${props.prefix || ''}${formattedNumber}${props.suffix || ''}`
}

const formatNumber = (value: number): string => {
  switch (props.format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals
      }).format(value)
    
    case 'percentage':
      return new Intl.NumberFormat('en-US', {
        style: 'percent',
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals
      }).format(value / 100)
    
    case 'duration':
      return formatDuration(value)
    
    case 'number':
    default:
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals
      }).format(value)
  }
}

const formatDuration = (seconds: number): string => {
  if (seconds < 60) return `${Math.round(seconds)}s`
  if (seconds < 3600) return `${Math.round(seconds / 60)}m`
  if (seconds < 86400) return `${Math.round(seconds / 3600)}h`
  return `${Math.round(seconds / 86400)}d`
}

const easeOut = (t: number): number => {
  return 1 - Math.pow(1 - t, 3)
}

const animateCounter = () => {
  if (isAnimating.value || !shouldAnimate.value) return
  
  const startValue = animatedValue.value
  const endValue = numericValue.value
  const startTime = performance.now()
  
  isAnimating.value = true
  emit('animation-start')
  
  trackEvent('counter_animation_start', {
    component: 'AnimatedCounter',
    label: props.label,
    start_value: startValue,
    end_value: endValue,
    duration: props.animationDuration
  })

  const animate = (currentTime: number) => {
    const elapsed = currentTime - startTime - props.animationDelay
    
    if (elapsed < 0) {
      animationId.value = requestAnimationFrame(animate)
      return
    }
    
    const progress = Math.min(elapsed / props.animationDuration, 1)
    const easedProgress = easeOut(progress)
    
    animatedValue.value = startValue + (endValue - startValue) * easedProgress
    
    emit('animation-update', progress)
    
    if (progress < 1) {
      animationId.value = requestAnimationFrame(animate)
    } else {
      isAnimating.value = false
      animatedValue.value = endValue
      emit('animation-complete')
      
      trackEvent('counter_animation_complete', {
        component: 'AnimatedCounter',
        label: props.label,
        final_value: endValue
      })
    }
  }
  
  animationId.value = requestAnimationFrame(animate)
}

const stopAnimation = () => {
  if (animationId.value) {
    cancelAnimationFrame(animationId.value)
    animationId.value = null
  }
  isAnimating.value = false
}

const resetAnimation = () => {
  stopAnimation()
  animatedValue.value = 0
}

// Watchers
watch(shouldAnimate, (animate) => {
  if (animate) {
    setTimeout(() => animateCounter(), 100) // Small delay to ensure visibility
  }
})

watch(() => props.value, () => {
  if (shouldAnimate.value) {
    animateCounter()
  } else {
    animatedValue.value = numericValue.value
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
  
  // Set initial value
  if (!props.animate || (props.respectReducedMotion && prefersReducedMotion.value)) {
    animatedValue.value = numericValue.value
  }
})

onUnmounted(() => {
  stopAnimation()
})

// Expose methods for parent components
defineExpose({
  animateCounter,
  resetAnimation,
  stopAnimation
})
</script>

<style scoped>
.animated-counter {
  @apply relative flex flex-col items-center text-center p-4 rounded-lg transition-all duration-300;
}

.animated-counter.size-sm {
  @apply p-3;
}

.animated-counter.size-lg {
  @apply p-6;
}

.animated-counter.size-xl {
  @apply p-8;
}

.counter-icon {
  @apply mb-3;
}

.counter-icon .icon {
  @apply w-8 h-8 text-blue-600 dark:text-blue-400;
}

.size-sm .counter-icon .icon {
  @apply w-6 h-6;
}

.size-lg .counter-icon .icon {
  @apply w-10 h-10;
}

.size-xl .counter-icon .icon {
  @apply w-12 h-12;
}

.counter-content {
  @apply flex flex-col items-center w-full;
}

.counter-value-container {
  @apply flex items-center justify-center gap-2 mb-2;
}

.counter-value {
  @apply font-bold text-gray-900 dark:text-white tabular-nums;
  font-variant-numeric: tabular-nums;
}

.size-sm .counter-value {
  @apply text-2xl;
}

.size-md .counter-value {
  @apply text-3xl;
}

.size-lg .counter-value {
  @apply text-4xl;
}

.size-xl .counter-value {
  @apply text-5xl;
}

.trend-indicator {
  @apply flex items-center gap-1 px-2 py-1 rounded-full text-sm font-medium;
}

.trend-indicator.trend-up {
  @apply bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300;
}

.trend-indicator.trend-down {
  @apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300;
}

.trend-indicator.trend-neutral {
  @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300;
}

.trend-icon {
  @apply w-4 h-4;
}

.trend-value {
  @apply tabular-nums;
  font-variant-numeric: tabular-nums;
}

.counter-label {
  @apply text-sm font-medium text-gray-600 dark:text-gray-300 mb-1;
}

.size-lg .counter-label,
.size-xl .counter-label {
  @apply text-base;
}

.counter-description {
  @apply text-xs text-gray-500 dark:text-gray-400 max-w-xs;
}

.size-lg .counter-description,
.size-xl .counter-description {
  @apply text-sm max-w-sm;
}

.counter-loading {
  @apply absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-800/80 rounded-lg;
}

.loading-spinner {
  @apply w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin;
}

.counter-error {
  @apply absolute inset-0 flex flex-col items-center justify-center bg-red-50/90 dark:bg-red-900/20 rounded-lg p-2;
}

.error-icon {
  @apply w-5 h-5 text-red-600 dark:text-red-400 mb-1;
}

.error-text {
  @apply text-xs text-red-600 dark:text-red-400 text-center;
}

/* Theme variations */
.theme-minimal {
  @apply bg-transparent p-2;
}

.theme-modern {
  @apply bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-100 dark:border-blue-800;
}

.theme-card {
  @apply bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700;
}

/* Hover effects */
.animated-counter:hover {
  @apply transform scale-105;
}

.theme-minimal:hover {
  @apply transform scale-100 bg-gray-50 dark:bg-gray-800/50;
}

/* Focus styles for accessibility */
.animated-counter:focus-within {
  @apply ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .animated-counter,
  .counter-value,
  .loading-spinner {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>