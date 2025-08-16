<template>
  <div class="network-density-gauge">
    <div class="chart-header">
      <h4 class="chart-title">Network Density</h4>
    </div>
    
    <div class="gauge-container">
      <div class="gauge-wrapper">
        <svg class="gauge-svg" viewBox="0 0 200 120">
          <!-- Background arc -->
          <path
            d="M 20 100 A 80 80 0 0 1 180 100"
            fill="none"
            stroke="#E5E7EB"
            stroke-width="12"
            stroke-linecap="round"
          />
          
          <!-- Progress arc -->
          <path
            :d="progressArc"
            fill="none"
            :stroke="gaugeColor"
            stroke-width="12"
            stroke-linecap="round"
            class="progress-arc"
          />
          
          <!-- Center text -->
          <text x="100" y="85" text-anchor="middle" class="gauge-value">
            {{ value.toFixed(1) }}%
          </text>
          <text x="100" y="105" text-anchor="middle" class="gauge-label">
            Density
          </text>
        </svg>
      </div>
      
      <div class="gauge-description">
        <p class="description-text">
          Network density measures how interconnected your alumni community is.
          Higher density indicates stronger community bonds.
        </p>
        
        <div class="density-scale">
          <div class="scale-item">
            <div class="scale-color bg-red-500"></div>
            <span class="scale-label">Low (0-25%)</span>
          </div>
          <div class="scale-item">
            <div class="scale-color bg-yellow-500"></div>
            <span class="scale-label">Medium (25-50%)</span>
          </div>
          <div class="scale-item">
            <div class="scale-color bg-green-500"></div>
            <span class="scale-label">High (50%+)</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  value: number
}

const props = defineProps<Props>()

const progressArc = computed(() => {
  const percentage = Math.min(Math.max(props.value, 0), 100)
  const angle = (percentage / 100) * 160 // 160 degrees total arc
  const radians = (angle * Math.PI) / 180
  const x = 100 + 80 * Math.cos(Math.PI - radians)
  const y = 100 - 80 * Math.sin(Math.PI - radians)
  
  return `M 20 100 A 80 80 0 ${angle > 80 ? 1 : 0} 1 ${x} ${y}`
})

const gaugeColor = computed(() => {
  if (props.value >= 50) return '#10B981' // green
  if (props.value >= 25) return '#F59E0B' // yellow
  return '#EF4444' // red
})
</script>

<style scoped>
.network-density-gauge {
  @apply w-full h-full;
}

.chart-header {
  @apply mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.gauge-container {
  @apply space-y-4;
}

.gauge-wrapper {
  @apply flex justify-center;
}

.gauge-svg {
  @apply w-48 h-32;
}

.gauge-value {
  @apply text-2xl font-bold fill-current text-gray-900 dark:text-white;
}

.gauge-label {
  @apply text-sm fill-current text-gray-600 dark:text-gray-400;
}

.progress-arc {
  @apply transition-all duration-1000 ease-out;
}

.gauge-description {
  @apply space-y-3;
}

.description-text {
  @apply text-sm text-gray-600 dark:text-gray-400 text-center;
}

.density-scale {
  @apply flex justify-center space-x-4;
}

.scale-item {
  @apply flex items-center space-x-1;
}

.scale-color {
  @apply w-3 h-3 rounded-full;
}

.scale-label {
  @apply text-xs text-gray-600 dark:text-gray-400;
}
</style>