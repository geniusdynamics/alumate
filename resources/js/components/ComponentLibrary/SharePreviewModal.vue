<template>
  <Teleport to="body">
    <div
      class="share-preview-modal fixed inset-0 z-50 overflow-y-auto"
      role="dialog"
      aria-labelledby="share-modal-title"
      aria-modal="true"
    >
      <!-- Backdrop -->
      <div 
        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        @click="handleBackdropClick"
        aria-hidden="true"
      ></div>
      
      <!-- Modal Container -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div 
          class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full"
          @click.stop
        >
          <!-- Modal Header -->
          <header class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h2 
                id="share-modal-title"
                class="text-xl font-semibold text-gray-900 dark:text-white"
              >
                Share Component Preview
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Share this preview with others or embed it in your documentation
              </p>
            </div>
            
            <button
              @click="handleClose"
              class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              aria-label="Close share modal"
            >
              <Icon name="x" class="h-5 w-5" />
            </button>
          </header>
          
          <!-- Modal Body -->
          <div class="p-6 space-y-6">
            <!-- Component Info -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
              <div class="flex items-center space-x-3">
                <Icon 
                  :name="getCategoryIcon(component.category)" 
                  class="h-8 w-8 text-gray-500 dark:text-gray-400" 
                />
                <div>
                  <h3 class="font-medium text-gray-900 dark:text-white">
                    {{ component.name }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ getCategoryName(component.category) }} Component
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Share Options -->
            <div class="space-y-4">
              <!-- Direct Link -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Direct Link
                </label>
                <div class="flex items-center space-x-2">
                  <input
                    ref="directLinkInput"
                    :value="previewUrl"
                    readonly
                    class="flex-1 block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                  />
                  <button
                    @click="copyDirectLink"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    <Icon name="clipboard" class="h-4 w-4 mr-2" />
                    Copy
                  </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Share this link to let others view the component preview
                </p>
              </div>
              
              <!-- Embed Code -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Embed Code
                </label>
                <div class="relative">
                  <textarea
                    ref="embedCodeTextarea"
                    :value="embedCode"
                    readonly
                    rows="4"
                    class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                  ></textarea>
                  <button
                    @click="copyEmbedCode"
                    class="absolute top-2 right-2 inline-flex items-center px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                  >
                    <Icon name="clipboard" class="h-3 w-3 mr-1" />
                    Copy
                  </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Embed this iframe in your documentation or website
                </p>
              </div>
              
              <!-- QR Code -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  QR Code
                </label>
                <div class="flex items-center space-x-4">
                  <div class="flex-shrink-0">
                    <div 
                      ref="qrCodeContainer"
                      class="w-32 h-32 bg-white border border-gray-300 rounded-lg flex items-center justify-center"
                    >
                      <canvas
                        ref="qrCodeCanvas"
                        width="128"
                        height="128"
                        class="max-w-full max-h-full"
                      ></canvas>
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                      Scan this QR code with a mobile device to view the preview
                    </p>
                    <button
                      @click="downloadQRCode"
                      class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                      <Icon name="arrow-down-tray" class="h-4 w-4 mr-2" />
                      Download QR Code
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Share Settings -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Share Settings
              </h3>
              
              <div class="space-y-4">
                <!-- Expiration -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Link Expiration
                  </label>
                  <select
                    v-model="shareSettings.expiration"
                    @change="updateShareUrl"
                    class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="never">Never expires</option>
                    <option value="1hour">1 hour</option>
                    <option value="1day">1 day</option>
                    <option value="1week">1 week</option>
                    <option value="1month">1 month</option>
                  </select>
                </div>
                
                <!-- Password Protection -->
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Password Protection
                    </label>
                    <button
                      @click="togglePasswordProtection"
                      :class="getPasswordToggleClasses()"
                      role="switch"
                      :aria-checked="shareSettings.passwordProtected"
                      aria-label="Toggle password protection"
                    >
                      <span 
                        :class="getPasswordToggleThumbClasses()"
                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                      ></span>
                    </button>
                  </div>
                  
                  <div v-if="shareSettings.passwordProtected" class="mt-2">
                    <input
                      v-model="shareSettings.password"
                      type="password"
                      placeholder="Enter password"
                      @input="updateShareUrl"
                      class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                </div>
                
                <!-- Allow Comments -->
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Allow Comments
                  </label>
                  <button
                    @click="toggleComments"
                    :class="getCommentsToggleClasses()"
                    role="switch"
                    :aria-checked="shareSettings.allowComments"
                    aria-label="Toggle comments"
                  >
                    <span 
                      :class="getCommentsToggleThumbClasses()"
                      class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                    ></span>
                  </button>
                </div>
                
                <!-- Analytics -->
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Track Views
                  </label>
                  <button
                    @click="toggleAnalytics"
                    :class="getAnalyticsToggleClasses()"
                    role="switch"
                    :aria-checked="shareSettings.trackViews"
                    aria-label="Toggle view tracking"
                  >
                    <span 
                      :class="getAnalyticsToggleThumbClasses()"
                      class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                    ></span>
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Social Sharing -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Social Sharing
              </h3>
              
              <div class="flex items-center space-x-3">
                <button
                  @click="shareToTwitter"
                  class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <Icon name="twitter" class="h-4 w-4 mr-2" />
                  Twitter
                </button>
                
                <button
                  @click="shareToLinkedIn"
                  class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <Icon name="linkedin" class="h-4 w-4 mr-2" />
                  LinkedIn
                </button>
                
                <button
                  @click="shareViaEmail"
                  class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <Icon name="envelope" class="h-4 w-4 mr-2" />
                  Email
                </button>
              </div>
            </div>
          </div>
          
          <!-- Modal Footer -->
          <footer class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
            <button
              @click="handleClose"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Close
            </button>
          </footer>
        </div>
      </div>
    </div>
    
    <!-- Success Toast -->
    <div
      v-if="showSuccessToast"
      class="fixed bottom-4 right-4 z-60 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg transition-all duration-300"
    >
      <div class="flex items-center space-x-2">
        <Icon name="check-circle" class="h-4 w-4" />
        <span class="text-sm">{{ successMessage }}</span>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue'
import type { Component, ComponentCategory } from '@/types/components'
import Icon from '@/components/Common/Icon.vue'

interface ShareSettings {
  expiration: 'never' | '1hour' | '1day' | '1week' | '1month'
  passwordProtected: boolean
  password: string
  allowComments: boolean
  trackViews: boolean
}

interface Props {
  previewUrl: string
  component: Component
}

interface Emits {
  (e: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Reactive state
const shareSettings = ref<ShareSettings>({
  expiration: 'never',
  passwordProtected: false,
  password: '',
  allowComments: true,
  trackViews: true
})

const showSuccessToast = ref(false)
const successMessage = ref('')

// Template refs
const directLinkInput = ref<HTMLInputElement | null>(null)
const embedCodeTextarea = ref<HTMLTextAreaElement | null>(null)
const qrCodeCanvas = ref<HTMLCanvasElement | null>(null)
const qrCodeContainer = ref<HTMLElement | null>(null)

// Computed properties
const embedCode = computed(() => {
  const width = 800
  const height = 600
  return `<iframe 
  src="${props.previewUrl}" 
  width="${width}" 
  height="${height}" 
  frameborder="0" 
  allowfullscreen
  title="${props.component.name} Component Preview"
  loading="lazy">
</iframe>`
})

// Toggle classes helpers
const getPasswordToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  shareSettings.value.passwordProtected ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getPasswordToggleThumbClasses = () => [
  shareSettings.value.passwordProtected ? 'translate-x-5' : 'translate-x-0'
]

const getCommentsToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  shareSettings.value.allowComments ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getCommentsToggleThumbClasses = () => [
  shareSettings.value.allowComments ? 'translate-x-5' : 'translate-x-0'
]

const getAnalyticsToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  shareSettings.value.trackViews ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getAnalyticsToggleThumbClasses = () => [
  shareSettings.value.trackViews ? 'translate-x-5' : 'translate-x-0'
]

// Methods
const getCategoryIcon = (category: ComponentCategory): string => {
  const iconMap: Record<ComponentCategory, string> = {
    hero: 'star',
    forms: 'document-text',
    testimonials: 'chat-bubble-left-right',
    statistics: 'chart-bar',
    ctas: 'cursor-arrow-rays',
    media: 'photo'
  }
  return iconMap[category] || 'square-3-stack-3d'
}

const getCategoryName = (category: ComponentCategory): string => {
  const nameMap: Record<ComponentCategory, string> = {
    hero: 'Hero Section',
    forms: 'Form',
    testimonials: 'Testimonial',
    statistics: 'Statistics',
    ctas: 'Call to Action',
    media: 'Media'
  }
  return nameMap[category] || category
}

const showToast = (message: string) => {
  successMessage.value = message
  showSuccessToast.value = true
  
  setTimeout(() => {
    showSuccessToast.value = false
  }, 3000)
}

const copyDirectLink = async () => {
  try {
    await navigator.clipboard.writeText(props.previewUrl)
    showToast('Direct link copied to clipboard')
  } catch (error) {
    console.error('Failed to copy direct link:', error)
    // Fallback for older browsers
    if (directLinkInput.value) {
      directLinkInput.value.select()
      document.execCommand('copy')
      showToast('Direct link copied to clipboard')
    }
  }
}

const copyEmbedCode = async () => {
  try {
    await navigator.clipboard.writeText(embedCode.value)
    showToast('Embed code copied to clipboard')
  } catch (error) {
    console.error('Failed to copy embed code:', error)
    // Fallback for older browsers
    if (embedCodeTextarea.value) {
      embedCodeTextarea.value.select()
      document.execCommand('copy')
      showToast('Embed code copied to clipboard')
    }
  }
}

const generateQRCode = async () => {
  if (!qrCodeCanvas.value) return
  
  try {
    // Simple QR code generation (in a real implementation, you'd use a QR code library)
    const canvas = qrCodeCanvas.value
    const ctx = canvas.getContext('2d')
    
    if (!ctx) return
    
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    
    // Draw placeholder QR code pattern
    ctx.fillStyle = '#000000'
    
    // Draw corner squares
    const cornerSize = 20
    const positions = [
      [10, 10], [canvas.width - cornerSize - 10, 10], [10, canvas.height - cornerSize - 10]
    ]
    
    positions.forEach(([x, y]) => {
      ctx.fillRect(x, y, cornerSize, cornerSize)
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(x + 4, y + 4, cornerSize - 8, cornerSize - 8)
      ctx.fillStyle = '#000000'
      ctx.fillRect(x + 8, y + 8, cornerSize - 16, cornerSize - 16)
    })
    
    // Draw data pattern (simplified)
    for (let x = 0; x < canvas.width; x += 4) {
      for (let y = 0; y < canvas.height; y += 4) {
        if (Math.random() > 0.5) {
          ctx.fillRect(x, y, 3, 3)
        }
      }
    }
    
    // Add text below QR code
    ctx.fillStyle = '#666666'
    ctx.font = '10px Arial'
    ctx.textAlign = 'center'
    ctx.fillText('Scan to view', canvas.width / 2, canvas.height - 5)
    
  } catch (error) {
    console.error('Failed to generate QR code:', error)
  }
}

const downloadQRCode = () => {
  if (!qrCodeCanvas.value) return
  
  try {
    const link = document.createElement('a')
    link.download = `${props.component.name.toLowerCase().replace(/\s+/g, '-')}-qr-code.png`
    link.href = qrCodeCanvas.value.toDataURL()
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    showToast('QR code downloaded')
  } catch (error) {
    console.error('Failed to download QR code:', error)
  }
}

const updateShareUrl = () => {
  // In a real implementation, this would update the share URL based on settings
  console.log('Share settings updated:', shareSettings.value)
}

const togglePasswordProtection = () => {
  shareSettings.value.passwordProtected = !shareSettings.value.passwordProtected
  if (!shareSettings.value.passwordProtected) {
    shareSettings.value.password = ''
  }
  updateShareUrl()
}

const toggleComments = () => {
  shareSettings.value.allowComments = !shareSettings.value.allowComments
  updateShareUrl()
}

const toggleAnalytics = () => {
  shareSettings.value.trackViews = !shareSettings.value.trackViews
  updateShareUrl()
}

const shareToTwitter = () => {
  const text = `Check out this ${props.component.name} component preview`
  const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(props.previewUrl)}`
  window.open(url, '_blank', 'width=600,height=400')
}

const shareToLinkedIn = () => {
  const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(props.previewUrl)}`
  window.open(url, '_blank', 'width=600,height=400')
}

const shareViaEmail = () => {
  const subject = `Component Preview: ${props.component.name}`
  const body = `Check out this component preview: ${props.previewUrl}`
  const url = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
  window.location.href = url
}

const handleClose = () => {
  emit('close')
}

const handleBackdropClick = () => {
  emit('close')
}

// Keyboard event handler
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    handleClose()
  }
}

// Lifecycle
onMounted(async () => {
  document.addEventListener('keydown', handleKeydown)
  document.body.style.overflow = 'hidden'
  
  // Generate QR code
  await nextTick()
  generateQRCode()
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.share-preview-modal {
  backdrop-filter: blur(4px);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .share-preview-modal {
    @apply contrast-125;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .share-preview-modal *,
  .share-preview-modal *::before,
  .share-preview-modal *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>