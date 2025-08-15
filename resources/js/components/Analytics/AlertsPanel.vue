<template>
  <div class="alerts-panel">
    <div class="panel-header">
      <h3 class="panel-title">
        <Icon name="alert-triangle" class="w-5 h-5" />
        Analytics Alerts
      </h3>
      <button
        v-if="alerts.length > 3"
        @click="showAll = !showAll"
        class="toggle-button"
      >
        {{ showAll ? 'Show Less' : `Show All (${alerts.length})` }}
      </button>
    </div>
    
    <div class="alerts-list">
      <div
        v-for="(alert, index) in displayedAlerts"
        :key="index"
        class="alert-item"
        :class="alertTypeClass(alert.type)"
      >
        <div class="alert-icon">
          <Icon :name="getAlertIcon(alert.type)" class="w-5 h-5" />
        </div>
        
        <div class="alert-content">
          <p class="alert-message">{{ alert.message }}</p>
          <div class="alert-details">
            <span class="alert-metric">{{ alert.metric }}</span>
            <span class="alert-value">{{ formatValue(alert.value) }}</span>
          </div>
        </div>
        
        <button
          @click="dismissAlert(index)"
          class="dismiss-button"
        >
          <Icon name="x" class="w-4 h-4" />
        </button>
      </div>
    </div>
    
    <div v-if="alerts.length === 0" class="no-alerts">
      <Icon name="check-circle" class="w-8 h-8 text-green-500" />
      <p class="no-alerts-text">All systems are performing well!</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Icon from '@/Components/Icon.vue'

interface Alert {
  type: 'success' | 'warning' | 'info' | 'error'
  message: string
  metric: string
  value: number | string
}

interface Props {
  alerts: Alert[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  dismiss: [index: number]
}>()

const showAll = ref(false)

const displayedAlerts = computed(() => {
  if (showAll.value || props.alerts.length <= 3) {
    return props.alerts
  }
  return props.alerts.slice(0, 3)
})

const alertTypeClass = (type: string) => {
  const classes = {
    success: 'alert-success',
    warning: 'alert-warning',
    info: 'alert-info',
    error: 'alert-error',
  }
  return classes[type as keyof typeof classes] || 'alert-info'
}

const getAlertIcon = (type: string) => {
  const icons = {
    success: 'check-circle',
    warning: 'alert-triangle',
    info: 'info',
    error: 'alert-circle',
  }
  return icons[type as keyof typeof icons] || 'info'
}

const formatValue = (value: number | string) => {
  if (typeof value === 'number') {
    return value.toLocaleString()
  }
  return value
}

const dismissAlert = (index: number) => {
  emit('dismiss', index)
}
</script>

<style scoped>
.alerts-panel {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.panel-header {
  @apply flex items-center justify-between mb-4;
}

.panel-title {
  @apply flex items-center space-x-2 text-lg font-semibold text-gray-900 dark:text-white;
}

.toggle-button {
  @apply text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300;
  @apply font-medium transition-colors;
}

.alerts-list {
  @apply space-y-3;
}

.alert-item {
  @apply flex items-start space-x-3 p-4 rounded-lg border-l-4;
  @apply transition-all duration-200 hover:shadow-sm;
}

.alert-success {
  @apply bg-green-50 dark:bg-green-900/10 border-l-green-500;
}

.alert-warning {
  @apply bg-yellow-50 dark:bg-yellow-900/10 border-l-yellow-500;
}

.alert-info {
  @apply bg-blue-50 dark:bg-blue-900/10 border-l-blue-500;
}

.alert-error {
  @apply bg-red-50 dark:bg-red-900/10 border-l-red-500;
}

.alert-icon {
  @apply flex-shrink-0 mt-0.5;
}

.alert-success .alert-icon {
  @apply text-green-600 dark:text-green-400;
}

.alert-warning .alert-icon {
  @apply text-yellow-600 dark:text-yellow-400;
}

.alert-info .alert-icon {
  @apply text-blue-600 dark:text-blue-400;
}

.alert-error .alert-icon {
  @apply text-red-600 dark:text-red-400;
}

.alert-content {
  @apply flex-1 min-w-0;
}

.alert-message {
  @apply text-sm font-medium text-gray-900 dark:text-white mb-1;
}

.alert-details {
  @apply flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400;
}

.alert-metric {
  @apply font-medium;
}

.alert-value {
  @apply px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded;
}

.dismiss-button {
  @apply flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
  @apply transition-colors p-1 rounded;
}

.no-alerts {
  @apply flex flex-col items-center justify-center py-8 text-center;
}

.no-alerts-text {
  @apply text-gray-600 dark:text-gray-400 mt-2;
}
</style>