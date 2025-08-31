<template>
  <div class="theme-preview">
    <!-- Header -->
    <div class="theme-preview-header">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Theme Preview & Testing
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Comprehensive theme visualization with accessibility and performance analysis
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="sharePreview"
            class="btn-secondary"
            :disabled="!selectedTheme"
          >
            <Icon name="share" class="w-4 h-4 mr-2" />
            Share Preview
          </button>
          <button
            @click="exportTheme"
            class="btn-secondary"
            :disabled="!selectedTheme"
          >
            <Icon name="download" class="w-4 h-4 mr-2" />
            Export Theme
          </button>
        </div>
      </div>
    </div>

    <!-- Theme Selection -->
    <div class="theme-selection">
      <div class="flex items-center gap-4 mb-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
          Primary Theme:
        </label>
        <select
          v-model="selectedThemeId"
          @change="onThemeChange"
          class="form-select"
        >
          <option value="">Select a theme</option>
          <option
            v-for="theme in themes"
            :key="theme.id"
            :value="theme.id"
          >
            {{ theme.name }}
          </option>
        </select>
      </div>

      <!-- Comparison Mode -->
      <div v-if="comparisonMode" class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
          Compare with:
        </label>
        <select
          v-model="comparisonThemeId"
          class="form-select"
        >
          <option value="">Select comparison theme</option>
          <option
            v-for="theme in themes"
            :key="theme.id"
            :value="theme.id"
            :disabled="theme.id === selectedThemeId"
          >
            {{ theme.name }}
          </option>
        </select>
      </div>

      <div class="flex items-center gap-4 mt-4">
        <button
          @click="comparisonMode = !comparisonMode"
          class="btn-secondary"
        >
          <Icon :name="comparisonMode ? 'eye' : 'compare'" class="w-4 h-4 mr-2" />
          {{ comparisonMode ? 'Single View' : 'Compare Themes' }}
        </button>
        <button
          @click="refreshPreview"
          class="btn-secondary"
          :disabled="loading"
        >
          <Icon name="refresh-cw" class="w-4 h-4 mr-2" />
          Refresh
        </button>
      </div>
    </div>

    <!-- Preview Controls -->
    <div class="preview-controls">
      <div class="control-group">
        <label class="control-label">View Mode:</label>
        <div class="control-buttons">
          <button
            v-for="view in viewModes"
            :key="view.id"
            @click="currentView = view.id"
            class="control-btn"
            :class="{ 'control-btn--active': currentView === view.id }"
          >
            <Icon :name="view.icon" class="w-4 h-4 mr-2" />
            {{ view.label }}
          </button>
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Device:</label>
        <div class="control-buttons">
          <button
            v-for="device in devices"
            :key="device.name"
            @click="currentDevice = device.name"
            class="control-btn"
            :class="{ 'control-btn--active': currentDevice === device.name }"
            :title="device.label"
          >
            <Icon :name="device.icon" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Options:</label>
        <div class="control-options">
          <label class="checkbox-label">
            <input
              v-model="showAccessibilityOverlay"
              type="checkbox"
              class="checkbox"
            />
            Accessibility Overlay
          </label>
          <label class="checkbox-label">
            <input
              v-model="showPerformanceMetrics"
              type="checkbox"
              class="checkbox"
            />
            Performance Metrics
          </label>
          <label class="checkbox-label">
            <input
              v-model="enableAnimations"
              type="checkbox"
              class="checkbox"
            />
            Enable Animations
          </label>
        </div>
      </div>
    </div>

    <!-- Main Preview Area -->
    <div class="preview-area" :class="{ 'comparison-mode': comparisonMode }">
      <!-- Single Theme Preview -->
      <div v-if="!comparisonMode && selectedTheme" class="preview-container">
        <div class="preview-header">
          <h3 class="preview-title">{{ selectedTheme.name }}</h3>
          <div class="preview-status">
            <div
              class="status-indicator"
              :class="getThemeStatusClass(selectedTheme)"
            ></div>
            <span class="status-text">{{ getThemeStatus(selectedTheme) }}</span>
          </div>
        </div>
        
        <ThemePreviewFrame
          :theme="selectedTheme"
          :view-mode="currentView"
          :device="currentDevice"
          :show-accessibility-overlay="showAccessibilityOverlay"
          :show-performance-metrics="showPerformanceMetrics"
          :enable-animations="enableAnimations"
          @performance-update="onPerformanceUpdate"
        />
      </div>

      <!-- Side-by-Side Comparison -->
      <div v-else-if="comparisonMode && selectedTheme && comparisonTheme" class="comparison-container">
        <div class="comparison-theme">
          <div class="preview-header">
            <h3 class="preview-title">{{ selectedTheme.name }}</h3>
            <div class="preview-status">
              <div
                class="status-indicator"
                :class="getThemeStatusClass(selectedTheme)"
              ></div>
              <span class="status-text">{{ getThemeStatus(selectedTheme) }}</span>
            </div>
          </div>
          
          <ThemePreviewFrame
            :theme="selectedTheme"
            :view-mode="currentView"
            :device="currentDevice"
            :show-accessibility-overlay="showAccessibilityOverlay"
            :show-performance-metrics="showPerformanceMetrics"
            :enable-animations="enableAnimations"
            @performance-update="onPerformanceUpdate"
          />
        </div>

        <div class="comparison-divider">
          <div class="divider-line"></div>
          <div class="divider-label">VS</div>
          <div class="divider-line"></div>
        </div>

        <div class="comparison-theme">
          <div class="preview-header">
            <h3 class="preview-title">{{ comparisonTheme.name }}</h3>
            <div class="preview-status">
              <div
                class="status-indicator"
                :class="getThemeStatusClass(comparisonTheme)"
              ></div>
              <span class="status-text">{{ getThemeStatus(comparisonTheme) }}</span>
            </div>
          </div>
          
          <ThemePreviewFrame
            :theme="comparisonTheme"
            :view-mode="currentView"
            :device="currentDevice"
            :show-accessibility-overlay="showAccessibilityOverlay"
            :show-performance-metrics="showPerformanceMetrics"
            :enable-animations="enableAnimations"
            @performance-update="onComparisonPerformanceUpdate"
          />
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <Icon name="palette" class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          No Theme Selected
        </h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          Select a theme from the dropdown above to start previewing
        </p>
      </div>
    </div>

    <!-- Analysis Panel -->
    <div v-if="selectedTheme" class="analysis-panel">
      <div class="analysis-tabs">
        <button
          v-for="tab in analysisTabs"
          :key="tab.id"
          @click="currentAnalysisTab = tab.id"
          class="analysis-tab"
          :class="{ 'analysis-tab--active': currentAnalysisTab === tab.id }"
        >
          <Icon :name="tab.icon" class="w-4 h-4 mr-2" />
          {{ tab.label }}
        </button>
      </div>

      <div class="analysis-content">
        <!-- Accessibility Analysis -->
        <div v-if="currentAnalysisTab === 'accessibility'" class="analysis-section">
          <AccessibilityAnalysis
            :theme="selectedTheme"
            :comparison-theme="comparisonTheme"
            @issue-highlight="onAccessibilityIssueHighlight"
          />
        </div>

        <!-- Performance Analysis -->
        <div v-if="currentAnalysisTab === 'performance'" class="analysis-section">
          <PerformanceAnalysis
            :theme="selectedTheme"
            :comparison-theme="comparisonTheme"
            :performance-data="performanceData"
            :comparison-performance-data="comparisonPerformanceData"
          />
        </div>

        <!-- Component Coverage -->
        <div v-if="currentAnalysisTab === 'coverage'" class="analysis-section">
          <ComponentCoverage
            :theme="selectedTheme"
            :comparison-theme="comparisonTheme"
          />
        </div>

        <!-- Export Options -->
        <div v-if="currentAnalysisTab === 'export'" class="analysis-section">
          <ExportOptions
            :theme="selectedTheme"
            @export="onThemeExport"
          />
        </div>
      </div>
    </div>

    <!-- Share Preview Modal -->
    <SharePreviewModal
      v-if="showShareModal"
      :theme="selectedTheme"
      :comparison-theme="comparisonTheme"
      :view-settings="shareSettings"
      @close="showShareModal = false"
      @share="onPreviewShared"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Icon from '@/components/Common/Icon.vue'
import ThemePreviewFrame from './ThemePreviewFrame.vue'
import AccessibilityAnalysis from './AccessibilityAnalysis.vue'
import PerformanceAnalysis from './PerformanceAnalysis.vue'
import ComponentCoverage from './ComponentCoverage.vue'
import ExportOptions from './ExportOptions.vue'
import SharePreviewModal from './SharePreviewModal.vue'
import { useNotifications } from '@/composables/useNotifications'
import type { GrapeJSThemeData, ThemePerformanceData, ThemeExportOptions } from '@/types/components'

interface Props {
  initialThemes?: GrapeJSThemeData[]
  initialThemeId?: number
}

const props = withDefaults(defineProps<Props>(), {
  initialThemes: () => [],
  initialThemeId: undefined
})

const emit = defineEmits<{
  themeSelected: [theme: GrapeJSThemeData]
  themeCompared: [primary: GrapeJSThemeData, comparison: GrapeJSThemeData]
}>()

// State
const themes = ref<GrapeJSThemeData[]>(props.initialThemes)
const selectedThemeId = ref<number | string>(props.initialThemeId || '')
const comparisonThemeId = ref<number | string>('')
const comparisonMode = ref(false)
const currentView = ref('components')
const currentDevice = ref('desktop')
const currentAnalysisTab = ref('accessibility')
const showAccessibilityOverlay = ref(false)
const showPerformanceMetrics = ref(false)
const enableAnimations = ref(true)
const showShareModal = ref(false)
const loading = ref(false)
const performanceData = ref<ThemePerformanceData | null>(null)
const comparisonPerformanceData = ref<ThemePerformanceData | null>(null)

const { showNotification } = useNotifications()

// View modes
const viewModes = [
  { id: 'components', label: 'Components', icon: 'grid' },
  { id: 'styleguide', label: 'Style Guide', icon: 'palette' },
  { id: 'accessibility', label: 'Accessibility', icon: 'eye' },
  { id: 'responsive', label: 'Responsive', icon: 'smartphone' }
]

// Devices
const devices = [
  { name: 'desktop', label: 'Desktop', icon: 'monitor' },
  { name: 'tablet', label: 'Tablet', icon: 'tablet' },
  { name: 'mobile', label: 'Mobile', icon: 'smartphone' }
]

// Analysis tabs
const analysisTabs = [
  { id: 'accessibility', label: 'Accessibility', icon: 'eye' },
  { id: 'performance', label: 'Performance', icon: 'zap' },
  { id: 'coverage', label: 'Coverage', icon: 'check-circle' },
  { id: 'export', label: 'Export', icon: 'download' }
]

// Computed
const selectedTheme = computed(() => 
  themes.value.find(theme => theme.id === selectedThemeId.value) || null
)

const comparisonTheme = computed(() => 
  themes.value.find(theme => theme.id === comparisonThemeId.value) || null
)

const shareSettings = computed(() => ({
  viewMode: currentView.value,
  device: currentDevice.value,
  showAccessibilityOverlay: showAccessibilityOverlay.value,
  showPerformanceMetrics: showPerformanceMetrics.value,
  enableAnimations: enableAnimations.value,
  comparisonMode: comparisonMode.value
}))

// Methods
const loadThemes = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/component-themes/grapejs')
    const data = await response.json()
    themes.value = data.themes
  } catch (error) {
    console.error('Failed to load themes:', error)
    showNotification('Failed to load themes', 'error')
  } finally {
    loading.value = false
  }
}

const onThemeChange = () => {
  if (selectedTheme.value) {
    emit('themeSelected', selectedTheme.value)
  }
}

const refreshPreview = async () => {
  if (selectedTheme.value) {
    loading.value = true
    // Simulate refresh delay
    await new Promise(resolve => setTimeout(resolve, 500))
    loading.value = false
    showNotification('Preview refreshed', 'success')
  }
}

const sharePreview = () => {
  if (!selectedTheme.value) {
    showNotification('Please select a theme to share', 'warning')
    return
  }
  showShareModal.value = true
}

const exportTheme = async () => {
  if (!selectedTheme.value) {
    showNotification('Please select a theme to export', 'warning')
    return
  }

  try {
    const exportData = {
      name: selectedTheme.value.name,
      version: '1.0.0',
      exported: new Date().toISOString(),
      config: selectedTheme.value.styleManager,
      cssVariables: selectedTheme.value.cssVariables,
      tailwindMappings: selectedTheme.value.tailwindMappings,
      accessibility: selectedTheme.value.accessibility,
      performance: performanceData.value
    }

    const blob = new Blob([JSON.stringify(exportData, null, 2)], { 
      type: 'application/json' 
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${selectedTheme.value.slug || 'theme'}-export.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)

    showNotification('Theme exported successfully', 'success')
  } catch (error) {
    console.error('Failed to export theme:', error)
    showNotification('Failed to export theme', 'error')
  }
}

const getThemeStatus = (theme: GrapeJSThemeData) => {
  const accessibilityIssues = theme.accessibility?.length || 0
  const performanceScore = theme.performance?.score || 0

  if (accessibilityIssues === 0 && performanceScore >= 90) {
    return 'Excellent'
  } else if (accessibilityIssues <= 2 && performanceScore >= 70) {
    return 'Good'
  } else if (accessibilityIssues <= 5 && performanceScore >= 50) {
    return 'Fair'
  } else {
    return 'Needs Improvement'
  }
}

const getThemeStatusClass = (theme: GrapeJSThemeData) => {
  const status = getThemeStatus(theme)
  switch (status) {
    case 'Excellent':
      return 'bg-green-500'
    case 'Good':
      return 'bg-blue-500'
    case 'Fair':
      return 'bg-yellow-500'
    default:
      return 'bg-red-500'
  }
}

const onPerformanceUpdate = (data: ThemePerformanceData) => {
  performanceData.value = data
}

const onComparisonPerformanceUpdate = (data: ThemePerformanceData) => {
  comparisonPerformanceData.value = data
}

const onAccessibilityIssueHighlight = (issue: any) => {
  // Handle accessibility issue highlighting in preview
  console.log('Highlighting accessibility issue:', issue)
}

const onThemeExport = (options: ThemeExportOptions) => {
  // Handle theme export with specific options
  console.log('Exporting theme with options:', options)
}

const onPreviewShared = (shareData: any) => {
  showNotification('Preview link generated successfully', 'success')
  showShareModal.value = false
}

// Watchers
watch([selectedTheme, comparisonTheme], ([primary, comparison]) => {
  if (primary && comparison && comparisonMode.value) {
    emit('themeCompared', primary, comparison)
  }
})

// Lifecycle
onMounted(() => {
  if (props.initialThemes.length === 0) {
    loadThemes()
  }
})
</script>

<style scoped>
.theme-preview {
  @apply space-y-6;
}

.theme-preview-header {
  @apply bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm;
}

.theme-selection {
  @apply bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm;
}

.form-select {
  @apply block w-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white;
}

.preview-controls {
  @apply bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm space-y-4;
}

.control-group {
  @apply flex items-center gap-4;
}

.control-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 min-w-20;
}

.control-buttons {
  @apply flex gap-2;
}

.control-btn {
  @apply px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200 flex items-center;
}

.control-btn--active {
  @apply bg-blue-600 text-white border-blue-600 hover:bg-blue-700;
}

.control-options {
  @apply flex gap-4;
}

.checkbox-label {
  @apply flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300;
}

.checkbox {
  @apply rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700;
}

.preview-area {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden;
}

.comparison-mode {
  @apply min-h-96;
}

.preview-container {
  @apply p-6;
}

.comparison-container {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6 p-6;
}

.comparison-theme {
  @apply space-y-4;
}

.comparison-divider {
  @apply hidden lg:flex flex-col items-center justify-center absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 z-10;
}

.divider-line {
  @apply w-px h-8 bg-gray-300 dark:bg-gray-600;
}

.divider-label {
  @apply px-3 py-1 bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400 rounded-full my-2;
}

.preview-header {
  @apply flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-700;
}

.preview-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.preview-status {
  @apply flex items-center gap-2;
}

.status-indicator {
  @apply w-3 h-3 rounded-full;
}

.status-text {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.empty-state {
  @apply flex flex-col items-center justify-center py-16 text-center;
}

.analysis-panel {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden;
}

.analysis-tabs {
  @apply flex border-b border-gray-200 dark:border-gray-700;
}

.analysis-tab {
  @apply px-6 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border-b-2 border-transparent hover:border-gray-300 dark:hover:border-gray-600 transition-colors duration-200 flex items-center;
}

.analysis-tab--active {
  @apply text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400;
}

.analysis-content {
  @apply p-6;
}

.analysis-section {
  @apply space-y-4;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>