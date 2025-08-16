<template>
  <div class="summary-card" :class="cardColorClass">
    <div class="card-content">
      <div class="card-header">
        <div class="icon-container" :class="iconColorClass">
          <Icon :name="icon" class="w-6 h-6" />
        </div>
        <div class="trend-indicator" :class="trendColorClass">
          <Icon :name="trendIcon" class="w-4 h-4" />
          <span class="trend-text">{{ Math.abs(change) }}%</span>
        </div>
      </div>
      
      <div class="card-body">
        <h3 class="card-title">{{ title }}</h3>
        <p class="card-value">{{ formattedValue }}</p>
        <p class="card-change" :class="trendColorClass">
          {{ trendText }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Icon from '@/Components/Icon.vue'

interface Props {
  title: string
  value: string | number
  change: number
  trend: 'up' | 'down' | 'stable'
  icon: string
  color: string
}

const props = defineProps<Props>()

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString()
  }
  return props.value
})

const trendIcon = computed(() => {
  switch (props.trend) {
    case 'up':
      return 'trending-up'
    case 'down':
      return 'trending-down'
    default:
      return 'minus'
  }
})

const trendText = computed(() => {
  const changeText = props.change > 0 ? 'increase' : 'decrease'
  return `${Math.abs(props.change)}% ${changeText} from last period`
})

const cardColorClass = computed(() => {
  return `card-${props.color}`
})

const iconColorClass = computed(() => {
  return `icon-${props.color}`
})

const trendColorClass = computed(() => {
  switch (props.trend) {
    case 'up':
      return 'trend-positive'
    case 'down':
      return 'trend-negative'
    default:
      return 'trend-neutral'
  }
})
</script>

<style scoped>
.summary-card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700;
  @apply p-6 transition-all duration-200 hover:shadow-md;
}

.card-content {
  @apply space-y-4;
}

.card-header {
  @apply flex items-center justify-between;
}

.icon-container {
  @apply p-3 rounded-lg;
}

.icon-container.icon-blue {
  @apply bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400;
}

.icon-container.icon-green {
  @apply bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400;
}

.icon-container.icon-red {
  @apply bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400;
}

.icon-container.icon-purple {
  @apply bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400;
}

.icon-container.icon-yellow {
  @apply bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400;
}

.trend-indicator {
  @apply flex items-center space-x-1 px-2 py-1 rounded-full text-xs font-medium;
}

.trend-positive {
  @apply bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400;
}

.trend-negative {
  @apply bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400;
}

.trend-neutral {
  @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400;
}

.card-body {
  @apply space-y-2;
}

.card-title {
  @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.card-value {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.card-change {
  @apply text-sm font-medium;
}

.trend-text {
  @apply text-xs;
}

/* Card color variants */
.card-blue {
  @apply border-l-4 border-l-blue-500;
}

.card-green {
  @apply border-l-4 border-l-green-500;
}

.card-red {
  @apply border-l-4 border-l-red-500;
}

.card-purple {
  @apply border-l-4 border-l-purple-500;
}

.card-yellow {
  @apply border-l-4 border-l-yellow-500;
}
</style>