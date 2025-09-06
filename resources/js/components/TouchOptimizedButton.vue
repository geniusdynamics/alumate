<!-- ABOUTME: Touch-optimized button component for mobile-friendly interactions -->
<!-- ABOUTME: Provides enhanced touch feedback, loading states, and accessibility features -->
<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    :aria-label="ariaLabel"
    :aria-busy="loading"
    @click="handleClick"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
    @touchcancel="handleTouchCancel"
    ref="buttonRef"
  >
    <!-- Loading State -->
    <div v-if="loading" class="button-loading">
      <svg class="loading-spinner" viewBox="0 0 24 24">
        <circle 
          cx="12" 
          cy="12" 
          r="10" 
          stroke="currentColor" 
          stroke-width="2" 
          fill="none" 
          stroke-linecap="round" 
          stroke-dasharray="31.416" 
          stroke-dashoffset="31.416"
        >
          <animate 
            attributeName="stroke-dasharray" 
            dur="2s" 
            values="0 31.416;15.708 15.708;0 31.416;0 31.416" 
            repeatCount="indefinite"
          />
          <animate 
            attributeName="stroke-dashoffset" 
            dur="2s" 
            values="0;-15.708;-31.416;-31.416" 
            repeatCount="indefinite"
          />
        </circle>
      </svg>
      <span class="loading-text">{{ loadingText }}</span>
    </div>

    <!-- Button Content -->
    <div v-else class="button-content">
      <!-- Icon Slot -->
      <span v-if="$slots.default" class="button-icon">
        <slot />
      </span>
      
      <!-- Text Content -->
      <span v-if="$slots.default" class="button-text">
        <slot />
      </span>
    </div>

    <!-- Touch Feedback Overlay -->
    <div 
      v-if="showTouchFeedback"
      class="touch-feedback-overlay"
      :class="touchFeedbackClass"
    ></div>
  </button>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  type?: 'button' | 'submit' | 'reset'
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline'
  size?: 'sm' | 'md' | 'lg' | 'xl'
  disabled?: boolean
  loading?: boolean
  loadingText?: string
  ariaLabel?: string
  fullWidth?: boolean
  rounded?: boolean
}

interface Emits {
  click: [event: MouseEvent]
  touchStart: [event: TouchEvent]
  touchEnd: [event: TouchEvent]
}

const props = withDefaults(defineProps<Props>(), {
  type: 'button',
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  loadingText: 'Loading...',
  fullWidth: false,
  rounded: false
})

const emit = defineEmits<Emits>()

// Refs
const buttonRef = ref<HTMLButtonElement>()
const showTouchFeedback = ref(false)
const touchFeedbackClass = ref('')

// Computed
const buttonClasses = computed(() => [
  'touch-optimized-button',
  `button-${props.variant}`,
  `button-${props.size}`,
  {
    'button-disabled': props.disabled,
    'button-loading': props.loading,
    'button-full-width': props.fullWidth,
    'button-rounded': props.rounded,
  }
])

// Methods
const handleClick = (event: MouseEvent) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}

const handleTouchStart = (event: TouchEvent) => {
  if (!props.disabled && !props.loading) {
    showTouchFeedback.value = true
    touchFeedbackClass.value = 'touch-active'
    emit('touchStart', event)
  }
}

const handleTouchEnd = (event: TouchEvent) => {
  setTimeout(() => {
    showTouchFeedback.value = false
    touchFeedbackClass.value = ''
  }, 150)
  
  if (!props.disabled && !props.loading) {
    emit('touchEnd', event)
  }
}

const handleTouchCancel = () => {
  showTouchFeedback.value = false
  touchFeedbackClass.value = ''
}
</script>

<style scoped>
.touch-optimized-button {
  @apply relative inline-flex items-center justify-center;
  @apply font-medium text-center;
  @apply border border-transparent rounded-lg;
  @apply transition-all duration-200 ease-in-out;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2;
  @apply select-none;
  
  /* Touch-friendly minimum dimensions */
  min-height: 44px;
  min-width: 44px;
  
  /* Prevent text selection and zoom on double-tap */
  -webkit-user-select: none;
  -webkit-touch-callout: none;
  -webkit-tap-highlight-color: transparent;
}

/* Size Variants */
.button-sm {
  @apply px-3 py-2 text-sm;
  min-height: 36px;
}

.button-md {
  @apply px-4 py-2.5 text-base;
  min-height: 44px;
}

.button-lg {
  @apply px-6 py-3 text-lg;
  min-height: 52px;
}

.button-xl {
  @apply px-8 py-4 text-xl;
  min-height: 60px;
}

/* Color Variants */
.button-primary {
  @apply bg-blue-600 text-white border-blue-600;
  @apply hover:bg-blue-700 hover:border-blue-700;
  @apply focus:ring-blue-500;
  @apply active:bg-blue-800;
}

.button-secondary {
  @apply bg-gray-600 text-white border-gray-600;
  @apply hover:bg-gray-700 hover:border-gray-700;
  @apply focus:ring-gray-500;
  @apply active:bg-gray-800;
}

.button-danger {
  @apply bg-red-600 text-white border-red-600;
  @apply hover:bg-red-700 hover:border-red-700;
  @apply focus:ring-red-500;
  @apply active:bg-red-800;
}

.button-ghost {
  @apply bg-transparent text-gray-700 dark:text-gray-300 border-transparent;
  @apply hover:bg-gray-100 dark:hover:bg-gray-800;
  @apply focus:ring-gray-500;
  @apply active:bg-gray-200 dark:active:bg-gray-700;
}

.button-outline {
  @apply bg-transparent text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400;
  @apply hover:bg-blue-50 dark:hover:bg-blue-900/20;
  @apply focus:ring-blue-500;
  @apply active:bg-blue-100 dark:active:bg-blue-900/30;
}

/* State Variants */
.button-disabled {
  @apply opacity-50 cursor-not-allowed;
  @apply pointer-events-none;
}

.button-loading {
  @apply cursor-wait;
  @apply pointer-events-none;
}

.button-full-width {
  @apply w-full;
}

.button-rounded {
  @apply rounded-full;
}

/* Button Content */
.button-content {
  @apply flex items-center justify-center gap-2;
}

.button-icon {
  @apply flex-shrink-0;
}

.button-text {
  @apply truncate;
}

/* Loading State */
.button-loading {
  @apply flex items-center justify-center gap-2;
}

.loading-spinner {
  @apply w-5 h-5;
  animation: spin 1s linear infinite;
}

.loading-text {
  @apply text-sm;
}

/* Touch Feedback */
.touch-feedback-overlay {
  @apply absolute inset-0 rounded-lg pointer-events-none;
  @apply transition-all duration-150 ease-out;
}

.touch-active {
  @apply bg-white bg-opacity-20;
}

/* Dark Mode Adjustments */
.dark .button-primary {
  @apply bg-blue-500 border-blue-500;
  @apply hover:bg-blue-600 hover:border-blue-600;
  @apply active:bg-blue-700;
}

.dark .button-secondary {
  @apply bg-gray-500 border-gray-500;
  @apply hover:bg-gray-600 hover:border-gray-600;
  @apply active:bg-gray-700;
}

.dark .button-danger {
  @apply bg-red-500 border-red-500;
  @apply hover:bg-red-600 hover:border-red-600;
  @apply active:bg-red-700;
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
  .touch-optimized-button {
    min-height: 48px; /* Larger touch targets on mobile */
  }
  
  .button-sm {
    min-height: 40px;
  }
  
  .button-lg {
    min-height: 56px;
  }
  
  .button-xl {
    min-height: 64px;
  }
}

/* Focus Visible for Keyboard Navigation */
.touch-optimized-button:focus-visible {
  @apply outline-2 outline-offset-2 outline-blue-500;
}

/* Animations */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Hover Effects (Desktop Only) */
@media (hover: hover) and (pointer: fine) {
  .touch-optimized-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  
  .touch-optimized-button:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
}

/* Reduce Motion for Accessibility */
@media (prefers-reduced-motion: reduce) {
  .touch-optimized-button {
    transition: none;
  }
  
  .loading-spinner {
    animation: none;
  }
  
  .touch-feedback-overlay {
    transition: none;
  }
}
</style>