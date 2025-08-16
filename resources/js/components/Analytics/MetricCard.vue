<template>
  <div class="metric-card bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center">
      <div :class="iconClasses" class="flex-shrink-0">
        <span class="text-2xl">{{ icon }}</span>
      </div>
      <div class="ml-4 flex-1">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 truncate">
          {{ title }}
        </p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ value }}
        </p>
        <p v-if="change" :class="changeClasses" class="text-sm font-medium">
          {{ change }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  title: string
  value: string | number
  icon: string
  color: 'blue' | 'green' | 'yellow' | 'purple' | 'red'
  change?: string
  changeType?: 'positive' | 'negative' | 'neutral'
}

const props = defineProps<Props>()

const iconClasses = computed(() => {
  const baseClasses = 'w-12 h-12 rounded-lg flex items-center justify-center'
  
  const colorClasses = {
    blue: 'bg-blue-100 dark:bg-blue-900',
    green: 'bg-green-100 dark:bg-green-900',
    yellow: 'bg-yellow-100 dark:bg-yellow-900',
    purple: 'bg-purple-100 dark:bg-purple-900',
    red: 'bg-red-100 dark:bg-red-900',
  }
  
  return `${baseClasses} ${colorClasses[props.color]}`
})

const changeClasses = computed(() => {
  if (!props.changeType) return 'text-gray-600 dark:text-gray-400'
  
  const typeClasses = {
    positive: 'text-green-600 dark:text-green-400',
    negative: 'text-red-600 dark:text-red-400',
    neutral: 'text-gray-600 dark:text-gray-400',
  }
  
  return typeClasses[props.changeType]
})
</script>

<style scoped>
.metric-card {
  transition: transform 0.2s ease-in-out, shadow 0.2s ease-in-out;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>