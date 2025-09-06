<template>
  <div class="mobile-template-renderer">
    <!-- Mobile Header -->
    <header class="mobile-header">
      <div class="header-content">
        <button
          @click="toggleDeviceMenu"
          class="device-toggle-btn"
          :aria-expanded="deviceMenuOpen"
          aria-label="Switch device view"
        >
          <component :is="getDeviceIcon(currentViewport)" class="device-icon" />
          <span class="viewport-label">{{ getViewportLabel(currentViewport) }}</span>
          <svg class="menu-arrow" :class="{ 'rotate-180': deviceMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div class="header-actions">
          <button
            @click="toggleOrientation"
            class="orientation-btn"
            :aria-label="`Switch to ${isLandscape ? 'portrait' : 'landscape'} orientation`"
          >
            <svg class="orientation-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    :d="isLandscape
                        ? 'M6 14l6-6 6 6'
                        : 'M18 10l-6 6-6-6'" />
            </svg>
          </button>

          <button
            @click="toggleTouchMode"
            :class="['touch-mode-btn', { 'active': touchModeEnabled }]"
            :aria-pressed="touchModeEnabled"
            aria-label="Toggle touch interaction mode"
          >
            <svg class="touch-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Device Menu -->
      <transition name="slide-down">
        <div v-if="deviceMenuOpen" class="device-menu">
          <button
            v-for="viewport in availableViewports"
            :key="viewport.key"
            @click="switchViewport(viewport.key)"
            :class="['device-option', { 'active': currentViewport === viewport.key }]"
            :aria-current="currentViewport === viewport.key ? 'true' : 'false'"
          >
            <component :is="viewport.icon" class="device-option-icon" />
            <div class="device-option-info">
              <span class="device-name">{{ viewport.name }}</span>
              <span class="device-specs">{{ viewport.width }}×{{ viewport.height }}</span>
            </div>
          </button>
        </div>
      </transition>
    </header>

    <!-- Mobile Preview Container -->
    <div class="mobile-preview-container" :class="containerClasses">
      <!-- Device Frame -->
      <div class="device-frame" :style="deviceFrameStyle">
        <!-- Screen Content -->
        <div
          ref="screenRef"
          class="screen-content"
          :class="{
            'touch-interactive': touchModeEnabled,
            'viewport-mobile': currentViewport === 'mobile'
          }"
          :style="screenContentStyle"
        >
          <!-- Loading State -->
          <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p class="loading-text">Rendering {{ getViewportLabel(currentViewport) }} view...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="renderError" class="error-state">
            <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h3 class="error-title">Rendering Error</h3>
            <p class="error-message">{{ renderError }}</p>
            <button @click="retryRender" class="retry-btn">Retry</button>
          </div>

          <!-- Rendered Content -->
          <div v-else-if="renderedContent" class="rendered-template" v-html="renderedContent"></div>

          <!-- Empty State -->
          <div v-else class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h3 class="empty-title">No Template Selected</h3>
            <p class="empty-message">Select a template to see the mobile preview</p>
          </div>
        </div>

        <!-- Touch Indicators (when touch mode is active) -->
        <div v-if="touchModeEnabled && touchIndicators.length > 0" class="touch-indicators">
          <div
            v-for="(indicator, index) in touchIndicators"
            :key="`touch-${index}`"
            class="touch-indicator"
            :style="{
              left: `${indicator.x}px`,
              top: `${indicator.y}px`,
              width: `${indicator.size}px`,
              height: `${indicator.size}px`
            }"
          >
            <div class="touch-ripple"></div>
          </div>
        </div>
      </div>

      <!-- Touch Mode Instructions -->
      <transition name="fade">
        <div v-if="touchModeEnabled && !hasDemoCompleted" class="touch-instructions">
          <div class="instructions-content">
            <h4 class="instructions-title">Touch Mode Active</h4>
            <p class="instructions-text">Tap interactive elements to see touch feedback and gestures.</p>
            <button @click="completeDemo" class="dismiss-btn">Got it</button>
          </div>
        </div>
      </transition>
    </div>

    <!-- Mobile Controls Footer -->
    <footer class="mobile-controls-footer">
      <div class="controls-grid">
        <button
          @click="zoomIn"
          :disabled="zoomLevel >= maxZoom"
          class="control-btn zoom-in"
          aria-label="Zoom in"
        >
          <svg class="control-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
        </button>

        <button
          @click="resetZoom"
          :disabled="zoomLevel === 1"
          class="control-btn reset-zoom"
          aria-label="Reset zoom"
        >
          <span class="zoom-level">{{ Math.round(zoomLevel * 100) }}%</span>
        </button>

        <button
          @click="zoomOut"
          :disabled="zoomLevel <= minZoom"
          class="control-btn zoom-out"
          aria-label="Zoom out"
        >
          <svg class="control-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
          </svg>
        </button>
      </div>

      <div class="footer-info">
        <span class="device-info">{{ getDeviceInfoString() }}</span>
        <span class="performance-info" v-if="renderTime">Rendered in {{ renderTime }}ms</span>
      </div>
    </footer>

    <!-- Keyboard Shortcuts (Mobile) -->
    <transition name="slide-up">
      <div v-if="showKeyboardShortcuts" class="keyboard-shortcuts-mobile">
        <div class="shortcuts-header">
          <h4 class="shortcuts-title">Mobile Shortcuts</h4>
          <button @click="toggleKeyboardShortcuts" class="close-shortcuts-btn">
            <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="shortcuts-list">
          <div class="shortcut-group">
            <h5 class="shortcut-group-title">Navigation</h5>
            <div class="shortcut-item">
              <kbd class="shortcut-key">Tab</kbd>
              <span class="shortcut-desc">Focus next element</span>
            </div>
            <div class="shortcut-item">
              <kbd class="shortcut-key">Shift+Tab</kbd>
              <span class="shortcut-desc">Focus previous element</span>
            </div>
          </div>

          <div class="shortcut-group">
            <h5 class="shortcut-group-title">Actions</h5>
            <div class="shortcut-item">
              <kbd class="shortcut-key">Enter</kbd>
              <span class="shortcut-desc">Activate focused element</span>
            </div>
            <div class="shortcut-item">
              <kbd class="shortcut-key">Space</kbd>
              <span class="shortcut-desc">Activate button or toggle</span>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

// Device and Viewport Icons
import {
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  DeviceTabletIcon,
} from '@heroicons/vue/24/outline'

// Types
import type {
  Template,
  ViewportType
} from '@/types/components'

// Custom touch interaction event type
interface TouchInteractionEvent {
  type: 'touchstart' | 'touchend' | 'touchmove'
  touches: number
  coordinates: Array<{ x: number; y: number }>
}

// Props
interface Props {
  template?: Template | null
  initialViewport?: ViewportType
  enableTouchMode?: boolean
  showDeviceFrame?: boolean
  onRendered?: (templateId: number, viewport: ViewportType) => void
  onTouchInteraction?: (event: TouchInteractionEvent) => void
}

const props = withDefaults(defineProps<Props>(), {
  template: null,
  initialViewport: 'mobile',
  enableTouchMode: true,
  showDeviceFrame: true
})

// Emits
const emit = defineEmits<{
  viewportChanged: [viewport: ViewportType]
  touchInteraction: [event: TouchInteractionEvent]
  error: [error: string]
  rendered: [data: { templateId: number; viewport: ViewportType; renderTime: number }]
}>()

// Reactive State
const currentViewport = ref<ViewportType>(props.initialViewport)
const deviceMenuOpen = ref(false)
const touchModeEnabled = ref(props.enableTouchMode)
const loading = ref(false)
const renderError = ref('')
const renderedContent = ref('')
const renderTime = ref<number | null>(null)
const zoomLevel = ref(1)
const showKeyboardShortcuts = ref(false)
const hasDemoCompleted = ref(false)
const touchIndicators = ref<Array<{x: number; y: number; size: number}>>([])

// Refs
const screenRef = ref<HTMLElement | null>(null)

// Device Configurations
const availableViewports = [
  {
    key: 'mobile' as ViewportType,
    name: 'Mobile',
    width: 375,
    height: 667,
    icon: DevicePhoneMobileIcon
  },
  {
    key: 'tablet' as ViewportType,
    name: 'Tablet',
    width: 768,
    height: 1024,
    icon: DeviceTabletIcon
  },
  {
    key: 'desktop' as ViewportType,
    name: 'Desktop',
    width: 1920,
    height: 1080,
    icon: ComputerDesktopIcon
  }
]

// Constants
const minZoom = 0.5
const maxZoom = 2.0
const zoomStep = 0.25

// Computed Properties
const containerClasses = computed(() => ({
  'device-frame-enabled': props.showDeviceFrame,
  [`viewport-${currentViewport.value}`]: true,
  'touch-mode-active': touchModeEnabled.value
}))

const deviceFrameStyle = computed(() => {
  const viewport = availableViewports.find(v => v.key === currentViewport.value)
  if (!viewport) return {}

  const width = viewport.width
  const height = viewport.height

  // Scale device frame to fit container better
  const scale = Math.min(400 / width, 600 / height, 1)

  return {
    width: `${width * scale}px`,
    height: `${height * scale}px`,
    '--device-scale': scale
  }
})

const screenContentStyle = computed(() => {
  const baseWidth = availableViewports.find(v => v.key === currentViewport.value)?.width || 375

  return {
    transform: `scale(${zoomLevel.value})`,
    transformOrigin: 'top center',
    width: `${baseWidth}px`,
    maxWidth: `${baseWidth}px`
  }
})

// Methods
const getDeviceIcon = (viewport: ViewportType) => {
  return availableViewports.find(v => v.key === viewport)?.icon || ComputerDesktopIcon
}

const getViewportLabel = (viewport: ViewportType): string => {
  return availableViewports.find(v => v.key === viewport)?.name || 'Unknown'
}

const getDeviceInfoString = (): string => {
  const viewport = availableViewports.find(v => v.key === currentViewport.value)
  if (!viewport) return 'Unknown Device'

  return `${viewport.name} (${viewport.width}×${viewport.height})`
}

const toggleDeviceMenu = () => {
  deviceMenuOpen.value = !deviceMenuOpen.value
}

const switchViewport = (viewport: ViewportType) => {
  if (currentViewport.value !== viewport) {
    currentViewport.value = viewport
    deviceMenuOpen.value = false

    emit('viewportChanged', viewport)
    renderTemplate()
  }
}

const toggleTouchMode = () => {
  touchModeEnabled.value = !touchModeEnabled.value
}

const completeDemo = () => {
  hasDemoCompleted.value = true
}

const zoomIn = () => {
  const newZoom = Math.min(zoomLevel.value + zoomStep, maxZoom)
  if (newZoom !== zoomLevel.value) {
    zoomLevel.value = newZoom
  }
}

const resetZoom = () => {
  zoomLevel.value = 1
}

const zoomOut = () => {
  const newZoom = Math.max(zoomLevel.value - zoomStep, minZoom)
  if (newZoom !== zoomLevel.value) {
    zoomLevel.value = newZoom
  }
}

const toggleKeyboardShortcuts = () => {
  showKeyboardShortcuts.value = !showKeyboardShortcuts.value
}

const retryRender = () => {
  renderError.value = ''
  renderTemplate()
}

const renderTemplate = async () => {
  if (!props.template?.id) {
    renderError.value = 'No template provided'
    return
  }

  try {
    loading.value = true
    renderError.value = ''
    const startTime = performance.now()

    // Simulate template rendering
    // In real implementation, this would call the API
    await new Promise(resolve => setTimeout(resolve, 1000)) // Simulate API call

    const mockHtml = `
      <div class="mobile-template-container">
        <header class="mobile-header-preview">
          <h1 class="mobile-title">Template Title</h1>
          <p class="mobile-subtitle">This is a responsive mobile preview</p>
        </header>

        <main class="mobile-content">
          <div class="mobile-section">
            <h2>Sample Section</h2>
            <p>Mobile-optimized content for ${currentViewport.value} viewport</p>
          </div>

          <div class="mobile-form-section">
            <input type="text" placeholder="Mobile-friendly input" class="mobile-input">
            <button class="mobile-button cta-button" type="button">Call to Action</button>
          </div>
        </main>

        <footer class="mobile-footer">
          <p>&copy; 2025 Mobile Template</p>
        </footer>
      </div>
    `

    renderedContent.value = mockHtml

    const endTime = performance.now()
    renderTime.value = Math.round(endTime - startTime)

    emit('rendered', {
      templateId: props.template.id,
      viewport: currentViewport.value,
      renderTime: renderTime.value
    })

  } catch (error) {
    renderError.value = error instanceof Error ? error.message : 'Rendering failed'
    emit('error', renderError.value)
  } finally {
    loading.value = false
  }
}

// Touch Event Handlers
const handleTouchStart = (event: TouchEvent) => {
  if (!touchModeEnabled.value) return

  const touches = Array.from(event.touches)
  touchIndicators.value = touches.map(touch => ({
    x: touch.clientX,
    y: touch.clientY,
    size: 44 // Minimum touch target size
  }))

  emit('touchInteraction', {
    type: 'touchstart',
    touches: touches.length,
    coordinates: touches.map(t => ({ x: t.clientX, y: t.clientY }))
  })
}

const handleTouchEnd = (event: TouchEvent) => {
  if (!touchModeEnabled.value) return

  touchIndicators.value = []
  emit('touchInteraction', {
    type: 'touchend',
    touches: 0,
    coordinates: []
  })
}

const handleTouchMove = (event: TouchEvent) => {
  if (!touchModeEnabled.value) return

  // Update touch indicator positions
  const touches = Array.from(event.touches)
  touches.forEach((touch, index) => {
    if (touchIndicators.value[index]) {
      touchIndicators.value[index].x = touch.clientX
      touchIndicators.value[index].y = touch.clientY
    }
  })
}

// Keyboard Shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  // Prevent default for handled shortcuts
  switch (event.key.toLowerCase()) {
    case 'escape':
      showKeyboardShortcuts.value = false
      deviceMenuOpen.value = false
      break

    case 'tab':
      // Let default tab behavior work for accessibility
      break

    case '=':
    case '+':
      if (event.ctrlKey || event.metaKey) {
        event.preventDefault()
        zoomIn()
      }
      break

    case '-':
      if (event.ctrlKey || event.metaKey) {
        event.preventDefault()
        zoomOut()
      }
      break

    case '0':
      if (event.ctrlKey || event.metaKey) {
        event.preventDefault()
        resetZoom()
      }
      break

    case '?':
      if (event.ctrlKey || event.metaKey) {
        event.preventDefault()
        toggleKeyboardShortcuts()
      }
      break
  }
}

// Lifecycle Hooks
onMounted(() => {
  // Add event listeners
  if (screenRef.value) {
    screenRef.value.addEventListener('touchstart', handleTouchStart, { passive: true })
    screenRef.value.addEventListener('touchend', handleTouchEnd, { passive: true })
    screenRef.value.addEventListener('touchmove', handleTouchMove, { passive: true })
  }

  document.addEventListener('keydown', handleKeydown)

  // Initial render
  if (props.template) {
    renderTemplate()
  }
})

onBeforeUnmount(() => {
  // Remove event listeners
  if (screenRef.value) {
    screenRef.value.removeEventListener('touchstart', handleTouchStart)
    screenRef.value.removeEventListener('touchend', handleTouchEnd)
    screenRef.value.removeEventListener('touchmove', handleTouchMove)
  }

  document.removeEventListener('keydown', handleKeydown)
})

// Watchers
watch(() => props.template, (newTemplate) => {
  if (newTemplate) {
    renderTemplate()
  } else {
    renderedContent.value = ''
    renderError.value = ''
  }
})

watch(() => props.enableTouchMode, (newValue) => {
  touchModeEnabled.value = newValue
})
</script>

<style scoped>
.mobile-template-renderer {
  @apply h-full flex flex-col bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800;
}

/* Mobile Header */
.mobile-header {
  @apply bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-4 py-3;
}

.header-content {
  @apply flex items-center justify-between;
}

.device-toggle-btn {
  @apply flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg;
  @apply bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300;
  @apply hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.device-icon {
  @apply w-4 h-4;
}

.menu-arrow {
  @apply w-4 h-4 transition-transform duration-200;
}

.header-actions {
  @apply flex items-center gap-2;
}

.orientation-btn,
.touch-mode-btn {
  @apply p-2 rounded-lg transition-colors;
  @apply hover:bg-slate-100 dark:hover:bg-slate-700;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.touch-mode-btn.active {
  @apply bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300;
}

.orientation-icon {
  @apply w-4 h-4;
}

/* Device Menu */
.device-menu {
  @apply mt-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg py-2;
}

.device-option {
  @apply w-full flex items-center gap-3 px-4 py-3 text-sm;
  @apply hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.device-option.active {
  @apply bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300;
}

.device-option-icon {
  @apply w-5 h-5 text-slate-500 dark:text-slate-400;
}

.device-option-info {
  @apply flex flex-col;
}

.device-name {
  @apply font-medium text-slate-900 dark:text-slate-100;
}

.device-specs {
  @apply text-xs text-slate-500 dark:text-slate-400;
}

/* Mobile Preview Container */
.mobile-preview-container {
  @apply flex-1 flex items-center justify-center p-6 overflow-hidden;
}

.device-frame {
  @apply bg-black rounded-lg border-4 border-slate-300 dark:border-slate-600 p-2;
  @apply shadow-2xl transform transition-transform duration-300;
}

.screen-content {
  @apply bg-white dark:bg-slate-50 rounded w-full h-full overflow-auto;
  @apply relative transform-gpu;
}

.touch-interactive {
  @apply cursor-pointer select-none;
}

/* Loading State */
.loading-state {
  @apply flex flex-col items-center justify-center h-full;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4;
}

.loading-text {
  @apply text-slate-600 dark:text-slate-400;
}

/* Error State */
.error-state {
  @apply flex flex-col items-center justify-center h-full text-center p-8;
}

.error-icon {
  @apply w-12 h-12 text-red-500 mb-4;
}

.error-title {
  @apply text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2;
}

.error-message {
  @apply text-slate-600 dark:text-slate-400 mb-4;
}

.retry-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

/* Empty State */
.empty-state {
  @apply flex flex-col items-center justify-center h-full text-center p-8;
}

.empty-icon {
  @apply w-16 h-16 text-slate-400 mb-4;
}

.empty-title {
  @apply text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2;
}

.empty-message {
  @apply text-slate-600 dark:text-slate-400;
}

/* Touch Indicators */
.touch-indicators {
  @apply absolute inset-0 pointer-events-none z-10;
}

.touch-indicator {
  @apply absolute rounded-full border-2 border-blue-500 bg-blue-500 bg-opacity-20;
  @apply pointer-events-none animate-ping;
}

.touch-ripple {
  @apply absolute inset-0 rounded-full border-2 border-blue-500 animate-ping;
}

/* Touch Instructions */
.touch-instructions {
  @apply absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-slate-800 text-white px-4 py-3 rounded-lg shadow-lg z-20;
}

.instructions-content {
  @apply text-center;
}

.instructions-title {
  @apply text-sm font-semibold mb-1;
}

.instructions-text {
  @apply text-xs mb-2;
}

.dismiss-btn {
  @apply text-xs bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded transition-colors;
}

/* Mobile Controls Footer */
.mobile-controls-footer {
  @apply bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 px-6 py-4;
}

.controls-grid {
  @apply flex items-center justify-center gap-4 mb-2;
}

.control-btn {
  @apply p-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
  @apply hover:bg-slate-100 dark:hover:bg-slate-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.zoom-in,
.zoom-out {
  @apply bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300;
}

.reset-zoom {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.control-icon {
  @apply w-5 h-5;
}

.zoom-level {
  @apply font-semibold;
}

.footer-info {
  @apply flex items-center justify-between text-sm text-slate-600 dark:text-slate-400;
}

.device-info {
  @apply font-medium;
}

.performance-info {
  @apply text-right;
}

/* Keyboard Shortcuts Mobile */
.keyboard-shortcuts-mobile {
  @apply fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 z-30;
  max-height: 80vh;
  overflow-y: auto;
}

.shortcuts-header {
  @apply flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700;
}

.shortcuts-title {
  @apply text-lg font-semibold text-slate-900 dark:text-slate-100;
}

.close-shortcuts-btn {
  @apply p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors;
}

.close-icon {
  @apply w-5 h-5;
}

.shortcuts-list {
  @apply p-4 space-y-6;
}

.shortcut-group {
  @apply space-y-3;
}

.shortcut-group-title {
  @apply text-sm font-medium text-slate-700 dark:text-slate-300;
}

.shortcut-item {
  @apply flex items-center justify-between py-2;
}

.shortcut-key {
  @apply inline-flex items-center px-2 py-1 text-xs bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-300 rounded;
}

.shortcut-desc {
  @apply text-sm text-slate-600 dark:text-slate-400;
}

/* Transitions */
.slide-down-enter-active,
.slide-down-leave-active {
  @apply transition-all duration-200;
}

.slide-down-enter-from,
.slide-down-leave-to {
  @apply opacity-0 -translate-y-2;
}

.fade-enter-active,
.fade-leave-active {
  @apply transition-opacity duration-300;
}

.fade-enter-from,
.fade-leave-to {
  @apply opacity-0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  @apply transition-all duration-200;
}

.slide-up-enter-from,
.slide-up-leave-to {
  @apply opacity-0 translate-y-4;
}

/* Responsive Design */
@media (max-width: 640px) {
  .mobile-header {
    @apply px-3 py-2;
  }

  .device-toggle-btn {
    @apply px-2 py-1 text-xs;
  }

  .header-actions {
    @apply gap-1;
  }

  .mobile-preview-container {
    @apply p-3;
  }

  .device-frame {
    @apply border-2 p-1;
  }
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
  .device-frame {
    @apply border-slate-900 dark:border-slate-100;
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner,
  .touch-indicator,
  .touch-ripple {
    @apply animate-none;
  }

  .menu-arrow {
    @apply transition-none;
  }

  * {
    @apply transition-none;
  }
}

/* Touch Device Optimizations */
@media (pointer: coarse) {
  .device-toggle-btn,
  .control-btn {
    @apply min-h-12 min-w-12;
  }

  .touch-instructions {
    @apply px-6 py-4;
  }

  .instructions-text {
    @apply text-sm;
  }
}
</style>