<template>
  <div class="w-full h-full">
    <canvas ref="chartCanvas" class="w-full h-full"></canvas>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'

interface DataPoint {
  x: string | number
  y: number
}

interface Dataset {
  label: string
  data: DataPoint[]
  borderColor?: string
  backgroundColor?: string
  fill?: boolean
}

interface Props {
  datasets: Dataset[]
  options?: any
  width?: number
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  width: 400,
  height: 300,
  options: () => ({})
})

const chartCanvas = ref<HTMLCanvasElement>()
let chartInstance: any = null

// Mock chart implementation (in a real app, you'd use Chart.js or similar)
const drawChart = () => {
  if (!chartCanvas.value) return

  const canvas = chartCanvas.value
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // Set canvas size
  canvas.width = props.width
  canvas.height = props.height

  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // Draw background
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, canvas.width, canvas.height)

  // Draw grid lines
  ctx.strokeStyle = '#e5e7eb'
  ctx.lineWidth = 1

  // Vertical grid lines
  for (let i = 0; i <= 10; i++) {
    const x = (canvas.width / 10) * i
    ctx.beginPath()
    ctx.moveTo(x, 0)
    ctx.lineTo(x, canvas.height)
    ctx.stroke()
  }

  // Horizontal grid lines
  for (let i = 0; i <= 10; i++) {
    const y = (canvas.height / 10) * i
    ctx.beginPath()
    ctx.moveTo(0, y)
    ctx.lineTo(canvas.width, y)
    ctx.stroke()
  }

  // Draw datasets
  props.datasets.forEach((dataset, index) => {
    if (dataset.data.length === 0) return

    const color = dataset.borderColor || `hsl(${index * 60}, 70%, 50%)`
    ctx.strokeStyle = color
    ctx.lineWidth = 2

    // Calculate data points
    const maxY = Math.max(...dataset.data.map(d => d.y))
    const minY = Math.min(...dataset.data.map(d => d.y))
    const rangeY = maxY - minY || 1

    ctx.beginPath()
    dataset.data.forEach((point, pointIndex) => {
      const x = (canvas.width / (dataset.data.length - 1)) * pointIndex
      const y = canvas.height - ((point.y - minY) / rangeY) * canvas.height

      if (pointIndex === 0) {
        ctx.moveTo(x, y)
      } else {
        ctx.lineTo(x, y)
      }
    })
    ctx.stroke()

    // Draw data points
    ctx.fillStyle = color
    dataset.data.forEach((point, pointIndex) => {
      const x = (canvas.width / (dataset.data.length - 1)) * pointIndex
      const y = canvas.height - ((point.y - minY) / rangeY) * canvas.height

      ctx.beginPath()
      ctx.arc(x, y, 3, 0, 2 * Math.PI)
      ctx.fill()
    })
  })

  // Draw legend
  let legendY = 20
  props.datasets.forEach((dataset, index) => {
    const color = dataset.borderColor || `hsl(${index * 60}, 70%, 50%)`
    
    // Legend color box
    ctx.fillStyle = color
    ctx.fillRect(10, legendY, 12, 12)
    
    // Legend text
    ctx.fillStyle = '#374151'
    ctx.font = '12px sans-serif'
    ctx.fillText(dataset.label, 28, legendY + 9)
    
    legendY += 20
  })
}

onMounted(() => {
  drawChart()
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})

watch(() => props.datasets, () => {
  drawChart()
}, { deep: true })

watch(() => [props.width, props.height], () => {
  drawChart()
})
</script>