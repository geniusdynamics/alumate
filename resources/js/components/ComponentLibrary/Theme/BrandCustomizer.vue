<template>
  <div class="brand-customizer">
    <!-- Header -->
    <div class="brand-customizer-header">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Brand Customizer
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage brand assets, guidelines, and styling consistency
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="showAnalyticsModal = true"
            class="btn-secondary"
          >
            <Icon name="chart-bar" class="w-4 h-4 mr-2" />
            Usage Analytics
          </button>
          <button
            @click="exportBrandAssets"
            class="btn-secondary"
          >
            <Icon name="download" class="w-4 h-4 mr-2" />
            Export Assets
          </button>
          <button
            @click="createBrandTemplate"
            class="btn-primary"
          >
            <Icon name="plus" class="w-4 h-4 mr-2" />
            New Brand Template
          </button>
        </div>
      </div>
    </div>

    <!-- Brand Assets Management -->
    <div class="brand-section">
      <div class="section-header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Brand Assets
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Upload and manage logos, colors, fonts, and other brand elements
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Logo Management -->
        <div class="asset-card">
          <div class="asset-card-header">
            <h4 class="font-medium text-gray-900 dark:text-white">Logos</h4>
            <button
              @click="uploadLogo"
              class="btn-sm btn-primary"
            >
              <Icon name="upload" class="w-4 h-4 mr-1" />
              Upload
            </button>
          </div>
          
          <div class="logo-grid">
            <div
              v-for="logo in brandAssets.logos"
              :key="logo.id"
              class="logo-item"
              :class="{ 'logo-item--primary': logo.isPrimary }"
            >
              <div class="logo-preview">
                <img
                  :src="logo.url"
                  :alt="logo.alt"
                  class="max-h-12 max-w-full object-contain"
                />
              </div>
              <div class="logo-info">
                <p class="text-xs font-medium text-gray-900 dark:text-white">
                  {{ logo.name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ logo.type }} • {{ formatFileSize(logo.size) }}
                </p>
              </div>
              <div class="logo-actions">
                <button
                  @click="setPrimaryLogo(logo)"
                  class="btn-icon"
                  :class="{ 'text-blue-600': logo.isPrimary }"
                  title="Set as Primary"
                >
                  <Icon name="star" class="w-3 h-3" />
                </button>
                <button
                  @click="optimizeLogo(logo)"
                  class="btn-icon"
                  title="Optimize"
                >
                  <Icon name="lightning-bolt" class="w-3 h-3" />
                </button>
                <button
                  @click="deleteLogo(logo)"
                  class="btn-icon text-red-600"
                  title="Delete"
                >
                  <Icon name="trash" class="w-3 h-3" />
                </button>
              </div>
            </div>
            
            <!-- Upload Placeholder -->
            <div
              v-if="brandAssets.logos.length === 0"
              class="logo-upload-placeholder"
              @click="uploadLogo"
            >
              <Icon name="photograph" class="w-8 h-8 text-gray-400 mb-2" />
              <p class="text-sm text-gray-500">Upload your first logo</p>
            </div>
          </div>
        </div>

        <!-- Color Palette -->
        <div class="asset-card">
          <div class="asset-card-header">
            <h4 class="font-medium text-gray-900 dark:text-white">Color Palette</h4>
            <button
              @click="addColor"
              class="btn-sm btn-primary"
            >
              <Icon name="plus" class="w-4 h-4 mr-1" />
              Add Color
            </button>
          </div>
          
          <div class="color-palette">
            <div
              v-for="color in brandAssets.colors"
              :key="color.id"
              class="color-item"
            >
              <div
                class="color-swatch"
                :style="{ backgroundColor: color.value }"
                @click="editColor(color)"
              ></div>
              <div class="color-info">
                <p class="text-xs font-medium text-gray-900 dark:text-white">
                  {{ color.name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                  {{ color.value }}
                </p>
                <div class="color-usage">
                  <span class="text-xs text-gray-400">
                    Used in {{ color.usageCount || 0 }} components
                  </span>
                </div>
              </div>
              <div class="color-actions">
                <button
                  @click="copyColorValue(color)"
                  class="btn-icon"
                  title="Copy Color Value"
                >
                  <Icon name="clipboard-copy" class="w-3 h-3" />
                </button>
                <button
                  @click="checkColorContrast(color)"
                  class="btn-icon"
                  title="Check Contrast"
                >
                  <Icon name="eye" class="w-3 h-3" />
                </button>
                <button
                  @click="deleteColor(color)"
                  class="btn-icon text-red-600"
                  title="Delete"
                >
                  <Icon name="trash" class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Typography Management -->
    <div class="brand-section">
      <div class="section-header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Typography
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Define font families, sizes, and text styles
        </p>
      </div>

      <div class="typography-manager">
        <div class="font-families">
          <h4 class="font-medium text-gray-900 dark:text-white mb-3">Font Families</h4>
          <div class="font-grid">
            <div
              v-for="font in brandAssets.fonts"
              :key="font.id"
              class="font-item"
            >
              <div class="font-preview" :style="{ fontFamily: font.family }">
                <p class="text-lg">{{ font.name }}</p>
                <p class="text-sm text-gray-600">The quick brown fox jumps over the lazy dog</p>
              </div>
              <div class="font-info">
                <p class="text-xs font-medium">{{ font.name }}</p>
                <p class="text-xs text-gray-500">{{ font.family }}</p>
                <div class="font-weights">
                  <span
                    v-for="weight in font.weights"
                    :key="weight"
                    class="weight-badge"
                  >
                    {{ weight }}
                  </span>
                </div>
              </div>
              <div class="font-actions">
                <button
                  @click="setPrimaryFont(font)"
                  class="btn-icon"
                  :class="{ 'text-blue-600': font.isPrimary }"
                  title="Set as Primary"
                >
                  <Icon name="star" class="w-3 h-3" />
                </button>
                <button
                  @click="editFont(font)"
                  class="btn-icon"
                  title="Edit Font"
                >
                  <Icon name="pencil" class="w-3 h-3" />
                </button>
                <button
                  @click="deleteFont(font)"
                  class="btn-icon text-red-600"
                  title="Delete"
                >
                  <Icon name="trash" class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
          
          <button
            @click="addFont"
            class="btn-secondary mt-3"
          >
            <Icon name="plus" class="w-4 h-4 mr-2" />
            Add Font Family
          </button>
        </div>
      </div>
    </div>

    <!-- Brand Guidelines Enforcement -->
    <div class="brand-section">
      <div class="section-header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Brand Guidelines
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Set rules and constraints to maintain brand consistency
        </p>
      </div>

      <div class="guidelines-grid">
        <!-- Color Guidelines -->
        <div class="guideline-card">
          <h4 class="font-medium text-gray-900 dark:text-white mb-3">Color Guidelines</h4>
          <div class="space-y-3">
            <div class="guideline-rule">
              <label class="flex items-center">
                <input
                  v-model="brandGuidelines.enforceColorPalette"
                  type="checkbox"
                  class="form-checkbox"
                />
                <span class="ml-2 text-sm">Enforce approved color palette only</span>
              </label>
            </div>
            <div class="guideline-rule">
              <label class="flex items-center">
                <input
                  v-model="brandGuidelines.requireContrastCheck"
                  type="checkbox"
                  class="form-checkbox"
                />
                <span class="ml-2 text-sm">Require WCAG AA contrast compliance</span>
              </label>
            </div>
            <div class="guideline-rule">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Minimum contrast ratio
              </label>
              <select
                v-model="brandGuidelines.minContrastRatio"
                class="form-select mt-1"
              >
                <option value="3">3:1 (AA Large)</option>
                <option value="4.5">4.5:1 (AA Normal)</option>
                <option value="7">7:1 (AAA Normal)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Typography Guidelines -->
        <div class="guideline-card">
          <h4 class="font-medium text-gray-900 dark:text-white mb-3">Typography Guidelines</h4>
          <div class="space-y-3">
            <div class="guideline-rule">
              <label class="flex items-center">
                <input
                  v-model="brandGuidelines.enforceFontFamilies"
                  type="checkbox"
                  class="form-checkbox"
                />
                <span class="ml-2 text-sm">Restrict to approved font families</span>
              </label>
            </div>
            <div class="guideline-rule">
              <label class="flex items-center">
                <input
                  v-model="brandGuidelines.enforceTypographyScale"
                  type="checkbox"
                  class="form-checkbox"
                />
                <span class="ml-2 text-sm">Use consistent typography scale</span>
              </label>
            </div>
            <div class="guideline-rule">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Maximum font sizes
              </label>
              <div class="grid grid-cols-2 gap-2 mt-1">
                <input
                  v-model="brandGuidelines.maxHeadingSize"
                  type="number"
                  placeholder="Heading"
                  class="form-input"
                />
                <input
                  v-model="brandGuidelines.maxBodySize"
                  type="number"
                  placeholder="Body"
                  class="form-input"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Logo Guidelines -->
        <div class="guideline-card">
          <h4 class="font-medium text-gray-900 dark:text-white mb-3">Logo Guidelines</h4>
          <div class="space-y-3">
            <div class="guideline-rule">
              <label class="flex items-center">
                <input
                  v-model="brandGuidelines.enforceLogoPlacement"
                  type="checkbox"
                  class="form-checkbox"
                />
                <span class="ml-2 text-sm">Enforce logo placement rules</span>
              </label>
            </div>
            <div class="guideline-rule">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Minimum logo size (px)
              </label>
              <input
                v-model="brandGuidelines.minLogoSize"
                type="number"
                class="form-input mt-1"
                placeholder="32"
              />
            </div>
            <div class="guideline-rule">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Logo clear space (multiplier)
              </label>
              <input
                v-model="brandGuidelines.logoClearSpace"
                type="number"
                step="0.1"
                class="form-input mt-1"
                placeholder="1.5"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Brand Templates -->
    <div class="brand-section">
      <div class="section-header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Brand Templates
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Pre-configured brand themes for quick application
        </p>
      </div>

      <div class="templates-grid">
        <div
          v-for="template in brandTemplates"
          :key="template.id"
          class="template-card"
          :class="{ 'template-card--active': selectedTemplate?.id === template.id }"
          @click="selectTemplate(template)"
        >
          <div class="template-preview">
            <div class="preview-colors">
              <div
                v-for="color in template.colors.slice(0, 4)"
                :key="color.name"
                class="preview-color"
                :style="{ backgroundColor: color.value }"
              ></div>
            </div>
            <div class="preview-typography" :style="{ fontFamily: template.primaryFont }">
              <h5>{{ template.name }}</h5>
              <p>Sample text</p>
            </div>
          </div>
          
          <div class="template-info">
            <h4 class="font-medium text-gray-900 dark:text-white">
              {{ template.name }}
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ template.description }}
            </p>
            <div class="template-stats">
              <span class="text-xs text-gray-500">
                {{ template.usageCount || 0 }} components using this template
              </span>
            </div>
          </div>
          
          <div class="template-actions">
            <button
              @click.stop="applyTemplate(template)"
              class="btn-sm btn-primary"
            >
              Apply
            </button>
            <button
              @click.stop="editTemplate(template)"
              class="btn-sm btn-secondary"
            >
              Edit
            </button>
            <button
              @click.stop="duplicateTemplate(template)"
              class="btn-icon"
              title="Duplicate"
            >
              <Icon name="duplicate" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Brand Consistency Checker -->
    <div class="brand-section">
      <div class="section-header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Brand Consistency Report
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Automated checks for brand compliance across all components
        </p>
      </div>

      <div class="consistency-report">
        <div class="report-summary">
          <div class="summary-card">
            <div class="summary-icon bg-green-100 text-green-600">
              <Icon name="check-circle" class="w-6 h-6" />
            </div>
            <div class="summary-content">
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ consistencyReport.compliantComponents }}
              </h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">Compliant Components</p>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="summary-icon bg-yellow-100 text-yellow-600">
              <Icon name="exclamation-triangle" class="w-6 h-6" />
            </div>
            <div class="summary-content">
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ consistencyReport.warningComponents }}
              </h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">Minor Issues</p>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="summary-icon bg-red-100 text-red-600">
              <Icon name="x-circle" class="w-6 h-6" />
            </div>
            <div class="summary-content">
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ consistencyReport.nonCompliantComponents }}
              </h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">Non-Compliant</p>
            </div>
          </div>
        </div>

        <div class="report-details">
          <div
            v-for="issue in consistencyReport.issues"
            :key="issue.id"
            class="issue-item"
            :class="`issue-item--${issue.severity}`"
          >
            <div class="issue-icon">
              <Icon
                :name="getIssueIcon(issue.severity)"
                class="w-4 h-4"
              />
            </div>
            <div class="issue-content">
              <h5 class="font-medium text-gray-900 dark:text-white">
                {{ issue.title }}
              </h5>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ issue.description }}
              </p>
              <div class="issue-components">
                <span class="text-xs text-gray-500">
                  Affects: {{ issue.affectedComponents.join(', ') }}
                </span>
              </div>
            </div>
            <div class="issue-actions">
              <button
                v-if="issue.autoFixAvailable"
                @click="autoFixIssue(issue)"
                class="btn-sm btn-primary"
              >
                Auto Fix
              </button>
              <button
                @click="viewIssueDetails(issue)"
                class="btn-sm btn-secondary"
              >
                View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <BrandAnalyticsModal
      v-if="showAnalyticsModal"
      :analytics-data="brandAnalytics"
      @close="showAnalyticsModal = false"
    />

    <ColorEditorModal
      v-if="showColorEditor"
      :color="editingColor"
      :is-new="isNewColor"
      @save="handleColorSave"
      @cancel="closeColorEditor"
    />

    <FontEditorModal
      v-if="showFontEditor"
      :font="editingFont"
      :is-new="isNewFont"
      @save="handleFontSave"
      @cancel="closeFontEditor"
    />

    <TemplateEditorModal
      v-if="showTemplateEditor"
      :template="editingTemplate"
      :is-new="isNewTemplate"
      :brand-assets="brandAssets"
      @save="handleTemplateSave"
      @cancel="closeTemplateEditor"
    />

    <ContrastCheckerModal
      v-if="showContrastChecker"
      :color="contrastCheckColor"
      :background-colors="brandAssets.colors"
      @close="showContrastChecker = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Icon from '@/components/Common/Icon.vue'
import BrandAnalyticsModal from './BrandAnalyticsModal.vue'
import ColorEditorModal from './ColorEditorModal.vue'
import FontEditorModal from './FontEditorModal.vue'
import TemplateEditorModal from './TemplateEditorModal.vue'
import ContrastCheckerModal from './ContrastCheckerModal.vue'
import { useNotifications } from '@/composables/useNotifications'
import type {
  BrandAssets,
  BrandGuidelines,
  BrandTemplate,
  BrandConsistencyReport,
  BrandAnalytics,
  BrandColor,
  BrandFont,
  BrandLogo
} from '@/types/components'

interface Props {
  initialBrandAssets?: BrandAssets
  initialGuidelines?: BrandGuidelines
  initialTemplates?: BrandTemplate[]
}

const props = withDefaults(defineProps<Props>(), {
  initialBrandAssets: () => ({
    logos: [],
    colors: [],
    fonts: []
  }),
  initialGuidelines: () => ({
    enforceColorPalette: true,
    requireContrastCheck: true,
    minContrastRatio: 4.5,
    enforceFontFamilies: true,
    enforceTypographyScale: true,
    maxHeadingSize: 48,
    maxBodySize: 18,
    enforceLogoPlacement: true,
    minLogoSize: 32,
    logoClearSpace: 1.5
  }),
  initialTemplates: () => []
})

const emit = defineEmits<{
  brandUpdated: [assets: BrandAssets]
  guidelinesUpdated: [guidelines: BrandGuidelines]
  templateApplied: [template: BrandTemplate]
}>()

// State
const brandAssets = ref<BrandAssets>(props.initialBrandAssets)
const brandGuidelines = ref<BrandGuidelines>(props.initialGuidelines)
const brandTemplates = ref<BrandTemplate[]>(props.initialTemplates)
const selectedTemplate = ref<BrandTemplate | null>(null)
const consistencyReport = ref<BrandConsistencyReport>({
  compliantComponents: 0,
  warningComponents: 0,
  nonCompliantComponents: 0,
  issues: []
})
const brandAnalytics = ref<BrandAnalytics>({
  assetUsage: {},
  colorUsage: {},
  fontUsage: {},
  templateUsage: {},
  complianceScore: 0,
  trendsData: []
})

// Modal states
const showAnalyticsModal = ref(false)
const showColorEditor = ref(false)
const showFontEditor = ref(false)
const showTemplateEditor = ref(false)
const showContrastChecker = ref(false)

// Editor states
const editingColor = ref<BrandColor | null>(null)
const editingFont = ref<BrandFont | null>(null)
const editingTemplate = ref<BrandTemplate | null>(null)
const contrastCheckColor = ref<BrandColor | null>(null)
const isNewColor = ref(false)
const isNewFont = ref(false)
const isNewTemplate = ref(false)

const loading = ref(false)

const { showNotification } = useNotifications()

// Methods
const loadBrandData = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/brand-customizer/data')
    const data = await response.json()
    
    brandAssets.value = data.assets
    brandGuidelines.value = data.guidelines
    brandTemplates.value = data.templates
    consistencyReport.value = data.consistencyReport
    brandAnalytics.value = data.analytics
  } catch (error) {
    console.error('Failed to load brand data:', error)
    showNotification('Failed to load brand data', 'error')
  } finally {
    loading.value = false
  }
}

// Logo Management
const uploadLogo = async () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.multiple = true
  
  input.onchange = async (event) => {
    const files = (event.target as HTMLInputElement).files
    if (!files) return
    
    const formData = new FormData()
    Array.from(files).forEach(file => {
      formData.append('logos[]', file)
    })
    
    try {
      const response = await fetch('/api/brand-customizer/logos', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: formData
      })
      
      if (response.ok) {
        const newLogos = await response.json()
        brandAssets.value.logos.push(...newLogos)
        emit('brandUpdated', brandAssets.value)
        showNotification('Logos uploaded successfully', 'success')
        
        // Auto-optimize uploaded logos
        newLogos.forEach((logo: BrandLogo) => optimizeLogo(logo))
      }
    } catch (error) {
      console.error('Failed to upload logos:', error)
      showNotification('Failed to upload logos', 'error')
    }
  }
  
  input.click()
}

const setPrimaryLogo = async (logo: BrandLogo) => {
  try {
    const response = await fetch(`/api/brand-customizer/logos/${logo.id}/set-primary`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      brandAssets.value.logos.forEach(l => l.isPrimary = l.id === logo.id)
      emit('brandUpdated', brandAssets.value)
      showNotification('Primary logo updated', 'success')
    }
  } catch (error) {
    console.error('Failed to set primary logo:', error)
    showNotification('Failed to set primary logo', 'error')
  }
}

const optimizeLogo = async (logo: BrandLogo) => {
  try {
    const response = await fetch(`/api/brand-customizer/logos/${logo.id}/optimize`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const optimizedLogo = await response.json()
      const index = brandAssets.value.logos.findIndex(l => l.id === logo.id)
      if (index !== -1) {
        brandAssets.value.logos[index] = optimizedLogo
      }
      showNotification('Logo optimized successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to optimize logo:', error)
    showNotification('Failed to optimize logo', 'error')
  }
}

const deleteLogo = async (logo: BrandLogo) => {
  if (!confirm(`Are you sure you want to delete "${logo.name}"?`)) {
    return
  }
  
  try {
    const response = await fetch(`/api/brand-customizer/logos/${logo.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      brandAssets.value.logos = brandAssets.value.logos.filter(l => l.id !== logo.id)
      emit('brandUpdated', brandAssets.value)
      showNotification('Logo deleted successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to delete logo:', error)
    showNotification('Failed to delete logo', 'error')
  }
}

// Color Management
const addColor = () => {
  editingColor.value = null
  isNewColor.value = true
  showColorEditor.value = true
}

const editColor = (color: BrandColor) => {
  editingColor.value = color
  isNewColor.value = false
  showColorEditor.value = true
}

const handleColorSave = async (colorData: any) => {
  try {
    const url = isNewColor.value ? '/api/brand-customizer/colors' : `/api/brand-customizer/colors/${editingColor.value?.id}`
    const method = isNewColor.value ? 'POST' : 'PUT'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(colorData)
    })
    
    if (response.ok) {
      const savedColor = await response.json()
      
      if (isNewColor.value) {
        brandAssets.value.colors.push(savedColor)
      } else {
        const index = brandAssets.value.colors.findIndex(c => c.id === savedColor.id)
        if (index !== -1) {
          brandAssets.value.colors[index] = savedColor
        }
      }
      
      emit('brandUpdated', brandAssets.value)
      showNotification(`Color ${isNewColor.value ? 'added' : 'updated'} successfully`, 'success')
      closeColorEditor()
    }
  } catch (error) {
    console.error('Failed to save color:', error)
    showNotification('Failed to save color', 'error')
  }
}

const closeColorEditor = () => {
  showColorEditor.value = false
  editingColor.value = null
  isNewColor.value = false
}

const copyColorValue = async (color: BrandColor) => {
  try {
    await navigator.clipboard.writeText(color.value)
    showNotification('Color value copied to clipboard', 'success')
  } catch (error) {
    console.error('Failed to copy color value:', error)
    showNotification('Failed to copy color value', 'error')
  }
}

const checkColorContrast = (color: BrandColor) => {
  contrastCheckColor.value = color
  showContrastChecker.value = true
}

const deleteColor = async (color: BrandColor) => {
  if (!confirm(`Are you sure you want to delete "${color.name}"?`)) {
    return
  }
  
  try {
    const response = await fetch(`/api/brand-customizer/colors/${color.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      brandAssets.value.colors = brandAssets.value.colors.filter(c => c.id !== color.id)
      emit('brandUpdated', brandAssets.value)
      showNotification('Color deleted successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to delete color:', error)
    showNotification('Failed to delete color', 'error')
  }
}

// Font Management
const addFont = () => {
  editingFont.value = null
  isNewFont.value = true
  showFontEditor.value = true
}

const editFont = (font: BrandFont) => {
  editingFont.value = font
  isNewFont.value = false
  showFontEditor.value = true
}

const setPrimaryFont = async (font: BrandFont) => {
  try {
    const response = await fetch(`/api/brand-customizer/fonts/${font.id}/set-primary`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      brandAssets.value.fonts.forEach(f => f.isPrimary = f.id === font.id)
      emit('brandUpdated', brandAssets.value)
      showNotification('Primary font updated', 'success')
    }
  } catch (error) {
    console.error('Failed to set primary font:', error)
    showNotification('Failed to set primary font', 'error')
  }
}

const handleFontSave = async (fontData: any) => {
  try {
    const url = isNewFont.value ? '/api/brand-customizer/fonts' : `/api/brand-customizer/fonts/${editingFont.value?.id}`
    const method = isNewFont.value ? 'POST' : 'PUT'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(fontData)
    })
    
    if (response.ok) {
      const savedFont = await response.json()
      
      if (isNewFont.value) {
        brandAssets.value.fonts.push(savedFont)
      } else {
        const index = brandAssets.value.fonts.findIndex(f => f.id === savedFont.id)
        if (index !== -1) {
          brandAssets.value.fonts[index] = savedFont
        }
      }
      
      emit('brandUpdated', brandAssets.value)
      showNotification(`Font ${isNewFont.value ? 'added' : 'updated'} successfully`, 'success')
      closeFontEditor()
    }
  } catch (error) {
    console.error('Failed to save font:', error)
    showNotification('Failed to save font', 'error')
  }
}

const closeFontEditor = () => {
  showFontEditor.value = false
  editingFont.value = null
  isNewFont.value = false
}

const deleteFont = async (font: BrandFont) => {
  if (!confirm(`Are you sure you want to delete "${font.name}"?`)) {
    return
  }
  
  try {
    const response = await fetch(`/api/brand-customizer/fonts/${font.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      brandAssets.value.fonts = brandAssets.value.fonts.filter(f => f.id !== font.id)
      emit('brandUpdated', brandAssets.value)
      showNotification('Font deleted successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to delete font:', error)
    showNotification('Failed to delete font', 'error')
  }
}

// Template Management
const createBrandTemplate = () => {
  editingTemplate.value = null
  isNewTemplate.value = true
  showTemplateEditor.value = true
}

const selectTemplate = (template: BrandTemplate) => {
  selectedTemplate.value = template
}

const applyTemplate = async (template: BrandTemplate) => {
  try {
    const response = await fetch(`/api/brand-customizer/templates/${template.id}/apply`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const updatedAssets = await response.json()
      brandAssets.value = updatedAssets
      emit('templateApplied', template)
      showNotification('Brand template applied successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to apply template:', error)
    showNotification('Failed to apply template', 'error')
  }
}

const editTemplate = (template: BrandTemplate) => {
  editingTemplate.value = template
  isNewTemplate.value = false
  showTemplateEditor.value = true
}

const duplicateTemplate = async (template: BrandTemplate) => {
  try {
    const response = await fetch(`/api/brand-customizer/templates/${template.id}/duplicate`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const newTemplate = await response.json()
      brandTemplates.value.push(newTemplate)
      showNotification('Template duplicated successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to duplicate template:', error)
    showNotification('Failed to duplicate template', 'error')
  }
}

const handleTemplateSave = async (templateData: any) => {
  try {
    const url = isNewTemplate.value ? '/api/brand-customizer/templates' : `/api/brand-customizer/templates/${editingTemplate.value?.id}`
    const method = isNewTemplate.value ? 'POST' : 'PUT'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(templateData)
    })
    
    if (response.ok) {
      const savedTemplate = await response.json()
      
      if (isNewTemplate.value) {
        brandTemplates.value.push(savedTemplate)
      } else {
        const index = brandTemplates.value.findIndex(t => t.id === savedTemplate.id)
        if (index !== -1) {
          brandTemplates.value[index] = savedTemplate
        }
      }
      
      showNotification(`Template ${isNewTemplate.value ? 'created' : 'updated'} successfully`, 'success')
      closeTemplateEditor()
    }
  } catch (error) {
    console.error('Failed to save template:', error)
    showNotification('Failed to save template', 'error')
  }
}

const closeTemplateEditor = () => {
  showTemplateEditor.value = false
  editingTemplate.value = null
  isNewTemplate.value = false
}

// Consistency Checking
const runConsistencyCheck = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/brand-customizer/consistency-check', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        guidelines: brandGuidelines.value,
        assets: brandAssets.value
      })
    })
    
    if (response.ok) {
      consistencyReport.value = await response.json()
      showNotification('Brand consistency check completed', 'success')
    }
  } catch (error) {
    console.error('Failed to run consistency check:', error)
    showNotification('Failed to run consistency check', 'error')
  } finally {
    loading.value = false
  }
}

const autoFixIssue = async (issue: any) => {
  try {
    const response = await fetch(`/api/brand-customizer/auto-fix/${issue.id}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const result = await response.json()
      if (result.success) {
        // Remove the fixed issue from the report
        consistencyReport.value.issues = consistencyReport.value.issues.filter(i => i.id !== issue.id)
        showNotification('Issue fixed automatically', 'success')
        
        // Refresh brand data if assets were updated
        if (result.updatedAssets) {
          brandAssets.value = result.updatedAssets
          emit('brandUpdated', brandAssets.value)
        }
      }
    }
  } catch (error) {
    console.error('Failed to auto-fix issue:', error)
    showNotification('Failed to auto-fix issue', 'error')
  }
}

const viewIssueDetails = (issue: any) => {
  // Navigate to detailed issue view or show modal
  router.visit(`/brand-customizer/issues/${issue.id}`)
}

// Export and Analytics
const exportBrandAssets = async () => {
  try {
    const response = await fetch('/api/brand-customizer/export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        assets: brandAssets.value,
        guidelines: brandGuidelines.value,
        format: 'zip' // or 'json', 'css', etc.
      })
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = 'brand-assets.zip'
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
      
      showNotification('Brand assets exported successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to export brand assets:', error)
    showNotification('Failed to export brand assets', 'error')
  }
}

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const getIssueIcon = (severity: string): string => {
  switch (severity) {
    case 'error':
      return 'x-circle'
    case 'warning':
      return 'exclamation-triangle'
    case 'info':
      return 'information-circle'
    default:
      return 'information-circle'
  }
}

// Watch for guideline changes and auto-save
watch(brandGuidelines, async (newGuidelines) => {
  try {
    await fetch('/api/brand-customizer/guidelines', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(newGuidelines)
    })
    
    emit('guidelinesUpdated', newGuidelines)
    
    // Re-run consistency check when guidelines change
    runConsistencyCheck()
  } catch (error) {
    console.error('Failed to save guidelines:', error)
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  if (props.initialBrandAssets.logos.length === 0) {
    loadBrandData()
  } else {
    runConsistencyCheck()
  }
})
</script>

<style scoped>
.brand-customizer {
  @apply p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm;
}

.brand-customizer-header {
  @apply mb-8 pb-6 border-b border-gray-200 dark:border-gray-700;
}

.brand-section {
  @apply mb-8;
}

.section-header {
  @apply mb-6;
}

.asset-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.asset-card-header {
  @apply flex items-center justify-between mb-4;
}

.logo-grid {
  @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4;
}

.logo-item {
  @apply bg-white dark:bg-gray-800 rounded-lg p-4 border-2 border-gray-200 dark:border-gray-600 transition-all duration-200;
}

.logo-item--primary {
  @apply border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.logo-preview {
  @apply flex items-center justify-center h-16 mb-3 bg-gray-100 dark:bg-gray-600 rounded;
}

.logo-info {
  @apply mb-3;
}

.logo-actions {
  @apply flex gap-2;
}

.logo-upload-placeholder {
  @apply flex flex-col items-center justify-center h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-400 transition-colors duration-200;
}

.color-palette {
  @apply grid grid-cols-1 sm:grid-cols-2 gap-4;
}

.color-item {
  @apply flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600;
}

.color-swatch {
  @apply w-12 h-12 rounded-lg border-2 border-white shadow-sm cursor-pointer flex-shrink-0;
}

.color-info {
  @apply flex-1 min-w-0;
}

.color-usage {
  @apply mt-1;
}

.color-actions {
  @apply flex gap-1;
}

.typography-manager {
  @apply space-y-6;
}

.font-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-4;
}

.font-item {
  @apply bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600;
}

.font-preview {
  @apply mb-3;
}

.font-info {
  @apply mb-3;
}

.font-weights {
  @apply flex gap-1 mt-2;
}

.weight-badge {
  @apply px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded;
}

.font-actions {
  @apply flex gap-2;
}

.guidelines-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.guideline-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.guideline-rule {
  @apply space-y-2;
}

.templates-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.template-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg border-2 border-transparent cursor-pointer transition-all duration-200 hover:border-blue-200 dark:hover:border-blue-600;
}

.template-card--active {
  @apply border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.template-preview {
  @apply p-4;
}

.preview-colors {
  @apply flex gap-1 mb-3;
}

.preview-color {
  @apply w-4 h-4 rounded;
}

.preview-typography {
  @apply space-y-1;
}

.template-info {
  @apply p-4 pt-0;
}

.template-stats {
  @apply mt-2;
}

.template-actions {
  @apply flex gap-2 p-4 pt-0;
}

.consistency-report {
  @apply space-y-6;
}

.report-summary {
  @apply grid grid-cols-1 md:grid-cols-3 gap-6;
}

.summary-card {
  @apply flex items-center gap-4 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600;
}

.summary-icon {
  @apply w-12 h-12 rounded-lg flex items-center justify-center;
}

.summary-content {
  @apply flex-1;
}

.report-details {
  @apply space-y-4;
}

.issue-item {
  @apply flex items-start gap-4 p-4 rounded-lg border;
}

.issue-item--error {
  @apply bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800;
}

.issue-item--warning {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800;
}

.issue-item--info {
  @apply bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800;
}

.issue-icon {
  @apply flex-shrink-0 mt-1;
}

.issue-content {
  @apply flex-1 min-w-0;
}

.issue-components {
  @apply mt-2;
}

.issue-actions {
  @apply flex gap-2;
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

.btn-sm.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white;
}

.btn-sm.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300;
}

.btn-icon {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.form-checkbox {
  @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.form-select {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.form-input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}
</style>