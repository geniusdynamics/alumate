<template>
  <div class="analytics-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1 class="dashboard-title">Analytics Dashboard</h1>
        <p class="dashboard-subtitle">
          Comprehensive insights into alumni engagement and platform performance
        </p>
      </div>
      
      <div class="header-actions">
        <DateRangePicker
          v-model:start-date="filters.start_date"
          v-model:end-date="filters.end_date"
          @change="refreshData"
        />
        
        <button
          @click="showExportModal = true"
          class="btn btn-secondary"
        >
          <Icon name="download" class="w-4 h-4" />
          Export Data
        </button>
        
        <button
          @click="showCustomReportModal = true"
          class="btn btn-primary"
        >
          <Icon name="chart-bar" class="w-4 h-4" />
          Custom Report
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Loading analytics data...</p>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Summary Cards -->
      <div class="summary-grid">
        <SummaryCard
          v-for="metric in summaryMetrics"
          :key="metric.key"
          :title="metric.title"
          :value="metric.value"
          :change="metric.change"
          :trend="metric.trend"
          :icon="metric.icon"
          :color="metric.color"
        />
      </div>

      <!-- Alerts -->
      <AlertsPanel
        v-if="alerts.length > 0"
        :alerts="alerts"
        class="mb-6"
      />

      <!-- Main Charts Grid -->
      <div class="charts-grid">
        <!-- Engagement Metrics -->
        <div class="chart-section">
          <div class="section-header">
            <h2 class="section-title">Engagement Metrics</h2>
            <button
              @click="refreshEngagementData"
              class="btn btn-ghost btn-sm"
            >
              <Icon name="refresh" class="w-4 h-4" />
            </button>
          </div>
          
          <div class="charts-row">
            <EngagementChart
              :data="engagementData"
              class="chart-container"
            />
            
            <UserActivityChart
              :data="activityData.daily_active_users"
              class="chart-container"
            />
          </div>
        </div>

        <!-- Alumni Activity -->
        <div class="chart-section">
          <div class="section-header">
            <h2 class="section-title">Alumni Activity</h2>
          </div>
          
          <div class="charts-row">
            <PostActivityChart
              :data="activityData.post_activity"
              class="chart-container"
            />
            
            <FeatureUsageChart
              :data="activityData.feature_usage"
              class="chart-container"
            />
          </div>
        </div>

        <!-- Community Health -->
        <div class="chart-section">
          <div class="section-header">
            <h2 class="section-title">Community Health</h2>
          </div>
          
          <div class="charts-row">
            <NetworkDensityGauge
              :value="communityHealth.network_density"
              class="chart-container"
            />
            
            <GroupParticipationChart
              :data="communityHealth.group_participation"
              class="chart-container"
            />
          </div>
        </div>

        <!-- Platform Usage -->
        <div class="chart-section">
          <div class="section-header">
            <h2 class="section-title">Platform Usage</h2>
          </div>
          
          <div class="charts-row">
            <DeviceBreakdownChart
              :data="platformUsage.device_breakdown"
              class="chart-container"
            />
            
            <PeakUsageChart
              :data="platformUsage.peak_usage_times"
              class="chart-container"
            />
          </div>
        </div>

        <!-- Geographic Distribution -->
        <div class="chart-section full-width">
          <div class="section-header">
            <h2 class="section-title">Geographic Distribution</h2>
          </div>
          
          <GeographicMap
            :data="activityData.geographic_distribution"
            class="chart-container-large"
          />
        </div>
      </div>

      <!-- Data Tables -->
      <div class="tables-section">
        <div class="section-header">
          <h2 class="section-title">Detailed Analytics</h2>
        </div>
        
        <div class="tables-grid">
          <AnalyticsTable
            title="Top Performing Groups"
            :data="communityHealth.group_participation"
            :columns="groupColumns"
            class="table-container"
          />
          
          <AnalyticsTable
            title="Graduation Year Activity"
            :data="activityData.graduation_year_activity"
            :columns="graduationYearColumns"
            class="table-container"
          />
        </div>
      </div>
    </div>

    <!-- Export Modal -->
    <ExportModal
      v-if="showExportModal"
      @close="showExportModal = false"
      @export="handleExport"
    />

    <!-- Custom Report Modal -->
    <CustomReportModal
      v-if="showCustomReportModal"
      @close="showCustomReportModal = false"
      @generate="handleCustomReport"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// Components
import DateRangePicker from '@/Components/Analytics/DateRangePicker.vue'
import SummaryCard from '@/Components/Analytics/SummaryCard.vue'
import AlertsPanel from '@/Components/Analytics/AlertsPanel.vue'
import EngagementChart from '@/Components/Analytics/Charts/EngagementChart.vue'
import UserActivityChart from '@/Components/Analytics/Charts/UserActivityChart.vue'
import PostActivityChart from '@/Components/Analytics/Charts/PostActivityChart.vue'
import FeatureUsageChart from '@/Components/Analytics/Charts/FeatureUsageChart.vue'
import NetworkDensityGauge from '@/Components/Analytics/Charts/NetworkDensityGauge.vue'
import GroupParticipationChart from '@/Components/Analytics/Charts/GroupParticipationChart.vue'
import DeviceBreakdownChart from '@/Components/Analytics/Charts/DeviceBreakdownChart.vue'
import PeakUsageChart from '@/Components/Analytics/Charts/PeakUsageChart.vue'
import GeographicMap from '@/Components/Analytics/Charts/GeographicMap.vue'
import AnalyticsTable from '@/Components/Analytics/AnalyticsTable.vue'
import ExportModal from '@/Components/Analytics/ExportModal.vue'
import CustomReportModal from '@/Components/Analytics/CustomReportModal.vue'
import Icon from '@/Components/Icon.vue'

// Types
interface AnalyticsData {
  engagement_metrics: any
  alumni_activity: any
  community_health: any
  platform_usage: any
}

interface SummaryMetric {
  key: string
  title: string
  value: string | number
  change: number
  trend: 'up' | 'down' | 'stable'
  icon: string
  color: string
}

// Reactive data
const loading = ref(true)
const showExportModal = ref(false)
const showCustomReportModal = ref(false)

const filters = reactive({
  start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  institution_id: null,
  graduation_year: null,
  location: null,
  program: null,
})

const analyticsData = ref<AnalyticsData>({
  engagement_metrics: {},
  alumni_activity: {},
  community_health: {},
  platform_usage: {},
})

const alerts = ref([])

// Computed properties
const engagementData = computed(() => analyticsData.value.engagement_metrics)
const activityData = computed(() => analyticsData.value.alumni_activity)
const communityHealth = computed(() => analyticsData.value.community_health)
const platformUsage = computed(() => analyticsData.value.platform_usage)

const summaryMetrics = computed((): SummaryMetric[] => [
  {
    key: 'total_users',
    title: 'Total Users',
    value: engagementData.value.total_users || 0,
    change: 12.5,
    trend: 'up',
    icon: 'users',
    color: 'blue',
  },
  {
    key: 'active_users',
    title: 'Active Users',
    value: engagementData.value.active_users || 0,
    change: 8.3,
    trend: 'up',
    icon: 'user-check',
    color: 'green',
  },
  {
    key: 'engagement_rate',
    title: 'Engagement Rate',
    value: `${engagementData.value.engagement_rate || 0}%`,
    change: -2.1,
    trend: 'down',
    icon: 'heart',
    color: 'red',
  },
  {
    key: 'network_density',
    title: 'Network Density',
    value: `${communityHealth.value.network_density || 0}%`,
    change: 5.7,
    trend: 'up',
    icon: 'share-2',
    color: 'purple',
  },
])

const groupColumns = [
  { key: 'name', label: 'Group Name' },
  { key: 'members_count', label: 'Members' },
  { key: 'posts_count', label: 'Posts' },
]

const graduationYearColumns = [
  { key: 'graduation_year', label: 'Year' },
  { key: 'count', label: 'Active Alumni' },
]

// Methods
const loadDashboardData = async () => {
  loading.value = true
  
  try {
    const response = await axios.get('/api/analytics/dashboard', {
      params: filters,
    })
    
    if (response.data.success) {
      analyticsData.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const loadAnalyticsSummary = async () => {
  try {
    const response = await axios.get('/api/analytics/summary', {
      params: filters,
    })
    
    if (response.data.success) {
      alerts.value = response.data.data.alerts || []
    }
  } catch (error) {
    console.error('Failed to load analytics summary:', error)
  }
}

const refreshData = async () => {
  await Promise.all([
    loadDashboardData(),
    loadAnalyticsSummary(),
  ])
}

const refreshEngagementData = async () => {
  try {
    const response = await axios.get('/api/analytics/engagement-metrics', {
      params: filters,
    })
    
    if (response.data.success) {
      analyticsData.value.engagement_metrics = response.data.data
    }
  } catch (error) {
    console.error('Failed to refresh engagement data:', error)
  }
}

const handleExport = async (exportConfig: any) => {
  try {
    const response = await axios.get('/api/analytics/export', {
      params: {
        ...exportConfig,
        ...filters,
      },
      responseType: 'blob',
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `analytics_export_${Date.now()}.${exportConfig.format}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    showExportModal.value = false
  } catch (error) {
    console.error('Failed to export data:', error)
  }
}

const handleCustomReport = async (reportConfig: any) => {
  try {
    const response = await axios.post('/api/analytics/custom-report', {
      ...reportConfig,
      filters,
    })
    
    if (response.data.success) {
      // Handle custom report data
      console.log('Custom report generated:', response.data.data)
      showCustomReportModal.value = false
    }
  } catch (error) {
    console.error('Failed to generate custom report:', error)
  }
}

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.analytics-dashboard {
  @apply min-h-screen bg-gray-50 dark:bg-gray-900;
}

.dashboard-header {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4;
  @apply flex items-center justify-between;
}

.header-content {
  @apply flex-1;
}

.dashboard-title {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.dashboard-subtitle {
  @apply text-gray-600 dark:text-gray-400 mt-1;
}

.header-actions {
  @apply flex items-center space-x-4;
}

.loading-container {
  @apply flex flex-col items-center justify-center py-20;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4;
}

.dashboard-content {
  @apply p-6 space-y-6;
}

.summary-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6;
}

.charts-grid {
  @apply space-y-8;
}

.chart-section {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.chart-section.full-width {
  @apply col-span-full;
}

.section-header {
  @apply flex items-center justify-between mb-6;
}

.section-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.charts-row {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6;
}

.chart-container {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4;
}

.chart-container-large {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4 h-96;
}

.tables-section {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.tables-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6;
}

.table-container {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4;
}

.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500;
  @apply dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600;
}

.btn-ghost {
  @apply text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
}

.btn-sm {
  @apply px-3 py-1.5 text-xs;
}

.btn > svg {
  @apply mr-2;
}
</style>