<template>
  <div 
    v-if="isOpen" 
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90"
    @click="handleBackdropClick"
    @keydown.esc="close"
    tabindex="0"
  >
    <!-- Close button -->
    <button
      @click="close"
      class="absolute top-4 right-4 z-10 p-2 text-white hover:text-gray-300 transition-colors"
      aria-label="Close lightbox"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>

    <!-- Navigation arrows -->
    <button
      v-if="photos.length > 1"
      @click="previousPhoto"
      class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 p-2 text-white hover:text-gray-300 transition-colors"
      aria-label="Previous photo"
    >
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <button
      v-if="photos.length > 1"
      @click="nextPhoto"
      class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 p-2 text-white hover:text-gray-300 transition-colors"
      aria-label="Next photo"
    >
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>

    <!-- Main photo container -->
    <div class="relative max-w-full max-h-full p-4">
      <img
        v-if="currentPhoto"
        :src="currentPhoto.url"
        :alt="currentPhoto.title || 'Photo'"
        class="max-w-full max-h-full object-contain"
        @click.stop
      >
      
      <!-- Photo info -->
      <div 
        v-if="currentPhoto && (currentPhoto.title || currentPhoto.description)"
        class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white p-4"
      >
        <h3 v-if="currentPhoto.title" class="text-lg font-semibold mb-1">
          {{ currentPhoto.title }}
        </h3>
        <p v-if="currentPhoto.description" class="text-sm text-gray-300">
          {{ currentPhoto.description }}
        </p>
        <div class="flex items-center justify-between mt-2 text-xs text-gray-400">
          <span v-if="currentPhoto.uploaded_by">
            Uploaded by {{ currentPhoto.uploaded_by.name }}
          </span>
          <span v-if="photos.length > 1">
            {{ currentIndex + 1 }} of {{ photos.length }}
          </span>
        </div>
      </div>
    </div>

    <!-- Thumbnail strip -->
    <div 
      v-if="photos.length > 1"
      class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 bg-black bg-opacity-50 rounded-lg p-2"
    >
      <button
        v-for="(photo, index) in photos"
        :key="photo.id"
        @click="setCurrentPhoto(index)"
        class="w-12 h-12 rounded overflow-hidden border-2 transition-all"
        :class="{
          'border-white': index === currentIndex,
          'border-transparent opacity-70 hover:opacity-100': index !== currentIndex
        }"
      >
        <img
          :src="photo.thumbnail_url || photo.url"
          :alt="photo.title || 'Thumbnail'"
          class="w-full h-full object-cover"
        >
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

interface Photo {
  id: number
  url: string
  thumbnail_url?: string
  title?: string
  description?: string
  uploaded_by?: {
    id: number
    name: string
  }
}

interface Props {
  photos: Photo[]
  initialIndex?: number
  isOpen: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialIndex: 0
})

const emit = defineEmits<{
  close: []
  photoChanged: [index: number]
}>()

const currentIndex = ref(props.initialIndex)

const currentPhoto = computed(() => {
  return props.photos[currentIndex.value] || null
})

const nextPhoto = () => {
  if (props.photos.length > 1) {
    currentIndex.value = (currentIndex.value + 1) % props.photos.length
    emit('photoChanged', currentIndex.value)
  }
}

const previousPhoto = () => {
  if (props.photos.length > 1) {
    currentIndex.value = currentIndex.value === 0 
      ? props.photos.length - 1 
      : currentIndex.value - 1
    emit('photoChanged', currentIndex.value)
  }
}

const setCurrentPhoto = (index: number) => {
  currentIndex.value = index
  emit('photoChanged', index)
}

const close = () => {
  emit('close')
}

const handleBackdropClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    close()
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (!props.isOpen) return
  
  switch (event.key) {
    case 'Escape':
      close()
      break
    case 'ArrowLeft':
      previousPhoto()
      break
    case 'ArrowRight':
      nextPhoto()
      break
  }
}

// Watch for prop changes
watch(() => props.initialIndex, (newIndex) => {
  currentIndex.value = newIndex
})

watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* Ensure the lightbox is above everything else */
.fixed {
  position: fixed;
}
</style>