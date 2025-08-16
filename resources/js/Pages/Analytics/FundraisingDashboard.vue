<template>
  <AppLayout title="Fundraising Analytics">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Fundraising Analytics Dashboard
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Comprehensive insights into giving patterns, campaign performance, and donor analytics
          </p>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Date From
              </label>
              <input
                v-model="filters.date_from"
                type="date"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                @change="loadDashboard"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Date To
              </label>
              <input
                v-model="filters.date_to"
                type="date"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                @change="loadDashboard"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Institution
              </label>
              <select
                v-model="filters.institution_id"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                @change="loadDashboard"
              >
                <option value="">All Institutions</option>
                <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                  {{ institution.name }}
                </option>
              </select>
            </div>
            <div class="flex items-end">
              <button
                @click="exportData"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                :disabled="loading"
              >
                Export Data
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-600 dark:text-gray-400">Loading analytics...</p>
        </div>

        <!-- Dashboard Content -->
        <div v-else-if="dashboardData" class="space-y-8">
          <!-- Overview Metrics -->
          <OverviewMetrics :metrics="dashboardData.overview_metrics" />

          <!-- Charts Row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Giving Trends Chart -->
            <GivingTrendsChart :trends="dashboardData.trends" />
            
            <!-- Campaign Performance Chart -->
            <CampaignPerformanceChart :campaigns="dashboardData.campaign_performance" />
          </div>

          <!-- Detailed Analytics Tabs -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <nav class="-mb-px flex space-x-8 px-6">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === tab.id
                      ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                  ]"
                >
                  {{ tab.name }}
                </button>
              </nav>
            </div>

            <div class="p-6">
              <!-- Giving Patterns Tab -->
              <GivingPatternsAnalysis
                v-if="activeTab === 'patterns'"
                :patterns="dashboardData.giving_patterns"
              />

              <!-- Donor Analytics Tab -->
              <DonorAnalytics
                v-else-if="activeTab === 'donors'"
                :analytics="dashboardData.donor_analytics"
              />

              <!-- Predictive Analytics Tab -->
              <PredictiveAnalytics
                v-else-if="activeTab === 'predictive'"
                :predictions="dashboardData.predictive_insights"
              />

              <!-- ROI Analysis Tab -->
              <ROIAnalysis
                v-else-if="activeTab === 'roi'"
                :campaigns="dashboardData.campaign_performance"
              />
            </div>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <div class="text-red-500 mb-4">
            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Failed to Load Analytics
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">{{ error }}</p>
          <button
            @click="loadDashboard"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import OverviewMetrics from '@/Components/Analytics/Fundraising/OverviewMetrics.vue'
import GivingTrendsChart from '@/Components/Analytics/Fundraising/GivingTrendsChart.vue'
import CampaignPerformanceChart from '@/Components/Analytics/Fundraising/CampaignPerformanceChart.vue'
import GivingPatternsAnalysis from '@/Components/Analytics/Fundraising/GivingPatternsAnalysis.vue'
import DonorAnalytics from '@/Components/Analytics/Fundraising/DonorAnalytics.vue'
import PredictiveAnalytics from '@/Components/Analytics/Fundraising/PredictiveAnalytics.vue'
import ROIAnalysis from '@/Components/Analytics/Fundraising/ROIAnalysis.vue'

const props = defineProps({
  institutions: {
    type: Array,
    default: () => []
  }
})

const loading = ref(false)
const error = ref(null)
const dashboardData = ref(null)
const activeTab = ref('patterns')

const filters = reactive({
  date_from: '',
  date_to: '',
  institution_id: ''
})

const tabs = [
  { id: 'patterns', name: 'Giving Patterns' },
  { id: 'donors', name: 'Donor Analytics' },
  { id: 'predictive', name: 'Predictive Insights' },
  { id: 'roi', name: 'ROI Analysis' }
]

const loadDashboard = async () => {
  loading.value = true
  error.value = null

  try {
    const params = new URLSearchParams()
    
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)
    if (filters.institution_id) params.append('institution_id', filters.institution_id)

    const response = await fetch(`/api/fundraising-analytics/dashboard?${params}`)
    
    if (!response.ok) {
      throw new Error('Failed to load dashboard data')
    }

    dashboardData.value = await response.json()
  } catch (err) {
    error.value = err.message
    console.error('Dashboard loading error:', err)
  } finally {
    loading.value = false
  }
}

const exportData = async () => {
  try {
    const params = new URLSearchParams()
    params.append('type', 'dashboard')
    params.append('format', 'json')
    
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)
    if (filters.institution_id) params.append('institution_id', filters.institution_id)

    const response = await fetch('/api/fundraising-analytics/export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: params
    })

    if (!response.ok) {
      throw new Error('Export failed')
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `fundraising-analytics-${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (err) {
    console.error('Export error:', err)
    alert('Failed to export data. Please try again.')
  }
}

onMounted(() => {
  // Set default date range to last 12 months
  const now = new Date()
  const lastYear = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate())
  
  filters.date_from = lastYear.toISOString().split('T')[0]
  filters.date_to = now.toISOString().split('T')[0]
  
  loadDashboard()
})
</script>