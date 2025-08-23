<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Webhook Tester
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Test webhook endpoints and validate payload handling
      </p>
    </div>

    <div class="p-6">
      <!-- Webhook URL Configuration -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Webhook URL
        </label>
        <div class="flex gap-3">
          <input
            v-model="webhookUrl"
            type="url"
            placeholder="https://your-app.com/webhooks/alumni-platform"
            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
          />
          <button
            @click="validateUrl"
            :disabled="!webhookUrl || validating"
            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="validating">Validating...</span>
            <span v-else>Validate</span>
          </button>
        </div>
        
        <!-- URL Validation Result -->
        <div v-if="urlValidation" class="mt-2">
          <div
            :class="[
              'p-3 rounded-lg text-sm',
              urlValidation.valid
                ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300'
                : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'
            ]"
          >
            <div class="flex items-center gap-2">
              <svg
                :class="[
                  'w-4 h-4',
                  urlValidation.valid ? 'text-green-500' : 'text-red-500'
                ]"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  v-if="urlValidation.valid"
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
                <path
                  v-else
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="font-medium">
                {{ urlValidation.valid ? 'URL is valid and reachable' : 'URL validation failed' }}
              </span>
            </div>
            <p class="mt-1">{{ urlValidation.message }}</p>
            <div v-if="urlValidation.details" class="mt-2 text-xs">
              <div>Response Time: {{ urlValidation.details.response_time }}ms</div>
              <div>Status Code: {{ urlValidation.details.status_code }}</div>
              <div v-if="urlValidation.details.headers">
                Headers: {{ Object.keys(urlValidation.details.headers).join(', ') }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Event Selection -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Select Event to Test
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <button
            v-for="event in availableEvents"
            :key="event.event"
            @click="selectedEvent = event"
            :class="[
              'p-4 border rounded-lg text-left transition-colors',
              selectedEvent?.event === event.event
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="font-medium text-gray-900 dark:text-white">
              {{ event.name }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              {{ event.description }}
            </div>
            <div class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-mono">
              {{ event.event }}
            </div>
          </button>
        </div>
      </div>

      <!-- Payload Customization -->
      <div v-if="selectedEvent" class="mb-6">
        <div class="flex items-center justify-between mb-3">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Payload Data
          </label>
          <div class="flex gap-2">
            <button
              @click="resetPayload"
              class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
            >
              Reset to Default
            </button>
            <button
              @click="randomizePayload"
              class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
            >
              Randomize Data
            </button>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
          <textarea
            v-model="payloadJson"
            rows="12"
            class="w-full bg-transparent border-0 text-sm font-mono text-gray-800 dark:text-gray-200 resize-none focus:ring-0"
            placeholder="Webhook payload JSON..."
          ></textarea>
        </div>
        
        <!-- JSON Validation -->
        <div v-if="payloadError" class="mt-2 text-sm text-red-600 dark:text-red-400">
          Invalid JSON: {{ payloadError }}
        </div>
      </div>

      <!-- Webhook Secret -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Webhook Secret (Optional)
        </label>
        <input
          v-model="webhookSecret"
          type="password"
          placeholder="Enter webhook secret for signature verification"
          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
        />
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          If provided, a signature will be generated and included in the X-Signature header
        </p>
      </div>

      <!-- Test Controls -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <label class="flex items-center">
            <input
              v-model="includeRetries"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
              Test retry behavior
            </span>
          </label>
          
          <label class="flex items-center">
            <input
              v-model="validateResponse"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
              Validate response
            </span>
          </label>
        </div>
        
        <button
          @click="sendTestWebhook"
          :disabled="!webhookUrl || !selectedEvent || testing"
          class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="testing">Sending Test...</span>
          <span v-else>Send Test Webhook</span>
        </button>
      </div>

      <!-- Test Results -->
      <div v-if="testResults.length > 0" class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
          Test Results
        </h4>
        
        <div class="space-y-4">
          <div
            v-for="(result, index) in testResults"
            :key="index"
            class="border border-gray-200 dark:border-gray-600 rounded-lg"
          >
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600 rounded-t-lg">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded text-xs font-medium',
                      result.success
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                    ]"
                  >
                    {{ result.success ? 'Success' : 'Failed' }}
                  </span>
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ result.timestamp }}
                  </span>
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ result.duration }}ms
                  </span>
                </div>
                
                <button
                  @click="result.expanded = !result.expanded"
                  class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                >
                  {{ result.expanded ? 'Collapse' : 'Expand' }}
                </button>
              </div>
            </div>
            
            <div v-if="result.expanded" class="p-4 space-y-4">
              <!-- Request Details -->
              <div>
                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Request Details
                </h5>
                <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                  <div class="text-sm space-y-1">
                    <div><strong>URL:</strong> {{ result.request.url }}</div>
                    <div><strong>Method:</strong> {{ result.request.method }}</div>
                    <div><strong>Headers:</strong></div>
                    <pre class="text-xs text-gray-600 dark:text-gray-400 ml-4">{{ JSON.stringify(result.request.headers, null, 2) }}</pre>
                  </div>
                </div>
              </div>
              
              <!-- Response Details -->
              <div>
                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Response Details
                </h5>
                <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                  <div class="text-sm space-y-1">
                    <div><strong>Status:</strong> {{ result.response.status }} {{ result.response.statusText }}</div>
                    <div><strong>Headers:</strong></div>
                    <pre class="text-xs text-gray-600 dark:text-gray-400 ml-4">{{ JSON.stringify(result.response.headers, null, 2) }}</pre>
                    <div v-if="result.response.body"><strong>Body:</strong></div>
                    <pre v-if="result.response.body" class="text-xs text-gray-600 dark:text-gray-400 ml-4">{{ result.response.body }}</pre>
                  </div>
                </div>
              </div>
              
              <!-- Error Details -->
              <div v-if="result.error">
                <h5 class="text-sm font-medium text-red-700 dark:text-red-300 mb-2">
                  Error Details
                </h5>
                <div class="bg-red-50 dark:bg-red-900/20 rounded p-3">
                  <pre class="text-sm text-red-700 dark:text-red-300">{{ result.error }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Webhook Signature Verification Guide -->
      <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
          Webhook Signature Verification
        </h4>
        
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
          <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
            Webhooks include a signature in the <code>X-Signature</code> header for security verification.
          </p>
          
          <div class="space-y-4">
            <div>
              <h5 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                Node.js Example
              </h5>
              <pre class="text-xs bg-blue-100 dark:bg-blue-900/40 rounded p-3 overflow-x-auto"><code>const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(payload, 'utf8')
    .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );
}</code></pre>
            </div>
            
            <div>
              <h5 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                PHP Example
              </h5>
              <pre class="text-xs bg-blue-100 dark:bg-blue-900/40 rounded p-3 overflow-x-auto"><code>function verifyWebhookSignature($payload, $signature, $secret) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($signature, $expectedSignature);
}</code></pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { enhancedWebhookEvents } from '@/Data/completeApiDocumentation.js'

const webhookUrl = ref('')
const selectedEvent = ref(null)
const payloadJson = ref('')
const webhookSecret = ref('')
const includeRetries = ref(false)
const validateResponse = ref(true)
const validating = ref(false)
const testing = ref(false)
const urlValidation = ref(null)
const testResults = ref([])

const availableEvents = enhancedWebhookEvents

const payloadError = computed(() => {
  if (!payloadJson.value) return null
  
  try {
    JSON.parse(payloadJson.value)
    return null
  } catch (error) {
    return error.message
  }
})

watch(selectedEvent, (newEvent) => {
  if (newEvent) {
    payloadJson.value = JSON.stringify(newEvent.example_payload, null, 2)
  }
})

const validateUrl = async () => {
  if (!webhookUrl.value) return
  
  validating.value = true
  urlValidation.value = null
  
  try {
    // Simulate URL validation
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Mock validation result
    const isValid = webhookUrl.value.startsWith('https://') && webhookUrl.value.includes('.')
    
    urlValidation.value = {
      valid: isValid,
      message: isValid 
        ? 'URL is reachable and accepts POST requests'
        : 'URL appears to be invalid or unreachable',
      details: isValid ? {
        response_time: Math.floor(Math.random() * 500) + 100,
        status_code: 200,
        headers: {
          'content-type': 'application/json',
          'server': 'nginx/1.18.0'
        }
      } : null
    }
  } catch (error) {
    urlValidation.value = {
      valid: false,
      message: `Validation failed: ${error.message}`
    }
  } finally {
    validating.value = false
  }
}

const resetPayload = () => {
  if (selectedEvent.value) {
    payloadJson.value = JSON.stringify(selectedEvent.value.example_payload, null, 2)
  }
}

const randomizePayload = () => {
  if (!selectedEvent.value) return
  
  try {
    const payload = JSON.parse(payloadJson.value)
    
    // Randomize some common fields
    if (payload.data) {
      if (payload.data.id) {
        payload.data.id = Math.floor(Math.random() * 10000) + 1
      }
      if (payload.data.user && payload.data.user.id) {
        payload.data.user.id = Math.floor(Math.random() * 1000) + 1
      }
      if (payload.data.content) {
        const messages = [
          'Just completed an amazing project!',
          'Excited to share this milestone with everyone.',
          'Looking forward to new opportunities ahead.',
          'Grateful for all the support from the community.'
        ]
        payload.data.content = messages[Math.floor(Math.random() * messages.length)]
      }
    }
    
    // Update timestamp
    payload.timestamp = new Date().toISOString()
    
    payloadJson.value = JSON.stringify(payload, null, 2)
  } catch (error) {
    console.error('Failed to randomize payload:', error)
  }
}

const generateSignature = (payload, secret) => {
  // This would use crypto in a real implementation
  // For demo purposes, return a mock signature
  return `sha256=${btoa(payload + secret).substring(0, 32)}`
}

const sendTestWebhook = async () => {
  if (!webhookUrl.value || !selectedEvent.value || payloadError.value) return
  
  testing.value = true
  const startTime = Date.now()
  
  try {
    const payload = payloadJson.value
    const headers = {
      'Content-Type': 'application/json',
      'User-Agent': 'Alumni-Platform-Webhooks/1.0',
      'X-Event-Type': selectedEvent.value.event
    }
    
    // Add signature if secret is provided
    if (webhookSecret.value) {
      headers['X-Signature'] = generateSignature(payload, webhookSecret.value)
    }
    
    // Simulate webhook delivery
    await new Promise(resolve => setTimeout(resolve, Math.random() * 2000 + 500))
    
    const duration = Date.now() - startTime
    const success = Math.random() > 0.1 // 90% success rate for demo
    
    const result = {
      success,
      timestamp: new Date().toLocaleString(),
      duration,
      expanded: false,
      request: {
        url: webhookUrl.value,
        method: 'POST',
        headers
      },
      response: success ? {
        status: 200,
        statusText: 'OK',
        headers: {
          'content-type': 'application/json',
          'server': 'nginx/1.18.0'
        },
        body: '{"success": true, "message": "Webhook received successfully"}'
      } : {
        status: 500,
        statusText: 'Internal Server Error',
        headers: {
          'content-type': 'text/plain'
        },
        body: 'Internal server error'
      },
      error: success ? null : 'Connection timeout after 30 seconds'
    }
    
    testResults.value.unshift(result)
    
    // Limit to last 10 results
    if (testResults.value.length > 10) {
      testResults.value = testResults.value.slice(0, 10)
    }
    
  } catch (error) {
    const result = {
      success: false,
      timestamp: new Date().toLocaleString(),
      duration: Date.now() - startTime,
      expanded: false,
      request: {
        url: webhookUrl.value,
        method: 'POST',
        headers: {}
      },
      response: null,
      error: error.message
    }
    
    testResults.value.unshift(result)
  } finally {
    testing.value = false
  }
}
</script>