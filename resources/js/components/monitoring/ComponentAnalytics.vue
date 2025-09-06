<!-- ABOUTME: Component analytics dashboard for monitoring individual component performance and usage -->
<!-- ABOUTME: Displays metrics like render times, error rates, and usage statistics for Vue components -->
<template>
  <div class="component-analytics-container">
    <div class="analytics-header">
      <h3 class="analytics-title">Component Analytics</h3>
      <div class="analytics-controls">
        <select 
          v-model="selectedMetric" 
          @change="updateChart"
          class="metric-selector"
        >
          <option value="performance">Performance</option>
          <option value="errors">Error Rate</option>
          <option value="usage">Usage Count</option>
          <option value="memory">Memory Usage</option>
        </select>
        <select 
          v-model="selectedTimeRange" 
          @change="updateChart"
          class="time-range-selector"
        >
          <option value="1h">Last Hour</option>
          <option value="24h">Last 24 Hours</option>
          <option value="7d">Last 7 Days</option>
        </select>
      </div>
    </div>

    <!-- Component Performance Overview -->
    <div class="performance-overview">
      <div class="overview-card">
        <div class="card-header">
          <h4 class="card-title">Top Performing Components</h4>
        </div>
        <div class="card-content">
          <div 
            v-for="component in topPerformingComponents" 
            :key="component.name"
            class="component-item good"
          >
            <div class="component-info">
              <span class="component-name">{{ component.name }}</span>
              <span class="component-metric">{{ formatMetric(component.value, selectedMetric) }}</span>
            </div>
            <div class="performance-indicator good">
              <svg class="indicator-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <div class="overview-card">
        <div class="card-header">
          <h4 class="card-title">Components Needing Attention</h4>
        </div>
        <div class="card-content">
          <div 
            v-for="component in problematicComponents" 
            :key="component.name"
            class="component-item warning"
          >
            <div class="component-info">
              <span class="component-name">{{ component.name }}</span>
              <span class="component-metric">{{ formatMetric(component.value, selectedMetric) }}</span>
            </div>
            <div class="performance-indicator warning">
              <svg class="indicator-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Component List -->
    <div class="component-details">
      <div class="details-header">
        <h4 class="details-title">All Components</h4>
        <div class="search-filter">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search components..."
            class="search-input"
          />
        </div>
      </div>
      
      <div class="component-table">
        <div class="table-header">
          <div class="header-cell component-col">Component</div>
          <div class="header-cell metric-col">{{ getMetricLabel(selectedMetric) }}</div>
          <div class="header-cell trend-col">Trend</div>
          <div class="header-cell status-col">Status</div>
          <div class="header-cell actions-col">Actions</div>
        </div>
        
        <div class="table-body">
          <div 
            v-for="component in filteredComponents" 
            :key="component.name"
            class="table-row"
            @click="selectComponent(component)"
          >
            <div class="table-cell component-col">
              <div class="component-details-info">
                <span class="component-name">{{ component.name }}</span>
                <span class="component-path">{{ component.path }}</span>
              </div>
            </div>
            
            <div class="table-cell metric-col">
              <span class="metric-value">{{ formatMetric(component.value, selectedMetric) }}</span>
            </div>
            
            <div class="table-cell trend-col">
              <div class="trend-indicator" :class="component.trend">
                <svg v-if="component.trend === 'up'" class="trend-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                </svg>
                <svg v-else-if="component.trend === 'down'" class="trend-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                </svg>
                <svg v-else class="trend-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                </svg>
                <span class="trend-value">{{ component.trendValue }}%</span>
              </div>
            </div>
            
            <div class="table-cell status-col">
              <span :class="['status-badge', component.status]">{{ component.status }}</span>
            </div>
            
            <div class="table-cell actions-col">
              <button 
                @click.stop="analyzeComponent(component)"
                class="action-button"
                title="Analyze"
              >
                <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="analytics-loading">
      <div class="loading-spinner"></div>
      <span>Loading component analytics...</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface ComponentData {
  name: string
  path: string
  value: number
  trend: 'up' | 'down' | 'neutral'
  trendValue: number
  status: 'healthy' | 'warning' | 'critical'
  renderTime?: number
  errorRate?: number
  usageCount?: number
  memoryUsage?: number
}

interface Props {
  data?: ComponentData[]
  refreshInterval?: number
}

interface Emits {
  'component-select': [component: ComponentData]
  'component-analyze': [component: ComponentData]
  'refresh': []
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  refreshInterval: 30000
})

const emit = defineEmits<Emits>()

// Refs
const loading = ref(false)
const selectedMetric = ref('performance')
const selectedTimeRange = ref('24h')
const searchQuery = ref('')

// Sample data for demo purposes
const sampleData = ref<ComponentData[]>([
  {
    name: 'UserProfile',
    path: '/components/UserProfile.vue',
    value: 45,
    trend: 'up',
    trendValue: 12,
    status: 'healthy',
    renderTime: 45,
    errorRate: 0.1,
    usageCount: 1250,
    memoryUsage: 2.3
  },
  {
    name: 'DataTable',
    path: '/components/DataTable.vue',
    value: 120,
    trend: 'down',
    trendValue: -8,
    status: 'warning',
    renderTime: 120,
    errorRate: 2.1,
    usageCount: 890,
    memoryUsage: 5.7
  },
  {
    name: 'NavigationMenu',
    path: '/components/NavigationMenu.vue',
    value: 32,
    trend: 'neutral',
    trendValue: 0,
    status: 'healthy',
    renderTime: 32,
    errorRate: 0.05,
    usageCount: 2100,
    memoryUsage: 1.8
  }
])

// Computed
const componentData = computed(() => {
  return props.data.length > 0 ? props.data : sampleData.value
})

const topPerformingComponents = computed(() => {
  return componentData.value
    .filter(c => c.status === 'healthy')
    .sort((a, b) => a.value - b.value)
    .slice(0, 3)
})

const problematicComponents = computed(() => {
  return componentData.value
    .filter(c => c.status === 'warning' || c.status === 'critical')
    .sort((a, b) => b.value - a.value)
    .slice(0, 3)
})

const filteredComponents = computed(() => {
  let filtered = componentData.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(component => 
      component.name.toLowerCase().includes(query) ||
      component.path.toLowerCase().includes(query)
    )
  }
  
  return filtered.sort((a, b) => {
    // Sort by status priority, then by metric value
    const statusOrder = { critical: 0, warning: 1, healthy: 2 }
    const statusDiff = statusOrder[a.status] - statusOrder[b.status]
    if (statusDiff !== 0) return statusDiff
    
    return b.value - a.value
  })
})

// Methods
const formatMetric = (value: number, metric: string): string => {
  switch (metric) {
    case 'performance':
      return `${value}ms`
    case 'errors':
      return `${value}%`
    case 'usage':
      return value.toLocaleString()
    case 'memory':
      return `${value}MB`
    default:
      return value.toString()
  }
}

const getMetricLabel = (metric: string): string => {
  switch (metric) {
    case 'performance':
      return 'Render Time'
    case 'errors':
      return 'Error Rate'
    case 'usage':
      return 'Usage Count'
    case 'memory':
      return 'Memory Usage'
    default:
      return 'Value'
  }
}

const updateChart = () => {
  // Update component data based on selected metric and time range
  loading.value = true
  
  setTimeout(() => {
    // Simulate data update
    componentData.value.forEach(component => {
      switch (selectedMetric.value) {
        case 'performance':
          component.value = component.renderTime || 0
          break
        case 'errors':
          component.value = component.errorRate || 0
          break
        case 'usage':
          component.value = component.usageCount || 0
          break
        case 'memory':
          component.value = component.memoryUsage || 0
          break
      }
    })
    
    loading.value = false
  }, 500)
}

const selectComponent = (component: ComponentData) => {
  emit('component-select', component)
}

const analyzeComponent = (component: ComponentData) => {
  emit('component-analyze', component)
}

// Lifecycle
onMounted(() => {
  updateChart()
})
</script>

<style scoped>
.component-analytics-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.analytics-header {
  @apply flex items-center justify-between mb-6;
}

.analytics-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.analytics-controls {
  @apply flex items-center gap-3;
}

.metric-selector,
.time-range-selector {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.performance-overview {
  @apply grid grid-cols-1 md:grid-cols-2 gap-6 mb-8;
}

.overview-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4;
}

.card-header {
  @apply mb-4;
}

.card-title {
  @apply text-base font-medium text-gray-900 dark:text-white;
}

.card-content {
  @apply space-y-3;
}

.component-item {
  @apply flex items-center justify-between p-3 rounded-lg;
}

.component-item.good {
  @apply bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800;
}

.component-item.warning {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800;
}

.component-info {
  @apply flex flex-col;
}

.component-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.component-metric {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.performance-indicator {
  @apply flex items-center;
}

.performance-indicator.good {
  @apply text-green-600 dark:text-green-400;
}

.performance-indicator.warning {
  @apply text-yellow-600 dark:text-yellow-400;
}

.indicator-icon {
  @apply w-5 h-5;
}

.component-details {
  @apply space-y-4;
}

.details-header {
  @apply flex items-center justify-between;
}

.details-title {
  @apply text-base font-medium text-gray-900 dark:text-white;
}

.search-filter {
  @apply flex items-center;
}

.search-input {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  @apply w-64;
}

.component-table {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden;
}

.table-header {
  @apply grid grid-cols-5 gap-4 p-4 bg-gray-50 dark:bg-gray-700;
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.table-body {
  @apply divide-y divide-gray-200 dark:divide-gray-700;
}

.table-row {
  @apply grid grid-cols-5 gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700;
  @apply cursor-pointer transition-colors duration-200;
}

.table-cell {
  @apply flex items-center;
}

.component-details-info {
  @apply flex flex-col;
}

.component-path {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.metric-value {
  @apply font-medium text-gray-900 dark:text-white;
}

.trend-indicator {
  @apply flex items-center gap-1;
}

.trend-indicator.up {
  @apply text-green-600 dark:text-green-400;
}

.trend-indicator.down {
  @apply text-red-600 dark:text-red-400;
}

.trend-indicator.neutral {
  @apply text-gray-600 dark:text-gray-400;
}

.trend-icon {
  @apply w-4 h-4;
}

.trend-value {
  @apply text-xs font-medium;
}

.status-badge {
  @apply px-2 py-1 text-xs font-medium rounded-full;
}

.status-badge.healthy {
  @apply bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400;
}

.status-badge.warning {
  @apply bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400;
}

.status-badge.critical {
  @apply bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400;
}

.action-button {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
  @apply hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md;
  @apply transition-colors duration-200;
}

.action-icon {
  @apply w-4 h-4;
}

.analytics-loading {
  @apply flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-400;
}

.loading-spinner {
  @apply w-5 h-5 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin;
}

/* Responsive Design */
@media (max-width: 768px) {
  .analytics-header {
    @apply flex-col items-start gap-3;
  }
  
  .performance-overview {
    @apply grid-cols-1;
  }
  
  .table-header,
  .table-row {
    @apply grid-cols-3;
  }
  
  .trend-col,
  .actions-col {
    @apply hidden;
  }
  
  .search-input {
    @apply w-full;
  }
}

@media (max-width: 640px) {
  .component-analytics-container {
    @apply p-4;
  }
  
  .table-header,
  .table-row {
    @apply grid-cols-2;
  }
  
  .status-col {
    @apply hidden;
  }
}
</style>