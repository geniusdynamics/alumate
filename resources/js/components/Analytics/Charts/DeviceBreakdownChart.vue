<template>
  <div class="device-breakdown-chart">
    <div class="chart-header">
      <h4 class="chart-title">Device Usage</h4>
    </div>
    
    <div class="chart-container">
      <div class="device-stats">
        <div
          v-for="(percentage, device) in data"
          :key="device"
          class="device-item"
        >
          <div class="device-info">
            <div class="device-icon" :class="getDeviceIconClass(device)">
              <Icon :name="getDeviceIcon(device)" class="w-5 h-5" />
            </div>
            <div class="device-details">
              <span class="device-name">{{ formatDeviceName(device) }}</span>
              <span class="device-percentage">{{ percentage }}%</span>
            </div>
          </div>
          <div class="device-bar">
            <div
              class="device-fill"
              :style="{ width: `${percentage}%` }"
              :class="getDeviceColor(device)"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue'

interface Props {
  data: Record<string, number>
}

const props = defineProps<Props>()

const formatDeviceName = (device: string): string => {
  return device.charAt(0).toUpperCase() + device.slice(1)
}

const getDeviceIcon = (device: string): string => {
  const icons: Record<string, string> = {
    desktop: 'monitor',
    mobile: 'smartphone',
    tablet: 'tablet',
  }
  return icons[device] || 'device-unknown'
}

const getDeviceIconClass = (device: string): string => {
  const classes: Record<string, string> = {
    desktop: 'text-blue-600 bg-blue-100 dark:bg-blue-900/20',
    mobile: 'text-green-600 bg-green-100 dark:bg-green-900/20',
    tablet: 'text-purple-600 bg-purple-100 dark:bg-purple-900/20',
  }
  return classes[device] || 'text-gray-600 bg-gray-100 dark:bg-gray-700'
}

const getDeviceColor = (device: string): string => {
  const colors: Record<string, string> = {
    desktop: 'bg-blue-500',
    mobile: 'bg-green-500',
    tablet: 'bg-purple-500',
  }
  return colors[device] || 'bg-gray-500'
}
</script>

<style scoped>
.device-breakdown-chart {
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

.device-item {
  @apply space-y-3;
}

.device-info {
  @apply flex items-center space-x-3;
}

.device-icon {
  @apply p-2 rounded-lg;
}

.device-details {
  @apply flex-1 flex items-center justify-between;
}

.device-name {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.device-percentage {
  @apply text-sm font-semibold text-gray-600 dark:text-gray-400;
}

.device-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2;
}

.device-fill {
  @apply h-2 rounded-full transition-all duration-300;
}
</style>