<!-- ABOUTME: Traffic trends monitoring component displaying user activity and traffic patterns -->
<!-- ABOUTME: Shows traffic metrics, user engagement trends, and activity patterns with interactive charts -->
<template>
  <div class="traffic-trends-container">
    <div class="trends-header">
      <h3 class="trends-title">Traffic Trends</h3>
      <div class="trends-controls">
        <select 
          v-model="selectedMetric" 
          @change="updateChart"
          class="metric-selector"
        >
          <option value="pageviews">Page Views</option>
          <option value="sessions">Sessions</option>
          <option value="users">Unique Users</option>
          <option value="bounce_rate">Bounce Rate</option>
        </select>
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

    <!-- Traffic Summary Cards -->
    <div class="traffic-summary">
      <div class="summary-card">
        <div class="card-icon pageviews">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ formatNumber(trafficData.pageviews) }}</div>
          <div class="card-label">Page Views</div>
          <div class="card-change" :class="trafficData.pageviewsChange >= 0 ? 'positive' : 'negative'">
            {{ trafficData.pageviewsChange >= 0 ? '+' : '' }}{{ trafficData.pageviewsChange }}%
          </div>
        </div>
      </div>

      <div class="summary-card">
        <div class="card-icon sessions">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ formatNumber(trafficData.sessions) }}</div>
          <div class="card-label">Sessions</div>
          <div class="card-change" :class="trafficData.sessionsChange >= 0 ? 'positive' : 'negative'">
            {{ trafficData.sessionsChange >= 0 ? '+' : '' }}{{ trafficData.sessionsChange }}%
          </div>
        </div>
      </div>

      <div class="summary-card">
        <div class="card-icon users">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ formatNumber(trafficData.users) }}</div>
          <div class="card-label">Unique Users</div>
          <div class="card-change" :class="trafficData.usersChange >= 0 ? 'positive' : 'negative'">
            {{ trafficData.usersChange >= 0 ? '+' : '' }}{{ trafficData.usersChange }}%
          </div>
        </div>
      </div>

      <div class="summary-card">
        <div class="card-icon bounce">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ trafficData.bounceRate }}%</div>
          <div class="card-label">Bounce Rate</div>
          <div class="card-change" :class="trafficData.bounceRateChange <= 0 ? 'positive' : 'negative'">
            {{ trafficData.bounceRateChange >= 0 ? '+' : '' }}{{ trafficData.bounceRateChange }}%
          </div>
        </div>
      </div>
    </div>

    <!-- Traffic Chart -->
    <div class="chart-wrapper">
      <canvas 
        ref="chartCanvas" 
        :id="chartId"
        class="traffic-chart"
      ></canvas>
    </div>

    <!-- Top Pages -->
    <div class="top-pages">
      <h4 class="section-title">Top Pages</h4>
      <div class="pages-list">
        <div 
          v-for="page in topPages" 
          :key="page.path"
          class="page-item"
        >
          <div class="page-info">
            <div class="page-path">{{ page.path }}</div>
            <div class="page-title">{{ page.title }}</div>
          </div>
          <div class="page-stats">
            <div class="stat-item">
              <span class="stat-value">{{ formatNumber(page.views) }}</span>
              <span class="stat-label">views</span>
            </div>
            <div class="stat-item">
              <span class="stat-value">{{ page.avgTime }}s</span>
              <span class="stat-label">avg time</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="trends-loading">
      <div class="loading-spinner"></div>
      <span>Loading traffic data...</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
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

interface TrafficDataPoint {
  timestamp: string
  pageviews: number
  sessions: number
  users: number
  bounce_rate: number
}

interface TopPage {
  path: string
  title: string
  views: number
  avgTime: number
}

interface Props {
  data?: TrafficDataPoint[]
  height?: number
  refreshInterval?: number
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  height: 350,
  refreshInterval: 30000
})

// Refs
const chartCanvas = ref<HTMLCanvasElement>()
const chart = ref<Chart | null>(null)
const loading = ref(false)
const selectedMetric = ref('pageviews')
const selectedTimeRange = ref('24h')

// Sample data
const trafficData = ref({
  pageviews: 45230,
  pageviewsChange: 12.5,
  sessions: 18940,
  sessionsChange: 8.3,
  users: 12450,
  usersChange: 15.2,
  bounceRate: 42.3,
  bounceRateChange: -2.1
})

const topPages = ref<TopPage[]>([
  { path: '/', title: 'Homepage', views: 12450, avgTime: 145 },
  { path: '/dashboard', title: 'Dashboard', views: 8930, avgTime: 320 },
  { path: '/profile', title: 'User Profile', views: 6720, avgTime: 180 },
  { path: '/settings', title: 'Settings', views: 4560, avgTime: 95 },
  { path: '/help', title: 'Help Center', views: 3240, avgTime: 210 }
])

// Computed
const chartId = computed(() => `traffic-chart-${Math.random().toString(36).substr(2, 9)}`)

const isDarkMode = computed(() => {
  return document.documentElement.classList.contains('dark')
})

const chartData = computed(() => {
  if (!props.data || props.data.length === 0) {
    // Generate sample data
    const sampleData = generateSampleData()
    return createChartData(sampleData)
  }
  return createChartData(props.data)
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
      display: false
    },
    tooltip: {
      backgroundColor: isDarkMode.value ? '#1f2937' : '#ffffff',
      titleColor: isDarkMode.value ? '#f9fafb' : '#111827',
      bodyColor: isDarkMode.value ? '#e5e7eb' : '#374151',
      borderColor: isDarkMode.value ? '#374151' : '#e5e7eb',
      borderWidth: 1,
      cornerRadius: 8,
      callbacks: {
        label: function(context: any) {
          const value = context.parsed.y
          switch (selectedMetric.value) {
            case 'bounce_rate':
              return `${value}%`
            default:
              return formatNumber(value)
          }
        }
      }
    }
  },
  scales: {
    x: {
      display: true,
      grid: {
        color: isDarkMode.value ? '#374151' : '#f3f4f6'
      },
      ticks: {
        color: isDarkMode.value ? '#9ca3af' : '#6b7280'
      }
    },
    y: {
      display: true,
      grid: {
        color: isDarkMode.value ? '#374151' : '#f3f4f6'
      },
      ticks: {
        color: isDarkMode.value ? '#9ca3af' : '#6b7280',
        callback: function(value: any) {
          if (selectedMetric.value === 'bounce_rate') {
            return `${value}%`
          }
          return formatNumber(value)
        }
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

// Methods
const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const generateSampleData = (): TrafficDataPoint[] => {
  const data: TrafficDataPoint[] = []
  const now = new Date()
  
  for (let i = 23; i >= 0; i--) {
    const timestamp = new Date(now.getTime() - i * 60 * 60 * 1000)
    data.push({
      timestamp: timestamp.toISOString(),
      pageviews: Math.floor(Math.random() * 2000) + 1000,
      sessions: Math.floor(Math.random() * 800) + 400,
      users: Math.floor(Math.random() * 600) + 300,
      bounce_rate: Math.floor(Math.random() * 20) + 35
    })
  }
  
  return data
}

const createChartData = (data: TrafficDataPoint[]) => {
  const labels = data.map(point => {
    const date = new Date(point.timestamp)
    return date.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  })

  const values = data.map(point => {
    switch (selectedMetric.value) {
      case 'pageviews':
        return point.pageviews
      case 'sessions':
        return point.sessions
      case 'users':
        return point.users
      case 'bounce_rate':
        return point.bounce_rate
      default:
        return point.pageviews
    }
  })

  return {
    labels,
    datasets: [
      {
        data: values,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true
      }
    ]
  }
}

const updateChart = async () => {
  if (!chart.value) {
    await initChart()
    return
  }

  loading.value = true
  
  try {
    chart.value.data = chartData.value
    chart.value.options = chartOptions.value
    chart.value.update('active')
  } finally {
    loading.value = false
  }
}

const initChart = async () => {
  if (!chartCanvas.value) return

  try {
    loading.value = true

    if (chart.value) {
      chart.value.destroy()
    }

    chart.value = new Chart(chartCanvas.value, {
      type: 'line',
      data: chartData.value,
      options: chartOptions.value
    })
  } finally {
    loading.value = false
  }
}

const handleResize = () => {
  if (chart.value) {
    chart.value.resize()
  }
}

// Lifecycle
onMounted(async () => {
  await nextTick()
  await initChart()
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
.traffic-trends-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.trends-header {
  @apply flex items-center justify-between mb-6;
}

.trends-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.trends-controls {
  @apply flex items-center gap-3;
}

.metric-selector,
.time-range-selector {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.traffic-summary {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6;
}

.summary-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4 flex items-center gap-4;
}

.card-icon {
  @apply w-12 h-12 rounded-lg flex items-center justify-center;
}

.card-icon.pageviews {
  @apply bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400;
}

.card-icon.sessions {
  @apply bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400;
}

.card-icon.users {
  @apply bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400;
}

.card-icon.bounce {
  @apply bg-orange-100 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400;
}

.card-icon svg {
  @apply w-6 h-6;
}

.card-content {
  @apply flex-grow;
}

.card-value {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.card-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.card-change {
  @apply text-sm font-medium;
}

.card-change.positive {
  @apply text-green-600 dark:text-green-400;
}

.card-change.negative {
  @apply text-red-600 dark:text-red-400;
}

.chart-wrapper {
  @apply relative mb-6;
  height: 350px;
}

.traffic-chart {
  @apply w-full h-full;
}

.top-pages {
  @apply space-y-4;
}

.section-title {
  @apply text-base font-medium text-gray-900 dark:text-white mb-4;
}

.pages-list {
  @apply space-y-3;
}

.page-item {
  @apply flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.page-info {
  @apply flex-grow;
}

.page-path {
  @apply font-medium text-gray-900 dark:text-white;
}

.page-title {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.page-stats {
  @apply flex items-center gap-4;
}

.stat-item {
  @apply text-center;
}

.stat-value {
  @apply block font-medium text-gray-900 dark:text-white;
}

.stat-label {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.trends-loading {
  @apply flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-400;
}

.loading-spinner {
  @apply w-5 h-5 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin;
}

/* Responsive Design */
@media (max-width: 768px) {
  .trends-header {
    @apply flex-col items-start gap-3;
  }
  
  .traffic-summary {
    @apply grid-cols-1;
  }
  
  .chart-wrapper {
    height: 300px;
  }
  
  .page-item {
    @apply flex-col items-start gap-2;
  }
  
  .page-stats {
    @apply w-full justify-between;
  }
}

@media (max-width: 640px) {
  .traffic-trends-container {
    @apply p-4;
  }
  
  .chart-wrapper {
    height: 250px;
  }
}
</style>