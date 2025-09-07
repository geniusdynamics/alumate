<template>
  <div class="export-options">
    <div class="export-header">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Export Options
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Export theme data for external design tools and platforms
        </p>
      </div>
    </div>

    <!-- Export Formats -->
    <div class="export-formats">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Export Formats
      </h4>
      <div class="formats-grid">
        <div
          v-for="format in exportFormats"
          :key="format.id"
          class="format-card"
          :class="{ 'format-selected': selectedFormats.includes(format.id) }"
          @click="toggleFormat(format.id)"
        >
          <div class="format-header">
            <Icon :name="format.icon" class="w-6 h-6" :class="format.iconColor" />
            <div class="format-info">
              <h5 class="format-name">{{ format.name }}</h5>
              <p class="format-description">{{ format.description }}</p>
            </div>
            <div class="format-checkbox">
              <input
                type="checkbox"
                :checked="selectedFormats.includes(format.id)"
                @change="toggleFormat(format.id)"
                class="checkbox"
              />
            </div>
          </div>
          
          <div v-if="selectedFormats.includes(format.id)" class="format-options">
            <div v-if="format.options && format.options.length > 0" class="options-list">
              <div
                v-for="option in format.options"
                :key="option.id"
                class="option-item"
              >
                <label class="option-label">
                  <input
                    v-model="formatSettings[format.id][option.id]"
                    :type="option.type"
                    class="option-input"
                    :min="option.min"
                    :max="option.max"
                    :step="option.step"
                  />
                  <span class="option-text">{{ option.label }}</span>
                </label>
                <p v-if="option.description" class="option-description">
                  {{ option.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Content -->
    <div class="export-content">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Content to Export
      </h4>
      <div class="content-options">
        <div class="content-grid">
          <label
            v-for="content in contentOptions"
            :key="content.id"
            class="content-option"
            :class="{ 'content-selected': selectedContent.includes(content.id) }"
          >
            <input
              v-model="selectedContent"
              :value="content.id"
              type="checkbox"
              class="content-checkbox"
            />
            <div class="content-info">
              <Icon :name="content.icon" class="w-5 h-5" :class="content.iconColor" />
              <div class="content-details">
                <span class="content-name">{{ content.name }}</span>
                <span class="content-description">{{ content.description }}</span>
              </div>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Advanced Options -->
    <div class="advanced-options">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Advanced Options
      </h4>
      <div class="advanced-grid">
        <div class="advanced-section">
          <h5 class="section-title">Compression</h5>
          <div class="section-options">
            <label class="option-label">
              <input
                v-model="advancedOptions.minify"
                type="checkbox"
                class="checkbox"
              />
              <span>Minify output files</span>
            </label>
            <label class="option-label">
              <input
                v-model="advancedOptions.compress"
                type="checkbox"
                class="checkbox"
              />
              <span>Enable compression</span>
            </label>
          </div>
        </div>

        <div class="advanced-section">
          <h5 class="section-title">Compatibility</h5>
          <div class="section-options">
            <label class="option-label">
              <input
                v-model="advancedOptions.includeVendorPrefixes"
                type="checkbox"
                class="checkbox"
              />
              <span>Include vendor prefixes</span>
            </label>
            <label class="option-label">
              <input
                v-model="advancedOptions.legacySupport"
                type="checkbox"
                class="checkbox"
              />
              <span>Legacy browser support</span>
            </label>
          </div>
        </div>

        <div class="advanced-section">
          <h5 class="section-title">Documentation</h5>
          <div class="section-options">
            <label class="option-label">
              <input
                v-model="advancedOptions.includeComments"
                type="checkbox"
                class="checkbox"
              />
              <span>Include comments</span>
            </label>
            <label class="option-label">
              <input
                v-model="advancedOptions.generateDocs"
                type="checkbox"
                class="checkbox"
              />
              <span>Generate documentation</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Preview -->
    <div v-if="selectedFormats.length > 0" class="export-preview">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Export Preview
      </h4>
      <div class="preview-tabs">
        <button
          v-for="format in selectedExportFormats"
          :key="format.id"
          @click="previewFormat = format.id"
          class="preview-tab"
          :class="{ 'preview-tab--active': previewFormat === format.id }"
        >
          <Icon :name="format.icon" class="w-4 h-4 mr-2" />
          {{ format.name }}
        </button>
      </div>
      
      <div class="preview-content">
        <div class="preview-header">
          <span class="preview-title">{{ getPreviewTitle() }}</span>
          <button
            @click="copyPreview"
            class="preview-copy"
            title="Copy to clipboard"
          >
            <Icon name="copy" class="w-4 h-4" />
          </button>
        </div>
        <pre class="preview-code"><code>{{ getPreviewContent() }}</code></pre>
      </div>
    </div>

    <!-- Export Summary -->
    <div class="export-summary">
      <div class="summary-stats">
        <div class="stat-item">
          <span class="stat-label">Formats:</span>
          <span class="stat-value">{{ selectedFormats.length }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Content Types:</span>
          <span class="stat-value">{{ selectedContent.length }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Estimated Size:</span>
          <span class="stat-value">{{ estimatedSize }}</span>
        </div>
      </div>
    </div>

    <!-- Export Actions -->
    <div class="export-actions">
      <div class="actions-left">
        <button
          @click="resetOptions"
          class="btn-secondary"
        >
          <Icon name="refresh-cw" class="w-4 h-4 mr-2" />
          Reset Options
        </button>
        <button
          @click="savePreset"
          class="btn-secondary"
        >
          <Icon name="save" class="w-4 h-4 mr-2" />
          Save Preset
        </button>
      </div>
      
      <div class="actions-right">
        <button
          @click="previewExport"
          class="btn-secondary"
          :disabled="selectedFormats.length === 0"
        >
          <Icon name="eye" class="w-4 h-4 mr-2" />
          Preview
        </button>
        <button
          @click="startExport"
          class="btn-primary"
          :disabled="selectedFormats.length === 0 || exporting"
        >
          <Icon name="download" class="w-4 h-4 mr-2" />
          {{ exporting ? 'Exporting...' : 'Export Theme' }}
        </button>
      </div>
    </div>

    <!-- Export Progress -->
    <div v-if="exporting" class="export-progress">
      <div class="progress-header">
        <span class="progress-title">Exporting Theme...</span>
        <span class="progress-percentage">{{ exportProgress }}%</span>
      </div>
      <div class="progress-bar">
        <div
          class="progress-fill"
          :style="{ width: `${exportProgress}%` }"
        ></div>
      </div>
      <div class="progress-status">{{ exportStatus }}</div>
    </div>

    <!-- Export Presets Modal -->
    <div v-if="showPresetsModal" class="modal-overlay" @click="showPresetsModal = false">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Export Presets</h3>
          <button @click="showPresetsModal = false" class="modal-close">
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>
        
        <div class="modal-body">
          <div class="presets-list">
            <div
              v-for="preset in exportPresets"
              :key="preset.id"
              class="preset-item"
              @click="loadPreset(preset)"
            >
              <div class="preset-info">
                <span class="preset-name">{{ preset.name }}</span>
                <span class="preset-description">{{ preset.description }}</span>
              </div>
              <button
                @click.stop="deletePreset(preset.id)"
                class="preset-delete"
              >
                <Icon name="trash-2" class="w-4 h-4" />
              </button>
            </div>
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
import type { GrapeJSThemeData, ThemeExportOptions } from '@/types/components'

interface Props {
  theme: GrapeJSThemeData
}

const props = defineProps<Props>()

const emit = defineEmits<{
  export: [options: ThemeExportOptions]
}>()

// State
const selectedFormats = ref<string[]>(['json'])
const selectedContent = ref<string[]>(['colors', 'typography', 'spacing'])
const previewFormat = ref('json')
const exporting = ref(false)
const exportProgress = ref(0)
const exportStatus = ref('')
const showPresetsModal = ref(false)
const formatSettings = ref<Record<string, any>>({})
const advancedOptions = ref({
  minify: false,
  compress: false,
  includeVendorPrefixes: true,
  legacySupport: false,
  includeComments: true,
  generateDocs: false
})

const { showNotification } = useNotifications()

// Export formats
const exportFormats = [
  {
    id: 'json',
    name: 'JSON',
    description: 'Standard JSON format for theme data',
    icon: 'file-text',
    iconColor: 'text-blue-600',
    options: [
      {
        id: 'indent',
        label: 'Indentation spaces',
        type: 'number',
        min: 0,
        max: 8,
        step: 1,
        description: 'Number of spaces for JSON indentation'
      }
    ]
  },
  {
    id: 'css',
    name: 'CSS Variables',
    description: 'CSS custom properties file',
    icon: 'code',
    iconColor: 'text-green-600',
    options: [
      {
        id: 'prefix',
        label: 'Variable prefix',
        type: 'text',
        description: 'Prefix for CSS variable names'
      }
    ]
  },
  {
    id: 'scss',
    name: 'SCSS Variables',
    description: 'Sass/SCSS variables file',
    icon: 'code',
    iconColor: 'text-pink-600',
    options: [
      {
        id: 'prefix',
        label: 'Variable prefix',
        type: 'text',
        description: 'Prefix for SCSS variable names'
      }
    ]
  },
  {
    id: 'tailwind',
    name: 'Tailwind Config',
    description: 'Tailwind CSS configuration',
    icon: 'wind',
    iconColor: 'text-cyan-600',
    options: []
  },
  {
    id: 'figma',
    name: 'Figma Tokens',
    description: 'Design tokens for Figma',
    icon: 'figma',
    iconColor: 'text-purple-600',
    options: []
  },
  {
    id: 'sketch',
    name: 'Sketch Palette',
    description: 'Color palette for Sketch',
    icon: 'palette',
    iconColor: 'text-orange-600',
    options: []
  }
]

// Content options
const contentOptions = [
  {
    id: 'colors',
    name: 'Colors',
    description: 'Color palette and variables',
    icon: 'palette',
    iconColor: 'text-red-500'
  },
  {
    id: 'typography',
    name: 'Typography',
    description: 'Font families, sizes, and weights',
    icon: 'type',
    iconColor: 'text-blue-500'
  },
  {
    id: 'spacing',
    name: 'Spacing',
    description: 'Margins, padding, and layout spacing',
    icon: 'move',
    iconColor: 'text-green-500'
  },
  {
    id: 'borders',
    name: 'Borders',
    description: 'Border radius, width, and styles',
    icon: 'square',
    iconColor: 'text-purple-500'
  },
  {
    id: 'shadows',
    name: 'Shadows',
    description: 'Box shadows and elevation',
    icon: 'box',
    iconColor: 'text-gray-500'
  },
  {
    id: 'animations',
    name: 'Animations',
    description: 'Transition and animation settings',
    icon: 'zap',
    iconColor: 'text-yellow-500'
  },
  {
    id: 'components',
    name: 'Components',
    description: 'Component-specific styling',
    icon: 'grid',
    iconColor: 'text-indigo-500'
  }
]

// Export presets
const exportPresets = ref([
  {
    id: 'web-dev',
    name: 'Web Development',
    description: 'CSS and JSON for web development',
    formats: ['json', 'css'],
    content: ['colors', 'typography', 'spacing', 'borders']
  },
  {
    id: 'design-system',
    name: 'Design System',
    description: 'Complete design system export',
    formats: ['json', 'figma', 'sketch'],
    content: ['colors', 'typography', 'spacing', 'borders', 'shadows']
  },
  {
    id: 'tailwind-setup',
    name: 'Tailwind Setup',
    description: 'Tailwind CSS configuration',
    formats: ['tailwind', 'json'],
    content: ['colors', 'typography', 'spacing']
  }
])

// Computed
const selectedExportFormats = computed(() => 
  exportFormats.filter(format => selectedFormats.value.includes(format.id))
)

const estimatedSize = computed(() => {
  const baseSize = selectedFormats.value.length * 2 // KB per format
  const contentMultiplier = selectedContent.value.length * 0.5
  const totalSize = baseSize + contentMultiplier
  
  if (totalSize < 1) return `${Math.round(totalSize * 1000)} B`
  if (totalSize < 1000) return `${Math.round(totalSize)} KB`
  return `${Math.round(totalSize / 1000)} MB`
})

// Methods
const toggleFormat = (formatId: string) => {
  const index = selectedFormats.value.indexOf(formatId)
  if (index > -1) {
    selectedFormats.value.splice(index, 1)
  } else {
    selectedFormats.value.push(formatId)
    
    // Initialize format settings
    if (!formatSettings.value[formatId]) {
      const format = exportFormats.find(f => f.id === formatId)
      if (format?.options) {
        formatSettings.value[formatId] = {}
        format.options.forEach(option => {
          formatSettings.value[formatId][option.id] = option.type === 'number' ? 2 : ''
        })
      }
    }
  }
  
  // Update preview format if current one is deselected
  if (!selectedFormats.value.includes(previewFormat.value) && selectedFormats.value.length > 0) {
    previewFormat.value = selectedFormats.value[0]
  }
}

const getPreviewTitle = () => {
  const format = exportFormats.find(f => f.id === previewFormat.value)
  return format ? `${format.name} Preview` : 'Preview'
}

const getPreviewContent = () => {
  switch (previewFormat.value) {
    case 'json':
      return generateJSONPreview()
    case 'css':
      return generateCSSPreview()
    case 'scss':
      return generateSCSSPreview()
    case 'tailwind':
      return generateTailwindPreview()
    case 'figma':
      return generateFigmaPreview()
    case 'sketch':
      return generateSketchPreview()
    default:
      return '// Preview not available'
  }
}

const generateJSONPreview = () => {
  const data: any = {}
  
  if (selectedContent.value.includes('colors')) {
    data.colors = {}
    Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
      if (key.includes('color')) {
        data.colors[key.replace('--theme-color-', '')] = value
      }
    })
  }
  
  if (selectedContent.value.includes('typography')) {
    data.typography = {
      fontFamily: props.theme.cssVariables['--theme-font-family'],
      fontSize: props.theme.cssVariables['--theme-font-size-base'],
      lineHeight: props.theme.cssVariables['--theme-line-height']
    }
  }
  
  if (selectedContent.value.includes('spacing')) {
    data.spacing = {}
    Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
      if (key.includes('spacing')) {
        data.spacing[key.replace('--theme-spacing-', '')] = value
      }
    })
  }
  
  const indent = formatSettings.value.json?.indent || 2
  return JSON.stringify(data, null, indent)
}

const generateCSSPreview = () => {
  const prefix = formatSettings.value.css?.prefix || '--theme'
  let css = ':root {\n'
  
  Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
    if (shouldIncludeVariable(key)) {
      const variableName = prefix ? key.replace('--theme', prefix) : key
      css += `  ${variableName}: ${value};\n`
    }
  })
  
  css += '}'
  return css
}

const generateSCSSPreview = () => {
  const prefix = formatSettings.value.scss?.prefix || '$theme'
  let scss = ''
  
  Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
    if (shouldIncludeVariable(key)) {
      const variableName = `${prefix}-${key.replace('--theme-', '').replace(/-/g, '_')}`
      scss += `${variableName}: ${value};\n`
    }
  })
  
  return scss
}

const generateTailwindPreview = () => {
  const config: any = {
    theme: {
      extend: {}
    }
  }
  
  if (selectedContent.value.includes('colors')) {
    config.theme.extend.colors = {}
    Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
      if (key.includes('color')) {
        const colorName = key.replace('--theme-color-', '').replace(/-/g, '')
        config.theme.extend.colors[colorName] = value
      }
    })
  }
  
  if (selectedContent.value.includes('typography')) {
    config.theme.extend.fontFamily = {
      sans: [props.theme.cssVariables['--theme-font-family'] || 'system-ui']
    }
  }
  
  if (selectedContent.value.includes('spacing')) {
    config.theme.extend.spacing = {}
    Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
      if (key.includes('spacing')) {
        const spacingName = key.replace('--theme-spacing-', '').replace(/-/g, '')
        config.theme.extend.spacing[spacingName] = value
      }
    })
  }
  
  return `module.exports = ${JSON.stringify(config, null, 2)}`
}

const generateFigmaPreview = () => {
  const tokens: any = {}
  
  if (selectedContent.value.includes('colors')) {
    tokens.color = {}
    Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
      if (key.includes('color')) {
        const tokenName = key.replace('--theme-color-', '').replace(/-/g, ' ')
        tokens.color[tokenName] = {
          value: value,
          type: 'color'
        }
      }
    })
  }
  
  return JSON.stringify(tokens, null, 2)
}

const generateSketchPreview = () => {
  const palette: any = {
    compatibleVersion: '2.0',
    pluginVersion: '2.0',
    colors: []
  }
  
  if (selectedContent.value.includes('colors')) {
    Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
      if (key.includes('color') && typeof value === 'string' && value.startsWith('#')) {
        palette.colors.push({
          name: key.replace('--theme-color-', '').replace(/-/g, ' '),
          red: parseInt(value.slice(1, 3), 16) / 255,
          green: parseInt(value.slice(3, 5), 16) / 255,
          blue: parseInt(value.slice(5, 7), 16) / 255,
          alpha: 1
        })
      }
    })
  }
  
  return JSON.stringify(palette, null, 2)
}

const shouldIncludeVariable = (key: string) => {
  if (selectedContent.value.includes('colors') && key.includes('color')) return true
  if (selectedContent.value.includes('typography') && (key.includes('font') || key.includes('line-height'))) return true
  if (selectedContent.value.includes('spacing') && key.includes('spacing')) return true
  if (selectedContent.value.includes('borders') && (key.includes('border') || key.includes('radius'))) return true
  if (selectedContent.value.includes('shadows') && key.includes('shadow')) return true
  if (selectedContent.value.includes('animations') && (key.includes('animation') || key.includes('transition'))) return true
  return false
}

const copyPreview = async () => {
  try {
    await navigator.clipboard.writeText(getPreviewContent())
    showNotification('Preview copied to clipboard', 'success')
  } catch (error) {
    console.error('Failed to copy preview:', error)
    showNotification('Failed to copy preview', 'error')
  }
}

const previewExport = () => {
  // Show preview in modal or expand current preview
  showNotification('Export preview generated', 'info')
}

const startExport = async () => {
  if (selectedFormats.value.length === 0) {
    showNotification('Please select at least one export format', 'warning')
    return
  }
  
  exporting.value = true
  exportProgress.value = 0
  exportStatus.value = 'Preparing export...'
  
  try {
    // Simulate export progress
    const steps = [
      'Collecting theme data...',
      'Processing colors...',
      'Processing typography...',
      'Processing spacing...',
      'Generating files...',
      'Compressing archive...',
      'Finalizing export...'
    ]
    
    for (let i = 0; i < steps.length; i++) {
      exportStatus.value = steps[i]
      exportProgress.value = Math.round(((i + 1) / steps.length) * 100)
      await new Promise(resolve => setTimeout(resolve, 500))
    }
    
    // Create export options
    const exportOptions: ThemeExportOptions = {
      formats: selectedFormats.value,
      content: selectedContent.value,
      settings: formatSettings.value,
      advanced: advancedOptions.value
    }
    
    emit('export', exportOptions)
    
    showNotification('Theme exported successfully', 'success')
  } catch (error) {
    console.error('Export failed:', error)
    showNotification('Export failed', 'error')
  } finally {
    exporting.value = false
    exportProgress.value = 0
    exportStatus.value = ''
  }
}

const resetOptions = () => {
  selectedFormats.value = ['json']
  selectedContent.value = ['colors', 'typography', 'spacing']
  formatSettings.value = {}
  advancedOptions.value = {
    minify: false,
    compress: false,
    includeVendorPrefixes: true,
    legacySupport: false,
    includeComments: true,
    generateDocs: false
  }
  previewFormat.value = 'json'
  
  showNotification('Export options reset', 'info')
}

const savePreset = () => {
  showPresetsModal.value = true
}

const loadPreset = (preset: any) => {
  selectedFormats.value = [...preset.formats]
  selectedContent.value = [...preset.content]
  showPresetsModal.value = false
  
  showNotification(`Preset "${preset.name}" loaded`, 'success')
}

const deletePreset = (presetId: string) => {
  const index = exportPresets.value.findIndex(p => p.id === presetId)
  if (index > -1) {
    exportPresets.value.splice(index, 1)
    showNotification('Preset deleted', 'success')
  }
}

// Lifecycle
onMounted(() => {
  // Initialize format settings for default formats
  selectedFormats.value.forEach(formatId => {
    const format = exportFormats.find(f => f.id === formatId)
    if (format?.options) {
      formatSettings.value[formatId] = {}
      format.options.forEach(option => {
        formatSettings.value[formatId][option.id] = option.type === 'number' ? 2 : ''
      })
    }
  })
})
</script>

<style scoped>
.export-options {
  @apply space-y-6;
}

.export-header {
  @apply pb-4 border-b border-gray-200 dark:border-gray-700;
}

.export-formats {
  @apply space-y-4;
}

.formats-grid {
  @apply space-y-3;
}

.format-card {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden cursor-pointer transition-all duration-200 hover:border-blue-300 dark:hover:border-blue-600;
}

.format-selected {
  @apply border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.format-header {
  @apply flex items-center gap-4 p-4;
}

.format-info {
  @apply flex-1;
}

.format-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.format-description {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.format-checkbox {
  @apply flex-shrink-0;
}

.checkbox {
  @apply rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700;
}

.format-options {
  @apply px-4 pb-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50;
}

.options-list {
  @apply space-y-3 pt-3;
}

.option-item {
  @apply space-y-1;
}

.option-label {
  @apply flex items-center gap-3;
}

.option-input {
  @apply px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
}

.option-text {
  @apply text-sm text-gray-900 dark:text-white;
}

.option-description {
  @apply text-xs text-gray-600 dark:text-gray-400 ml-6;
}

.export-content {
  @apply space-y-4;
}

.content-options {
  @apply space-y-4;
}

.content-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-3;
}

.content-option {
  @apply flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer transition-all duration-200 hover:border-blue-300 dark:hover:border-blue-600;
}

.content-selected {
  @apply border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.content-checkbox {
  @apply rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700;
}

.content-info {
  @apply flex items-center gap-3;
}

.content-details {
  @apply space-y-1;
}

.content-name {
  @apply block font-medium text-gray-900 dark:text-white;
}

.content-description {
  @apply block text-sm text-gray-600 dark:text-gray-400;
}

.advanced-options {
  @apply space-y-4;
}

.advanced-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-6;
}

.advanced-section {
  @apply space-y-3;
}

.section-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.section-options {
  @apply space-y-2;
}

.option-label {
  @apply flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300;
}

.export-preview {
  @apply space-y-4;
}

.preview-tabs {
  @apply flex gap-2 border-b border-gray-200 dark:border-gray-700;
}

.preview-tab {
  @apply px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border-b-2 border-transparent hover:border-gray-300 dark:hover:border-gray-600 transition-colors duration-200 flex items-center;
}

.preview-tab--active {
  @apply text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400;
}

.preview-content {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden;
}

.preview-header {
  @apply flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600;
}

.preview-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.preview-copy {
  @apply p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.preview-code {
  @apply p-4 bg-gray-900 text-gray-100 text-sm font-mono overflow-x-auto max-h-64;
}

.export-summary {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4;
}

.summary-stats {
  @apply grid grid-cols-3 gap-4;
}

.stat-item {
  @apply text-center;
}

.stat-label {
  @apply block text-sm text-gray-600 dark:text-gray-400;
}

.stat-value {
  @apply block text-lg font-semibold text-gray-900 dark:text-white;
}

.export-actions {
  @apply flex items-center justify-between;
}

.actions-left {
  @apply flex gap-3;
}

.actions-right {
  @apply flex gap-3;
}

.export-progress {
  @apply space-y-3;
}

.progress-header {
  @apply flex justify-between items-center;
}

.progress-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.progress-percentage {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.progress-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2;
}

.progress-fill {
  @apply bg-blue-600 h-full rounded-full transition-all duration-300;
}

.progress-status {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full;
}

.modal-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.modal-close {
  @apply p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.modal-body {
  @apply p-4;
}

.presets-list {
  @apply space-y-3;
}

.preset-item {
  @apply flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200;
}

.preset-info {
  @apply flex-1;
}

.preset-name {
  @apply block font-medium text-gray-900 dark:text-white;
}

.preset-description {
  @apply block text-sm text-gray-600 dark:text-gray-400;
}

.preset-delete {
  @apply p-1 rounded hover:bg-red-100 dark:hover:bg-red-900 transition-colors duration-200 text-red-600 dark:text-red-400;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>