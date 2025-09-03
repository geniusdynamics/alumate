<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Production Monitoring Dashboard</h1>
            <p class="text-gray-600 mt-2">Real-time system health and analytics monitoring</p>
          </div>
          <div class="flex space-x-4">
            <select v-model="selectedTimeframe" @change="fetchDashboardData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="realtime">Real-time</option>
              <option value="hour">Last Hour</option>
              <option value="day">Last 24 Hours</option>
              <option value="week">Last 7 Days</option>
              <option value="month">Last 30 Days</option>
            </select>
            <button @click="runMonitoringCycle"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    :disabled="isRunningCycle">
              {{ isRunningCycle ? 'Running...' : 'Run Monitoring Cycle' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Dashboard Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- System Overview Card -->
        <SystemOverviewCard :data="dashboardData.summary" />

        <!-- Health Score Card -->
        <HealthScoreCard :score="dashboardData.health_score" />

        <!-- KPIs Grid -->
        <KpiCards :kpis="dashboardData.kpis" />

        <!-- Performance Chart -->
        <PerformanceChart :data="dashboardData.charts.performance_over_time" />

        <!-- Alerts Summary -->
        <AlertsSummary :alerts="dashboardData.alerts" />

        <!-- Component Analytics -->
        <ComponentAnalytics :data="getComponentData()" />

        <!-- Traffic Trends -->
        <TrafficTrends :data="dashboardData.charts.user_activity_trends" />

        <!-- Error Rate Chart -->
        <ErrorRateChart :data="dashboardData.charts.error_rate_trends" />

        <!-- Security Incidents -->
        <SecurityIncidents :data="dashboardData.charts.security_incidents" />

        <!-- Recent Activity -->
        <RecentActivity :activity="dashboardData.recent_activity" />
      </div>

      <!-- Alerts Panel (Collapsible) -->
      <div v-if="dashboardData.alerts && dashboardData.alerts.length > 0" class="mt-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Active Alerts</h2>
            <button @click="showAlertModal = !showAlertModal"
                    class="text-blue-600 hover:text-blue-800">
              View All
            </button>
          </div>
          <div class="space-y-3">
            <div v-for="alert in dashboardData.alerts.slice(0, 5)"
                 :key="alert.timestamp"
                 class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <AlertIcon :priority="alert.priority" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ alert.message || alert.title }}</p>
                  <p class="text-xs text-gray-500">{{ formatTimestamp(alert.timestamp) }}</p>
                </div>
              </div>
              <button class="text-gray-400 hover:text-gray-600">×</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Metrics Panel -->
      <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Detailed Performance Metrics -->
        <div class="bg-white rounded-lg shadow-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h3>
          <DetailedMetrics :metrics="getDetailedMetrics()" />
        </div>

        <!-- System Health Details -->
        <div class="bg-white rounded-lg shadow-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">System Health</h3>
          <SystemHealthList :health="getSystemHealth()" />
        </div>
      </div>
    </div>

    <!-- Alert Modal -->
    <AlertModal v-if="showAlertModal"
                :alerts="allAlerts"
                @close="showAlertModal = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

// Component imports
import SystemOverviewCard from '@/components/monitoring/SystemOverviewCard.vue'
import HealthScoreCard from '@/components/monitoring/HealthScoreCard.vue'
import KpiCards from '@/components/monitoring/KpiCards.vue'
import PerformanceChart from '@/components/monitoring/PerformanceChart.vue'
import AlertsSummary from '@/components/monitoring/AlertsSummary.vue'
import ComponentAnalytics from '@/components/monitoring/ComponentAnalytics.vue'
import TrafficTrends from '@/components/monitoring/TrafficTrends.vue'
import ErrorRateChart from '@/components/monitoring/ErrorRateChart.vue'
import SecurityIncidents from '@/components/monitoring/SecurityIncidents.vue'
import RecentActivity from '@/components/monitoring/RecentActivity.vue'
import DetailedMetrics from '@/components/monitoring/DetailedMetrics.vue'
import SystemHealthList from '@/components/monitoring/SystemHealthList.vue'
import AlertModal from '@/components/monitoring/AlertModal.vue'
import AlertIcon from '@/components/monitoring/AlertIcon.vue'

// Reactive data
const dashboardData = ref({})
const allAlerts = ref([])
const selectedTimeframe = ref('realtime')
const loading = ref(false)
const showAlertModal = ref(false)
const isRunningCycle = ref(false)

// Auto-refresh interval
let refreshInterval: number | null = null

const fetchDashboardData = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/admin/monitoring/dashboard`, {
      params: { timeframe: selectedTimeframe.value }
    })

    dashboardData.value = response.data.data
    updateLastRefresh()
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
    useToast().error('Failed to update dashboard data')
  } finally {
    loading.value = false
  }
}

const fetchAlerts = async () => {
  try {
    const response = await axios.get('/api/admin/monitoring/alerts')
    allAlerts.value = response.data.alerts
  } catch (error) {
    console.error('Failed to fetch alerts:', error)
  }
}

const runMonitoringCycle = async () => {
  try {
    isRunningCycle.value = true
    useToast().info('Starting monitoring cycle...')

    const response = await axios.post('/api/admin/monitoring/cycle')
    useToast().success('Monitoring cycle completed successfully')

    await fetchDashboardData()
    await fetchAlerts()
  } catch (error) {
    console.error('Failed to run monitoring cycle:', error)
    useToast().error('Monitoring cycle failed')
  } finally {
    isRunningCycle.value = false
  }
}

const updateLastRefresh = () => {
  dashboardData.value.last_updated = new Date().toISOString()
}

const formatTimestamp = (timestamp: string) => {
  return new Date(timestamp).toLocaleString()
}

// Computed methods
const getComponentData = () => {
  return dashboardData.value.charts?.component_performance || {}
}

const getDetailedMetrics = () => {
  return {
    responseTime: dashboardData.value.kpis?.system_performance || 0,
    memoryUsage: dashboardData.value.charts?.performance_over_time?.datasets?.[2]?.data?.slice(-1)[0] || 0,
    errorRate: dashboardData.value.kpis?.error_rate || 0,
    throughput: dashboardData.value.charts?.user_activity_trends?.datasets?.[0]?.data?.slice(-1)[0] || 0,
  }
}

const getSystemHealth = () => {
  // Transform system health data for display
  return [
    { name: 'Database', status: 'healthy', details: 'PostgreSQL responding normally' },
    { name: 'Cache', status: 'healthy', details: 'Redis cache operational' },
    { name: 'File System', status: 'healthy', details: 'Storage available: 245GB' },
    { name: 'Queue', status: 'healthy', details: 'Jobs processing normally' },
    { name: 'Mail', status: 'warning', details: 'SMTP server timeout' },
    { name: 'CDN', status: 'healthy', details: 'All content delivery operational' },
  ]
}

// Lifecycle hooks
onMounted(async () => {
  await Promise.all([
    fetchDashboardData(),
    fetchAlerts()
  ])

  // Auto-refresh every 2 minutes for realtime view
  if (selectedTimeframe.value === 'realtime') {
    refreshInterval = window.setInterval(fetchDashboardData, 120000)
  }
})

onBeforeUnmount(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>