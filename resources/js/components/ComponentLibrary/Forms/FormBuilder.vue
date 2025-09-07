<template>
  <div class="form-builder bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Form Builder
        </h3>
        <div class="flex items-center space-x-2">
          <button
            type="button"
            class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-md hover:bg-blue-200 dark:hover:bg-blue-900/50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            @click="previewForm"
          >
            Preview
          </button>
          <button
            type="button"
            class="px-3 py-1 text-sm bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-md hover:bg-green-200 dark:hover:bg-green-900/50 focus:outline-none focus:ring-2 focus:ring-green-500"
            @click="saveForm"
          >
            Save
          </button>
        </div>
      </div>
    </div>

    <div class="flex h-[600px]">
      <!-- Field Library Sidebar -->
      <div class="w-64 bg-gray-50 dark:bg-gray-700 border-r border-gray-200 dark:border-gray-600 overflow-y-auto">
        <div class="p-4">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
            Field Types
          </h4>
          
          <div class="space-y-2">
            <div
              v-for="fieldType in fieldTypes"
              :key="fieldType.type"
              class="field-type-item p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 cursor-grab hover:shadow-md transition-shadow"
              :draggable="true"
              @dragstart="handleFieldTypeDragStart($event, fieldType)"
              @dragend="handleFieldTypeDragEnd"
            >
              <div class="flex items-center">
                <component
                  :is="fieldType.icon"
                  class="w-5 h-5 text-gray-500 dark:text-gray-400 mr-3"
                  aria-hidden="true"
                />
                <div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ fieldType.name }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ fieldType.description }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Canvas -->
      <div class="flex-1 flex flex-col">
        <!-- Form Settings Bar -->
        <div class="bg-white dark:bg-gray-800 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Layout:
                </label>
                <select
                  v-model="formConfig.layout"
                  class="ml-2 text-sm border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                >
                  <option value="single-column">Single Column</option>
                  <option value="two-column">Two Column</option>
                  <option value="grid">Grid</option>
                </select>
              </div>
              
              <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Spacing:
                </label>
                <select
                  v-model="formConfig.spacing"
                  class="ml-2 text-sm border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                >
                  <option value="compact">Compact</option>
                  <option value="default">Default</option>
                  <option value="spacious">Spacious</option>
                </select>
              </div>
            </div>
            
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ formConfig.fields.length }} field{{ formConfig.fields.length !== 1 ? 's' : '' }}
            </div>
          </div>
        </div>

        <!-- Drop Zone -->
        <div
          class="flex-1 p-6 overflow-y-auto"
          :class="dropZoneClasses"
          @dragover.prevent="handleDragOver"
          @dragenter.prevent="handleDragEnter"
          @dragleave="handleDragLeave"
          @drop.prevent="handleDrop"
        >
          <!-- Empty State -->
          <div
            v-if="formConfig.fields.length === 0"
            class="h-full flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg"
          >
            <div class="text-center">
              <svg
                class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                stroke="currentColor"
                fill="none"
                viewBox="0 0 48 48"
                aria-hidden="true"
              >
                <path
                  d="M8 14v20c0 4.418 7.163 8 16 8 1.381 0 2.721-.087 4-.252M8 14c0 4.418 7.163 8 16 8s16-3.582 16-8M8 14c0-4.418 7.163-8 16-8s16 3.582 16 8m0 0v14m-16-4c0 4.418 7.163 8 16 8 1.381 0 2.721-.087 4-.252"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No fields added
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Drag field types from the sidebar to start building your form.
              </p>
            </div>
          </div>

          <!-- Form Fields -->
          <div
            v-else
            class="space-y-4"
          >
            <div
              v-for="(field, index) in formConfig.fields"
              :key="field.id"
              class="form-field-item relative group"
              :class="{ 'ring-2 ring-blue-500': selectedFieldId === field.id }"
              @click="selectField(field.id)"
            >
              <!-- Drop Indicator -->
              <div
                v-if="dragOverIndex === index"
                class="absolute -top-2 left-0 right-0 h-1 bg-blue-500 rounded-full z-10"
              />

              <!-- Field Preview -->
              <div
                class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 transition-colors cursor-pointer"
                :draggable="true"
                @dragstart="handleFieldDragStart($event, field, index)"
                @dragend="handleFieldDragEnd"
              >
                <FormFieldRenderer
                  :field="field"
                  :value="getFieldPreviewValue(field)"
                  :form-id="'preview'"
                  :draggable="true"
                  @update:value="() => {}"
                />
                
                <!-- Field Actions -->
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <div class="flex items-center space-x-1">
                    <button
                      type="button"
                      class="p-1 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                      :aria-label="`Edit ${field.label} field`"
                      @click.stop="editField(field.id)"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                      </svg>
                    </button>
                    
                    <button
                      type="button"
                      class="p-1 text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
                      :aria-label="`Delete ${field.label} field`"
                      @click.stop="deleteField(field.id)"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Drop Indicator (bottom) -->
              <div
                v-if="dragOverIndex === index + 1"
                class="absolute -bottom-2 left-0 right-0 h-1 bg-blue-500 rounded-full z-10"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Field Properties Panel -->
      <div
        v-if="selectedField"
        class="w-80 bg-gray-50 dark:bg-gray-700 border-l border-gray-200 dark:border-gray-600 overflow-y-auto"
      >
        <div class="p-4">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
            Field Properties
          </h4>
          
          <FieldPropertiesEditor
            :field="selectedField"
            @update:field="updateField"
          />
        </div>
      </div>
    </div>

    <!-- Field Editor Modal -->
    <FieldEditorModal
      v-if="editingField"
      :field="editingField"
      @save="saveFieldEdit"
      @cancel="cancelFieldEdit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import type { FormComponentConfig, FormField, FormFieldType } from '@/types/components'
import FormFieldRenderer from './FormFieldRenderer.vue'
import FieldPropertiesEditor from './FieldPropertiesEditor.vue'
import FieldEditorModal from './FieldEditorModal.vue'

interface Props {
  initialConfig?: FormComponentConfig
}

interface Emits {
  (e: 'update:config', config: FormComponentConfig): void
  (e: 'preview', config: FormComponentConfig): void
  (e: 'save', config: FormComponentConfig): void
}

const props = withDefaults(defineProps<Props>(), {
  initialConfig: () => ({
    fields: [],
    layout: 'single-column',
    spacing: 'default',
    submission: {
      method: 'POST',
      action: '/api/forms/submit'
    }
  })
})

const emit = defineEmits<Emits>()

// Refs
const isDragOver = ref(false)
const dragOverIndex = ref<number | null>(null)
const selectedFieldId = ref<string | null>(null)
const editingField = ref<FormField | null>(null)

// Form configuration
const formConfig = reactive<FormComponentConfig>({ ...props.initialConfig })

// Field types library
const fieldTypes = [
  {
    type: 'text' as FormFieldType,
    name: 'Text Input',
    description: 'Single line text input',
    icon: 'TextIcon'
  },
  {
    type: 'email' as FormFieldType,
    name: 'Email',
    description: 'Email address input',
    icon: 'EmailIcon'
  },
  {
    type: 'phone' as FormFieldType,
    name: 'Phone',
    description: 'Phone number input',
    icon: 'PhoneIcon'
  },
  {
    type: 'textarea' as FormFieldType,
    name: 'Textarea',
    description: 'Multi-line text input',
    icon: 'TextareaIcon'
  },
  {
    type: 'select' as FormFieldType,
    name: 'Select',
    description: 'Dropdown selection',
    icon: 'SelectIcon'
  },
  {
    type: 'checkbox' as FormFieldType,
    name: 'Checkbox',
    description: 'Single checkbox',
    icon: 'CheckboxIcon'
  },
  {
    type: 'radio' as FormFieldType,
    name: 'Radio Group',
    description: 'Multiple choice selection',
    icon: 'RadioIcon'
  },
  {
    type: 'number' as FormFieldType,
    name: 'Number',
    description: 'Numeric input',
    icon: 'NumberIcon'
  },
  {
    type: 'url' as FormFieldType,
    name: 'URL',
    description: 'Website URL input',
    icon: 'UrlIcon'
  },
  {
    type: 'date' as FormFieldType,
    name: 'Date',
    description: 'Date picker',
    icon: 'DateIcon'
  }
]

// Computed properties
const selectedField = computed(() => {
  if (!selectedFieldId.value) return null
  return formConfig.fields.find(field => field.id === selectedFieldId.value) || null
})

const dropZoneClasses = computed(() => [
  'transition-colors duration-200',
  {
    'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700': isDragOver.value,
    'bg-gray-100 dark:bg-gray-900': !isDragOver.value,
  }
])

// Methods
const generateFieldId = () => {
  return `field-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`
}

const createFieldFromType = (type: FormFieldType): FormField => {
  const baseField: FormField = {
    id: generateFieldId(),
    type,
    name: `${type}_${Date.now()}`,
    label: `${type.charAt(0).toUpperCase() + type.slice(1)} Field`,
    required: false,
    width: 'full'
  }

  // Add type-specific defaults
  switch (type) {
    case 'textarea':
      baseField.rows = 4
      break
    case 'select':
    case 'radio':
      baseField.options = [
        { label: 'Option 1', value: 'option1' },
        { label: 'Option 2', value: 'option2' }
      ]
      break
    case 'number':
      baseField.min = 0
      baseField.step = 1
      break
    case 'email':
      baseField.placeholder = 'Enter your email address'
      break
    case 'phone':
      baseField.placeholder = 'Enter your phone number'
      break
    case 'url':
      baseField.placeholder = 'https://example.com'
      break
  }

  return baseField
}

const getFieldPreviewValue = (field: FormField) => {
  switch (field.type) {
    case 'text':
    case 'email':
    case 'phone':
    case 'url':
      return field.placeholder || 'Sample text'
    case 'textarea':
      return 'Sample textarea content...'
    case 'number':
      return 42
    case 'checkbox':
      return false
    case 'select':
    case 'radio':
      return field.options?.[0]?.value || ''
    case 'date':
      return '2024-01-01'
    default:
      return ''
  }
}

// Drag and drop handlers
const handleFieldTypeDragStart = (event: DragEvent, fieldType: any) => {
  if (event.dataTransfer) {
    event.dataTransfer.setData('application/json', JSON.stringify({
      type: 'field-type',
      fieldType: fieldType.type
    }))
    event.dataTransfer.effectAllowed = 'copy'
  }
}

const handleFieldTypeDragEnd = () => {
  // Reset any drag state if needed
}

const handleFieldDragStart = (event: DragEvent, field: FormField, index: number) => {
  if (event.dataTransfer) {
    event.dataTransfer.setData('application/json', JSON.stringify({
      type: 'field-reorder',
      fieldId: field.id,
      fromIndex: index
    }))
    event.dataTransfer.effectAllowed = 'move'
  }
}

const handleFieldDragEnd = () => {
  dragOverIndex.value = null
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  
  // Calculate drop position
  const container = event.currentTarget as HTMLElement
  const rect = container.getBoundingClientRect()
  const y = event.clientY - rect.top
  
  // Find the closest field position
  const fieldElements = container.querySelectorAll('.form-field-item')
  let closestIndex = formConfig.fields.length
  
  for (let i = 0; i < fieldElements.length; i++) {
    const fieldRect = fieldElements[i].getBoundingClientRect()
    const fieldY = fieldRect.top - rect.top + fieldRect.height / 2
    
    if (y < fieldY) {
      closestIndex = i
      break
    }
  }
  
  dragOverIndex.value = closestIndex
}

const handleDragEnter = () => {
  isDragOver.value = true
}

const handleDragLeave = (event: DragEvent) => {
  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
  const x = event.clientX
  const y = event.clientY
  
  // Only hide drag over state if mouse is outside the drop zone
  if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
    isDragOver.value = false
    dragOverIndex.value = null
  }
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  
  try {
    const data = JSON.parse(event.dataTransfer?.getData('application/json') || '{}')
    
    if (data.type === 'field-type') {
      // Add new field
      const newField = createFieldFromType(data.fieldType)
      const insertIndex = dragOverIndex.value ?? formConfig.fields.length
      
      formConfig.fields.splice(insertIndex, 0, newField)
      selectedFieldId.value = newField.id
      
    } else if (data.type === 'field-reorder') {
      // Reorder existing field
      const fromIndex = data.fromIndex
      const toIndex = dragOverIndex.value ?? formConfig.fields.length
      
      if (fromIndex !== toIndex) {
        const field = formConfig.fields.splice(fromIndex, 1)[0]
        const adjustedToIndex = toIndex > fromIndex ? toIndex - 1 : toIndex
        formConfig.fields.splice(adjustedToIndex, 0, field)
      }
    }
    
  } catch (error) {
    console.error('Error handling drop:', error)
  } finally {
    dragOverIndex.value = null
    emitConfigUpdate()
  }
}

// Field management
const selectField = (fieldId: string) => {
  selectedFieldId.value = fieldId
}

const editField = (fieldId: string) => {
  const field = formConfig.fields.find(f => f.id === fieldId)
  if (field) {
    editingField.value = { ...field }
  }
}

const deleteField = (fieldId: string) => {
  const index = formConfig.fields.findIndex(f => f.id === fieldId)
  if (index !== -1) {
    formConfig.fields.splice(index, 1)
    if (selectedFieldId.value === fieldId) {
      selectedFieldId.value = null
    }
    emitConfigUpdate()
  }
}

const updateField = (updatedField: FormField) => {
  const index = formConfig.fields.findIndex(f => f.id === updatedField.id)
  if (index !== -1) {
    formConfig.fields[index] = { ...updatedField }
    emitConfigUpdate()
  }
}

const saveFieldEdit = (updatedField: FormField) => {
  updateField(updatedField)
  editingField.value = null
}

const cancelFieldEdit = () => {
  editingField.value = null
}

// Form actions
const previewForm = () => {
  emit('preview', { ...formConfig })
}

const saveForm = () => {
  emit('save', { ...formConfig })
}

const emitConfigUpdate = () => {
  emit('update:config', { ...formConfig })
}

// Icon components (simplified placeholders)
const TextIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z"/></svg>' }
const EmailIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>' }
const PhoneIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>' }
const TextareaIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>' }
const SelectIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>' }
const CheckboxIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' }
const RadioIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' }
const NumberIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>' }
const UrlIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/></svg>' }
const DateIcon = { template: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>' }
</script>

<style scoped>
/* Drag and drop visual feedback */
.field-type-item:active {
  cursor: grabbing;
}

.form-field-item {
  position: relative;
}

/* Smooth transitions for drag operations */
.form-field-item {
  transition: all 0.2s ease;
}

.form-field-item:hover {
  transform: translateY(-1px);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .border-gray-200 {
    border-color: #000000;
  }
  
  .bg-gray-50 {
    background-color: #ffffff;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .form-field-item {
    transition: none;
  }
  
  .form-field-item:hover {
    transform: none;
  }
}
</style>