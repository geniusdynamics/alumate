<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div
        class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        @click="$emit('close')"
      ></div>

      <!-- Modal panel -->
      <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Brand Usage Analytics
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Insights into brand asset usage and compliance across components
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="btn-icon"
          >
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <!-- Analytics Content -->
        <div class="space-y-6">
          <!-- Overview Cards -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="metric-card">
              <div class="metric-icon bg-blue-100 text-blue-600">
                <Icon name="color-swatch" class="w-6 h-6" />
              </div>
              <div class="metric-content">
                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ Object.keys(analyticsData.colorUsage).length }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Colors in Use</p>
              </div>
            </div>

            <div class="metric-card">
              <div class="metric-icon bg-green-100 text-green-600">
                <Icon name="document-text" class="w-6 h-6" />
              </div>
              <div class="metric-content">
                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ Object.keys(analyticsData.fontUsage).length }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Fonts in Use</p>
              </div>
            </div>

            <div class="metric-card">
              <div class="metric-icon bg-purple-100 text-purple-600">
                <Icon name="template" class="w-6 h-6" />
              </div>
              <div class="metric-content">
                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ Object.keys(analyticsData.templateUsage).length }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Active Templates</p>
              </div>
            </div>

            <div class="metric-card">
              <div class="metric-icon bg-yellow-100 text-yellow-600">
                <Icon name="chart-bar" class="w-6 h-6" />
              </div>
              <div class="metric-content">
                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ Math.round(analyticsData.complianceScore * 100) }}%
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Compliance Score</p>
              </div>
            </div>
          </div>

          <!-- Usage Charts -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Color Usage Chart -->
            <div class="chart-container">
              <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Color Usage Distribution
              </h4>
              <div class="space-y-3">
                <div
                  v-for="(usage, colorName) in sortedColorUsage"
                  :key="colorName"
                  class="usage-bar-item"
                >
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ colorName }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ usage }} uses
                    </span>
                  </div>
                  <div class="usage-bar">
                    <div
                      class="usage-bar-fill bg-blue-500"
                      :style="{ width: `${(usage / maxColorUsage) * 100}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Font Usage Chart -->
            <div class="chart-container">
              <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Font Usage Distribution
              </h4>
              <div class="space-y-3">
                <div
                  v-for="(usage, fontName) in sortedFontUsage"
                  :key="fontName"
                  class="usage-bar-item"
                >
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ fontName }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ usage }} uses
                    </span>
                  </div>
                  <div class="usage-bar">
                    <div
                      class="usage-bar-fill bg-green-500"
                      :style="{ width: `${(usage / maxFontUsage) * 100}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Trends Chart -->
          <div class="chart-container">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Compliance Trends (Last 30 Days)
            </h4>
            <div class="trends-chart">
              <svg
                viewBox="0 0 800 200"
                class="w-full h-48"
              >
                <!-- Grid lines -->
                <defs>
                  <pattern id="grid" width="40" height="20" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="1"/>
                  </pattern>
                </defs>
                <rect width="800" height="200" fill="url(#grid)" />
                
                <!-- Trend line -->
                <polyline
                  :points="trendLinePoints"
                  fill="none"
                  stroke="#3b82f6"
                  stroke-width="2"
                />
                
                <!-- Data points -->
                <circle
                  v-for="(point, index) in trendPoints"
                  :key="index"
                  :cx="point.x"
                  :cy="point.y"
                  r="4"
                  fill="#3b82f6"
                  class="hover:r-6 transition-all duration-200"
                />
              </svg>
            </div>
          </div>

          <!-- Asset Usage Details -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Most Used Assets -->
            <div class="asset-list">
              <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Most Used Assets
              </h4>
              <div class="space-y-3">
                <div
                  v-for="(usage, assetName) in topAssets"
                  :key="assetName"
                  class="asset-item"
                >
                  <div class="asset-info">
                    <h5 class="font-medium text-gray-900 dark:text-white">
                      {{ assetName }}
                    </h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      Used in {{ usage }} components
                    </p>
                  </div>
                  <div class="asset-usage">
                    <div class="usage-indicator">
                      <div
                        class="usage-fill"
                        :style="{ width: `${(usage / maxAssetUsage) * 100}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Unused Assets -->
            <div class="asset-list">
              <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Unused Assets
              </h4>
              <div class="space-y-3">
                <div
                  v-for="asset in unusedAssets"
                  :key="asset"
                  class="asset-item"
                >
                  <div class="asset-info">
                    <h5 class="font-medium text-gray-900 dark:text-white">
                      {{ asset }}
                    </h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      Not currently in use
                    </p>
                  </div>
                  <div class="asset-actions">
                    <button
                      @click="removeUnusedAsset(asset)"
                      class="btn-sm btn-secondary"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Last updated: {{ formatDate(new Date()) }}
          </div>
          <div class="flex gap-3">
            <button
              @click="exportAnalytics"
              class="btn-secondary"
            >
              <Icon name="download" class="w-4 h-4 mr-2" />
              Export Report
            </button>
            <button
              @click="refreshAnalytics"
              class="btn-primary"
            >
              <Icon name="refresh" class="w-4 h-4 mr-2" />
              Refresh Data
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import type { BrandAnalytics } from '@/types/components'

interface Props {
  analyticsData: BrandAnalytics
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  refresh: []
  export: []
  removeAsset: [assetName: string]
}>()

// Computed properties for sorted usage data
const sortedColorUsage = computed(() => {
  return Object.fromEntries(
    Object.entries(props.analyticsData.colorUsage)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 10)
  )
})

const sortedFontUsage = computed(() => {
  return Object.fromEntries(
    Object.entries(props.analyticsData.fontUsage)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 10)
  )
})

const maxColorUsage = computed(() => {
  return Math.max(...Object.values(props.analyticsData.colorUsage))
})

const maxFontUsage = computed(() => {
  return Math.max(...Object.values(props.analyticsData.fontUsage))
})

const topAssets = computed(() => {
  return Object.fromEntries(
    Object.entries(props.analyticsData.assetUsage)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 5)
  )
})

const maxAssetUsage = computed(() => {
  return Math.max(...Object.values(props.analyticsData.assetUsage))
})

const unusedAssets = computed(() => {
  return Object.entries(props.analyticsData.assetUsage)
    .filter(([, usage]) => usage === 0)
    .map(([name]) => name)
    .slice(0, 5)
})

// Trend chart data
const trendPoints = computed(() => {
  const data = props.analyticsData.trendsData.filter(d => d.metric === 'compliance')
  const maxValue = Math.max(...data.map(d => d.value))
  
  return data.map((point, index) => ({
    x: (index / (data.length - 1)) * 760 + 20,
    y: 180 - (point.value / maxValue) * 160
  }))
})

const trendLinePoints = computed(() => {
  return trendPoints.value.map(p => `${p.x},${p.y}`).join(' ')
})

// Methods
const formatDate = (date: Date): string => {
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const refreshAnalytics = () => {
  emit('refresh')
}

const exportAnalytics = () => {
  emit('export')
}

const removeUnusedAsset = (assetName: string) => {
  if (confirm(`Are you sure you want to remove "${assetName}"?`)) {
    emit('removeAsset', assetName)
  }
}
</script>

<style scoped>
.btn-icon {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.btn-sm {
  @apply px-3 py-1.5 text-sm rounded-md font-medium transition-colors duration-200;
}

.btn-sm.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300;
}

.metric-card {
  @apply flex items-center gap-4 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600;
}

.metric-icon {
  @apply w-12 h-12 rounded-lg flex items-center justify-center;
}

.metric-content {
  @apply flex-1;
}

.chart-container {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.usage-bar-item {
  @apply space-y-1;
}

.usage-bar {
  @apply w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2;
}

.usage-bar-fill {
  @apply h-2 rounded-full transition-all duration-300;
}

.trends-chart {
  @apply bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600;
}

.asset-list {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.asset-item {
  @apply flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600;
}

.asset-info {
  @apply flex-1 min-w-0;
}

.asset-usage {
  @apply flex-shrink-0 ml-4;
}

.usage-indicator {
  @apply w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2;
}

.usage-fill {
  @apply h-2 bg-blue-500 rounded-full transition-all duration-300;
}

.asset-actions {
  @apply flex-shrink-0 ml-4;
}
</style>