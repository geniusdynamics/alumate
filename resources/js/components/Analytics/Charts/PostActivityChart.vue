<template>
  <div class="post-activity-chart">
    <div class="chart-header">
      <h4 class="chart-title">Post Activity</h4>
      <div class="chart-controls">
        <button
          v-for="view in viewOptions"
          :key="view.key"
          @click="selectedView = view.key"
          class="view-button"
          :class="{ 'active': selectedView === view.key }"
        >
          {{ view.label }}
        </button>
      </div>
    </div>
    
    <div class="chart-container">
      <canvas ref="chartCanvas" class="chart-canvas"></canvas>
    </div>
    
    <div class="chart-summary">
      <div class="summary-item">
        <span class="summary-label">Total Posts</span>
        <span class="summary-value">{{ totalPosts }}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Daily Average</span>
        <span class="summary-value">{{ dailyAverage }}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Most Active Day</span>
        <span class="summary-value">{{ mostActiveDay }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'

interface PostData {
  date: string
  count: number
}

interface Props {
  data: PostData[]
}

const props = defineProps<Props>()

const chartCanvas = ref<HTMLCanvasElement>()
const selectedView = ref('daily')

const viewOptions = [
  { key: 'daily', label: 'Daily' },
  { key: 'weekly', label: 'Weekly' },
  { key: 'monthly', label: 'Monthly' },
]

const processedData = computed(() => {
  if (!props.data || !Array.isArray(props.data)) {
    return []
  }
  
  let data = [...props.data]
  
  if (selectedView.value === 'weekly') {
    // Group by week
    const weeklyData: { [key: string]: number } = {}
    data.forEach(item => {
      const date = new Date(item.date)
      const weekStart = new Date(date.setDate(date.getDate() - date.getDay()))
      const weekKey = weekStart.toISOString().split('T')[0]
      weeklyData[weekKey] = (weeklyData[weekKey] || 0) + item.count
    })
    
    return Object.entries(weeklyData).map(([date, count]) => ({
      date: new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
      count
    }))
  } else if (selectedView.value === 'monthly') {
    // Group by month
    const monthlyData: { [key: string]: number } = {}
    data.forEach(item => {
      const date = new Date(item.date)
      const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
      monthlyData[monthKey] = (monthlyData[monthKey] || 0) + item.count
    })
    
    return Object.entries(monthlyData).map(([date, count]) => ({
      date: new Date(date + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
      count
    }))
  }
  
  // Daily view (default)
  return data.map(item => ({
    date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    count: item.count || 0
  }))
})

const totalPosts = computed(() => {
  return processedData.value.reduce((sum, item) => sum + item.count, 0).toLocaleString()
})

const dailyAverage = computed(() => {
  if (processedData.value.length === 0) return '0'
  const total = processedData.value.reduce((sum, item) => sum + item.count, 0)
  return Math.round(total / processedData.value.length).toLocaleString()
})

const mostActiveDay = computed(() => {
  if (processedData.value.length === 0) return 'N/A'
  
  const peak = processedData.value.reduce((max, current) => 
    current.count > max.count ? current : max
  )
  return peak.date
})

const drawChart = async () => {
  if (!chartCanvas.value || processedData.value.length === 0) return
  
  const ctx = chartCanvas.value.getContext('2d')
  if (!ctx) return
  
  // Clear canvas
  ctx.clearRect(0, 0, chartCanvas.value.width, chartCanvas.value.height)
  
  // Set canvas size
  const rect = chartCanvas.value.getBoundingClientRect()
  chartCanvas.value.width = rect.width * window.devicePixelRatio
  chartCanvas.value.height = rect.height * window.devicePixelRatio
  ctx.scale(window.devicePixelRatio, window.devicePixelRatio)
  
  // Chart dimensions
  const width = rect.width
  const height = rect.height
  const padding = 40
  const chartWidth = width - padding * 2
  const chartHeight = height - padding * 2
  
  const data = processedData.value
  const maxValue = Math.max(...data.map(d => d.count), 1)
  
  // Draw grid lines
  ctx.strokeStyle = '#E5E7EB'
  ctx.lineWidth = 1
  
  for (let i = 0; i <= 5; i++) {
    const y = padding + (chartHeight / 5) * i
    ctx.beginPath()
    ctx.moveTo(padding, y)
    ctx.lineTo(padding + chartWidth, y)
    ctx.stroke()
    
    // Y-axis labels
    const value = Math.round((maxValue / 5) * (5 - i))
    ctx.fillStyle = '#6B7280'
    ctx.font = '10px sans-serif'
    ctx.textAlign = 'right'
    ctx.fillText(value.toString(), padding - 10, y + 3)
  }
  
  // Draw bars
  const barWidth = Math.max(8, chartWidth / data.length * 0.6)
  const barSpacing = chartWidth / data.length
  
  data.forEach((item, index) => {
    const barHeight = (item.count / maxValue) * chartHeight
    const x = padding + index * barSpacing + (barSpacing - barWidth) / 2
    const y = padding + chartHeight - barHeight
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, y, 0, y + barHeight)
    gradient.addColorStop(0, '#3B82F6')
    gradient.addColorStop(1, '#1E40AF')
    
    // Draw bar
    ctx.fillStyle = gradient
    ctx.fillRect(x, y, barWidth, barHeight)
    
    // Draw value on top of bar if there's space
    if (barHeight > 20) {
      ctx.fillStyle = '#FFFFFF'
      ctx.font = '10px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(item.count.toString(), x + barWidth / 2, y + 15)
    }
  })
  
  // Draw x-axis labels
  ctx.fillStyle = '#6B7280'
  ctx.font = '10px sans-serif'
  ctx.textAlign = 'center'
  
  const labelStep = Math.max(1, Math.floor(data.length / 8))
  data.forEach((item, index) => {
    if (index % labelStep === 0 || index === data.length - 1) {
      const x = padding + index * barSpacing + barSpacing / 2
      ctx.fillText(item.date, x, padding + chartHeight + 20)
    }
  })
}

const updateChart = () => {
  nextTick(() => {
    drawChart()
  })
}

onMounted(() => {
  nextTick(() => {
    drawChart()
  })
})

watch(() => props.data, () => {
  updateChart()
}, { deep: true })

watch(selectedView, () => {
  updateChart()
})
</script>

<style scoped>
.post-activity-chart {
  @apply w-full h-full;
}

.chart-header {
  @apply flex items-center justify-between mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.chart-controls {
  @apply flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1;
}

.view-button {
  @apply px-3 py-1 text-sm font-medium rounded-md transition-colors;
  @apply text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.view-button.active {
  @apply bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm;
}

.chart-container {
  @apply relative h-64 mb-4;
}

.chart-canvas {
  @apply w-full h-full;
}

.chart-summary {
  @apply grid grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-600;
}

.summary-item {
  @apply text-center;
}

.summary-label {
  @apply block text-xs text-gray-600 dark:text-gray-400 mb-1;
}

.summary-value {
  @apply block text-lg font-semibold text-gray-900 dark:text-white;
}
</style>