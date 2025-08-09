<template>
  <div class="success-metrics-tracking bg-white rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ title }}</h3>
        <p v-if="subtitle" class="text-gray-600">{{ subtitle }}</p>
      </div>
      <div class="flex items-center space-x-2">
        <button
          @click="refreshMetrics"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          :disabled="isRefreshing"
        >
          <svg 
            class="w-4 h-4 mr-2" 
            :class="{ 'animate-spin': isRefreshing }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Refresh
        </button>
        <div class="text-xs text-gray-500">
          Last updated: {{ formatLastUpdated(lastUpdated) }}
        </div>
      </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div 
        v-for="metric in metrics" 
        :key="metric.id"
        class="metric-card bg-gradient-to-br from-white to-gray-50 rounded-lg p-6 border border-gray-200 hover:border-blue-300 hover:shadow-md transition-all duration-200"
      >
        <!-- Metric Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <div 
              class="w-12 h-12 rounded-lg flex items-center justify-center mr-3"
              :class="getMetricBackgroundColor(metric.category)"
            >
              <component 
                :is="getMetricIcon(metric.category)" 
                class="w-6 h-6"
                :class="getMetricIconColor(metric.category)"
              />
            </div>
            <div>
              <h4 class="font-semibold text-gray-900">{{ metric.name }}</h4>
              <p class="text-xs text-gray-500">{{ metric.category }}</p>
            </div>
          </div>
          <div v-if="metric.trending" class="flex items-center">
            <svg 
              class="w-4 h-4"
              :class="getTrendColor(metric.trend)"
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path 
                v-if="metric.trend === 'up'"
                fill-rule="evenodd" 
                d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" 
                clip-rule="evenodd"
              />
              <path 
                v-else-if="metric.trend === 'down'"
                fill-rule="evenodd" 
                d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" 
                clip-rule="evenodd"
              />
              <path 
                v-else
                fill-rule="evenodd" 
                d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" 
                clip-rule="evenodd"
              />
            </svg>
          </div>
        </div>

        <!-- Current Value -->
        <div class="mb-4">
          <div class="text-3xl font-bold text-gray-900 mb-1">
            {{ formatMetricValue(metric.currentValue, metric.unit) }}
          </div>
          <div class="text-sm text-gray-600">Current Value</div>
        </div>

        <!-- Progress Indicator -->
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-600">Progress to Goal</span>
            <span class="text-sm font-medium text-gray-900">
              {{ Math.round((metric.currentValue / metric.targetValue) * 100) }}%
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="h-2 rounded-full transition-all duration-1000 ease-out"
              :class="getProgressBarColor(metric.category)"
              :style="{ width: `${Math.min(100, (metric.currentValue / metric.targetValue) * 100)}%` }"
            ></div>
          </div>
        </div>

        <!-- Target and Change -->
        <div class="flex items-center justify-between text-sm">
          <div>
            <span class="text-gray-600">Target: </span>
            <span class="font-medium text-gray-900">
              {{ formatMetricValue(metric.targetValue, metric.unit) }}
            </span>
          </div>
          <div v-if="metric.changeFromPrevious" class="flex items-center">
            <span 
              class="font-medium"
              :class="getChangeColor(metric.changeFromPrevious)"
            >
              {{ metric.changeFromPrevious > 0 ? '+' : '' }}{{ metric.changeFromPrevious }}%
            </span>
          </div>
        </div>

        <!-- Verification Badge -->
        <div v-if="metric.verified" class="mt-3 flex items-center text-green-600 text-xs">
          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          Verified Data
        </div>
      </div>
    </div>

    <!-- Performance Summary -->
    <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg p-6 border border-blue-200">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600 mb-1">{{ metricsOnTrack }}</div>
          <div class="text-sm text-gray-600">On Track</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600 mb-1">{{ metricsExceeding }}</div>
          <div class="text-sm text-gray-600">Exceeding Goals</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-600 mb-1">{{ metricsBehind }}</div>
          <div class="text-sm text-gray-600">Behind Target</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600 mb-1">{{ averageProgress }}%</div>
          <div class="text-sm text-gray-600">Avg Progress</div>
        </div>
      </div>
    </div>

    <!-- Insights -->
    <div v-if="insights.length > 0" class="mt-6">
      <h4 class="font-semibold text-gray-900 mb-3">Key Insights</h4>
      <div class="space-y-2">
        <div 
          v-for="insight in insights" 
          :key="insight.id"
          class="flex items-start p-3 bg-yellow-50 rounded-lg border border-yellow-200"
        >
          <svg class="w-5 h-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ insight.title }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ insight.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface SuccessMetric {
  id: string
  name: string
  category: 'engagement' | 'financial' | 'operational' | 'growth'
  currentValue: number
  targetValue: number
  unit: 'percentage' | 'count' | 'currency' | 'days'
  trend: 'up' | 'down' | 'stable'
  trending: boolean
  changeFromPrevious?: number
  verified: boolean
}

interface Insight {
  id: string
  title: string
  description: string
  type: 'positive' | 'warning' | 'info'
}

interface Props {
  title: string
  subtitle?: string
  metrics: SuccessMetric[]
  insights: Insight[]
  lastUpdated: Date
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'refresh-metrics': []
}>()

const isRefreshing = ref(false)

const metricsOnTrack = computed(() => 
  props.metrics.filter(m => {
    const progress = (m.currentValue / m.targetValue) * 100
    return progress >= 80 && progress < 100
  }).length
)

const metricsExceeding = computed(() => 
  props.metrics.filter(m => (m.currentValue / m.targetValue) * 100 >= 100).length
)

const metricsBehind = computed(() => 
  props.metrics.filter(m => (m.currentValue / m.targetValue) * 100 < 80).length
)

const averageProgress = computed(() => {
  if (props.metrics.length === 0) return 0
  const totalProgress = props.metrics.reduce((sum, metric) => 
    sum + Math.min(100, (metric.currentValue / metric.targetValue) * 100), 0
  )
  return Math.round(totalProgress / props.metrics.length)
})

const refreshMetrics = async () => {
  isRefreshing.value = true
  emit('refresh-metrics')
  
  // Simulate API call
  setTimeout(() => {
    isRefreshing.value = false
  }, 2000)
}

const getMetricBackgroundColor = (category: string): string => {
  const colors = {
    engagement: 'bg-blue-100',
    financial: 'bg-green-100',
    operational: 'bg-purple-100',
    growth: 'bg-orange-100'
  }
  return colors[category as keyof typeof colors] || 'bg-gray-100'
}

const getMetricIconColor = (category: string): string => {
  const colors = {
    engagement: 'text-blue-600',
    financial: 'text-green-600',
    operational: 'text-purple-600',
    growth: 'text-orange-600'
  }
  return colors[category as keyof typeof colors] || 'text-gray-600'
}

const getProgressBarColor = (category: string): string => {
  const colors = {
    engagement: 'bg-blue-500',
    financial: 'bg-green-500',
    operational: 'bg-purple-500',
    growth: 'bg-orange-500'
  }
  return colors[category as keyof typeof colors] || 'bg-gray-500'
}

const getTrendColor = (trend: string): string => {
  const colors = {
    up: 'text-green-500',
    down: 'text-red-500',
    stable: 'text-gray-500'
  }
  return colors[trend as keyof typeof colors] || 'text-gray-500'
}

const getChangeColor = (change: number): string => {
  if (change > 0) return 'text-green-600'
  if (change < 0) return 'text-red-600'
  return 'text-gray-600'
}

const getMetricIcon = (category: string) => {
  // Return appropriate icon component based on category
  return 'svg' // Placeholder
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

const formatLastUpdated = (date: Date): string => {
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`
  return date.toLocaleDateString()
}
</script>

<style scoped>
.metric-card {
  @apply transition-all duration-300 ease-in-out;
}

.metric-card:hover {
  @apply transform -translate-y-1;
}

/* Animate progress bars on load */
@keyframes fillProgress {
  from { width: 0%; }
  to { width: var(--progress-width); }
}
</style>