<template>
  <div class="template-customizer">
    <!-- Header -->
    <div class="customizer-header">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Customize Form Template
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Add, remove, or modify fields to create your perfect form
      </p>
    </div>

    <!-- Template Selection -->
    <div class="template-selection mb-6">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Base Template
      </label>
      <select
        v-model="selectedTemplateId"
        @change="loadTemplate"
        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      >
        <option value="">Select a template...</option>
        <option
          v-for="template in availableTemplates"
          :key="template.id"
          :value="template.id"
        >
          {{ template.name }}
        </option>
      </select>
    </div>

    <!-- Customization Options -->
    <div v-if="currentTemplate" class="customization-options">
      <!-- Preset Customizations -->
      <div class="preset-section mb-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
          Quick Presets
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <button
            v-for="preset in availablePresets"
            :key="preset.id"
            @click="applyPreset(preset.id)"
            class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white transition-colors"
          >
            {{ preset.name }}
          </button>
        </div>
      </div>

      <!-- Field Management -->
      <div class="field-management mb-6">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-md font-medium text-gray-900 dark:text-white">
            Form Fields
          </h4>
          <button
            @click="showAddFieldModal = true"
            class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            Add Field
          </button>
        </div>

        <!-- Field List -->
        <div class="field-list space-y-3">
          <div
            v-for="(field, index) in customizedTemplate.config.fields"
            :key="field.id"
            class="field-item p-4 border border-gray-200 rounded-lg dark:border-gray-700"
            :class="{ 'opacity-50': field.disabled }"
          >
            <div class="flex items-center justify-between">
              <div class="field-info flex-1">
                <div class="flex items-center space-x-3">
                  <span class="field-type-badge px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300">
                    {{ field.type }}
                  </span>
                  <span class="field-name font-medium text-gray-900 dark:text-white">
                    {{ field.label }}
                  </span>
                  <span v-if="field.required" class="text-red-500 text-sm">*</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ field.placeholder || field.helpText || 'No description' }}
                </p>
              </div>

              <!-- Field Actions -->
              <div class="field-actions flex items-center space-x-2">
                <button
                  @click="moveField(index, -1)"
                  :disabled="index === 0"
                  class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                  title="Move up"
                >
                  <ChevronUpIcon class="w-4 h-4" />
                </button>
                <button
                  @click="moveField(index, 1)"
                  :disabled="index === customizedTemplate.config.fields.length - 1"
                  class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                  title="Move down"
                >
                  <ChevronDownIcon class="w-4 h-4" />
                </button>
                <button
                  @click="editField(field)"
                  class="p-1 text-blue-600 hover:text-blue-800"
                  title="Edit field"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  @click="removeField(field.id)"
                  :disabled="isRequiredField(field.id)"
                  class="p-1 text-red-600 hover:text-red-800 disabled:opacity-50 disabled:cursor-not-allowed"
                  title="Remove field"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Template Configuration -->
      <div class="template-config mb-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
          Template Settings
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Form Title
            </label>
            <input
              v-model="customizedTemplate.config.title"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Layout
            </label>
            <select
              v-model="customizedTemplate.config.layout"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
              <option value="single-column">Single Column</option>
              <option value="two-column">Two Column</option>
              <option value="grid">Grid</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Theme
            </label>
            <select
              v-model="customizedTemplate.config.theme"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
              <option value="default">Default</option>
              <option value="minimal">Minimal</option>
              <option value="modern">Modern</option>
              <option value="classic">Classic</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Color Scheme
            </label>
            <select
              v-model="customizedTemplate.config.colorScheme"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
              <option value="default">Default</option>
              <option value="primary">Primary</option>
              <option value="secondary">Secondary</option>
              <option value="accent">Accent</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="preview-section mb-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
          Preview
        </h4>
        <div class="preview-container border border-gray-200 rounded-lg p-4 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
          <FormBase
            :config="customizedTemplate.config"
            :preview-mode="true"
            class="max-w-2xl mx-auto"
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="actions flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <button
            @click="saveCustomization"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            Save Template
          </button>
          <button
            @click="exportTemplate"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
          >
            Export JSON
          </button>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="resetTemplate"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
          >
            Reset
          </button>
          <button
            @click="validateTemplate"
            class="px-4 py-2 border border-green-300 text-green-700 rounded-md hover:bg-green-50 dark:border-green-600 dark:text-green-400 dark:hover:bg-green-900/20 transition-colors"
          >
            Validate
          </button>
        </div>
      </div>
    </div>

    <!-- Add Field Modal -->
    <FieldEditorModal
      v-if="showAddFieldModal"
      :field="null"
      :available-field-types="allowedFieldTypes"
      @save="addField"
      @cancel="showAddFieldModal = false"
    />

    <!-- Edit Field Modal -->
    <FieldEditorModal
      v-if="showEditFieldModal && editingField"
      :field="editingField"
      :available-field-types="allowedFieldTypes"
      @save="updateField"
      @cancel="showEditFieldModal = false"
    />

    <!-- Validation Results Modal -->
    <div
      v-if="showValidationResults"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Validation Results
        </h3>
        <div v-if="validationResults.valid" class="text-green-600 dark:text-green-400">
          <CheckCircleIcon class="w-5 h-5 inline mr-2" />
          Template is valid and ready to use!
        </div>
        <div v-else class="text-red-600 dark:text-red-400">
          <XCircleIcon class="w-5 h-5 inline mr-2" />
          <p class="mb-2">Template has validation errors:</p>
          <ul class="list-disc list-inside space-y-1">
            <li v-for="error in validationResults.errors" :key="error" class="text-sm">
              {{ error }}
            </li>
          </ul>
        </div>
        <div class="mt-4 flex justify-end">
          <button
            @click="showValidationResults = false"
            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  ChevronUpIcon, 
  ChevronDownIcon, 
  PencilIcon, 
  TrashIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import FormBase from './FormBase.vue'
import FieldEditorModal from './FieldEditorModal.vue'
import { formTemplates } from './templates'
import { TemplateCustomizer, templateCustomizationUtils } from './templates/customization'
import type { FormTemplate, FormField, FieldCustomizationOptions } from '@/types/components'

// Props
interface Props {
  initialTemplateId?: string
  customizationOptions?: FieldCustomizationOptions
}

const props = withDefaults(defineProps<Props>(), {
  initialTemplateId: '',
  customizationOptions: () => ({
    allowAdd: true,
    allowRemove: true,
    allowModify: true,
    allowReorder: true
  })
})

// Emits
const emit = defineEmits<{
  templateSaved: [template: FormTemplate]
  templateExported: [json: string]
}>()

// Reactive state
const selectedTemplateId = ref(props.initialTemplateId)
const currentTemplate = ref<FormTemplate | null>(null)
const customizedTemplate = ref<FormTemplate | null>(null)
const customizer = ref<TemplateCustomizer | null>(null)

const showAddFieldModal = ref(false)
const showEditFieldModal = ref(false)
const editingField = ref<FormField | null>(null)
const showValidationResults = ref(false)
const validationResults = ref<{ valid: boolean; errors: string[] }>({ valid: true, errors: [] })

// Computed properties
const availableTemplates = computed(() => formTemplates)

const availablePresets = computed(() => [
  { id: 'minimal', name: 'Minimal' },
  { id: 'detailed', name: 'Detailed' },
  { id: 'gdpr-compliant', name: 'GDPR Compliant' }
])

const allowedFieldTypes = computed(() => {
  return props.customizationOptions.allowedFieldTypes || [
    'text', 'email', 'phone', 'select', 'checkbox', 'textarea', 'radio', 'number', 'url', 'date'
  ]
})

const requiredFields = computed(() => {
  return props.customizationOptions.requiredFields || []
})

// Methods
const loadTemplate = () => {
  if (!selectedTemplateId.value) {
    currentTemplate.value = null
    customizedTemplate.value = null
    customizer.value = null
    return
  }

  const template = formTemplates.find(t => t.id === selectedTemplateId.value)
  if (template) {
    currentTemplate.value = { ...template }
    customizedTemplate.value = { ...template }
    customizer.value = new TemplateCustomizer(template)
  }
}

const applyPreset = (presetId: string) => {
  if (!currentTemplate.value || !customizer.value) return

  try {
    const preset = TemplateCustomizer.createPreset(presetId, currentTemplate.value.id)
    const newTemplate = customizer.value.applyCustomizations(preset)
    customizedTemplate.value = newTemplate
  } catch (error) {
    console.error('Failed to apply preset:', error)
  }
}

const addField = (field: FormField) => {
  if (!customizer.value || !customizedTemplate.value) return

  try {
    const newTemplate = customizer.value.addField(field)
    customizedTemplate.value = newTemplate
    showAddFieldModal.value = false
  } catch (error) {
    console.error('Failed to add field:', error)
  }
}

const removeField = (fieldId: string) => {
  if (!customizer.value || !customizedTemplate.value) return

  try {
    const newTemplate = customizer.value.removeField(fieldId, props.customizationOptions)
    customizedTemplate.value = newTemplate
  } catch (error) {
    console.error('Failed to remove field:', error)
    alert(error.message)
  }
}

const editField = (field: FormField) => {
  editingField.value = { ...field }
  showEditFieldModal.value = true
}

const updateField = (updatedField: FormField) => {
  if (!customizer.value || !customizedTemplate.value) return

  try {
    const newTemplate = customizer.value.modifyField(updatedField.id, updatedField)
    customizedTemplate.value = newTemplate
    showEditFieldModal.value = false
    editingField.value = null
  } catch (error) {
    console.error('Failed to update field:', error)
  }
}

const moveField = (index: number, direction: number) => {
  if (!customizedTemplate.value) return

  const fields = [...customizedTemplate.value.config.fields]
  const newIndex = index + direction

  if (newIndex < 0 || newIndex >= fields.length) return

  // Swap fields
  [fields[index], fields[newIndex]] = [fields[newIndex], fields[index]]
  
  customizedTemplate.value.config.fields = fields
}

const isRequiredField = (fieldId: string): boolean => {
  return requiredFields.value.includes(fieldId)
}

const saveCustomization = () => {
  if (!customizedTemplate.value) return

  // Validate before saving
  const validation = validateCurrentTemplate()
  if (!validation.valid) {
    validationResults.value = validation
    showValidationResults.value = true
    return
  }

  emit('templateSaved', customizedTemplate.value)
}

const exportTemplate = () => {
  if (!customizer.value) return

  const json = customizer.value.exportCustomization()
  emit('templateExported', json)
  
  // Download as file
  const blob = new Blob([json], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${customizedTemplate.value?.id || 'template'}-customized.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const resetTemplate = () => {
  if (currentTemplate.value) {
    customizedTemplate.value = { ...currentTemplate.value }
    customizer.value = new TemplateCustomizer(currentTemplate.value)
  }
}

const validateTemplate = () => {
  const validation = validateCurrentTemplate()
  validationResults.value = validation
  showValidationResults.value = true
}

const validateCurrentTemplate = (): { valid: boolean; errors: string[] } => {
  if (!customizer.value || !customizedTemplate.value) {
    return { valid: false, errors: ['No template loaded'] }
  }

  // Create a mock customization to validate
  const mockCustomization = {
    id: 'validation-test',
    templateId: customizedTemplate.value.id,
    name: 'Validation Test',
    modifications: {
      addedFields: [],
      removedFieldIds: [],
      modifiedFields: [],
      configChanges: {}
    },
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  }

  return customizer.value.validateCustomization(mockCustomization, props.customizationOptions)
}

// Lifecycle
onMounted(() => {
  if (props.initialTemplateId) {
    loadTemplate()
  }
})
</script>

<style scoped>
.template-customizer {
  @apply max-w-4xl mx-auto p-6 bg-white dark:bg-gray-900 rounded-lg shadow-lg;
}

.customizer-header {
  @apply mb-6 pb-4 border-b border-gray-200 dark:border-gray-700;
}

.field-item {
  @apply transition-all duration-200;
}

.field-item:hover {
  @apply shadow-sm;
}

.field-type-badge {
  @apply font-mono;
}

.preview-container {
  @apply max-h-96 overflow-y-auto;
}
</style>