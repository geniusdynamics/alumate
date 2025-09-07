<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div
        class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        @click="$emit('cancel')"
      ></div>

      <!-- Modal panel -->
      <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ isNew ? 'Create Brand Template' : 'Edit Brand Template' }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ isNew ? 'Create a new brand template with colors, fonts, and styling' : 'Update template properties and configuration' }}
            </p>
          </div>
          <button
            @click="$emit('cancel')"
            class="btn-icon"
          >
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit">
          <div class="space-y-8">
            <!-- Template Preview -->
            <div class="template-preview-section">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Template Preview
              </label>
              <div class="template-preview-container">
                <div class="preview-card" :style="previewStyles">
                  <div class="preview-header">
                    <h3 class="preview-title">{{ form.name || 'Template Name' }}</h3>
                    <p class="preview-subtitle">Sample component with template styling</p>
                  </div>
                  <div class="preview-content">
                    <div class="preview-colors">
                      <div
                        v-for="color in selectedColors"
                        :key="color.id"
                        class="preview-color-swatch"
                        :style="{ backgroundColor: color.value }"
                        :title="color.name"
                      ></div>
                    </div>
                    <div class="preview-text">
                      <p class="preview-body-text">
                        This is how body text will appear with the selected typography settings.
                      </p>
                      <button class="preview-button" :style="buttonStyles">
                        Sample Button
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Template Name *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="form-input"
                  placeholder="e.g., Corporate Blue"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Template Tags
                </label>
                <input
                  v-model="tagsInput"
                  type="text"
                  class="form-input"
                  placeholder="e.g., professional, modern, blue"
                  @input="updateTags"
                />
                <p class="text-xs text-gray-500 mt-1">
                  Comma-separated tags for categorization
                </p>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="form.description"
                class="form-textarea"
                rows="3"
                placeholder="Describe when and how this template should be used..."
              ></textarea>
            </div>

            <!-- Color Selection -->
            <div class="color-selection-section">
              <div class="section-header">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                  Color Palette
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Select colors from your brand assets to include in this template
                </p>
              </div>

              <div class="color-grid">
                <div
                  v-for="color in brandAssets.colors"
                  :key="color.id"
                  class="color-option"
                  :class="{ 'color-option--selected': isColorSelected(color) }"
                  @click="toggleColor(color)"
                >
                  <div
                    class="color-swatch"
                    :style="{ backgroundColor: color.value }"
                  ></div>
                  <div class="color-info">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ color.name }}
                    </p>
                    <p class="text-xs text-gray-500 font-mono">
                      {{ color.value }}
                    </p>
                    <p class="text-xs text-gray-400">
                      {{ color.type }}
                    </p>
                  </div>
                  <div class="color-selection-indicator">
                    <Icon
                      v-if="isColorSelected(color)"
                      name="check-circle"
                      class="w-5 h-5 text-blue-600"
                    />
                  </div>
                </div>
              </div>

              <div v-if="selectedColors.length === 0" class="empty-state">
                <Icon name="color-swatch" class="w-8 h-8 text-gray-400 mb-2" />
                <p class="text-sm text-gray-500">
                  Select colors to include in this template
                </p>
              </div>
            </div>

            <!-- Typography Selection -->
            <div class="typography-selection-section">
              <div class="section-header">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                  Typography
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Choose primary and secondary fonts for this template
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Primary Font
                  </label>
                  <select
                    v-model="form.primaryFont"
                    class="form-select"
                  >
                    <option value="">Select primary font</option>
                    <option
                      v-for="font in brandAssets.fonts"
                      :key="font.id"
                      :value="font.family"
                    >
                      {{ font.name }} ({{ font.type }})
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Secondary Font (Optional)
                  </label>
                  <select
                    v-model="form.secondaryFont"
                    class="form-select"
                  >
                    <option value="">Select secondary font</option>
                    <option
                      v-for="font in brandAssets.fonts"
                      :key="font.id"
                      :value="font.family"
                    >
                      {{ font.name }} ({{ font.type }})
                    </option>
                  </select>
                </div>
              </div>

              <!-- Font Preview -->
              <div v-if="form.primaryFont" class="font-preview">
                <div class="font-sample" :style="{ fontFamily: form.primaryFont }">
                  <h4 class="text-lg font-semibold">Primary Font Sample</h4>
                  <p class="text-sm">The quick brown fox jumps over the lazy dog</p>
                </div>
                <div v-if="form.secondaryFont" class="font-sample" :style="{ fontFamily: form.secondaryFont }">
                  <h4 class="text-lg font-semibold">Secondary Font Sample</h4>
                  <p class="text-sm">The quick brown fox jumps over the lazy dog</p>
                </div>
              </div>
            </div>

            <!-- Logo Variant Selection -->
            <div class="logo-selection-section">
              <div class="section-header">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                  Logo Variant
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Choose which logo variant works best with this template
                </p>
              </div>

              <div class="logo-grid">
                <div
                  v-for="logo in brandAssets.logos"
                  :key="logo.id"
                  class="logo-option"
                  :class="{ 'logo-option--selected': form.logoVariant === logo.id }"
                  @click="form.logoVariant = logo.id"
                >
                  <div class="logo-preview">
                    <img
                      :src="logo.url"
                      :alt="logo.alt"
                      class="max-h-12 max-w-full object-contain"
                    />
                  </div>
                  <div class="logo-info">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ logo.name }}
                    </p>
                    <p class="text-xs text-gray-500">
                      {{ logo.type }}
                    </p>
                  </div>
                  <div class="logo-selection-indicator">
                    <Icon
                      v-if="form.logoVariant === logo.id"
                      name="check-circle"
                      class="w-5 h-5 text-blue-600"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Template Settings -->
            <div class="template-settings-section">
              <div class="section-header">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                  Template Settings
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Additional configuration for this template
                </p>
              </div>

              <div class="settings-grid">
                <div class="setting-item">
                  <label class="flex items-center">
                    <input
                      v-model="form.isDefault"
                      type="checkbox"
                      class="form-checkbox"
                    />
                    <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                      Set as default template
                    </span>
                  </label>
                  <p class="text-xs text-gray-500 mt-1">
                    This template will be automatically applied to new components
                  </p>
                </div>

                <div class="setting-item">
                  <label class="flex items-center">
                    <input
                      v-model="form.autoApplyToExisting"
                      type="checkbox"
                      class="form-checkbox"
                    />
                    <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                      Apply to existing components
                    </span>
                  </label>
                  <p class="text-xs text-gray-500 mt-1">
                    Update existing components to use this template
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex gap-3">
              <button
                v-if="!isNew"
                type="button"
                @click="previewTemplate"
                class="btn-secondary"
              >
                <Icon name="eye" class="w-4 h-4 mr-2" />
                Preview Template
              </button>
              <button
                type="button"
                @click="exportTemplate"
                class="btn-secondary"
              >
                <Icon name="download" class="w-4 h-4 mr-2" />
                Export Template
              </button>
            </div>
            <div class="flex gap-3">
              <button
                type="button"
                @click="$emit('cancel')"
                class="btn-secondary"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="btn-primary"
                :disabled="!isFormValid"
              >
                {{ isNew ? 'Create Template' : 'Update Template' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import type { BrandTemplate, BrandAssets, BrandColor } from '@/types/components'

interface Props {
  template?: BrandTemplate | null
  isNew: boolean
  brandAssets: BrandAssets
}

const props = withDefaults(defineProps<Props>(), {
  template: null
})

const emit = defineEmits<{
  save: [templateData: any]
  cancel: []
  preview: [template: BrandTemplate]
  export: [template: BrandTemplate]
}>()

// Form state
const form = ref({
  name: '',
  description: '',
  colors: [] as string[],
  primaryFont: '',
  secondaryFont: '',
  logoVariant: '',
  tags: [] as string[],
  isDefault: false,
  autoApplyToExisting: false
})

const tagsInput = ref('')

// Computed properties
const selectedColors = computed(() => {
  return props.brandAssets.colors.filter(color => 
    form.value.colors.includes(color.id)
  )
})

const primaryColor = computed(() => {
  return selectedColors.value.find(color => color.type === 'primary') || selectedColors.value[0]
})

const previewStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (form.value.primaryFont) {
    styles.fontFamily = form.value.primaryFont
  }
  
  if (primaryColor.value) {
    styles.borderColor = primaryColor.value.value
  }
  
  return styles
})

const buttonStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (primaryColor.value) {
    styles.backgroundColor = primaryColor.value.value
    styles.color = '#ffffff'
  }
  
  return styles
})

const isFormValid = computed(() => {
  return form.value.name.trim() !== '' && 
         selectedColors.value.length > 0 &&
         form.value.primaryFont !== ''
})

// Methods
const isColorSelected = (color: BrandColor): boolean => {
  return form.value.colors.includes(color.id)
}

const toggleColor = (color: BrandColor) => {
  const index = form.value.colors.indexOf(color.id)
  if (index > -1) {
    form.value.colors.splice(index, 1)
  } else {
    form.value.colors.push(color.id)
  }
}

const updateTags = () => {
  form.value.tags = tagsInput.value
    .split(',')
    .map(tag => tag.trim())
    .filter(tag => tag !== '')
}

const previewTemplate = () => {
  const templateData = createTemplateData()
  emit('preview', templateData)
}

const exportTemplate = () => {
  const templateData = createTemplateData()
  emit('export', templateData)
}

const createTemplateData = () => {
  return {
    id: props.template?.id || '',
    name: form.value.name,
    description: form.value.description,
    colors: selectedColors.value,
    primaryFont: form.value.primaryFont,
    secondaryFont: form.value.secondaryFont,
    logoVariant: form.value.logoVariant,
    tags: form.value.tags,
    isDefault: form.value.isDefault,
    previewImage: '', // Will be generated server-side
    usageCount: props.template?.usageCount || 0
  }
}

const handleSubmit = () => {
  if (!isFormValid.value) return

  const templateData = {
    name: form.value.name.trim(),
    description: form.value.description.trim(),
    colorIds: form.value.colors,
    primaryFont: form.value.primaryFont,
    secondaryFont: form.value.secondaryFont || undefined,
    logoVariant: form.value.logoVariant || undefined,
    tags: form.value.tags,
    isDefault: form.value.isDefault,
    autoApplyToExisting: form.value.autoApplyToExisting
  }

  emit('save', templateData)
}

// Initialize form with existing template data
onMounted(() => {
  if (props.template) {
    form.value = {
      name: props.template.name,
      description: props.template.description,
      colors: props.template.colors.map(c => c.id),
      primaryFont: props.template.primaryFont,
      secondaryFont: props.template.secondaryFont || '',
      logoVariant: props.template.logoVariant || '',
      tags: props.template.tags || [],
      isDefault: props.template.isDefault || false,
      autoApplyToExisting: false
    }
    
    tagsInput.value = form.value.tags.join(', ')
  }
})

// Watch for template prop changes
watch(() => props.template, (newTemplate) => {
  if (newTemplate) {
    form.value = {
      name: newTemplate.name,
      description: newTemplate.description,
      colors: newTemplate.colors.map(c => c.id),
      primaryFont: newTemplate.primaryFont,
      secondaryFont: newTemplate.secondaryFont || '',
      logoVariant: newTemplate.logoVariant || '',
      tags: newTemplate.tags || [],
      isDefault: newTemplate.isDefault || false,
      autoApplyToExisting: false
    }
    
    tagsInput.value = form.value.tags.join(', ')
  }
})
</script>

<style scoped>
.btn-icon {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-4 py-2 rounded-md font-medium transition-colors duration-200;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.form-input {
  @apply block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.form-select {
  @apply block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.form-textarea {
  @apply block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.form-checkbox {
  @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.template-preview-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.template-preview-container {
  @apply bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600;
}

.preview-card {
  @apply bg-white dark:bg-gray-800 rounded-lg p-6 border-2 border-gray-200 dark:border-gray-600 transition-all duration-200;
}

.preview-header {
  @apply mb-4;
}

.preview-title {
  @apply text-xl font-bold text-gray-900 dark:text-white;
}

.preview-subtitle {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.preview-content {
  @apply space-y-4;
}

.preview-colors {
  @apply flex gap-2;
}

.preview-color-swatch {
  @apply w-6 h-6 rounded border border-gray-200;
}

.preview-text {
  @apply space-y-3;
}

.preview-body-text {
  @apply text-gray-700 dark:text-gray-300;
}

.preview-button {
  @apply px-4 py-2 rounded-md font-medium transition-colors duration-200;
}

.section-header {
  @apply mb-4;
}

.color-selection-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.color-grid {
  @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4;
}

.color-option {
  @apply flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-600 cursor-pointer transition-all duration-200 hover:border-blue-300;
}

.color-option--selected {
  @apply border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.color-swatch {
  @apply w-8 h-8 rounded border border-gray-200 flex-shrink-0;
}

.color-info {
  @apply flex-1 min-w-0;
}

.color-selection-indicator {
  @apply flex-shrink-0;
}

.empty-state {
  @apply flex flex-col items-center justify-center py-8 text-center;
}

.typography-selection-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.font-preview {
  @apply mt-4 space-y-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600;
}

.font-sample {
  @apply space-y-2;
}

.logo-selection-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.logo-grid {
  @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4;
}

.logo-option {
  @apply flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-600 cursor-pointer transition-all duration-200 hover:border-blue-300;
}

.logo-option--selected {
  @apply border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.logo-preview {
  @apply w-16 h-16 flex items-center justify-center bg-gray-100 dark:bg-gray-600 rounded;
}

.logo-info {
  @apply flex-1 min-w-0;
}

.logo-selection-indicator {
  @apply flex-shrink-0;
}

.template-settings-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.settings-grid {
  @apply space-y-4;
}

.setting-item {
  @apply space-y-1;
}
</style>