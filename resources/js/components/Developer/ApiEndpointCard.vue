<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getMethodColor(endpoint.method)
              ]"
            >
              {{ endpoint.method }}
            </span>
            <code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
              {{ endpoint.path }}
            </code>
            <button
              v-if="endpoint.deprecated"
              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
            >
              Deprecated
            </button>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            {{ endpoint.description }}
          </p>
          
          <!-- Tags -->
          <div v-if="endpoint.tags" class="flex flex-wrap gap-2 mb-3">
            <span
              v-for="tag in endpoint.tags"
              :key="tag"
              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
            >
              {{ tag }}
            </span>
          </div>
        </div>
        
        <div class="flex items-center gap-2 ml-4">
          <button
            @click="toggleExpanded"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-medium"
          >
            {{ expanded ? 'Collapse' : 'Expand' }}
          </button>
          <button
            @click="tryEndpoint"
            class="px-3 py-1 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700"
          >
            Try it
          </button>
        </div>
      </div>
    </div>

    <!-- Expanded Content -->
    <div v-if="expanded" class="p-6 space-y-6">
      <!-- Authentication -->
      <div v-if="endpoint.auth" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">
          Authentication Required
        </h4>
        <p class="text-sm text-yellow-700 dark:text-yellow-300">
          {{ endpoint.auth.description || 'This endpoint requires authentication.' }}
        </p>
      </div>

      <!-- Parameters -->
      <div v-if="endpoint.parameters && endpoint.parameters.length > 0">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Parameters
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Name
                </th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Type
                </th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Required
                </th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Description
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="param in endpoint.parameters" :key="param.name">
                <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">
                  {{ param.name }}
                </td>
                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                  {{ param.type || 'string' }}
                </td>
                <td class="px-4 py-2">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                      param.required
                        ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                    ]"
                  >
                    {{ param.required ? 'Required' : 'Optional' }}
                  </span>
                </td>
                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                  {{ param.description }}
                  <span v-if="param.default" class="text-xs text-gray-500 dark:text-gray-400">
                    (default: {{ param.default }})
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Request Body -->
      <div v-if="endpoint.requestBody">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Request Body
        </h4>
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ endpoint.requestBody.contentType || 'application/json' }}
            </span>
            <button
              @click="copyToClipboard(JSON.stringify(endpoint.requestBody.example, null, 2))"
              class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
            >
              Copy
            </button>
          </div>
          <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ JSON.stringify(endpoint.requestBody.example, null, 2) }}</code></pre>
        </div>
      </div>

      <!-- Response Examples -->
      <div v-if="endpoint.responses">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Response Examples
        </h4>
        <div class="space-y-4">
          <div
            v-for="(response, statusCode) in endpoint.responses"
            :key="statusCode"
            class="border border-gray-200 dark:border-gray-600 rounded-lg"
          >
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600 rounded-t-lg">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded text-xs font-medium',
                      getStatusColor(statusCode)
                    ]"
                  >
                    {{ statusCode }}
                  </span>
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ response.description }}
                  </span>
                </div>
                <button
                  @click="copyToClipboard(JSON.stringify(response.example, null, 2))"
                  class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                >
                  Copy
                </button>
              </div>
            </div>
            <div class="p-4">
              <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ JSON.stringify(response.example, null, 2) }}</code></pre>
            </div>
          </div>
        </div>
      </div>

      <!-- Code Examples -->
      <div v-if="endpoint.codeExamples">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Code Examples
        </h4>
        <div class="space-y-4">
          <div
            v-for="example in endpoint.codeExamples"
            :key="example.language"
            class="border border-gray-200 dark:border-gray-600 rounded-lg"
          >
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600 rounded-t-lg">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ example.language }}
                </span>
                <button
                  @click="copyToClipboard(example.code)"
                  class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                >
                  Copy
                </button>
              </div>
            </div>
            <div class="p-4">
              <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ example.code }}</code></pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  endpoint: {
    type: Object,
    required: true
  }
})

const expanded = ref(false)

const toggleExpanded = () => {
  expanded.value = !expanded.value
}

const getMethodColor = (method) => {
  const colors = {
    GET: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    POST: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    PUT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    PATCH: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    DELETE: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  return colors[method] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

const getStatusColor = (status) => {
  const code = parseInt(status)
  if (code >= 200 && code < 300) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  } else if (code >= 400 && code < 500) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  } else if (code >= 500) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    // Could emit an event or show a toast notification here
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

const tryEndpoint = () => {
  // Emit event to parent to open API testing modal
  // This would integrate with the API testing functionality
  console.log('Try endpoint:', props.endpoint)
}
</script>