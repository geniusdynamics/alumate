<template>
  <div 
    ref="containerRef"
    class="lazy-component-container"
    :class="containerClass"
  >
    <!-- Loading state -->
    <div 
      v-if="isLoading"
      class="lazy-component-loading"
      :class="loadingClass"
    >
      <slot name="loading">
        <div class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      </slot>
    </div>

    <!-- Error state -->
    <div 
      v-else-if="isError"
      class="lazy-component-error"
      :class="errorClass"
    >
      <slot name="error">
        <div class="flex flex-col items-center justify-center py-8 text-gray-500">
          <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <p class="text-sm">Failed to load component</p>
          <button 
            @click="retry"
            class="mt-2 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
          >
            Retry
          </button>
        </div>
      </slot>
    </div>

    <!-- Loaded component -->
    <component 
      v-else-if="component"
      :is="component"
      v-bind="componentProps"
      @[eventName]="$emit(eventName, $event)"
      v-for="eventName in componentEvents"
      :key="eventName"
    />

    <!-- Placeholder when not yet triggered -->
    <div 
      v-else
      class="lazy-component-placeholder"
      :class="placeholderClass"
    >
      <slot name="placeholder" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useLazyComponent } from '@/composables/useLazyLoading'

interface Props {
  componentLoader: () => Promise<any>
  componentProps?: Record<string, any>
  componentEvents?: string[]
  containerClass?: string
  loadingClass?: string
  errorClass?: string
  placeholderClass?: string
  rootMargin?: string
  threshold?: number
  immediate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  componentProps: () => ({}),
  componentEvents: () => [],
  rootMargin: '100px',
  threshold: 0.1,
  immediate: false
})

const emit = defineEmits<{
  [key: string]: any
}>()

const {
  containerRef,
  component,
  isLoading,
  isError
} = useLazyComponent(props.componentLoader, {
  rootMargin: props.rootMargin,
  threshold: props.threshold,
  once: true
})

// Load immediately if requested
if (props.immediate) {
  props.componentLoader().then(loadedComponent => {
    component.value = loadedComponent.default || loadedComponent
  }).catch(() => {
    isError.value = true
  })
}

const retry = async () => {
  isError.value = false
  isLoading.value = true
  
  try {
    const loadedComponent = await props.componentLoader()
    component.value = loadedComponent.default || loadedComponent
  } catch (error) {
    console.error('Error retrying component load:', error)
    isError.value = true
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.lazy-component-container {
  @apply w-full;
}

.lazy-component-loading,
.lazy-component-error,
.lazy-component-placeholder {
  @apply w-full;
}
</style>