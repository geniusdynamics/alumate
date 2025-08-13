<template>
  <div>
    <!-- Install Prompt Component -->
    <InstallPrompt
      ref="installPrompt"
      :auto-show="true"
      :show-delay="10000"
      :dismiss-delay="30000"
      @install="handleInstall"
      @dismiss="handleInstallDismiss"
      @error="handleInstallError"
    />
    
    <!-- Offline Indicator Component -->
    <OfflineIndicator
      ref="offlineIndicator"
      :show-online="true"
      :show-offline="true"
      :show-connection-quality="true"
      :auto-hide-delay="3000"
      :show-retry-button="true"
      :show-queued-actions="true"
      @online="handleOnline"
      @offline="handleOffline"
      @retry="handleRetry"
      @dismiss="handleOfflineDismiss"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import InstallPrompt from './InstallPrompt.vue'
import OfflineIndicator from './OfflineIndicator.vue'
import PushNotificationService from '../../Services/PushNotificationService.js'

// Component refs
const installPrompt = ref(null)
const offlineIndicator = ref(null)

// Services
let pushNotificationService = null

// Props
const props = defineProps({
  enablePushNotifications: {
    type: Boolean,
    default: true
  },
  enableInstallPrompt: {
    type: Boolean,
    default: true
  },
  enableOfflineIndicator: {
    type: Boolean,
    default: true
  }
})

// Emits
const emit = defineEmits([
  'pwa-ready',
  'app-installed',
  'push-subscribed',
  'offline-mode',
  'online-mode'
])

// Methods
const initializePWA = async () => {
  try {
    // Initialize push notification service
    if (props.enablePushNotifications) {
      pushNotificationService = new PushNotificationService()
      await pushNotificationService.init()
    }
    
    // Check if app is already installed
    const isInstalled = window.matchMedia('(display-mode: standalone)').matches
    
    // Emit PWA ready event
    emit('pwa-ready', {
      isInstalled,
      pushSupported: pushNotificationService?.isSupported() || false,
      serviceWorkerSupported: 'serviceWorker' in navigator
    })
    
    console.log('PWA Integration initialized successfully')
    
  } catch (error) {
    console.error('PWA initialization failed:', error)
  }
}

// Event handlers
const handleInstall = (event) => {
  console.log('App installation:', event)
  emit('app-installed', event)
  
  // Track installation
  if (window.gtag) {
    window.gtag('event', 'pwa_install_success', {
      event_category: 'PWA',
      event_label: 'App Installation'
    })
  }
}

const handleInstallDismiss = (event) => {
  console.log('Install prompt dismissed:', event)
  
  // Track dismissal
  if (window.gtag) {
    window.gtag('event', 'pwa_install_dismiss', {
      event_category: 'PWA',
      event_label: event.permanent ? 'Permanent' : 'Temporary'
    })
  }
}

const handleInstallError = (error) => {
  console.error('Install error:', error)
  
  // Track error
  if (window.gtag) {
    window.gtag('event', 'pwa_install_error', {
      event_category: 'PWA',
      event_label: error.message
    })
  }
}

const handleOnline = (event) => {
  console.log('Network online:', event)
  emit('online-mode', event)
  
  // Sync any queued offline actions
  if (window.pwaManager) {
    window.pwaManager.syncOfflineActions()
  }
}

const handleOffline = () => {
  console.log('Network offline')
  emit('offline-mode')
}

const handleRetry = () => {
  console.log('Connection retry attempted')
}

const handleOfflineDismiss = () => {
  console.log('Offline indicator dismissed')
}

// Public methods for parent components
const showInstallPrompt = () => {
  if (installPrompt.value) {
    installPrompt.value.show()
  }
}

const hideInstallPrompt = () => {
  if (installPrompt.value) {
    installPrompt.value.hide()
  }
}

const showOfflineIndicator = () => {
  if (offlineIndicator.value) {
    offlineIndicator.value.show()
  }
}

const hideOfflineIndicator = () => {
  if (offlineIndicator.value) {
    offlineIndicator.value.hide()
  }
}

const enablePushNotifications = async () => {
  if (pushNotificationService) {
    try {
      const subscription = await pushNotificationService.subscribe()
      emit('push-subscribed', subscription)
      return subscription
    } catch (error) {
      console.error('Push notification subscription failed:', error)
      throw error
    }
  }
  throw new Error('Push notification service not available')
}

const disablePushNotifications = async () => {
  if (pushNotificationService) {
    await pushNotificationService.unsubscribe()
  }
}

const updatePushPreferences = async (preferences) => {
  if (pushNotificationService) {
    await pushNotificationService.updatePreferences(preferences)
  }
}

const sendTestNotification = async () => {
  if (pushNotificationService) {
    await pushNotificationService.sendTestNotification()
  }
}

const getPWAStatus = () => {
  return {
    isInstalled: window.matchMedia('(display-mode: standalone)').matches,
    isInstallable: installPrompt.value?.isInstallable() || false,
    isOnline: navigator.onLine,
    pushSubscribed: pushNotificationService?.isSubscribed() || false,
    pushSupported: pushNotificationService?.isSupported() || false,
    serviceWorkerActive: !!navigator.serviceWorker.controller
  }
}

const getNetworkStatus = () => {
  if (offlineIndicator.value) {
    return {
      online: offlineIndicator.value.isOnline(),
      connectionQuality: offlineIndicator.value.connectionQuality(),
      queuedActions: offlineIndicator.value.queuedActions()
    }
  }
  
  return {
    online: navigator.onLine,
    connectionQuality: null,
    queuedActions: 0
  }
}

// Lifecycle
onMounted(() => {
  initializePWA()
  
  // Listen for app state changes
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && offlineIndicator.value) {
      // Refresh network status when app becomes visible
      offlineIndicator.value.refresh()
    }
  })
})

onUnmounted(() => {
  // Cleanup if needed
})

// Expose public methods
defineExpose({
  showInstallPrompt,
  hideInstallPrompt,
  showOfflineIndicator,
  hideOfflineIndicator,
  enablePushNotifications,
  disablePushNotifications,
  updatePushPreferences,
  sendTestNotification,
  getPWAStatus,
  getNetworkStatus
})
</script>

<style scoped>
/* Component-specific styles if needed */
</style>