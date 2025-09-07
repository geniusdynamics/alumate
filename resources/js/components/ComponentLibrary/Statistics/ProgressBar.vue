<template>
  <div 
    ref="progressRef"
    :class="[
      'progress-bar-container',
      `size-${size}`,
      `theme-${theme}`,
      { 'has-label': showLabel, 'has-value': showValue }
    ]"
    :aria-label="`${label}: ${Math.round(animatedProgress)}% complete`"
    role="progressbar"
    :aria-valuenow="Math.round(animatedProgress)"
    :aria-valuemin="0"
    :aria-valuemax="100"
    :aria-valuetext="`${Math.round(animatedProgress)}% ${label}`"
  >
    <!-- Header with label and value -->
    <div v-if="showLabel || showValue" class="progress-header">
      <div v-if="showLabel" class="progress-label">{{ label }}</div>
      <div v-if="showValue" class="progress-value">
        <span class="current-value">{{ formatValue(animatedProgress) }}</span>
        <span v-if="showTarget && target" class="target-value">/ {{ formatValue(target) }}</span>
      </div>
    </div>

    <!-- Progress bar track -->
    <div 
      :class="[
        'progress-track',
        { 'has-segments': segments && segments.length > 0 }
      ]"
    >
      <!-- Segments (if defined) -->
      <div 
        v-if="segments && segments.length > 0"
        class="progress-segments"
      >
        <div
          v-for="(segment, index) in segments"
          :key="index"
          :class="[
            'progress-segment',
            { 'active': animatedProgress >= segment.threshold }
          ]"
          :style="{
            width: `${segment.width}%`,
            backgroundColor: segment.color || getSegmentColor(index)
          }"
          :aria-label="`Segment ${index + 1}: ${segment.label || ''}`"
        />
      </div>

      <!-- Main progress fill -->
      <div 
        :class="[
          'progress-fill',
          `color-${color}`,
          { 'animated': animate && !prefersReducedMotion }
        ]"
        :style="{
          width: `${Math.min(animatedProgress, 100)}%`,
          backgroundColor: customColor || undefined,
          transition: animate && !prefersReducedMotion ? `width ${animationDuration}ms ${animationEasing}` : 'none'
        }"
      >
        <!-- Gradient overlay for modern theme -->
        <div 
          v-if="theme === 'modern'" 
          class="progress-gradient"
        />
        
        <!-- Animated stripes for loading state -->
        <div 
          v-if="loading || animated" 
          class="progress-stripes"
        />
      </div>

      <!-- Milestone markers -->
      <div 
        v-if="milestones && milestones.length > 0"
        class="progress-milestones"
      >
        <div
          v-for="(milestone, index) in milestones"
          :key="index"
          :class="[
            'milestone-marker',
            { 
              'reached': animatedProgress >= milestone.value,
              'current': Math.abs(animatedProgress - milestone.value) < 1
            }
          ]"
          :style="{ left: `${milestone.value}%` }"
          :title="milestone.label"
          :aria-label="`Milestone: ${milestone.label} at ${milestone.value}%`"
        >
          <div class="milestone-dot" />
          <div v-if="milestone.showLabel" class="milestone-label">
            {{ milestone.label }}
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="description" class="progress-description">{{ description }}</div>

    <!-- Loading overlay -->
    <div 
      v-if="loading" 
      class="progress-loading"
      aria-label="Loading progress data"
    >
      <div class="loading-spinner" />
    </div>

    <!-- Error state -->
    <div 
      v-if="error" 
      class="progress-error"
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

// Icons
const ExclamationTriangleIcon = 'div' // Replace with actual icon component

export interface ProgressSegment {
  threshold: number
  width: number
  color?: string
  label?: string
}

export interface ProgressMilestone {
  value: number
  label: string
  showLabel?: boolean
}

interface Props {
  value: number
  target?: number
  label: string
  description?: string
  size?: 'sm' | 'md' | 'lg'
  theme?: 'default' | 'minimal' | 'modern' | 'rounded'
  color?: 'blue' | 'green' | 'red' | 'yellow' | 'purple' | 'indigo'
  customColor?: string
  showLabel?: boolean
  showValue?: boolean
  showTarget?: boolean
  format?: 'percentage' | 'number' | 'currency'
  animate?: boolean
  animated?: boolean // For striped animation
  animationDuration?: number
  animationDelay?: number
  animationEasing?: string
  segments?: ProgressSegment[]
  milestones?: ProgressMilestone[]
  loading?: boolean
  error?: string | null
  respectReducedMotion?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  theme: 'default',
  color: 'blue',
  showLabel: true,
  showValue: true,
  showTarget: false,
  format: 'percentage',
  animate: true,
  animated: false,
  animationDuration: 1500,
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
  'milestone-reached': [milestone: ProgressMilestone]
}>()

// Refs
const progressRef = ref<HTMLElement>()
const { trackEvent } = useAnalytics()

// Animation state
const animatedProgress = ref<number>(0)
const isAnimating = ref(false)
const animationId = ref<number | null>(null)
const reachedMilestones = ref<Set<number>>(new Set())

// Intersection observer for scroll-triggered animations
const { isIntersecting } = useIntersectionObserver(progressRef, {
  threshold: 0.3,
  rootMargin: '50px'
})

// Check for reduced motion preference
const prefersReducedMotion = ref(false)

// Computed properties
const normalizedValue = computed(() => {
  const val = Math.max(0, Math.min(100, props.value))
  return isNaN(val) ? 0 : val
})

const shouldAnimate = computed(() => {
  if (!props.animate) return false
  if (props.respectReducedMotion && prefersReducedMotion.value) return false
  return isIntersecting.value
})

const errorIcon = computed(() => ExclamationTriangleIcon)

// Methods
const formatValue = (value: number): string => {
  switch (props.format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value)
    
    case 'number':
      return new Intl.NumberFormat('en-US').format(Math.round(value))
    
    case 'percentage':
    default:
      return `${Math.round(value)}%`
  }
}

const getSegmentColor = (index: number): string => {
  const colors = [
    '#ef4444', // red
    '#f97316', // orange
    '#eab308', // yellow
    '#22c55e', // green
    '#3b82f6', // blue
    '#8b5cf6', // purple
  ]
  return colors[index % colors.length]
}

const easeOut = (t: number): number => {
  return 1 - Math.pow(1 - t, 3)
}

const animateProgress = () => {
  if (isAnimating.value || !shouldAnimate.value) return
  
  const startValue = animatedProgress.value
  const endValue = normalizedValue.value
  const startTime = performance.now()
  
  isAnimating.value = true
  emit('animation-start')
  
  trackEvent('progress_animation_start', {
    component: 'ProgressBar',
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
    
    const currentValue = startValue + (endValue - startValue) * easedProgress
    animatedProgress.value = currentValue
    
    // Check for milestone achievements
    checkMilestones(currentValue)
    
    emit('animation-update', progress)
    
    if (progress < 1) {
      animationId.value = requestAnimationFrame(animate)
    } else {
      isAnimating.value = false
      animatedProgress.value = endValue
      emit('animation-complete')
      
      trackEvent('progress_animation_complete', {
        component: 'ProgressBar',
        label: props.label,
        final_value: endValue
      })
    }
  }
  
  animationId.value = requestAnimationFrame(animate)
}

const checkMilestones = (currentValue: number) => {
  if (!props.milestones) return
  
  props.milestones.forEach((milestone) => {
    if (currentValue >= milestone.value && !reachedMilestones.value.has(milestone.value)) {
      reachedMilestones.value.add(milestone.value)
      emit('milestone-reached', milestone)
      
      trackEvent('progress_milestone_reached', {
        component: 'ProgressBar',
        label: props.label,
        milestone_label: milestone.label,
        milestone_value: milestone.value,
        current_value: currentValue
      })
    }
  })
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
  animatedProgress.value = 0
  reachedMilestones.value.clear()
}

// Watchers
watch(shouldAnimate, (animate) => {
  if (animate) {
    setTimeout(() => animateProgress(), 100)
  }
})

watch(() => props.value, () => {
  if (shouldAnimate.value) {
    animateProgress()
  } else {
    animatedProgress.value = normalizedValue.value
    checkMilestones(normalizedValue.value)
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
    animatedProgress.value = normalizedValue.value
    checkMilestones(normalizedValue.value)
  }
})

onUnmounted(() => {
  stopAnimation()
})

// Expose methods for parent components
defineExpose({
  animateProgress,
  resetAnimation,
  stopAnimation
})
</script>

<style scoped>
.progress-bar-container {
  @apply relative w-full;
}

.progress-header {
  @apply flex justify-between items-center mb-2;
}

.progress-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.size-lg .progress-label {
  @apply text-base;
}

.progress-value {
  @apply text-sm font-semibold text-gray-900 dark:text-white tabular-nums;
  font-variant-numeric: tabular-nums;
}

.size-lg .progress-value {
  @apply text-base;
}

.target-value {
  @apply text-gray-500 dark:text-gray-400;
}

.progress-track {
  @apply relative w-full bg-gray-200 dark:bg-gray-700 overflow-hidden;
}

.size-sm .progress-track {
  @apply h-2 rounded-full;
}

.size-md .progress-track {
  @apply h-3 rounded-full;
}

.size-lg .progress-track {
  @apply h-4 rounded-lg;
}

.theme-minimal .progress-track {
  @apply bg-gray-100 dark:bg-gray-800;
}

.theme-modern .progress-track {
  @apply bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 shadow-inner;
}

.theme-rounded .progress-track {
  @apply rounded-full;
}

.progress-segments {
  @apply absolute inset-0 flex;
}

.progress-segment {
  @apply h-full transition-opacity duration-300;
}

.progress-segment:not(.active) {
  @apply opacity-30;
}

.progress-fill {
  @apply relative h-full transition-all duration-300;
}

.progress-fill.color-blue {
  @apply bg-blue-600;
}

.progress-fill.color-green {
  @apply bg-green-600;
}

.progress-fill.color-red {
  @apply bg-red-600;
}

.progress-fill.color-yellow {
  @apply bg-yellow-600;
}

.progress-fill.color-purple {
  @apply bg-purple-600;
}

.progress-fill.color-indigo {
  @apply bg-indigo-600;
}

.progress-gradient {
  @apply absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent;
}

.progress-stripes {
  @apply absolute inset-0 bg-gradient-to-r;
  background-image: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 10px,
    rgba(255, 255, 255, 0.1) 10px,
    rgba(255, 255, 255, 0.1) 20px
  );
  animation: progress-stripes 1s linear infinite;
}

@keyframes progress-stripes {
  0% {
    background-position: 0 0;
  }
  100% {
    background-position: 40px 0;
  }
}

.progress-milestones {
  @apply absolute inset-0 pointer-events-none;
}

.milestone-marker {
  @apply absolute top-0 transform -translate-x-1/2 pointer-events-auto;
  height: 100%;
}

.milestone-dot {
  @apply absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2;
  @apply w-3 h-3 bg-white border-2 border-gray-400 rounded-full transition-colors duration-200;
}

.milestone-marker.reached .milestone-dot {
  @apply border-green-500 bg-green-500;
}

.milestone-marker.current .milestone-dot {
  @apply border-blue-500 bg-blue-500 scale-125;
}

.milestone-label {
  @apply absolute top-full left-1/2 transform -translate-x-1/2 mt-1;
  @apply text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap;
}

.progress-description {
  @apply text-xs text-gray-500 dark:text-gray-400 mt-2;
}

.size-lg .progress-description {
  @apply text-sm;
}

.progress-loading {
  @apply absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-800/80 rounded-lg;
}

.loading-spinner {
  @apply w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin;
}

.progress-error {
  @apply absolute inset-0 flex items-center justify-center bg-red-50/90 dark:bg-red-900/20 rounded-lg p-2;
}

.error-icon {
  @apply w-4 h-4 text-red-600 dark:text-red-400 mr-2;
}

.error-text {
  @apply text-xs text-red-600 dark:text-red-400;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .progress-fill,
  .progress-segment,
  .milestone-dot,
  .loading-spinner,
  .progress-stripes {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>