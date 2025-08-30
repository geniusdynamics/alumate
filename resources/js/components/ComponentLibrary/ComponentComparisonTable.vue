<template>
  <div class="component-comparison-table">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <th class="text-left p-4 font-medium text-gray-900 dark:text-white w-48">
              Feature
            </th>
            <th
              v-for="component in components"
              :key="component.id"
              class="text-center p-4 font-medium text-gray-900 dark:text-white min-w-64"
            >
              <div class="space-y-3">
                <!-- Component Preview -->
                <div class="relative aspect-video bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                  <img
                    v-if="getPreviewImage(component.id)"
                    :src="getPreviewImage(component.id)"
                    :alt="`Preview of ${component.name}`"
                    class="w-full h-full object-cover"
                  />
                  <div
                    v-else
                    class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500"
                  >
                    <Icon :name="getCategoryIcon(component.category)" class="h-8 w-8" />
                  </div>
                </div>

                <!-- Component Name -->
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">
                    {{ component.name }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ getCategoryName(component.category) }}
                  </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-2">
                  <button
                    @click="$emit('preview', component)"
                    class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
                  >
                    Preview
                  </button>
                  <button
                    @click="$emit('select', component)"
                    class="flex-1 bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 transition-colors"
                  >
                    Select
                  </button>
                  <button
                    @click="$emit('remove', component)"
                    class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                    :aria-label="`Remove ${component.name} from comparison`"
                  >
                    <Icon name="x" class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- Basic Information -->
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Description
            </td>
            <td
              v-for="component in components"
              :key="`desc-${component.id}`"
              class="p-4 text-sm text-gray-600 dark:text-gray-400"
            >
              {{ component.description || 'No description available' }}
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Type
            </td>
            <td
              v-for="component in components"
              :key="`type-${component.id}`"
              class="p-4 text-sm text-gray-600 dark:text-gray-400"
            >
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                {{ component.type }}
              </span>
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Audience Type
            </td>
            <td
              v-for="component in components"
              :key="`audience-${component.id}`"
              class="p-4 text-sm text-gray-600 dark:text-gray-400"
            >
              <span
                v-if="getAudienceType(component)"
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300"
              >
                {{ formatAudienceType(getAudienceType(component)) }}
              </span>
              <span v-else class="text-gray-400">N/A</span>
            </td>
          </tr>

          <!-- Performance Metrics -->
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Rating
            </td>
            <td
              v-for="component in components"
              :key="`rating-${component.id}`"
              class="p-4"
            >
              <div class="flex items-center space-x-2">
                <div class="flex items-center">
                  <Icon
                    v-for="star in 5"
                    :key="star"
                    :name="star <= Math.floor(getRating(component.id)) ? 'star-solid' : 'star'"
                    :class="[
                      'h-4 w-4',
                      star <= Math.floor(getRating(component.id))
                        ? 'text-yellow-400'
                        : 'text-gray-300 dark:text-gray-600'
                    ]"
                  />
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                  {{ getRating(component.id).toFixed(1) }}
                </span>
              </div>
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Usage Count
            </td>
            <td
              v-for="component in components"
              :key="`usage-${component.id}`"
              class="p-4 text-sm text-gray-600 dark:text-gray-400"
            >
              {{ formatUsageCount(getUsageCount(component.id)) }}
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Conversion Rate
            </td>
            <td
              v-for="component in components"
              :key="`conversion-${component.id}`"
              class="p-4 text-sm text-gray-600 dark:text-gray-400"
            >
              {{ formatConversionRate(getConversionRate(component.id)) }}
            </td>
          </tr>

          <!-- Technical Details -->
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Version
            </td>
            <td
              v-for="component in components"
              :key="`version-${component.id}`"
              class="p-4 text-sm text-gray-600 dark:text-gray-400"
            >
              <span class="font-mono">{{ component.version }}</span>
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Last Updated
            </td>
            <td
              v-for="component in components"
              :key="`updated-${component.id}`"
              class="p-4 text-sm text-gray-600 dark:text-gray-400"
            >
              {{ formatDate(component.updatedAt) }}
            </td>
          </tr>

          <!-- Feature Comparison -->
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Mobile Optimized
            </td>
            <td
              v-for="component in components"
              :key="`mobile-${component.id}`"
              class="p-4 text-center"
            >
              <Icon
                name="check"
                class="h-5 w-5 text-green-500 mx-auto"
                aria-label="Yes"
              />
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Accessibility Compliant
            </td>
            <td
              v-for="component in components"
              :key="`a11y-${component.id}`"
              class="p-4 text-center"
            >
              <Icon
                name="check"
                class="h-5 w-5 text-green-500 mx-auto"
                aria-label="Yes"
              />
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              A/B Testing Support
            </td>
            <td
              v-for="component in components"
              :key="`ab-${component.id}`"
              class="p-4 text-center"
            >
              <Icon
                :name="hasABTestingSupport(component) ? 'check' : 'x'"
                :class="[
                  'h-5 w-5 mx-auto',
                  hasABTestingSupport(component) ? 'text-green-500' : 'text-red-500'
                ]"
                :aria-label="hasABTestingSupport(component) ? 'Yes' : 'No'"
              />
            </td>
          </tr>

          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Analytics Integration
            </td>
            <td
              v-for="component in components"
              :key="`analytics-${component.id}`"
              class="p-4 text-center"
            >
              <Icon
                name="check"
                class="h-5 w-5 text-green-500 mx-auto"
                aria-label="Yes"
              />
            </td>
          </tr>

          <!-- Configuration Options -->
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Customization Options
            </td>
            <td
              v-for="component in components"
              :key="`custom-${component.id}`"
              class="p-4"
            >
              <div class="space-y-1">
                <div
                  v-for="option in getCustomizationOptions(component)"
                  :key="option"
                  class="text-xs text-gray-600 dark:text-gray-400"
                >
                  • {{ option }}
                </div>
              </div>
            </td>
          </tr>

          <!-- Pros and Cons -->
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Best For
            </td>
            <td
              v-for="component in components"
              :key="`pros-${component.id}`"
              class="p-4"
            >
              <div class="space-y-1">
                <div
                  v-for="pro in getBestFor(component)"
                  :key="pro"
                  class="text-xs text-green-700 dark:text-green-400"
                >
                  • {{ pro }}
                </div>
              </div>
            </td>
          </tr>

          <tr>
            <td class="p-4 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900">
              Considerations
            </td>
            <td
              v-for="component in components"
              :key="`cons-${component.id}`"
              class="p-4"
            >
              <div class="space-y-1">
                <div
                  v-for="consideration in getConsiderations(component)"
                  :key="consideration"
                  class="text-xs text-amber-700 dark:text-amber-400"
                >
                  • {{ consideration }}
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Comparison Summary -->
    <div class="mt-8 bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Comparison Summary
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Highest Rated -->
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-2">
            <Icon name="star" class="h-6 w-6 inline mr-2" />
            Highest Rated
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ getHighestRated()?.name || 'N/A' }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
            {{ getHighestRated() ? getRating(getHighestRated()!.id).toFixed(1) + ' stars' : '' }}
          </div>
        </div>

        <!-- Most Popular -->
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-2">
            <Icon name="chart-bar" class="h-6 w-6 inline mr-2" />
            Most Popular
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ getMostPopular()?.name || 'N/A' }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
            {{ getMostPopular() ? formatUsageCount(getUsageCount(getMostPopular()!.id)) + ' uses' : '' }}
          </div>
        </div>

        <!-- Best Conversion -->
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mb-2">
            <Icon name="trending-up" class="h-6 w-6 inline mr-2" />
            Best Conversion
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ getBestConversion()?.name || 'N/A' }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
            {{ getBestConversion() ? formatConversionRate(getConversionRate(getBestConversion()!.id)) : '' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Component, ComponentCategory, AudienceType } from '@/types/components'
import Icon from '@/components/Common/Icon.vue'

interface Props {
  components: Component[]
}

interface Emits {
  (e: 'remove', component: Component): void
  (e: 'select', component: Component): void
  (e: 'preview', component: Component): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Mock data for demonstration
const mockRatings: Record<string, number> = {}
const mockUsageCounts: Record<string, number> = {}
const mockConversionRates: Record<string, number> = {}

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

const getPreviewImage = (componentId: string): string | undefined => {
  const imageMap: Record<string, string> = {
    'hero-individual-1': 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'hero-institution-1': 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'form-signup-1': 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'testimonial-carousel-1': 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'stats-counters-1': 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'cta-button-1': 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    'media-gallery-1': 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
  }
  return imageMap[componentId]
}

const getAudienceType = (component: Component): AudienceType | undefined => {
  const config = component.config as any
  return config?.audienceType
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

const getRating = (componentId: string): number => {
  if (!mockRatings[componentId]) {
    mockRatings[componentId] = Math.random() * 2 + 3 // 3-5 stars
  }
  return mockRatings[componentId]
}

const getUsageCount = (componentId: string): number => {
  if (!mockUsageCounts[componentId]) {
    mockUsageCounts[componentId] = Math.floor(Math.random() * 1000) + 50
  }
  return mockUsageCounts[componentId]
}

const getConversionRate = (componentId: string): number => {
  if (!mockConversionRates[componentId]) {
    mockConversionRates[componentId] = Math.random() * 0.1 + 0.05 // 5-15%
  }
  return mockConversionRates[componentId]
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

const hasABTestingSupport = (component: Component): boolean => {
  // In a real implementation, this would check component configuration
  return Math.random() > 0.3 // 70% chance of having A/B testing support
}

const getCustomizationOptions = (component: Component): string[] => {
  const baseOptions = ['Colors', 'Typography', 'Spacing', 'Layout']
  const categoryOptions: Record<ComponentCategory, string[]> = {
    hero: ['Background Media', 'CTA Buttons', 'Statistics'],
    forms: ['Field Types', 'Validation Rules', 'Submission Handling'],
    testimonials: ['Display Layout', 'Author Information', 'Rating Display'],
    statistics: ['Chart Types', 'Animation Settings', 'Data Sources'],
    ctas: ['Button Styles', 'Tracking Parameters', 'A/B Testing'],
    media: ['Gallery Layout', 'Lightbox Settings', 'Optimization']
  }
  
  return [...baseOptions, ...(categoryOptions[component.category] || [])]
}

const getBestFor = (component: Component): string[] => {
  const categoryBestFor: Record<ComponentCategory, string[]> = {
    hero: ['Landing pages', 'Homepage headers', 'Campaign pages'],
    forms: ['Lead generation', 'User registration', 'Contact forms'],
    testimonials: ['Social proof', 'Trust building', 'Case studies'],
    statistics: ['Data visualization', 'Achievement showcases', 'Progress tracking'],
    ctas: ['Conversion optimization', 'Action prompts', 'Navigation'],
    media: ['Visual storytelling', 'Product showcases', 'Event galleries']
  }
  
  return categoryBestFor[component.category] || ['General use cases']
}

const getConsiderations = (component: Component): string[] => {
  const categoryConsiderations: Record<ComponentCategory, string[]> = {
    hero: ['Large file sizes', 'Mobile performance', 'Content hierarchy'],
    forms: ['Validation complexity', 'Spam protection', 'Data privacy'],
    testimonials: ['Content moderation', 'Regular updates', 'Authenticity'],
    statistics: ['Data accuracy', 'Real-time updates', 'Loading performance'],
    ctas: ['Button fatigue', 'Placement strategy', 'A/B testing setup'],
    media: ['Loading times', 'Storage costs', 'Accessibility compliance']
  }
  
  return categoryConsiderations[component.category] || ['Standard considerations']
}

// Computed properties for summary
const getHighestRated = computed(() => {
  return props.components.reduce((highest, component) => {
    const currentRating = getRating(component.id)
    const highestRating = highest ? getRating(highest.id) : 0
    return currentRating > highestRating ? component : highest
  }, null as Component | null)
})

const getMostPopular = computed(() => {
  return props.components.reduce((mostPopular, component) => {
    const currentUsage = getUsageCount(component.id)
    const mostPopularUsage = mostPopular ? getUsageCount(mostPopular.id) : 0
    return currentUsage > mostPopularUsage ? component : mostPopular
  }, null as Component | null)
})

const getBestConversion = computed(() => {
  return props.components.reduce((bestConversion, component) => {
    const currentRate = getConversionRate(component.id)
    const bestRate = bestConversion ? getConversionRate(bestConversion.id) : 0
    return currentRate > bestRate ? component : bestConversion
  }, null as Component | null)
})
</script>

<style scoped>
.component-comparison-table {
  @apply w-full;
}

.component-comparison-table table {
  @apply border border-gray-200 dark:border-gray-700;
}

.component-comparison-table th,
.component-comparison-table td {
  @apply border-r border-gray-200 dark:border-gray-700;
}

.component-comparison-table th:last-child,
.component-comparison-table td:last-child {
  @apply border-r-0;
}

.component-comparison-table tbody tr:hover {
  @apply bg-gray-50 dark:bg-gray-700;
}

/* Responsive table */
@media (max-width: 768px) {
  .component-comparison-table {
    @apply text-sm;
  }
  
  .component-comparison-table th,
  .component-comparison-table td {
    @apply p-2;
  }
}

/* Focus styles */
.component-comparison-table button:focus {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .component-comparison-table table {
    @apply border-2;
  }
}
</style>