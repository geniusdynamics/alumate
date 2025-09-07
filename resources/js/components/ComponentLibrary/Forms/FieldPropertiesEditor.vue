<template>
  <div class="space-y-4">
    <!-- Basic Properties -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Field Label
      </label>
      <input
        v-model="localField.label"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="emitUpdate"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Field Name
      </label>
      <input
        v-model="localField.name"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="emitUpdate"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Placeholder
      </label>
      <input
        v-model="localField.placeholder"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="emitUpdate"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Help Text
      </label>
      <textarea
        v-model="localField.helpText"
        rows="2"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="emitUpdate"
      />
    </div>

    <!-- Field Options -->
    <div class="space-y-3">
      <div class="flex items-center">
        <input
          v-model="localField.required"
          type="checkbox"
          :id="`required-${localField.id}`"
          class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500"
          @change="emitUpdate"
        />
        <label
          :for="`required-${localField.id}`"
          class="ml-2 text-sm text-gray-700 dark:text-gray-300"
        >
          Required field
        </label>
      </div>

      <div class="flex items-center">
        <input
          v-model="localField.disabled"
          type="checkbox"
          :id="`disabled-${localField.id}`"
          class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500"
          @change="emitUpdate"
        />
        <label
          :for="`disabled-${localField.id}`"
          class="ml-2 text-sm text-gray-700 dark:text-gray-300"
        >
          Disabled
        </label>
      </div>

      <div class="flex items-center">
        <input
          v-model="localField.readonly"
          type="checkbox"
          :id="`readonly-${localField.id}`"
          class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500"
          @change="emitUpdate"
        />
        <label
          :for="`readonly-${localField.id}`"
          class="ml-2 text-sm text-gray-700 dark:text-gray-300"
        >
          Read only
        </label>
      </div>
    </div>

    <!-- Field Width -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Field Width
      </label>
      <select
        v-model="localField.width"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="emitUpdate"
      >
        <option value="full">Full Width</option>
        <option value="half">Half Width</option>
        <option value="third">One Third</option>
        <option value="quarter">One Quarter</option>
      </select>
    </div>

    <!-- Type-specific Properties -->
    
    <!-- Textarea Rows -->
    <div v-if="localField.type === 'textarea'">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Rows
      </label>
      <input
        v-model.number="localField.rows"
        type="number"
        min="2"
        max="20"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="emitUpdate"
      />
    </div>

    <!-- Number Field Properties -->
    <div v-if="localField.type === 'number'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Minimum Value
        </label>
        <input
          v-model.number="localField.min"
          type="number"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          @input="emitUpdate"
        />
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Maximum Value
        </label>
        <input
          v-model.number="localField.max"
          type="number"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          @input="emitUpdate"
        />
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Step
        </label>
        <input
          v-model.number="localField.step"
          type="number"
          min="0.01"
          step="0.01"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          @input="emitUpdate"
        />
      </div>
    </div>

    <!-- Text Pattern -->
    <div v-if="localField.type === 'text'">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Pattern (RegEx)
      </label>
      <input
        v-model="localField.pattern"
        type="text"
        placeholder="e.g., [A-Za-z0-9]+"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="emitUpdate"
      />
    </div>

    <!-- Select/Radio Options -->
    <div v-if="localField.type === 'select' || localField.type === 'radio'" class="space-y-3">
      <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Options
        </label>
        <button
          type="button"
          class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 focus:outline-none focus:ring-2 focus:ring-blue-500"
          @click="addOption"
        >
          Add Option
        </button>
      </div>
      
      <div class="space-y-2">
        <div
          v-for="(option, index) in localField.options"
          :key="index"
          class="flex items-center space-x-2"
        >
          <input
            v-model="option.label"
            type="text"
            placeholder="Option label"
            class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            @input="emitUpdate"
          />
          <input
            v-model="option.value"
            type="text"
            placeholder="Value"
            class="w-20 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            @input="emitUpdate"
          />
          <button
            type="button"
            class="p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
            @click="removeOption(index)"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Multiple Selection for Select -->
      <div v-if="localField.type === 'select'" class="flex items-center">
        <input
          v-model="localField.multiple"
          type="checkbox"
          :id="`multiple-${localField.id}`"
          class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500"
          @change="emitUpdate"
        />
        <label
          :for="`multiple-${localField.id}`"
          class="ml-2 text-sm text-gray-700 dark:text-gray-300"
        >
          Allow multiple selections
        </label>
      </div>
    </div>

    <!-- Validation Rules -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Validation Rules
        </label>
        <button
          type="button"
          class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded hover:bg-green-200 dark:hover:bg-green-900/50 focus:outline-none focus:ring-2 focus:ring-green-500"
          @click="addValidationRule"
        >
          Add Rule
        </button>
      </div>
      
      <div class="space-y-2">
        <div
          v-for="(rule, index) in localField.validation"
          :key="index"
          class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-700 rounded"
        >
          <select
            v-model="rule.rule"
            class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            @change="emitUpdate"
          >
            <option value="required">Required</option>
            <option value="email">Email</option>
            <option value="phone">Phone</option>
            <option value="url">URL</option>
            <option value="min">Minimum</option>
            <option value="max">Maximum</option>
            <option value="minLength">Min Length</option>
            <option value="maxLength">Max Length</option>
            <option value="pattern">Pattern</option>
          </select>
          
          <input
            v-if="needsValue(rule.rule)"
            v-model="rule.value"
            type="text"
            placeholder="Value"
            class="w-20 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            @input="emitUpdate"
          />
          
          <input
            v-model="rule.message"
            type="text"
            placeholder="Error message"
            class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            @input="emitUpdate"
          />
          
          <button
            type="button"
            class="p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
            @click="removeValidationRule(index)"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Accessibility -->
    <div class="space-y-3">
      <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Accessibility
      </h5>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          ARIA Label
        </label>
        <input
          v-model="localField.ariaLabel"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          @input="emitUpdate"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FormField, FormValidationConfig, FormFieldOption } from '@/types/components'

interface Props {
  field: FormField
}

interface Emits {
  (e: 'update:field', field: FormField): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local copy of the field for editing
const localField = ref<FormField>({ ...props.field })

// Initialize arrays if they don't exist
if (!localField.value.options) {
  localField.value.options = []
}
if (!localField.value.validation) {
  localField.value.validation = []
}

// Watch for prop changes
watch(
  () => props.field,
  (newField) => {
    localField.value = { ...newField }
    if (!localField.value.options) {
      localField.value.options = []
    }
    if (!localField.value.validation) {
      localField.value.validation = []
    }
  },
  { deep: true }
)

// Methods
const emitUpdate = () => {
  emit('update:field', { ...localField.value })
}

const addOption = () => {
  if (!localField.value.options) {
    localField.value.options = []
  }
  
  localField.value.options.push({
    label: `Option ${localField.value.options.length + 1}`,
    value: `option${localField.value.options.length + 1}`
  })
  
  emitUpdate()
}

const removeOption = (index: number) => {
  if (localField.value.options) {
    localField.value.options.splice(index, 1)
    emitUpdate()
  }
}

const addValidationRule = () => {
  if (!localField.value.validation) {
    localField.value.validation = []
  }
  
  localField.value.validation.push({
    rule: 'required',
    message: 'This field is required'
  })
  
  emitUpdate()
}

const removeValidationRule = (index: number) => {
  if (localField.value.validation) {
    localField.value.validation.splice(index, 1)
    emitUpdate()
  }
}

const needsValue = (rule: string): boolean => {
  return ['min', 'max', 'minLength', 'maxLength', 'pattern'].includes(rule)
}
</script>

<style scoped>
/* Custom scrollbar for better UX */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Dark mode scrollbar */
.dark ::-webkit-scrollbar-track {
  background: #374151;
}

.dark ::-webkit-scrollbar-thumb {
  background: #6b7280;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>