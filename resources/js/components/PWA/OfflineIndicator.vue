<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-full"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-full"
    >
      <div
        v-if="showIndicator"
        :class="[
          'fixed top-0 left-0 right-0 z-50 px-4 py-3 text-center text-sm font-medium shadow-lg',
          indicatorClasses
        ]"
      >
        <div class="flex items-center justify-center space-x-2">
          <!-- Status Icon -->
          <component :is="statusIcon" class="h-4 w-4 flex-shrink-0" />
          
          <!-- Status Message -->
          <span>{{ statusMessage }}</span>
          
          <!-- Retry Button (when offline) -->
          <button
            v-if="!isOnline && showRetryButton"
            @click="handleRetry"
            :disabled="retrying"
            class="ml-2 rounded bg-white/20 px-2 py-1 text-xs hover:bg-white/30 disabled:opacity-50"
          >
            <span v-if="!retrying">Retry</span>
            <span v-else class="flex items-center">
              <svg class="mr-1 h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
              </svg>
              Retrying...
            </span>
          </button>
          
          <!-- Close Button -->
          <button
            v-if="dismissible"
            @click="handleDismiss"
            class="ml-2 rounded-full p-1 hover:bg-white/20"
          >
            <XMarkIcon class="h-3 w-3" />
          </button>
        </div>
        
        <!-- Connection Quality Info -->
        <div v-if="showConnectionInfo && connectionInfo" class="mt-1 text-xs opacity-75">
          {{ connectionInfo }}
        </div>
        
        <!-- Offline Actions Queue -->
        <div v-if="!isOnline && queuedActions > 0" class="mt-1 text-xs opacity-75">
          {{ queuedActions }} action{{ queuedActions !== 1 ? 's' : '' }} queued for sync
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { 
  WifiIcon, 
  ExclamationTriangleIcon, 
  CheckCircleIcon,
  XMarkIcon,
  SignalIcon,
  SignalSlashIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  showOnline: {
    type: Boolean,
    default: false
  },
  showOffline: {
    type: Boolean,
    default: true
  },
  showConnectionQuality: {
    type: Boolean,
    default: true
  },
  autoHideDelay: {
    type: Number,
    default: 3000 // 3 seconds for online status
  },
  dismissible: {
    type: Boolean,
    default: true
  },
  showRetryButton: {
    type: Boolean,
    default: true
  },
  showQueuedActions: {
    type: Boolean,
    default: true
  }
})

// Emits
const emit = defineEmits(['online', 'offline', 'retry', 'dismiss'])

// Reactive state
const isOnline = ref(navigator.onLine)
const showIndicator = ref(false)
const retrying = ref(false)
const dismissed = ref(false)
const queuedActions = ref(0)
const connectionQuality = ref(null)
const autoHideTimeout = ref(null)

// Network connection info
const connection = ref(navigator.connection || navigator.mozConnection || navigator.webkitConnection)

// Computed properties
const statusIcon = computed(() => {
  if (!isOnline.value) return SignalSlashIcon
  if (connectionQuality.value === 'slow') return ExclamationTriangleIcon
  if (connectionQuality.value === 'fast') return CheckCircleIcon
  return WifiIcon
})

const statusMessage = computed(() => {
  if (!isOnline.value) {
    return 'You\'re offline. Some features may be limited.'
  }
  
  if (connectionQuality.value === 'slow') {
    return 'Slow connection detected. Some features may be slower.'
  }
  
  if (connectionQuality.value === 'fast') {
    return 'You\'re back online!'
  }
  
  return 'Connected'
})

const indicatorClasses = computed(() => {
  if (!isOnline.value) {
    return 'bg-red-500 text-white'
  }
  
  if (connectionQuality.value === 'slow') {
    return 'bg-yellow-500 text-white'
  }
  
  return 'bg-green-500 text-white'
})

const connectionInfo = computed(() => {
  if (!props.showConnectionQuality || !connection.value) return null
  
  const conn = connection.value
  const effectiveType = conn.effectiveType || 'unknown'
  const downlink = conn.downlink ? `${conn.downlink} Mbps` : ''
  const rtt = conn.rtt ? `${conn.rtt}ms` : ''
  
  const parts = [effectiveType.toUpperCase()]
  if (downlink) parts.push(downlink)
  if (rtt) parts.push(rtt)
  
  return parts.join(' • ')
})

// Methods
const updateOnlineStatus = () => {
  const wasOnline = isOnline.value
  isOnline.value = navigator.onLine
  
  if (wasOnline !== isOnline.value) {
    dismissed.value = false
    
    if (isOnline.value) {
      handleOnline()
    } else {
      handleOffline()
    }
  }
}

const handleOnline = () => {
  console.log('Network: Online')
  updateConnectionQuality()
  
  if (props.showOnline && !dismissed.value) {
    showIndicator.value = true
    scheduleAutoHide()
  }
  
  // Trigger sync of queued actions
  syncQueuedActions()
  
  emit('online', {
    connectionQuality: connectionQuality.value,
    connectionInfo: connectionInfo.value
  })
}

const handleOffline = () => {
  console.log('Network: Offline')
  connectionQuality.value = null
  
  if (props.showOffline && !dismissed.value) {
    showIndicator.value = true
    clearAutoHide()
  }
  
  emit('offline')
}

const updateConnectionQuality = () => {
  if (!connection.value) return
  
  const conn = connection.value
  const effectiveType = conn.effectiveType
  const downlink = conn.downlink || 0
  const rtt = conn.rtt || 0
  
  // Determine connection quality
  if (effectiveType === '4g' && downlink > 1.5 && rtt < 150) {
    connectionQuality.value = 'fast'
  } else if (effectiveType === 'slow-2g' || downlink < 0.5 || rtt > 500) {
    connectionQuality.value = 'slow'
  } else {
    connectionQuality.value = 'normal'
  }
}

const handleRetry = async () => {
  retrying.value = true
  
  try {
    // Attempt to fetch a small resource to test connectivity
    const response = await fetch('/api/ping', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      // Connection restored
      isOnline.value = true
      handleOnline()
    }
  } catch (error) {
    console.log('Retry failed:', error)
  } finally {
    retrying.value = false
  }
  
  emit('retry')
}

const handleDismiss = () => {
  dismissed.value = true
  showIndicator.value = false
  clearAutoHide()
  emit('dismiss')
}

const scheduleAutoHide = () => {
  clearAutoHide()
  
  if (props.autoHideDelay > 0) {
    autoHideTimeout.value = setTimeout(() => {
      if (isOnline.value) {
        showIndicator.value = false
      }
    }, props.autoHideDelay)
  }
}

const clearAutoHide = () => {
  if (autoHideTimeout.value) {
    clearTimeout(autoHideTimeout.value)
    autoHideTimeout.value = null
  }
}

const syncQueuedActions = async () => {
  // Trigger service worker background sync
  if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
    try {
      const registration = await navigator.serviceWorker.getRegistration()
      if (registration) {
        await registration.sync.register('background-sync')
      }
    } catch (error) {
      console.error('Background sync registration failed:', error)
    }
  }
  
  // Update queued actions count
  updateQueuedActionsCount()
}

const updateQueuedActionsCount = () => {
  // This would typically get the count from IndexedDB or localStorage
  // For now, we'll simulate it
  const stored = localStorage.getItem('offline-actions-queue')
  if (stored) {
    try {
      const actions = JSON.parse(stored)
      queuedActions.value = Array.isArray(actions) ? actions.length : 0
    } catch (error) {
      queuedActions.value = 0
    }
  } else {
    queuedActions.value = 0
  }
}

const handleConnectionChange = () => {
  updateConnectionQuality()
  
  // Show indicator if connection quality changed significantly
  if (isOnline.value && !dismissed.value) {
    if (connectionQuality.value === 'slow' || connectionQuality.value === 'fast') {
      showIndicator.value = true
      scheduleAutoHide()
    }
  }
}

// Watch for changes
watch(isOnline, (newValue) => {
  if (!newValue) {
    // When going offline, clear any auto-hide timeout
    clearAutoHide()
  }
})

// Lifecycle
onMounted(() => {
  // Listen for online/offline events
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
  
  // Listen for connection changes
  if (connection.value) {
    connection.value.addEventListener('change', handleConnectionChange)
  }
  
  // Listen for service worker messages about queued actions
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data.type === 'OFFLINE_ACTION_QUEUED') {
        updateQueuedActionsCount()
      } else if (event.data.type === 'BACKGROUND_SYNC_SUCCESS') {
        queuedActions.value = 0
      }
    })
  }
  
  // Initial setup
  updateConnectionQuality()
  updateQueuedActionsCount()
  
  // Show initial status if offline
  if (!isOnline.value && props.showOffline) {
    showIndicator.value = true
  }
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
  
  if (connection.value) {
    connection.value.removeEventListener('change', handleConnectionChange)
  }
  
  clearAutoHide()
})

// Public methods
const show = () => {
  dismissed.value = false
  showIndicator.value = true
}

const hide = () => {
  showIndicator.value = false
  clearAutoHide()
}

const refresh = () => {
  updateOnlineStatus()
  updateConnectionQuality()
  updateQueuedActionsCount()
}

// Expose public methods
defineExpose({
  show,
  hide,
  refresh,
  isOnline: () => isOnline.value,
  connectionQuality: () => connectionQuality.value,
  queuedActions: () => queuedActions.value
})
</script>

<style scoped>
/* Additional custom styles if needed */
</style>