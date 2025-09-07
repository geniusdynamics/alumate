<template>
  <div
    :class="cardClasses"
    role="gridcell"
    :aria-label="cardAriaLabel"
    @dragstart="handleDragStart"
    :draggable="!comparisonMode"
  >
    <!-- Component Preview Image -->
    <div class="component-card__preview">
      <div class="relative aspect-video bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden group">
        <img
          v-if="previewImage"
          :src="previewImage"
          :alt="`Preview of ${component.name}`"
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          loading="lazy"
        />
        <div
          v-else
          class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500"
        >
          <Icon :name="getCategoryIcon(component.category)" class="h-12 w-12" />
        </div>
        
        <!-- Overlay Actions -->
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
          <div class="flex space-x-2">
            <button
              @click="$emit('preview', component)"
              class="bg-white text-gray-900 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors"
              aria-label="Preview component"
            >
              <Icon name="eye" class="h-4 w-4 mr-2" />
              Preview
            </button>
            <button
              @click="$emit('view-details', component)"
              class="bg-white text-gray-900 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors"
              aria-label="View component details"
            >
              <Icon name="information-circle" class="h-4 w-4 mr-2" />
              Details
            </button>
          </div>
        </div>

        <!-- Status Badges -->
        <div class="absolute top-3 left-3 flex space-x-2">
          <span
            v-if="recentlyUsed"
            class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full dark:bg-blue-900 dark:text-blue-300"
          >
            Recent
          </span>
          <span
            v-if="isHighRated"
            class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full dark:bg-yellow-900 dark:text-yellow-300"
          >
            Top Rated
          </span>
        </div>

        <!-- Comparison Checkbox -->
        <div
          v-if="comparisonMode"
          class="absolute top-3 right-3"
        >
          <label class="flex items-center">
            <input
              type="checkbox"
              :checked="selectedForComparison"
              :disabled="maxComparisonReached && !selectedForComparison"
              @change="$emit('toggle-comparison', component)"
              class="sr-only"
            />
            <div
              :class="[
                'w-6 h-6 rounded border-2 flex items-center justify-center transition-colors',
                selectedForComparison
                  ? 'bg-indigo-600 border-indigo-600'
                  : maxComparisonReached
                  ? 'bg-gray-300 border-gray-300 cursor-not-allowed'
                  : 'bg-white border-gray-300 hover:border-indigo-500'
              ]"
            >
              <Icon
                v-if="selectedForComparison"
                name="check"
                class="h-4 w-4 text-white"
              />
            </div>
            <span class="sr-only">
              {{ selectedForComparison ? 'Remove from' : 'Add to' }} comparison
            </span>
          </label>
        </div>
      </div>
    </div>

    <!-- Component Info -->
    <div class="component-card__content p-4">
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex-1 min-w-0">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
            {{ component.name }}
          </h3>
          <div class="flex items-center space-x-2 mt-1">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
              <Icon :name="getCategoryIcon(component.category)" class="h-3 w-3 mr-1" />
              {{ getCategoryName(component.category) }}
            </span>
            <span
              v-if="audienceType"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
            >
              {{ formatAudienceType(audienceType) }}
            </span>
          </div>
        </div>

        <!-- Favorite Button -->
        <button
          @click="$emit('favorite', component)"
          :class="favoriteButtonClasses"
          :aria-label="isFavorite ? 'Remove from favorites' : 'Add to favorites'"
        >
          <Icon
            :name="isFavorite ? 'heart-solid' : 'heart'"
            class="h-5 w-5"
          />
        </button>
      </div>

      <!-- Description -->
      <p
        v-if="component.description"
        class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2"
      >
        {{ component.description }}
      </p>

      <!-- Stats Row -->
      <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
        <div class="flex items-center space-x-4">
          <!-- Rating -->
          <div class="flex items-center space-x-1">
            <div class="flex items-center">
              <Icon
                v-for="star in 5"
                :key="star"
                :name="star <= Math.floor(rating.average) ? 'star-solid' : 'star'"
                :class="[
                  'h-4 w-4',
                  star <= Math.floor(rating.average)
                    ? 'text-yellow-400'
                    : 'text-gray-300 dark:text-gray-600'
                ]"
              />
            </div>
            <span class="ml-1">{{ rating.average.toFixed(1) }}</span>
            <span class="text-gray-400">{{ rating.count }}</span>
          </div>

          <!-- Usage Stats -->
          <div class="flex items-center space-x-1">
            <Icon name="chart-bar" class="h-4 w-4" />
            <span>{{ formatUsageCount(usageStats.totalUsage) }}</span>
          </div>
        </div>

        <!-- Last Updated -->
        <div class="text-xs">
          {{ formatDate(component.updatedAt) }}
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex space-x-2">
        <button
          @click="$emit('select', component)"
          class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
        >
          <Icon name="plus" class="h-4 w-4 mr-2" />
          Add to Page
        </button>

        <button
          v-if="!comparisonMode"
          @click="$emit('toggle-comparison', component)"
          :disabled="maxComparisonReached && !selectedForComparison"
          :class="comparisonButtonClasses"
          :aria-label="selectedForComparison ? 'Remove from comparison' : 'Add to comparison'"
        >
          <Icon name="scale" class="h-4 w-4" />
        </button>
      </div>

      <!-- List View Additional Info -->
      <div v-if="viewMode === 'list'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="font-medium text-gray-900 dark:text-white">Version:</span>
            <span class="text-gray-600 dark:text-gray-400 ml-2">{{ component.version }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-900 dark:text-white">Type:</span>
            <span class="text-gray-600 dark:text-gray-400 ml-2">{{ component.type }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-900 dark:text-white">Conversion Rate:</span>
            <span class="text-gray-600 dark:text-gray-400 ml-2">
              {{ usageStats.conversionRate ? (usageStats.conversionRate * 100).toFixed(1) + '%' : 'N/A' }}
            </span>
          </div>
          <div>
            <span class="font-medium text-gray-900 dark:text-white">Recent Usage:</span>
            <span class="text-gray-600 dark:text-gray-400 ml-2">{{ usageStats.recentUsage }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Drag Indicator -->
    <div
      v-if="!comparisonMode"
      class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
    >
      <Icon name="arrows-pointing-out" class="h-5 w-5 text-gray-400" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Component, ComponentCategory, AudienceType } from '@/types/components'
import Icon from '@/components/Common/Icon.vue'

interface ComponentUsageStats {
  totalUsage: number
  recentUsage: number
  conversionRate?: number
  averageRating?: number
  totalRatings?: number
}

interface ComponentRating {
  average: number
  count: number
  distribution: Record<number, number>
}

interface Props {
  component: Component
  viewMode: 'grid' | 'list' | 'comparison'
  isFavorite: boolean
  recentlyUsed: boolean
  usageStats: ComponentUsageStats
  rating: ComponentRating
  selectedForComparison: boolean
  comparisonMode: boolean
  maxComparisonReached: boolean
}

interface Emits {
  (e: 'preview', component: Component): void
  (e: 'favorite', component: Component): void
  (e: 'select', component: Component): void
  (e: 'drag-start', component: Component): void
  (e: 'toggle-comparison', component: Component): void
  (e: 'view-details', component: Component): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed properties
const cardClasses = computed(() => [
  'component-card relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-200 group cursor-pointer',
  {
    'component-card--list': props.viewMode === 'list',
    'component-card--grid': props.viewMode === 'grid',
    'component-card--comparison': props.comparisonMode,
    'component-card--selected': props.selectedForComparison,
    'component-card--favorite': props.isFavorite,
    'component-card--recent': props.recentlyUsed
  }
])

const cardAriaLabel = computed(() => 
  `${props.component.name} component, ${props.component.category} category, rated ${props.rating.average.toFixed(1)} stars`
)

const favoriteButtonClasses = computed(() => [
  'p-2 rounded-full transition-colors',
  props.isFavorite
    ? 'text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900'
    : 'text-gray-400 hover:text-red-500 hover:bg-gray-50 dark:hover:bg-gray-700'
])

const comparisonButtonClasses = computed(() => [
  'px-3 py-2 rounded-md text-sm font-medium transition-colors',
  props.selectedForComparison
    ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-300'
    : props.maxComparisonReached
    ? 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500'
    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
])

const previewImage = computed(() => {
  // In a real implementation, this would come from component metadata
  const imageMap: Record<string, string> = {
    'hero-individual-1': 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'hero-institution-1': 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'form-signup-1': 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'testimonial-carousel-1': 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'stats-counters-1': 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'cta-button-1': 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'media-gallery-1': 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
  }
  return imageMap[props.component.id]
})

const audienceType = computed(() => {
  const config = props.component.config as any
  return config?.audienceType as AudienceType | undefined
})

const isHighRated = computed(() => props.rating.average >= 4.5)

// Methods
const getCategoryIcon = (category: ComponentCategory): string => {
  const iconMap: Record<ComponentCategory, string> = {
    hero: 'star',
    forms: 'document-text',
    testimonials: 'chat-bubble-left-right',
    statistics: 'chart-bar',
    ctas: 'cursor-arrow-rays',
    media: 'photo'
  }
  return iconMap[category] || 'square-3-stack-3d'
}

const getCategoryName = (category: ComponentCategory): string => {
  const nameMap: Record<ComponentCategory, string> = {
    hero: 'Hero',
    forms: 'Form',
    testimonials: 'Testimonial',
    statistics: 'Statistics',
    ctas: 'CTA',
    media: 'Media'
  }
  return nameMap[category] || category
}

const formatAudienceType = (type: AudienceType): string => {
  const formatMap: Record<AudienceType, string> = {
    individual: 'Individual',
    institution: 'Institution',
    employer: 'Employer'
  }
  return formatMap[type] || type
}

const formatUsageCount = (count: number): string => {
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}k`
  }
  return count.toString()
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - date.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7)
    return `${weeks} week${weeks > 1 ? 's' : ''} ago`
  } else {
    return date.toLocaleDateString()
  }
}

const handleDragStart = (event: DragEvent) => {
  if (props.comparisonMode) {
    event.preventDefault()
    return
  }
  
  // Set drag data
  if (event.dataTransfer) {
    event.dataTransfer.setData('application/json', JSON.stringify({
      type: 'component',
      component: props.component
    }))
    event.dataTransfer.effectAllowed = 'copy'
  }
  
  emit('drag-start', props.component)
}
</script>

<style scoped>
.component-card {
  @apply transition-all duration-200;
}

.component-card:hover {
  @apply shadow-lg transform -translate-y-1;
}

.component-card--list {
  @apply flex items-start space-x-4 p-4;
}

.component-card--list .component-card__preview {
  @apply flex-shrink-0 w-48;
}

.component-card--list .component-card__content {
  @apply flex-1 p-0;
}

.component-card--selected {
  @apply ring-2 ring-indigo-500 ring-offset-2;
}

.component-card--favorite {
  @apply border-red-200 dark:border-red-800;
}

.component-card--recent {
  @apply border-blue-200 dark:border-blue-800;
}

.component-card--comparison {
  @apply cursor-default;
}

.component-card--comparison:hover {
  @apply transform-none translate-y-0;
}

/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Focus styles */
.component-card button:focus {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .component-card {
    @apply border-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .component-card,
  .component-card *,
  .component-card *::before,
  .component-card *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>