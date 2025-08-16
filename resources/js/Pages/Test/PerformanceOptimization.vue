<template>
  <div class="performance-optimization-demo">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-8">Performance Optimization Demo</h1>
      
      <!-- Performance Dashboard -->
      <div class="mb-8">
        <PerformanceDashboard />
      </div>

      <!-- Lazy Loading Demo -->
      <div class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">Lazy Loading Demo</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="i in 12" :key="i" class="aspect-video">
            <LazyImage
              :src="`https://picsum.photos/400/300?random=${i}`"
              :alt="`Demo image ${i}`"
              class="w-full h-full object-cover rounded-lg"
              :width="400"
              :height="300"
              :eager="i <= 3"
            />
          </div>
        </div>
      </div>

      <!-- Code Splitting Demo -->
      <div class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">Code Splitting Demo</h2>
        <div class="space-y-4">
          <button 
            @click="loadChartComponent"
            :disabled="loadingChart"
            class="btn btn-primary"
          >
            {{ loadingChart ? 'Loading Chart...' : 'Load Chart Component' }}
          </button>
          
          <button 
            @click="loadMapComponent"
            :disabled="loadingMap"
            class="btn btn-primary ml-4"
          >
            {{ loadingMap ? 'Loading Map...' : 'Load Map Component' }}
          </button>
          
          <button 
            @click="loadSearchComponent"
            :disabled="loadingSearch"
            class="btn btn-primary ml-4"
          >
            {{ loadingSearch ? 'Loading Search...' : 'Load Advanced Search' }}
          </button>
        </div>
        
        <!-- Dynamically loaded components -->
        <div class="mt-6 space-y-6">
          <div v-if="chartComponent" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Chart Component (Lazy Loaded)</h3>
            <component :is="chartComponent" :data="chartData" />
          </div>
          
          <div v-if="mapComponent" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Map Component (Lazy Loaded)</h3>
            <component :is="mapComponent" />
          </div>
          
          <div v-if="searchComponent" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Advanced Search (Lazy Loaded)</h3>
            <component :is="searchComponent" />
          </div>
        </div>
      </div>

      <!-- Bundle Analysis -->
      <div class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">Bundle Analysis</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="bundle in bundleInfo" :key="bundle.name" class="bundle-card">
              <div class="bundle-header">
                <h4 class="font-semibold">{{ bundle.name }}</h4>
                <span class="bundle-status" :class="bundle.isLoaded ? 'loaded' : 'not-loaded'">
                  {{ bundle.isLoaded ? 'Loaded' : 'Not Loaded' }}
                </span>
              </div>
              <div class="bundle-details">
                <div class="bundle-metric">
                  <span>Size:</span>
                  <span>{{ formatSize(bundle.size) }}</span>
                </div>
                <div v-if="bundle.loadTime" class="bundle-metric">
                  <span>Load Time:</span>
                  <span>{{ formatTime(bundle.loadTime) }}</span>
                </div>
                <div v-if="bundle.gzipSize" class="bundle-metric">
                  <span>Gzipped:</span>
                  <span>{{ formatSize(bundle.gzipSize) }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-6">
            <h4 class="font-semibold mb-2">Total Bundle Size: {{ formatSize(totalBundleSize) }}</h4>
            <button @click="generateBundleReport" class="btn btn-secondary">
              Generate Bundle Report
            </button>
          </div>
        </div>
      </div>

      <!-- Performance Metrics -->
      <div class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">Real-time Performance Metrics</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="metric-card">
              <div class="metric-label">Load Time</div>
              <div class="metric-value">{{ formatTime(currentMetrics?.loadTime) }}</div>
            </div>
            <div class="metric-card">
              <div class="metric-label">First Contentful Paint</div>
              <div class="metric-value">{{ formatTime(currentMetrics?.firstContentfulPaint) }}</div>
            </div>
            <div class="metric-card">
              <div class="metric-label">Memory Usage</div>
              <div class="metric-value">{{ formatSize(currentMetrics?.memoryUsage) }}</div>
            </div>
            <div class="metric-card">
              <div class="metric-label">Network Requests</div>
              <div class="metric-value">{{ currentMetrics?.networkRequests || 0 }}</div>
            </div>
          </div>
          
          <div class="mt-6">
            <h4 class="font-semibold mb-2">Performance Score: {{ performanceScore }}/100</h4>
            <div class="progress-bar">
              <div 
                class="progress-fill" 
                :style="{ width: `${performanceScore}%` }"
                :class="getScoreClass(performanceScore)"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Performance Alerts -->
      <div v-if="alerts.length > 0" class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">Performance Alerts</h2>
        <div class="space-y-2">
          <div 
            v-for="alert in alerts" 
            :key="alert"
            class="alert alert-warning"
          >
            {{ alert }}
          </div>
        </div>
      </div>

      <!-- Optimization Tools -->
      <div class="mb-8">
        <h2 class="text-2xl font-semibent mb-4">Optimization Tools</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="space-y-4">
            <button @click="optimizePage" class="btn btn-success">
              Optimize Current Page
            </button>
            
            <button @click="preloadCriticalResources" class="btn btn-primary ml-4">
              Preload Critical Resources
            </button>
            
            <button @click="enableLazyLoading" class="btn btn-primary ml-4">
              Enable Lazy Loading
            </button>
            
            <button @click="compressImages" class="btn btn-primary ml-4">
              Compress Images
            </button>
          </div>
          
          <div class="mt-6">
            <h4 class="font-semibold mb-2">Optimization Status</h4>
            <div class="space-y-2">
              <div class="flex items-center">
                <span class="optimization-status" :class="optimizations.lazyLoading ? 'enabled' : 'disabled'"></span>
                <span class="ml-2">Lazy Loading</span>
              </div>
              <div class="flex items-center">
                <span class="optimization-status" :class="optimizations.imageOptimization ? 'enabled' : 'disabled'"></span>
                <span class="ml-2">Image Optimization</span>
              </div>
              <div class="flex items-center">
                <span class="optimization-status" :class="optimizations.caching ? 'enabled' : 'disabled'"></span>
                <span class="ml-2">Caching</span>
              </div>
              <div class="flex items-center">
                <span class="optimization-status" :class="optimizations.preloading ? 'enabled' : 'disabled'"></span>
                <span class="ml-2">Resource Preloading</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePerformanceMonitoring } from '@/Composables/usePerformanceMonitoring'
import { bundleAnalyzer } from '@/utils/bundle-analyzer'
import { performanceOptimizer } from '@/utils/performance-optimizer'
import { createLazyComponent, dynamicImportWithRetry } from '@/utils/lazy-loading'
import PerformanceDashboard from '@/Components/Performance/PerformanceDashboard.vue'
import LazyImage from '@/Components/Performance/LazyImage.vue'

// Performance monitoring
const {
  metrics,
  alerts,
  getAverageMetrics,
  getPerformanceScore
} = usePerformanceMonitoring('PerformanceOptimizationDemo')

// Component state
const loadingChart = ref(false)
const loadingMap = ref(false)
const loadingSearch = ref(false)
const chartComponent = ref(null)
const mapComponent = ref(null)
const searchComponent = ref(null)

const optimizations = ref({
  lazyLoading: false,
  imageOptimization: false,
  caching: false,
  preloading: false
})

// Computed properties
const currentMetrics = computed(() => getAverageMetrics())
const performanceScore = computed(() => getPerformanceScore())
const bundleInfo = computed(() => bundleAnalyzer.getAllBundles())
const totalBundleSize = computed(() => bundleAnalyzer.getTotalBundleSize())

// Chart data for demo
const chartData = ref({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
  datasets: [{
    label: 'Performance Score',
    data: [65, 70, 75, 80, 85, 90],
    borderColor: 'rgb(59, 130, 246)',
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
    tension: 0.4
  }]
})

// Methods
const loadChartComponent = async () => {
  if (chartComponent.value) return
  
  loadingChart.value = true
  try {
    const module = await dynamicImportWithRetry(() => import('chart.js'))
    // Create a simple chart component
    chartComponent.value = createLazyComponent(
      () => import('@/Components/Charts/LineChart.vue').catch(() => {
        // Fallback to a simple div if component doesn't exist
        return {
          template: '<div class="h-64 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">Chart Component Loaded</div>'
        }
      })
    )
  } catch (error) {
    console.error('Failed to load chart component:', error)
  } finally {
    loadingChart.value = false
  }
}

const loadMapComponent = async () => {
  if (mapComponent.value) return
  
  loadingMap.value = true
  try {
    mapComponent.value = createLazyComponent(
      () => import('@/Components/AlumniMap.vue').catch(() => {
        return {
          template: '<div class="h-64 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">Map Component Loaded</div>'
        }
      })
    )
  } catch (error) {
    console.error('Failed to load map component:', error)
  } finally {
    loadingMap.value = false
  }
}

const loadSearchComponent = async () => {
  if (searchComponent.value) return
  
  loadingSearch.value = true
  try {
    searchComponent.value = createLazyComponent(
      () => import('@/Components/AdvancedSearch.vue').catch(() => {
        return {
          template: '<div class="h-32 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">Advanced Search Component Loaded</div>'
        }
      })
    )
  } catch (error) {
    console.error('Failed to load search component:', error)
  } finally {
    loadingSearch.value = false
  }
}

const generateBundleReport = () => {
  const report = bundleAnalyzer.generateReport()
  const blob = new Blob([report], { type: 'text/markdown' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `bundle-analysis-${new Date().toISOString().split('T')[0]}.md`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const optimizePage = () => {
  performanceOptimizer.optimizePage()
  optimizations.value = {
    lazyLoading: true,
    imageOptimization: true,
    caching: true,
    preloading: true
  }
}

const preloadCriticalResources = () => {
  // Preload critical resources
  const criticalResources = [
    '/build/assets/app.css',
    '/build/assets/app.js'
  ]
  
  criticalResources.forEach(resource => {
    const link = document.createElement('link')
    link.rel = 'preload'
    link.href = resource
    link.as = resource.endsWith('.css') ? 'style' : 'script'
    document.head.appendChild(link)
  })
  
  optimizations.value.preloading = true
}

const enableLazyLoading = () => {
  // Enable lazy loading for all images
  const images = document.querySelectorAll('img:not([loading])')
  images.forEach(img => {
    img.setAttribute('loading', 'lazy')
  })
  
  optimizations.value.lazyLoading = true
}

const compressImages = () => {
  // This would typically be done server-side or during build
  // For demo purposes, we'll just mark it as enabled
  optimizations.value.imageOptimization = true
}

// Utility functions
const formatTime = (time?: number): string => {
  if (!time) return '0ms'
  return `${Math.round(time)}ms`
}

const formatSize = (size?: number): string => {
  if (!size) return '0B'
  const units = ['B', 'KB', 'MB', 'GB']
  let unitIndex = 0
  let value = size
  
  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024
    unitIndex++
  }
  
  return `${value.toFixed(1)}${units[unitIndex]}`
}

const getScoreClass = (score: number): string => {
  if (score >= 90) return 'bg-green-500'
  if (score >= 70) return 'bg-yellow-500'
  return 'bg-red-500'
}

onMounted(() => {
  // Initialize performance optimizer
  performanceOptimizer.optimizePage()
})
</script>

<style scoped>
.performance-optimization-demo {
  @apply min-h-screen bg-gray-50 dark:bg-gray-900;
}

.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500;
}

.btn-success {
  @apply text-white bg-green-600 hover:bg-green-700 focus:ring-green-500;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.bundle-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4;
}

.bundle-header {
  @apply flex justify-between items-center mb-2;
}

.bundle-status {
  @apply px-2 py-1 rounded-full text-xs font-medium;
}

.bundle-status.loaded {
  @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.bundle-status.not-loaded {
  @apply bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200;
}

.bundle-details {
  @apply space-y-1;
}

.bundle-metric {
  @apply flex justify-between text-sm;
}

.metric-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center;
}

.metric-label {
  @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.metric-value {
  @apply text-2xl font-bold text-gray-900 dark:text-white mt-1;
}

.progress-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2;
}

.progress-fill {
  @apply h-2 rounded-full transition-all duration-300;
}

.alert {
  @apply rounded-lg p-4;
}

.alert-warning {
  @apply bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200;
}

.optimization-status {
  @apply w-3 h-3 rounded-full;
}

.optimization-status.enabled {
  @apply bg-green-500;
}

.optimization-status.disabled {
  @apply bg-gray-300 dark:bg-gray-600;
}
</style>