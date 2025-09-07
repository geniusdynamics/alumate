<template>
  <div class="performance-analysis">
    <div class="analysis-header">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Performance Analysis
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Theme performance impact and optimization recommendations
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="runPerformanceTest"
            class="btn-secondary"
            :disabled="testRunning"
          >
            <Icon name="zap" class="w-4 h-4 mr-2" :class="{ 'animate-pulse': testRunning }" />
            {{ testRunning ? 'Testing...' : 'Run Test' }}
          </button>
          <button
            @click="exportReport"
            class="btn-secondary"
          >
            <Icon name="download" class="w-4 h-4 mr-2" />
            Export Report
          </button>
        </div>
      </div>
    </div>

    <!-- Performance Scores -->
    <div class="performance-scores">
      <div class="score-grid">
        <!-- Overall Performance Score -->
        <div class="score-card main-score">
          <div class="score-circle" :class="getScoreClass(overallScore)">
            <div class="score-number">{{ overallScore }}</div>
            <div class="score-label">Performance</div>
          </div>
          <div class="score-details">
            <div class="score-breakdown">
              <div class="breakdown-item">
                <span class="breakdown-label">Load Time:</span>
                <span class="breakdown-value" :class="getMetricClass(performanceData?.loadTime || 0, 'loadTime')">
                  {{ performanceData?.loadTime || 0 }}ms
                </span>
              </div>
              <div class="breakdown-item">
                <span class="breakdown-label">Bundle Size:</span>
                <span class="breakdown-value" :class="getMetricClass(performanceData?.bundleSize || 0, 'bundleSize')">
                  {{ formatBytes(performanceData?.bundleSize || 0) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Individual Metrics -->
        <div class="metric-card">
          <div class="metric-header">
            <Icon name="clock" class="w-5 h-5 text-blue-600" />
            <span class="metric-title">Load Time</span>
          </div>
          <div class="metric-value" :class="getMetricClass(performanceData?.loadTime || 0, 'loadTime')">
            {{ performanceData?.loadTime || 0 }}ms
          </div>
          <div class="metric-description">
            Time to load theme assets
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-header">
            <Icon name="cpu" class="w-5 h-5 text-green-600" />
            <span class="metric-title">Render Time</span>
          </div>
          <div class="metric-value" :class="getMetricClass(performanceData?.renderTime || 0, 'renderTime')">
            {{ performanceData?.renderTime || 0 }}ms
          </div>
          <div class="metric-description">
            Time to render components
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-header">
            <Icon name="hard-drive" class="w-5 h-5 text-purple-600" />
            <span class="metric-title">Bundle Size</span>
          </div>
          <div class="metric-value" :class="getMetricClass(performanceData?.bundleSize || 0, 'bundleSize')">
            {{ formatBytes(performanceData?.bundleSize || 0) }}
          </div>
          <div class="metric-description">
            Total theme asset size
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-header">
            <Icon name="layers" class="w-5 h-5 text-orange-600" />
            <span class="metric-title">CSS Complexity</span>
          </div>
          <div class="metric-value" :class="getComplexityClass(cssComplexity)">
            {{ cssComplexity }}
          </div>
          <div class="metric-description">
            CSS rules and selectors
          </div>
        </div>
      </div>

      <!-- Comparison Metrics (if comparison theme provided) -->
      <div v-if="comparisonTheme && comparisonPerformanceData" class="comparison-section">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          Performance Comparison
        </h4>
        <div class="comparison-grid">
          <div class="comparison-item">
            <div class="comparison-metric">Load Time</div>
            <div class="comparison-values">
              <div class="comparison-current">
                {{ performanceData?.loadTime || 0 }}ms
              </div>
              <div class="comparison-arrow">
                <Icon
                  :name="getComparisonIcon('loadTime')"
                  class="w-4 h-4"
                  :class="getComparisonClass('loadTime')"
                />
              </div>
              <div class="comparison-other">
                {{ comparisonPerformanceData.loadTime }}ms
              </div>
            </div>
            <div class="comparison-difference" :class="getComparisonClass('loadTime')">
              {{ getPerformanceDifference('loadTime') }}
            </div>
          </div>

          <div class="comparison-item">
            <div class="comparison-metric">Bundle Size</div>
            <div class="comparison-values">
              <div class="comparison-current">
                {{ formatBytes(performanceData?.bundleSize || 0) }}
              </div>
              <div class="comparison-arrow">
                <Icon
                  :name="getComparisonIcon('bundleSize')"
                  class="w-4 h-4"
                  :class="getComparisonClass('bundleSize')"
                />
              </div>
              <div class="comparison-other">
                {{ formatBytes(comparisonPerformanceData.bundleSize) }}
              </div>
            </div>
            <div class="comparison-difference" :class="getComparisonClass('bundleSize')">
              {{ getBundleSizeDifference() }}
            </div>
          </div>

          <div class="comparison-item">
            <div class="comparison-metric">Overall Score</div>
            <div class="comparison-values">
              <div class="comparison-current">
                {{ overallScore }}
              </div>
              <div class="comparison-arrow">
                <Icon
                  :name="getComparisonIcon('score')"
                  class="w-4 h-4"
                  :class="getComparisonClass('score')"
                />
              </div>
              <div class="comparison-other">
                {{ comparisonScore }}
              </div>
            </div>
            <div class="comparison-difference" :class="getComparisonClass('score')">
              {{ getScoreDifference() }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Insights -->
    <div class="performance-insights">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Performance Insights
      </h4>
      <div class="insights-grid">
        <div
          v-for="insight in performanceInsights"
          :key="insight.id"
          class="insight-card"
          :class="insight.type"
        >
          <div class="insight-header">
            <Icon
              :name="insight.icon"
              class="w-5 h-5"
              :class="getInsightIconClass(insight.type)"
            />
            <span class="insight-title">{{ insight.title }}</span>
            <span class="insight-impact" :class="getImpactClass(insight.impact)">
              {{ insight.impact }}
            </span>
          </div>
          <p class="insight-description">{{ insight.description }}</p>
          <div v-if="insight.recommendation" class="insight-recommendation">
            <strong>Recommendation:</strong> {{ insight.recommendation }}
          </div>
          <div v-if="insight.potentialSavings" class="insight-savings">
            <strong>Potential Savings:</strong> {{ insight.potentialSavings }}
          </div>
        </div>
      </div>
    </div>

    <!-- Optimization Suggestions -->
    <div class="optimization-suggestions">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Optimization Suggestions
      </h4>
      <div class="suggestions-list">
        <div
          v-for="suggestion in optimizationSuggestions"
          :key="suggestion.id"
          class="suggestion-item"
        >
          <div class="suggestion-header">
            <div class="suggestion-info">
              <Icon :name="suggestion.icon" class="w-5 h-5 text-blue-600" />
              <span class="suggestion-title">{{ suggestion.title }}</span>
              <span class="suggestion-difficulty" :class="getDifficultyClass(suggestion.difficulty)">
                {{ suggestion.difficulty }}
              </span>
            </div>
            <button
              v-if="suggestion.autoApply"
              @click="applySuggestion(suggestion)"
              class="suggestion-button"
              :disabled="suggestion.applying"
            >
              {{ suggestion.applying ? 'Applying...' : 'Apply' }}
            </button>
          </div>
          <p class="suggestion-description">{{ suggestion.description }}</p>
          <div class="suggestion-details">
            <div class="detail-item">
              <span class="detail-label">Expected Impact:</span>
              <span class="detail-value">{{ suggestion.expectedImpact }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Implementation Time:</span>
              <span class="detail-value">{{ suggestion.implementationTime }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Timeline -->
    <div class="performance-timeline">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Performance Timeline
      </h4>
      <div class="timeline-chart">
        <div class="timeline-bars">
          <div
            v-for="(phase, index) in performanceTimeline"
            :key="phase.name"
            class="timeline-bar"
            :style="{ width: `${(phase.duration / totalDuration) * 100}%` }"
          >
            <div class="bar-fill" :class="getPhaseClass(phase.type)"></div>
            <div class="bar-label">{{ phase.name }}</div>
            <div class="bar-duration">{{ phase.duration }}ms</div>
          </div>
        </div>
        <div class="timeline-legend">
          <div class="legend-item">
            <div class="legend-color css"></div>
            <span>CSS Loading</span>
          </div>
          <div class="legend-item">
            <div class="legend-color js"></div>
            <span>JavaScript</span>
          </div>
          <div class="legend-item">
            <div class="legend-color render"></div>
            <span>Rendering</span>
          </div>
          <div class="legend-item">
            <div class="legend-color paint"></div>
            <span>Painting</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Resource Breakdown -->
    <div class="resource-breakdown">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Resource Breakdown
      </h4>
      <div class="resource-chart">
        <div class="resource-items">
          <div
            v-for="resource in resourceBreakdown"
            :key="resource.type"
            class="resource-item"
          >
            <div class="resource-header">
              <Icon :name="resource.icon" class="w-4 h-4" />
              <span class="resource-type">{{ resource.type }}</span>
              <span class="resource-size">{{ formatBytes(resource.size) }}</span>
            </div>
            <div class="resource-bar">
              <div
                class="resource-fill"
                :style="{ width: `${(resource.size / totalResourceSize) * 100}%` }"
                :class="resource.colorClass"
              ></div>
            </div>
            <div class="resource-details">
              <span class="resource-count">{{ resource.count }} files</span>
              <span class="resource-percentage">{{ Math.round((resource.size / totalResourceSize) * 100) }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import { useNotifications } from '@/composables/useNotifications'
import type { GrapeJSThemeData, ThemePerformanceData } from '@/types/components'

interface Props {
  theme: GrapeJSThemeData
  comparisonTheme?: GrapeJSThemeData | null
  performanceData?: ThemePerformanceData | null
  comparisonPerformanceData?: ThemePerformanceData | null
}

const props = defineProps<Props>()

// State
const testRunning = ref(false)
const cssComplexity = ref(0)
const performanceInsights = ref<any[]>([])
const optimizationSuggestions = ref<any[]>([])
const performanceTimeline = ref<any[]>([])
const resourceBreakdown = ref<any[]>([])

const { showNotification } = useNotifications()

// Computed
const overallScore = computed(() => {
  if (!props.performanceData) return 0
  
  const loadTimeScore = Math.max(0, 100 - (props.performanceData.loadTime / 20))
  const bundleSizeScore = Math.max(0, 100 - (props.performanceData.bundleSize / 10000))
  const renderTimeScore = Math.max(0, 100 - (props.performanceData.renderTime / 15))
  
  return Math.round((loadTimeScore + bundleSizeScore + renderTimeScore) / 3)
})

const comparisonScore = computed(() => {
  if (!props.comparisonPerformanceData) return 0
  
  const loadTimeScore = Math.max(0, 100 - (props.comparisonPerformanceData.loadTime / 20))
  const bundleSizeScore = Math.max(0, 100 - (props.comparisonPerformanceData.bundleSize / 10000))
  const renderTimeScore = Math.max(0, 100 - (props.comparisonPerformanceData.renderTime / 15))
  
  return Math.round((loadTimeScore + bundleSizeScore + renderTimeScore) / 3)
})

const totalDuration = computed(() => 
  performanceTimeline.value.reduce((sum, phase) => sum + phase.duration, 0)
)

const totalResourceSize = computed(() => 
  resourceBreakdown.value.reduce((sum, resource) => sum + resource.size, 0)
)

// Methods
const runPerformanceTest = async () => {
  testRunning.value = true
  
  try {
    // Simulate performance test
    await new Promise(resolve => setTimeout(resolve, 3000))
    
    // Calculate CSS complexity
    calculateCSSComplexity()
    
    // Generate insights
    generatePerformanceInsights()
    
    // Generate optimization suggestions
    generateOptimizationSuggestions()
    
    // Generate timeline
    generatePerformanceTimeline()
    
    // Generate resource breakdown
    generateResourceBreakdown()
    
    showNotification('Performance test completed', 'success')
  } catch (error) {
    console.error('Performance test failed:', error)
    showNotification('Performance test failed', 'error')
  } finally {
    testRunning.value = false
  }
}

const calculateCSSComplexity = () => {
  // Simulate CSS complexity calculation based on theme variables
  const variableCount = Object.keys(props.theme.cssVariables).length
  const estimatedRules = variableCount * 3 // Rough estimate
  cssComplexity.value = estimatedRules
}

const generatePerformanceInsights = () => {
  const insights = []
  
  if (props.performanceData) {
    // Load time insights
    if (props.performanceData.loadTime > 2000) {
      insights.push({
        id: 'slow-load-time',
        type: 'warning',
        icon: 'clock',
        title: 'Slow Load Time',
        impact: 'High',
        description: 'Theme assets are taking longer than recommended to load.',
        recommendation: 'Consider optimizing CSS and reducing the number of custom fonts.',
        potentialSavings: `${Math.round((props.performanceData.loadTime - 1500) / 1000 * 100)}% faster loading`
      })
    }
    
    // Bundle size insights
    if (props.performanceData.bundleSize > 500000) {
      insights.push({
        id: 'large-bundle',
        type: 'error',
        icon: 'hard-drive',
        title: 'Large Bundle Size',
        impact: 'High',
        description: 'Theme bundle size is larger than recommended for optimal performance.',
        recommendation: 'Remove unused CSS rules and optimize asset compression.',
        potentialSavings: `${formatBytes(props.performanceData.bundleSize - 300000)} reduction possible`
      })
    }
    
    // CSS complexity insights
    if (cssComplexity.value > 1000) {
      insights.push({
        id: 'complex-css',
        type: 'warning',
        icon: 'layers',
        title: 'High CSS Complexity',
        impact: 'Medium',
        description: 'Theme has a high number of CSS rules which may impact rendering performance.',
        recommendation: 'Simplify CSS selectors and remove redundant rules.',
        potentialSavings: '15-25% faster rendering'
      })
    }
    
    // Positive insights
    if (props.performanceData.loadTime <= 1000) {
      insights.push({
        id: 'fast-loading',
        type: 'success',
        icon: 'zap',
        title: 'Fast Loading',
        impact: 'Positive',
        description: 'Theme loads quickly and provides excellent user experience.',
        recommendation: null,
        potentialSavings: null
      })
    }
  }
  
  performanceInsights.value = insights
}

const generateOptimizationSuggestions = () => {
  const suggestions = []
  
  suggestions.push({
    id: 'minify-css',
    title: 'Minify CSS',
    description: 'Remove whitespace and comments from CSS to reduce file size.',
    difficulty: 'Easy',
    expectedImpact: '10-15% size reduction',
    implementationTime: '5 minutes',
    icon: 'minimize',
    autoApply: true,
    applying: false
  })
  
  suggestions.push({
    id: 'optimize-fonts',
    title: 'Optimize Font Loading',
    description: 'Use font-display: swap and preload critical fonts.',
    difficulty: 'Medium',
    expectedImpact: '200-500ms faster loading',
    implementationTime: '15 minutes',
    icon: 'type',
    autoApply: false,
    applying: false
  })
  
  suggestions.push({
    id: 'remove-unused-css',
    title: 'Remove Unused CSS',
    description: 'Identify and remove CSS rules that are not being used.',
    difficulty: 'Medium',
    expectedImpact: '20-30% size reduction',
    implementationTime: '30 minutes',
    icon: 'trash-2',
    autoApply: true,
    applying: false
  })
  
  suggestions.push({
    id: 'enable-compression',
    title: 'Enable Gzip Compression',
    description: 'Compress CSS and JavaScript files for faster transfer.',
    difficulty: 'Easy',
    expectedImpact: '60-70% size reduction',
    implementationTime: '10 minutes',
    icon: 'archive',
    autoApply: false,
    applying: false
  })
  
  optimizationSuggestions.value = suggestions
}

const generatePerformanceTimeline = () => {
  performanceTimeline.value = [
    { name: 'CSS Loading', duration: 300, type: 'css' },
    { name: 'Font Loading', duration: 200, type: 'css' },
    { name: 'JavaScript', duration: 150, type: 'js' },
    { name: 'DOM Rendering', duration: 100, type: 'render' },
    { name: 'Style Calculation', duration: 80, type: 'render' },
    { name: 'Layout', duration: 60, type: 'render' },
    { name: 'Paint', duration: 40, type: 'paint' }
  ]
}

const generateResourceBreakdown = () => {
  resourceBreakdown.value = [
    {
      type: 'CSS',
      size: 150000,
      count: 3,
      icon: 'file-text',
      colorClass: 'bg-blue-500'
    },
    {
      type: 'Fonts',
      size: 200000,
      count: 2,
      icon: 'type',
      colorClass: 'bg-green-500'
    },
    {
      type: 'Images',
      size: 100000,
      count: 5,
      icon: 'image',
      colorClass: 'bg-purple-500'
    },
    {
      type: 'JavaScript',
      size: 80000,
      count: 2,
      icon: 'code',
      colorClass: 'bg-yellow-500'
    }
  ]
}

const applySuggestion = async (suggestion: any) => {
  suggestion.applying = true
  
  try {
    // Simulate applying optimization
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    showNotification(`${suggestion.title} applied successfully`, 'success')
    
    // Remove suggestion from list
    const index = optimizationSuggestions.value.findIndex(s => s.id === suggestion.id)
    if (index > -1) {
      optimizationSuggestions.value.splice(index, 1)
    }
  } catch (error) {
    console.error('Failed to apply suggestion:', error)
    showNotification('Failed to apply optimization', 'error')
  } finally {
    suggestion.applying = false
  }
}

const exportReport = () => {
  const report = {
    theme: props.theme.name,
    testDate: new Date().toISOString(),
    overallScore: overallScore.value,
    performanceData: props.performanceData,
    cssComplexity: cssComplexity.value,
    insights: performanceInsights.value,
    suggestions: optimizationSuggestions.value,
    timeline: performanceTimeline.value,
    resources: resourceBreakdown.value
  }
  
  const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `performance-report-${props.theme.slug || 'theme'}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
  
  showNotification('Performance report exported', 'success')
}

const getScoreClass = (score: number) => {
  if (score >= 90) return 'score-excellent'
  if (score >= 70) return 'score-good'
  if (score >= 50) return 'score-fair'
  return 'score-poor'
}

const getMetricClass = (value: number, type: string) => {
  const thresholds = {
    loadTime: { good: 1000, fair: 2000 },
    renderTime: { good: 600, fair: 1200 },
    bundleSize: { good: 200000, fair: 500000 }
  }
  
  const threshold = thresholds[type as keyof typeof thresholds]
  if (!threshold) return 'metric-good'
  
  if (value <= threshold.good) return 'metric-good'
  if (value <= threshold.fair) return 'metric-fair'
  return 'metric-poor'
}

const getComplexityClass = (complexity: number) => {
  if (complexity <= 500) return 'metric-good'
  if (complexity <= 1000) return 'metric-fair'
  return 'metric-poor'
}

const getInsightIconClass = (type: string) => {
  switch (type) {
    case 'success':
      return 'text-green-600'
    case 'warning':
      return 'text-yellow-600'
    case 'error':
      return 'text-red-600'
    default:
      return 'text-gray-600'
  }
}

const getImpactClass = (impact: string) => {
  switch (impact.toLowerCase()) {
    case 'high':
      return 'impact-high'
    case 'medium':
      return 'impact-medium'
    case 'low':
      return 'impact-low'
    case 'positive':
      return 'impact-positive'
    default:
      return 'impact-neutral'
  }
}

const getDifficultyClass = (difficulty: string) => {
  switch (difficulty.toLowerCase()) {
    case 'easy':
      return 'difficulty-easy'
    case 'medium':
      return 'difficulty-medium'
    case 'hard':
      return 'difficulty-hard'
    default:
      return 'difficulty-medium'
  }
}

const getPhaseClass = (type: string) => {
  switch (type) {
    case 'css':
      return 'phase-css'
    case 'js':
      return 'phase-js'
    case 'render':
      return 'phase-render'
    case 'paint':
      return 'phase-paint'
    default:
      return 'phase-other'
  }
}

const getComparisonIcon = (metric: string) => {
  if (!props.performanceData || !props.comparisonPerformanceData) return 'minus'
  
  const current = props.performanceData[metric as keyof ThemePerformanceData] as number
  const comparison = props.comparisonPerformanceData[metric as keyof ThemePerformanceData] as number
  
  if (metric === 'score') {
    return current > comparison ? 'trending-up' : current < comparison ? 'trending-down' : 'minus'
  } else {
    // For load time and bundle size, lower is better
    return current < comparison ? 'trending-up' : current > comparison ? 'trending-down' : 'minus'
  }
}

const getComparisonClass = (metric: string) => {
  if (!props.performanceData || !props.comparisonPerformanceData) return 'text-gray-600'
  
  const current = props.performanceData[metric as keyof ThemePerformanceData] as number
  const comparison = props.comparisonPerformanceData[metric as keyof ThemePerformanceData] as number
  
  if (metric === 'score') {
    return current > comparison ? 'text-green-600' : current < comparison ? 'text-red-600' : 'text-gray-600'
  } else {
    // For load time and bundle size, lower is better
    return current < comparison ? 'text-green-600' : current > comparison ? 'text-red-600' : 'text-gray-600'
  }
}

const getPerformanceDifference = (metric: string) => {
  if (!props.performanceData || !props.comparisonPerformanceData) return ''
  
  const current = props.performanceData[metric as keyof ThemePerformanceData] as number
  const comparison = props.comparisonPerformanceData[metric as keyof ThemePerformanceData] as number
  const diff = Math.abs(current - comparison)
  
  if (metric === 'loadTime' || metric === 'renderTime') {
    return `${diff}ms ${current < comparison ? 'faster' : 'slower'}`
  }
  
  return ''
}

const getBundleSizeDifference = () => {
  if (!props.performanceData || !props.comparisonPerformanceData) return ''
  
  const current = props.performanceData.bundleSize
  const comparison = props.comparisonPerformanceData.bundleSize
  const diff = Math.abs(current - comparison)
  
  return `${formatBytes(diff)} ${current < comparison ? 'smaller' : 'larger'}`
}

const getScoreDifference = () => {
  if (!props.performanceData || !props.comparisonPerformanceData) return ''
  
  const diff = Math.abs(overallScore.value - comparisonScore.value)
  return `${diff} points ${overallScore.value > comparisonScore.value ? 'better' : 'worse'}`
}

const formatBytes = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Lifecycle
onMounted(() => {
  if (props.performanceData) {
    calculateCSSComplexity()
    generatePerformanceInsights()
    generateOptimizationSuggestions()
    generatePerformanceTimeline()
    generateResourceBreakdown()
  }
})

// Watchers
watch(() => props.performanceData, (newData) => {
  if (newData) {
    calculateCSSComplexity()
    generatePerformanceInsights()
    generateOptimizationSuggestions()
    generatePerformanceTimeline()
    generateResourceBreakdown()
  }
})
</script>

<style scoped>
.performance-analysis {
  @apply space-y-6;
}

.analysis-header {
  @apply pb-4 border-b border-gray-200 dark:border-gray-700;
}

.performance-scores {
  @apply space-y-6;
}

.score-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4;
}

.score-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 flex items-center gap-6;
}

.main-score {
  @apply md:col-span-2;
}

.score-circle {
  @apply w-20 h-20 rounded-full flex flex-col items-center justify-center text-white font-bold;
}

.score-excellent {
  @apply bg-green-500;
}

.score-good {
  @apply bg-blue-500;
}

.score-fair {
  @apply bg-yellow-500;
}

.score-poor {
  @apply bg-red-500;
}

.score-number {
  @apply text-xl;
}

.score-label {
  @apply text-xs uppercase;
}

.score-details {
  @apply flex-1 space-y-3;
}

.score-breakdown {
  @apply space-y-2;
}

.breakdown-item {
  @apply flex justify-between;
}

.breakdown-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.breakdown-value {
  @apply text-sm font-medium;
}

.metric-card {
  @apply bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700;
}

.metric-header {
  @apply flex items-center gap-2 mb-2;
}

.metric-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.metric-value {
  @apply text-2xl font-bold mb-1;
}

.metric-good {
  @apply text-green-600;
}

.metric-fair {
  @apply text-yellow-600;
}

.metric-poor {
  @apply text-red-600;
}

.metric-description {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.comparison-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6;
}

.comparison-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.comparison-item {
  @apply bg-white dark:bg-gray-800 rounded-lg p-4 text-center;
}

.comparison-metric {
  @apply text-sm font-medium text-gray-900 dark:text-white mb-3;
}

.comparison-values {
  @apply flex items-center justify-center gap-3 mb-2;
}

.comparison-current {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.comparison-other {
  @apply text-lg font-semibold text-gray-600 dark:text-gray-400;
}

.comparison-arrow {
  @apply flex items-center;
}

.comparison-difference {
  @apply text-sm font-medium;
}

.performance-insights {
  @apply space-y-4;
}

.insights-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.insight-card {
  @apply rounded-lg p-4 border;
}

.insight-card.success {
  @apply border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20;
}

.insight-card.warning {
  @apply border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20;
}

.insight-card.error {
  @apply border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20;
}

.insight-header {
  @apply flex items-center gap-3 mb-2;
}

.insight-title {
  @apply flex-1 font-medium text-gray-900 dark:text-white;
}

.insight-impact {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.impact-high {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.impact-medium {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.impact-low {
  @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.impact-positive {
  @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.impact-neutral {
  @apply bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200;
}

.insight-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-2;
}

.insight-recommendation {
  @apply text-sm text-blue-600 dark:text-blue-400 mb-2;
}

.insight-savings {
  @apply text-sm text-green-600 dark:text-green-400;
}

.optimization-suggestions {
  @apply space-y-4;
}

.suggestions-list {
  @apply space-y-3;
}

.suggestion-item {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg p-4;
}

.suggestion-header {
  @apply flex items-center justify-between mb-2;
}

.suggestion-info {
  @apply flex items-center gap-3;
}

.suggestion-title {
  @apply font-medium text-gray-900 dark:text-white;
}

.suggestion-difficulty {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.difficulty-easy {
  @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.difficulty-medium {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.difficulty-hard {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.suggestion-button {
  @apply px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed;
}

.suggestion-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-3;
}

.suggestion-details {
  @apply grid grid-cols-2 gap-4;
}

.detail-item {
  @apply flex justify-between;
}

.detail-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.detail-value {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.performance-timeline {
  @apply space-y-4;
}

.timeline-chart {
  @apply space-y-4;
}

.timeline-bars {
  @apply space-y-2;
}

.timeline-bar {
  @apply relative bg-gray-200 dark:bg-gray-700 rounded h-12 flex items-center;
}

.bar-fill {
  @apply absolute inset-0 rounded;
}

.phase-css {
  @apply bg-blue-500;
}

.phase-js {
  @apply bg-yellow-500;
}

.phase-render {
  @apply bg-green-500;
}

.phase-paint {
  @apply bg-purple-500;
}

.phase-other {
  @apply bg-gray-500;
}

.bar-label {
  @apply relative z-10 px-3 text-sm font-medium text-white;
}

.bar-duration {
  @apply relative z-10 px-3 text-sm text-white ml-auto;
}

.timeline-legend {
  @apply flex flex-wrap gap-4;
}

.legend-item {
  @apply flex items-center gap-2;
}

.legend-color {
  @apply w-4 h-4 rounded;
}

.legend-color.css {
  @apply bg-blue-500;
}

.legend-color.js {
  @apply bg-yellow-500;
}

.legend-color.render {
  @apply bg-green-500;
}

.legend-color.paint {
  @apply bg-purple-500;
}

.resource-breakdown {
  @apply space-y-4;
}

.resource-chart {
  @apply space-y-4;
}

.resource-items {
  @apply space-y-3;
}

.resource-item {
  @apply space-y-2;
}

.resource-header {
  @apply flex items-center gap-3;
}

.resource-type {
  @apply flex-1 font-medium text-gray-900 dark:text-white;
}

.resource-size {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.resource-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2;
}

.resource-fill {
  @apply h-full rounded-full;
}

.resource-details {
  @apply flex justify-between text-sm text-gray-600 dark:text-gray-400;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>