<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Add New Integration
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="createIntegration" class="space-y-6">
          <!-- Step 1: Select Integration Type -->
          <div v-if="step === 1">
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
              Select Integration Type
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="(typeConfig, type) in integrationTypes"
                :key="type"
                @click="selectType(type)"
                :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                  form.type === type
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <div class="flex items-center">
                  <component
                    :is="getIcon(typeConfig.icon)"
                    class="w-8 h-8 text-blue-600 mr-3"
                  />
                  <div>
                    <h5 class="font-medium text-gray-900 dark:text-white">
                      {{ typeConfig.label }}
                    </h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ typeConfig.description }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Select Provider -->
          <div v-if="step === 2">
            <div class="flex items-center mb-4">
              <button
                type="button"
                @click="step = 1"
                class="text-blue-600 hover:text-blue-700 mr-3"
              >
                <ArrowLeftIcon class="w-5 h-5" />
              </button>
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                Select {{ integrationTypes[form.type]?.label }} Provider
              </h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="(providerName, provider) in integrationTypes[form.type]?.providers"
                :key="provider"
                @click="selectProvider(provider)"
                :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                  form.provider === provider
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <div class="text-center">
                  <h5 class="font-medium text-gray-900 dark:text-white">
                    {{ providerName }}
                  </h5>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ provider }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Configuration -->
          <div v-if="step === 3">
            <div class="flex items-center mb-4">
              <button
                type="button"
                @click="step = 2"
                class="text-blue-600 hover:text-blue-700 mr-3"
              >
                <ArrowLeftIcon class="w-5 h-5" />
              </button>
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                Configure {{ integrationTypes[form.type]?.providers[form.provider] }}
              </h4>
            </div>

            <div class="space-y-4">
              <!-- Basic Information -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Integration Name
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  :placeholder="`${integrationTypes[form.type]?.providers[form.provider]} Integration`"
                />
              </div>

              <!-- Provider-specific Configuration -->
              <div v-if="providerConfig" class="space-y-4">
                <h5 class="font-medium text-gray-900 dark:text-white">
                  Connection Settings
                </h5>
                
                <div
                  v-for="(fieldConfig, fieldName) in providerConfig.fields"
                  :key="fieldName"
                  class="space-y-2"
                >
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ fieldConfig.label }}
                    <span v-if="fieldConfig.required" class="text-red-500">*</span>
                  </label>
                  
                  <input
                    v-if="fieldConfig.type === 'text' || fieldConfig.type === 'password'"
                    v-model="form.configuration[fieldName]"
                    :type="fieldConfig.type"
                    :required="fieldConfig.required"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  />
                  
                  <input
                    v-else-if="fieldConfig.type === 'url'"
                    v-model="form.configuration[fieldName]"
                    type="url"
                    :required="fieldConfig.required"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  />
                  
                  <textarea
                    v-else-if="fieldConfig.type === 'textarea'"
                    v-model="form.configuration[fieldName]"
                    :required="fieldConfig.required"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  ></textarea>
                </div>
              </div>

              <!-- Options -->
              <div class="space-y-4">
                <h5 class="font-medium text-gray-900 dark:text-white">
                  Options
                </h5>
                
                <div class="flex items-center">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    id="is_active"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Enable this integration
                  </label>
                </div>
                
                <div class="flex items-center">
                  <input
                    v-model="form.is_test_mode"
                    type="checkbox"
                    id="is_test_mode"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label for="is_test_mode" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Test mode (safe for testing without affecting live data)
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
              <span>Step {{ step }} of 3</span>
              <div class="flex space-x-1">
                <div
                  v-for="i in 3"
                  :key="i"
                  :class="[
                    'w-2 h-2 rounded-full',
                    i <= step ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'
                  ]"
                ></div>
              </div>
            </div>
            
            <div class="flex items-center space-x-3">
              <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                Cancel
              </button>
              
              <button
                v-if="step < 3"
                type="button"
                @click="nextStep"
                :disabled="!canProceed"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
              
              <button
                v-else
                type="submit"
                :disabled="processing || !canProceed"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="processing">Creating...</span>
                <span v-else>Create Integration</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  XMarkIcon,
  ArrowLeftIcon,
  EnvelopeIcon,
  CalendarIcon,
  ShieldCheckIcon,
  UsersIcon,
  CreditCardIcon,
  ChartBarIcon,
  CogIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  integrationTypes: Object,
  selectedType: String,
})

const emit = defineEmits(['close', 'created'])

const step = ref(1)
const processing = ref(false)

const form = ref({
  name: '',
  type: props.selectedType || '',
  provider: '',
  configuration: {},
  is_active: true,
  is_test_mode: true,
})

const iconComponents = {
  mail: MailIcon,
  calendar: CalendarIcon,
  'shield-check': ShieldCheckIcon,
  users: UsersIcon,
  'credit-card': CreditCardIcon,
  'chart-bar': ChartBarIcon,
  cog: CogIcon,
}

const getIcon = (iconName) => {
  return iconComponents[iconName] || CogIcon
}

const providerConfig = computed(() => {
  if (!form.value.type || !form.value.provider) return null
  
  // This would come from the backend in a real implementation
  const configs = {
    email_marketing: {
      mailchimp: {
        fields: {
          api_key: { type: 'password', label: 'API Key', required: true },
          server_prefix: { type: 'text', label: 'Server Prefix', required: true },
          list_id: { type: 'text', label: 'Default List ID', required: false },
        }
      },
      constant_contact: {
        fields: {
          api_key: { type: 'password', label: 'API Key', required: true },
          access_token: { type: 'password', label: 'Access Token', required: true },
        }
      }
    },
    calendar: {
      google: {
        fields: {
          client_id: { type: 'text', label: 'Client ID', required: true },
          client_secret: { type: 'password', label: 'Client Secret', required: true },
        }
      },
      outlook: {
        fields: {
          client_id: { type: 'text', label: 'Application ID', required: true },
          client_secret: { type: 'password', label: 'Client Secret', required: true },
          tenant_id: { type: 'text', label: 'Tenant ID', required: true },
        }
      }
    },
    sso: {
      saml2: {
        fields: {
          entity_id: { type: 'text', label: 'Entity ID', required: true },
          sso_url: { type: 'url', label: 'SSO URL', required: true },
          certificate: { type: 'textarea', label: 'X.509 Certificate', required: true },
        }
      },
      oidc: {
        fields: {
          client_id: { type: 'text', label: 'Client ID', required: true },
          client_secret: { type: 'password', label: 'Client Secret', required: true },
          discovery_url: { type: 'url', label: 'Discovery URL', required: true },
        }
      }
    }
  }
  
  return configs[form.value.type]?.[form.value.provider] || null
})

const canProceed = computed(() => {
  if (step.value === 1) return !!form.value.type
  if (step.value === 2) return !!form.value.provider
  if (step.value === 3) {
    if (!form.value.name) return false
    
    if (providerConfig.value) {
      for (const [fieldName, fieldConfig] of Object.entries(providerConfig.value.fields)) {
        if (fieldConfig.required && !form.value.configuration[fieldName]) {
          return false
        }
      }
    }
    
    return true
  }
  return false
})

// Initialize step based on selectedType
watch(() => props.selectedType, (newType) => {
  if (newType) {
    form.value.type = newType
    step.value = 2
  }
}, { immediate: true })

const selectType = (type) => {
  form.value.type = type
  form.value.provider = ''
  form.value.configuration = {}
}

const selectProvider = (provider) => {
  form.value.provider = provider
  form.value.configuration = {}
  
  // Set default name
  const typeName = props.integrationTypes[form.value.type]?.label || form.value.type
  const providerName = props.integrationTypes[form.value.type]?.providers[provider] || provider
  form.value.name = `${providerName} ${typeName}`
}

const nextStep = () => {
  if (canProceed.value && step.value < 3) {
    step.value++
  }
}

const createIntegration = async () => {
  if (!canProceed.value || processing.value) return
  
  processing.value = true
  
  try {
    router.post(route('admin.integrations.store'), form.value, {
      onSuccess: () => {
        emit('created')
      },
      onError: (errors) => {
        console.error('Integration creation failed:', errors)
      },
      onFinish: () => {
        processing.value = false
      }
    })
  } catch (error) {
    console.error('Integration creation failed:', error)
    processing.value = false
  }
}
</script>