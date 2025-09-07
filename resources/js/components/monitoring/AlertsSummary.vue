<!-- ABOUTME: Alerts summary component for monitoring dashboard displaying system alerts and notifications -->
<!-- ABOUTME: Shows alert counts, severity levels, and recent alerts with filtering and management options -->
<template>
  <div class="alerts-summary-container">
    <div class="alerts-header">
      <h3 class="alerts-title">System Alerts</h3>
      <div class="alerts-controls">
        <select 
          v-model="selectedSeverity" 
          @change="filterAlerts"
          class="severity-filter"
        >
          <option value="all">All Severities</option>
          <option value="critical">Critical</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
        <button 
          @click="refreshAlerts"
          class="refresh-button"
          :disabled="loading"
        >
          <svg class="refresh-icon" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Alert Statistics -->
    <div class="alert-stats">
      <div class="stat-item critical">
        <div class="stat-value">{{ alertCounts.critical }}</div>
        <div class="stat-label">Critical</div>
      </div>
      <div class="stat-item high">
        <div class="stat-value">{{ alertCounts.high }}</div>
        <div class="stat-label">High</div>
      </div>
      <div class="stat-item medium">
        <div class="stat-value">{{ alertCounts.medium }}</div>
        <div class="stat-label">Medium</div>
      </div>
      <div class="stat-item low">
        <div class="stat-value">{{ alertCounts.low }}</div>
        <div class="stat-label">Low</div>
      </div>
    </div>

    <!-- Recent Alerts List -->
    <div class="alerts-list">
      <div v-if="loading" class="alerts-loading">
        <div class="loading-spinner"></div>
        <span>Loading alerts...</span>
      </div>
      
      <div v-else-if="filteredAlerts.length === 0" class="no-alerts">
        <svg class="no-alerts-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p>No alerts found</p>
      </div>
      
      <div v-else class="alerts-items">
        <div
          v-for="alert in filteredAlerts.slice(0, maxDisplayed)"
          :key="alert.id"
          :class="[
            'alert-item',
            `alert-${alert.severity}`,
            { 'alert-acknowledged': alert.acknowledged }
          ]"
          @click="handleAlertClick(alert)"
        >
          <div class="alert-indicator">
            <div :class="['severity-dot', `severity-${alert.severity}`]"></div>
          </div>
          
          <div class="alert-content">
            <div class="alert-title">{{ alert.title }}</div>
            <div class="alert-message">{{ alert.message }}</div>
            <div class="alert-meta">
              <span class="alert-source">{{ alert.source }}</span>
              <span class="alert-time">{{ formatTime(alert.timestamp) }}</span>
            </div>
          </div>
          
          <div class="alert-actions">
            <button
              v-if="!alert.acknowledged"
              @click.stop="acknowledgeAlert(alert.id)"
              class="action-button acknowledge"
              title="Acknowledge"
            >
              <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </button>
            
            <button
              @click.stop="dismissAlert(alert.id)"
              class="action-button dismiss"
              title="Dismiss"
            >
              <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
      
      <div v-if="filteredAlerts.length > maxDisplayed" class="show-more">
        <button @click="showAllAlerts" class="show-more-button">
          View All {{ filteredAlerts.length }} Alerts
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Alert {
  id: string
  title: string
  message: string
  severity: 'critical' | 'high' | 'medium' | 'low'
  source: string
  timestamp: string
  acknowledged: boolean
  category?: string
  metadata?: Record<string, any>
}

interface Props {
  alerts?: Alert[]
  maxDisplayed?: number
  autoRefresh?: boolean
  refreshInterval?: number
}

interface Emits {
  'alert-click': [alert: Alert]
  'alert-acknowledge': [alertId: string]
  'alert-dismiss': [alertId: string]
  'show-all': []
  'refresh': []
}

const props = withDefaults(defineProps<Props>(), {
  alerts: () => [],
  maxDisplayed: 5,
  autoRefresh: false,
  refreshInterval: 30000
})

const emit = defineEmits<Emits>()

// Refs
const loading = ref(false)
const selectedSeverity = ref('all')
const refreshTimer = ref<NodeJS.Timeout | null>(null)

// Computed
const alertCounts = computed(() => {
  const counts = {
    critical: 0,
    high: 0,
    medium: 0,
    low: 0
  }
  
  props.alerts.forEach(alert => {
    if (!alert.acknowledged) {
      counts[alert.severity]++
    }
  })
  
  return counts
})

const filteredAlerts = computed(() => {
  let filtered = props.alerts
  
  if (selectedSeverity.value !== 'all') {
    filtered = filtered.filter(alert => alert.severity === selectedSeverity.value)
  }
  
  // Sort by severity and timestamp
  const severityOrder = { critical: 0, high: 1, medium: 2, low: 3 }
  
  return filtered.sort((a, b) => {
    // First sort by acknowledgment status
    if (a.acknowledged !== b.acknowledged) {
      return a.acknowledged ? 1 : -1
    }
    
    // Then by severity
    const severityDiff = severityOrder[a.severity] - severityOrder[b.severity]
    if (severityDiff !== 0) return severityDiff
    
    // Finally by timestamp (newest first)
    return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  })
})

// Methods
const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`
  return date.toLocaleDateString()
}

const filterAlerts = () => {
  // Filtering is handled by computed property
}

const refreshAlerts = async () => {
  loading.value = true
  try {
    emit('refresh')
    // Simulate loading delay
    await new Promise(resolve => setTimeout(resolve, 500))
  } finally {
    loading.value = false
  }
}

const handleAlertClick = (alert: Alert) => {
  emit('alert-click', alert)
}

const acknowledgeAlert = (alertId: string) => {
  emit('alert-acknowledge', alertId)
}

const dismissAlert = (alertId: string) => {
  emit('alert-dismiss', alertId)
}

const showAllAlerts = () => {
  emit('show-all')
}

const startAutoRefresh = () => {
  if (props.autoRefresh && props.refreshInterval > 0) {
    refreshTimer.value = setInterval(() => {
      refreshAlerts()
    }, props.refreshInterval)
  }
}

const stopAutoRefresh = () => {
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
    refreshTimer.value = null
  }
}

// Lifecycle
onMounted(() => {
  startAutoRefresh()
})

// Cleanup
const cleanup = () => {
  stopAutoRefresh()
}

// Make sure to cleanup on unmount
if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', cleanup)
}
</script>

<style scoped>
.alerts-summary-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.alerts-header {
  @apply flex items-center justify-between mb-6;
}

.alerts-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.alerts-controls {
  @apply flex items-center gap-3;
}

.severity-filter {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.refresh-button {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
  @apply border border-gray-300 dark:border-gray-600 rounded-md;
  @apply hover:bg-gray-50 dark:hover:bg-gray-700;
  @apply transition-colors duration-200;
}

.refresh-icon {
  @apply w-4 h-4;
}

.alert-stats {
  @apply grid grid-cols-4 gap-4 mb-6;
}

.stat-item {
  @apply text-center p-3 rounded-lg border;
}

.stat-item.critical {
  @apply bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800;
}

.stat-item.high {
  @apply bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800;
}

.stat-item.medium {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800;
}

.stat-item.low {
  @apply bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800;
}

.stat-value {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.stat-label {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.alerts-list {
  @apply space-y-3;
}

.alerts-loading {
  @apply flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-400;
}

.loading-spinner {
  @apply w-5 h-5 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin;
}

.no-alerts {
  @apply flex flex-col items-center justify-center py-8 text-gray-500 dark:text-gray-400;
}

.no-alerts-icon {
  @apply w-12 h-12 mb-3 text-green-500;
}

.alerts-items {
  @apply space-y-2;
}

.alert-item {
  @apply flex items-start gap-3 p-4 rounded-lg border cursor-pointer;
  @apply transition-all duration-200 ease-in-out;
  @apply hover:shadow-md;
}

.alert-critical {
  @apply bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800;
}

.alert-high {
  @apply bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800;
}

.alert-medium {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800;
}

.alert-low {
  @apply bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800;
}

.alert-acknowledged {
  @apply opacity-60;
}

.alert-indicator {
  @apply flex-shrink-0 mt-1;
}

.severity-dot {
  @apply w-3 h-3 rounded-full;
}

.severity-critical {
  @apply bg-red-500;
}

.severity-high {
  @apply bg-orange-500;
}

.severity-medium {
  @apply bg-yellow-500;
}

.severity-low {
  @apply bg-blue-500;
}

.alert-content {
  @apply flex-grow min-w-0;
}

.alert-title {
  @apply font-medium text-gray-900 dark:text-white mb-1;
}

.alert-message {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-2;
}

.alert-meta {
  @apply flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400;
}

.alert-source {
  @apply font-medium;
}

.alert-actions {
  @apply flex items-center gap-2;
}

.action-button {
  @apply p-1.5 rounded-md transition-colors duration-200;
}

.action-button.acknowledge {
  @apply text-green-600 hover:bg-green-100 dark:hover:bg-green-900/20;
}

.action-button.dismiss {
  @apply text-red-600 hover:bg-red-100 dark:hover:bg-red-900/20;
}

.action-icon {
  @apply w-4 h-4;
}

.show-more {
  @apply text-center pt-4 border-t border-gray-200 dark:border-gray-700;
}

.show-more-button {
  @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300;
  @apply font-medium text-sm transition-colors duration-200;
}

/* Responsive Design */
@media (max-width: 768px) {
  .alert-stats {
    @apply grid-cols-2 gap-3;
  }
  
  .alerts-header {
    @apply flex-col items-start gap-3;
  }
  
  .alert-item {
    @apply p-3;
  }
  
  .alert-meta {
    @apply flex-col items-start gap-1;
  }
}

@media (max-width: 640px) {
  .alerts-summary-container {
    @apply p-4;
  }
  
  .alert-stats {
    @apply grid-cols-1;
  }
}
</style>