<template>
  <div class="form-builder" :class="{ 'loading': isLoading, 'error': hasError }">
    <!-- Form Builder Header -->
    <div class="form-builder-header">
      <div class="header-left">
        <h2 class="form-title">{{ formConfig.name || 'Untitled Form' }}</h2>
        <span class="form-status" :class="formStatusClass">
          {{ formStatusText }}
        </span>
      </div>
      <div class="header-right">
        <button
          @click="togglePreviewMode"
          class="preview-btn"
          :aria-pressed="isPreviewMode"
          :aria-label="`${isPreviewMode ? 'Exit' : 'Enter'} preview mode`"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
          {{ isPreviewMode ? 'Edit' : 'Preview' }}
        </button>
        <button
          @click="saveForm"
          :disabled="isSaving"
          class="save-btn"
          :aria-label="isSaving ? 'Saving form...' : 'Save form'"
        >
          <svg v-if="isSaving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
            <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          {{ isSaving ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>

    <!-- Form Builder Content -->
    <div class="form-builder-content" v-if="!isPreviewMode">
      <!-- Field Palette -->
      <div class="field-palette">
        <h3 class="palette-title">Form Fields</h3>
        <div class="field-types">
          <div
            v-for="fieldType in availableFieldTypes"
            :key="fieldType.type"
            class="field-type-item"
            draggable="true"
            @dragstart="onDragStart($event, fieldType)"
            :aria-label="`Add ${fieldType.label} field`"
          >
            <component :is="fieldType.icon" class="field-icon" />
            <span class="field-label">{{ fieldType.label }}</span>
          </div>
        </div>
      </div>

      <!-- Form Canvas -->
      <div class="form-canvas">
        <div class="form-canvas-header">
          <h3 class="canvas-title">Form Design</h3>
          <div class="canvas-actions">
            <button @click="showFormSettings = true" class="settings-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 100 4m0-4v2m0 16v2m0-2a2 2 0 100-4m0 4a2 2 0 100-4m0 4v-2m-6-8h2m10 0h2M4.93 4.93l1.41 1.41m10.73 0l1.41 1.41M4.93 19.07l1.41-1.41m10.73 0l1.41-1.41"></path>
              </svg>
            </button>
            <button @click="showCRMSettings = true" class="crm-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </button>
          </div>
        </div>

        <div
          class="form-drop-zone"
          @dragover.prevent
          @drop="onDrop($event)"
          :class="{ 'drag-over': isDragOver }"
        >
          <div v-if="formFields.length === 0" class="empty-state">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="empty-text">Drag fields here to build your form</p>
          </div>

          <div v-else class="form-fields">
            <FormFieldComponent
              v-for="(field, index) in formFields"
              :key="field.id"
              :field="field"
              :index="index"
              @update="updateField($event, index)"
              @remove="removeField(index)"
              @move="moveField($event, index)"
              @duplicate="duplicateField(index)"
              @select="selectField(field)"
            />
          </div>
        </div>
      </div>

      <!-- Field Properties Panel -->
      <div class="field-properties" v-if="selectedField">
        <div class="properties-header">
          <h3 class="properties-title">Field Properties</h3>
          <button @click="selectedField = null" class="close-properties-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="properties-content">
          <FormFieldEditor
            :field="selectedField"
            @update="updateSelectedField"
            @add-validation="addFieldValidation"
            @remove-validation="removeFieldValidation"
          />
        </div>
      </div>
    </div>

    <!-- Form Preview -->
    <div class="form-preview" v-else>
      <div class="preview-header">
        <h3 class="preview-title">Form Preview</h3>
        <button @click="togglePreviewMode" class="exit-preview-btn">
          Exit Preview
        </button>
      </div>

      <div class="preview-content">
        <FormPreview
          :form-config="formConfig"
          :fields="formFields"
          @submit="handleFormSubmission"
          :is-submitting="isSubmitting"
        />
      </div>
    </div>

    <!-- Modals -->
    <FormSettingsModal
      v-if="showFormSettings"
      :config="formConfig"
      @update="updateFormConfig"
      @close="showFormSettings = false"
    />

    <CRMIntegrationModal
      v-if="showCRMSettings"
      :tenant-id="tenantId"
      :form-id="formId"
      :fields="formFields"
      :integration="crmIntegration"
      @update="onCRMIntegrationUpdated"
      @close="showCRMSettings = false"
    />

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>{{ loadingMessage }}</p>
      </div>
    </div>

    <!-- Error Overlay -->
    <div v-if="hasError" class="error-overlay">
      <div class="error-content">
        <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <h3 class="error-title">Form Builder Error</h3>
        <p class="error-message">{{ errorMessage }}</p>
        <button @click="resetBuilder" class="error-btn">
          Try Again
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { io, type Socket } from 'socket.io-client'
import FormFieldComponent from './forms/FormField.vue'
// import FormFieldEditor from './FormFieldEditor.vue'
// import FormPreview from './FormPreview.vue'
// import FormSettingsModal from './FormSettingsModal.vue'
// import CRMIntegrationModal from './CRMIntegrationModal.vue'
import type {
  FormConfig,
  FormField,
  FieldType,
  ValidationRule,
  CRMIntegration,
  FormSubmission
} from '@/types/forms'

// Icon components (simplified)
const TextIcon = { template: '<span>T</span>' }
const EmailIcon = { template: '<span>@</span>' }
const SelectIcon = { template: '<span>▼</span>' }
const CheckboxIcon = { template: '<span>☐</span>' }
const RadioIcon = { template: '<span>○</span>' }
const TextareaIcon = { template: '<span>📝</span>' }

// Props
interface Props {
  formId?: string
  tenantId?: string
  initialConfig?: Partial<FormConfig>
  socketUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
  socketUrl: 'http://localhost:3000'
})

// Emits
const emit = defineEmits<{
  'save': [formData: any]
  'submit': [submission: FormSubmission]
  'change': [changeData: any]
  'error': [error: Error]
  'ready': []
}>()

// Reactive state
const socket: Ref<Socket | null> = ref(null)
const isSocketConnected = ref(false)
const formConfig = ref<FormConfig>({
  id: props.formId || '',
  name: 'New Form',
  description: '',
  submitButtonText: 'Submit',
  successMessage: 'Form submitted successfully!',
  errorMessage: 'There was an error submitting the form.',
  redirectUrl: '',
  theme: 'default',
  layout: 'vertical'
})

const formFields = ref<FormField[]>([])
const selectedField = ref<FormField | null>(null)
const isLoading = ref(false)
const isSaving = ref(false)
const isSubmitting = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const loadingMessage = ref('Loading form builder...')
const isPreviewMode = ref(false)
const isDragOver = ref(false)
const showCRMSettings = ref(false)
const showFormSettings = ref(false)

// CRM Integration state
const crmIntegration = ref<CRMIntegration | null>(null)

// Available field types
const availableFieldTypes = [
  { type: 'text', label: 'Text Field', icon: TextIcon },
  { type: 'email', label: 'Email', icon: EmailIcon },
  { type: 'password', label: 'Password', icon: TextIcon },
  { type: 'textarea', label: 'Text Area', icon: TextareaIcon },
  { type: 'select', label: 'Select Dropdown', icon: SelectIcon },
  { type: 'checkbox', label: 'Checkbox', icon: CheckboxIcon },
  { type: 'radio', label: 'Radio Button', icon: RadioIcon },
  { type: 'date', label: 'Date Picker', icon: TextIcon },
  { type: 'number', label: 'Number', icon: TextIcon },
  { type: 'phone', label: 'Phone Number', icon: TextIcon },
  { type: 'url', label: 'Website URL', icon: TextIcon },
  { type: 'file', label: 'File Upload', icon: TextIcon },
  { type: 'hidden', label: 'Hidden Field', icon: TextIcon }
]

// Computed properties
const formStatusText = computed(() => {
  if (isSaving.value) return 'Saving...'
  if (hasError.value) return 'Error'
  return 'Draft'
})

const formStatusClass = computed(() => {
  if (isSaving.value) return 'status-saving'
  if (hasError.value) return 'status-error'
  return 'status-draft'
})

// Lifecycle
onMounted(async () => {
  try {
    await initializeSocket()
    if (props.formId) {
      await loadForm(props.formId)
    } else if (props.initialConfig) {
      Object.assign(formConfig.value, props.initialConfig)
    }
    emit('ready')
  } catch (error) {
    console.error('Failed to initialize form builder:', error)
    hasError.value = true
    errorMessage.value = error instanceof Error ? error.message : 'Failed to initialize form builder'
    emit('error', error instanceof Error ? error : new Error('Initialization failed'))
  } finally {
    isLoading.value = false
  }
})

onUnmounted(() => {
  if (socket.value) {
    socket.value.disconnect()
  }
})

// Methods
const initializeSocket = async (): Promise<void> => {
  try {
    socket.value = io(props.socketUrl, {
      transports: ['websocket', 'polling'],
      timeout: 5000
    })

    socket.value.on('connect', () => {
      console.log('Connected to real-time server')
      isSocketConnected.value = true

      if (props.formId && props.tenantId) {
        socket.value?.emit('join:form', {
          formId: props.formId,
          tenantId: props.tenantId
        })
      }
    })

    socket.value.on('disconnect', () => {
      console.log('Disconnected from real-time server')
      isSocketConnected.value = false
    })

    socket.value.on('form:change', (changeData: any) => {
      handleRemoteChange(changeData)
    })

    socket.value.on('form:submit', (submissionData: any) => {
      handleRemoteSubmission(submissionData)
    })

  } catch (error) {
    console.error('Failed to initialize socket:', error)
    throw error
  }
}

const loadForm = async (formId: string): Promise<void> => {
  try {
    loadingMessage.value = 'Loading form...'
    const response = await axios.get(`/api/forms/${formId}`)
    const formData = response.data

    formConfig.value = formData.config
    formFields.value = formData.fields || []
    crmIntegration.value = formData.crmIntegration || null

  } catch (error) {
    console.error('Failed to load form:', error)
    throw new Error('Failed to load form')
  }
}

const saveForm = async (): Promise<void> => {
  if (isSaving.value) return

  try {
    isSaving.value = true

    const formData = {
      config: formConfig.value,
      fields: formFields.value,
      crmIntegration: crmIntegration.value
    }

    let response
    if (props.formId) {
      response = await axios.put(`/api/forms/${props.formId}`, formData)
    } else {
      response = await axios.post('/api/forms', formData)
      formConfig.value.id = response.data.id
    }

    emit('save', formData)

    if (socket.value && isSocketConnected.value && props.tenantId) {
      socket.value.emit('form:change', {
        type: 'form_saved',
        formId: formConfig.value.id,
        tenantId: props.tenantId,
        data: formData,
        timestamp: Date.now()
      })
    }

  } catch (error) {
    console.error('Failed to save form:', error)
    hasError.value = true
    errorMessage.value = 'Failed to save form'
    emit('error', error instanceof Error ? error : new Error('Save failed'))
  } finally {
    isSaving.value = false
  }
}

const onDragStart = (event: DragEvent, fieldType: any): void => {
  event.dataTransfer!.setData('application/json', JSON.stringify(fieldType))
  event.dataTransfer!.effectAllowed = 'copy'
}

const onDrop = (event: DragEvent): void => {
  event.preventDefault()
  isDragOver.value = false

  try {
    const fieldTypeData = JSON.parse(event.dataTransfer!.getData('application/json'))
    addField(fieldTypeData.type)
  } catch (error) {
    console.error('Failed to parse dropped field type:', error)
  }
}

const addField = (fieldType: FieldType): void => {
  const newField: FormField = {
    id: generateFieldId(),
    type: fieldType,
    label: getDefaultLabel(fieldType),
    placeholder: '',
    required: false,
    defaultValue: '',
    options: fieldType === 'select' || fieldType === 'radio' ? [{ value: '', label: 'Option 1' }] : undefined,
    validation: {},
    styling: {}
  }

  formFields.value.push(newField)
  broadcastChange('field_added', { field: newField })
}

const updateField = (updatedField: FormField, index: number): void => {
  formFields.value[index] = { ...formFields.value[index], ...updatedField }
  broadcastChange('field_updated', { fieldId: updatedField.id, field: updatedField })
}

const removeField = (index: number): void => {
  const removedField = formFields.value[index]
  formFields.value.splice(index, 1)
  broadcastChange('field_removed', { fieldId: removedField.id })
}

const moveField = (newIndex: number, currentIndex: number): void => {
  const field = formFields.value.splice(currentIndex, 1)[0]
  formFields.value.splice(newIndex, 0, field)
  broadcastChange('field_moved', { fieldId: field.id, fromIndex: currentIndex, toIndex: newIndex })
}

const duplicateField = (index: number): void => {
  const originalField = formFields.value[index]
  const duplicatedField: FormField = {
    ...originalField,
    id: generateFieldId(),
    label: `${originalField.label} (Copy)`
  }

  formFields.value.splice(index + 1, 0, duplicatedField)
  broadcastChange('field_duplicated', { originalFieldId: originalField.id, newField: duplicatedField })
}

const selectField = (field: FormField): void => {
  selectedField.value = field
}

const updateSelectedField = (updatedField: FormField): void => {
  if (selectedField.value) {
    const index = formFields.value.findIndex(f => f.id === selectedField.value!.id)
    if (index !== -1) {
      updateField(updatedField, index)
      selectedField.value = { ...selectedField.value, ...updatedField }
    }
  }
}

const addFieldValidation = (rule: ValidationRule): void => {
  if (selectedField.value) {
    if (!selectedField.value.validation) {
      selectedField.value.validation = {}
    }
    broadcastChange('validation_added', { fieldId: selectedField.value.id, rule })
  }
}

const removeFieldValidation = (ruleId: string): void => {
  if (selectedField.value) {
    broadcastChange('validation_removed', { fieldId: selectedField.value.id, ruleId })
  }
}

const updateFormConfig = (newConfig: Partial<FormConfig>): void => {
  Object.assign(formConfig.value, newConfig)
  broadcastChange('config_updated', { config: newConfig })
}

const handleFormSubmission = async (formData: Record<string, any>): Promise<void> => {
  try {
    isSubmitting.value = true

    const validationErrors = validateFormData(formData)
    if (validationErrors.length > 0) {
      throw new Error('Form validation failed')
    }

    if (crmIntegration.value) {
      await submitToCRM(formData)
    }

    const submission: FormSubmission = {
      formId: formConfig.value.id,
      data: formData,
      submittedAt: new Date(),
      tenantId: props.tenantId
    }

    await axios.post(`/api/forms/${formConfig.value.id}/submissions`, submission)
    emit('submit', submission)

    if (socket.value && isSocketConnected.value && props.tenantId) {
      socket.value.emit('form:submit', {
        formId: formConfig.value.id,
        tenantId: props.tenantId,
        submission,
        timestamp: Date.now()
      })
    }

  } catch (error) {
    console.error('Form submission failed:', error)
    emit('error', error instanceof Error ? error : new Error('Submission failed'))
  } finally {
    isSubmitting.value = false
  }
}

const validateFormData = (formData: Record<string, any>): string[] => {
  const errors: string[] = []

  formFields.value.forEach(field => {
    const value = formData[field.id]

    if (field.required && (!value || value === '')) {
      errors.push(`${field.label} is required`)
    }

    if (value) {
      switch (field.type) {
        case 'email':
          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors.push(`${field.label} must be a valid email address`)
          }
          break
        case 'url':
          try {
            new URL(value)
          } catch {
            errors.push(`${field.label} must be a valid URL`)
          }
          break
        case 'number':
          if (isNaN(Number(value))) {
            errors.push(`${field.label} must be a number`)
          }
          break
      }
    }
  })

  return errors
}

const submitToCRM = async (formData: Record<string, any>): Promise<void> => {
  if (!crmIntegration.value) return

  try {
    const crmData: Record<string, any> = {}
    if (crmIntegration.value.fieldMapping) {
      crmIntegration.value.fieldMapping.forEach(mapping => {
        const formValue = formData[mapping.formFieldId]
        crmData[mapping.crmFieldId] = formValue
      })
    }

    await axios.post('/api/crm/submit', {
      crmType: crmIntegration.value.crmType,
      connectionId: crmIntegration.value.connectionId,
      data: crmData,
      tenantId: props.tenantId
    })

  } catch (error) {
    console.error('CRM submission failed:', error)
    throw new Error('Failed to submit to CRM')
  }
}

const onCRMIntegrationUpdated = (integration: CRMIntegration): void => {
  crmIntegration.value = integration
  broadcastChange('crm_updated', { integration })
}

const broadcastChange = (type: string, data: any): void => {
  const changeData = {
    type,
    formId: formConfig.value.id,
    tenantId: props.tenantId,
    data,
    timestamp: Date.now()
  }

  emit('change', changeData)

  if (socket.value && isSocketConnected.value && props.tenantId) {
    socket.value.emit('form:change', changeData)
  }
}

const handleRemoteChange = (changeData: any): void => {
  if (changeData.formId !== formConfig.value.id) return

  switch (changeData.type) {
    case 'field_added':
      if (changeData.data.field) {
        formFields.value.push(changeData.data.field)
      }
      break
    case 'field_updated':
      const updateIndex = formFields.value.findIndex(f => f.id === changeData.data.fieldId)
      if (updateIndex !== -1 && changeData.data.field) {
        formFields.value[updateIndex] = { ...formFields.value[updateIndex], ...changeData.data.field }
      }
      break
    case 'field_removed':
      const removeIndex = formFields.value.findIndex(f => f.id === changeData.data.fieldId)
      if (removeIndex !== -1) {
        formFields.value.splice(removeIndex, 1)
      }
      break
    case 'config_updated':
      if (changeData.data.config) {
        Object.assign(formConfig.value, changeData.data.config)
      }
      break
  }
}

const handleRemoteSubmission = (submissionData: any): void => {
  if (submissionData.formId === formConfig.value.id) {
    console.log('Remote form submission:', submissionData)
  }
}

const togglePreviewMode = (): void => {
  isPreviewMode.value = !isPreviewMode.value
}

const resetBuilder = (): void => {
  hasError.value = false
  errorMessage.value = ''
  selectedField.value = null
  isDragOver.value = false
}

// Utility functions
const generateFieldId = (): string => {
  return 'field-' + Math.random().toString(36).substr(2, 9)
}

const getDefaultLabel = (fieldType: FieldType): string => {
  const labels: Record<FieldType, string> = {
    text: 'Text Field',
    email: 'Email Address',
    password: 'Password',
    textarea: 'Text Area',
    select: 'Select Field',
    checkbox: 'Checkbox',
    radio: 'Radio Button',
    date: 'Date',
    number: 'Number',
    phone: 'Phone Number',
    url: 'Website URL',
    file: 'File Upload',
    hidden: 'Hidden Field',
    rating: 'Rating',
    signature: 'Signature'
  }

  return labels[fieldType] || 'New Field'
}

// Expose methods for parent components
defineExpose({
  saveForm,
  addField,
  removeField,
  updateField,
  getFormData: () => ({
    config: formConfig.value,
    fields: formFields.value,
    crmIntegration: crmIntegration.value
  })
})
</script>

<style scoped>
.form-builder {
  @apply relative h-full w-full bg-gray-50 dark:bg-gray-900;
}

.form-builder.loading {
  @apply pointer-events-none;
}

.form-builder.error {
  @apply opacity-75;
}

/* Form Builder Header */
.form-builder-header {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between;
}

.header-left {
  @apply flex items-center gap-3;
}

.form-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.form-status {
  @apply px-2 py-1 text-xs font-medium rounded-full;
}

.form-status.status-draft {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.form-status.status-saving {
  @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.form-status.status-error {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.header-right {
  @apply flex items-center gap-2;
}

.preview-btn,
.save-btn {
  @apply flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed;
}

.preview-btn {
  @apply bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600;
}

.save-btn {
  @apply bg-green-600 text-white hover:bg-green-700;
}

/* Form Builder Content */
.form-builder-content {
  @apply flex h-full;
}

/* Field Palette */
.field-palette {
  @apply w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4;
}

.palette-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white mb-4;
}

.field-types {
  @apply space-y-2;
}

.field-type-item {
  @apply flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-move;
  @apply hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none;
}

.field-icon {
  @apply w-5 h-5 text-gray-600 dark:text-gray-400;
}

.field-label {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

/* Form Canvas */
.form-canvas {
  @apply flex-1 flex flex-col;
}

.form-canvas-header {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between;
}

.canvas-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.canvas-actions {
  @apply flex items-center gap-2;
}

.settings-btn,
.crm-btn {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none;
}

/* Form Drop Zone */
.form-drop-zone {
  @apply flex-1 p-6 overflow-y-auto;
  @apply transition-colors;
}

.form-drop-zone.drag-over {
  @apply bg-blue-50 dark:bg-blue-900 border-2 border-dashed border-blue-300 dark:border-blue-600;
}

.empty-state {
  @apply flex flex-col items-center justify-center h-full text-center;
}

.empty-text {
  @apply text-gray-500 dark:text-gray-400 mt-4;
}

.form-fields {
  @apply space-y-4;
}

/* Field Properties Panel */
.field-properties {
  @apply w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col;
}

.properties-header {
  @apply px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between;
}

.properties-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-properties-btn {
  @apply p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded;
}

.properties-content {
  @apply flex-1 p-6 overflow-y-auto;
}

/* Form Preview */
.form-preview {
  @apply flex-1 flex flex-col;
}

.preview-header {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between;
}

.preview-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.exit-preview-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none;
}

.preview-content {
  @apply flex-1 p-6 overflow-y-auto;
}

/* Modals */
.crm-settings-overlay,
.settings-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.crm-settings-panel,
.settings-panel {
  @apply bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden;
}

.crm-header,
.settings-header {
  @apply px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between;
}

.crm-title,
.settings-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-crm-btn,
.close-settings-btn {
  @apply p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded;
}

.crm-content,
.settings-content {
  @apply p-6 overflow-y-auto max-h-[calc(90vh-80px)];
}

/* Loading and Error Overlays */
.loading-overlay,
.error-overlay {
  @apply absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 flex items-center justify-center z-40;
}

.loading-content,
.error-content {
  @apply flex flex-col items-center gap-4 text-center;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin;
}

.error-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.error-message {
  @apply text-gray-600 dark:text-gray-400;
}

.error-btn {
  @apply px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors;
  @apply focus:ring-2 focus:ring-red-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .form-builder-content {
    @apply flex-col;
  }

  .field-palette,
  .field-properties {
    @apply w-full;
  }

  .form-canvas {
    @apply order-first;
  }

  .field-types {
    @apply flex flex-wrap gap-2;
  }

  .field-type-item {
    @apply flex-col text-center p-2;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .form-builder {
    @apply border-2 border-gray-900 dark:border-white;
  }

  .field-type-item,
  .preview-btn,
  .save-btn,
  .settings-btn,
  .crm-btn {
    @apply border-2;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    @apply animate-none;
  }

  .preview-btn,
  .save-btn,
  .exit-preview-btn,
  .error-btn {
    @apply transition-none;
  }
}

/* Focus Management */
.form-builder :focus-visible {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800;
}
</style>