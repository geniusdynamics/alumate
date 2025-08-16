<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-4 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-4 scale-95"
    >
      <div
        v-if="showPrompt"
        class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
        @click.self="handleDismiss"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
        
        <!-- Install Prompt Modal -->
        <div class="relative w-full max-w-md transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10">
          <!-- Close Button -->
          <button
            @click="handleDismiss"
            class="absolute right-4 top-4 rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
          
          <!-- App Icon -->
          <div class="mb-4 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg">
              <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
          </div>
          
          <!-- Content -->
          <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Install Alumni Platform
            </h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
              Get the full app experience with offline access, push notifications, and faster loading.
            </p>
          </div>
          
          <!-- Features List -->
          <div class="mt-4 space-y-2">
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
              <CheckIcon class="mr-2 h-4 w-4 text-green-500" />
              Works offline
            </div>
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
              <CheckIcon class="mr-2 h-4 w-4 text-green-500" />
              Push notifications
            </div>
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
              <CheckIcon class="mr-2 h-4 w-4 text-green-500" />
              Faster loading
            </div>
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
              <CheckIcon class="mr-2 h-4 w-4 text-green-500" />
              Native app experience
            </div>
          </div>
          
          <!-- Actions -->
          <div class="mt-6 flex space-x-3">
            <button
              @click="handleInstall"
              :disabled="installing"
              class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed dark:focus:ring-offset-gray-900"
            >
              <span v-if="!installing" class="flex items-center justify-center">
                <ArrowDownTrayIcon class="mr-2 h-4 w-4" />
                Install App
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                Installing...
              </span>
            </button>
            <button
              @click="handleDismiss"
              class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900"
            >
              Maybe Later
            </button>
          </div>
          
          <!-- Don't show again -->
          <div class="mt-4 flex items-center justify-center">
            <label class="flex items-center text-xs text-gray-500 dark:text-gray-400">
              <input
                v-model="dontShowAgain"
                type="checkbox"
                class="mr-2 h-3 w-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
              >
              Don't show this again
            </label>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { XMarkIcon, CheckIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  autoShow: {
    type: Boolean,
    default: true
  },
  showDelay: {
    type: Number,
    default: 5000 // 5 seconds
  },
  dismissDelay: {
    type: Number,
    default: 30000 // 30 seconds auto-dismiss
  }
})

// Emits
const emit = defineEmits(['install', 'dismiss', 'error'])

// Reactive state
const showPrompt = ref(false)
const installing = ref(false)
const dontShowAgain = ref(false)
const deferredPrompt = ref(null)
const autoShowTimeout = ref(null)
const autoDismissTimeout = ref(null)

// Check if app is already installed
const isAppInstalled = ref(false)

// Methods
const checkInstallability = () => {
  // Check if app is already installed
  if (window.matchMedia('(display-mode: standalone)').matches) {
    isAppInstalled.value = true
    return false
  }
  
  // Check if user has dismissed permanently
  const dismissed = localStorage.getItem('pwa-install-dismissed')
  if (dismissed === 'permanent') {
    return false
  }
  
  // Check if recently dismissed (within 7 days)
  const lastDismissed = localStorage.getItem('pwa-install-last-dismissed')
  if (lastDismissed) {
    const daysSinceDismissed = (Date.now() - parseInt(lastDismissed)) / (1000 * 60 * 60 * 24)
    if (daysSinceDismissed < 7) {
      return false
    }
  }
  
  return true
}

const handleBeforeInstallPrompt = (e) => {
  // Prevent the mini-infobar from appearing on mobile
  e.preventDefault()
  
  // Stash the event so it can be triggered later
  deferredPrompt.value = e
  
  // Show custom install prompt if conditions are met
  if (props.autoShow && checkInstallability()) {
    schedulePromptDisplay()
  }
}

const schedulePromptDisplay = () => {
  if (autoShowTimeout.value) {
    clearTimeout(autoShowTimeout.value)
  }
  
  autoShowTimeout.value = setTimeout(() => {
    showInstallPrompt()
  }, props.showDelay)
}

const showInstallPrompt = () => {
  if (!deferredPrompt.value || isAppInstalled.value) return
  
  showPrompt.value = true
  
  // Auto-dismiss after specified delay
  if (props.dismissDelay > 0) {
    autoDismissTimeout.value = setTimeout(() => {
      handleDismiss()
    }, props.dismissDelay)
  }
  
  // Track prompt shown
  trackEvent('pwa_install_prompt_shown')
}

const handleInstall = async () => {
  if (!deferredPrompt.value) {
    emit('error', new Error('Install prompt not available'))
    return
  }
  
  installing.value = true
  
  try {
    // Show the install prompt
    deferredPrompt.value.prompt()
    
    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.value.userChoice
    
    if (outcome === 'accepted') {
      console.log('User accepted the install prompt')
      trackEvent('pwa_install_accepted')
      emit('install', { outcome })
    } else {
      console.log('User dismissed the install prompt')
      trackEvent('pwa_install_declined')
    }
    
    // Clear the deferredPrompt
    deferredPrompt.value = null
    
  } catch (error) {
    console.error('Error during app installation:', error)
    emit('error', error)
  } finally {
    installing.value = false
    showPrompt.value = false
    clearTimeouts()
  }
}

const handleDismiss = () => {
  showPrompt.value = false
  clearTimeouts()
  
  // Save dismissal preference
  if (dontShowAgain.value) {
    localStorage.setItem('pwa-install-dismissed', 'permanent')
    trackEvent('pwa_install_dismissed_permanent')
  } else {
    localStorage.setItem('pwa-install-last-dismissed', Date.now().toString())
    trackEvent('pwa_install_dismissed_temporary')
  }
  
  emit('dismiss', { permanent: dontShowAgain.value })
}

const handleAppInstalled = () => {
  console.log('PWA was installed')
  isAppInstalled.value = true
  showPrompt.value = false
  clearTimeouts()
  
  // Clear dismissal preferences
  localStorage.removeItem('pwa-install-dismissed')
  localStorage.removeItem('pwa-install-last-dismissed')
  
  trackEvent('pwa_install_completed')
  
  // Show success message
  showInstallSuccessMessage()
}

const showInstallSuccessMessage = () => {
  // Create a temporary success notification
  const notification = document.createElement('div')
  notification.className = 'fixed top-4 right-4 z-50 rounded-lg bg-green-500 px-4 py-2 text-white shadow-lg'
  notification.textContent = '✅ App installed successfully!'
  
  document.body.appendChild(notification)
  
  setTimeout(() => {
    notification.remove()
  }, 3000)
}

const clearTimeouts = () => {
  if (autoShowTimeout.value) {
    clearTimeout(autoShowTimeout.value)
    autoShowTimeout.value = null
  }
  
  if (autoDismissTimeout.value) {
    clearTimeout(autoDismissTimeout.value)
    autoDismissTimeout.value = null
  }
}

const trackEvent = (eventName, data = {}) => {
  // Track analytics event
  if (window.gtag) {
    window.gtag('event', eventName, data)
  }
  
  // Custom event for internal tracking
  window.dispatchEvent(new CustomEvent('pwa-install-event', {
    detail: { event: eventName, data }
  }))
}

// Public methods
const show = () => {
  if (deferredPrompt.value && checkInstallability()) {
    showInstallPrompt()
  }
}

const hide = () => {
  handleDismiss()
}

const isInstallable = () => {
  return !!deferredPrompt.value && !isAppInstalled.value
}

// Lifecycle
onMounted(() => {
  // Listen for beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
  
  // Listen for appinstalled event
  window.addEventListener('appinstalled', handleAppInstalled)
  
  // Check if already installed
  isAppInstalled.value = window.matchMedia('(display-mode: standalone)').matches
})

onUnmounted(() => {
  window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
  window.removeEventListener('appinstalled', handleAppInstalled)
  clearTimeouts()
})

// Expose public methods
defineExpose({
  show,
  hide,
  isInstallable,
  isInstalled: () => isAppInstalled.value
})
</script>

<style scoped>
/* Additional custom styles if needed */
</style>