<template>
  <div
    :class="skeletonClasses"
    role="status"
    aria-label="Loading component"
  >
    <!-- Preview Skeleton -->
    <div class="component-skeleton__preview">
      <div 
        :class="previewSkeletonClasses"
      >
        <div class="animate-pulse bg-gray-200 dark:bg-gray-700 w-full h-full rounded-lg"></div>
      </div>
    </div>
    
    <!-- Content Skeleton -->
    <div class="component-skeleton__content">
      <div class="space-y-2">
        <!-- Title Skeleton -->
        <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-4 rounded w-3/4"></div>
        
        <!-- Description Skeleton -->
        <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 rounded w-full"></div>
        <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 rounded w-2/3"></div>
        
        <!-- Metadata Skeleton -->
        <div class="flex items-center space-x-3 mt-3">
          <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 rounded w-12"></div>
          <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 rounded w-8"></div>
          <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 rounded w-16"></div>
        </div>
      </div>
    </div>
    
    <!-- Actions Skeleton (List View Only) -->
    <div 
      v-if="viewMode === 'list'"
      class="component-skeleton__actions"
    >
      <div class="flex items-center space-x-2">
        <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-7 rounded w-16"></div>
        <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-7 rounded w-12"></div>
      </div>
    </div>
    
    <!-- Favorite Button Skeleton -->
    <div class="absolute top-4 right-4">
      <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-6 w-6 rounded-full"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  viewMode: 'grid' | 'list'
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'grid'
})

// Computed properties
const skeletonClasses = computed(() => [
  'component-skeleton relative',
  'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700',
  {
    'p-4': props.viewMode === 'grid',
    'p-4 flex items-center space-x-4': props.viewMode === 'list'
  }
])

const previewSkeletonClasses = computed(() => [
  {
    'aspect-video mb-3': props.viewMode === 'grid',
    'w-24 h-16 flex-shrink-0': props.viewMode === 'list'
  }
])
</script>

<style scoped>
.component-skeleton {
  @apply animate-pulse;
}

.component-skeleton__preview {
  @apply mb-3;
}

.component-skeleton__content {
  @apply flex-1;
}

.component-skeleton__actions {
  @apply ml-4 flex-shrink-0;
}

/* List view specific styles */
.component-skeleton:has(.component-skeleton__actions) .component-skeleton__preview {
  @apply mb-0;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .component-skeleton,
  .component-skeleton *,
  .animate-pulse {
    animation: none !important;
  }
  
  .component-skeleton .animate-pulse {
    @apply bg-gray-300 dark:bg-gray-600;
  }
}
</style>