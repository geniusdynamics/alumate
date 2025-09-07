<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="video-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
      @click="handleOverlayClick"
      @keydown.esc="closeModal"
    >
      <!-- Modal Background -->
      <div
        class="absolute inset-0 bg-black bg-opacity-75 transition-opacity"
        :class="{ 'opacity-100': isVisible, 'opacity-0': !isVisible }"
      ></div>

      <!-- Modal Content -->
      <div
        ref="modalContent"
        :class="modalClasses"
        role="dialog"
        :aria-labelledby="modalTitleId"
        aria-modal="true"
        @click.stop
      >
        <!-- Modal Header -->
        <header class="flex items-center justify-between p-4 border-b border-gray-700">
          <div class="flex items-center space-x-3">
            <!-- Author Photo -->
            <ResponsiveImage
              v-if="testimonial.author.photo"
              :src="testimonial.author.photo"
              :alt="`Photo of ${testimonial.author.name}`"
              class="w-10 h-10 rounded-full object-cover"
            />
            
            <!-- Author Info -->
            <div>
              <h2
                :id="modalTitleId"
                class="text-lg font-semibold text-white"
              >
                {{ testimonial.author.name }}
              </h2>
              <p class="text-sm text-gray-300">
                {{ testimonial.author.title }}
                <span v-if="testimonial.author.company">
                  at {{ testimonial.author.company }}
                </span>
              </p>
            </div>
          </div>

          <!-- Close Button -->
          <button
            @click="closeModal"
            class="p-2 text-gray-400 hover:text-white transition-colors rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
            :aria-label="'Close video modal'"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </header>

        <!-- Video Container -->
        <div class="relative bg-black">
          <ResponsiveVideo
            v-if="testimonial.content.videoAsset"
            :src="testimonial.content.videoAsset"
            :autoplay="videoSettings?.autoplay ?? true"
            :muted="videoSettings?.muted ?? false"
            :show-controls="videoSettings?.showControls ?? true"
            :show-captions="videoSettings?.showCaptions ?? true"
            :preload="videoSettings?.preload ?? 'auto'"
            :lazy-load="false"
            :show-quality-selector="true"
            :enable-bandwidth-detection="true"
            :show-transcript="!!testimonial.content.videoAsset.transcript"
            :transcript="testimonial.content.videoAsset.transcript"
            :caption-tracks="captionTracks"
            :track-analytics="true"
            :analytics-id="testimonial.id"
            aspect-ratio="16:9"
            @play="handleVideoPlay"
            @pause="handleVideoPause"
            @ended="handleVideoEnded"
            @timeupdate="handleTimeUpdate"
            @qualitychange="handleQualityChange"
            @seeking="handleSeeking"
            @seeked="handleSeeked"
            @volumechange="handleVolumeChange"
            class="w-full"
          />
        </div>

        <!-- Modal Footer -->
        <footer v-if="showFooter" class="p-4 bg-gray-800">
          <!-- Quote -->
          <blockquote class="mb-4">
            <p class="text-gray-200 leading-relaxed">
              "{{ testimonial.content.quote }}"
            </p>
          </blockquote>

          <!-- Rating -->
          <div
            v-if="testimonial.content.rating"
            class="flex items-center mb-4"
            :aria-label="`Rating: ${testimonial.content.rating} out of 5 stars`"
          >
            <div class="flex space-x-1">
              <svg
                v-for="star in 5"
                :key="`modal-star-${star}`"
                :class="[
                  'w-4 h-4',
                  star <= testimonial.content.rating
                    ? 'text-yellow-400 fill-current'
                    : 'text-gray-600'
                ]"
                viewBox="0 0 20 20"
                :aria-hidden="true"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
            <span class="ml-2 text-sm text-gray-400">
              {{ testimonial.content.rating }}/5
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <!-- Share Button -->
              <button
                @click="handleShare"
                class="flex items-center space-x-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                </svg>
                <span>Share</span>
              </button>

              <!-- Like Button -->
              <button
                @click="handleLike"
                :class="[
                  'flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50',
                  isLiked
                    ? 'bg-red-600 hover:bg-red-700 text-white'
                    : 'bg-gray-700 hover:bg-gray-600 text-white'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span>{{ isLiked ? 'Liked' : 'Like' }}</span>
              </button>
            </div>

            <!-- Date -->
            <div class="text-sm text-gray-400">
              {{ formatDate(testimonial.content.dateCreated) }}
            </div>
          </div>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import type { Testimonial, VideoSettings } from '@/types/components'

// Import child components
import ResponsiveVideo from '@/components/Common/ResponsiveVideo.vue'
import ResponsiveImage from '@/components/Common/ResponsiveImage.vue'

interface Props {
  testimonial: Testimonial
  videoSettings?: VideoSettings
  showFooter?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showFooter: true,
  videoSettings: () => ({
    autoplay: true,
    muted: false,
    showControls: true,
    showCaptions: true,
    preload: 'auto'
  })
})

const emit = defineEmits<{
  close: []
  videoInteraction: [event: { type: string, data?: any }]
}>()

// Computed properties for video enhancements
const captionTracks = computed(() => {
  const tracks = []
  
  if (props.testimonial.content.videoAsset?.captions) {
    tracks.push({
      kind: 'captions' as const,
      src: props.testimonial.content.videoAsset.captions,
      srclang: 'en',
      label: 'English captions',
      default: true
    })
  }
  
  return tracks
})

// Reactive state
const isOpen = ref(true)
const isVisible = ref(false)
const isLiked = ref(false)
const modalContent = ref<HTMLElement>()

// Computed properties
const modalTitleId = computed(() => `video-modal-title-${props.testimonial.id}`)

const modalClasses = computed(() => [
  'relative bg-gray-900 rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transition-all duration-300',
  {
    'scale-100 opacity-100': isVisible.value,
    'scale-95 opacity-0': !isVisible.value
  }
])

// Methods
const closeModal = () => {
  isVisible.value = false
  setTimeout(() => {
    isOpen.value = false
    emit('close')
  }, 300)
}

const handleOverlayClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    closeModal()
  }
}

const handleVideoPlay = () => {
  emit('videoInteraction', { type: 'play' })
}

const handleVideoPause = () => {
  emit('videoInteraction', { type: 'pause' })
}

const handleVideoEnded = () => {
  emit('videoInteraction', { type: 'ended' })
}

const handleTimeUpdate = (currentTime: number, duration: number) => {
  emit('videoInteraction', { 
    type: 'timeupdate', 
    data: { currentTime, duration, progress: (currentTime / duration) * 100 } 
  })
}

const handleQualityChange = (quality: any) => {
  emit('videoInteraction', { 
    type: 'quality_change', 
    data: { quality: quality.label } 
  })
}

const handleSeeking = (time: number) => {
  emit('videoInteraction', { 
    type: 'seeking', 
    data: { time } 
  })
}

const handleSeeked = (time: number) => {
  emit('videoInteraction', { 
    type: 'seeked', 
    data: { time } 
  })
}

const handleVolumeChange = (volume: number, muted: boolean) => {
  emit('videoInteraction', { 
    type: 'volume_change', 
    data: { volume, muted } 
  })
}

const handleShare = async () => {
  const shareData = {
    title: `Video testimonial from ${props.testimonial.author.name}`,
    text: props.testimonial.content.quote,
    url: window.location.href
  }

  try {
    if (navigator.share) {
      await navigator.share(shareData)
    } else {
      // Fallback: copy to clipboard
      await navigator.clipboard.writeText(`"${props.testimonial.content.quote}" - ${props.testimonial.author.name}`)
    }

    emit('videoInteraction', {
      type: 'share',
      data: { method: navigator.share ? 'native' : 'clipboard' }
    })
  } catch (error) {
    console.error('Error sharing video testimonial:', error)
  }
}

const handleLike = () => {
  isLiked.value = !isLiked.value
  emit('videoInteraction', {
    type: 'like',
    data: { liked: isLiked.value }
  })
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    closeModal()
  }
}

const trapFocus = (event: KeyboardEvent) => {
  if (!modalContent.value) return

  const focusableElements = modalContent.value.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  
  const firstElement = focusableElements[0] as HTMLElement
  const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement

  if (event.key === 'Tab') {
    if (event.shiftKey) {
      if (document.activeElement === firstElement) {
        event.preventDefault()
        lastElement.focus()
      }
    } else {
      if (document.activeElement === lastElement) {
        event.preventDefault()
        firstElement.focus()
      }
    }
  }
}

// Lifecycle
onMounted(async () => {
  await nextTick()
  
  // Animate in
  setTimeout(() => {
    isVisible.value = true
  }, 50)

  // Add event listeners
  document.addEventListener('keydown', handleKeydown)
  document.addEventListener('keydown', trapFocus)
  
  // Prevent body scroll
  document.body.style.overflow = 'hidden'
  
  // Focus the modal
  if (modalContent.value) {
    modalContent.value.focus()
  }
})

onUnmounted(() => {
  // Remove event listeners
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('keydown', trapFocus)
  
  // Restore body scroll
  document.body.style.overflow = ''
})

// Watch for modal close
watch(isOpen, (open) => {
  if (!open) {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.video-modal-overlay {
  backdrop-filter: blur(4px);
}

/* Ensure modal is above everything */
.video-modal-overlay {
  z-index: 9999;
}

/* Smooth transitions */
.video-modal-overlay * {
  transition: all 0.3s ease;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .video-modal-overlay .modal-content {
    border: 2px solid white;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .video-modal-overlay *,
  .video-modal-overlay *::before,
  .video-modal-overlay *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Mobile responsive */
@media (max-width: 640px) {
  .video-modal-overlay {
    padding: 1rem;
  }
  
  .video-modal-overlay .modal-content {
    max-height: 95vh;
  }
}

/* Focus management */
.video-modal-overlay .modal-content:focus {
  outline: 2px solid #6366f1;
  outline-offset: -2px;
}
</style>