<template>
  <div class="enterprise-metrics-visualization bg-white rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="mb-6">
      <h3 class="text-xl font-bold text-gray-900 mb-2">{{ title }}</h3>
      <p v-if="subtitle" class="text-gray-600">{{ subtitle }}</p>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div 
        v-for="metric in metrics" 
        :key="metric.id"
        class="metric-card bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-blue-300 transition-colors duration-200"
      >
        <!-- Metric Header -->
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center">
            <div 
              class="w-10 h-10 rounded-full flex items-center justify-center mr-3"
              :class="getMetricIconBg(metric.category)"
            >
              <component 
                :is="getMetricIcon(metric.category)" 
                class="w-5 h-5"
                :class="getMetricIconColor(metric.category)"
              />
            </div>
            <div>
              <h4 class="font-semibold text-gray-900 text-sm">{{ metric.name }}</h4>
              <p class="text-xs text-gray-500">{{ metric.timeframe }}</p>
            </div>
          </div>
          <div v-if="metric.verified" class="text-green-500">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>

        <!-- Before/After Comparison -->
        <div class="space-y-3">
          <!-- Before Value -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Before:</span>
            <span class="font-medium text-gray-900">
              {{ formatMetricValue(metric.beforeValue, metric.unit) }}
            </span>
          </div>

          <!-- Progress Bar -->
          <div class="relative">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-1000 ease-out"
                :style="{ width: `${Math.min(100, (metric.afterValue / Math.max(metric.beforeValue, metric.afterValue)) * 100)}%` }"
              ></div>
            </div>
            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
          </div>

          <!-- After Value -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">After:</span>
            <span class="font-bold text-green-600">
              {{ formatMetricValue(metric.afterValue, metric.unit) }}
            </span>
          </div>

          <!-- Improvement Badge -->
          <div class="flex items-center justify-center">
            <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
              </svg>
              +{{ metric.improvementPercentage }}%
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Statistics -->
    <div v-if="showSummary" class="border-t border-gray-200 pt-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 mb-1">
            {{ averageImprovement }}%
          </div>
          <div class="text-sm text-gray-600">Average Improvement</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 mb-1">
            {{ totalMetrics }}
          </div>
          <div class="text-sm text-gray-600">Metrics Tracked</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-600 mb-1">
            {{ verifiedMetrics }}
          </div>
          <div class="text-sm text-gray-600">Verified Results</div>
        </div>
      </div>
    </div>

    <!-- ROI Highlight -->
    <div v-if="roiData" class="mt-6 bg-gradient-to-r from-blue-50 to-green-50 rounded-lg p-4 border border-blue-200">
      <div class="flex items-center justify-between">
        <div>
          <h4 class="font-semibold text-gray-900 mb-1">Return on Investment</h4>
          <p class="text-sm text-gray-600">{{ roiData.timeframe }} implementation period</p>
        </div>
        <div class="text-right">
          <div class="text-2xl font-bold text-green-600">{{ roiData.percentage }}%</div>
          <div class="text-sm text-gray-600">ROI</div>
        </div>
      </div>
      <div class="mt-3 text-sm text-gray-700">
        <strong>Investment:</strong> {{ formatCurrency(roiData.investment) }} | 
        <strong>Return:</strong> {{ formatCurrency(roiData.return) }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { InstitutionalResult } from '@/types/homepage'

interface EnterpriseMetric extends InstitutionalResult {
  id: string
  name: string
  category: 'engagement' | 'financial' | 'operational' | 'growth'
  unit: 'percentage' | 'count' | 'currency' | 'days'
}

interface ROIData {
  percentage: number
  investment: number
  return: number
  timeframe: string
}

interface Props {
  title: string
  subtitle?: string
  metrics: EnterpriseMetric[]
  showSummary?: boolean
  roiData?: ROIData
}

const props = withDefaults(defineProps<Props>(), {
  showSummary: true
})

const averageImprovement = computed(() => {
  if (props.metrics.length === 0) return 0
  const total = props.metrics.reduce((sum, metric) => sum + metric.improvementPercentage, 0)
  return Math.round(total / props.metrics.length)
})

const totalMetrics = computed(() => props.metrics.length)

const verifiedMetrics = computed(() => 
  props.metrics.filter(metric => metric.verified).length
)

const getMetricIcon = (category: string) => {
  const icons = {
    engagement: 'svg',
    financial: 'svg', 
    operational: 'svg',
    growth: 'svg'
  }
  return icons[category as keyof typeof icons] || 'svg'
}

const getMetricIconBg = (category: string) => {
  const backgrounds = {
    engagement: 'bg-blue-100',
    financial: 'bg-green-100',
    operational: 'bg-purple-100',
    growth: 'bg-orange-100'
  }
  return backgrounds[category as keyof typeof backgrounds] || 'bg-gray-100'
}

const getMetricIconColor = (category: string) => {
  const colors = {
    engagement: 'text-blue-600',
    financial: 'text-green-600',
    operational: 'text-purple-600',
    growth: 'text-orange-600'
  }
  return colors[category as keyof typeof colors] || 'text-gray-600'
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
</script>

<style scoped>
.metric-card {
  @apply transition-all duration-200;
}

.metric-card:hover {
  @apply transform -translate-y-1 shadow-md;
}

/* Animation for progress bars */
@keyframes fillProgress {
  from {
    width: 0%;
  }
  to {
    width: var(--progress-width);
  }
}
</style>