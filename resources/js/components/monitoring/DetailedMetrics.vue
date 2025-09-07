<!-- ABOUTME: Detailed metrics monitoring component displaying comprehensive system performance data -->
<!-- ABOUTME: Shows detailed performance metrics, resource usage, and system health indicators with drill-down capabilities -->
<template>
  <div class="detailed-metrics-container">
    <div class="metrics-header">
      <h3 class="metrics-title">Detailed Metrics</h3>
      <div class="metrics-controls">
        <select 
          v-model="selectedCategory" 
          @change="updateMetrics"
          class="category-selector"
        >
          <option value="performance">Performance</option>
          <option value="resources">Resources</option>
          <option value="database">Database</option>
          <option value="network">Network</option>
          <option value="cache">Cache</option>
        </select>
        <select 
          v-model="selectedTimeRange" 
          @change="updateMetrics"
          class="time-range-selector"
        >
          <option value="1h">Last Hour</option>
          <option value="24h">Last 24 Hours</option>
          <option value="7d">Last 7 Days</option>
          <option value="30d">Last 30 Days</option>
        </select>
      </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
      <div
        v-for="metric in currentMetrics"
        :key="metric.id"
        :class="[
          'metric-card',
          `metric-${metric.status}`,
          { 'metric-clickable': metric.drillDown }
        ]"
        @click="handleMetricClick(metric)"
      >
        <div class="metric-header">
          <div class="metric-icon">
            <component :is="metric.icon" class="icon" />
          </div>
          <div class="metric-trend" v-if="metric.trend">
            <svg 
              v-if="metric.trend.direction === 'up'" 
              :class="['trend-icon', metric.trend.isGood ? 'trend-good' : 'trend-bad']" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
            </svg>
            <svg 
              v-else-if="metric.trend.direction === 'down'" 
              :class="['trend-icon', metric.trend.isGood ? 'trend-good' : 'trend-bad']" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
            </svg>
            <svg 
              v-else 
              class="trend-icon trend-neutral" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
            </svg>
            <span class="trend-value">{{ metric.trend.value }}%</span>
          </div>
        </div>

        <div class="metric-content">
          <div class="metric-value">
            {{ formatMetricValue(metric.value, metric.unit) }}
          </div>
          <div class="metric-label">{{ metric.label }}</div>
          <div v-if="metric.description" class="metric-description">
            {{ metric.description }}
          </div>
        </div>

        <div v-if="metric.threshold" class="metric-threshold">
          <div class="threshold-bar">
            <div 
              class="threshold-fill"
              :style="{ width: `${getThresholdPercentage(metric)}%` }"
              :class="getThresholdColor(metric)"
            ></div>
          </div>
          <div class="threshold-labels">
            <span class="threshold-current">{{ formatMetricValue(metric.value, metric.unit) }}</span>
            <span class="threshold-max">{{ formatMetricValue(metric.threshold.max, metric.unit) }}</span>
          </div>
        </div>

        <div v-if="metric.subMetrics" class="sub-metrics">
          <div 
            v-for="subMetric in metric.subMetrics"
            :key="subMetric.id"
            class="sub-metric"
          >
            <span class="sub-metric-label">{{ subMetric.label }}:</span>
            <span class="sub-metric-value">{{ formatMetricValue(subMetric.value, subMetric.unit) }}</span>
          </div>
        </div>

        <div v-if="metric.lastUpdated" class="metric-updated">
          Last updated: {{ formatTime(metric.lastUpdated) }}
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="metrics-loading">
      <div class="loading-spinner"></div>
      <span>Loading detailed metrics...</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface MetricTrend {
  direction: 'up' | 'down' | 'neutral'
  value: number
  isGood: boolean
}

interface MetricThreshold {
  min: number
  max: number
  warning: number
  critical: number
}

interface SubMetric {
  id: string
  label: string
  value: number
  unit: string
}

interface Metric {
  id: string
  label: string
  value: number
  unit: string
  description?: string
  icon: any
  status: 'healthy' | 'warning' | 'critical'
  trend?: MetricTrend
  threshold?: MetricThreshold
  subMetrics?: SubMetric[]
  lastUpdated: string
  drillDown?: boolean
  category: string
}

interface Props {
  metrics?: Metric[]
  refreshInterval?: number
}

interface Emits {
  'metric-click': [metric: Metric]
  'refresh': []
}

const props = withDefaults(defineProps<Props>(), {
  metrics: () => [],
  refreshInterval: 30000
})

const emit = defineEmits<Emits>()

// Refs
const loading = ref(false)
const selectedCategory = ref('performance')
const selectedTimeRange = ref('24h')

// Sample metrics data
const sampleMetrics = ref<Metric[]>([
  {
    id: 'cpu-usage',
    label: 'CPU Usage',
    value: 45.2,
    unit: 'percent',
    description: 'Current CPU utilization across all cores',
    icon: 'CpuIcon',
    status: 'healthy',
    trend: { direction: 'up', value: 5.2, isGood: false },
    threshold: { min: 0, max: 100, warning: 70, critical: 90 },
    subMetrics: [
      { id: 'cpu-core-1', label: 'Core 1', value: 42, unit: 'percent' },
      { id: 'cpu-core-2', label: 'Core 2', value: 48, unit: 'percent' }
    ],
    lastUpdated: new Date().toISOString(),
    drillDown: true,
    category: 'performance'
  },
  {
    id: 'memory-usage',
    label: 'Memory Usage',
    value: 6.8,
    unit: 'gb',
    description: 'Current RAM utilization',
    icon: 'MemoryIcon',
    status: 'warning',
    trend: { direction: 'up', value: 12.3, isGood: false },
    threshold: { min: 0, max: 16, warning: 12, critical: 14 },
    lastUpdated: new Date().toISOString(),
    drillDown: true,
    category: 'resources'
  },
  {
    id: 'response-time',
    label: 'Response Time',
    value: 245,
    unit: 'ms',
    description: 'Average API response time',
    icon: 'ClockIcon',
    status: 'healthy',
    trend: { direction: 'down', value: 8.1, isGood: true },
    threshold: { min: 0, max: 1000, warning: 500, critical: 800 },
    lastUpdated: new Date().toISOString(),
    drillDown: true,
    category: 'performance'
  },
  {
    id: 'disk-usage',
    label: 'Disk Usage',
    value: 125.4,
    unit: 'gb',
    description: 'Current disk space utilization',
    icon: 'DatabaseIcon',
    status: 'healthy',
    trend: { direction: 'up', value: 2.1, isGood: true },
    threshold: { min: 0, max: 500, warning: 400, critical: 450 },
    lastUpdated: new Date().toISOString(),
    category: 'resources'
  },
  {
    id: 'db-connections',
    label: 'Database Connections',
    value: 23,
    unit: 'count',
    description: 'Active database connections',
    icon: 'DatabaseIcon',
    status: 'healthy',
    trend: { direction: 'neutral', value: 0, isGood: true },
    threshold: { min: 0, max: 100, warning: 80, critical: 95 },
    lastUpdated: new Date().toISOString(),
    category: 'database'
  },
  {
    id: 'cache-hit-rate',
    label: 'Cache Hit Rate',
    value: 94.2,
    unit: 'percent',
    description: 'Percentage of cache hits vs misses',
    icon: 'CacheIcon',
    status: 'healthy',
    trend: { direction: 'up', value: 1.8, isGood: true },
    threshold: { min: 0, max: 100, warning: 80, critical: 70 },
    lastUpdated: new Date().toISOString(),
    category: 'cache'
  }
])

// Computed
const metricsData = computed(() => {
  return props.metrics.length > 0 ? props.metrics : sampleMetrics.value
})

const currentMetrics = computed(() => {
  return metricsData.value.filter(metric => 
    selectedCategory.value === 'all' || metric.category === selectedCategory.value
  )
})

// Methods
const formatMetricValue = (value: number, unit: string): string => {
  switch (unit) {
    case 'percent':
      return `${value.toFixed(1)}%`
    case 'ms':
      return `${value.toFixed(0)}ms`
    case 'gb':
      return `${value.toFixed(1)} GB`
    case 'mb':
      return `${value.toFixed(1)} MB`
    case 'count':
      return value.toString()
    case 'requests':
      return `${value.toLocaleString()} req/s`
    default:
      return value.toString()
  }
}

const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const getThresholdPercentage = (metric: Metric): number => {
  if (!metric.threshold) return 0
  return Math.min((metric.value / metric.threshold.max) * 100, 100)
}

const getThresholdColor = (metric: Metric): string => {
  if (!metric.threshold) return 'bg-gray-300'
  
  if (metric.value >= metric.threshold.critical) return 'bg-red-500'
  if (metric.value >= metric.threshold.warning) return 'bg-yellow-500'
  return 'bg-green-500'
}

const updateMetrics = async () => {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500))
    emit('refresh')
  } finally {
    loading.value = false
  }
}

const handleMetricClick = (metric: Metric) => {
  if (metric.drillDown) {
    emit('metric-click', metric)
  }
}

// Lifecycle
onMounted(() => {
  if (props.refreshInterval > 0) {
    setInterval(updateMetrics, props.refreshInterval)
  }
})
</script>

<style scoped>
.detailed-metrics-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.metrics-header {
  @apply flex items-center justify-between mb-6;
}

.metrics-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.metrics-controls {
  @apply flex items-center gap-3;
}

.category-selector,
.time-range-selector {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.metrics-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.metric-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border;
  @apply transition-all duration-200 ease-in-out;
}

.metric-healthy {
  @apply border-green-200 dark:border-green-800;
}

.metric-warning {
  @apply border-yellow-200 dark:border-yellow-800;
}

.metric-critical {
  @apply border-red-200 dark:border-red-800;
}

.metric-clickable {
  @apply cursor-pointer hover:shadow-md hover:scale-105;
}

.metric-header {
  @apply flex items-start justify-between mb-4;
}

.metric-icon {
  @apply w-10 h-10 rounded-lg flex items-center justify-center;
  @apply bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400;
}

.icon {
  @apply w-5 h-5;
}

.metric-trend {
  @apply flex items-center gap-1;
}

.trend-icon {
  @apply w-4 h-4;
}

.trend-good {
  @apply text-green-600 dark:text-green-400;
}

.trend-bad {
  @apply text-red-600 dark:text-red-400;
}

.trend-neutral {
  @apply text-gray-600 dark:text-gray-400;
}

.trend-value {
  @apply text-xs font-medium;
}

.metric-content {
  @apply mb-4;
}

.metric-value {
  @apply text-2xl font-bold text-gray-900 dark:text-white mb-1;
}

.metric-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.metric-description {
  @apply text-xs text-gray-500 dark:text-gray-400 mt-1;
}

.metric-threshold {
  @apply mb-4;
}

.threshold-bar {
  @apply w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-2;
}

.threshold-fill {
  @apply h-2 rounded-full transition-all duration-300 ease-in-out;
}

.threshold-labels {
  @apply flex justify-between text-xs text-gray-500 dark:text-gray-400;
}

.threshold-current {
  @apply font-medium;
}

.sub-metrics {
  @apply space-y-1 mb-4 p-3 bg-white dark:bg-gray-800 rounded-md;
}

.sub-metric {
  @apply flex justify-between text-xs;
}

.sub-metric-label {
  @apply text-gray-600 dark:text-gray-400;
}

.sub-metric-value {
  @apply font-medium text-gray-900 dark:text-white;
}

.metric-updated {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.metrics-loading {
  @apply flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-400;
}

.loading-spinner {
  @apply w-5 h-5 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin;
}

/* Responsive Design */
@media (max-width: 768px) {
  .metrics-header {
    @apply flex-col items-start gap-3;
  }
  
  .metrics-grid {
    @apply grid-cols-1;
  }
  
  .metric-header {
    @apply flex-col items-start gap-2;
  }
}

@media (max-width: 640px) {
  .detailed-metrics-container {
    @apply p-4;
  }
  
  .metric-card {
    @apply p-3;
  }
}

/* Focus styles for accessibility */
.metric-clickable:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
  @apply ring-offset-white dark:ring-offset-gray-800;
}
</style>