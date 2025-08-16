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
              Comprehensive guide to the Alumni Platform API v1.0
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

          <!-- Endpoints -->
          <section id="endpoints" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              API Endpoints
            </h2>

            <!-- Endpoint Categories -->
            <div class="space-y-8">
              <div
                v-for="category in endpointCategories"
                :key="category.name"
                :id="category.id"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
              >
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                  <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ category.name }}
                  </h3>
                  <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ category.description }}
                  </p>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                  <div
                    v-for="endpoint in category.endpoints"
                    :key="endpoint.path"
                    class="p-6"
                  >
                    <div class="flex items-start gap-4">
                      <span
                        :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          getMethodColor(endpoint.method)
                        ]"
                      >
                        {{ endpoint.method }}
                      </span>
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <code class="text-sm font-mono text-gray-900 dark:text-white">
                            {{ endpoint.path }}
                          </code>
                          <button
                            @click="tryEndpoint(endpoint)"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                          >
                            Try it
                          </button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                          {{ endpoint.description }}
                        </p>

                        <!-- Parameters -->
                        <div v-if="endpoint.parameters" class="mb-4">
                          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Parameters
                          </h4>
                          <div class="space-y-2">
                            <div
                              v-for="param in endpoint.parameters"
                              :key="param.name"
                              class="flex items-start gap-3 text-sm"
                            >
                              <code class="text-blue-600 dark:text-blue-400 font-mono">
                                {{ param.name }}
                              </code>
                              <span
                                :class="[
                                  'px-1.5 py-0.5 rounded text-xs font-medium',
                                  param.required
                                    ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                ]"
                              >
                                {{ param.required ? 'required' : 'optional' }}
                              </span>
                              <span class="text-gray-600 dark:text-gray-400">
                                {{ param.description }}
                              </span>
                            </div>
                          </div>
                        </div>

                        <!-- Example Response -->
                        <div v-if="endpoint.example" class="mb-4">
                          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Example Response
                          </h4>
                          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ JSON.stringify(endpoint.example, null, 2) }}</code></pre>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
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

          <!-- SDKs -->
          <section id="sdks" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
              SDKs & Libraries
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div
                v-for="sdk in sdks"
                :key="sdk.language"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
              >
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                    <component :is="sdk.icon" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                      {{ sdk.language }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ sdk.description }}
                    </p>
                  </div>
                </div>

                <div class="space-y-3">
                  <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Installation
                    </h4>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                      <code class="text-sm text-gray-800 dark:text-gray-200">{{ sdk.install }}</code>
                    </div>
                  </div>

                  <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Example Usage
                    </h4>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                      <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ sdk.example }}</code></pre>
                    </div>
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
  { id: 'endpoints', title: 'API Endpoints', subsections: [
    { id: 'social', title: 'Social Features' },
    { id: 'career', title: 'Career Development' },
    { id: 'events', title: 'Events' },
    { id: 'fundraising', title: 'Fundraising' },
    { id: 'analytics', title: 'Analytics' }
  ]},
  { id: 'webhooks', title: 'Webhooks' },
  { id: 'sdks', title: 'SDKs & Libraries' }
]

// Example code
const authExample = `curl -H "Authorization: Bearer YOUR_API_TOKEN" \\
     -H "Content-Type: application/json" \\
     https://your-domain.com/api/user`

// Endpoint categories
const endpointCategories = [
  {
    id: 'social',
    name: 'Social Features',
    description: 'Posts, timeline, connections, and social interactions',
    endpoints: [
      {
        method: 'GET',
        path: '/api/timeline',
        description: 'Get personalized timeline',
        parameters: [
          { name: 'page', required: false, description: 'Page number for pagination' },
          { name: 'per_page', required: false, description: 'Items per page (max 50)' }
        ],
        example: {
          success: true,
          data: [
            {
              id: 123,
              content: 'Excited to share my new role!',
              user: { id: 1, name: 'John Doe' },
              created_at: '2024-01-15T10:30:00Z'
            }
          ]
        }
      },
      {
        method: 'POST',
        path: '/api/posts',
        description: 'Create a new post',
        parameters: [
          { name: 'content', required: true, description: 'Post content' },
          { name: 'visibility', required: false, description: 'Post visibility (public, circles, groups)' }
        ]
      }
    ]
  },
  {
    id: 'career',
    name: 'Career Development',
    description: 'Job matching, mentorship, and career tracking',
    endpoints: [
      {
        method: 'GET',
        path: '/api/jobs/recommendations',
        description: 'Get personalized job recommendations',
        parameters: [
          { name: 'industry', required: false, description: 'Filter by industry' },
          { name: 'location', required: false, description: 'Filter by location' }
        ]
      }
    ]
  }
]

// SDKs
const sdks = [
  {
    language: 'JavaScript',
    description: 'For Node.js and browser applications',
    install: 'npm install @alumni-platform/api-client',
    example: `import { AlumniPlatformAPI } from '@alumni-platform/api-client';

const api = new AlumniPlatformAPI({
  baseURL: 'https://your-domain.com/api',
  token: 'your-access-token'
});

const timeline = await api.posts.getTimeline();`,
    icon: 'CodeBracketIcon'
  },
  {
    language: 'PHP',
    description: 'For Laravel and PHP applications',
    install: 'composer require alumni-platform/api-client',
    example: `use AlumniPlatform\\ApiClient\\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'token' => 'your-access-token'
]);

$alumni = $client->alumni()->search(['industry' => 'tech']);`,
    icon: 'CodeBracketIcon'
  },
  {
    language: 'Python',
    description: 'For Python applications',
    install: 'pip install alumni-platform-api',
    example: `from alumni_platform import AlumniPlatformAPI

api = AlumniPlatformAPI(
    base_url='https://your-domain.com/api',
    token='your-access-token'
)

jobs = api.jobs.get_recommendations()`,
    icon: 'CodeBracketIcon'
  }
]

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
    const response = await fetch('/api/webhooks/events', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
        'Accept': 'application/json'
      }
    })
    const data = await response.json()
    availableEvents.value = data.data
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