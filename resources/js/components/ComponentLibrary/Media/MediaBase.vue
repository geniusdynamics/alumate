<template>
  <div
    :class="containerClasses"
    role="region"
    :aria-label="ariaLabel"
  >
    <!-- Component Header -->
    <div v-if="config.title || config.description" class="mb-6">
      <h2 
        v-if="config.title"
        :class="titleClasses"
        :id="titleId"
      >
        {{ config.title }}
      </h2>
      <p 
        v-if="config.description"
        class="text-gray-600 dark:text-gray-400 mt-2"
        :aria-describedby="titleId"
      >
        {{ config.description }}
      </p>
    </div>

    <!-- Media Content -->
    <div :class="contentClasses">
      <slot />
    </div>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 dark:bg-gray-900 dark:bg-opacity-90"
    >
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ loadingMessage }}</p>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="hasError"
      class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-800 p-4"
    >
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ errorMessage }}</p>
        <button
          @click="retryLoad"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
          Try Again
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, provide } from 'vue'
import type { MediaComponentConfig } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'

interface Props {
  config: MediaComponentConfig
  isLoading?: boolean
  hasError?: boolean
  errorMessage?: string
  loadingMessage?: string
  trackAnalytics?: boolean
  analyticsId?: string
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  hasError: false,
  errorMessage: 'Unable to load media content',
  loadingMessage: 'Loading...',
  trackAnalytics: true
})

const emit = defineEmits<{
  retry: []
  error: [error: Error]
  loaded: []
}>()

// Generate unique IDs for accessibility
const titleId = `media-title-${Math.random().toString(36).substr(2, 9)}`

// Analytics
const { trackEvent } = useAnalytics()

// Computed properties
const ariaLabel = computed(() => {
  return props.config.accessibility?.ariaLabel || 
         props.config.title || 
         'Media component'
})

const containerClasses = computed(() => [
  'media-component-base',
  'relative',
  {
    // Theme classes
    'bg-white dark:bg-gray-900': props.config.theme === 'default',
    'bg-gray-50 dark:bg-gray-800': props.config.theme === 'minimal',
    'bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900': props.config.theme === 'modern',
    'border border-gray-200 dark:border-gray-700 rounded-lg': props.config.theme === 'card',
    
    // Spacing
    'p-4': props.config.spacing === 'compact',
    'p-6': props.config.spacing === 'default',
    'p-8': props.config.spacing === 'spacious',
    
    // Responsive behavior
    'w-full': true,
    'max-w-none': props.config.layout === 'full-width',
    'max-w-4xl mx-auto': props.config.layout === 'contained',
    'max-w-6xl mx-auto': props.config.layout === 'wide',
  }
])

const titleClasses = computed(() => [
  'font-bold text-gray-900 dark:text-white',
  {
    'text-2xl': props.config.titleSize === 'lg',
    'text-xl': props.config.titleSize === 'md',
    'text-lg': props.config.titleSize === 'sm',
    'text-center': props.config.textAlignment === 'center',
    'text-right': props.config.textAlignment === 'right',
    'text-left': props.config.textAlignment === 'left',
  }
])

const contentClasses = computed(() => [
  'media-content',
  'relative',
  {
    // Layout classes
    'grid gap-6': props.config.layout === 'grid',
    'flex flex-col space-y-6': props.config.layout === 'column',
    'flex flex-row space-x-6': props.config.layout === 'row',
    
    // Grid columns for gallery layouts
    'grid-cols-1 md:grid-cols-2 lg:grid-cols-3': props.config.gridColumns?.desktop === 3,
    'grid-cols-1 md:grid-cols-2 lg:grid-cols-4': props.config.gridColumns?.desktop === 4,
    'grid-cols-1 md:grid-cols-2': props.config.gridColumns?.desktop === 2,
    
    // Responsive adjustments
    'overflow-hidden': true,
  }
])

// Methods
const retryLoad = () => {
  if (props.trackAnalytics) {
    trackEvent('media_retry', {
      component_id: props.analyticsId,
      error_message: props.errorMessage
    })
  }
  
  emit('retry')
}

// Provide context to child components
provide('mediaConfig', props.config)
provide('trackAnalytics', props.trackAnalytics)
provide('analyticsId', props.analyticsId)
</script>

<style scoped>
.media-component-base {
  container-type: inline-size;
}

/* Responsive grid adjustments */
@container (max-width: 768px) {
  .media-content.grid {
    grid-template-columns: 1fr;
  }
}

@container (min-width: 768px) and (max-width: 1024px) {
  .media-content.grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .media-component-base {
    border: 1px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .media-component-base *,
  .media-component-base *::before,
  .media-component-base *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus management */
.media-component-base:focus-within {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* Print styles */
@media print {
  .media-component-base {
    break-inside: avoid;
    page-break-inside: avoid;
  }
}
</style>