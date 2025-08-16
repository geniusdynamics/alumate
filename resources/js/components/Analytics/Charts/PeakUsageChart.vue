<template>
  <div class="peak-usage-chart">
    <div class="chart-header">
      <h4 class="chart-title">Peak Usage Times</h4>
      <div class="chart-toggle">
        <button
          @click="viewMode = 'hourly'"
          class="toggle-button"
          :class="{ 'active': viewMode === 'hourly' }"
        >
          Hourly
        </button>
        <button
          @click="viewMode = 'daily'"
          class="toggle-button"
          :class="{ 'active': viewMode === 'daily' }"
        >
          Daily
        </button>
      </div>
    </div>
    
    <div class="chart-container">
      <div v-if="viewMode === 'hourly'" class="hourly-chart">
        <div class="hour-bars">
          <div
            v-for="hour in hourlyData"
            :key="hour.hour"
            class="hour-bar"
          >
            <div
              class="hour-fill"
              :style="{ height: `${getHourPercentage(hour.usage)}%` }"
              :class="getHourColor(hour.hour)"
            ></div>
            <span class="hour-label">{{ formatHour(hour.hour) }}</span>
          </div>
        </div>
      </div>
      
      <div v-else class="daily-chart">
        <div class="day-items">
          <div
            v-for="(usage, day) in data.daily"
            :key="day"
            class="day-item"
          >
            <div class="day-info">
              <span class="day-name">{{ formatDayName(day) }}</span>
              <span class="day-usage">{{ usage }}</span>
            </div>
            <div class="day-bar">
              <div
                class="day-fill"
                :style="{ width: `${getDayPercentage(usage)}%` }"
                :class="getDayColor(day)"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface HourlyData {
  hour: number
  usage: number
}

interface Props {
  data: {
    hourly: HourlyData[]
    daily: Record<string, number>
  }
}

const props = defineProps<Props>()
const viewMode = ref<'hourly' | 'daily'>('hourly')

const hourlyData = computed(() => {
  if (!props.data?.hourly || !Array.isArray(props.data.hourly)) {
    return Array.from({ length: 24 }, (_, i) => ({ hour: i, usage: 0 }))
  }
  return props.data.hourly
})

const maxHourlyUsage = computed(() => {
  return Math.max(...hourlyData.value.map(h => h.usage), 1)
})

const maxDailyUsage = computed(() => {
  if (!props.data?.daily) return 1
  return Math.max(...Object.values(props.data.daily), 1)
})

const getHourPercentage = (usage: number): number => {
  return (usage / maxHourlyUsage.value) * 100
}

const getDayPercentage = (usage: number): number => {
  return (usage / maxDailyUsage.value) * 100
}

const formatHour = (hour: number): string => {
  if (hour === 0) return '12a'
  if (hour < 12) return `${hour}a`
  if (hour === 12) return '12p'
  return `${hour - 12}p`
}

const formatDayName = (day: string): string => {
  return day.charAt(0).toUpperCase() + day.slice(1)
}

const getHourColor = (hour: number): string => {
  // Peak hours (9-17) in blue, evening (18-23) in purple, night/early morning in gray
  if (hour >= 9 && hour <= 17) return 'bg-blue-500'
  if (hour >= 18 && hour <= 23) return 'bg-purple-500'
  return 'bg-gray-400'
}

const getDayColor = (day: string): string => {
  const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
  return weekdays.includes(day) ? 'bg-blue-500' : 'bg-green-500'
}
</script>

<style scoped>
.peak-usage-chart {
  @apply w-full h-full;
}

.chart-header {
  @apply flex items-center justify-between mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.chart-toggle {
  @apply flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1;
}

.toggle-button {
  @apply px-3 py-1 text-sm font-medium rounded-md transition-colors;
  @apply text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.toggle-button.active {
  @apply bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm;
}

.chart-container {
  @apply h-48;
}

.hourly-chart {
  @apply h-full;
}

.hour-bars {
  @apply flex items-end justify-between h-full space-x-1;
}

.hour-bar {
  @apply flex-1 flex flex-col items-center space-y-1;
}

.hour-fill {
  @apply w-full rounded-t transition-all duration-300;
  min-height: 4px;
}

.hour-label {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.daily-chart {
  @apply space-y-3;
}

.day-item {
  @apply space-y-2;
}

.day-info {
  @apply flex items-center justify-between;
}

.day-name {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.day-usage {
  @apply text-sm font-semibold text-gray-600 dark:text-gray-400;
}

.day-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2;
}

.day-fill {
  @apply h-2 rounded-full transition-all duration-300;
}
</style>