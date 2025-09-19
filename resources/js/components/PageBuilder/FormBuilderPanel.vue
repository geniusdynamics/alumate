<template>
  <div class="form-builder-panel bg-white border-l border-gray-200 w-80 h-full overflow-y-auto">
    <div class="p-4 border-b border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900">Form Builder</h3>
      <p class="text-sm text-gray-600 mt-1">Create and configure forms with CRM integration</p>
    </div>

    <!-- Form Configuration -->
    <div class="p-4 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Form Name</label>
        <input
          v-model="formConfig.name"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter form name"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
        <textarea
          v-model="formConfig.description"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Form description"
        ></textarea>
      </div>

      <!-- CRM Integration Toggle -->
      <div class="border-t pt-4">
        <div class="flex items-center justify-between mb-3">
          <label class="text-sm font-medium text-gray-700">CRM Integration</label>
          <button
            @click="toggleCrmIntegration"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
              formConfig.crm_integration_config.enabled ? 'bg-blue-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                formConfig.crm_integration_config.enabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>

        <div v-if="formConfig.crm_integration_config.enabled" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">CRM Provider</label>
            <select
              v-model="formConfig.crm_integration_config.provider"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select Provider</option>
              <option value="salesforce">Salesforce</option>
              <option value="hubspot">HubSpot</option>
              <option value="pipedrive">Pipedrive</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Field Types Palette -->
    <div class="border-t border-gray-200">
      <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Field Types</h4>
        <div class="grid grid-cols-2 gap-2">
          <button
            v-for="(fieldType, key) in fieldTypes"
            :key="key"
            @click="addField(key, fieldType)"
            class="p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors text-left"
          >
            <div class="flex items-center space-x-2">
              <Icon :name="fieldType.icon" class="w-4 h-4 text-gray-600" />
              <span class="text-xs font-medium text-gray-700">{{ fieldType.label }}</span>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Icon } from '@/components/ui'
import { useFormBuilder } from '@/composables/useFormBuilder'

interface FormConfig {
  name: string
  description: string
  crm_integration_config: {
    enabled: boolean
    provider: string
    field_mappings: Record<string, string>
  }
}

const { fieldTypes, loadFieldTypes, createForm } = useFormBuilder()

const formConfig = reactive<FormConfig>({
  name: '',
  description: '',
  crm_integration_config: {
    enabled: false,
    provider: '',
    field_mappings: {}
  }
})

const emit = defineEmits<{
  fieldAdded: [fieldType: string, fieldConfig: any]
  formConfigChanged: [config: FormConfig]
}>()

onMounted(async () => {
  await loadFieldTypes()
})

const toggleCrmIntegration = () => {
  formConfig.crm_integration_config.enabled = !formConfig.crm_integration_config.enabled
  if (!formConfig.crm_integration_config.enabled) {
    formConfig.crm_integration_config.provider = ''
    formConfig.crm_integration_config.field_mappings = {}
  }
  emit('formConfigChanged', formConfig)
}

const addField = (fieldType: string, fieldConfig: any) => {
  emit('fieldAdded', fieldType, fieldConfig)
}
</script>