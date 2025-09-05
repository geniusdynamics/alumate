<template>
  <div class="template-recommendations bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Smart Template Recommendations
    </h3>

    <div class="space-y-6">
      <!-- Top Performing Templates -->
      <div>
        <h4 class="text-md font-medium text-gray-800 dark:text-white mb-4 flex items-center">
          <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
          </svg>
          High-Performing Templates
        </h4>

        <div v-if="highPerformingTemplates.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="template in highPerformingTemplates"
            :key="template.id"
            class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4"
          >
            <div class="flex items-start justify-between">
              <div class="flex-grow">
                <div class="font-medium text-gray-900 dark:text-white mb-1">
                  {{ template.name }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                  {{ template.conversionRate }}% conversion rate
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                  <span>{{ formatNumber(template.conversions) }} conversions</span>
                  <span>{{ template.audienceType }}</span>
                </div>
              </div>

              <div class="flex items-center gap-1 text-green-600 dark:text-green-400">
                <span class="text-sm font-medium">{{ template.score }}/100</span>
                <svg v-if="template.trend === 'up'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                </svg>
              </div>
            </div>

            <button
              @click="recommendTemplate(template.id)"
              class="mt-3 w-full bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
              :aria-label="`Use ${template.name} template`"
            >
              Use This Template
            </button>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
          <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          <p>No high-performing templates found</p>
        </div>
      </div>

      <!-- Trending Templates -->
      <div>
        <h4 class="text-md font-medium text-gray-800 dark:text-white mb-4 flex items-center">
          <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
          Rising Stars
        </h4>

        <div v-if="trendingTemplates.length > 0" class="space-y-3">
          <div
            v-for="template in trendingTemplates"
            :key="template.id"
            class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4"
          >
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-medium text-sm">
                ↑
              </div>
              <div>
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ template.name }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  {{ template.change > 0 ? '+' : '' }}{{ template.change }}% vs last week
                </div>
              </div>
            </div>

            <div class="text-right">
              <div class="font-medium text-gray-900 dark:text-white">
                {{ template.conversionRate }}%
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ formatNumber(template.conversions) }} conv.
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
          <p class="text-sm">No trending templates this week</p>
        </div>
      </div>

      <!-- A/B Testing Suggestions -->
      <div v-if="abTestSuggestions.length > 0">
        <h4 class="text-md font-medium text-gray-800 dark:text-white mb-4 flex items-center">
          <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          A/B Test Opportunities
        </h4>

        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="suggestion in abTestSuggestions.slice(0, 2)"
              :key="suggestion.id"
              class="text-sm"
            >
              <div class="font-medium text-gray-900 dark:text-white mb-1">
                Test: {{ suggestion.templateA }} vs {{ suggestion.templateB }}
              </div>
              <div class="text-gray-600 dark:text-gray-400 mb-2">
                {{ suggestion.reason }}
              </div>
              <div class="flex items-center gap-2">
                <span class="text-purple-600 dark:text-purple-400 font-medium">
                  {{ suggestion.potentialLift }}% potential lift
                </span>
                <button
                  @click="startABTest(suggestion.id)"
                  class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 text-xs underline"
                >
                  Start Test
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Performance Insights -->
      <div>
        <h4 class="text-md font-medium text-gray-800 dark:text-white mb-4 flex items-center">
          <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Performance Insights
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="insight in performanceInsights"
            :key="insight.id"
            :class="[
              'rounded-lg p-4 border',
              insight.type === 'positive' ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' :
              insight.type === 'warning' ? 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800' :
              'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800'
            ]"
          >
            <div class="flex items-start gap-3">
              <div :class="[
                'flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center',
                insight.type === 'positive' ? 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-400' :
                insight.type === 'warning' ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-800 dark:text-yellow-400' :
                'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-400'
              ]">
                <svg v-if="insight.type === 'positive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <svg v-else-if="insight.type === 'warning'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>

              <div class="flex-grow min-w-0">
                <div class="font-medium text-gray-900 dark:text-white text-sm">
                  {{ insight.title }}
                </div>
                <div class="text-gray-600 dark:text-gray-400 text-xs mt-1">
                  {{ insight.description }}
                </div>
                <div class="text-gray-500 dark:text-gray-400 text-xs mt-2">
                  {{ insight.action }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

// Types
interface TemplateRecommendation {
  id: number
  name: string
  audienceType: string
  conversionRate: number
  conversions: number
  change: number
  score: number
  trend: 'up' | 'down' | 'neutral'
}

interface ABTestSuggestion {
  id: string
  templateA: string
  templateB: string
  reason: string
  potentialLift: number
}

interface PerformanceInsight {
  id: string
  title: string
  description: string
  action: string
  type: 'positive' | 'warning' | 'info'
}

interface Props {
  templates: TemplateRecommendation[]
  performanceData: any
}

const props = defineProps<Props>()

// Sample data for demo
const abTestSuggestions = ref<ABTestSuggestion[]>([
  {
    id: '1',
    templateA: 'Hero Dark',
    templateB: 'Hero Light',
    reason: 'Similar conversion rates but different visual appeal',
    potentialLift: 15
  },
  {
    id: '2',
    templateA: 'CTA Left',
    templateB: 'CTA Center',
    reason: 'Button positioning affects user engagement',
    potentialLift: 8
  }
])

// Computed properties
const highPerformingTemplates = computed(() =>
  props.templates
    .filter(t => parseFloat(t.conversionRate.toString()) > 5.0)
    .sort((a, b) => parseFloat(b.conversionRate.toString()) - parseFloat(a.conversionRate.toString()))
    .slice(0, 4)
)

const trendingTemplates = computed(() =>
  props.templates
    .filter(t => t.change > 0)
    .sort((a, b) => b.change - a.change)
    .slice(0, 3)
)

const performanceInsights = ref<PerformanceInsight[]>([
  {
    id: '1',
    title: 'Mobile conversion is 23% higher than desktop',
    description: 'Users on mobile devices are converting at significantly higher rates.',
    action: 'Optimize desktop experience or increase mobile traffic',
    type: 'positive'
  },
  {
    id: '2',
    title: 'Long-form content outperforming short content',
    description: 'Templates with detailed descriptions have 18% higher engagement.',
    action: 'Consider adding more content depth to templates',
    type: 'positive'
  },
  {
    id: '3',
    title: 'Color scheme A converting 12% better',
    description: 'Blue-based color schemes are performing significantly better this week.',
    action: 'Consider using blue as the primary color in new templates',
    type: 'info'
  },
  {
    id: '4',
    title: 'Form abandonment rate increased by 5%',
    description: 'Users are abandoning the conversion form more frequently this week.',
    action: 'Review form length and optimization opportunities',
    type: 'warning'
  }
])

// Methods
const recommendTemplate = (templateId: number) => {
  // In a real app, this would navigate to template usage or emit an event
  console.log('Recommending template:', templateId)
  // emit('template-recommended', templateId)
}

const startABTest = (testId: string) => {
  console.log('Starting A/B test:', testId)
  // Implementation for A/B test setup
}

const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}
</script>

<style scoped>
.template-recommendations {
  min-height: 600px;
}

/* Custom button animations */
button:hover {
  transform: translateY(-1px);
  transition: all 0.2s ease-in-out;
}

/* Card hover effects */
.template-card:hover {
  transform: translateY(-2px);
  transition: all 0.3s ease-in-out;
}

/* Responsive design */
@media (max-width: 768px) {
  .template-recommendations {
    @apply p-4;
  }

  .grid-cols-1.md\\:grid-cols-2 {
    @apply grid-cols-1;
  }
}

/* Dark mode support */
.dark .template-card {
  @apply bg-gray-700 border-gray-600;
}
</style>