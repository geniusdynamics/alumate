<template>
  <div class="live-preview">
    <!-- Preview Header -->
    <div class="preview-header">
      <div class="header-left">
        <h3 class="preview-title">Live Preview</h3>
        <div class="preview-status">
          <div :class="['status-indicator', { 'active': isLive, 'updating': isUpdating }]"></div>
          <span class="status-text">
            {{ isUpdating ? 'Updating...' : isLive ? 'Live' : 'Disconnected' }}
          </span>
        </div>
      </div>

      <div class="header-right">
        <div class="preview-controls">
          <!-- Device Selector -->
          <div class="device-selector">
            <button
              v-for="device in devices"
              :key="device.id"
              @click="switchPreviewDevice(device.id)"
              :class="['device-btn', { 'active': previewDevice === device.id }]"
              :title="`Preview on ${device.name}`"
            >
              <Icon :name="device.icon" class="w-4 h-4" />
            </button>
          </div>

          <!-- Preview Actions -->
          <button
            @click="refreshPreview"
            :disabled="isUpdating"
            class="action-btn refresh-btn"
            title="Refresh preview"
          >
            <Icon name="refresh" :class="['w-4 h-4', { 'animate-spin': isUpdating }]" />
          </button>

          <button
            @click="openInNewTab"
            class="action-btn external-btn"
            title="Open in new tab"
          >
            <Icon name="external-link" class="w-4 h-4" />
          </button>

          <button
            @click="toggleInteractionMode"
            :class="['action-btn interaction-btn', { 'active': interactionMode }]"
            title="Toggle interaction mode"
          >
            <Icon name="cursor-click" class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Preview Container -->
    <div class="preview-container">
      <div 
        :class="['preview-frame', `device-${previewDevice}`, { 'with-frame': showDeviceFrame }]"
        :style="frameStyles"
      >
        <!-- Device Frame -->
        <div v-if="showDeviceFrame" class="device-frame">
          <div class="device-screen">
            <div class="screen-content">
              <iframe
                ref="previewIframe"
                :src="previewUrl"
                class="preview-iframe"
                @load="onIframeLoad"
                @error="onIframeError"
              ></iframe>
            </div>
          </div>
        </div>

        <!-- Direct Preview -->
        <iframe
          v-else
          ref="previewIframe"
          :src="previewUrl"
          class="preview-iframe direct"
          @load="onIframeLoad"
          @error="onIframeError"
        ></iframe>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="loading-overlay">
          <div class="loading-spinner"></div>
          <p class="loading-text">Loading preview...</p>
        </div>

        <!-- Error Overlay -->
        <div v-if="previewError" class="error-overlay">
          <div class="error-content">
            <Icon name="alert-circle" class="w-8 h-8 text-red-500" />
            <p class="error-message">{{ previewError }}</p>
            <button @click="retryPreview" class="retry-btn">
              Retry
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Interaction Panel -->
    <div v-if="interactionMode" class="interaction-panel">
      <div class="panel-header">
        <h4 class="panel-title">Interactions</h4>
        <button @click="clearInteractionLogs" class="clear-btn">
          Clear
        </button>
      </div>

      <div class="interaction-logs">
        <div v-if="interactionLogs.length === 0" class="no-interactions">
          <p>No interactions recorded</p>
          <p class="hint">Click elements in the preview to test interactions</p>
        </div>

        <div v-else class="log-entries">
          <div
            v-for="(log, index) in interactionLogs"
            :key="index"
            :class="['log-entry', log.type]"
          >
            <div class="log-header">
              <span class="log-type">{{ log.type.toUpperCase() }}</span>
              <span class="log-time">{{ formatTime(log.timestamp) }}</span>
            </div>
            <div class="log-details">
              <p class="log-element">{{ log.element }}</p>
              <p v-if="log.data" class="log-data">{{ JSON.stringify(log.data) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Metrics -->
    <div v-if="showPerformanceMetrics" class="performance-metrics">
      <div class="metrics-header">
        <h4 class="metrics-title">Performance</h4>
        <button @click="showPerformanceMetrics = false" class="close-metrics-btn">
          <Icon name="x" class="w-4 h-4" />
        </button>
      </div>

      <div class="metrics-grid">
        <div class="metric-item">
          <span class="metric-label">Load Time</span>
          <span class="metric-value">{{ performanceMetrics.loadTime }}ms</span>
        </div>
        <div class="metric-item">
          <span class="metric-label">DOM Ready</span>
          <span class="metric-value">{{ performanceMetrics.domReady }}ms</span>
        </div>
        <div class="metric-item">
          <span class="metric-label">First Paint</span>
          <span class="metric-value">{{ performanceMetrics.firstPaint }}ms</span>
        </div>
        <div class="metric-item">
          <span class="metric-label">LCP</span>
          <span class="metric-value">{{ performanceMetrics.lcp }}ms</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { realTimeEditingService } from '../../services/RealTimeEditingService'
import Icon from '../ui/Icon.vue'

// Props
interface Props {
  pageId?: string
  grapeJSEditor?: any
  autoUpdate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  autoUpdate: true
})

// Emits
const emit = defineEmits<{
  interactionDetected: [interaction: any]
  performanceUpdate: [metrics: any]
}>()

// Reactive state
const previewDevice = ref('desktop')
const showDeviceFrame = ref(true)
const interactionMode = ref(false)
const isLive = ref(false)
const isLoading = ref(false)
const isUpdating = ref(false)
const previewError = ref('')
const showPerformanceMetrics = ref(false)

const interactionLogs = ref<any[]>([])
const performanceMetrics = reactive({
  loadTime: 0,
  domReady: 0,
  firstPaint: 0,
  lcp: 0
})

// Refs
const previewIframe = ref<HTMLIFrameElement | null>(null)

// Device configurations
const devices = realTimeEditingService.devices

// Computed
const currentDevice = computed(() => 
  devices.find(d => d.id === previewDevice.value) || devices[0]
)

const frameStyles = computed(() => {
  const device = currentDevice.value
  return {
    width: `${device.width}px`,
    height: `${device.height}px`,
    maxWidth: '100%',
    maxHeight: '100%'
  }
})

const previewUrl = computed(() => {
  if (!props.pageId) return 'about:blank'
  
  const baseUrl = `/api/pages/${props.pageId}/preview`
  const params = new URLSearchParams({
    device: previewDevice.value,
    timestamp: Date.now().toString(),
    interaction_mode: interactionMode.value ? '1' : '0'
  })
  
  return `${baseUrl}?${params.toString()}`
})

// Methods
const switchPreviewDevice = (deviceId: string) => {
  previewDevice.value = deviceId
  refreshPreview()
}

const refreshPreview = async () => {
  if (!previewIframe.value) return

  isLoading.value = true
  previewError.value = ''
  
  try {
    // Update iframe src to trigger reload
    previewIframe.value.src = previewUrl.value
  } catch (error) {
    console.error('Failed to refresh preview:', error)
    previewError.value = 'Failed to load preview'
    isLoading.value = false
  }
}

const openInNewTab = () => {
  if (previewUrl.value && previewUrl.value !== 'about:blank') {
    window.open(previewUrl.value, '_blank')
  }
}

const toggleInteractionMode = () => {
  interactionMode.value = !interactionMode.value
  
  if (interactionMode.value) {
    setupInteractionTracking()
  } else {
    removeInteractionTracking()
  }
  
  refreshPreview()
}

const onIframeLoad = () => {
  isLoading.value = false
  isUpdating.value = false
  isLive.value = true
  
  // Measure performance
  measurePerformance()
  
  // Setup interaction tracking if enabled
  if (interactionMode.value) {
    setupInteractionTracking()
  }
}

const onIframeError = () => {
  isLoading.value = false
  isUpdating.value = false
  isLive.value = false
  previewError.value = 'Failed to load preview'
}

const retryPreview = () => {
  previewError.value = ''
  refreshPreview()
}

const setupInteractionTracking = () => {
  if (!previewIframe.value || !previewIframe.value.contentWindow) return

  try {
    const iframeDoc = previewIframe.value.contentDocument
    if (!iframeDoc) return

    // Add event listeners to track interactions
    const trackInteraction = (event: Event) => {
      const target = event.target as HTMLElement
      const interaction = {
        type: event.type,
        element: getElementSelector(target),
        timestamp: Date.now(),
        data: extractEventData(event)
      }
      
      interactionLogs.value.push(interaction)
      emit('interactionDetected', interaction)
      
      // Limit log size
      if (interactionLogs.value.length > 100) {
        interactionLogs.value.shift()
      }
    }

    // Track various interaction types
    const eventTypes = ['click', 'submit', 'change', 'focus', 'blur', 'scroll']
    eventTypes.forEach(eventType => {
      iframeDoc.addEventListener(eventType, trackInteraction, true)
    })

  } catch (error) {
    console.warn('Could not setup interaction tracking:', error)
  }
}

const removeInteractionTracking = () => {
  // Interaction tracking is automatically removed when iframe reloads
}

const getElementSelector = (element: HTMLElement): string => {
  if (element.id) {
    return `#${element.id}`
  }
  
  if (element.className) {
    const classes = element.className.split(' ').filter(c => c.trim())
    if (classes.length > 0) {
      return `.${classes[0]}`
    }
  }
  
  return element.tagName.toLowerCase()
}

const extractEventData = (event: Event): any => {
  const data: any = {}
  
  if (event.type === 'click') {
    const mouseEvent = event as MouseEvent
    data.coordinates = { x: mouseEvent.clientX, y: mouseEvent.clientY }
  }
  
  if (event.type === 'submit') {
    const form = event.target as HTMLFormElement
    const formData = new FormData(form)
    data.formData = Object.fromEntries(formData.entries())
  }
  
  if (event.type === 'change') {
    const input = event.target as HTMLInputElement
    data.value = input.value
    data.type = input.type
  }
  
  return data
}

const clearInteractionLogs = () => {
  interactionLogs.value = []
}

const measurePerformance = () => {
  if (!previewIframe.value || !previewIframe.value.contentWindow) return

  try {
    const iframeWindow = previewIframe.value.contentWindow
    const performance = iframeWindow.performance
    
    if (performance) {
      const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
      
      if (navigation) {
        performanceMetrics.loadTime = Math.round(navigation.loadEventEnd - navigation.fetchStart)
        performanceMetrics.domReady = Math.round(navigation.domContentLoadedEventEnd - navigation.fetchStart)
      }
      
      // Get paint metrics
      const paintEntries = performance.getEntriesByType('paint')
      const firstPaint = paintEntries.find(entry => entry.name === 'first-paint')
      if (firstPaint) {
        performanceMetrics.firstPaint = Math.round(firstPaint.startTime)
      }
      
      // Get LCP if available
      if ('PerformanceObserver' in iframeWindow) {
        const observer = new iframeWindow.PerformanceObserver((list) => {
          const entries = list.getEntries()
          const lastEntry = entries[entries.length - 1]
          if (lastEntry) {
            performanceMetrics.lcp = Math.round(lastEntry.startTime)
          }
        })
        
        try {
          observer.observe({ entryTypes: ['largest-contentful-paint'] })
        } catch (e) {
          // LCP not supported
        }
      }
      
      emit('performanceUpdate', performanceMetrics)
    }
  } catch (error) {
    console.warn('Could not measure performance:', error)
  }
}

const formatTime = (timestamp: number): string => {
  return new Date(timestamp).toLocaleTimeString()
}

const handleLiveUpdate = (event: CustomEvent) => {
  if (!props.autoUpdate) return
  
  isUpdating.value = true
  
  // Debounce updates
  setTimeout(() => {
    refreshPreview()
  }, 500)
}

// Event listeners for real-time updates
onMounted(() => {
  // Listen for GrapeJS updates
  window.addEventListener('grapejs:preview-update', handleLiveUpdate)
  
  // Initialize preview
  if (props.pageId) {
    refreshPreview()
  }
  
  // Sync with editing service device
  previewDevice.value = realTimeEditingService.editingState.deviceMode
})

onUnmounted(() => {
  window.removeEventListener('grapejs:preview-update', handleLiveUpdate)
})

// Watch for device changes from editing service
watch(() => realTimeEditingService.editingState.deviceMode, (newDevice) => {
  previewDevice.value = newDevice
  refreshPreview()
})

// Watch for page ID changes
watch(() => props.pageId, (newPageId) => {
  if (newPageId) {
    refreshPreview()
  }
})
</script>

<style scoped>
.live-preview {
  @apply flex flex-col h-full bg-gray-50 dark:bg-gray-900;
}

.preview-header {
  @apply flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700;
}

.header-left {
  @apply flex items-center space-x-3;
}

.preview-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.preview-status {
  @apply flex items-center space-x-2;
}

.status-indicator {
  @apply w-2 h-2 rounded-full bg-gray-400;
}

.status-indicator.active {
  @apply bg-green-500;
}

.status-indicator.updating {
  @apply bg-yellow-500 animate-pulse;
}

.status-text {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.header-right {
  @apply flex items-center space-x-4;
}

.preview-controls {
  @apply flex items-center space-x-2;
}

.device-selector {
  @apply flex items-center space-x-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg;
}

.device-btn {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-600 rounded transition-colors;
}

.device-btn.active {
  @apply text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-600;
}

.action-btn {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors;
}

.action-btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.action-btn.active {
  @apply text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900;
}

.preview-container {
  @apply flex-1 flex items-center justify-center p-4 overflow-auto;
}

.preview-frame {
  @apply relative bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300;
}

.preview-frame.with-frame {
  @apply p-4;
}

.device-frame {
  @apply relative;
}

.device-frame::before {
  content: '';
  @apply absolute inset-0 border-4 border-gray-800 rounded-xl pointer-events-none z-10;
}

.device-desktop .device-frame::before {
  @apply border-8 rounded-lg;
}

.device-tablet .device-frame::before {
  @apply border-6 rounded-lg;
}

.device-mobile .device-frame::before {
  @apply border-4 rounded-xl;
}

.device-screen {
  @apply relative overflow-hidden rounded-lg;
}

.screen-content {
  @apply w-full h-full;
}

.preview-iframe {
  @apply w-full h-full border-0;
}

.preview-iframe.direct {
  @apply rounded-lg;
}

.loading-overlay {
  @apply absolute inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-20;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin;
}

.loading-text {
  @apply mt-2 text-sm text-gray-600;
}

.error-overlay {
  @apply absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-20;
}

.error-content {
  @apply flex flex-col items-center space-y-3 text-center;
}

.error-message {
  @apply text-sm text-gray-600;
}

.retry-btn {
  @apply px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded;
}

.interaction-panel {
  @apply w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col;
}

.panel-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.panel-title {
  @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.clear-btn {
  @apply px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded;
}

.interaction-logs {
  @apply flex-1 overflow-y-auto;
}

.no-interactions {
  @apply p-4 text-center;
}

.no-interactions p {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.hint {
  @apply text-xs text-gray-500 dark:text-gray-500 mt-1;
}

.log-entries {
  @apply space-y-2 p-4;
}

.log-entry {
  @apply p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border-l-4;
}

.log-entry.click {
  @apply border-blue-500;
}

.log-entry.submit {
  @apply border-green-500;
}

.log-entry.change {
  @apply border-yellow-500;
}

.log-entry.focus,
.log-entry.blur {
  @apply border-purple-500;
}

.log-entry.scroll {
  @apply border-gray-500;
}

.log-header {
  @apply flex items-center justify-between mb-1;
}

.log-type {
  @apply text-xs font-mono font-semibold uppercase;
}

.log-time {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.log-details {
  @apply space-y-1;
}

.log-element {
  @apply text-sm font-mono text-gray-700 dark:text-gray-300;
}

.log-data {
  @apply text-xs text-gray-600 dark:text-gray-400 font-mono;
}

.performance-metrics {
  @apply absolute top-4 right-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-4 min-w-64;
}

.metrics-header {
  @apply flex items-center justify-between mb-3;
}

.metrics-title {
  @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.close-metrics-btn {
  @apply p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
}

.metrics-grid {
  @apply grid grid-cols-2 gap-3;
}

.metric-item {
  @apply flex flex-col;
}

.metric-label {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.metric-value {
  @apply text-sm font-semibold text-gray-900 dark:text-white;
}
</style>