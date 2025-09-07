<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Integration Test Results
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="space-y-4">
          <!-- Success Result -->
          <div v-if="results.success" class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="w-6 h-6 text-green-500" />
            </div>
            <div>
              <h4 class="text-sm font-medium text-green-800 dark:text-green-400">
                Connection Successful
              </h4>
              <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                {{ results.message || 'The integration is working correctly.' }}
              </p>
            </div>
          </div>

          <!-- Error Result -->
          <div v-else class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <XCircleIcon class="w-6 h-6 text-red-500" />
            </div>
            <div>
              <h4 class="text-sm font-medium text-red-800 dark:text-red-400">
                Connection Failed
              </h4>
              <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                {{ results.error || 'The integration test failed.' }}
              </p>
            </div>
          </div>

          <!-- Warning -->
          <div v-if="results.warning" class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="w-6 h-6 text-yellow-500" />
            </div>
            <div>
              <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-400">
                Warning
              </h4>
              <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                {{ results.warning }}
              </p>
            </div>
          </div>

          <!-- Additional Data -->
          <div v-if="results.data && Object.keys(results.data).length > 0" class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
              Connection Details
            </h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
              <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ JSON.stringify(results.data, null, 2) }}</pre>
            </div>
          </div>

          <!-- Discovery Data for OIDC -->
          <div v-if="results.discovery_data" class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
              OIDC Discovery Information
            </h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3 space-y-2">
              <div v-if="results.discovery_data.issuer" class="text-xs">
                <span class="font-medium text-gray-600 dark:text-gray-400">Issuer:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ results.discovery_data.issuer }}</span>
              </div>
              <div v-if="results.discovery_data.authorization_endpoint" class="text-xs">
                <span class="font-medium text-gray-600 dark:text-gray-400">Authorization Endpoint:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ results.discovery_data.authorization_endpoint }}</span>
              </div>
              <div v-if="results.discovery_data.token_endpoint" class="text-xs">
                <span class="font-medium text-gray-600 dark:text-gray-400">Token Endpoint:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ results.discovery_data.token_endpoint }}</span>
              </div>
            </div>
          </div>

          <!-- Account Info for APIs -->
          <div v-if="results.account_info" class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
              Account Information
            </h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3 space-y-2">
              <div v-if="results.account_info.name" class="text-xs">
                <span class="font-medium text-gray-600 dark:text-gray-400">Account Name:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ results.account_info.name }}</span>
              </div>
              <div v-if="results.account_info.email" class="text-xs">
                <span class="font-medium text-gray-600 dark:text-gray-400">Email:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ results.account_info.email }}</span>
              </div>
              <div v-if="results.account_info.id" class="text-xs">
                <span class="font-medium text-gray-600 dark:text-gray-400">Account ID:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ results.account_info.id }}</span>
              </div>
            </div>
          </div>

          <!-- API Versions for Salesforce -->
          <div v-if="results.versions && Array.isArray(results.versions)" class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
              Available API Versions
            </h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
              <div class="grid grid-cols-2 gap-2">
                <div
                  v-for="version in results.versions.slice(0, 6)"
                  :key="version.version"
                  class="text-xs text-gray-700 dark:text-gray-300"
                >
                  v{{ version.version }}
                </div>
              </div>
              <div v-if="results.versions.length > 6" class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                ... and {{ results.versions.length - 6 }} more versions
              </div>
            </div>
          </div>

          <!-- SSO URL Accessibility -->
          <div v-if="results.hasOwnProperty('sso_url_accessible')" class="mt-4">
            <div class="flex items-center space-x-2">
              <component
                :is="results.sso_url_accessible ? CheckCircleIcon : XCircleIcon"
                :class="[
                  'w-5 h-5',
                  results.sso_url_accessible ? 'text-green-500' : 'text-red-500'
                ]"
              />
              <span class="text-sm text-gray-700 dark:text-gray-300">
                SSO URL {{ results.sso_url_accessible ? 'is accessible' : 'is not accessible' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  XMarkIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

defineProps({
  results: {
    type: Object,
    required: true,
  },
})

defineEmits(['close'])
</script>