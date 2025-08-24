<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        SDK Generator
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Generate client SDKs for your preferred programming language
      </p>
    </div>

    <div class="p-6">
      <!-- Language Selection -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Select Language
        </label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <button
            v-for="lang in availableLanguages"
            :key="lang.id"
            @click="selectedLanguage = lang.id"
            :class="[
              'p-4 border rounded-lg text-left transition-colors',
              selectedLanguage === lang.id
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="flex items-center gap-3">
              <component :is="lang.icon" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
              <div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ lang.name }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ lang.description }}
                </div>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Configuration Options -->
      <div v-if="selectedLanguage" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Configuration
        </h4>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Package Name
            </label>
            <input
              v-model="config.packageName"
              type="text"
              :placeholder="getPackageNamePlaceholder(selectedLanguage)"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              API Base URL
            </label>
            <input
              v-model="config.baseUrl"
              type="url"
              placeholder="https://your-domain.com/api"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Version
            </label>
            <input
              v-model="config.version"
              type="text"
              placeholder="1.0.0"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <!-- Language-specific options -->
          <div v-if="selectedLanguage === 'javascript'">
            <label class="flex items-center">
              <input
                v-model="config.includeTypes"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Include TypeScript definitions
              </span>
            </label>
          </div>

          <div v-if="selectedLanguage === 'php'">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Namespace
              </label>
              <input
                v-model="config.namespace"
                type="text"
                placeholder="YourCompany\\AlumniPlatform"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
          </div>

          <div v-if="selectedLanguage === 'python'">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Python Version
              </label>
              <select
                v-model="config.pythonVersion"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="3.8">Python 3.8+</option>
                <option value="3.9">Python 3.9+</option>
                <option value="3.10">Python 3.10+</option>
                <option value="3.11">Python 3.11+</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Endpoint Selection -->
      <div v-if="selectedLanguage" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Include Endpoints
        </h4>
        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3">
          <label
            v-for="category in endpointCategories"
            :key="category.id"
            class="flex items-center space-x-2"
          >
            <input
              v-model="config.includedCategories"
              :value="category.id"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              {{ category.name }} ({{ category.endpointCount }} endpoints)
            </span>
          </label>
        </div>
      </div>

      <!-- Generate Button -->
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          <span v-if="selectedLanguage">
            Ready to generate {{ getLanguageName(selectedLanguage) }} SDK
          </span>
        </div>
        <button
          @click="generateSdk"
          :disabled="!selectedLanguage || generating"
          class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="generating">Generating...</span>
          <span v-else>Generate SDK</span>
        </button>
      </div>

      <!-- Generated SDK Preview -->
      <div v-if="generatedSdk" class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Generated SDK
          </h4>
          <div class="flex gap-2">
            <button
              @click="downloadSdk"
              class="px-3 py-1 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700"
            >
              Download
            </button>
            <button
              @click="copySdk"
              class="px-3 py-1 bg-gray-600 text-white text-sm font-medium rounded hover:bg-gray-700"
            >
              Copy
            </button>
          </div>
        </div>

        <!-- File Structure -->
        <div class="mb-4">
          <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            File Structure
          </h5>
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
            <div class="text-sm font-mono text-gray-800 dark:text-gray-200">
              <div v-for="file in generatedSdk.files" :key="file.path" class="py-1">
                📄 {{ file.path }}
              </div>
            </div>
          </div>
        </div>

        <!-- Installation Instructions -->
        <div class="mb-4">
          <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Installation
          </h5>
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
            <pre class="text-sm text-gray-800 dark:text-gray-200"><code>{{ generatedSdk.installation }}</code></pre>
          </div>
        </div>

        <!-- Usage Example -->
        <div>
          <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Usage Example
          </h5>
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
            <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ generatedSdk.example }}</code></pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { CodeBracketIcon } from '@heroicons/vue/24/outline'

const selectedLanguage = ref('')
const generating = ref(false)
const generatedSdk = ref(null)

const config = reactive({
  packageName: '',
  baseUrl: 'https://your-domain.com/api',
  version: '1.0.0',
  includeTypes: true,
  namespace: '',
  pythonVersion: '3.8',
  includedCategories: []
})

const availableLanguages = [
  {
    id: 'javascript',
    name: 'JavaScript/Node.js',
    description: 'For web and Node.js apps',
    icon: CodeBracketIcon
  },
  {
    id: 'php',
    name: 'PHP',
    description: 'For Laravel and PHP apps',
    icon: CodeBracketIcon
  },
  {
    id: 'python',
    name: 'Python',
    description: 'For Python applications',
    icon: CodeBracketIcon
  },
  {
    id: 'csharp',
    name: 'C#',
    description: 'For .NET applications',
    icon: CodeBracketIcon
  }
]

const endpointCategories = [
  { id: 'social', name: 'Social Features', endpointCount: 12 },
  { id: 'career', name: 'Career Development', endpointCount: 8 },
  { id: 'events', name: 'Events', endpointCount: 15 },
  { id: 'fundraising', name: 'Fundraising', endpointCount: 10 },
  { id: 'analytics', name: 'Analytics', endpointCount: 6 },
  { id: 'webhooks', name: 'Webhooks', endpointCount: 5 }
]

const getPackageNamePlaceholder = (language) => {
  const placeholders = {
    javascript: '@your-company/alumni-platform-api',
    php: 'your-company/alumni-platform-api',
    python: 'alumni-platform-api',
    csharp: 'YourCompany.AlumniPlatform.Api'
  }
  return placeholders[language] || 'alumni-platform-api'
}

const getLanguageName = (language) => {
  const names = {
    javascript: 'JavaScript/Node.js',
    php: 'PHP',
    python: 'Python',
    csharp: 'C#'
  }
  return names[language] || language
}

const generateSdk = async () => {
  generating.value = true
  
  try {
    // Simulate SDK generation
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Generate SDK based on selected language
    const sdkTemplates = {
      javascript: {
        files: [
          { path: 'package.json' },
          { path: 'src/index.js' },
          { path: 'src/client.js' },
          { path: 'src/resources/posts.js' },
          { path: 'src/resources/alumni.js' },
          { path: 'src/resources/events.js' },
          { path: 'types/index.d.ts' }
        ],
        installation: 'npm install ' + (config.packageName || '@your-company/alumni-platform-api'),
        example: `import { AlumniPlatformAPI } from '${config.packageName || '@your-company/alumni-platform-api'}';

const api = new AlumniPlatformAPI({
  baseURL: '${config.baseUrl}',
  token: 'your-access-token'
});

// Get timeline posts
const timeline = await api.posts.getTimeline();

// Search alumni
const alumni = await api.alumni.search({
  industry: 'technology',
  location: 'San Francisco'
});

// Create a post
const post = await api.posts.create({
  content: 'Excited to share my new role!',
  visibility: 'public'
});`
      },
      php: {
        files: [
          { path: 'composer.json' },
          { path: 'src/Client.php' },
          { path: 'src/Resources/Posts.php' },
          { path: 'src/Resources/Alumni.php' },
          { path: 'src/Resources/Events.php' },
          { path: 'src/Exceptions/ApiException.php' }
        ],
        installation: 'composer require ' + (config.packageName || 'your-company/alumni-platform-api'),
        example: `<?php

use ${config.namespace || 'YourCompany\\AlumniPlatform'}\\Client;

$client = new Client([
    'base_uri' => '${config.baseUrl}',
    'token' => 'your-access-token'
]);

// Get timeline posts
$timeline = $client->posts()->getTimeline();

// Search alumni
$alumni = $client->alumni()->search([
    'industry' => 'technology',
    'location' => 'San Francisco'
]);

// Create a post
$post = $client->posts()->create([
    'content' => 'Excited to share my new role!',
    'visibility' => 'public'
]);`
      },
      python: {
        files: [
          { path: 'setup.py' },
          { path: 'alumni_platform/__init__.py' },
          { path: 'alumni_platform/client.py' },
          { path: 'alumni_platform/resources/posts.py' },
          { path: 'alumni_platform/resources/alumni.py' },
          { path: 'alumni_platform/resources/events.py' },
          { path: 'alumni_platform/exceptions.py' }
        ],
        installation: 'pip install ' + (config.packageName || 'alumni-platform-api'),
        example: `from alumni_platform import AlumniPlatformAPI

api = AlumniPlatformAPI(
    base_url='${config.baseUrl}',
    token='your-access-token'
)

# Get timeline posts
timeline = api.posts.get_timeline()

# Search alumni
alumni = api.alumni.search(
    industry='technology',
    location='San Francisco'
)

# Create a post
post = api.posts.create(
    content='Excited to share my new role!',
    visibility='public'
)`
      }
    }
    
    generatedSdk.value = sdkTemplates[selectedLanguage.value]
    
  } finally {
    generating.value = false
  }
}

const downloadSdk = () => {
  // Create a zip file with the generated SDK
  const filename = `${config.packageName || 'alumni-platform-api'}-${selectedLanguage.value}-sdk.zip`
  
  // In a real implementation, this would generate and download the actual SDK files
  console.log('Downloading SDK:', filename)
  
  // For demo purposes, just show an alert
  alert(`SDK download started: ${filename}`)
}

const copySdk = async () => {
  try {
    await navigator.clipboard.writeText(generatedSdk.value.example)
    // Show success message
  } catch (err) {
    console.error('Failed to copy SDK example:', err)
  }
}
</script>