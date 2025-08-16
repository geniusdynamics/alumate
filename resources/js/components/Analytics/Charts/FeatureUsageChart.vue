<template>
  <div class="feature-usage-chart">
    <div class="chart-header">
      <h4 class="chart-title">Feature Usage</h4>
    </div>
    
    <div class="chart-container">
      <div class="usage-bars">
        <div
          v-for="(feature, key) in data"
          :key="key"
          class="usage-bar-item"
        >
          <div class="feature-info">
            <span class="feature-name">{{ formatFeatureName(key) }}</span>
            <span class="feature-value">{{ feature }}%</span>
          </div>
          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: `${feature}%` }"
              :class="getProgressColor(feature)"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  data: Record<string, number>
}

const props = defineProps<Props>()

const formatFeatureName = (key: string): string => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getProgressColor = (value: number): string => {
  if (value >= 80) return 'bg-green-500'
  if (value >= 60) return 'bg-blue-500'
  if (value >= 40) return 'bg-yellow-500'
  return 'bg-red-500'
}
</script>

<style scoped>
.feature-usage-chart {
  @apply w-full h-full;
}

.chart-header {
  @apply mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.chart-container {
  @apply space-y-4;
}

.usage-bar-item {
  @apply space-y-2;
}

.feature-info {
  @apply flex items-center justify-between;
}

.feature-name {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.feature-value {
  @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.progress-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2;
}

.progress-fill {
  @apply h-2 rounded-full transition-all duration-300;
}
</style>