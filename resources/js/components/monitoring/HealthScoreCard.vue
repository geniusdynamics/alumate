<template>
  <div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">System Health Score</h3>
    <div class="flex items-center justify-center">
      <div class="relative">
        <svg class="w-24 h-24" viewBox="0 0 36 36">
          <path
            d="M18 2.0845
               a 15.9155 15.9155 0 0 1 0 31.831
               a 15.9155 15.9155 0 0 1 0 -31.831"
            fill="none"
            stroke="#E5E7EB"
            stroke-width="3"
            stroke-dasharray="100, 100"
          />
          <path
            d="M18 2.0845
               a 15.9155 15.9155 0 0 1 0 31.831
               a 15.9155 15.9155 0 0 1 0 -31.831"
            fill="none"
            :stroke="strokeColor"
            stroke-width="3"
            stroke-dasharray="100, 100"
            :stroke-dashoffset="strokeDashOffset"
            stroke-linecap="round"
          />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <span class="text-2xl font-bold" :class="textColor">{{ score }}%</span>
        </div>
      </div>
    </div>
    <div class="mt-4 text-center">
      <p class="text-sm text-gray-600">{{ healthLabel }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  score: number
}

const props = defineProps<{
  score: number
}>()

const strokeDashOffset = computed(() => {
  return 100 - (props.score / 100) * 100
})

const strokeColor = computed(() => {
  if (props.score >= 90) return '#10B981' // green
  if (props.score >= 70) return '#F59E0B' // yellow
  return '#EF4444' // red
})

const textColor = computed(() => {
  if (props.score >= 90) return 'text-green-600'
  if (props.score >= 70) return 'text-yellow-600'
  return 'text-red-600'
})

const healthLabel = computed(() => {
  if (props.score >= 90) return 'Excellent'
  if (props.score >= 80) return 'Good'
  if (props.score >= 70) return 'Fair'
  if (props.score >= 60) return 'Poor'
  return 'Critical'
})
</script>