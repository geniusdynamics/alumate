<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        API Tester
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Test API endpoints directly from the documentation
      </p>
    </div>

    <div class="p-6">
      <!-- Endpoint Configuration -->
      <div class="space-y-4 mb-6">
        <div class="flex gap-3">
          <select
            v-model="request.method"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
            <option value="DELETE">DELETE</option>
          </select>
          <input
            v-model="request.url"
            type="text"
            placeholder="/api/endpoint"
            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
          />
          <button
            @click="sendRequest"
            :disabled="loading || !request.url"
            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading">Sending...</span>
            <span v-else>Send</span>
          </button>
        </div>

        <!-- Authentication -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Authentication
          </h4>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                API Token
              </label>
              <input
                v-model="request.token"
                type="password"
                placeholder="Your API token"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Headers -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Headers
          </h4>
          <button
            @click="addHeader"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
          >
            Add Header
          </button>
        </div>
        <div class="space-y-2">
          <div
            v-for="(header, index) in request.headers"
            :key="index"
            class="flex gap-2"
          >
            <input
              v-model="header.key"
              type="text"
              placeholder="Header name"
              class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
            />
            <input
              v-model="header.value"
              type="text"
              placeholder="Header value"
              class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
            />
            <button
              @click="removeHeader(index)"
              class="px-3 py-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200"
            >
              Remove
            </button>
          </div>
        </div>
      </div>

      <!-- Request Body -->
      <div v-if="['POST', 'PUT', 'PATCH'].includes(request.method)" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Request Body
        </h4>
        <div class="space-y-3">
          <div class="flex gap-2">
            <button
              v-for="type in ['JSON', 'Form Data', 'Raw']"
              :key="type"
              @click="request.bodyType = type"
              :class="[
                'px-3 py-1 text-sm font-medium rounded',
                request.bodyType === type
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                  : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              {{ type }}
            </button>
          </div>

          <!-- JSON Body -->
          <div v-if="request.bodyType === 'JSON'">
            <textarea
              v-model="request.jsonBody"
              rows="8"
              placeholder='{"key": "value"}'
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 font-mono"
            ></textarea>
          </div>

          <!-- Form Data -->
          <div v-if="request.bodyType === 'Form Data'" class="space-y-2">
            <div
              v-for="(field, index) in request.formData"
              :key="index"
              class="flex gap-2"
            >
              <input
                v-model="field.key"
                type="text"
                placeholder="Field name"
                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
              />
              <input
                v-model="field.value"
                type="text"
                placeholder="Field value"
                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
              />
              <button
                @click="removeFormField(index)"
                class="px-3 py-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200"
              >
                Remove
              </button>
            </div>
            <button
              @click="addFormField"
              class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
            >
              Add Field
            </button>
          </div>

          <!-- Raw Body -->
          <div v-if="request.bodyType === 'Raw'">
            <textarea
              v-model="request.rawBody"
              rows="8"
              placeholder="Raw request body"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 font-mono"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Response -->
      <div v-if="response" class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Response
        </h4>
        
        <!-- Response Status -->
        <div class="flex items-center gap-3 mb-4">
          <span
            :class="[
              'inline-flex items-center px-2 py-1 rounded text-sm font-medium',
              getStatusColor(response.status)
            ]"
          >
            {{ response.status }} {{ response.statusText }}
          </span>
          <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ response.duration }}ms
          </span>
          <button
            @click="copyResponse"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
          >
            Copy Response
          </button>
        </div>

        <!-- Response Headers -->
        <div v-if="response.headers && Object.keys(response.headers).length > 0" class="mb-4">
          <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Response Headers
          </h5>
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
            <div
              v-for="(value, key) in response.headers"
              :key="key"
              class="flex justify-between text-sm"
            >
              <span class="font-mono text-gray-600 dark:text-gray-400">{{ key }}:</span>
              <span class="text-gray-800 dark:text-gray-200">{{ value }}</span>
            </div>
          </div>
        </div>

        <!-- Response Body -->
        <div>
          <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Response Body
          </h5>
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ formatResponseBody(response.data) }}</code></pre>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
          <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">
            Request Failed
          </h4>
          <p class="text-sm text-red-700 dark:text-red-300">
            {{ error }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const loading = ref(false)
const response = ref(null)
const error = ref(null)

const request = reactive({
  method: 'GET',
  url: '',
  token: '',
  headers: [],
  bodyType: 'JSON',
  jsonBody: '{}',
  formData: [],
  rawBody: ''
})

const addHeader = () => {
  request.headers.push({ key: '', value: '' })
}

const removeHeader = (index) => {
  request.headers.splice(index, 1)
}

const addFormField = () => {
  request.formData.push({ key: '', value: '' })
}

const removeFormField = (index) => {
  request.formData.splice(index, 1)
}

const sendRequest = async () => {
  loading.value = true
  response.value = null
  error.value = null

  try {
    const startTime = Date.now()
    
    // Build headers
    const headers = {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }

    // Add authentication
    if (request.token) {
      headers['Authorization'] = `Bearer ${request.token}`
    }

    // Add custom headers
    request.headers.forEach(header => {
      if (header.key && header.value) {
        headers[header.key] = header.value
      }
    })

    // Build request options
    const options = {
      method: request.method,
      headers
    }

    // Add body for non-GET requests
    if (['POST', 'PUT', 'PATCH'].includes(request.method)) {
      if (request.bodyType === 'JSON') {
        headers['Content-Type'] = 'application/json'
        options.body = request.jsonBody
      } else if (request.bodyType === 'Form Data') {
        const formData = new FormData()
        request.formData.forEach(field => {
          if (field.key && field.value) {
            formData.append(field.key, field.value)
          }
        })
        options.body = formData
      } else if (request.bodyType === 'Raw') {
        options.body = request.rawBody
      }
    }

    // Make request
    const baseUrl = window.location.origin
    const fullUrl = request.url.startsWith('/') ? baseUrl + request.url : request.url
    
    const res = await fetch(fullUrl, options)
    const duration = Date.now() - startTime
    
    let data
    const contentType = res.headers.get('content-type')
    if (contentType && contentType.includes('application/json')) {
      data = await res.json()
    } else {
      data = await res.text()
    }

    // Extract response headers
    const responseHeaders = {}
    res.headers.forEach((value, key) => {
      responseHeaders[key] = value
    })

    response.value = {
      status: res.status,
      statusText: res.statusText,
      headers: responseHeaders,
      data,
      duration
    }

  } catch (err) {
    error.value = err.message || 'Request failed'
  } finally {
    loading.value = false
  }
}

const getStatusColor = (status) => {
  if (status >= 200 && status < 300) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  } else if (status >= 400 && status < 500) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  } else if (status >= 500) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

const formatResponseBody = (data) => {
  if (typeof data === 'string') {
    try {
      return JSON.stringify(JSON.parse(data), null, 2)
    } catch {
      return data
    }
  }
  return JSON.stringify(data, null, 2)
}

const copyResponse = async () => {
  try {
    await navigator.clipboard.writeText(formatResponseBody(response.value.data))
  } catch (err) {
    console.error('Failed to copy response:', err)
  }
}
</script>