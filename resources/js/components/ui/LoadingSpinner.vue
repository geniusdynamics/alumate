<template>
  <div class="loading-spinner" :class="sizeClass" :aria-label="ariaLabel">
    <div class="spinner-circle" :class="colorClass"></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type SpinnerSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
type SpinnerColor = 'primary' | 'secondary' | 'white' | 'gray'

interface Props {
  size?: SpinnerSize
  color?: SpinnerColor
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  color: 'primary',
  ariaLabel: 'Loading...'
})

// Computed properties
const sizeClass = computed(() => {
  const sizes = {
    xs: 'w-3 h-3',
    sm: 'w-4 h-4',
    md: 'w-6 h-6',
    lg: 'w-8 h-8',
    xl: 'w-12 h-12'
  }
  return sizes[props.size]
})

const colorClass = computed(() => {
  const colors = {
    primary: 'border-blue-600',
    secondary: 'border-gray-600',
    white: 'border-white',
    gray: 'border-gray-400'
  }
  return colors[props.color]
})
</script>

<style scoped>
.loading-spinner {
  @apply inline-block;
}

.spinner-circle {
  @apply w-full h-full border-2 border-solid border-transparent rounded-full animate-spin;
  border-top-color: currentColor;
  border-right-color: currentColor;
}

.border-blue-600 {
  border-top-color: #2563eb;
  border-right-color: #2563eb;
}

.border-gray-600 {
  border-top-color: #4b5563;
  border-right-color: #4b5563;
}

.border-white {
  border-top-color: #ffffff;
  border-right-color: #ffffff;
}

.border-gray-400 {
  border-top-color: #9ca3af;
  border-right-color: #9ca3af;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>