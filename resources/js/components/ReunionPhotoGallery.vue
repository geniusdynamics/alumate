<template>
  <div class="reunion-photo-gallery">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Reunion Photos</h3>
      
      <div class="flex items-center space-x-3">
        <!-- Filters -->
        <select
          v-model="selectedFilter"
          class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Photos</option>
          <option value="featured">Featured</option>
          <option value="my-photos">My Photos</option>
        </select>
        
        <!-- Upload button -->
        <button
          v-if="canUpload"
          @click="showUploadModal = true"
          class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          Upload Photo
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Loading photos...</p>
    </div>

    <!-- Photo grid -->
    <div v-else-if="photos.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="photo in photos"
        :key="photo.id"
        class="relative group cursor-pointer"
        @click="openLightbox(photo)"
      >
        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
          <img
            :src="photo.thumbnail_url || photo.url"
            :alt="photo.title || 'Reunion photo'"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
            loading="lazy"
          />
        </div>
        
        <!-- Overlay with info -->
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-opacity duration-200 rounded-lg flex items-end">
          <div class="p-3 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            <p v-if="photo.title" class="text-sm font-medium truncate">{{ photo.title }}</p>
            <div class="flex items-center space-x-3 text-xs mt-1">
              <span class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
                </svg>
                {{ photo.likes_count }}
              </span>
              <span class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                {{ photo.comments_count }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Featured badge -->
        <div v-if="photo.is_featured" class="absolute top-2 right-2">
          <div class="bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-medium">
            Featured
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <div class="text-gray-400 mb-4">
        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No photos yet</h3>
      <p class="text-gray-600 mb-4">Be the first to share memories from this reunion!</p>
      <button
        v-if="canUpload"
        @click="showUploadModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
      >
        Upload First Photo
      </button>
    </div>

    <!-- Upload Modal -->
    <PhotoUploadModal
      v-if="showUploadModal"
      :event-id="eventId"
      @close="showUploadModal = false"
      @uploaded="handlePhotoUploaded"
    />

    <!-- Lightbox -->
    <PhotoLightbox
      v-if="lightboxPhoto"
      :photo="lightboxPhoto"
      :photos="photos"
      @close="lightboxPhoto = null"
      @next="showNextPhoto"
      @previous="showPreviousPhoto"
      @like="handleLike"
      @comment="handleComment"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import PhotoUploadModal from './PhotoUploadModal.vue'
import PhotoLightbox from './PhotoLightbox.vue'

interface Photo {
  id: number
  title: string
  description: string
  url: string
  thumbnail_url: string
  uploader: {
    id: number
    name: string
    avatar_url: string
  }
  likes_count: number
  comments_count: number
  is_featured: boolean
  is_liked_by_user: boolean
  tagged_users: any[]
  created_at: string
}

interface Props {
  eventId: number
  canUpload?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canUpload: false
})

const photos = ref<Photo[]>([])
const loading = ref(false)
const selectedFilter = ref('all')
const showUploadModal = ref(false)
const lightboxPhoto = ref<Photo | null>(null)

const currentPhotoIndex = computed(() => {
  if (!lightboxPhoto.value) return -1
  return photos.value.findIndex(p => p.id === lightboxPhoto.value!.id)
})

const loadPhotos = async () => {
  loading.value = true
  
  try {
    const params = new URLSearchParams()
    if (selectedFilter.value === 'featured') {
      params.append('featured', '1')
    } else if (selectedFilter.value === 'my-photos') {
      // This would need the current user ID
      // params.append('uploaded_by', currentUserId)
    }
    
    const response = await fetch(`/api/reunions/${props.eventId}/photos?${params}`)
    const data = await response.json()
    photos.value = data
  } catch (error) {
    console.error('Error loading photos:', error)
  } finally {
    loading.value = false
  }
}

const openLightbox = (photo: Photo) => {
  lightboxPhoto.value = photo
}

const showNextPhoto = () => {
  const currentIndex = currentPhotoIndex.value
  if (currentIndex < photos.value.length - 1) {
    lightboxPhoto.value = photos.value[currentIndex + 1]
  }
}

const showPreviousPhoto = () => {
  const currentIndex = currentPhotoIndex.value
  if (currentIndex > 0) {
    lightboxPhoto.value = photos.value[currentIndex - 1]
  }
}

const handlePhotoUploaded = (newPhoto: Photo) => {
  photos.value.unshift(newPhoto)
  showUploadModal.value = false
}

const handleLike = async (photo: Photo) => {
  try {
    const method = photo.is_liked_by_user ? 'DELETE' : 'POST'
    const response = await fetch(`/api/reunion-photos/${photo.id}/like`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      
      // Update the photo in the list
      const photoIndex = photos.value.findIndex(p => p.id === photo.id)
      if (photoIndex !== -1) {
        photos.value[photoIndex].likes_count = data.likes_count
        photos.value[photoIndex].is_liked_by_user = !photo.is_liked_by_user
      }
      
      // Update lightbox photo if it's the same
      if (lightboxPhoto.value && lightboxPhoto.value.id === photo.id) {
        lightboxPhoto.value.likes_count = data.likes_count
        lightboxPhoto.value.is_liked_by_user = !photo.is_liked_by_user
      }
    }
  } catch (error) {
    console.error('Error liking photo:', error)
  }
}

const handleComment = async (photo: Photo, comment: string) => {
  try {
    const response = await fetch(`/api/reunion-photos/${photo.id}/comments`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ comment })
    })
    
    if (response.ok) {
      // Increment comment count
      const photoIndex = photos.value.findIndex(p => p.id === photo.id)
      if (photoIndex !== -1) {
        photos.value[photoIndex].comments_count++
      }
      
      if (lightboxPhoto.value && lightboxPhoto.value.id === photo.id) {
        lightboxPhoto.value.comments_count++
      }
    }
  } catch (error) {
    console.error('Error commenting on photo:', error)
  }
}

watch(selectedFilter, () => {
  loadPhotos()
})

onMounted(() => {
  loadPhotos()
})
</script>