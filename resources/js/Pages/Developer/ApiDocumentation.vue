<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              API Documentation
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              Comprehensive guide to the Alumni Platform API v1.0 - Complete with examples, SDKs, and testing tools
            </p>
          </div>
          <div class="flex items-center space-x-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
              <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
              API Online
            </span>
            <button
              @click="toggleTheme"
              class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"
            >
              <SunIcon v-if="isDark" class="w-5 h-5" />
              <MoonIcon v-else class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex gap-8">
        <!-- Sidebar Navigation -->
        <div class="w-64 flex-shrink-0">
          <nav class="sticky top-8 space-y-1">
            <div
              v-for="section in sections"
              :key="section.id"
              class="space-y-1"
            >
              <button
                @click="scrollToSection(section.id)"
                :class="[
                  'w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                  activeSection === section.id
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                ]"
              >
                {{ section.title }}
              </button>
              <div v-if="section.subsections" class="ml-4 space-y-1">
                <button
                  v-for="subsection in section.subsections"
                  :key="subsection.id"
                  @click="scrollToSection(subsection.id)"
                  :class="[
                    'w-full text-left px-3 py-1 rounded text-xs transition-colors',
                    activeSection === subsection.id
                      ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300'
                      : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50'
                  ]"
                >
                  {{ subsection.title }}
                </button>
              </div>
            </div>
          </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 max-w-none">
          <!-- Quick Start -->
          <section id="quick-start" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              Quick Start
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Authentication
              </h3>
              <p class="text-gray-600 dark:text-gray-400 mb-4">
                All API requests require authentication using Bearer tokens.
              </p>
              
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Example Request
                  </span>
                  <button
                    @click="copyToClipboard(authExample)"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                  >
                    Copy
                  </button>
                </div>
                <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ authExample }}</code></pre>
              </div>
            </div>

            <!-- API Key Management -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                API Key Management
              </h3>
              
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Generate New API Key
                  </label>
                  <div class="flex gap-3">
                    <input
                      v-model="newKeyName"
                      type="text"
                      placeholder="API Key Name"
                      class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
                    />
                    <button
                      @click="generateApiKey"
                      :disabled="!newKeyName || generatingKey"
                      class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span v-if="generatingKey">Generating...</span>
                      <span v-else>Generate</span>
                    </button>
                  </div>
                </div>

                <!-- Generated API Key -->
                <div v-if="generatedKey" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-green-800 dark:text-green-200">
                      New API Key Generated
                    </span>
                    <button
                      @click="copyToClipboard(generatedKey)"
                      class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200"
                    >
                      Copy Key
                    </button>
                  </div>
                  <code class="text-sm text-green-700 dark:text-green-300 break-all">{{ generatedKey }}</code>
                  <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                    ⚠️ Save this key securely. It won't be shown again.
                  </p>
                </div>

                <!-- Existing API Keys -->
                <div v-if="apiKeys.length > 0">
                  <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Existing API Keys
                  </h4>
                  <div class="space-y-2">
                    <div
                      v-for="key in apiKeys"
                      :key="key.id"
                      class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                    >
                      <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ key.name }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          Created {{ formatDate(key.created_at) }} • Last used {{ formatDate(key.last_used_at) }}
                        </div>
                      </div>
                      <button
                        @click="revokeApiKey(key.id)"
                        class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200"
                      >
                        Revoke
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- API Tester -->
          <section id="api-tester" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              API Tester
            </h2>
            <ApiTester />
          </section>

          <!-- Endpoints -->
          <section id="endpoints" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              API Endpoints
            </h2>

            <!-- Endpoint Categories -->
            <div class="space-y-8">
              <div
                v-for="(category, categoryId) in apiEndpointsData"
                :key="categoryId"
                :id="categoryId"
              >
                <div class="mb-6">
                  <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ category.name }}
                  </h3>
                  <p class="text-gray-600 dark:text-gray-400">
                    {{ category.description }}
                  </p>
                </div>
                
                <div class="space-y-4">
                  <ApiEndpointCard
                    v-for="endpoint in category.endpoints"
                    :key="`${endpoint.method}-${endpoint.path}`"
                    :endpoint="endpoint"
                  />
                </div>
              </div>
            </div>
          </section>

          <!-- Webhooks -->
          <section id="webhooks" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              Webhooks
            </h2>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
              <p class="text-gray-600 dark:text-gray-400 mb-4">
                Webhooks allow your application to receive real-time notifications when events occur in the platform.
              </p>

              <!-- Webhook Management -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Create Webhook
                  </h3>
                  
                  <form @submit.prevent="createWebhook" class="space-y-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Webhook URL
                      </label>
                      <input
                        v-model="webhookForm.url"
                        type="url"
                        required
                        placeholder="https://your-app.com/webhooks/alumni-platform"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Events
                      </label>
                      <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                        <label
                          v-for="event in availableEvents"
                          :key="event.event"
                          class="flex items-center space-x-2 text-sm"
                        >
                          <input
                            v-model="webhookForm.events"
                            :value="event.event"
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          />
                          <span class="text-gray-700 dark:text-gray-300">{{ event.name }}</span>
                        </label>
                      </div>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Secret (optional)
                      </label>
                      <input
                        v-model="webhookForm.secret"
                        type="text"
                        placeholder="Leave empty to auto-generate"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
                      />
                    </div>

                    <button
                      type="submit"
                      :disabled="creatingWebhook"
                      class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span v-if="creatingWebhook">Creating...</span>
                      <span v-else>Create Webhook</span>
                    </button>
                  </form>
                </div>

                <!-- Existing Webhooks -->
                <div v-if="webhooks.length > 0">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Your Webhooks
                  </h3>
                  
                  <div class="space-y-4">
                    <div
                      v-for="webhook in webhooks"
                      :key="webhook.id"
                      class="border border-gray-200 dark:border-gray-600 rounded-lg p-4"
                    >
                      <div class="flex items-start justify-between">
                        <div class="flex-1">
                          <div class="flex items-center gap-2 mb-2">
                            <code class="text-sm font-mono text-gray-900 dark:text-white">
                              {{ webhook.url }}
                            </code>
                            <span
                              :class="[
                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                webhook.status === 'active'
                                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                  : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                              ]"
                            >
                              {{ webhook.status }}
                            </span>
                          </div>
                          <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Events: {{ webhook.events.join(', ') }}
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-400">
                            Created {{ formatDate(webhook.created_at) }}
                          </div>
                        </div>
                        <div class="flex items-center gap-2">
                          <button
                            @click="testWebhook(webhook.id)"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                          >
                            Test
                          </button>
                          <button
                            @click="deleteWebhook(webhook.id)"
                            class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200"
                          >
                            Delete
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Webhook Tester -->
          <section id="webhook-tester" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              Webhook Tester
            </h2>
            <WebhookTester />
          </section>

          <!-- Postman Collection -->
          <section id="postman" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              Postman Collection Generator
            </h2>
            <PostmanGenerator />
          </section>

          <!-- SDKs -->
          <section id="sdks" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              SDKs & Code Generation
            </h2>
            <SdkGenerator />
          </section>

          <!-- Integration Examples -->
          <section id="integration-examples" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              Integration Examples
            </h2>
            <IntegrationExamples />
          </section>

          <!-- Rate Limits -->
          <section id="rate-limits" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              Rate Limits
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
              <p class="text-gray-600 dark:text-gray-400 mb-6">
                The Alumni Platform API implements rate limiting to ensure fair usage and system stability. 
                Different endpoint categories have different limits.
              </p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                  v-for="(limit, category) in rateLimits"
                  :key="category"
                  class="border border-gray-200 dark:border-gray-600 rounded-lg p-4"
                >
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 capitalize">
                    {{ category.replace('_', ' ') }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    {{ limit.description }}
                  </p>
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Per Minute:</span>
                      <span class="text-sm font-medium text-gray-900 dark:text-white">{{ limit.requests_per_minute }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Per Hour:</span>
                      <span class="text-sm font-medium text-gray-900 dark:text-white">{{ limit.requests_per_hour }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                  Rate Limit Headers
                </h4>
                <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                  All API responses include rate limit information in the headers:
                </p>
                <div class="bg-blue-100 dark:bg-blue-900/40 rounded p-3">
                  <pre class="text-xs text-blue-800 dark:text-blue-200"><code>X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200</code></pre>
                </div>
              </div>
            </div>
          </section>

          <!-- Error Codes -->
          <section id="error-codes" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              Error Codes
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
              <p class="text-gray-600 dark:text-gray-400 mb-6">
                The Alumni Platform API uses conventional HTTP response codes to indicate the success or failure of an API request.
              </p>
              
              <div class="space-y-4">
                <div
                  v-for="(error, code) in errorCodes"
                  :key="code"
                  class="border border-gray-200 dark:border-gray-600 rounded-lg p-4"
                >
                  <div class="flex items-center gap-3 mb-3">
                    <span
                      :class="[
                        'inline-flex items-center px-2 py-1 rounded text-sm font-medium',
                        parseInt(code) < 400 
                          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                          : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                      ]"
                    >
                      {{ code }}
                    </span>
                    <span class="font-medium text-gray-900 dark:text-white">
                      {{ error.code }}
                    </span>
                  </div>
                  
                  <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    {{ error.description }}
                  </p>
                  
                  <div>
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Common Causes:
                    </h5>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside space-y-1">
                      <li v-for="cause in error.common_causes" :key="cause">{{ cause }}</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'
import ApiTester from '@/Components/Developer/ApiTester.vue'
import ApiEndpointCard from '@/Components/Developer/ApiEndpointCard.vue'
import PostmanGenerator from '@/Components/Developer/PostmanGenerator.vue'
import SdkGenerator from '@/Components/Developer/SdkGenerator.vue'
import WebhookTester from '@/Components/Developer/WebhookTester.vue'
import IntegrationExamples from '@/Components/Developer/IntegrationExamples.vue'
import { completeApiEndpoints, enhancedWebhookEvents, enhancedSdkExamples, rateLimits, errorCodes } from '@/Data/completeApiDocumentation.js'

// Reactive data
const isDark = ref(false)
const activeSection = ref('quick-start')
const newKeyName = ref('')
const generatingKey = ref(false)
const generatedKey = ref('')
const apiKeys = ref([])
const creatingWebhook = ref(false)
const webhooks = ref([])
const availableEvents = ref([])

// Forms
const webhookForm = ref({
  url: '',
  events: [],
  secret: ''
})

// Navigation sections
const sections = [
  { id: 'quick-start', title: 'Quick Start' },
  { id: 'api-tester', title: 'API Tester' },
  { id: 'endpoints', title: 'API Endpoints', subsections: [
    { id: 'authentication', title: 'Authentication' },
    { id: 'social', title: 'Social Features' },
    { id: 'alumni', title: 'Alumni Directory' },
    { id: 'career', title: 'Career Development' },
    { id: 'events', title: 'Events & Networking' },
    { id: 'fundraising', title: 'Fundraising' },
    { id: 'analytics', title: 'Analytics' },
    { id: 'webhooks-endpoints', title: 'Webhooks API' }
  ]},
  { id: 'webhooks', title: 'Webhook Management' },
  { id: 'webhook-tester', title: 'Webhook Tester' },
  { id: 'postman', title: 'Postman Collection' },
  { id: 'sdks', title: 'SDKs & Code Generation' },
  { id: 'integration-examples', title: 'Integration Examples' },
  { id: 'rate-limits', title: 'Rate Limits' },
  { id: 'error-codes', title: 'Error Codes' }
]

// Example code
const authExample = `curl -H "Authorization: Bearer YOUR_API_TOKEN" \\
     -H "Content-Type: application/json" \\
     https://your-domain.com/api/user`

// Use complete API endpoints data
const apiEndpointsData = completeApiEndpoints

// Use enhanced SDK examples
const sdks = Object.values(enhancedSdkExamples).map(sdk => ({
  language: sdk.name,
  description: sdk.description,
  install: sdk.installation,
  example: sdk.example,
  icon: 'CodeBracketIcon'
}))

// Methods
const toggleTheme = () => {
  isDark.value = !isDark.value
  document.documentElement.classList.toggle('dark', isDark.value)
}

const scrollToSection = (sectionId) => {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
    activeSection.value = sectionId
  }
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    // Show success message
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

const generateApiKey = async () => {
  if (!newKeyName.value) return
  
  generatingKey.value = true
  try {
    const response = await router.post('/api/developer/api-keys', {
      name: newKeyName.value
    }, {
      preserveState: true,
      onSuccess: (page) => {
        generatedKey.value = page.props.apiKey
        newKeyName.value = ''
        loadApiKeys()
      }
    })
  } finally {
    generatingKey.value = false
  }
}

const loadApiKeys = async () => {
  try {
    const response = await fetch('/api/developer/api-keys', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
        'Accept': 'application/json'
      }
    })
    const data = await response.json()
    apiKeys.value = data.data
  } catch (err) {
    console.error('Failed to load API keys:', err)
  }
}

const revokeApiKey = async (keyId) => {
  if (!confirm('Are you sure you want to revoke this API key?')) return
  
  try {
    await router.delete(`/api/developer/api-keys/${keyId}`, {
      preserveState: true,
      onSuccess: () => {
        loadApiKeys()
      }
    })
  } catch (err) {
    console.error('Failed to revoke API key:', err)
  }
}

const createWebhook = async () => {
  if (!webhookForm.value.url || webhookForm.value.events.length === 0) return
  
  creatingWebhook.value = true
  try {
    await router.post('/api/webhooks', webhookForm.value, {
      preserveState: true,
      onSuccess: () => {
        webhookForm.value = { url: '', events: [], secret: '' }
        loadWebhooks()
      }
    })
  } finally {
    creatingWebhook.value = false
  }
}

const loadWebhooks = async () => {
  try {
    const response = await fetch('/api/webhooks', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
        'Accept': 'application/json'
      }
    })
    const data = await response.json()
    webhooks.value = data.data
  } catch (err) {
    console.error('Failed to load webhooks:', err)
  }
}

const loadAvailableEvents = async () => {
  try {
    // Use enhanced webhook events from documentation data
    availableEvents.value = enhancedWebhookEvents
  } catch (err) {
    console.error('Failed to load available events:', err)
  }
}

const testWebhook = async (webhookId) => {
  try {
    await router.post(`/api/webhooks/${webhookId}/test`, {}, {
      preserveState: true
    })
  } catch (err) {
    console.error('Failed to test webhook:', err)
  }
}

const deleteWebhook = async (webhookId) => {
  if (!confirm('Are you sure you want to delete this webhook?')) return
  
  try {
    await router.delete(`/api/webhooks/${webhookId}`, {
      preserveState: true,
      onSuccess: () => {
        loadWebhooks()
      }
    })
  } catch (err) {
    console.error('Failed to delete webhook:', err)
  }
}

const getMethodColor = (method) => {
  const colors = {
    GET: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    POST: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    PUT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    DELETE: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  return colors[method] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

const tryEndpoint = (endpoint) => {
  // Open API testing interface
  console.log('Try endpoint:', endpoint)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  loadApiKeys()
  loadWebhooks()
  loadAvailableEvents()
  
  // Check for dark mode preference
  isDark.value = document.documentElement.classList.contains('dark')
})
</script>