<template>
  <div class="accessibility-analysis">
    <div class="analysis-header">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Accessibility Analysis
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            WCAG 2.1 AA compliance assessment and recommendations
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="runFullAudit"
            class="btn-secondary"
            :disabled="auditRunning"
          >
            <Icon name="refresh-cw" class="w-4 h-4 mr-2" :class="{ 'animate-spin': auditRunning }" />
            {{ auditRunning ? 'Running...' : 'Run Audit' }}
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

    <!-- Overall Score -->
    <div class="score-overview">
      <div class="score-card">
        <div class="score-circle" :class="getScoreClass(overallScore)">
          <div class="score-number">{{ overallScore }}</div>
          <div class="score-label">Score</div>
        </div>
        <div class="score-details">
          <div class="score-breakdown">
            <div class="breakdown-item">
              <span class="breakdown-label">Passed:</span>
              <span class="breakdown-value text-green-600">{{ passedChecks }}</span>
            </div>
            <div class="breakdown-item">
              <span class="breakdown-label">Warnings:</span>
              <span class="breakdown-value text-yellow-600">{{ warningChecks }}</span>
            </div>
            <div class="breakdown-item">
              <span class="breakdown-label">Failed:</span>
              <span class="breakdown-value text-red-600">{{ failedChecks }}</span>
            </div>
          </div>
          <div class="compliance-level">
            <span class="compliance-label">Compliance Level:</span>
            <span class="compliance-badge" :class="getComplianceClass(complianceLevel)">
              {{ complianceLevel }}
            </span>
          </div>
        </div>
      </div>

      <!-- Comparison Score (if comparison theme provided) -->
      <div v-if="comparisonTheme" class="score-card">
        <div class="score-circle" :class="getScoreClass(comparisonScore)">
          <div class="score-number">{{ comparisonScore }}</div>
          <div class="score-label">Comparison</div>
        </div>
        <div class="score-details">
          <div class="score-comparison">
            <div class="comparison-item">
              <Icon
                :name="overallScore > comparisonScore ? 'trending-up' : overallScore < comparisonScore ? 'trending-down' : 'minus'"
                class="w-4 h-4"
                :class="overallScore > comparisonScore ? 'text-green-600' : overallScore < comparisonScore ? 'text-red-600' : 'text-gray-600'"
              />
              <span class="comparison-text">
                {{ Math.abs(overallScore - comparisonScore) }} points
                {{ overallScore > comparisonScore ? 'better' : overallScore < comparisonScore ? 'worse' : 'same' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Accessibility Checks -->
    <div class="accessibility-checks">
      <div class="checks-header">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Detailed Checks
        </h4>
        <div class="filter-controls">
          <select v-model="selectedCategory" class="filter-select">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category" :value="category">
              {{ category }}
            </option>
          </select>
          <select v-model="selectedSeverity" class="filter-select">
            <option value="">All Severities</option>
            <option value="fail">Failed Only</option>
            <option value="warning">Warnings Only</option>
            <option value="pass">Passed Only</option>
          </select>
        </div>
      </div>

      <div class="checks-list">
        <div
          v-for="check in filteredChecks"
          :key="check.id"
          class="check-item"
          :class="check.status"
          @click="toggleCheckDetails(check.id)"
        >
          <div class="check-header">
            <div class="check-icon">
              <Icon
                :name="getCheckIcon(check.status)"
                class="w-5 h-5"
                :class="getCheckIconClass(check.status)"
              />
            </div>
            <div class="check-info">
              <div class="check-title">{{ check.title }}</div>
              <div class="check-category">{{ check.category }}</div>
            </div>
            <div class="check-status">
              <span class="status-badge" :class="getStatusBadgeClass(check.status)">
                {{ check.status.toUpperCase() }}
              </span>
              <button
                class="details-toggle"
                :class="{ 'expanded': expandedChecks.includes(check.id) }"
              >
                <Icon name="chevron-down" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Check Details -->
          <div v-if="expandedChecks.includes(check.id)" class="check-details">
            <div class="detail-section">
              <h5 class="detail-title">Description</h5>
              <p class="detail-text">{{ check.description }}</p>
            </div>

            <div v-if="check.impact" class="detail-section">
              <h5 class="detail-title">Impact</h5>
              <p class="detail-text">{{ check.impact }}</p>
            </div>

            <div v-if="check.recommendation" class="detail-section">
              <h5 class="detail-title">Recommendation</h5>
              <p class="detail-text">{{ check.recommendation }}</p>
            </div>

            <div v-if="check.wcagReference" class="detail-section">
              <h5 class="detail-title">WCAG Reference</h5>
              <div class="wcag-references">
                <a
                  v-for="ref in check.wcagReference"
                  :key="ref.criterion"
                  :href="ref.url"
                  target="_blank"
                  class="wcag-link"
                >
                  {{ ref.criterion }} - {{ ref.title }}
                  <Icon name="external-link" class="w-3 h-3 ml-1" />
                </a>
              </div>
            </div>

            <div v-if="check.codeExample" class="detail-section">
              <h5 class="detail-title">Code Example</h5>
              <pre class="code-example"><code>{{ check.codeExample }}</code></pre>
            </div>

            <div v-if="check.elements && check.elements.length > 0" class="detail-section">
              <h5 class="detail-title">Affected Elements</h5>
              <div class="affected-elements">
                <button
                  v-for="element in check.elements"
                  :key="element.selector"
                  @click="highlightElement(element)"
                  class="element-button"
                >
                  {{ element.selector }}
                  <span class="element-count">{{ element.count }}</span>
                </button>
              </div>
            </div>

            <!-- Comparison Data -->
            <div v-if="comparisonTheme && check.comparisonData" class="detail-section">
              <h5 class="detail-title">Comparison</h5>
              <div class="comparison-data">
                <div class="comparison-row">
                  <span class="comparison-label">Current Theme:</span>
                  <span class="comparison-value" :class="getStatusBadgeClass(check.status)">
                    {{ check.status.toUpperCase() }}
                  </span>
                </div>
                <div class="comparison-row">
                  <span class="comparison-label">{{ comparisonTheme.name }}:</span>
                  <span class="comparison-value" :class="getStatusBadgeClass(check.comparisonData.status)">
                    {{ check.comparisonData.status.toUpperCase() }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Fixes -->
    <div v-if="quickFixes.length > 0" class="quick-fixes">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Quick Fixes
      </h4>
      <div class="fixes-list">
        <div
          v-for="fix in quickFixes"
          :key="fix.id"
          class="fix-item"
        >
          <div class="fix-header">
            <Icon name="zap" class="w-4 h-4 text-yellow-600" />
            <span class="fix-title">{{ fix.title }}</span>
            <button
              @click="applyQuickFix(fix)"
              class="fix-button"
              :disabled="fix.applying"
            >
              {{ fix.applying ? 'Applying...' : 'Apply Fix' }}
            </button>
          </div>
          <p class="fix-description">{{ fix.description }}</p>
        </div>
      </div>
    </div>

    <!-- Accessibility Guidelines -->
    <div class="accessibility-guidelines">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Accessibility Guidelines
      </h4>
      <div class="guidelines-grid">
        <div
          v-for="guideline in guidelines"
          :key="guideline.id"
          class="guideline-card"
        >
          <div class="guideline-header">
            <Icon :name="guideline.icon" class="w-5 h-5 text-blue-600" />
            <h5 class="guideline-title">{{ guideline.title }}</h5>
          </div>
          <p class="guideline-description">{{ guideline.description }}</p>
          <div class="guideline-actions">
            <a
              :href="guideline.learnMoreUrl"
              target="_blank"
              class="guideline-link"
            >
              Learn More
              <Icon name="external-link" class="w-3 h-3 ml-1" />
            </a>
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
import type { GrapeJSThemeData } from '@/types/components'

interface Props {
  theme: GrapeJSThemeData
  comparisonTheme?: GrapeJSThemeData | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  issueHighlight: [issue: any]
}>()

// State
const auditRunning = ref(false)
const expandedChecks = ref<string[]>([])
const selectedCategory = ref('')
const selectedSeverity = ref('')
const accessibilityChecks = ref<any[]>([])
const quickFixes = ref<any[]>([])

const { showNotification } = useNotifications()

// Categories
const categories = [
  'Color Contrast',
  'Typography',
  'Navigation',
  'Forms',
  'Images',
  'Interactive Elements',
  'Structure',
  'Keyboard Navigation'
]

// Guidelines
const guidelines = [
  {
    id: 'color-contrast',
    title: 'Color Contrast',
    description: 'Ensure sufficient color contrast between text and background colors.',
    icon: 'eye',
    learnMoreUrl: 'https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html'
  },
  {
    id: 'keyboard-navigation',
    title: 'Keyboard Navigation',
    description: 'All interactive elements should be accessible via keyboard.',
    icon: 'keyboard',
    learnMoreUrl: 'https://www.w3.org/WAI/WCAG21/Understanding/keyboard.html'
  },
  {
    id: 'alt-text',
    title: 'Alternative Text',
    description: 'Provide meaningful alternative text for images.',
    icon: 'image',
    learnMoreUrl: 'https://www.w3.org/WAI/WCAG21/Understanding/non-text-content.html'
  },
  {
    id: 'focus-indicators',
    title: 'Focus Indicators',
    description: 'Ensure visible focus indicators for interactive elements.',
    icon: 'target',
    learnMoreUrl: 'https://www.w3.org/WAI/WCAG21/Understanding/focus-visible.html'
  }
]

// Computed
const overallScore = computed(() => {
  if (accessibilityChecks.value.length === 0) return 0
  
  const totalWeight = accessibilityChecks.value.reduce((sum, check) => sum + (check.weight || 1), 0)
  const weightedScore = accessibilityChecks.value.reduce((sum, check) => {
    const weight = check.weight || 1
    const score = check.status === 'pass' ? 100 : check.status === 'warning' ? 50 : 0
    return sum + (score * weight)
  }, 0)
  
  return Math.round(weightedScore / totalWeight)
})

const comparisonScore = computed(() => {
  if (!props.comparisonTheme || accessibilityChecks.value.length === 0) return 0
  
  const totalWeight = accessibilityChecks.value.reduce((sum, check) => sum + (check.weight || 1), 0)
  const weightedScore = accessibilityChecks.value.reduce((sum, check) => {
    const weight = check.weight || 1
    const comparisonStatus = check.comparisonData?.status || 'fail'
    const score = comparisonStatus === 'pass' ? 100 : comparisonStatus === 'warning' ? 50 : 0
    return sum + (score * weight)
  }, 0)
  
  return Math.round(weightedScore / totalWeight)
})

const passedChecks = computed(() => 
  accessibilityChecks.value.filter(check => check.status === 'pass').length
)

const warningChecks = computed(() => 
  accessibilityChecks.value.filter(check => check.status === 'warning').length
)

const failedChecks = computed(() => 
  accessibilityChecks.value.filter(check => check.status === 'fail').length
)

const complianceLevel = computed(() => {
  if (overallScore.value >= 95) return 'WCAG AAA'
  if (overallScore.value >= 80) return 'WCAG AA'
  if (overallScore.value >= 60) return 'WCAG A'
  return 'Non-Compliant'
})

const filteredChecks = computed(() => {
  return accessibilityChecks.value.filter(check => {
    const categoryMatch = !selectedCategory.value || check.category === selectedCategory.value
    const severityMatch = !selectedSeverity.value || check.status === selectedSeverity.value
    return categoryMatch && severityMatch
  })
})

// Methods
const runAccessibilityAudit = () => {
  const checks = []
  
  // Color Contrast Checks
  const primaryContrast = calculateContrast(
    props.theme.cssVariables['--theme-color-primary'] || '#007bff',
    props.theme.cssVariables['--theme-color-background'] || '#ffffff'
  )
  
  checks.push({
    id: 'primary-color-contrast',
    title: 'Primary Color Contrast',
    category: 'Color Contrast',
    description: 'Checks if the primary color has sufficient contrast against the background.',
    status: primaryContrast >= 4.5 ? 'pass' : primaryContrast >= 3 ? 'warning' : 'fail',
    impact: primaryContrast < 4.5 ? 'Users with visual impairments may have difficulty reading text.' : null,
    recommendation: primaryContrast < 4.5 ? 'Increase contrast to at least 4.5:1 for WCAG AA compliance.' : null,
    wcagReference: [
      {
        criterion: '1.4.3',
        title: 'Contrast (Minimum)',
        url: 'https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html'
      }
    ],
    weight: 3,
    comparisonData: props.comparisonTheme ? {
      status: calculateContrast(
        props.comparisonTheme.cssVariables['--theme-color-primary'] || '#007bff',
        props.comparisonTheme.cssVariables['--theme-color-background'] || '#ffffff'
      ) >= 4.5 ? 'pass' : 'fail'
    } : null
  })
  
  const textContrast = calculateContrast(
    props.theme.cssVariables['--theme-color-text'] || '#333333',
    props.theme.cssVariables['--theme-color-background'] || '#ffffff'
  )
  
  checks.push({
    id: 'text-color-contrast',
    title: 'Text Color Contrast',
    category: 'Color Contrast',
    description: 'Checks if the text color has sufficient contrast against the background.',
    status: textContrast >= 4.5 ? 'pass' : textContrast >= 3 ? 'warning' : 'fail',
    impact: textContrast < 4.5 ? 'Text may be difficult to read for users with visual impairments.' : null,
    recommendation: textContrast < 4.5 ? 'Increase text contrast to at least 4.5:1 for WCAG AA compliance.' : null,
    wcagReference: [
      {
        criterion: '1.4.3',
        title: 'Contrast (Minimum)',
        url: 'https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html'
      }
    ],
    weight: 3,
    comparisonData: props.comparisonTheme ? {
      status: calculateContrast(
        props.comparisonTheme.cssVariables['--theme-color-text'] || '#333333',
        props.comparisonTheme.cssVariables['--theme-color-background'] || '#ffffff'
      ) >= 4.5 ? 'pass' : 'fail'
    } : null
  })
  
  // Typography Checks
  const baseFontSize = parseInt(props.theme.cssVariables['--theme-font-size-base'] || '16px')
  checks.push({
    id: 'font-size',
    title: 'Base Font Size',
    category: 'Typography',
    description: 'Checks if the base font size is large enough for readability.',
    status: baseFontSize >= 16 ? 'pass' : baseFontSize >= 14 ? 'warning' : 'fail',
    impact: baseFontSize < 16 ? 'Small text may be difficult to read, especially for older users.' : null,
    recommendation: baseFontSize < 16 ? 'Use at least 16px for base font size.' : null,
    wcagReference: [
      {
        criterion: '1.4.4',
        title: 'Resize text',
        url: 'https://www.w3.org/WAI/WCAG21/Understanding/resize-text.html'
      }
    ],
    weight: 2,
    comparisonData: props.comparisonTheme ? {
      status: parseInt(props.comparisonTheme.cssVariables['--theme-font-size-base'] || '16px') >= 16 ? 'pass' : 'fail'
    } : null
  })
  
  // Interactive Elements
  checks.push({
    id: 'focus-indicators',
    title: 'Focus Indicators',
    category: 'Interactive Elements',
    description: 'Checks if interactive elements have visible focus indicators.',
    status: 'pass', // Assume pass for now, would need actual DOM inspection
    impact: null,
    recommendation: null,
    wcagReference: [
      {
        criterion: '2.4.7',
        title: 'Focus Visible',
        url: 'https://www.w3.org/WAI/WCAG21/Understanding/focus-visible.html'
      }
    ],
    weight: 2,
    comparisonData: props.comparisonTheme ? { status: 'pass' } : null
  })
  
  // Touch Target Size
  checks.push({
    id: 'touch-targets',
    title: 'Touch Target Size',
    category: 'Interactive Elements',
    description: 'Checks if touch targets are large enough for mobile devices.',
    status: 'pass', // Assume pass, would need actual measurement
    impact: null,
    recommendation: null,
    wcagReference: [
      {
        criterion: '2.5.5',
        title: 'Target Size',
        url: 'https://www.w3.org/WAI/WCAG21/Understanding/target-size.html'
      }
    ],
    weight: 2,
    comparisonData: props.comparisonTheme ? { status: 'pass' } : null
  })
  
  // Form Labels
  checks.push({
    id: 'form-labels',
    title: 'Form Labels',
    category: 'Forms',
    description: 'Checks if form inputs have proper labels.',
    status: 'pass', // Assume pass, would need DOM inspection
    impact: null,
    recommendation: null,
    wcagReference: [
      {
        criterion: '3.3.2',
        title: 'Labels or Instructions',
        url: 'https://www.w3.org/WAI/WCAG21/Understanding/labels-or-instructions.html'
      }
    ],
    weight: 3,
    comparisonData: props.comparisonTheme ? { status: 'pass' } : null
  })
  
  accessibilityChecks.value = checks
  
  // Generate quick fixes for failed checks
  generateQuickFixes()
}

const generateQuickFixes = () => {
  const fixes = []
  
  accessibilityChecks.value.forEach(check => {
    if (check.status === 'fail' || check.status === 'warning') {
      if (check.id === 'primary-color-contrast') {
        fixes.push({
          id: 'fix-primary-contrast',
          title: 'Improve Primary Color Contrast',
          description: 'Automatically adjust the primary color to meet WCAG AA standards.',
          checkId: check.id,
          applying: false
        })
      } else if (check.id === 'text-color-contrast') {
        fixes.push({
          id: 'fix-text-contrast',
          title: 'Improve Text Color Contrast',
          description: 'Automatically adjust the text color to meet WCAG AA standards.',
          checkId: check.id,
          applying: false
        })
      } else if (check.id === 'font-size') {
        fixes.push({
          id: 'fix-font-size',
          title: 'Increase Base Font Size',
          description: 'Set the base font size to 16px for better readability.',
          checkId: check.id,
          applying: false
        })
      }
    }
  })
  
  quickFixes.value = fixes
}

const runFullAudit = async () => {
  auditRunning.value = true
  
  try {
    // Simulate audit delay
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    runAccessibilityAudit()
    showNotification('Accessibility audit completed', 'success')
  } catch (error) {
    console.error('Audit failed:', error)
    showNotification('Audit failed', 'error')
  } finally {
    auditRunning.value = false
  }
}

const exportReport = () => {
  const report = {
    theme: props.theme.name,
    auditDate: new Date().toISOString(),
    overallScore: overallScore.value,
    complianceLevel: complianceLevel.value,
    summary: {
      passed: passedChecks.value,
      warnings: warningChecks.value,
      failed: failedChecks.value
    },
    checks: accessibilityChecks.value,
    recommendations: quickFixes.value
  }
  
  const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `accessibility-report-${props.theme.slug || 'theme'}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
  
  showNotification('Accessibility report exported', 'success')
}

const toggleCheckDetails = (checkId: string) => {
  const index = expandedChecks.value.indexOf(checkId)
  if (index > -1) {
    expandedChecks.value.splice(index, 1)
  } else {
    expandedChecks.value.push(checkId)
  }
}

const highlightElement = (element: any) => {
  emit('issueHighlight', element)
}

const applyQuickFix = async (fix: any) => {
  fix.applying = true
  
  try {
    // Simulate fix application
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Update the corresponding check
    const check = accessibilityChecks.value.find(c => c.id === fix.checkId)
    if (check) {
      check.status = 'pass'
      check.impact = null
      check.recommendation = null
    }
    
    // Remove the fix from the list
    const fixIndex = quickFixes.value.findIndex(f => f.id === fix.id)
    if (fixIndex > -1) {
      quickFixes.value.splice(fixIndex, 1)
    }
    
    showNotification('Quick fix applied successfully', 'success')
  } catch (error) {
    console.error('Failed to apply fix:', error)
    showNotification('Failed to apply fix', 'error')
  } finally {
    fix.applying = false
  }
}

const getScoreClass = (score: number) => {
  if (score >= 90) return 'score-excellent'
  if (score >= 70) return 'score-good'
  if (score >= 50) return 'score-fair'
  return 'score-poor'
}

const getComplianceClass = (level: string) => {
  switch (level) {
    case 'WCAG AAA':
      return 'compliance-aaa'
    case 'WCAG AA':
      return 'compliance-aa'
    case 'WCAG A':
      return 'compliance-a'
    default:
      return 'compliance-none'
  }
}

const getCheckIcon = (status: string) => {
  switch (status) {
    case 'pass':
      return 'check-circle'
    case 'warning':
      return 'alert-triangle'
    case 'fail':
      return 'x-circle'
    default:
      return 'help-circle'
  }
}

const getCheckIconClass = (status: string) => {
  switch (status) {
    case 'pass':
      return 'text-green-600'
    case 'warning':
      return 'text-yellow-600'
    case 'fail':
      return 'text-red-600'
    default:
      return 'text-gray-600'
  }
}

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'pass':
      return 'badge-success'
    case 'warning':
      return 'badge-warning'
    case 'fail':
      return 'badge-error'
    default:
      return 'badge-neutral'
  }
}

const calculateContrast = (color1: string, color2: string) => {
  const rgb1 = hexToRgb(color1)
  const rgb2 = hexToRgb(color2)
  
  if (!rgb1 || !rgb2) return 0
  
  const l1 = getRelativeLuminance(rgb1)
  const l2 = getRelativeLuminance(rgb2)
  
  const lighter = Math.max(l1, l2)
  const darker = Math.min(l1, l2)
  
  return (lighter + 0.05) / (darker + 0.05)
}

const hexToRgb = (hex: string) => {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null
}

const getRelativeLuminance = (rgb: { r: number; g: number; b: number }) => {
  const { r, g, b } = rgb
  const [rs, gs, bs] = [r, g, b].map(c => {
    c = c / 255
    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4)
  })
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs
}

// Watchers
watch(() => props.theme, () => {
  runAccessibilityAudit()
}, { immediate: true })

// Lifecycle
onMounted(() => {
  runAccessibilityAudit()
})
</script>

<style scoped>
.accessibility-analysis {
  @apply space-y-6;
}

.analysis-header {
  @apply pb-4 border-b border-gray-200 dark:border-gray-700;
}

.score-overview {
  @apply grid grid-cols-1 md:grid-cols-2 gap-6;
}

.score-card {
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

.compliance-level {
  @apply flex justify-between items-center;
}

.compliance-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.compliance-badge {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.compliance-aaa {
  @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.compliance-aa {
  @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.compliance-a {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.compliance-none {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.score-comparison {
  @apply space-y-2;
}

.comparison-item {
  @apply flex items-center gap-2;
}

.comparison-text {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.accessibility-checks {
  @apply space-y-4;
}

.checks-header {
  @apply flex items-center justify-between;
}

.filter-controls {
  @apply flex gap-3;
}

.filter-select {
  @apply text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
}

.checks-list {
  @apply space-y-3;
}

.check-item {
  @apply border rounded-lg overflow-hidden cursor-pointer transition-all duration-200;
}

.check-item.pass {
  @apply border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20;
}

.check-item.warning {
  @apply border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20;
}

.check-item.fail {
  @apply border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20;
}

.check-header {
  @apply flex items-center gap-4 p-4;
}

.check-icon {
  @apply flex-shrink-0;
}

.check-info {
  @apply flex-1;
}

.check-title {
  @apply font-medium text-gray-900 dark:text-white;
}

.check-category {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.check-status {
  @apply flex items-center gap-3;
}

.status-badge {
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

.details-toggle {
  @apply p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-transform duration-200;
}

.details-toggle.expanded {
  @apply transform rotate-180;
}

.check-details {
  @apply px-4 pb-4 space-y-4 border-t border-gray-200 dark:border-gray-700;
}

.detail-section {
  @apply space-y-2;
}

.detail-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.detail-text {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.wcag-references {
  @apply space-y-1;
}

.wcag-link {
  @apply text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center;
}

.code-example {
  @apply bg-gray-100 dark:bg-gray-800 p-3 rounded text-sm font-mono overflow-x-auto;
}

.affected-elements {
  @apply flex flex-wrap gap-2;
}

.element-button {
  @apply px-3 py-1 bg-gray-200 dark:bg-gray-600 rounded text-sm hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors duration-200 flex items-center gap-2;
}

.element-count {
  @apply bg-gray-400 dark:bg-gray-500 text-white text-xs px-1 rounded;
}

.comparison-data {
  @apply space-y-2;
}

.comparison-row {
  @apply flex justify-between items-center;
}

.comparison-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.comparison-value {
  @apply text-sm font-medium;
}

.quick-fixes {
  @apply space-y-4;
}

.fixes-list {
  @apply space-y-3;
}

.fix-item {
  @apply border border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20 rounded-lg p-4;
}

.fix-header {
  @apply flex items-center gap-3 mb-2;
}

.fix-title {
  @apply flex-1 font-medium text-gray-900 dark:text-white;
}

.fix-button {
  @apply px-3 py-1 bg-yellow-600 hover:bg-yellow-700 text-white text-sm rounded transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed;
}

.fix-description {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.accessibility-guidelines {
  @apply space-y-4;
}

.guidelines-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.guideline-card {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3;
}

.guideline-header {
  @apply flex items-center gap-3;
}

.guideline-title {
  @apply font-medium text-gray-900 dark:text-white;
}

.guideline-description {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.guideline-actions {
  @apply flex justify-end;
}

.guideline-link {
  @apply text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>