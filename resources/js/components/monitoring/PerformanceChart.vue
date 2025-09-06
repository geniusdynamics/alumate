<!-- ABOUTME: Performance monitoring chart component using Chart.js for data visualization -->
<!-- ABOUTME: Displays performance metrics over time with responsive design and dark mode support -->
<template>
  <div class="performance-chart-container">
    <div class="chart-header">
      <h3 class="chart-title">Performance Over Time</h3>
      <div class="chart-controls">
        <select 
          v-model="selectedTimeRange" 
          @change="updateChart"
          class="time-range-selector"
        >
          <option value="24h">Last 24 Hours</option>
          <option value="7d">Last 7 Days</option>
          <option value="30d">Last 30 Days</option>
          <option value="90d">Last 90 Days</option>
        </select>
      </div>
    </div>
    
    <div class="chart-wrapper">
      <canvas 
        ref="chartCanvas" 
        :id="chartId"
        class="performance-chart"
      ></canvas>
    </div>
    
    <div v-if="loading" class="chart-loading">
      <div class="loading-spinner"></div>
      <span>Loading performance data...</span>
    </div>
    
    <div v-if="error" class="chart-error">
      <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
      </svg>
      <span>{{ error }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

// Register Chart.js components
Chart.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface PerformanceDataPoint {
  timestamp: string
  response_time: number
  cpu_usage: number
  memory_usage: number
  throughput: number
}

interface Props {
  data?: PerformanceDataPoint[]
  height?: number
  refreshInterval?: number
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  height: 400,
  refreshInterval: 30000 // 30 seconds
})

// Refs
const chartCanvas = ref<HTMLCanvasElement>()
const chart = ref<Chart | null>(null)
const loading = ref(false)
const error = ref('')
const selectedTimeRange = ref('24h')

// Computed
const chartId = computed(() => `performance-chart-${Math.random().toString(36).substr(2, 9)}`)

const isDarkMode = computed(() => {
  return document.documentElement.classList.contains('dark')
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index' as const,
    intersect: false,
  },
  plugins: {
    title: {
      display: false
    },
    legend: {
      position: 'top' as const,
      labels: {
        color: isDarkMode.value ? '#e5e7eb' : '#374151',
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      backgroundColor: isDarkMode.value ? '#1f2937' : '#ffffff',
      titleColor: isDarkMode.value ? '#f9fafb' : '#111827',
      bodyColor: isDarkMode.value ? '#e5e7eb' : '#374151',
      borderColor: isDarkMode.value ? '#374151' : '#e5e7eb',
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        label: function(context: any) {
          const label = context.dataset.label || ''
          const value = context.parsed.y
          
          switch (label) {
            case 'Response Time':
              return `${label}: ${value}ms`
            case 'CPU Usage':
            case 'Memory Usage':
              return `${label}: ${value}%`
            case 'Throughput':
              return `${label}: ${value} req/s`
            default:
              return `${label}: ${value}`
          }
        }
      }
    }
  },
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'Time',
        color: isDarkMode.value ? '#9ca3af' : '#6b7280'
      },
      grid: {
        color: isDarkMode.value ? '#374151' : '#f3f4f6'
      },
      ticks: {
        color: isDarkMode.value ? '#9ca3af' : '#6b7280'
      }
    },
    y: {
      display: true,
      title: {
        display: true,
        text: 'Value',
        color: isDarkMode.value ? '#9ca3af' : '#6b7280'
      },
      grid: {
        color: isDarkMode.value ? '#374151' : '#f3f4f6'
      },
      ticks: {
        color: isDarkMode.value ? '#9ca3af' : '#6b7280'
      }
    }
  },
  elements: {
    line: {
      tension: 0.4
    },
    point: {
      radius: 3,
      hoverRadius: 6
    }
  }
}))

const chartData = computed(() => {
  if (!props.data || props.data.length === 0) {
    return {
      labels: [],
      datasets: []
    }
  }

  const labels = props.data.map(point => {
    const date = new Date(point.timestamp)
    return date.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  })

  return {
    labels,
    datasets: [
      {
        label: 'Response Time',
        data: props.data.map(point => point.response_time),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: false,
        yAxisID: 'y'
      },
      {
        label: 'CPU Usage',
        data: props.data.map(point => point.cpu_usage),
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        fill: false,
        yAxisID: 'y'
      },
      {
        label: 'Memory Usage',
        data: props.data.map(point => point.memory_usage),
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
        fill: false,
        yAxisID: 'y'
      },
      {
        label: 'Throughput',
        data: props.data.map(point => point.throughput),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: false,
        yAxisID: 'y'
      }
    ]
  }
})

// Methods
const initChart = async () => {
  if (!chartCanvas.value) return

  try {
    loading.value = true
    error.value = ''

    // Destroy existing chart
    if (chart.value) {
      chart.value.destroy()
    }

    // Create new chart
    chart.value = new Chart(chartCanvas.value, {
      type: 'line',
      data: chartData.value,
      options: chartOptions.value
    })

  } catch (err) {
    console.error('Error initializing chart:', err)
    error.value = 'Failed to initialize performance chart'
  } finally {
    loading.value = false
  }
}

const updateChart = async () => {
  if (!chart.value) {
    await initChart()
    return
  }

  try {
    loading.value = true
    error.value = ''

    // Update chart data
    chart.value.data = chartData.value
    chart.value.options = chartOptions.value
    chart.value.update('active')

  } catch (err) {
    console.error('Error updating chart:', err)
    error.value = 'Failed to update performance chart'
  } finally {
    loading.value = false
  }
}

const handleResize = () => {
  if (chart.value) {
    chart.value.resize()
  }
}

// Watchers
watch(() => props.data, () => {
  updateChart()
}, { deep: true })

watch(isDarkMode, () => {
  updateChart()
})

// Lifecycle
onMounted(async () => {
  await nextTick()
  await initChart()
  
  // Add resize listener
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  if (chart.value) {
    chart.value.destroy()
  }
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.performance-chart-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.chart-header {
  @apply flex items-center justify-between mb-6;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.chart-controls {
  @apply flex items-center gap-3;
}

.time-range-selector {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  @apply transition-colors duration-200;
}

.chart-wrapper {
  @apply relative;
  height: 400px;
}

.performance-chart {
  @apply w-full h-full;
}

.chart-loading {
  @apply absolute inset-0 flex items-center justify-center;
  @apply bg-white dark:bg-gray-800 bg-opacity-90 dark:bg-opacity-90;
  @apply text-gray-600 dark:text-gray-400;
}

.loading-spinner {
  @apply w-6 h-6 border-2 border-gray-300 border-t-blue-600 rounded-full;
  animation: spin 1s linear infinite;
}

.chart-error {
  @apply flex items-center justify-center gap-2 p-4;
  @apply text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20;
  @apply border border-red-200 dark:border-red-800 rounded-lg;
}

.error-icon {
  @apply w-5 h-5;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .chart-header {
    @apply flex-col items-start gap-3;
  }
  
  .chart-wrapper {
    height: 300px;
  }
}

@media (max-width: 640px) {
  .performance-chart-container {
    @apply p-4;
  }
  
  .chart-wrapper {
    height: 250px;
  }
}
</style>