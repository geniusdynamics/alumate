<template>
  <div class="component-coverage">
    <div class="coverage-header">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Component Coverage Analysis
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Theme compatibility across all component categories
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="runCoverageAnalysis"
            class="btn-secondary"
            :disabled="analysisRunning"
          >
            <Icon name="search" class="w-4 h-4 mr-2" :class="{ 'animate-pulse': analysisRunning }" />
            {{ analysisRunning ? 'Analyzing...' : 'Analyze Coverage' }}
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

    <!-- Overall Coverage Score -->
    <div class="coverage-overview">
      <div class="coverage-score-card">
        <div class="score-circle" :class="getCoverageScoreClass(overallCoverage)">
          <div class="score-number">{{ overallCoverage }}%</div>
          <div class="score-label">Coverage</div>
        </div>
        <div class="coverage-stats">
          <div class="stat-item">
            <span class="stat-label">Supported:</span>
            <span class="stat-value text-green-600">{{ supportedComponents }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Partial:</span>
            <span class="stat-value text-yellow-600">{{ partialComponents }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Unsupported:</span>
            <span class="stat-value text-red-600">{{ unsupportedComponents }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Total Components:</span>
            <span class="stat-value text-gray-900 dark:text-white">{{ totalComponents }}</span>
          </div>
        </div>
      </div>

      <!-- Comparison Coverage (if comparison theme provided) -->
      <div v-if="comparisonTheme" class="coverage-comparison">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          Coverage Comparison
        </h4>
        <div class="comparison-bars">
          <div class="comparison-item">
            <div class="comparison-label">{{ theme.name }}</div>
            <div class="comparison-bar">
              <div
                class="comparison-fill current"
                :style="{ width: `${overallCoverage}%` }"
              ></div>
            </div>
            <div class="comparison-percentage">{{ overallCoverage }}%</div>
          </div>
          <div class="comparison-item">
            <div class="comparison-label">{{ comparisonTheme.name }}</div>
            <div class="comparison-bar">
              <div
                class="comparison-fill comparison"
                :style="{ width: `${comparisonCoverage}%` }"
              ></div>
            </div>
            <div class="comparison-percentage">{{ comparisonCoverage }}%</div>
          </div>
        </div>
        <div class="coverage-difference">
          <Icon
            :name="overallCoverage > comparisonCoverage ? 'trending-up' : overallCoverage < comparisonCoverage ? 'trending-down' : 'minus'"
            class="w-4 h-4"
            :class="overallCoverage > comparisonCoverage ? 'text-green-600' : overallCoverage < comparisonCoverage ? 'text-red-600' : 'text-gray-600'"
          />
          <span class="difference-text">
            {{ Math.abs(overallCoverage - comparisonCoverage) }}% 
            {{ overallCoverage > comparisonCoverage ? 'better' : overallCoverage < comparisonCoverage ? 'worse' : 'same' }} coverage
          </span>
        </div>
      </div>
    </div>

    <!-- Component Categories -->
    <div class="component-categories">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Component Categories
      </h4>
      <div class="categories-grid">
        <div
          v-for="category in componentCategories"
          :key="category.id"
          class="category-card"
          :class="getCategoryStatusClass(category.status)"
          @click="toggleCategoryDetails(category.id)"
        >
          <div class="category-header">
            <div class="category-icon">
              <Icon :name="category.icon" class="w-6 h-6" />
            </div>
            <div class="category-info">
              <h5 class="category-name">{{ category.name }}</h5>
              <p class="category-description">{{ category.description }}</p>
            </div>
            <div class="category-status">
              <div class="status-indicator" :class="getStatusIndicatorClass(category.status)">
                <Icon :name="getStatusIcon(category.status)" class="w-4 h-4" />
              </div>
              <div class="coverage-percentage">{{ category.coverage }}%</div>
            </div>
          </div>

          <!-- Category Details -->
          <div v-if="expandedCategories.includes(category.id)" class="category-details">
            <div class="details-header">
              <span class="details-title">Components in this category:</span>
              <span class="details-count">{{ category.components.length }} total</span>
            </div>
            
            <div class="components-list">
              <div
                v-for="component in category.components"
                :key="component.id"
                class="component-item"
                :class="getComponentStatusClass(component.status)"
              >
                <div class="component-header">
                  <Icon :name="getStatusIcon(component.status)" class="w-4 h-4" />
                  <span class="component-name">{{ component.name }}</span>
                  <span class="component-status-badge" :class="getStatusBadgeClass(component.status)">
                    {{ component.status }}
                  </span>
                </div>
                
                <div v-if="component.issues && component.issues.length > 0" class="component-issues">
                  <div class="issues-header">
                    <Icon name="alert-triangle" class="w-4 h-4 text-yellow-600" />
                    <span class="issues-title">Issues ({{ component.issues.length }})</span>
                  </div>
                  <ul class="issues-list">
                    <li
                      v-for="issue in component.issues"
                      :key="issue.id"
                      class="issue-item"
                    >
                      <span class="issue-text">{{ issue.description }}</span>
                      <span class="issue-severity" :class="getSeverityClass(issue.severity)">
                        {{ issue.severity }}
                      </span>
                    </li>
                  </ul>
                </div>

                <div v-if="component.recommendations && component.recommendations.length > 0" class="component-recommendations">
                  <div class="recommendations-header">
                    <Icon name="lightbulb" class="w-4 h-4 text-blue-600" />
                    <span class="recommendations-title">Recommendations</span>
                  </div>
                  <ul class="recommendations-list">
                    <li
                      v-for="recommendation in component.recommendations"
                      :key="recommendation.id"
                      class="recommendation-item"
                    >
                      {{ recommendation.text }}
                    </li>
                  </ul>
                </div>

                <!-- Comparison Data -->
                <div v-if="comparisonTheme && component.comparisonData" class="component-comparison">
                  <div class="comparison-header">
                    <Icon name="git-compare" class="w-4 h-4 text-gray-600" />
                    <span class="comparison-title">Comparison</span>
                  </div>
                  <div class="comparison-status">
                    <div class="status-row">
                      <span class="status-label">Current:</span>
                      <span class="status-value" :class="getStatusBadgeClass(component.status)">
                        {{ component.status }}
                      </span>
                    </div>
                    <div class="status-row">
                      <span class="status-label">{{ comparisonTheme.name }}:</span>
                      <span class="status-value" :class="getStatusBadgeClass(component.comparisonData.status)">
                        {{ component.comparisonData.status }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Missing Components -->
    <div v-if="missingComponents.length > 0" class="missing-components">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Missing Components
      </h4>
      <div class="missing-list">
        <div
          v-for="missing in missingComponents"
          :key="missing.id"
          class="missing-item"
        >
          <div class="missing-header">
            <Icon name="x-circle" class="w-5 h-5 text-red-600" />
            <span class="missing-name">{{ missing.name }}</span>
            <span class="missing-category">{{ missing.category }}</span>
          </div>
          <p class="missing-description">{{ missing.description }}</p>
          <div class="missing-impact">
            <span class="impact-label">Impact:</span>
            <span class="impact-value" :class="getImpactClass(missing.impact)">
              {{ missing.impact }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Coverage Recommendations -->
    <div class="coverage-recommendations">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Coverage Improvement Recommendations
      </h4>
      <div class="recommendations-grid">
        <div
          v-for="recommendation in coverageRecommendations"
          :key="recommendation.id"
          class="recommendation-card"
        >
          <div class="recommendation-header">
            <Icon :name="recommendation.icon" class="w-5 h-5 text-blue-600" />
            <span class="recommendation-title">{{ recommendation.title }}</span>
            <span class="recommendation-priority" :class="getPriorityClass(recommendation.priority)">
              {{ recommendation.priority }}
            </span>
          </div>
          <p class="recommendation-description">{{ recommendation.description }}</p>
          <div class="recommendation-details">
            <div class="detail-item">
              <span class="detail-label">Effort:</span>
              <span class="detail-value">{{ recommendation.effort }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Impact:</span>
              <span class="detail-value">{{ recommendation.expectedImpact }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Coverage Trends -->
    <div class="coverage-trends">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Coverage Trends
      </h4>
      <div class="trends-chart">
        <div class="chart-container">
          <div class="chart-bars">
            <div
              v-for="(trend, index) in coverageTrends"
              :key="index"
              class="trend-bar"
            >
              <div class="bar-container">
                <div
                  class="bar-fill"
                  :style="{ height: `${trend.coverage}%` }"
                  :class="getTrendBarClass(trend.coverage)"
                ></div>
              </div>
              <div class="bar-label">{{ trend.period }}</div>
              <div class="bar-value">{{ trend.coverage }}%</div>
            </div>
          </div>
        </div>
        <div class="trend-summary">
          <div class="summary-item">
            <Icon name="trending-up" class="w-4 h-4 text-green-600" />
            <span class="summary-text">Coverage improved by {{ coverageImprovement }}% over time</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import { useNotifications } from '@/composables/useNotifications'
import type { GrapeJSThemeData } from '@/types/components'

interface Props {
  theme: GrapeJSThemeData
  comparisonTheme?: GrapeJSThemeData | null
}

const props = defineProps<Props>()

// State
const analysisRunning = ref(false)
const expandedCategories = ref<string[]>([])
const componentCategories = ref<any[]>([])
const missingComponents = ref<any[]>([])
const coverageRecommendations = ref<any[]>([])
const coverageTrends = ref<any[]>([])

const { showNotification } = useNotifications()

// Computed
const totalComponents = computed(() => 
  componentCategories.value.reduce((sum, category) => sum + category.components.length, 0)
)

const supportedComponents = computed(() => 
  componentCategories.value.reduce((sum, category) => 
    sum + category.components.filter((c: any) => c.status === 'supported').length, 0
  )
)

const partialComponents = computed(() => 
  componentCategories.value.reduce((sum, category) => 
    sum + category.components.filter((c: any) => c.status === 'partial').length, 0
  )
)

const unsupportedComponents = computed(() => 
  componentCategories.value.reduce((sum, category) => 
    sum + category.components.filter((c: any) => c.status === 'unsupported').length, 0
  )
)

const overallCoverage = computed(() => {
  if (totalComponents.value === 0) return 0
  const weightedScore = (supportedComponents.value * 100) + (partialComponents.value * 50)
  return Math.round(weightedScore / totalComponents.value)
})

const comparisonCoverage = computed(() => {
  if (!props.comparisonTheme) return 0
  // Simulate comparison coverage calculation
  return Math.round(Math.random() * 100)
})

const coverageImprovement = computed(() => {
  if (coverageTrends.value.length < 2) return 0
  const latest = coverageTrends.value[coverageTrends.value.length - 1].coverage
  const earliest = coverageTrends.value[0].coverage
  return latest - earliest
})

// Methods
const runCoverageAnalysis = async () => {
  analysisRunning.value = true
  
  try {
    // Simulate analysis delay
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    generateComponentCategories()
    generateMissingComponents()
    generateCoverageRecommendations()
    generateCoverageTrends()
    
    showNotification('Coverage analysis completed', 'success')
  } catch (error) {
    console.error('Coverage analysis failed:', error)
    showNotification('Coverage analysis failed', 'error')
  } finally {
    analysisRunning.value = false
  }
}

const generateComponentCategories = () => {
  componentCategories.value = [
    {
      id: 'hero',
      name: 'Hero Components',
      description: 'Landing page headers and hero sections',
      icon: 'layout',
      status: 'supported',
      coverage: 95,
      components: [
        {
          id: 'hero-basic',
          name: 'Basic Hero',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'hero-video',
          name: 'Video Hero',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'partial' } : null
        },
        {
          id: 'hero-carousel',
          name: 'Carousel Hero',
          status: 'partial',
          issues: [
            { id: 'carousel-1', description: 'Animation timing needs adjustment', severity: 'medium' }
          ],
          recommendations: [
            { id: 'carousel-rec-1', text: 'Optimize carousel transition timing for better UX' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'unsupported' } : null
        }
      ]
    },
    {
      id: 'forms',
      name: 'Form Components',
      description: 'Input forms and validation elements',
      icon: 'edit-3',
      status: 'partial',
      coverage: 80,
      components: [
        {
          id: 'form-basic',
          name: 'Basic Form',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'form-validation',
          name: 'Form Validation',
          status: 'partial',
          issues: [
            { id: 'validation-1', description: 'Error message styling inconsistent', severity: 'low' }
          ],
          recommendations: [
            { id: 'validation-rec-1', text: 'Standardize error message appearance across all form types' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'form-multi-step',
          name: 'Multi-step Form',
          status: 'unsupported',
          issues: [
            { id: 'multistep-1', description: 'Progress indicator not styled', severity: 'high' },
            { id: 'multistep-2', description: 'Step navigation missing theme colors', severity: 'medium' }
          ],
          recommendations: [
            { id: 'multistep-rec-1', text: 'Add theme support for progress indicators and step navigation' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'partial' } : null
        }
      ]
    },
    {
      id: 'testimonials',
      name: 'Testimonial Components',
      description: 'Customer reviews and social proof',
      icon: 'message-circle',
      status: 'supported',
      coverage: 90,
      components: [
        {
          id: 'testimonial-single',
          name: 'Single Testimonial',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'testimonial-carousel',
          name: 'Testimonial Carousel',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'testimonial-video',
          name: 'Video Testimonial',
          status: 'partial',
          issues: [
            { id: 'video-test-1', description: 'Video controls need theme styling', severity: 'low' }
          ],
          recommendations: [
            { id: 'video-test-rec-1', text: 'Apply theme colors to video player controls' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'unsupported' } : null
        }
      ]
    },
    {
      id: 'statistics',
      name: 'Statistics Components',
      description: 'Data visualization and metrics',
      icon: 'bar-chart-2',
      status: 'partial',
      coverage: 75,
      components: [
        {
          id: 'stats-counter',
          name: 'Animated Counter',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'stats-progress',
          name: 'Progress Bar',
          status: 'partial',
          issues: [
            { id: 'progress-1', description: 'Progress bar colors not fully themed', severity: 'medium' }
          ],
          recommendations: [
            { id: 'progress-rec-1', text: 'Use theme accent colors for progress indicators' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'partial' } : null
        },
        {
          id: 'stats-chart',
          name: 'Chart Component',
          status: 'unsupported',
          issues: [
            { id: 'chart-1', description: 'Chart colors hardcoded', severity: 'high' },
            { id: 'chart-2', description: 'No dark mode support', severity: 'medium' }
          ],
          recommendations: [
            { id: 'chart-rec-1', text: 'Implement theme-aware chart color schemes' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'unsupported' } : null
        }
      ]
    },
    {
      id: 'ctas',
      name: 'Call-to-Action Components',
      description: 'Buttons and conversion elements',
      icon: 'mouse-pointer',
      status: 'supported',
      coverage: 100,
      components: [
        {
          id: 'cta-button',
          name: 'CTA Button',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'cta-banner',
          name: 'CTA Banner',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'cta-inline',
          name: 'Inline CTA',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        }
      ]
    },
    {
      id: 'media',
      name: 'Media Components',
      description: 'Images, videos, and galleries',
      icon: 'image',
      status: 'partial',
      coverage: 70,
      components: [
        {
          id: 'media-gallery',
          name: 'Image Gallery',
          status: 'supported',
          issues: [],
          recommendations: [],
          comparisonData: props.comparisonTheme ? { status: 'supported' } : null
        },
        {
          id: 'media-video',
          name: 'Video Player',
          status: 'partial',
          issues: [
            { id: 'video-1', description: 'Player controls not themed', severity: 'medium' }
          ],
          recommendations: [
            { id: 'video-rec-1', text: 'Style video player controls to match theme' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'partial' } : null
        },
        {
          id: 'media-interactive',
          name: 'Interactive Demo',
          status: 'unsupported',
          issues: [
            { id: 'interactive-1', description: 'No theme integration', severity: 'high' }
          ],
          recommendations: [
            { id: 'interactive-rec-1', text: 'Add theme support for interactive elements' }
          ],
          comparisonData: props.comparisonTheme ? { status: 'unsupported' } : null
        }
      ]
    }
  ]
}

const generateMissingComponents = () => {
  missingComponents.value = [
    {
      id: 'missing-1',
      name: 'Advanced Chart Component',
      category: 'Statistics',
      description: 'Interactive charts with theme-aware color schemes and animations',
      impact: 'Medium'
    },
    {
      id: 'missing-2',
      name: 'Multi-step Form Wizard',
      category: 'Forms',
      description: 'Complex form flows with progress indicators and validation',
      impact: 'High'
    },
    {
      id: 'missing-3',
      name: 'Interactive Media Player',
      category: 'Media',
      description: 'Advanced video player with custom controls and theming',
      impact: 'Low'
    }
  ]
}

const generateCoverageRecommendations = () => {
  coverageRecommendations.value = [
    {
      id: 'rec-1',
      title: 'Implement Chart Theming',
      description: 'Add theme support for chart components to improve statistics coverage',
      priority: 'High',
      effort: '2-3 days',
      expectedImpact: '+15% coverage',
      icon: 'bar-chart-2'
    },
    {
      id: 'rec-2',
      title: 'Enhance Form Validation Styling',
      description: 'Standardize error message and validation styling across all form types',
      priority: 'Medium',
      effort: '1 day',
      expectedImpact: '+8% coverage',
      icon: 'edit-3'
    },
    {
      id: 'rec-3',
      title: 'Add Video Player Theming',
      description: 'Style video player controls to match theme colors and design',
      priority: 'Low',
      effort: '1-2 days',
      expectedImpact: '+5% coverage',
      icon: 'play-circle'
    }
  ]
}

const generateCoverageTrends = () => {
  coverageTrends.value = [
    { period: 'Jan', coverage: 65 },
    { period: 'Feb', coverage: 70 },
    { period: 'Mar', coverage: 75 },
    { period: 'Apr', coverage: 80 },
    { period: 'May', coverage: 85 },
    { period: 'Jun', coverage: overallCoverage.value }
  ]
}

const toggleCategoryDetails = (categoryId: string) => {
  const index = expandedCategories.value.indexOf(categoryId)
  if (index > -1) {
    expandedCategories.value.splice(index, 1)
  } else {
    expandedCategories.value.push(categoryId)
  }
}

const exportReport = () => {
  const report = {
    theme: props.theme.name,
    analysisDate: new Date().toISOString(),
    overallCoverage: overallCoverage.value,
    summary: {
      total: totalComponents.value,
      supported: supportedComponents.value,
      partial: partialComponents.value,
      unsupported: unsupportedComponents.value
    },
    categories: componentCategories.value,
    missing: missingComponents.value,
    recommendations: coverageRecommendations.value,
    trends: coverageTrends.value
  }
  
  const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `coverage-report-${props.theme.slug || 'theme'}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
  
  showNotification('Coverage report exported', 'success')
}

const getCoverageScoreClass = (score: number) => {
  if (score >= 90) return 'score-excellent'
  if (score >= 70) return 'score-good'
  if (score >= 50) return 'score-fair'
  return 'score-poor'
}

const getCategoryStatusClass = (status: string) => {
  switch (status) {
    case 'supported':
      return 'category-supported'
    case 'partial':
      return 'category-partial'
    case 'unsupported':
      return 'category-unsupported'
    default:
      return 'category-unknown'
  }
}

const getComponentStatusClass = (status: string) => {
  switch (status) {
    case 'supported':
      return 'component-supported'
    case 'partial':
      return 'component-partial'
    case 'unsupported':
      return 'component-unsupported'
    default:
      return 'component-unknown'
  }
}

const getStatusIndicatorClass = (status: string) => {
  switch (status) {
    case 'supported':
      return 'status-supported'
    case 'partial':
      return 'status-partial'
    case 'unsupported':
      return 'status-unsupported'
    default:
      return 'status-unknown'
  }
}

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'supported':
      return 'badge-success'
    case 'partial':
      return 'badge-warning'
    case 'unsupported':
      return 'badge-error'
    default:
      return 'badge-neutral'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'supported':
      return 'check-circle'
    case 'partial':
      return 'alert-triangle'
    case 'unsupported':
      return 'x-circle'
    default:
      return 'help-circle'
  }
}

const getSeverityClass = (severity: string) => {
  switch (severity.toLowerCase()) {
    case 'high':
      return 'severity-high'
    case 'medium':
      return 'severity-medium'
    case 'low':
      return 'severity-low'
    default:
      return 'severity-medium'
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
    default:
      return 'impact-medium'
  }
}

const getPriorityClass = (priority: string) => {
  switch (priority.toLowerCase()) {
    case 'high':
      return 'priority-high'
    case 'medium':
      return 'priority-medium'
    case 'low':
      return 'priority-low'
    default:
      return 'priority-medium'
  }
}

const getTrendBarClass = (coverage: number) => {
  if (coverage >= 90) return 'trend-excellent'
  if (coverage >= 70) return 'trend-good'
  if (coverage >= 50) return 'trend-fair'
  return 'trend-poor'
}

// Lifecycle
onMounted(() => {
  generateComponentCategories()
  generateMissingComponents()
  generateCoverageRecommendations()
  generateCoverageTrends()
})
</script>

<style scoped>
.component-coverage {
  @apply space-y-6;
}

.coverage-header {
  @apply pb-4 border-b border-gray-200 dark:border-gray-700;
}

.coverage-overview {
  @apply space-y-6;
}

.coverage-score-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 flex items-center gap-6;
}

.score-circle {
  @apply w-24 h-24 rounded-full flex flex-col items-center justify-center text-white font-bold;
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
  @apply text-2xl;
}

.score-label {
  @apply text-xs uppercase;
}

.coverage-stats {
  @apply flex-1 grid grid-cols-2 gap-4;
}

.stat-item {
  @apply flex justify-between;
}

.stat-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.stat-value {
  @apply text-sm font-medium;
}

.coverage-comparison {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6;
}

.comparison-bars {
  @apply space-y-3;
}

.comparison-item {
  @apply flex items-center gap-4;
}

.comparison-label {
  @apply text-sm font-medium text-gray-900 dark:text-white min-w-32;
}

.comparison-bar {
  @apply flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-4 relative overflow-hidden;
}

.comparison-fill {
  @apply h-full rounded-full transition-all duration-500;
}

.comparison-fill.current {
  @apply bg-blue-500;
}

.comparison-fill.comparison {
  @apply bg-gray-400;
}

.comparison-percentage {
  @apply text-sm font-medium text-gray-900 dark:text-white min-w-12 text-right;
}

.coverage-difference {
  @apply flex items-center gap-2 mt-4 justify-center;
}

.difference-text {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.component-categories {
  @apply space-y-4;
}

.categories-grid {
  @apply space-y-4;
}

.category-card {
  @apply border rounded-lg overflow-hidden cursor-pointer transition-all duration-200;
}

.category-supported {
  @apply border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20;
}

.category-partial {
  @apply border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20;
}

.category-unsupported {
  @apply border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20;
}

.category-unknown {
  @apply border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800;
}

.category-header {
  @apply flex items-center gap-4 p-4;
}

.category-icon {
  @apply flex-shrink-0 w-12 h-12 rounded-lg bg-white dark:bg-gray-700 flex items-center justify-center;
}

.category-info {
  @apply flex-1;
}

.category-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.category-description {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.category-status {
  @apply flex items-center gap-3;
}

.status-indicator {
  @apply w-8 h-8 rounded-full flex items-center justify-center;
}

.status-supported {
  @apply bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400;
}

.status-partial {
  @apply bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400;
}

.status-unsupported {
  @apply bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400;
}

.status-unknown {
  @apply bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400;
}

.coverage-percentage {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.category-details {
  @apply px-4 pb-4 border-t border-gray-200 dark:border-gray-700;
}

.details-header {
  @apply flex justify-between items-center mb-3 pt-3;
}

.details-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.details-count {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.components-list {
  @apply space-y-3;
}

.component-item {
  @apply border rounded-lg p-3;
}

.component-supported {
  @apply border-green-200 bg-green-25 dark:border-green-800 dark:bg-green-900/10;
}

.component-partial {
  @apply border-yellow-200 bg-yellow-25 dark:border-yellow-800 dark:bg-yellow-900/10;
}

.component-unsupported {
  @apply border-red-200 bg-red-25 dark:border-red-800 dark:bg-red-900/10;
}

.component-unknown {
  @apply border-gray-200 bg-gray-25 dark:border-gray-700 dark:bg-gray-800/50;
}

.component-header {
  @apply flex items-center gap-3 mb-2;
}

.component-name {
  @apply flex-1 font-medium text-gray-900 dark:text-white;
}

.component-status-badge {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.badge-success {
  @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.badge-warning {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.badge-error {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.badge-neutral {
  @apply bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200;
}

.component-issues {
  @apply mt-3 space-y-2;
}

.issues-header {
  @apply flex items-center gap-2;
}

.issues-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.issues-list {
  @apply space-y-1 ml-6;
}

.issue-item {
  @apply flex justify-between items-center text-sm;
}

.issue-text {
  @apply text-gray-600 dark:text-gray-400;
}

.issue-severity {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.severity-high {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.severity-medium {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.severity-low {
  @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.component-recommendations {
  @apply mt-3 space-y-2;
}

.recommendations-header {
  @apply flex items-center gap-2;
}

.recommendations-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.recommendations-list {
  @apply space-y-1 ml-6;
}

.recommendation-item {
  @apply text-sm text-blue-600 dark:text-blue-400;
}

.component-comparison {
  @apply mt-3 space-y-2;
}

.comparison-header {
  @apply flex items-center gap-2;
}

.comparison-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.comparison-status {
  @apply space-y-1 ml-6;
}

.status-row {
  @apply flex justify-between items-center text-sm;
}

.status-label {
  @apply text-gray-600 dark:text-gray-400;
}

.status-value {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.missing-components {
  @apply space-y-4;
}

.missing-list {
  @apply space-y-3;
}

.missing-item {
  @apply border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 rounded-lg p-4;
}

.missing-header {
  @apply flex items-center gap-3 mb-2;
}

.missing-name {
  @apply flex-1 font-medium text-gray-900 dark:text-white;
}

.missing-category {
  @apply px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded;
}

.missing-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-2;
}

.missing-impact {
  @apply flex justify-between items-center;
}

.impact-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.impact-value {
  @apply text-sm font-medium;
}

.impact-high {
  @apply text-red-600;
}

.impact-medium {
  @apply text-yellow-600;
}

.impact-low {
  @apply text-blue-600;
}

.coverage-recommendations {
  @apply space-y-4;
}

.recommendations-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.recommendation-card {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg p-4;
}

.recommendation-header {
  @apply flex items-center gap-3 mb-2;
}

.recommendation-title {
  @apply flex-1 font-medium text-gray-900 dark:text-white;
}

.recommendation-priority {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.priority-high {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.priority-medium {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.priority-low {
  @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.recommendation-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-3;
}

.recommendation-details {
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

.coverage-trends {
  @apply space-y-4;
}

.trends-chart {
  @apply space-y-4;
}

.chart-container {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6;
}

.chart-bars {
  @apply flex items-end justify-between gap-2 h-32;
}

.trend-bar {
  @apply flex-1 flex flex-col items-center;
}

.bar-container {
  @apply w-full h-24 bg-gray-200 dark:bg-gray-600 rounded-t relative;
}

.bar-fill {
  @apply absolute bottom-0 left-0 right-0 rounded-t transition-all duration-500;
}

.trend-excellent {
  @apply bg-green-500;
}

.trend-good {
  @apply bg-blue-500;
}

.trend-fair {
  @apply bg-yellow-500;
}

.trend-poor {
  @apply bg-red-500;
}

.bar-label {
  @apply text-xs text-gray-600 dark:text-gray-400 mt-2;
}

.bar-value {
  @apply text-xs font-medium text-gray-900 dark:text-white;
}

.trend-summary {
  @apply flex justify-center;
}

.summary-item {
  @apply flex items-center gap-2;
}

.summary-text {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>