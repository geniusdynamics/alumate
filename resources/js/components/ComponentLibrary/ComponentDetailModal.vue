<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 overflow-y-auto"
      role="dialog"
      :aria-labelledby="modalTitleId"
      aria-modal="true"
    >
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        @click="$emit('close')"
      ></div>

      <!-- Modal -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div
          class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden"
          @click.stop
        >
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                  <Icon :name="getCategoryIcon(component.category)" class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                </div>
              </div>
              <div>
                <h2 :id="modalTitleId" class="text-xl font-semibold text-gray-900 dark:text-white">
                  {{ component.name }}
                </h2>
                <div class="flex items-center space-x-3 mt-1">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    {{ getCategoryName(component.category) }}
                  </span>
                  <span
                    v-if="audienceType"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                  >
                    {{ formatAudienceType(audienceType) }}
                  </span>
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
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      {{ rating.average.toFixed(1) }} ({{ rating.count }})
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
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
              <button
                @click="$emit('close')"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                aria-label="Close modal"
              >
                <Icon name="x" class="h-6 w-6" />
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
            <div class="p-6">
              <!-- Component Preview -->
              <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                  Preview
                </h3>
                <div class="relative aspect-video bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                  <img
                    v-if="previewImage"
                    :src="previewImage"
                    :alt="`Preview of ${component.name}`"
                    class="w-full h-full object-cover"
                  />
                  <div
                    v-else
                    class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500"
                  >
                    <Icon :name="getCategoryIcon(component.category)" class="h-16 w-16" />
                  </div>
                  
                  <!-- Preview Actions -->
                  <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center opacity-0 hover:opacity-100">
                    <button
                      @click="$emit('preview', component)"
                      class="bg-white text-gray-900 px-6 py-3 rounded-md font-medium hover:bg-gray-100 transition-colors"
                    >
                      <Icon name="eye" class="h-5 w-5 mr-2" />
                      Live Preview
                    </button>
                  </div>
                </div>
              </div>

              <!-- Tabs -->
              <div class="mb-6">
                <nav class="flex space-x-8 border-b border-gray-200 dark:border-gray-700">
                  <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="getTabClasses(tab.id)"
                    :aria-selected="activeTab === tab.id"
                    role="tab"
                  >
                    <Icon :name="tab.icon" class="h-4 w-4 mr-2" />
                    {{ tab.name }}
                  </button>
                </nav>
              </div>

              <!-- Tab Content -->
              <div class="tab-content">
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-6">
                  <!-- Description -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Description
                    </h4>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                      {{ component.description || documentation.description }}
                    </p>
                  </div>

                  <!-- Key Features -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Key Features
                    </h4>
                    <ul class="space-y-2">
                      <li
                        v-for="feature in getKeyFeatures()"
                        :key="feature"
                        class="flex items-start space-x-2"
                      >
                        <Icon name="check" class="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                        <span class="text-gray-600 dark:text-gray-400">{{ feature }}</span>
                      </li>
                    </ul>
                  </div>

                  <!-- Best Practices -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Best Practices
                    </h4>
                    <ul class="space-y-2">
                      <li
                        v-for="practice in documentation.bestPractices"
                        :key="practice"
                        class="flex items-start space-x-2"
                      >
                        <Icon name="light-bulb" class="h-4 w-4 text-yellow-500 mt-0.5 flex-shrink-0" />
                        <span class="text-gray-600 dark:text-gray-400">{{ practice }}</span>
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- Configuration Tab -->
                <div v-if="activeTab === 'configuration'" class="space-y-6">
                  <!-- Properties -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Configuration Properties
                    </h4>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                      <dl class="space-y-3">
                        <div
                          v-for="(description, property) in documentation.properties"
                          :key="property"
                          class="flex flex-col sm:flex-row sm:items-center"
                        >
                          <dt class="font-mono text-sm text-indigo-600 dark:text-indigo-400 sm:w-1/3">
                            {{ property }}
                          </dt>
                          <dd class="text-sm text-gray-600 dark:text-gray-400 sm:w-2/3">
                            {{ description }}
                          </dd>
                        </div>
                      </dl>
                    </div>
                  </div>

                  <!-- Current Configuration -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Current Configuration
                    </h4>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                      <pre class="text-sm text-gray-600 dark:text-gray-400 overflow-x-auto">{{ JSON.stringify(component.config, null, 2) }}</pre>
                    </div>
                  </div>
                </div>

                <!-- Examples Tab -->
                <div v-if="activeTab === 'examples'" class="space-y-6">
                  <div
                    v-for="(example, index) in examples"
                    :key="index"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                  >
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      {{ example }}
                    </h4>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                      <code class="text-sm text-gray-600 dark:text-gray-400">
                        // Example implementation for {{ example.toLowerCase() }}
                        <br />
                        // This would contain actual code examples
                      </code>
                    </div>
                  </div>
                </div>

                <!-- Analytics Tab -->
                <div v-if="activeTab === 'analytics'" class="space-y-6">
                  <!-- Usage Statistics -->
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 text-center">
                      <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                        {{ formatUsageCount(usageStats.totalUsage) }}
                      </div>
                      <div class="text-sm text-gray-600 dark:text-gray-400">
                        Total Uses
                      </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 text-center">
                      <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-2">
                        {{ formatConversionRate(usageStats.conversionRate || 0) }}
                      </div>
                      <div class="text-sm text-gray-600 dark:text-gray-400">
                        Conversion Rate
                      </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 text-center">
                      <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                        {{ usageStats.recentUsage }}
                      </div>
                      <div class="text-sm text-gray-600 dark:text-gray-400">
                        Recent Uses
                      </div>
                    </div>
                  </div>

                  <!-- Rating Distribution -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Rating Distribution
                    </h4>
                    <div class="space-y-2">
                      <div
                        v-for="(count, stars) in rating.distribution"
                        :key="stars"
                        class="flex items-center space-x-3"
                      >
                        <div class="flex items-center space-x-1 w-16">
                          <span class="text-sm text-gray-600 dark:text-gray-400">{{ stars }}</span>
                          <Icon name="star-solid" class="h-3 w-3 text-yellow-400" />
                        </div>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                          <div
                            class="bg-yellow-400 h-2 rounded-full"
                            :style="{ width: `${(count / rating.count) * 100}%` }"
                          ></div>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-8">{{ count }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Accessibility Tab -->
                <div v-if="activeTab === 'accessibility'" class="space-y-6">
                  <!-- Accessibility Features -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Accessibility Features
                    </h4>
                    <ul class="space-y-2">
                      <li
                        v-for="feature in documentation.accessibility"
                        :key="feature"
                        class="flex items-start space-x-2"
                      >
                        <Icon name="check" class="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                        <span class="text-gray-600 dark:text-gray-400">{{ feature }}</span>
                      </li>
                    </ul>
                  </div>

                  <!-- WCAG Compliance -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      WCAG 2.1 Compliance
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="flex items-center space-x-2">
                        <Icon name="check" class="h-5 w-5 text-green-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Level A</span>
                      </div>
                      <div class="flex items-center space-x-2">
                        <Icon name="check" class="h-5 w-5 text-green-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Level AA</span>
                      </div>
                      <div class="flex items-center space-x-2">
                        <Icon name="check" class="h-5 w-5 text-green-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Level AAA</span>
                      </div>
                    </div>
                  </div>

                  <!-- Testing Tools -->
                  <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                      Recommended Testing Tools
                    </h4>
                    <ul class="space-y-2">
                      <li class="flex items-start space-x-2">
                        <Icon name="wrench" class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span class="text-gray-600 dark:text-gray-400">axe DevTools for automated testing</span>
                      </li>
                      <li class="flex items-start space-x-2">
                        <Icon name="wrench" class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span class="text-gray-600 dark:text-gray-400">NVDA or JAWS for screen reader testing</span>
                      </li>
                      <li class="flex items-start space-x-2">
                        <Icon name="wrench" class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span class="text-gray-600 dark:text-gray-400">Keyboard navigation testing</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
              <span>Version {{ component.version }}</span>
              <span>•</span>
              <span>Updated {{ formatDate(component.updatedAt) }}</span>
            </div>
            
            <div class="flex space-x-3">
              <button
                @click="$emit('preview', component)"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
              >
                <Icon name="eye" class="h-4 w-4 mr-2" />
                Preview
              </button>
              <button
                @click="$emit('select', component)"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <Icon name="plus" class="h-4 w-4 mr-2" />
                Add to Page
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
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

interface ComponentDocumentation {
  description: string
  properties: Record<string, string>
  examples: string[]
  bestPractices: string[]
  accessibility: string[]
}

interface Props {
  component: Component
  isOpen: boolean
  usageStats: ComponentUsageStats
  rating: ComponentRating
  documentation: ComponentDocumentation
  examples: string[]
  isFavorite?: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'preview', component: Component): void
  (e: 'select', component: Component): void
  (e: 'favorite', component: Component): void
}

const props = withDefaults(defineProps<Props>(), {
  isFavorite: false
})

const emit = defineEmits<Emits>()

// Reactive state
const activeTab = ref('overview')
const modalTitleId = `modal-title-${props.component.id}`

// Tab configuration
const tabs = [
  { id: 'overview', name: 'Overview', icon: 'information-circle' },
  { id: 'configuration', name: 'Configuration', icon: 'cog' },
  { id: 'examples', name: 'Examples', icon: 'code' },
  { id: 'analytics', name: 'Analytics', icon: 'chart-bar' },
  { id: 'accessibility', name: 'Accessibility', icon: 'shield-check' }
]

// Computed properties
const favoriteButtonClasses = computed(() => [
  'p-2 rounded-full transition-colors',
  props.isFavorite
    ? 'text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900'
    : 'text-gray-400 hover:text-red-500 hover:bg-gray-50 dark:hover:bg-gray-700'
])

const previewImage = computed(() => {
  const imageMap: Record<string, string> = {
    'hero-individual-1': 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'hero-institution-1': 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'form-signup-1': 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'testimonial-carousel-1': 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'stats-counters-1': 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'cta-button-1': 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'media-gallery-1': 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
  }
  return imageMap[props.component.id]
})

const audienceType = computed(() => {
  const config = props.component.config as any
  return config?.audienceType as AudienceType | undefined
})

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
    hero: 'Hero Section',
    forms: 'Form',
    testimonials: 'Testimonial',
    statistics: 'Statistics',
    ctas: 'Call to Action',
    media: 'Media'
  }
  return nameMap[category] || category
}

const formatAudienceType = (type: AudienceType | undefined): string => {
  if (!type) return 'N/A'
  const formatMap: Record<AudienceType, string> = {
    individual: 'Individual',
    institution: 'Institution',
    employer: 'Employer'
  }
  return formatMap[type] || type
}

const getTabClasses = (tabId: string) => [
  'flex items-center py-2 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  activeTab.value === tabId
    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
]

const getKeyFeatures = (): string[] => {
  const categoryFeatures: Record<ComponentCategory, string[]> = {
    hero: [
      'Responsive design with mobile optimization',
      'Multiple background media options (image, video, gradient)',
      'Customizable CTA buttons with tracking',
      'Animated statistics counters',
      'Audience-specific content variants'
    ],
    forms: [
      'Drag-and-drop field builder',
      'Real-time validation with custom rules',
      'CRM integration support',
      'Spam protection and security features',
      'Progressive enhancement for accessibility'
    ],
    testimonials: [
      'Multiple layout options (single, carousel, grid)',
      'Video testimonial support with accessibility controls',
      'Advanced filtering by audience and criteria',
      'Author information and company details',
      'Rating system integration'
    ],
    statistics: [
      'Animated counters with scroll triggers',
      'Real-time data integration',
      'Multiple chart types and visualizations',
      'Responsive grid layouts',
      'Performance optimization with caching'
    ],
    ctas: [
      'Multiple CTA types (button, banner, inline)',
      'Conversion tracking and analytics',
      'A/B testing framework',
      'Customizable styling and animations',
      'UTM parameter support'
    ],
    media: [
      'Image gallery with lightbox functionality',
      'Video embed with accessibility controls',
      'Interactive demo components',
      'Automatic image optimization',
      'CDN integration for global delivery'
    ]
  }
  
  return categoryFeatures[props.component.category] || ['Standard component features']
}

const formatUsageCount = (count: number): string => {
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}k`
  }
  return count.toString()
}

const formatConversionRate = (rate: number): string => {
  return `${(rate * 100).toFixed(1)}%`
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString()
}

// Watch for modal open/close to handle focus management
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    // Reset to overview tab when opening
    activeTab.value = 'overview'
    
    // Focus management
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
/* Modal animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

/* Tab content transitions */
.tab-content {
  min-height: 400px;
}

/* Focus styles */
.component-detail-modal button:focus,
.component-detail-modal [role="tab"]:focus {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
}

/* Scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-800;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600 rounded-full;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400 dark:bg-gray-500;
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .component-detail-modal {
    @apply contrast-125;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .component-detail-modal *,
  .component-detail-modal *::before,
  .component-detail-modal *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>