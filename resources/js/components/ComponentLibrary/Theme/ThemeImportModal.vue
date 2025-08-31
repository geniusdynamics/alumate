<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
          Import Theme
        </h3>
        <button @click="$emit('cancel')" class="btn-close">
          <Icon name="x" class="w-5 h-5" />
        </button>
      </div>

      <div class="modal-body">
        <div class="import-tabs">
          <button
            v-for="tab in importTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="tab-button"
            :class="{ 'tab-button--active': activeTab === tab.id }"
          >
            <Icon :name="tab.icon" class="w-4 h-4 mr-2" />
            {{ tab.label }}
          </button>
        </div>

        <div class="tab-content">
          <!-- JSON Import -->
          <div v-if="activeTab === 'json'" class="import-section">
            <div class="form-group">
              <label for="json-input" class="form-label">
                Paste Theme JSON Configuration
              </label>
              <textarea
                id="json-input"
                v-model="jsonInput"
                class="json-textarea"
                placeholder="Paste your theme JSON configuration here..."
                rows="12"
                @input="validateJson"
              ></textarea>
              <div v-if="jsonError" class="error-message">
                <Icon name="alert-circle" class="w-4 h-4 mr-2" />
                {{ jsonError }}
              </div>
            </div>
            
            <div class="form-group">
              <label for="theme-name-json" class="form-label">Theme Name</label>
              <input
                id="theme-name-json"
                v-model="importData.name"
                type="text"
                class="form-input"
                placeholder="Enter theme name"
                required
              />
            </div>
          </div>

          <!-- File Import -->
          <div v-if="activeTab === 'file'" class="import-section">
            <div class="file-drop-zone" @drop="handleFileDrop" @dragover.prevent @dragenter.prevent>
              <input
                ref="fileInput"
                type="file"
                accept=".json,.css"
                class="hidden"
                @change="handleFileSelect"
              />
              
              <div v-if="!selectedFile" class="drop-zone-content">
                <Icon name="upload" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                  Drop your theme file here
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  Supports JSON theme files and CSS files
                </p>
                <button @click="$refs.fileInput?.click()" class="btn-primary">
                  Choose File
                </button>
              </div>
              
              <div v-else class="file-selected">
                <div class="file-info">
                  <Icon name="file" class="w-8 h-8 text-blue-600 mr-3" />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">
                      {{ selectedFile.name }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ formatFileSize(selectedFile.size) }}
                    </p>
                  </div>
                </div>
                <button @click="clearFile" class="btn-icon text-red-600">
                  <Icon name="x" class="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <div v-if="selectedFile" class="form-group">
              <label for="theme-name-file" class="form-label">Theme Name</label>
              <input
                id="theme-name-file"
                v-model="importData.name"
                type="text"
                class="form-input"
                placeholder="Enter theme name"
                required
              />
            </div>
          </div>

          <!-- URL Import -->
          <div v-if="activeTab === 'url'" class="import-section">
            <div class="form-group">
              <label for="theme-url" class="form-label">
                Theme URL
              </label>
              <input
                id="theme-url"
                v-model="urlInput"
                type="url"
                class="form-input"
                placeholder="https://example.com/theme.json"
                @input="validateUrl"
              />
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Import theme from a remote URL (JSON format)
              </p>
              <div v-if="urlError" class="error-message">
                <Icon name="alert-circle" class="w-4 h-4 mr-2" />
                {{ urlError }}
              </div>
            </div>
            
            <div class="form-group">
              <label for="theme-name-url" class="form-label">Theme Name</label>
              <input
                id="theme-name-url"
                v-model="importData.name"
                type="text"
                class="form-input"
                placeholder="Enter theme name"
                required
              />
            </div>
          </div>

          <!-- GrapeJS Import -->
          <div v-if="activeTab === 'grapejs'" class="import-section">
            <div class="form-group">
              <label for="grapejs-config" class="form-label">
                GrapeJS Style Manager Configuration
              </label>
              <textarea
                id="grapejs-config"
                v-model="grapeJSInput"
                class="json-textarea"
                placeholder="Paste GrapeJS style manager configuration here..."
                rows="10"
                @input="validateGrapeJS"
              ></textarea>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Import theme from existing GrapeJS style manager configuration
              </p>
              <div v-if="grapeJSError" class="error-message">
                <Icon name="alert-circle" class="w-4 h-4 mr-2" />
                {{ grapeJSError }}
              </div>
            </div>
            
            <div class="form-group">
              <label for="theme-name-grapejs" class="form-label">Theme Name</label>
              <input
                id="theme-name-grapejs"
                v-model="importData.name"
                type="text"
                class="form-input"
                placeholder="Enter theme name"
                required
              />
            </div>
          </div>
        </div>

        <!-- Preview Section -->
        <div v-if="previewData" class="preview-section">
          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Import Preview
          </h4>
          <div class="preview-grid">
            <div class="preview-item">
              <span class="preview-label">Colors:</span>
              <div class="color-swatches">
                <div
                  v-for="(color, name) in previewData.colors"
                  :key="name"
                  class="color-swatch"
                  :style="{ backgroundColor: color }"
                  :title="`${name}: ${color}`"
                ></div>
              </div>
            </div>
            <div class="preview-item">
              <span class="preview-label">Font Family:</span>
              <span class="preview-value">{{ previewData.typography?.font_family || 'Not specified' }}</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Base Spacing:</span>
              <span class="preview-value">{{ previewData.spacing?.base || 'Not specified' }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="$emit('cancel')" class="btn-secondary">
          Cancel
        </button>
        <button
          @click="handleImport"
          class="btn-primary"
          :disabled="!canImport || importing"
        >
          <Icon v-if="importing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
          Import Theme
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Icon from '@/components/Common/Icon.vue'

const emit = defineEmits<{
  import: [data: any]
  cancel: []
}>()

// State
const activeTab = ref('json')
const importing = ref(false)
const selectedFile = ref<File | null>(null)

const importTabs = [
  { id: 'json', label: 'JSON', icon: 'code' },
  { id: 'file', label: 'File Upload', icon: 'upload' },
  { id: 'url', label: 'URL', icon: 'link' },
  { id: 'grapejs', label: 'GrapeJS Config', icon: 'settings' }
]

// Form data
const importData = ref({
  name: '',
  source: 'json'
})

// Input data
const jsonInput = ref('')
const urlInput = ref('')
const grapeJSInput = ref('')

// Validation errors
const jsonError = ref('')
const urlError = ref('')
const grapeJSError = ref('')

// Preview data
const previewData = ref<any>(null)

// Computed
const canImport = computed(() => {
  if (!importData.value.name.trim()) return false
  
  switch (activeTab.value) {
    case 'json':
      return jsonInput.value.trim() && !jsonError.value
    case 'file':
      return selectedFile.value !== null
    case 'url':
      return urlInput.value.trim() && !urlError.value
    case 'grapejs':
      return grapeJSInput.value.trim() && !grapeJSError.value
    default:
      return false
  }
})

// Methods
const handleOverlayClick = () => {
  emit('cancel')
}

const validateJson = () => {
  jsonError.value = ''
  previewData.value = null
  
  if (!jsonInput.value.trim()) return
  
  try {
    const parsed = JSON.parse(jsonInput.value)
    
    // Validate theme structure
    if (!parsed.config && !parsed.colors && !parsed.typography) {
      jsonError.value = 'Invalid theme format. Expected theme configuration object.'
      return
    }
    
    previewData.value = parsed.config || parsed
  } catch (error) {
    jsonError.value = 'Invalid JSON format'
  }
}

const validateUrl = () => {
  urlError.value = ''
  
  if (!urlInput.value.trim()) return
  
  try {
    new URL(urlInput.value)
  } catch {
    urlError.value = 'Invalid URL format'
  }
}

const validateGrapeJS = () => {
  grapeJSError.value = ''
  previewData.value = null
  
  if (!grapeJSInput.value.trim()) return
  
  try {
    const parsed = JSON.parse(grapeJSInput.value)
    
    // Validate GrapeJS structure
    if (!parsed.sectors && !parsed.styles) {
      grapeJSError.value = 'Invalid GrapeJS format. Expected style manager configuration.'
      return
    }
    
    // Convert GrapeJS config to theme preview
    previewData.value = convertGrapeJSToPreview(parsed)
  } catch (error) {
    grapeJSError.value = 'Invalid JSON format'
  }
}

const convertGrapeJSToPreview = (grapeJSConfig: any) => {
  // Convert GrapeJS style manager config to theme preview format
  const preview: any = {
    colors: {},
    typography: {},
    spacing: {}
  }
  
  if (grapeJSConfig.styles) {
    Object.entries(grapeJSConfig.styles).forEach(([key, value]) => {
      if (key.startsWith('--theme-color-')) {
        const colorName = key.replace('--theme-color-', '')
        preview.colors[colorName] = value
      } else if (key === '--theme-font-family') {
        preview.typography.font_family = value
      } else if (key.startsWith('--theme-spacing-')) {
        const spacingName = key.replace('--theme-spacing-', '')
        preview.spacing[spacingName] = value
      }
    })
  }
  
  return preview
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
    processFile(target.files[0])
  }
}

const handleFileDrop = (event: DragEvent) => {
  event.preventDefault()
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    selectedFile.value = event.dataTransfer.files[0]
    processFile(event.dataTransfer.files[0])
  }
}

const processFile = async (file: File) => {
  if (file.type === 'application/json' || file.name.endsWith('.json')) {
    try {
      const text = await file.text()
      const parsed = JSON.parse(text)
      previewData.value = parsed.config || parsed
      
      // Auto-fill theme name from filename
      if (!importData.value.name) {
        importData.value.name = file.name.replace(/\.[^/.]+$/, '')
      }
    } catch (error) {
      console.error('Failed to parse JSON file:', error)
    }
  } else if (file.type === 'text/css' || file.name.endsWith('.css')) {
    // Handle CSS file import (extract CSS variables)
    try {
      const text = await file.text()
      previewData.value = extractCSSVariables(text)
      
      if (!importData.value.name) {
        importData.value.name = file.name.replace(/\.[^/.]+$/, '')
      }
    } catch (error) {
      console.error('Failed to parse CSS file:', error)
    }
  }
}

const extractCSSVariables = (css: string) => {
  const preview: any = {
    colors: {},
    typography: {},
    spacing: {}
  }
  
  // Extract CSS custom properties
  const variableRegex = /--([\w-]+):\s*([^;]+);/g
  let match
  
  while ((match = variableRegex.exec(css)) !== null) {
    const [, name, value] = match
    
    if (name.startsWith('theme-color-') || name.startsWith('color-')) {
      const colorName = name.replace(/^(theme-)?color-/, '')
      preview.colors[colorName] = value.trim()
    } else if (name.includes('font')) {
      preview.typography[name.replace(/^theme-/, '')] = value.trim()
    } else if (name.includes('spacing') || name.includes('margin') || name.includes('padding')) {
      preview.spacing[name.replace(/^theme-/, '')] = value.trim()
    }
  }
  
  return preview
}

const clearFile = () => {
  selectedFile.value = null
  previewData.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleImport = async () => {
  importing.value = true
  
  try {
    let themeConfig: any = {}
    
    switch (activeTab.value) {
      case 'json':
        themeConfig = JSON.parse(jsonInput.value)
        break
      case 'file':
        if (selectedFile.value) {
          const text = await selectedFile.value.text()
          if (selectedFile.value.type === 'application/json') {
            themeConfig = JSON.parse(text)
          } else {
            // CSS file - convert to theme config
            themeConfig = { config: extractCSSVariables(text) }
          }
        }
        break
      case 'url':
        const response = await fetch(urlInput.value)
        themeConfig = await response.json()
        break
      case 'grapejs':
        themeConfig = {
          source: 'grapejs',
          styles: JSON.parse(grapeJSInput.value)
        }
        break
    }
    
    const importPayload = {
      name: importData.value.name,
      source: activeTab.value,
      config: themeConfig.config || themeConfig,
      grapeJSConfig: activeTab.value === 'grapejs' ? JSON.parse(grapeJSInput.value) : undefined
    }
    
    emit('import', importPayload)
  } catch (error) {
    console.error('Import failed:', error)
  } finally {
    importing.value = false
  }
}

// Watch for tab changes to clear errors
watch(activeTab, () => {
  jsonError.value = ''
  urlError.value = ''
  grapeJSError.value = ''
  previewData.value = null
})
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.modal-body {
  @apply flex-1 overflow-y-auto p-6 space-y-6;
}

.modal-footer {
  @apply flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700;
}

.import-tabs {
  @apply flex border-b border-gray-200 dark:border-gray-700;
}

.tab-button {
  @apply flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border-b-2 border-transparent hover:border-gray-300 dark:hover:border-gray-600;
}

.tab-button--active {
  @apply text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400;
}

.tab-content {
  @apply mt-6;
}

.import-section {
  @apply space-y-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white;
}

.json-textarea {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white font-mono text-sm;
}

.error-message {
  @apply flex items-center text-sm text-red-600 dark:text-red-400;
}

.file-drop-zone {
  @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors;
}

.drop-zone-content {
  @apply space-y-4;
}

.file-selected {
  @apply flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.file-info {
  @apply flex items-center;
}

.preview-section {
  @apply mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.preview-grid {
  @apply space-y-3;
}

.preview-item {
  @apply flex items-center justify-between;
}

.preview-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.preview-value {
  @apply text-sm text-gray-900 dark:text-white;
}

.color-swatches {
  @apply flex gap-2;
}

.color-swatch {
  @apply w-6 h-6 rounded border border-gray-300 dark:border-gray-600;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200;
}

.btn-close {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.btn-icon {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200;
}
</style>