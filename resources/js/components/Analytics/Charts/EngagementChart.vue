<template>
  <div class="engagement-chart">
    <div class="chart-header">
      <h4 class="chart-title">Engagement Overview</h4>
      <div class="chart-legend">
        <div class="legend-item">
          <div class="legend-color bg-blue-500"></div>
          <span class="legend-label">Posts</span>
        </div>
        <div class="legend-item">
          <div class="legend-color bg-green-500"></div>
          <span class="legend-label">Engagements</span>
        </div>
        <div class="legend-item">
          <div class="legend-color bg-purple-500"></div>
          <span class="legend-label">Connections</span>
        </div>
      </div>
    </div>
    
    <div class="chart-container">
      <canvas ref="chartCanvas" class="chart-canvas"></canvas>
    </div>
    
    <div class="chart-stats">
      <div class="stat-item">
        <span class="stat-label">Total Posts</span>
        <span class="stat-value">{{ data.posts_created || 0 }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Engagement Rate</span>
        <span class="stat-value">{{ (data.engagement_rate || 0).toFixed(1) }}%</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">New Connections</span>
        <span class="stat-value">{{ data.connections_made || 0 }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'

interface Props {
  data: {
    posts_created?: number
    engagement_rate?: number
    connections_made?: number
    total_users?: number
    active_users?: number
    new_users?: number
  }
}

const props = defineProps<Props>()

const chartCanvas = ref<HTMLCanvasElement>()

const drawChart = async () => {
  if (!chartCanvas.value) return
  
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
  
  // Data for visualization
  const metrics = [
    { label: 'Posts', value: props.data.posts_created || 0, color: '#3B82F6' },
    { label: 'Active Users', value: props.data.active_users || 0, color: '#10B981' },
    { label: 'New Users', value: props.data.new_users || 0, color: '#8B5CF6' },
  ]
  
  const maxValue = Math.max(...metrics.map(m => m.value), 1)
  
  // Draw bars
  const barWidth = chartWidth / metrics.length * 0.6
  const barSpacing = chartWidth / metrics.length
  
  metrics.forEach((metric, index) => {
    const barHeight = (metric.value / maxValue) * chartHeight
    const x = padding + index * barSpacing + (barSpacing - barWidth) / 2
    const y = padding + chartHeight - barHeight
    
    // Draw bar
    ctx.fillStyle = metric.color
    ctx.fillRect(x, y, barWidth, barHeight)
    
    // Draw value label
    ctx.fillStyle = '#374151'
    ctx.font = '12px sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(metric.value.toString(), x + barWidth / 2, y - 5)
    
    // Draw metric label
    ctx.fillText(metric.label, x + barWidth / 2, padding + chartHeight + 20)
  })
  
  // Draw grid lines
  ctx.strokeStyle = '#E5E7EB'
  ctx.lineWidth = 1
  
  for (let i = 0; i <= 5; i++) {
    const y = padding + (chartHeight / 5) * i
    ctx.beginPath()
    ctx.moveTo(padding, y)
    ctx.lineTo(padding + chartWidth, y)
    ctx.stroke()
    
    // Draw y-axis labels
    const value = Math.round((maxValue / 5) * (5 - i))
    ctx.fillStyle = '#6B7280'
    ctx.font = '10px sans-serif'
    ctx.textAlign = 'right'
    ctx.fillText(value.toString(), padding - 10, y + 3)
  }
}

onMounted(() => {
  nextTick(() => {
    drawChart()
  })
})

watch(() => props.data, () => {
  nextTick(() => {
    drawChart()
  })
}, { deep: true })
</script>

<style scoped>
.engagement-chart {
  @apply w-full h-full;
}

.chart-header {
  @apply flex items-center justify-between mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.chart-legend {
  @apply flex items-center space-x-4;
}

.legend-item {
  @apply flex items-center space-x-2;
}

.legend-color {
  @apply w-3 h-3 rounded-full;
}

.legend-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
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