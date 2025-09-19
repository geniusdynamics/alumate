<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                Code Preview: {{ code.name }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Preview how your {{ code.type.toUpperCase() }} code will render
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <!-- Preview Mode Selector -->
              <select
                v-model="previewMode"
                class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="isolated">Isolated</option>
                <option value="page-context">Page Context</option>
                <option value="component-context">Component Context</option>
              </select>
              
              <!-- Device Selector -->
              <div class="flex items-center space-x-1 border border-gray-300 rounded-md">
                <button
                  v-for="device in devices"
                  :key="device.id"
                  @click="selectedDevice = device.id"
                  :class="[
                    selectedDevice === device.id
                      ? 'bg-indigo-100 text-indigo-700'
                      : 'text-gray-500 hover:text-gray-700',
                    'px-2 py-1 text-xs font-medium'
                  ]"
                >
                  <component :is="device.icon" class="w-4 h-4" />
                </button>
              </div>

              <button
                @click="$emit('close')"
                class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="bg-gray-100 p-4">
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <!-- Preview Toolbar -->
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">
                  {{ currentDevice.name }} Preview ({{ currentDevice.width }})
                </span>
                <div v-if="code.type === 'javascript'" class="flex items-center space-x-2">
                  <button
                    @click="runJavaScript"
                    class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700"
                  >
                    Run JS
                  </button>
                  <button
                    @click="clearConsole"
                    class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50"
                  >
                    Clear Console
                  </button>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="refreshPreview"
                  class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50"
                >
                  Refresh
                </button>
                <button
                  @click="openInNewTab"
                  class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50"
                >
                  Open in New Tab
                </button>
              </div>
            </div>

            <!-- Preview Content -->
            <div class="flex" :style="{ height: previewHeight }">
              <!-- Main Preview -->
              <div class="flex-1 relative">
                <div
                  :class="[
                    'mx-auto bg-white transition-all duration-300',
                    selectedDevice === 'mobile' ? 'max-w-sm' :
                    selectedDevice === 'tablet' ? 'max-w-2xl' :
                    'max-w-full'
                  ]"
                  :style="{ width: currentDevice.width }"
                >
                  <!-- HTML Preview -->
                  <div
                    v-if="code.type === 'html'"
                    class="p-4 min-h-full"
                    v-html="sanitizedPreviewContent"
                  ></div>

                  <!-- CSS Preview -->
                  <div v-else-if="code.type === 'css'" class="p-4">
                    <div class="mb-4">
                      <h4 class="text-sm font-medium text-gray-900 mb-2">CSS Preview</h4>
                      <p class="text-xs text-gray-600">
                        This CSS will be applied to elements on your page. Below is a sample element to demonstrate the styles.
                      </p>
                    </div>
                    
                    <!-- Sample elements to demonstrate CSS -->
                    <div class="space-y-4">
                      <div class="demo-element p-4 border border-gray-200 rounded">
                        <h3 class="text-lg font-semibold mb-2">Sample Heading</h3>
                        <p class="text-gray-600 mb-2">
                          This is a sample paragraph to demonstrate your CSS styles.
                        </p>
                        <button class="px-4 py-2 bg-blue-500 text-white rounded">
                          Sample Button
                        </button>
                      </div>
                      
                      <div class="demo-form p-4 border border-gray-200 rounded">
                        <h4 class="text-md font-medium mb-2">Sample Form</h4>
                        <div class="space-y-2">
                          <input type="text" placeholder="Sample input" class="w-full p-2 border border-gray-300 rounded">
                          <textarea placeholder="Sample textarea" class="w-full p-2 border border-gray-300 rounded" rows="3"></textarea>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- JavaScript Preview -->
                  <div v-else-if="code.type === 'javascript'" class="p-4">
                    <div class="mb-4">
                      <h4 class="text-sm font-medium text-gray-900 mb-2">JavaScript Preview</h4>
                      <p class="text-xs text-gray-600 mb-4">
                        Click "Run JS" to execute your JavaScript code. Check the console output below.
                      </p>
                    </div>
                    
                    <!-- Sample DOM elements for JS to interact with -->
                    <div id="js-demo-container" class="space-y-4">
                      <div class="p-4 border border-gray-200 rounded">
                        <h3 id="demo-heading" class="text-lg font-semibold mb-2">Demo Heading</h3>
                        <p id="demo-paragraph" class="text-gray-600 mb-2">
                          This paragraph can be modified by your JavaScript.
                        </p>
                        <button id="demo-button" class="px-4 py-2 bg-blue-500 text-white rounded">
                          Demo Button
                        </button>
                        <div id="demo-output" class="mt-2 p-2 bg-gray-100 rounded text-sm"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Security Warning -->
                <div v-if="hasSecurityIssues" class="absolute top-0 left-0 right-0 bg-red-100 border-b border-red-200 p-2">
                  <div class="flex items-center text-red-800 text-sm">
                    <ShieldExclamationIcon class="w-4 h-4 mr-1" />
                    Security issues detected. Preview may not reflect actual behavior.
                  </div>
                </div>
              </div>

              <!-- Console Output (for JavaScript) -->
              <div v-if="code.type === 'javascript'" class="w-80 border-l border-gray-200 bg-gray-900 text-green-400 font-mono text-xs">
                <div class="p-2 border-b border-gray-700 bg-gray-800 text-gray-300">
                  Console Output
                </div>
                <div class="p-2 h-full overflow-y-auto">
                  <div v-for="(log, index) in consoleOutput" :key="index" class="mb-1">
                    <span :class="getConsoleLogClass(log.type)">
                      [{{ log.timestamp }}] {{ log.message }}
                    </span>
                  </div>
                  <div v-if="consoleOutput.length === 0" class="text-gray-500">
                    No console output yet. Run your JavaScript to see results.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="$emit('close')"
            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Injected Styles -->
    <component :is="'style'" v-if="code.type === 'css'">
      {{ code.code }}
    </component>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import {
  XMarkIcon,
  ShieldExclamationIcon,
  ComputerDesktopIcon,
  DeviceTabletIcon,
  DevicePhoneMobileIcon
} from '@heroicons/vue/24/outline'
import type { CustomCode } from '@/services/CustomCodeStorageService'
import { customCodeValidationService } from '@/services/CustomCodeValidationService'

// Props & Emits
interface Props {
  code: CustomCode
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

// Reactive state
const previewMode = ref<'isolated' | 'page-context' | 'component-context'>('isolated')
const selectedDevice = ref<'desktop' | 'tablet' | 'mobile'>('desktop')
const consoleOutput = ref<Array<{ type: string, message: string, timestamp: string }>>([])
const hasSecurityIssues = ref(false)

// Device configurations
const devices = [
  { id: 'desktop' as const, name: 'Desktop', width: '100%', icon: ComputerDesktopIcon },
  { id: 'tablet' as const, name: 'Tablet', width: '768px', icon: DeviceTabletIcon },
  { id: 'mobile' as const, name: 'Mobile', width: '375px', icon: DevicePhoneMobileIcon }
]

// Computed properties
const currentDevice = computed(() => {
  return devices.find(d => d.id === selectedDevice.value) || devices[0]
})

const previewHeight = computed(() => {
  return selectedDevice.value === 'mobile' ? '600px' : '500px'
})

const sanitizedPreviewContent = computed(() => {
  if (props.code.type !== 'html') return ''
  
  // Basic HTML sanitization (in production, use a proper sanitization library)
  let sanitized = props.code.code
  
  // Remove potentially dangerous elements and attributes
  sanitized = sanitized.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
  sanitized = sanitized.replace(/on\w+\s*=\s*["'][^"']*["']/gi, '')
  sanitized = sanitized.replace(/javascript:/gi, '')
  
  return sanitized
})

// Methods
const runJavaScript = () => {
  if (props.code.type !== 'javascript') return

  // Clear previous output
  consoleOutput.value = []

  // Create a safe execution context
  const originalConsole = window.console
  const mockConsole = {
    log: (...args: any[]) => addConsoleOutput('log', args.join(' ')),
    error: (...args: any[]) => addConsoleOutput('error', args.join(' ')),
    warn: (...args: any[]) => addConsoleOutput('warn', args.join(' ')),
    info: (...args: any[]) => addConsoleOutput('info', args.join(' '))
  }

  try {
    // Replace console temporarily
    ;(window as any).console = mockConsole

    // Create a safe execution environment
    const safeCode = `
      (function() {
        try {
          ${props.code.code}
        } catch (error) {
          console.error('Runtime Error: ' + error.message);
        }
      })();
    `

    // Execute the code
    eval(safeCode)
    
    if (consoleOutput.value.length === 0) {
      addConsoleOutput('info', 'Code executed successfully (no output)')
    }
  } catch (error) {
    addConsoleOutput('error', `Execution Error: ${error}`)
  } finally {
    // Restore original console
    window.console = originalConsole
  }
}

const addConsoleOutput = (type: string, message: string) => {
  const timestamp = new Date().toLocaleTimeString()
  consoleOutput.value.push({ type, message, timestamp })
}

const getConsoleLogClass = (type: string): string => {
  switch (type) {
    case 'error': return 'text-red-400'
    case 'warn': return 'text-yellow-400'
    case 'info': return 'text-blue-400'
    default: return 'text-green-400'
  }
}

const clearConsole = () => {
  consoleOutput.value = []
}

const refreshPreview = () => {
  // Force re-render by updating a reactive property
  nextTick(() => {
    if (props.code.type === 'javascript') {
      // Reset demo elements
      const container = document.getElementById('js-demo-container')
      if (container) {
        const heading = container.querySelector('#demo-heading')
        const paragraph = container.querySelector('#demo-paragraph')
        const output = container.querySelector('#demo-output')
        
        if (heading) heading.textContent = 'Demo Heading'
        if (paragraph) paragraph.textContent = 'This paragraph can be modified by your JavaScript.'
        if (output) output.textContent = ''
      }
    }
  })
}

const openInNewTab = () => {
  let content = ''
  
  if (props.code.type === 'html') {
    content = `
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8">
          <title>${props.code.name} - Preview</title>
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <style>
            body { font-family: system-ui, -apple-system, sans-serif; margin: 20px; }
          </style>
        </head>
        <body>
          ${props.code.code}
        </body>
      </html>
    `
  } else if (props.code.type === 'css') {
    content = `
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8">
          <title>${props.code.name} - CSS Preview</title>
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <style>
            body { font-family: system-ui, -apple-system, sans-serif; margin: 20px; }
            ${props.code.code}
          </style>
        </head>
        <body>
          <h1>CSS Preview</h1>
          <div class="demo-element">
            <h2>Sample Heading</h2>
            <p>This is a sample paragraph to demonstrate your CSS styles.</p>
            <button>Sample Button</button>
          </div>
        </body>
      </html>
    `
  } else if (props.code.type === 'javascript') {
    content = `
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8">
          <title>${props.code.name} - JavaScript Preview</title>
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <style>
            body { font-family: system-ui, -apple-system, sans-serif; margin: 20px; }
          </style>
        </head>
        <body>
          <h1>JavaScript Preview</h1>
          <div id="demo-container">
            <h2 id="demo-heading">Demo Heading</h2>
            <p id="demo-paragraph">This paragraph can be modified by your JavaScript.</p>
            <button id="demo-button">Demo Button</button>
            <div id="demo-output"></div>
          </div>
          <script>
            ${props.code.code}
          </script>
        </body>
      </html>
    `
  }

  const blob = new Blob([content], { type: 'text/html' })
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank')
  
  // Clean up the URL after a short delay
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}

const checkSecurityIssues = async () => {
  try {
    const validation = await customCodeValidationService.validateCode(props.code.code, props.code.type)
    hasSecurityIssues.value = validation.securityIssues.length > 0
  } catch (error) {
    console.error('Failed to check security issues:', error)
  }
}

// Lifecycle
onMounted(() => {
  checkSecurityIssues()
  
  // Auto-run JavaScript if it's safe
  if (props.code.type === 'javascript' && !hasSecurityIssues.value) {
    nextTick(() => {
      runJavaScript()
    })
  }
})
</script>