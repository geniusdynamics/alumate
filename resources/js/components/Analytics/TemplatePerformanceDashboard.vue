<template>
  <div class="template-performance-dashboard">
    <!-- Header -->
    <div class="dashboard-header mb-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Template Performance Dashboard</h1>
          <p class="text-gray-600 mt-1">Monitor and analyze your template performance metrics</p>
        </div>
        <div class="flex gap-3">
          <button
            @click="refreshData"
            :disabled="loading"
            class="btn-secondary flex items-center gap-2"
          >
            <RefreshCwIcon :class="{ 'animate-spin': loading }" class="w-4 h-4" />
            Refresh
          </button>
          <button
            @click="exportData"
            class="btn-primary flex items-center gap-2"
          >
            <DownloadIcon class="w-4 h-4" />
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="dashboard-filters mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
            <select v-model="filters.dateRange" @change="updateFilters" class="form-select">
              <option value="last_7_days">Last 7 days</option>
              <option value="last_30_days">Last 30 days</option>
              <option value="last_90_days">Last 90 days</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>
          <div v-if="filters.dateRange === 'custom'">
            <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
            <input
              v-model="filters.dateFrom"
              type="date"
              @change="updateFilters"
              class="form-input"
            >
          </div>
          <div v-if="filters.dateRange === 'custom'">
            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
            <input
              v-model="filters.dateTo"
              type="date"
              @change="updateFilters"
              class="form-input"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
            <select v-model="filters.templateId" @change="updateFilters" class="form-select">
              <option value="">All Templates</option>
              <option v-for="template in templates" :key="template.id" :value="template.id">
                {{ template.name }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-gray-600">Loading dashboard data...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
          <AlertCircleIcon class="w-5 h-5 text-red-400" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error loading dashboard</h3>
            <p class="text-sm text-red-700 mt-1">{{ error }}</p>
            <button @click="refreshData" class="mt-2 text-sm text-red-600 hover:text-red-500">
              Try again
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Summary Cards -->
      <div class="summary-cards mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <MetricCard
            v-for="metric in summaryMetrics"
            :key="metric.key"
            :title="metric.title"
            :value="metric.value"
            :change="metric.change"
            :change-type="metric.changeType"
            :icon="metric.icon"
          />
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-section mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Conversion Rate Trend -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversion Rate Trend</h3>
            <LineChart
              :data="trendData"
              :options="chartOptions"
              class="h-64"
            />
          </div>

          <!-- Template Performance -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Performance</h3>
            <BarChart
              :data="performanceData"
              :options="chartOptions"
              class="h-64"
            />
          </div>
        </div>
      </div>

      <!-- Insights Section -->
      <div class="insights-section mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights</h3>
          <div class="space-y-4">
            <InsightCard
              v-for="insight in insights"
              :key="insight.id"
              :insight="insight"
            />
          </div>
        </div>
      </div>

      <!-- Template Comparison -->
      <div class="comparison-section">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Template Comparison</h3>
            <button
              @click="showComparisonModal = true"
              class="btn-secondary text-sm"
            >
              Compare Templates
            </button>
          </div>
          <div v-if="comparisonData" class="comparison-table">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Template
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Usage Count
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Conversion Rate
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Performance Score
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="template in comparisonData.templates" :key="template.template.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ template.template.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ template.metrics.usage_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ template.metrics.conversion_rate }}%
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getScoreBadgeClass(template.performance_score)"
                      >
                        {{ template.performance_score }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            <BarChart3Icon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">No comparison data</h3>
            <p class="mt-1 text-sm text-gray-500">Select templates to compare their performance.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Comparison Modal -->
    <Modal
      v-if="showComparisonModal"
      @close="showComparisonModal = false"
      title="Compare Templates"
    >
      <TemplateComparison
        @compare="handleTemplateComparison"
        @close="showComparisonModal = false"
      />
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import {
  RefreshCwIcon,
  DownloadIcon,
  AlertCircleIcon,
  BarChart3Icon
} from 'lucide-vue-next'

// Components
import MetricCard from './MetricCard.vue'
import InsightCard from './InsightCard.vue'
import LineChart from './Charts/LineChart.vue'
import BarChart from './Charts/BarChart.vue'
import Modal from '../ui/Modal.vue'
import TemplateComparison from './TemplateComparison.vue'

// Composables
import { useDashboardStore } from '../../stores/dashboard'

// Props
interface Props {
  tenantId?: number
}

const props = withDefaults(defineProps<Props>(), {
  tenantId: 1
})

// Reactive data
const loading = ref(false)
const error = ref('')
const showComparisonModal = ref(false)
const dashboardStore = useDashboardStore()

// Filters
const filters = ref({
  dateRange: 'last_30_days',
  dateFrom: '',
  dateTo: '',
  templateId: ''
})

// Computed properties
const summaryMetrics = computed(() => {
  const data = dashboardStore.overviewData?.summary
  if (!data) return []

  return [
    {
      key: 'total_templates',
      title: 'Total Templates',
      value: data.total_templates || 0,
      change: 0,
      changeType: 'neutral',
      icon: 'template'
    },
    {
      key: 'total_conversions',
      title: 'Total Conversions',
      value: data.total_conversions || 0,
      change: 0,
      changeType: 'neutral',
      icon: 'conversion'
    },
    {
      key: 'conversion_rate',
      title: 'Conversion Rate',
      value: `${data.conversion_rate || 0}%`,
      change: 0,
      changeType: 'neutral',
      icon: 'percentage'
    },
    {
      key: 'unique_users',
      title: 'Unique Users',
      value: data.unique_users || 0,
      change: 0,
      changeType: 'neutral',
      icon: 'users'
    }
  ]
})

const trendData = computed(() => {
  const trends = dashboardStore.overviewData?.trends || []
  return {
    labels: trends.map(t => t.date),
    datasets: [{
      label: 'Conversion Rate',
      data: trends.map(t => t.conversions),
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: true
    }]
  }
})

const performanceData = computed(() => {
  const performance = dashboardStore.overviewData?.performance || {}
  const labels = Object.keys(performance)
  const data = Object.values(performance).map((p: any) => p.performance_score || 0)

  return {
    labels,
    datasets: [{
      label: 'Performance Score',
      data,
      backgroundColor: '#10B981',
      borderColor: '#059669',
      borderWidth: 1
    }]
  }
})

const insights = computed(() => {
  return dashboardStore.overviewData?.insights || []
})

const comparisonData = computed(() => {
  return dashboardStore.comparisonData
})

const templates = computed(() => {
  return dashboardStore.templates
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
}))

// Methods
const refreshData = async () => {
  loading.value = true
  error.value = ''

  try {
    await dashboardStore.fetchOverview(props.tenantId, filters.value)
  } catch (err: any) {
    error.value = err.message || 'Failed to load dashboard data'
  } finally {
    loading.value = false
  }
}

const updateFilters = () => {
  refreshData()
}

const exportData = async () => {
  try {
    await dashboardStore.exportData(props.tenantId, 'json', filters.value)
  } catch (err: any) {
    error.value = err.message || 'Failed to export data'
  }
}

const handleTemplateComparison = async (templateIds: number[]) => {
  try {
    await dashboardStore.fetchComparison(templateIds, filters.value)
    showComparisonModal.value = false
  } catch (err: any) {
    error.value = err.message || 'Failed to compare templates'
  }
}

const getScoreBadgeClass = (score: number) => {
  if (score >= 80) return 'bg-green-100 text-green-800'
  if (score >= 60) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.template-performance-dashboard {
  @apply p-6;
}

.dashboard-header {
  @apply bg-white rounded-lg shadow p-6;
}

.dashboard-filters {
  @apply bg-gray-50 rounded-lg p-4;
}

.btn-primary {
  @apply bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2;
}

.form-select {
  @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500;
}

.form-input {
  @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500;
}

.loading-state {
  @apply bg-white rounded-lg shadow p-6;
}

.error-state {
  @apply bg-white rounded-lg shadow p-6;
}

.summary-cards {
  @apply grid gap-6;
}

.charts-section {
  @apply grid gap-6;
}

.insights-section {
  @apply bg-white rounded-lg shadow;
}

.comparison-section {
  @apply bg-white rounded-lg shadow;
}

.comparison-table {
  @apply overflow-x-auto;
}
</style>