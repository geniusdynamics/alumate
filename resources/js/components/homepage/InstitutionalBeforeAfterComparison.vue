<template>
  <div class="institutional-before-after-comparison bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
      <h3 class="text-xl font-bold mb-2">{{ title }}</h3>
      <p class="text-blue-100">{{ subtitle }}</p>
    </div>

    <!-- Comparison Content -->
    <div class="p-6">
      <!-- Institution Info -->
      <div class="flex items-center mb-6">
        <img 
          v-if="institutionLogo"
          :src="institutionLogo" 
          :alt="`${institutionName} logo`"
          class="w-12 h-12 object-contain rounded-lg border border-gray-200 mr-4"
        />
        <div>
          <h4 class="font-semibold text-gray-900">{{ institutionName }}</h4>
          <p class="text-sm text-gray-600 capitalize">{{ institutionType }}</p>
          <p class="text-xs text-gray-500">{{ formatAlumniCount(alumniCount) }} Alumni</p>
        </div>
      </div>

      <!-- Before/After Grid -->
      <div class="grid md:grid-cols-2 gap-8 mb-6">
        <!-- Before Section -->
        <div class="before-section">
          <div class="flex items-center mb-4">
            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
              <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900">Before Implementation</h4>
          </div>
          
          <div class="space-y-4">
            <div 
              v-for="metric in beforeMetrics" 
              :key="metric.key"
              class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200"
            >
              <span class="text-sm font-medium text-gray-700">{{ metric.label }}</span>
              <span class="text-lg font-bold text-red-600">
                {{ formatMetricValue(metric.value, metric.unit) }}
              </span>
            </div>
          </div>

          <!-- Before Challenges -->
          <div class="mt-4">
            <h5 class="font-medium text-gray-900 mb-2">Key Challenges:</h5>
            <ul class="space-y-1">
              <li 
                v-for="challenge in beforeChallenges" 
                :key="challenge"
                class="flex items-start text-sm text-gray-600"
              >
                <svg class="w-4 h-4 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ challenge }}
              </li>
            </ul>
          </div>
        </div>

        <!-- After Section -->
        <div class="after-section">
          <div class="flex items-center mb-4">
            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
              <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900">After Implementation</h4>
          </div>
          
          <div class="space-y-4">
            <div 
              v-for="metric in afterMetrics" 
              :key="metric.key"
              class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200"
            >
              <span class="text-sm font-medium text-gray-700">{{ metric.label }}</span>
              <div class="text-right">
                <span class="text-lg font-bold text-green-600">
                  {{ formatMetricValue(metric.value, metric.unit) }}
                </span>
                <div class="text-xs text-green-600 font-medium">
                  +{{ getImprovementPercentage(metric.key) }}%
                </div>
              </div>
            </div>
          </div>

          <!-- After Benefits -->
          <div class="mt-4">
            <h5 class="font-medium text-gray-900 mb-2">Key Benefits:</h5>
            <ul class="space-y-1">
              <li 
                v-for="benefit in afterBenefits" 
                :key="benefit"
                class="flex items-start text-sm text-gray-600"
              >
                <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ benefit }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Transformation Arrow -->
      <div class="flex justify-center mb-6">
        <div class="flex items-center bg-blue-50 rounded-full px-4 py-2 border border-blue-200">
          <span class="text-sm font-medium text-blue-700 mr-2">{{ timeframe }} Transformation</span>
          <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </div>
      </div>

      <!-- Overall Impact Summary -->
      <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-4 border border-green-200">
        <div class="flex items-center justify-between mb-3">
          <h4 class="font-semibold text-gray-900">Overall Impact</h4>
          <div class="flex items-center text-green-600">
            <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
            <span class="font-bold">{{ overallImprovement }}% Average Improvement</span>
          </div>
        </div>
        <p class="text-sm text-gray-700">{{ impactSummary }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Metric {
  key: string
  label: string
  value: number
  unit: 'percentage' | 'count' | 'currency' | 'days'
}

interface Props {
  title: string
  subtitle: string
  institutionName: string
  institutionType: string
  institutionLogo?: string
  alumniCount: number
  beforeMetrics: Metric[]
  afterMetrics: Metric[]
  beforeChallenges: string[]
  afterBenefits: string[]
  timeframe: string
  impactSummary: string
}

const props = defineProps<Props>()

const overallImprovement = computed(() => {
  const improvements = props.afterMetrics.map(afterMetric => {
    const beforeMetric = props.beforeMetrics.find(m => m.key === afterMetric.key)
    if (!beforeMetric || beforeMetric.value === 0) return 0
    return ((afterMetric.value - beforeMetric.value) / beforeMetric.value) * 100
  })
  
  if (improvements.length === 0) return 0
  const total = improvements.reduce((sum, improvement) => sum + improvement, 0)
  return Math.round(total / improvements.length)
})

const getImprovementPercentage = (metricKey: string): number => {
  const beforeMetric = props.beforeMetrics.find(m => m.key === metricKey)
  const afterMetric = props.afterMetrics.find(m => m.key === metricKey)
  
  if (!beforeMetric || !afterMetric || beforeMetric.value === 0) return 0
  
  return Math.round(((afterMetric.value - beforeMetric.value) / beforeMetric.value) * 100)
}

const formatMetricValue = (value: number, unit: string): string => {
  switch (unit) {
    case 'percentage':
      return `${value}%`
    case 'currency':
      return formatCurrency(value)
    case 'count':
      return formatNumber(value)
    case 'days':
      return `${value} days`
    default:
      return value.toString()
  }
}

const formatCurrency = (value: number): string => {
  if (value >= 1000000) {
    return `$${(value / 1000000).toFixed(1)}M`
  } else if (value >= 1000) {
    return `$${(value / 1000).toFixed(1)}K`
  }
  return `$${value.toLocaleString()}`
}

const formatNumber = (value: number): string => {
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`
  } else if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`
  }
  return value.toLocaleString()
}

const formatAlumniCount = (count: number): string => {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}M`
  } else if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}K`
  }
  return count.toString()
}
</script>

<style scoped>
.before-section,
.after-section {
  @apply relative;
}

.before-section::after {
  content: '';
  @apply absolute top-0 right-0 w-px h-full bg-gray-200 hidden md:block;
}

/* Animation for metric cards */
.before-section > div > div,
.after-section > div > div {
  @apply transition-all duration-300 ease-in-out;
}

.before-section > div > div:hover,
.after-section > div > div:hover {
  @apply transform scale-105;
}
</style>