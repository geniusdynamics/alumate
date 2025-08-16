<template>
  <Teleport to="body">
    <div 
      v-if="isOpen"
      class="video-modal-overlay"
      @click="closeModal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="video-modal-title"
    >
      <div 
        class="video-modal-container"
        @click.stop
      >
        <!-- Modal Header -->
        <header class="video-modal-header">
          <h2 id="video-modal-title" class="modal-title">
            {{ title || 'Video Testimonial' }}
          </h2>
          <button 
            @click="closeModal"
            class="close-button"
            aria-label="Close video modal"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </header>

        <!-- Video Container -->
        <div class="video-container">
          <div class="video-wrapper">
            <!-- YouTube Video -->
            <iframe
              v-if="videoType === 'youtube'"
              :src="youtubeEmbedUrl"
              title="Video testimonial"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
              class="video-iframe"
            ></iframe>

            <!-- Vimeo Video -->
            <iframe
              v-else-if="videoType === 'vimeo'"
              :src="vimeoEmbedUrl"
              title="Video testimonial"
              frameborder="0"
              allow="autoplay; fullscreen; picture-in-picture"
              allowfullscreen
              class="video-iframe"
            ></iframe>

            <!-- Direct Video -->
            <video
              v-else-if="videoType === 'direct'"
              :src="videoUrl"
              controls
              autoplay
              class="video-element"
            >
              Your browser does not support the video tag.
            </video>

            <!-- Fallback for unsupported video types -->
            <div v-else class="video-error">
              <svg class="error-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              <p class="error-message">Unable to load video</p>
              <a 
                :href="videoUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="external-link"
              >
                Watch on external site
              </a>
            </div>
          </div>
        </div>

        <!-- Video Info -->
        <div v-if="description || alumniName" class="video-info">
          <div v-if="alumniName" class="alumni-info">
            <img 
              v-if="alumniImage"
              :src="alumniImage"
              :alt="`${alumniName} profile photo`"
              class="alumni-avatar"
            />
            <div class="alumni-details">
              <h3 class="alumni-name">{{ alumniName }}</h3>
              <p v-if="alumniRole" class="alumni-role">{{ alumniRole }}</p>
              <p v-if="alumniCompany" class="alumni-company">{{ alumniCompany }}</p>
            </div>
          </div>
          
          <p v-if="description" class="video-description">
            {{ description }}
          </p>
        </div>

        <!-- Video Actions -->
        <div class="video-actions">
          <button 
            @click="shareVideo"
            class="action-button share-button"
            aria-label="Share video"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
            </svg>
            Share
          </button>
          
          <button 
            @click="toggleFullscreen"
            class="action-button fullscreen-button"
            aria-label="Toggle fullscreen"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
            </svg>
            Fullscreen
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted } from 'vue'

interface Props {
  isOpen: boolean
  videoUrl: string
  title?: string
  description?: string
  alumniName?: string
  alumniRole?: string
  alumniCompany?: string
  alumniImage?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  share: [url: string]
}>()

// Determine video type and generate embed URLs
const videoType = computed(() => {
  if (!props.videoUrl) return 'unknown'
  
  if (props.videoUrl.includes('youtube.com') || props.videoUrl.includes('youtu.be')) {
    return 'youtube'
  } else if (props.videoUrl.includes('vimeo.com')) {
    return 'vimeo'
  } else if (props.videoUrl.match(/\.(mp4|webm|ogg)$/i)) {
    return 'direct'
  }
  
  return 'unknown'
})

const youtubeEmbedUrl = computed(() => {
  if (videoType.value !== 'youtube') return ''
  
  let videoId = ''
  
  if (props.videoUrl.includes('youtu.be/')) {
    videoId = props.videoUrl.split('youtu.be/')[1].split('?')[0]
  } else if (props.videoUrl.includes('youtube.com/watch?v=')) {
    videoId = props.videoUrl.split('v=')[1].split('&')[0]
  }
  
  return `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`
})

const vimeoEmbedUrl = computed(() => {
  if (videoType.value !== 'vimeo') return ''
  
  const videoId = props.videoUrl.split('vimeo.com/')[1].split('?')[0]
  return `https://player.vimeo.com/video/${videoId}?autoplay=1`
})

// Modal methods
const closeModal = () => {
  emit('close')
}

const shareVideo = () => {
  emit('share', props.videoUrl)
}

const toggleFullscreen = () => {
  const videoContainer = document.querySelector('.video-modal-container')
  if (!videoContainer) return
  
  if (!document.fullscreenElement) {
    videoContainer.requestFullscreen?.()
  } else {
    document.exitFullscreen?.()
  }
}

// Keyboard event handling
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    closeModal()
  }
}

// Prevent body scroll when modal is open
const preventBodyScroll = () => {
  document.body.style.overflow = 'hidden'
}

const restoreBodyScroll = () => {
  document.body.style.overflow = ''
}

// Watch for modal open/close
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    preventBodyScroll()
    document.addEventListener('keydown', handleKeydown)
  } else {
    restoreBodyScroll()
    document.removeEventListener('keydown', handleKeydown)
  }
})

onMounted(() => {
  if (props.isOpen) {
    preventBodyScroll()
    document.addEventListener('keydown', handleKeydown)
  }
})

onUnmounted(() => {
  restoreBodyScroll()
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.video-modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4;
  backdrop-filter: blur(4px);
}

.video-modal-container {
  @apply bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-screen overflow-y-auto;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.video-modal-header {
  @apply flex justify-between items-center p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-xl font-semibold text-gray-900;
}

.close-button {
  @apply text-gray-400 hover:text-gray-600 transition-colors p-1;
}

.close-button svg {
  @apply w-6 h-6;
}

.video-container {
  @apply p-6;
}

.video-wrapper {
  @apply relative w-full bg-black rounded-lg overflow-hidden;
  aspect-ratio: 16 / 9;
}

.video-iframe,
.video-element {
  @apply absolute inset-0 w-full h-full;
}

.video-error {
  @apply absolute inset-0 flex flex-col items-center justify-center text-white;
}

.error-icon {
  @apply w-12 h-12 mb-4 text-gray-400;
}

.error-message {
  @apply text-lg mb-4;
}

.external-link {
  @apply text-blue-400 hover:text-blue-300 underline;
}

.video-info {
  @apply px-6 pb-4 space-y-4;
}

.alumni-info {
  @apply flex items-center gap-4;
}

.alumni-avatar {
  @apply w-12 h-12 rounded-full object-cover border-2 border-gray-200;
}

.alumni-details {
  @apply flex-1;
}

.alumni-name {
  @apply text-lg font-semibold text-gray-900;
}

.alumni-role {
  @apply text-gray-700 font-medium;
}

.alumni-company {
  @apply text-gray-600;
}

.video-description {
  @apply text-gray-700 leading-relaxed;
}

.video-actions {
  @apply flex justify-center gap-4 p-6 border-t border-gray-200;
}

.action-button {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors;
}

.action-button svg {
  @apply w-5 h-5;
}

.share-button {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.fullscreen-button {
  @apply bg-gray-600 text-white hover:bg-gray-700;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .video-modal-overlay {
    @apply p-2;
  }
  
  .video-modal-container {
    @apply max-h-full;
  }
  
  .video-modal-header {
    @apply p-4;
  }
  
  .modal-title {
    @apply text-lg;
  }
  
  .video-container {
    @apply p-4;
  }
  
  .video-info {
    @apply px-4;
  }
  
  .alumni-info {
    @apply gap-3;
  }
  
  .alumni-avatar {
    @apply w-10 h-10;
  }
  
  .alumni-name {
    @apply text-base;
  }
  
  .video-actions {
    @apply p-4 flex-col;
  }
  
  .action-button {
    @apply w-full justify-center;
  }
}

/* Fullscreen styles */
:global(.video-modal-container:fullscreen) {
  @apply max-w-none max-h-none w-screen h-screen rounded-none;
}

:global(.video-modal-container:fullscreen .video-container) {
  @apply flex-1 flex items-center justify-center;
}

:global(.video-modal-container:fullscreen .video-wrapper) {
  @apply max-w-full max-h-full;
}
</style>