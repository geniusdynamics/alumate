<template>
  <div class="metric-card bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ value }}</p>
        <div v-if="change !== 0" class="flex items-center mt-2">
          <span
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
            :class="changeClass"
          >
            <TrendingUpIcon v-if="change > 0" class="w-3 h-3 mr-1" />
            <TrendingDownIcon v-else class="w-3 h-3 mr-1" />
            {{ Math.abs(change) }}%
          </span>
          <span class="text-xs text-gray-500 ml-2">vs last period</span>
        </div>
      </div>
      <div class="metric-icon" :class="iconClass">
        <component :is="iconComponent" class="w-8 h-8" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  TrendingUpIcon,
  TrendingDownIcon,
  TemplateIcon,
  UsersIcon,
  TargetIcon,
  PercentIcon
} from 'lucide-vue-next'

interface Props {
  title: string
  value: string | number
  change?: number
  changeType?: string
  icon?: string
}

const props = withDefaults(defineProps<Props>(), {
  change: 0,
  changeType: 'neutral',
  icon: 'default'
})

const changeClass = computed(() => {
  if (props.change > 0) {
    return 'bg-green-100 text-green-800'
  } else if (props.change < 0) {
    return 'bg-red-100 text-red-800'
  }
  return 'bg-gray-100 text-gray-800'
})

const iconComponent = computed(() => {
  switch (props.icon) {
    case 'template':
      return TemplateIcon
    case 'users':
      return UsersIcon
    case 'conversion':
      return TargetIcon
    case 'percentage':
      return PercentIcon
    default:
      return TargetIcon
  }
})

const iconClass = computed(() => {
  switch (props.icon) {
    case 'template':
      return 'text-blue-600 bg-blue-100'
    case 'users':
      return 'text-green-600 bg-green-100'
    case 'conversion':
      return 'text-purple-600 bg-purple-100'
    case 'percentage':
      return 'text-orange-600 bg-orange-100'
    default:
      return 'text-gray-600 bg-gray-100'
  }
})
</script>

<style scoped>
.metric-card {
  @apply transition-all duration-200 hover:shadow-lg;
}

.metric-icon {
  @apply p-3 rounded-full;
}
</style>