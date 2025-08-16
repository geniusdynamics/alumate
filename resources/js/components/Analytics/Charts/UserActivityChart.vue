<template>
  <div class="user-activity-chart">
    <div class="chart-header">
      <h4 class="chart-title">Daily Active Users</h4>
      <div class="chart-period">
        <select v-model="selectedPeriod" @change="updateChart" class="period-select">
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
      </div>
    </div>
    
    <div class="chart-container">
      <canvas ref="chartCanvas" class="chart-canvas"></canvas>
    </div>
    
    <div class="chart-stats">
      <div class="stat-item">
        <span class="stat-label">Peak Day</span>
        <span class="stat-value">{{ peakDay }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Average</span>
        <span class="stat-value">{{ averageUsers }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Trend</span>
        <span class="stat-value" :class="trendClass">{{ trendText }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'

interface ActivityData {
  date: string
  count: number
}

interface Props {
  data: ActivityData[]
}

const props = defineProps<Props>()

const chartCanvas = ref<HTMLCanvasElement>()
const selectedPeriod = ref(30)

const processedData = computed(() => {
  if (!props.data || !Array.isArray(props.data)) {
    return []
  }
  
  return props.data.slice(-selectedPeriod.value).map(item => ({
    date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    count: item.count || 0
  }))
})

const peakDay = computed(() => {
  if (processedData.value.length === 0) return 'N/A'
  
  const peak = processedData.value.reduce((max, current) => 
    current.count > max.count ? current : max
  )
  return peak.count.toLocaleString()
})

const averageUsers = computed(() => {
  if (processedData.value.length === 0) return 'N/A'
  
  const total = processedData.value.reduce((sum, item) => sum + item.count, 0)
  return Math.round(total / processedData.value.length).toLocaleString()
})

const trendText = computed(() => {
  if (processedData.value.length < 2) return 'N/A'
  
  const recent = processedData.value.slice(-7)
  const previous = processedData.value.slice(-14, -7)
  
  if (recent.length === 0 || previous.length === 0) return 'N/A'
  
  const recentAvg = recent.reduce((sum, item) => sum + item.count, 0) / recent.length
  const previousAvg = previous.reduce((sum, item) => sum + item.count, 0) / previous.length
  
  const change = ((recentAvg - previousAvg) / previousAvg) * 100
  
  if (Math.abs(change) < 5) return 'Stable'
  return change > 0 ? `+${change.toFixed(1)}%` : `${change.toFixed(1)}%`
})

const trendClass = computed(() => {
  if (trendText.value === 'N/A' || trendText.value === 'Stable') return 'text-gray-600'
  return trendText.value.startsWith('+') ? 'text-green-600' : 'text-red-600'
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
  const minValue = Math.min(...data.map(d => d.count), 0)
  const valueRange = maxValue - minValue || 1
  
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
    const value = Math.round(maxValue - (maxValue / 5) * i)
    ctx.fillStyle = '#6B7280'
    ctx.font = '10px sans-serif'
    ctx.textAlign = 'right'
    ctx.fillText(value.toString(), padding - 10, y + 3)
  }
  
  // Draw line chart
  if (data.length > 1) {
    ctx.strokeStyle = '#3B82F6'
    ctx.lineWidth = 2
    ctx.beginPath()
    
    data.forEach((point, index) => {
      const x = padding + (index / (data.length - 1)) * chartWidth
      const y = padding + chartHeight - ((point.count - minValue) / valueRange) * chartHeight
      
      if (index === 0) {
        ctx.moveTo(x, y)
      } else {
        ctx.lineTo(x, y)
      }
    })
    
    ctx.stroke()
    
    // Draw points
    ctx.fillStyle = '#3B82F6'
    data.forEach((point, index) => {
      const x = padding + (index / (data.length - 1)) * chartWidth
      const y = padding + chartHeight - ((point.count - minValue) / valueRange) * chartHeight
      
      ctx.beginPath()
      ctx.arc(x, y, 3, 0, 2 * Math.PI)
      ctx.fill()
    })
  }
  
  // Draw x-axis labels
  ctx.fillStyle = '#6B7280'
  ctx.font = '10px sans-serif'
  ctx.textAlign = 'center'
  
  const labelStep = Math.max(1, Math.floor(data.length / 6))
  data.forEach((point, index) => {
    if (index % labelStep === 0 || index === data.length - 1) {
      const x = padding + (index / (data.length - 1)) * chartWidth
      ctx.fillText(point.date, x, padding + chartHeight + 20)
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

watch(selectedPeriod, () => {
  updateChart()
})
</script>

<style scoped>
.user-activity-chart {
  @apply w-full h-full;
}

.chart-header {
  @apply flex items-center justify-between mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.period-select {
  @apply px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.chart-container {
  @apply relative h-64 mb-4;
}

.chart-canvas {
  @apply w-full h-full;
}

.chart-stats {
  @apply grid grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-600;
}

.stat-item {
  @apply text-center;
}

.stat-label {
  @apply block text-xs text-gray-600 dark:text-gray-400 mb-1;
}

.stat-value {
  @apply block text-lg font-semibold text-gray-900 dark:text-white;
}
</style>