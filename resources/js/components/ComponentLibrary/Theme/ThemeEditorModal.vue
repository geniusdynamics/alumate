<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
          {{ isNew ? 'Create New Theme' : 'Edit Theme' }}
        </h3>
        <button @click="$emit('cancel')" class="btn-close">
          <Icon name="x" class="w-5 h-5" />
        </button>
      </div>

      <div class="modal-body">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Configuration Panel -->
          <div class="config-panel">
            <form @submit.prevent="handleSave">
              <!-- Basic Info -->
              <div class="form-section">
                <h4 class="section-title">Basic Information</h4>
                <div class="form-group">
                  <label for="theme-name" class="form-label">Theme Name</label>
                  <input
                    id="theme-name"
                    v-model="formData.name"
                    type="text"
                    class="form-input"
                    placeholder="Enter theme name"
                    required
                  />
                </div>
                <div class="form-group">
                  <label class="form-checkbox">
                    <input
                      v-model="formData.isDefault"
                      type="checkbox"
                      class="checkbox"
                    />
                    <span class="checkbox-label">Set as default theme</span>
                  </label>
                </div>
              </div>

              <!-- Colors Section -->
              <div class="form-section">
                <h4 class="section-title">Colors</h4>
                <div class="color-grid">
                  <div
                    v-for="(color, name) in formData.colors"
                    :key="name"
                    class="color-input-group"
                  >
                    <label :for="`color-${name}`" class="form-label">
                      {{ formatColorName(name) }}
                    </label>
                    <div class="color-input-wrapper">
                      <input
                        :id="`color-${name}`"
                        v-model="formData.colors[name]"
                        type="color"
                        class="color-input"
                        @input="updatePreview"
                      />
                      <input
                        v-model="formData.colors[name]"
                        type="text"
                        class="color-text-input"
                        placeholder="#000000"
                        @input="updatePreview"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Typography Section -->
              <div class="form-section">
                <h4 class="section-title">Typography</h4>
                <div class="form-group">
                  <label for="font-family" class="form-label">Font Family</label>
                  <select
                    id="font-family"
                    v-model="formData.typography.fontFamily"
                    class="form-select"
                    @change="updatePreview"
                  >
                    <option value="Arial, sans-serif">Arial</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="'Times New Roman', serif">Times New Roman</option>
                    <option value="Helvetica, sans-serif">Helvetica</option>
                    <option value="'Courier New', monospace">Courier New</option>
                    <option value="Verdana, sans-serif">Verdana</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="heading-font" class="form-label">Heading Font</label>
                  <select
                    id="heading-font"
                    v-model="formData.typography.headingFont"
                    class="form-select"
                    @change="updatePreview"
                  >
                    <option value="">Same as body font</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="'Times New Roman', serif">Times New Roman</option>
                    <option value="Arial, sans-serif">Arial</option>
                    <option value="Helvetica, sans-serif">Helvetica</option>
                  </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div class="form-group">
                    <label for="base-font-size" class="form-label">Base Font Size</label>
                    <div class="input-with-unit">
                      <input
                        id="base-font-size"
                        v-model="formData.typography.fontSizes.base"
                        type="text"
                        class="form-input"
                        placeholder="16px"
                        @input="updatePreview"
                      />
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="heading-font-size" class="form-label">Heading Font Size</label>
                    <div class="input-with-unit">
                      <input
                        id="heading-font-size"
                        v-model="formData.typography.fontSizes.heading"
                        type="text"
                        class="form-input"
                        placeholder="2rem"
                        @input="updatePreview"
                      />
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="line-height" class="form-label">
                    Line Height: {{ formData.typography.lineHeight }}
                  </label>
                  <input
                    id="line-height"
                    v-model.number="formData.typography.lineHeight"
                    type="range"
                    min="1"
                    max="3"
                    step="0.1"
                    class="range-input"
                    @input="updatePreview"
                  />
                </div>
              </div>

              <!-- Spacing Section -->
              <div class="form-section">
                <h4 class="section-title">Spacing</h4>
                <div class="grid grid-cols-2 gap-4">
                  <div
                    v-for="(value, name) in formData.spacing"
                    :key="name"
                    class="form-group"
                  >
                    <label :for="`spacing-${name}`" class="form-label">
                      {{ formatSpacingName(name) }}
                    </label>
                    <input
                      :id="`spacing-${name}`"
                      v-model="formData.spacing[name]"
                      type="text"
                      class="form-input"
                      :placeholder="getSpacingPlaceholder(name)"
                      @input="updatePreview"
                    />
                  </div>
                </div>
              </div>

              <!-- Borders & Effects Section -->
              <div class="form-section">
                <h4 class="section-title">Borders & Effects</h4>
                <div class="grid grid-cols-2 gap-4">
                  <div class="form-group">
                    <label for="border-radius" class="form-label">Border Radius</label>
                    <input
                      id="border-radius"
                      v-model="formData.borders.radius"
                      type="text"
                      class="form-input"
                      placeholder="4px"
                      @input="updatePreview"
                    />
                  </div>
                  <div class="form-group">
                    <label for="border-width" class="form-label">Border Width</label>
                    <input
                      id="border-width"
                      v-model="formData.borders.width"
                      type="text"
                      class="form-input"
                      placeholder="1px"
                      @input="updatePreview"
                    />
                  </div>
                </div>
              </div>

              <!-- Animation Section -->
              <div class="form-section">
                <h4 class="section-title">Animations</h4>
                <div class="grid grid-cols-2 gap-4">
                  <div class="form-group">
                    <label for="animation-duration" class="form-label">Duration</label>
                    <input
                      id="animation-duration"
                      v-model="formData.animations.duration"
                      type="text"
                      class="form-input"
                      placeholder="0.3s"
                      @input="updatePreview"
                    />
                  </div>
                  <div class="form-group">
                    <label for="animation-easing" class="form-label">Easing</label>
                    <select
                      id="animation-easing"
                      v-model="formData.animations.easing"
                      class="form-select"
                      @change="updatePreview"
                    >
                      <option value="ease">Ease</option>
                      <option value="ease-in">Ease In</option>
                      <option value="ease-out">Ease Out</option>
                      <option value="ease-in-out">Ease In Out</option>
                      <option value="linear">Linear</option>
                    </select>
                  </div>
                </div>
              </div>
            </form>
          </div>

          <!-- Preview Panel -->
          <div class="preview-panel">
            <div class="preview-header">
              <h4 class="text-lg font-medium text-gray-900 dark:text-white">Live Preview</h4>
              <div class="preview-controls">
                <button
                  v-for="device in previewDevices"
                  :key="device.name"
                  @click="currentDevice = device.name"
                  class="device-btn"
                  :class="{ 'device-btn--active': currentDevice === device.name }"
                >
                  <Icon :name="device.icon" class="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <div class="preview-container" :class="`preview-${currentDevice}`">
              <div class="preview-frame" :style="previewStyle">
                <div class="preview-content">
                  <!-- Hero Section Preview -->
                  <div class="preview-hero" :style="{ backgroundColor: formData.colors.primary }">
                    <h1 class="preview-hero-title" :style="heroTitleStyle">
                      Welcome to Our Platform
                    </h1>
                    <p class="preview-hero-subtitle" :style="heroSubtitleStyle">
                      Experience the power of our component library
                    </p>
                    <button class="preview-hero-button" :style="buttonStyle">
                      Get Started
                    </button>
                  </div>

                  <!-- Content Section Preview -->
                  <div class="preview-section" :style="sectionStyle">
                    <h2 class="preview-section-title" :style="sectionTitleStyle">
                      Features
                    </h2>
                    <div class="preview-cards">
                      <div
                        v-for="i in 3"
                        :key="i"
                        class="preview-card"
                        :style="cardStyle"
                      >
                        <h3 class="preview-card-title" :style="cardTitleStyle">
                          Feature {{ i }}
                        </h3>
                        <p class="preview-card-text" :style="cardTextStyle">
                          This is a sample description for feature {{ i }}.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Accessibility Check -->
            <div class="accessibility-check">
              <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                Accessibility Check
              </h5>
              <div v-if="accessibilityIssues.length === 0" class="accessibility-pass">
                <Icon name="check-circle" class="w-4 h-4 text-green-600" />
                <span class="text-sm text-green-600">All accessibility checks passed</span>
              </div>
              <div v-else class="accessibility-issues">
                <div
                  v-for="issue in accessibilityIssues"
                  :key="issue"
                  class="accessibility-issue"
                >
                  <Icon name="alert-triangle" class="w-4 h-4 text-yellow-600" />
                  <span class="text-sm text-yellow-600">{{ issue }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="$emit('cancel')" class="btn-secondary">
          Cancel
        </button>
        <button @click="handleSave" class="btn-primary" :disabled="saving">
          <Icon v-if="saving" name="loader" class="w-4 h-4 mr-2 animate-spin" />
          {{ isNew ? 'Create Theme' : 'Save Changes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import type { GrapeJSThemeData } from '@/types/components'

interface Props {
  theme?: GrapeJSThemeData | null
  isNew: boolean
}

const props = withDefaults(defineProps<Props>(), {
  theme: null
})

const emit = defineEmits<{
  save: [themeData: any]
  cancel: []
}>()

// State
const saving = ref(false)
const currentDevice = ref('desktop')

const previewDevices = [
  { name: 'desktop', icon: 'monitor' },
  { name: 'tablet', icon: 'tablet' },
  { name: 'mobile', icon: 'smartphone' }
]

const formData = ref({
  name: '',
  isDefault: false,
  colors: {
    primary: '#007bff',
    secondary: '#6c757d',
    accent: '#28a745',
    background: '#ffffff',
    text: '#333333'
  },
  typography: {
    fontFamily: 'Arial, sans-serif',
    headingFont: '',
    fontSizes: {
      base: '16px',
      heading: '2rem'
    },
    lineHeight: 1.6
  },
  spacing: {
    base: '1rem',
    small: '0.5rem',
    large: '2rem',
    sectionPadding: '1.5rem'
  },
  borders: {
    radius: '4px',
    width: '1px'
  },
  animations: {
    duration: '0.3s',
    easing: 'ease'
  }
})

// Computed styles for preview
const previewStyle = computed(() => ({
  backgroundColor: formData.value.colors.background,
  color: formData.value.colors.text,
  fontFamily: formData.value.typography.fontFamily,
  fontSize: formData.value.typography.fontSizes.base,
  lineHeight: formData.value.typography.lineHeight
}))

const heroTitleStyle = computed(() => ({
  fontFamily: formData.value.typography.headingFont || formData.value.typography.fontFamily,
  fontSize: formData.value.typography.fontSizes.heading,
  color: '#ffffff'
}))

const heroSubtitleStyle = computed(() => ({
  color: '#ffffff',
  opacity: '0.9'
}))

const buttonStyle = computed(() => ({
  backgroundColor: formData.value.colors.accent,
  borderRadius: formData.value.borders.radius,
  transition: `all ${formData.value.animations.duration} ${formData.value.animations.easing}`
}))

const sectionStyle = computed(() => ({
  padding: formData.value.spacing.sectionPadding
}))

const sectionTitleStyle = computed(() => ({
  fontFamily: formData.value.typography.headingFont || formData.value.typography.fontFamily,
  color: formData.value.colors.primary
}))

const cardStyle = computed(() => ({
  backgroundColor: formData.value.colors.background,
  borderRadius: formData.value.borders.radius,
  border: `${formData.value.borders.width} solid ${formData.value.colors.secondary}`,
  padding: formData.value.spacing.base
}))

const cardTitleStyle = computed(() => ({
  fontFamily: formData.value.typography.headingFont || formData.value.typography.fontFamily,
  color: formData.value.colors.text
}))

const cardTextStyle = computed(() => ({
  color: formData.value.colors.text,
  opacity: '0.8'
}))

// Accessibility checking
const accessibilityIssues = computed(() => {
  const issues: string[] = []
  
  // Check color contrast
  const primaryContrast = getColorContrast(formData.value.colors.primary, formData.value.colors.background)
  if (primaryContrast < 4.5) {
    issues.push(`Primary color contrast ratio (${primaryContrast.toFixed(2)}) is below WCAG AA standard (4.5:1)`)
  }
  
  const textContrast = getColorContrast(formData.value.colors.text, formData.value.colors.background)
  if (textContrast < 4.5) {
    issues.push(`Text color contrast ratio (${textContrast.toFixed(2)}) is below WCAG AA standard (4.5:1)`)
  }
  
  return issues
})

// Methods
const formatColorName = (name: string) => {
  return name.charAt(0).toUpperCase() + name.slice(1).replace(/([A-Z])/g, ' $1')
}

const formatSpacingName = (name: string) => {
  return name.charAt(0).toUpperCase() + name.slice(1).replace(/([A-Z])/g, ' $1')
}

const getSpacingPlaceholder = (name: string) => {
  const placeholders: Record<string, string> = {
    base: '1rem',
    small: '0.5rem',
    large: '2rem',
    sectionPadding: '1.5rem'
  }
  return placeholders[name] || '1rem'
}

const updatePreview = () => {
  // Trigger reactivity for preview updates
}

const handleOverlayClick = () => {
  emit('cancel')
}

const handleSave = async () => {
  saving.value = true
  
  try {
    const themeData = {
      name: formData.value.name,
      is_default: formData.value.isDefault,
      config: {
        colors: formData.value.colors,
        typography: {
          font_family: formData.value.typography.fontFamily,
          heading_font: formData.value.typography.headingFont,
          font_sizes: formData.value.typography.fontSizes,
          line_height: formData.value.typography.lineHeight
        },
        spacing: formData.value.spacing,
        borders: formData.value.borders,
        animations: formData.value.animations
      }
    }
    
    emit('save', themeData)
  } finally {
    saving.value = false
  }
}

// Color contrast calculation
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

const getColorContrast = (color1: string, color2: string) => {
  const rgb1 = hexToRgb(color1)
  const rgb2 = hexToRgb(color2)
  
  if (!rgb1 || !rgb2) return 0
  
  const l1 = getRelativeLuminance(rgb1)
  const l2 = getRelativeLuminance(rgb2)
  
  const lighter = Math.max(l1, l2)
  const darker = Math.min(l1, l2)
  
  return (lighter + 0.05) / (darker + 0.05)
}

// Initialize form data
const initializeFormData = () => {
  if (props.theme) {
    const config = props.theme.styleManager || {}
    
    formData.value = {
      name: props.theme.name,
      isDefault: props.theme.isDefault,
      colors: {
        primary: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
        secondary: props.theme.cssVariables['--theme-color-secondary'] || '#6c757d',
        accent: props.theme.cssVariables['--theme-color-accent'] || '#28a745',
        background: props.theme.cssVariables['--theme-color-background'] || '#ffffff',
        text: props.theme.cssVariables['--theme-color-text'] || '#333333'
      },
      typography: {
        fontFamily: props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
        headingFont: props.theme.cssVariables['--theme-heading-font'] || '',
        fontSizes: {
          base: props.theme.cssVariables['--theme-font-size-base'] || '16px',
          heading: props.theme.cssVariables['--theme-font-size-heading'] || '2rem'
        },
        lineHeight: parseFloat(props.theme.cssVariables['--theme-line-height'] || '1.6')
      },
      spacing: {
        base: props.theme.cssVariables['--theme-spacing-base'] || '1rem',
        small: props.theme.cssVariables['--theme-spacing-small'] || '0.5rem',
        large: props.theme.cssVariables['--theme-spacing-large'] || '2rem',
        sectionPadding: props.theme.cssVariables['--theme-spacing-section-padding'] || '1.5rem'
      },
      borders: {
        radius: props.theme.cssVariables['--theme-border-radius'] || '4px',
        width: props.theme.cssVariables['--theme-border-width'] || '1px'
      },
      animations: {
        duration: props.theme.cssVariables['--theme-animation-duration'] || '0.3s',
        easing: props.theme.cssVariables['--theme-animation-easing'] || 'ease'
      }
    }
  }
}

onMounted(() => {
  initializeFormData()
})

watch(() => props.theme, () => {
  initializeFormData()
})
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.modal-body {
  @apply flex-1 overflow-y-auto p-6;
}

.modal-footer {
  @apply flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700;
}

.config-panel {
  @apply space-y-6;
}

.preview-panel {
  @apply space-y-4;
}

.form-section {
  @apply space-y-4;
}

.section-title {
  @apply text-lg font-medium text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2;
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

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white;
}

.form-checkbox {
  @apply flex items-center space-x-2;
}

.checkbox {
  @apply rounded border-gray-300 text-blue-600 focus:ring-blue-500;
}

.checkbox-label {
  @apply text-sm text-gray-700 dark:text-gray-300;
}

.color-grid {
  @apply grid grid-cols-1 sm:grid-cols-2 gap-4;
}

.color-input-group {
  @apply space-y-2;
}

.color-input-wrapper {
  @apply flex gap-2;
}

.color-input {
  @apply w-12 h-10 border border-gray-300 dark:border-gray-600 rounded cursor-pointer;
}

.color-text-input {
  @apply flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white;
}

.range-input {
  @apply w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700;
}

.preview-header {
  @apply flex items-center justify-between mb-4;
}

.preview-controls {
  @apply flex gap-1;
}

.device-btn {
  @apply p-2 rounded-md text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700;
}

.device-btn--active {
  @apply text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.preview-container {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden;
}

.preview-desktop {
  @apply w-full;
}

.preview-tablet {
  @apply w-3/4 mx-auto;
}

.preview-mobile {
  @apply w-1/2 mx-auto;
}

.preview-frame {
  @apply w-full min-h-96 overflow-y-auto;
}

.preview-content {
  @apply space-y-0;
}

.preview-hero {
  @apply p-8 text-center text-white;
}

.preview-hero-title {
  @apply text-3xl font-bold mb-4;
}

.preview-hero-subtitle {
  @apply text-lg mb-6;
}

.preview-hero-button {
  @apply px-6 py-3 text-white font-medium rounded-md;
}

.preview-section {
  @apply bg-gray-50 dark:bg-gray-900;
}

.preview-section-title {
  @apply text-2xl font-bold mb-6 text-center;
}

.preview-cards {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.preview-card {
  @apply p-4 rounded-lg;
}

.preview-card-title {
  @apply text-lg font-semibold mb-2;
}

.preview-card-text {
  @apply text-sm;
}

.accessibility-check {
  @apply mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.accessibility-pass {
  @apply flex items-center gap-2;
}

.accessibility-issues {
  @apply space-y-2;
}

.accessibility-issue {
  @apply flex items-center gap-2;
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
</style>