<template>
  <div class="search-analytics">
    <div class="analytics-header">
      <h3 class="analytics-title">Search Analytics</h3>
      <div class="analytics-period">
        <select v-model="selectedPeriod" @change="loadAnalytics" class="period-select">
          <option value="7d">Last 7 days</option>
          <option value="30d">Last 30 days</option>
          <option value="90d">Last 90 days</option>
        </select>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">
      <LoadingSpinner class="w-8 h-8" />
      <p>Loading analytics...</p>
    </div>

    <div v-else class="analytics-grid">
      <!-- Key Metrics -->
      <div class="metrics-row">
        <div class="metric-card">
          <div class="metric-value">{{ analytics.total_searches?.toLocaleString() || 0 }}</div>
          <div class="metric-label">Total Searches</div>
        </div>
        
        <div class="metric-card">
          <div class="metric-value">{{ analytics.unique_users?.toLocaleString() || 0 }}</div>
          <div class="metric-label">Unique Users</div>
        </div>
        
        <div class="metric-card">
          <div class="metric-value">{{ analytics.avg_results_per_search || 0 }}</div>
          <div class="metric-label">Avg Results</div>
        </div>
        
        <div class="metric-card">
          <div class="metric-value">{{ analytics.saved_searches_count || 0 }}</div>
          <div class="metric-label">Saved Searches</div>
        </div>
      </div>

      <!-- Popular Queries -->
      <div class="analytics-section">
        <h4 class="section-title">Popular Search Queries</h4>
        <div class="popular-queries">
          <div
            v-for="(query, index) in analytics.popular_queries || []"
            :key="index"
            class="query-item"
          >
            <span class="query-text">{{ query }}</span>
            <span class="query-rank">#{{ index + 1 }}</span>
          </div>
        </div>
      </div>

      <!-- Search Trends -->
      <div class="analytics-section">
        <h4 class="section-title">Search Trends</h4>
        <div class="trends-chart">
          <div
            v-for="(trend, index) in analytics.search_trends || []"
            :key="index"
            class="trend-bar"
          >
            <div class="trend-date">{{ formatDate(trend.date) }}</div>
            <div class="trend-bar-container">
              <div 
                class="trend-bar-fill"
                :style="{ width: `${(trend.count / maxTrendCount) * 100}%` }"
              ></div>
            </div>
            <div class="trend-count">{{ trend.count }}</div>
          </div>
        </div>
      </div>

      <!-- Popular Filters -->
      <div class="analytics-section">
        <h4 class="section-title">Popular Filters</h4>
        <div class="popular-filters">
          <div
            v-for="(filterGroup, filterType) in analytics.popular_filters || {}"
            :key="filterType"
            class="filter-group"
          >
            <h5 class="filter-type">{{ formatFilterType(filterType) }}</h5>
            <div class="filter-values">
              <span
                v-for="(value, index) in filterGroup.slice(0, 5)"
                :key="index"
                class="filter-value"
              >
                {{ value }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

interface SearchAnalytics {
  total_searches: number
  unique_users: number
  avg_results_per_search: number
  saved_searches_count: number
  popular_queries: string[]
  search_trends: Array<{ date: string; count: number }>
  popular_filters: Record<string, string[]>
}

// Reactive state
const isLoading = ref(false)
const selectedPeriod = ref('30d')
const analytics = ref<SearchAnalytics>({
  total_searches: 0,
  unique_users: 0,
  avg_results_per_search: 0,
  saved_searches_count: 0,
  popular_queries: [],
  search_trends: [],
  popular_filters: {}
})

// Computed properties
const maxTrendCount = computed(() => {
  const counts = analytics.value.search_trends?.map(t => t.count) || []
  return Math.max(...counts, 1)
})

// Methods
const loadAnalytics = async () => {
  isLoading.value = true
  
  try {
    const response = await fetch(`/api/search/analytics?period=${selectedPeriod.value}`)
    if (response.ok) {
      const data = await response.json()
      analytics.value = data.analytics || {}
    } else {
      console.error('Failed to load search analytics')
    }
  } catch (error) {
    console.error('Failed to load search analytics:', error)
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

const formatFilterType = (filterType: string): string => {
  const typeMap: Record<string, string> = {
    'location': 'Location',
    'industry': 'Industry',
    'graduation_year': 'Graduation Year',
    'skills': 'Skills'
  }
  
  return typeMap[filterType] || filterType
}

// Lifecycle
onMounted(() => {
  loadAnalytics()
})
</script>

<style scoped>
.search-analytics {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6;
}

.analytics-header {
  @apply flex items-center justify-between mb-6;
}

.analytics-title {
  @apply text-lg font-semibold text-gray-900;
}

.period-select {
  @apply px-3 py-2 border border-gray-300 rounded-md text-sm;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.loading-state {
  @apply flex flex-col items-center justify-center py-12 text-gray-600;
}

.analytics-grid {
  @apply space-y-8;
}

.metrics-row {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.metric-card {
  @apply bg-gray-50 rounded-lg p-4 text-center;
}

.metric-value {
  @apply text-2xl font-bold text-gray-900 mb-1;
}

.metric-label {
  @apply text-sm text-gray-600;
}

.analytics-section {
  @apply space-y-4;
}

.section-title {
  @apply text-base font-semibold text-gray-900;
}

.popular-queries {
  @apply space-y-2;
}

.query-item {
  @apply flex items-center justify-between py-2 px-3 bg-gray-50 rounded;
}

.query-text {
  @apply text-gray-700;
}

.query-rank {
  @apply text-sm text-gray-500 font-medium;
}

.trends-chart {
  @apply space-y-2;
}

.trend-bar {
  @apply flex items-center space-x-3;
}

.trend-date {
  @apply text-sm text-gray-600 w-16 flex-shrink-0;
}

.trend-bar-container {
  @apply flex-1 bg-gray-200 rounded-full h-4 overflow-hidden;
}

.trend-bar-fill {
  @apply h-full bg-blue-500 transition-all duration-300;
}

.trend-count {
  @apply text-sm text-gray-700 w-8 text-right;
}

.popular-filters {
  @apply space-y-4;
}

.filter-group {
  @apply space-y-2;
}

.filter-type {
  @apply text-sm font-medium text-gray-700;
}

.filter-values {
  @apply flex flex-wrap gap-2;
}

.filter-value {
  @apply inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded;
}
</style>