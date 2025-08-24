<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Link
            :href="route('admin.integrations.index')"
            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ integration.name }}
            </h1>
            <div class="flex items-center space-x-4 mt-1">
              <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ getTypeLabel(integration.type) }} • {{ integration.provider }}
              </span>
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  integration.is_active
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                ]"
              >
                {{ integration.is_active ? 'Active' : 'Inactive' }}
              </span>
              <span
                v-if="integration.is_test_mode"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
              >
                Test Mode
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <button
            @click="testIntegration"
            :disabled="testing"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
          >
            <span v-if="testing">
              <ArrowPathIcon class="w-4 h-4 mr-2 animate-spin" />
              Testing...
            </span>
            <span v-else>
              <PlayIcon class="w-4 h-4 mr-2" />
              Test Connection
            </span>
          </button>

          <button
            v-if="canSync"
            @click="syncIntegration"
            :disabled="syncing"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
          >
            <span v-if="syncing">
              <ArrowPathIcon class="w-4 h-4 mr-2 animate-spin" />
              Syncing...
            </span>
            <span v-else>
              <ArrowPathIcon class="w-4 h-4 mr-2" />
              Sync Now
            </span>
          </button>

          <Link
            :href="route('admin.integrations.edit', integration.id)"
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
          >
            <PencilIcon class="w-4 h-4 mr-2" />
            Edit
          </Link>
        </div>
      </div>

      <!-- Status Overview -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center',
                  integration.is_valid
                    ? 'bg-green-100 dark:bg-green-900/20'
                    : 'bg-red-100 dark:bg-red-900/20'
                ]"
              >
                <component
                  :is="integration.is_valid ? CheckCircleIcon : XCircleIcon"
                  :class="[
                    'w-5 h-5',
                    integration.is_valid ? 'text-green-600' : 'text-red-600'
                  ]"
                />
              </div>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Configuration
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ integration.is_valid ? 'Valid' : 'Invalid' }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center',
                  getSyncStatusColor()
                ]"
              >
                <component
                  :is="getSyncStatusIcon()"
                  class="w-5 h-5 text-white"
                />
              </div>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Sync Status
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ getSyncStatusText() }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <ClockIcon class="w-5 h-5 text-blue-600" />
              </div>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Last Sync
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ integration.last_sync_at || 'Never' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Configuration Issues -->
      <div
        v-if="integration.validation_errors.length > 0"
        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6"
      >
        <div class="flex">
          <ExclamationTriangleIcon class="w-6 h-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
          <div>
            <h3 class="text-lg font-medium text-red-800 dark:text-red-400 mb-2">
              Configuration Issues
            </h3>
            <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
              <li v-for="error in integration.validation_errors" :key="error">
                {{ error }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Configuration Details -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
              Basic Information
            </h2>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Name
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ integration.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Type
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ getTypeLabel(integration.type) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Provider
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ integration.provider }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Created By
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ integration.created_by }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Created At
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ integration.created_at }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Last Updated
              </label>
              <p class="text-sm text-gray-900 dark:text-white">
                {{ integration.updated_at }}
                <span v-if="integration.updated_by" class="text-gray-600 dark:text-gray-400">
                  by {{ integration.updated_by }}
                </span>
              </p>
            </div>
          </div>
        </div>

        <!-- Connection Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
              Connection Settings
            </h2>
          </div>
          <div class="p-6">
            <div v-if="integration.configuration && Object.keys(integration.configuration).length > 0" class="space-y-4">
              <div
                v-for="(value, key) in integration.configuration"
                :key="key"
                class="space-y-1"
              >
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ formatConfigKey(key) }}
                </label>
                <div class="text-sm text-gray-900 dark:text-white">
                  <span v-if="isSecretField(key)" class="text-gray-500 dark:text-gray-400">
                    ••••••••••••
                  </span>
                  <span v-else-if="typeof value === 'boolean'">
                    {{ value ? 'Yes' : 'No' }}
                  </span>
                  <span v-else-if="Array.isArray(value)">
                    {{ value.join(', ') }}
                  </span>
                  <span v-else>
                    {{ value }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500 dark:text-gray-400">
              No configuration settings available
            </div>
          </div>
        </div>
      </div>

      <!-- Webhook Settings -->
      <div
        v-if="integration.webhook_settings"
        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
      >
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            Webhook Settings
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="integration.webhook_url">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Webhook URL
            </label>
            <div class="flex items-center space-x-2">
              <code class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-sm rounded-md">
                {{ integration.webhook_url }}
              </code>
              <button
                @click="copyWebhookUrl"
                class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                Copy
              </button>
            </div>
          </div>
          <div v-else>
            <button
              @click="generateWebhookToken"
              :disabled="generatingToken"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md hover:bg-blue-100 dark:hover:bg-blue-900/30 disabled:opacity-50"
            >
              <span v-if="generatingToken">
                <ArrowPathIcon class="w-4 h-4 mr-2 animate-spin" />
                Generating...
              </span>
              <span v-else>
                <KeyIcon class="w-4 h-4 mr-2" />
                Generate Webhook URL
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Field Mappings -->
      <div
        v-if="integration.field_mappings && Object.keys(integration.field_mappings).length > 0"
        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
      >
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            Field Mappings
          </h2>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="(internalField, externalField) in integration.field_mappings"
              :key="externalField"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
            >
              <span class="text-sm font-medium text-gray-900 dark:text-white">
                {{ externalField }}
              </span>
              <ArrowRightIcon class="w-4 h-4 text-gray-400 mx-2" />
              <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ internalField }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sync Settings -->
      <div
        v-if="integration.sync_settings && Object.keys(integration.sync_settings).length > 0"
        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
      >
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            Sync Settings
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <div
            v-for="(value, key) in integration.sync_settings"
            :key="key"
            class="space-y-1"
          >
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ formatConfigKey(key) }}
            </label>
            <p class="text-sm text-gray-900 dark:text-white">
              <span v-if="typeof value === 'boolean'">
                {{ value ? 'Yes' : 'No' }}
              </span>
              <span v-else-if="Array.isArray(value)">
                {{ value.join(', ') }}
              </span>
              <span v-else>
                {{ value }}
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Results Modal -->
    <TestResultsModal
      v-if="showTestResults"
      :results="testResults"
      @close="showTestResults = false"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import TestResultsModal from '@/Components/Admin/Integrations/TestResultsModal.vue'
import {
  ArrowLeftIcon,
  PlayIcon,
  PencilIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  KeyIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  integration: Object,
  providerConfig: Object,
})

const testing = ref(false)
const syncing = ref(false)
const generatingToken = ref(false)
const showTestResults = ref(false)
const testResults = ref(null)

const integrationTypeLabels = {
  email_marketing: 'Email Marketing',
  calendar: 'Calendar Integration',
  sso: 'Single Sign-On',
  crm: 'CRM Integration',
  payment: 'Payment Processing',
  analytics: 'Analytics',
  webhook: 'Webhook',
}

const canSync = computed(() => {
  return ['email_marketing', 'calendar', 'crm'].includes(props.integration.type)
})

const getTypeLabel = (type) => {
  return integrationTypeLabels[type] || type
}

const getSyncStatusColor = () => {
  if (!props.integration.sync_status) return 'bg-gray-500'
  if (props.integration.sync_status === 'success') return 'bg-green-500'
  if (props.integration.sync_status === 'failed') return 'bg-red-500'
  return 'bg-yellow-500'
}

const getSyncStatusIcon = () => {
  if (!props.integration.sync_status) return ClockIcon
  if (props.integration.sync_status === 'success') return CheckCircleIcon
  if (props.integration.sync_status === 'failed') return XCircleIcon
  return ClockIcon
}

const getSyncStatusText = () => {
  if (!props.integration.sync_status) return 'Not Synced'
  if (props.integration.sync_status === 'success') return 'Healthy'
  if (props.integration.sync_status === 'failed') return 'Failed'
  return 'Pending'
}

const formatConfigKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const isSecretField = (key) => {
  const secretFields = ['password', 'secret', 'key', 'token', 'api_key', 'client_secret']
  return secretFields.some(field => key.toLowerCase().includes(field))
}

const testIntegration = async () => {
  testing.value = true
  
  try {
    const response = await fetch(route('admin.integrations.test', props.integration.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    })
    
    const result = await response.json()
    testResults.value = result
    showTestResults.value = true
    
    // Refresh the page to show updated status
    router.reload({ only: ['integration'] })
  } catch (error) {
    console.error('Test failed:', error)
  } finally {
    testing.value = false
  }
}

const syncIntegration = async () => {
  syncing.value = true
  
  try {
    const response = await fetch(route('admin.integrations.sync', props.integration.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    })
    
    const result = await response.json()
    
    if (result.success) {
      // Show success message or update UI
      router.reload({ only: ['integration'] })
    } else {
      console.error('Sync failed:', result.error)
    }
  } catch (error) {
    console.error('Sync failed:', error)
  } finally {
    syncing.value = false
  }
}

const generateWebhookToken = async () => {
  generatingToken.value = true
  
  try {
    const response = await fetch(route('admin.integrations.webhook-token', props.integration.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    })
    
    const result = await response.json()
    
    if (result.success) {
      router.reload({ only: ['integration'] })
    }
  } catch (error) {
    console.error('Token generation failed:', error)
  } finally {
    generatingToken.value = false
  }
}

const copyWebhookUrl = async () => {
  try {
    await navigator.clipboard.writeText(props.integration.webhook_url)
    // Show success message
  } catch (error) {
    console.error('Failed to copy URL:', error)
  }
}
</script>