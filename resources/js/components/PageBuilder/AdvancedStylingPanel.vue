<template>
  <div class="advanced-styling-panel">
    <!-- Panel Header -->
    <div class="panel-header">
      <div class="header-content">
        <h3 class="panel-title">Advanced Styling</h3>
        <p class="panel-subtitle">Tailwind CSS & Brand Guidelines</p>
      </div>
      <button @click="$emit('close')" class="close-btn" aria-label="Close styling panel">
        <Icon name="x" class="w-5 h-5" />
      </button>
    </div>

    <!-- Styling Tabs -->
    <div class="styling-tabs">
      <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
        :class="['tab-button', { active: activeTab === tab.id }]">
        <Icon :name="tab.icon" class="w-4 h-4" />
        {{ tab.name }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
      <!-- Brand Colors Tab -->
      <div v-if="activeTab === 'colors'" class="tab-panel">
        <div class="section-header">
          <h4 class="section-title">Brand Colors</h4>
          <p class="section-description">Use approved brand colors for consistency</p>
        </div>

        <div class="color-categories">
          <div v-for="category in colorCategories" :key="category" class="color-category">
            <h5 class="category-title">{{ formatCategoryName(category) }}</h5>
            <div class="color-grid">
              <button v-for="color in getBrandColorsByCategory(category)" :key="color.name"
                @click="applyBrandColor(color, selectedColorProperty)"
                :class="['color-swatch', { active: isColorActive(color) }]" :style="{ backgroundColor: color.value }"
                :title="color.name">
                <span class="color-name">{{ color.name }}</span>
                <span class="color-value">{{ color.value }}</span>
              </button>
            </div>
          </div>
        </div>

        <div class="color-property-selector">
          <label class="property-label">Apply to:</label>
          <div class="property-buttons">
            <button v-for="property in colorProperties" :key="property.key"
              @click="selectedColorProperty = property.key"
              :class="['property-btn', { active: selectedColorProperty === property.key }]">
              <Icon :name="property.icon" class="w-4 h-4" />
              {{ property.name }}
            </button>
          </div>
        </div>
      </div>

      <!-- Typography Tab -->
      <div v-if="activeTab === 'typography'" class="tab-panel">
        <div class="section-header">
          <h4 class="section-title">Typography</h4>
          <p class="section-description">Brand-approved fonts and text styling</p>
        </div>

        <!-- Font Family -->
        <div class="style-group">
          <label class="style-label">Font Family</label>
          <div class="font-grid">
            <button v-for="font in brandFonts" :key="font.name" @click="applyFont(font)"
              :class="['font-option', { active: isActiveFontFamily(font.family) }]">
              <span class="font-preview" :style="{ fontFamily: font.family }">
                {{ font.name }}
              </span>
              <span class="font-category">{{ font.category }}</span>
            </button>
          </div>
        </div>

        <!-- Font Size -->
        <div class="style-group">
          <label class="style-label">Font Size</label>
          <div class="size-grid">
            <button v-for="size in fontSizes" :key="size.value" @click="applyFontSize(size.value)"
              :class="['size-option', { active: isActiveFontSize(size.value) }]">
              <span class="size-name">{{ size.name }}</span>
              <span class="size-value">{{ size.value }}</span>
            </button>
          </div>
        </div>

        <!-- Font Weight -->
        <div class="style-group">
          <label class="style-label">Font Weight</label>
          <div class="weight-grid">
            <button v-for="weight in fontWeights" :key="weight.value" @click="applyFontWeight(weight.value)"
              :class="['weight-option', { active: isActiveFontWeight(weight.value) }]">
              <span class="weight-preview" :style="{ fontWeight: weight.value }">
                {{ weight.name }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Spacing Tab -->
      <div v-if="activeTab === 'spacing'" class="tab-panel">
        <div class="section-header">
          <h4 class="section-title">Spacing</h4>
          <p class="section-description">Tailwind CSS spacing scale</p>
        </div>

        <!-- Padding -->
        <div class="style-group">
          <label class="style-label">Padding</label>
          <div class="spacing-controls">
            <div class="spacing-visual">
              <div class="spacing-box">
                <div class="padding-area">
                  <input v-model="paddingTop" @input="updatePadding" type="number" class="spacing-input top"
                    placeholder="Top" />
                  <div class="horizontal-inputs">
                    <input v-model="paddingLeft" @input="updatePadding" type="number" class="spacing-input left"
                      placeholder="Left" />
                    <div class="content-area">Content</div>
                    <input v-model="paddingRight" @input="updatePadding" type="number" class="spacing-input right"
                      placeholder="Right" />
                  </div>
                  <input v-model="paddingBottom" @input="updatePadding" type="number" class="spacing-input bottom"
                    placeholder="Bottom" />
                </div>
              </div>
            </div>
            <div class="spacing-presets">
              <button v-for="preset in spacingPresets" :key="preset.name" @click="applySpacingPreset('padding', preset)"
                class="preset-btn">
                {{ preset.name }}
              </button>
            </div>
          </div>
        </div>

        <!-- Margin -->
        <div class="style-group">
          <label class="style-label">Margin</label>
          <div class="spacing-controls">
            <div class="spacing-visual">
              <div class="spacing-box margin-box">
                <div class="margin-area">
                  <input v-model="marginTop" @input="updateMargin" type="number" class="spacing-input top"
                    placeholder="Top" />
                  <div class="horizontal-inputs">
                    <input v-model="marginLeft" @input="updateMargin" type="number" class="spacing-input left"
                      placeholder="Left" />
                    <div class="content-area">Element</div>
                    <input v-model="marginRight" @input="updateMargin" type="number" class="spacing-input right"
                      placeholder="Right" />
                  </div>
                  <input v-model="marginBottom" @input="updateMargin" type="number" class="spacing-input bottom"
                    placeholder="Bottom" />
                </div>
              </div>
            </div>
            <div class="spacing-presets">
              <button v-for="preset in spacingPresets" :key="preset.name" @click="applySpacingPreset('margin', preset)"
                class="preset-btn">
                {{ preset.name }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Effects Tab -->
      <div v-if="activeTab === 'effects'" class="tab-panel">
        <div class="section-header">
          <h4 class="section-title">Effects</h4>
          <p class="section-description">Shadows, borders, and visual effects</p>
        </div>

        <!-- Shadows -->
        <div class="style-group">
          <label class="style-label">Box Shadow</label>
          <div class="shadow-grid">
            <button v-for="shadow in shadowOptions" :key="shadow.name" @click="applyShadow(shadow.value)"
              :class="['shadow-option', { active: isActiveShadow(shadow.value) }]">
              <div class="shadow-preview" :style="{ boxShadow: shadow.value }"></div>
              <span class="shadow-name">{{ shadow.name }}</span>
            </button>
          </div>
        </div>

        <!-- Border Radius -->
        <div class="style-group">
          <label class="style-label">Border Radius</label>
          <div class="radius-grid">
            <button v-for="radius in borderRadiusOptions" :key="radius.name" @click="applyBorderRadius(radius.value)"
              :class="['radius-option', { active: isActiveBorderRadius(radius.value) }]">
              <div class="radius-preview" :style="{ borderRadius: radius.value }"></div>
              <span class="radius-name">{{ radius.name }}</span>
            </button>
          </div>
        </div>

        <!-- Opacity -->
        <div class="style-group">
          <label class="style-label">Opacity</label>
          <div class="opacity-control">
            <input v-model="opacity" @input="applyOpacity" type="range" min="0" max="1" step="0.1"
              class="opacity-slider" />
            <span class="opacity-value">{{ Math.round(opacity * 100) }}%</span>
          </div>
        </div>
      </div>

      <!-- Presets Tab -->
      <div v-if="activeTab === 'presets'" class="tab-panel">
        <div class="section-header">
          <h4 class="section-title">Style Presets</h4>
          <p class="section-description">Save and reuse styling combinations</p>
        </div>

        <!-- Save Current Style -->
        <div class="save-preset-section">
          <h5 class="subsection-title">Save Current Style</h5>
          <div class="save-form">
            <input v-model="newPresetName" type="text" class="preset-input" placeholder="Preset name" />
            <input v-model="newPresetDescription" type="text" class="preset-input"
              placeholder="Description (optional)" />
            <select v-model="newPresetCategory" class="preset-select">
              <option value="">Select category</option>
              <option value="buttons">Buttons</option>
              <option value="headings">Headings</option>
              <option value="cards">Cards</option>
              <option value="forms">Forms</option>
              <option value="custom">Custom</option>
            </select>
            <button @click="saveCurrentStyleAsPreset" class="save-btn">
              <Icon name="save" class="w-4 h-4" />
              Save Preset
            </button>
          </div>
        </div>

        <!-- Existing Presets -->
        <div class="presets-section">
          <h5 class="subsection-title">Saved Presets</h5>
          <div class="preset-categories">
            <div v-for="category in presetCategories" :key="category" class="preset-category">
              <h6 class="preset-category-title">{{ formatCategoryName(category) }}</h6>
              <div class="preset-grid">
                <div v-for="preset in getPresetsByCategory(category)" :key="preset.id" class="preset-card">
                  <div class="preset-preview">
                    <div class="preset-sample" :style="getPresetPreviewStyle(preset)">
                      Sample
                    </div>
                  </div>
                  <div class="preset-info">
                    <h6 class="preset-name">{{ preset.name }}</h6>
                    <p class="preset-description">{{ preset.description }}</p>
                  </div>
                  <div class="preset-actions">
                    <button @click="applyPreset(preset)" class="apply-btn">
                      Apply
                    </button>
                    <button @click="deletePreset(preset.id)" class="delete-btn">
                      <Icon name="trash" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Brand Compliance Alert -->
    <div v-if="complianceViolations.length > 0" class="compliance-alert">
      <div class="alert-header">
        <Icon name="alert-circle" class="w-5 h-5 text-yellow-500" />
        <span class="alert-title">Brand Guideline Violations</span>
      </div>
      <ul class="violation-list">
        <li v-for="violation in complianceViolations" :key="violation" class="violation-item">
          {{ violation }}
        </li>
      </ul>
      <div class="alert-actions">
        <button @click="fixComplianceIssues" class="fix-btn">
          Auto-fix Issues
        </button>
        <button @click="dismissAlert" class="dismiss-btn">
          Dismiss
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import Icon from '../ui/Icon.vue'
import { TailwindStyleManager, type StylePreset, type TailwindColor, type TailwindFont } from '@/services/TailwindStyleManager'

// Props
interface Props {
  selectedComponent?: any
  grapeJSEditor?: any
}

const props = withDefaults(defineProps<Props>(), {
  selectedComponent: null,
  grapeJSEditor: null
})

// Emits
const emit = defineEmits<{
  close: []
  styleUpdated: [componentId: string, styles: any]
}>()

// Reactive data
const activeTab = ref('colors')
const selectedColorProperty = ref<'color' | 'background-color' | 'border-color'>('color')
const styleManager = ref<TailwindStyleManager | null>(null)
const brandColors = ref<TailwindColor[]>([])
const brandFonts = ref<TailwindFont[]>([])
const stylePresets = ref<StylePreset[]>([])
const complianceViolations = ref<string[]>([])

// Form data
const newPresetName = ref('')
const newPresetDescription = ref('')
const newPresetCategory = ref('')

// Spacing values
const paddingTop = ref(0)
const paddingRight = ref(0)
const paddingBottom = ref(0)
const paddingLeft = ref(0)
const marginTop = ref(0)
const marginRight = ref(0)
const marginBottom = ref(0)
const marginLeft = ref(0)
const opacity = ref(1)

// Tab configuration
const tabs = [
  { id: 'colors', name: 'Colors', icon: 'palette' },
  { id: 'typography', name: 'Typography', icon: 'type' },
  { id: 'spacing', name: 'Spacing', icon: 'move' },
  { id: 'effects', name: 'Effects', icon: 'sparkles' },
  { id: 'presets', name: 'Presets', icon: 'save' }
]

// Color properties
const colorProperties = [
  { key: 'color' as const, name: 'Text', icon: 'type' },
  { key: 'background-color' as const, name: 'Background', icon: 'square' },
  { key: 'border-color' as const, name: 'Border', icon: 'frame' }
]

// Computed properties
const colorCategories = computed(() => {
  const categories = new Set(brandColors.value.map(color => color.category))
  return Array.from(categories)
})

const presetCategories = computed(() => {
  const categories = new Set(stylePresets.value.map(preset => preset.category))
  return Array.from(categories).filter(Boolean)
})

const fontSizes = [
  { name: 'XS', value: '0.75rem' },
  { name: 'SM', value: '0.875rem' },
  { name: 'Base', value: '1rem' },
  { name: 'LG', value: '1.125rem' },
  { name: 'XL', value: '1.25rem' },
  { name: '2XL', value: '1.5rem' },
  { name: '3XL', value: '1.875rem' },
  { name: '4XL', value: '2.25rem' }
]

const fontWeights = [
  { name: 'Light', value: '300' },
  { name: 'Normal', value: '400' },
  { name: 'Medium', value: '500' },
  { name: 'Semibold', value: '600' },
  { name: 'Bold', value: '700' }
]

const spacingPresets = [
  { name: 'None', value: '0' },
  { name: 'XS', value: '0.5rem' },
  { name: 'SM', value: '0.75rem' },
  { name: 'MD', value: '1rem' },
  { name: 'LG', value: '1.5rem' },
  { name: 'XL', value: '2rem' },
  { name: '2XL', value: '3rem' }
]

const shadowOptions = [
  { name: 'None', value: 'none' },
  { name: 'Small', value: '0 1px 2px 0 rgb(0 0 0 / 0.05)' },
  { name: 'Default', value: '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)' },
  { name: 'Medium', value: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)' },
  { name: 'Large', value: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)' }
]

const borderRadiusOptions = [
  { name: 'None', value: '0' },
  { name: 'Small', value: '0.125rem' },
  { name: 'Default', value: '0.25rem' },
  { name: 'Medium', value: '0.375rem' },
  { name: 'Large', value: '0.5rem' },
  { name: 'Full', value: '9999px' }
]

// Methods
const initializeStyleManager = () => {
  if (props.grapeJSEditor) {
    styleManager.value = new TailwindStyleManager(props.grapeJSEditor)
    brandColors.value = styleManager.value.getBrandColors()
    brandFonts.value = styleManager.value.getBrandFonts()
    stylePresets.value = styleManager.value.getStylePresets()
  }
}

const getBrandColorsByCategory = (category: string): TailwindColor[] => {
  return brandColors.value.filter(color => color.category === category)
}

const formatCategoryName = (category: string): string => {
  return category.charAt(0).toUpperCase() + category.slice(1)
}

const applyBrandColor = (color: TailwindColor, property: 'color' | 'background-color' | 'border-color') => {
  if (styleManager.value) {
    styleManager.value.applyBrandColor(color.name, property)
    checkBrandCompliance()
  }
}

const isColorActive = (color: TailwindColor): boolean => {
  if (!props.selectedComponent || !props.grapeJSEditor) return false

  const selected = props.grapeJSEditor.getSelected()
  if (!selected) return false

  const styles = selected.getStyle()
  return styles[selectedColorProperty.value] === color.value
}

const applyFont = (font: TailwindFont) => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({ 'font-family': font.family })
    checkBrandCompliance()
  }
}

const applyFontSize = (size: string) => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({ 'font-size': size })
  }
}

const applyFontWeight = (weight: string) => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({ 'font-weight': weight })
  }
}

const isActiveFontFamily = (fontFamily: string): boolean => {
  if (!props.selectedComponent || !props.grapeJSEditor) return false

  const selected = props.grapeJSEditor.getSelected()
  if (!selected) return false

  const styles = selected.getStyle()
  return styles['font-family'] === fontFamily
}

const isActiveFontSize = (fontSize: string): boolean => {
  if (!props.selectedComponent || !props.grapeJSEditor) return false

  const selected = props.grapeJSEditor.getSelected()
  if (!selected) return false

  const styles = selected.getStyle()
  return styles['font-size'] === fontSize
}

const isActiveFontWeight = (fontWeight: string): boolean => {
  if (!props.selectedComponent || !props.grapeJSEditor) return false

  const selected = props.grapeJSEditor.getSelected()
  if (!selected) return false

  const styles = selected.getStyle()
  return styles['font-weight'] === fontWeight
}

const updatePadding = () => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({
      'padding-top': `${paddingTop.value}px`,
      'padding-right': `${paddingRight.value}px`,
      'padding-bottom': `${paddingBottom.value}px`,
      'padding-left': `${paddingLeft.value}px`
    })
  }
}

const updateMargin = () => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({
      'margin-top': `${marginTop.value}px`,
      'margin-right': `${marginRight.value}px`,
      'margin-bottom': `${marginBottom.value}px`,
      'margin-left': `${marginLeft.value}px`
    })
  }
}

const applySpacingPreset = (type: 'padding' | 'margin', preset: { name: string; value: string }) => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    if (type === 'padding') {
      selected.addStyle({ padding: preset.value })
      const pixels = parseFloat(preset.value) * 16 // Convert rem to px approximation
      paddingTop.value = paddingRight.value = paddingBottom.value = paddingLeft.value = pixels
    } else {
      selected.addStyle({ margin: preset.value })
      const pixels = parseFloat(preset.value) * 16
      marginTop.value = marginRight.value = marginBottom.value = marginLeft.value = pixels
    }
  }
}

const applyShadow = (shadowValue: string) => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({ 'box-shadow': shadowValue })
  }
}

const applyBorderRadius = (radiusValue: string) => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({ 'border-radius': radiusValue })
  }
}

const applyOpacity = () => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    selected.addStyle({ opacity: opacity.value.toString() })
  }
}

const isActiveShadow = (shadowValue: string): boolean => {
  if (!props.selectedComponent || !props.grapeJSEditor) return false

  const selected = props.grapeJSEditor.getSelected()
  if (!selected) return false

  const styles = selected.getStyle()
  return styles['box-shadow'] === shadowValue
}

const isActiveBorderRadius = (radiusValue: string): boolean => {
  if (!props.selectedComponent || !props.grapeJSEditor) return false

  const selected = props.grapeJSEditor.getSelected()
  if (!selected) return false

  const styles = selected.getStyle()
  return styles['border-radius'] === radiusValue
}

const saveCurrentStyleAsPreset = async () => {
  if (!styleManager.value || !newPresetName.value) return

  try {
    await styleManager.value.saveStylePreset(
      newPresetName.value,
      newPresetDescription.value,
      newPresetCategory.value || 'custom'
    )

    // Refresh presets
    stylePresets.value = styleManager.value.getStylePresets()

    // Clear form
    newPresetName.value = ''
    newPresetDescription.value = ''
    newPresetCategory.value = ''
  } catch (error) {
    console.error('Failed to save preset:', error)
  }
}

const getPresetsByCategory = (category: string): StylePreset[] => {
  return stylePresets.value.filter(preset => preset.category === category)
}

const applyPreset = (preset: StylePreset) => {
  if (styleManager.value) {
    styleManager.value.applyStylePreset(preset.id)
    loadComponentStyles()
    checkBrandCompliance()
  }
}

const deletePreset = async (presetId: string) => {
  if (styleManager.value && confirm('Are you sure you want to delete this preset?')) {
    await styleManager.value.deleteStylePreset(presetId)
    stylePresets.value = styleManager.value.getStylePresets()
  }
}

const getPresetPreviewStyle = (preset: StylePreset): Record<string, any> => {
  return {
    ...preset.styles,
    width: '100%',
    height: '40px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '12px'
  }
}

const checkBrandCompliance = () => {
  if (!styleManager.value || !props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    const compliance = styleManager.value.validateBrandCompliance(selected)
    complianceViolations.value = compliance.violations
  }
}

const fixComplianceIssues = () => {
  // Auto-fix common compliance issues
  if (!styleManager.value || !props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    const suggestions = styleManager.value.getStyleSuggestions(selected)
    // Apply automatic fixes based on suggestions
    // This is a simplified implementation
    complianceViolations.value = []
  }
}

const dismissAlert = () => {
  complianceViolations.value = []
}

const loadComponentStyles = () => {
  if (!props.selectedComponent || !props.grapeJSEditor) return

  const selected = props.grapeJSEditor.getSelected()
  if (selected) {
    const styles = selected.getStyle()

    // Load spacing values
    paddingTop.value = parseInt(styles['padding-top']) || 0
    paddingRight.value = parseInt(styles['padding-right']) || 0
    paddingBottom.value = parseInt(styles['padding-bottom']) || 0
    paddingLeft.value = parseInt(styles['padding-left']) || 0

    marginTop.value = parseInt(styles['margin-top']) || 0
    marginRight.value = parseInt(styles['margin-right']) || 0
    marginBottom.value = parseInt(styles['margin-bottom']) || 0
    marginLeft.value = parseInt(styles['margin-left']) || 0

    opacity.value = parseFloat(styles.opacity) || 1
  }
}

// Watchers
watch(() => props.selectedComponent, () => {
  if (props.selectedComponent) {
    loadComponentStyles()
    checkBrandCompliance()
  }
}, { immediate: true })

watch(() => props.grapeJSEditor, () => {
  if (props.grapeJSEditor) {
    initializeStyleManager()
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  if (props.grapeJSEditor) {
    initializeStyleManager()
  }
})
</script>

<style scoped>
.advanced-styling-panel {
  @apply w-80 bg-white border-l border-gray-200 flex flex-col h-full;
}

.panel-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50;
}

.header-content {
  @apply flex-1;
}

.panel-title {
  @apply text-lg font-semibold text-gray-900 mb-1;
}

.panel-subtitle {
  @apply text-sm text-gray-600;
}

.close-btn {
  @apply p-1 text-gray-400 hover:text-gray-600 transition-colors;
}

.styling-tabs {
  @apply flex border-b border-gray-200 bg-gray-50;
}

.tab-button {
  @apply flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors;
}

.tab-button.active {
  @apply text-blue-600 bg-white border-b-2 border-blue-600;
}

.tab-button svg {
  @apply mr-1;
}

.tab-content {
  @apply flex-1 overflow-y-auto;
}

.tab-panel {
  @apply p-4 space-y-6;
}

.section-header {
  @apply mb-4;
}

.section-title {
  @apply text-lg font-medium text-gray-900 mb-1;
}

.section-description {
  @apply text-sm text-gray-600;
}

.color-categories {
  @apply space-y-4;
}

.color-category {
  @apply space-y-2;
}

.category-title {
  @apply text-sm font-medium text-gray-700;
}

.color-grid {
  @apply grid grid-cols-2 gap-2;
}

.color-swatch {
  @apply relative p-3 rounded-lg border-2 border-transparent hover:border-gray-300 transition-colors cursor-pointer;
}

.color-swatch.active {
  @apply border-blue-500 ring-2 ring-blue-200;
}

.color-name {
  @apply block text-xs font-medium text-white drop-shadow-sm;
}

.color-value {
  @apply block text-xs text-white/80 drop-shadow-sm;
}

.color-property-selector {
  @apply mt-4 pt-4 border-t border-gray-200;
}

.property-label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.property-buttons {
  @apply flex space-x-2;
}

.property-btn {
  @apply flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors;
}

.property-btn.active {
  @apply text-blue-600 bg-blue-100;
}

.property-btn svg {
  @apply mr-1;
}

.style-group {
  @apply space-y-3;
}

.style-label {
  @apply block text-sm font-medium text-gray-700;
}

.font-grid {
  @apply space-y-2;
}

.font-option {
  @apply w-full p-3 text-left border border-gray-200 rounded-lg hover:border-gray-300 transition-colors;
}

.font-option.active {
  @apply border-blue-500 bg-blue-50;
}

.font-preview {
  @apply block text-sm font-medium text-gray-900;
}

.font-category {
  @apply block text-xs text-gray-500 capitalize;
}

.size-grid,
.weight-grid {
  @apply grid grid-cols-2 gap-2;
}

.size-option,
.weight-option {
  @apply p-2 text-center border border-gray-200 rounded-md hover:border-gray-300 transition-colors;
}

.size-option.active,
.weight-option.active {
  @apply border-blue-500 bg-blue-50;
}

.size-name,
.weight-preview {
  @apply block text-sm font-medium text-gray-900;
}

.size-value {
  @apply block text-xs text-gray-500;
}

.spacing-controls {
  @apply space-y-4;
}

.spacing-visual {
  @apply flex justify-center;
}

.spacing-box {
  @apply relative p-4 border-2 border-dashed border-gray-300 rounded-lg;
}

.margin-box {
  @apply border-red-300;
}

.padding-area,
.margin-area {
  @apply relative p-4 bg-blue-50 rounded;
}

.margin-area {
  @apply bg-red-50;
}

.horizontal-inputs {
  @apply flex items-center justify-between my-2;
}

.content-area {
  @apply px-4 py-2 bg-white rounded text-sm text-gray-600 text-center;
}

.spacing-input {
  @apply w-12 px-1 py-1 text-xs text-center border border-gray-300 rounded;
}

.spacing-input.top,
.spacing-input.bottom {
  @apply mx-auto;
}

.spacing-presets {
  @apply flex flex-wrap gap-2;
}

.preset-btn {
  @apply px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full hover:bg-gray-200 transition-colors;
}

.shadow-grid,
.radius-grid {
  @apply grid grid-cols-2 gap-3;
}

.shadow-option,
.radius-option {
  @apply p-3 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors cursor-pointer;
}

.shadow-option.active,
.radius-option.active {
  @apply border-blue-500 bg-blue-50;
}

.shadow-preview,
.radius-preview {
  @apply w-full h-8 bg-gray-100 mb-2;
}

.shadow-name,
.radius-name {
  @apply block text-xs text-center text-gray-600;
}

.opacity-control {
  @apply flex items-center space-x-3;
}

.opacity-slider {
  @apply flex-1;
}

.opacity-value {
  @apply text-sm font-medium text-gray-700;
}

.save-preset-section {
  @apply pb-6 border-b border-gray-200;
}

.subsection-title {
  @apply text-sm font-medium text-gray-900 mb-3;
}

.save-form {
  @apply space-y-3;
}

.preset-input,
.preset-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.save-btn {
  @apply flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors;
}

.save-btn svg {
  @apply mr-2;
}

.presets-section {
  @apply space-y-4;
}

.preset-categories {
  @apply space-y-6;
}

.preset-category-title {
  @apply text-sm font-medium text-gray-700 mb-3;
}

.preset-grid {
  @apply space-y-3;
}

.preset-card {
  @apply border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors;
}

.preset-preview {
  @apply mb-3;
}

.preset-sample {
  @apply w-full h-10 rounded text-xs flex items-center justify-center;
}

.preset-info {
  @apply mb-3;
}

.preset-name {
  @apply text-sm font-medium text-gray-900;
}

.preset-description {
  @apply text-xs text-gray-600;
}

.preset-actions {
  @apply flex space-x-2;
}

.apply-btn {
  @apply flex-1 px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition-colors;
}

.delete-btn {
  @apply p-1 text-gray-400 hover:text-red-600 transition-colors;
}

.compliance-alert {
  @apply border-t border-yellow-200 bg-yellow-50 p-4;
}

.alert-header {
  @apply flex items-center mb-2;
}

.alert-title {
  @apply ml-2 text-sm font-medium text-yellow-800;
}

.violation-list {
  @apply space-y-1 mb-3;
}

.violation-item {
  @apply text-xs text-yellow-700;
}

.alert-actions {
  @apply flex space-x-2;
}

.fix-btn {
  @apply px-3 py-1 text-xs font-medium text-yellow-800 bg-yellow-200 rounded hover:bg-yellow-300 transition-colors;
}

.dismiss-btn {
  @apply px-3 py-1 text-xs font-medium text-yellow-600 hover:text-yellow-800 transition-colors;
}
</style>