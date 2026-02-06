<template>
    <div class="lightbox-overlay" @click="closeLightbox">
        <div class="lightbox-container" @click.stop>
            <!-- Close button -->
            <button @click="closeLightbox" class="close-button">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Main image -->
            <div class="image-container">
                <img :src="currentPhoto.url" :alt="currentPhoto.alt || 'Photo'" class="main-image" />

                <!-- Navigation arrows -->
                <button v-if="photos.length > 1" @click="previousPhoto" class="nav-button nav-left">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button v-if="photos.length > 1" @click="nextPhoto" class="nav-button nav-right">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Photo info -->
            <div class="photo-info">
                <div class="photo-details">
                    <h3 class="photo-title">{{ currentPhoto.title || 'Untitled' }}</h3>
                    <p v-if="currentPhoto.description" class="photo-description">{{ currentPhoto.description }}</p>
                    <div class="photo-meta">
                        <span class="photo-index">{{ currentIndex + 1 }} of {{ photos.length }}</span>
                        <span v-if="currentPhoto.date" class="photo-date">{{ formatDate(currentPhoto.date) }}</span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="photo-actions">
                    <button @click="downloadPhoto" class="action-button">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Download
                    </button>

                    <button v-if="canEdit" @click="editPhoto" class="action-button">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                        Edit
                    </button>

                    <button v-if="canDelete" @click="deletePhoto" class="action-button delete">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>

            <!-- Thumbnails -->
            <div v-if="photos.length > 1" class="thumbnails">
                <div class="thumbnails-container">
                    <img
                        v-for="(photo, index) in photos"
                        :key="photo.id || index"
                        :src="photo.url"
                        :alt="photo.alt || 'Thumbnail'"
                        class="thumbnail"
                        :class="{ active: index === currentIndex }"
                        @click="setCurrentPhoto(index)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { format } from 'date-fns';
import { computed, ref } from 'vue';

const props = defineProps({
    photos: {
        type: Array,
        required: true,
    },
    initialIndex: {
        type: Number,
        default: 0,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'edit', 'delete', 'download']);

const currentIndex = ref(props.initialIndex);

const currentPhoto = computed(() => props.photos[currentIndex.value] || {});

const closeLightbox = () => {
    emit('close');
};

const nextPhoto = () => {
    currentIndex.value = (currentIndex.value + 1) % props.photos.length;
};

const previousPhoto = () => {
    currentIndex.value = currentIndex.value === 0 ? props.photos.length - 1 : currentIndex.value - 1;
};

const setCurrentPhoto = (index) => {
    currentIndex.value = index;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        return format(new Date(dateString), 'MMM d, yyyy');
    } catch {
        return dateString;
    }
};

const downloadPhoto = () => {
    // Create download link
    const link = document.createElement('a');
    link.href = currentPhoto.value.url;
    link.download = currentPhoto.value.title || 'photo';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    emit('download', currentPhoto.value);
};

const editPhoto = () => {
    emit('edit', currentPhoto.value);
};

const deletePhoto = () => {
    if (confirm('Are you sure you want to delete this photo?')) {
        emit('delete', currentPhoto.value);
    }
};

// Keyboard navigation
const handleKeydown = (event) => {
    switch (event.key) {
        case 'ArrowRight':
            nextPhoto();
            break;
        case 'ArrowLeft':
            previousPhoto();
            break;
        case 'Escape':
            closeLightbox();
            break;
    }
};

// Add keyboard listener
document.addEventListener('keydown', handleKeydown);
</script>

<style scoped>
.lightbox-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90;
}

.lightbox-container {
    @apply relative max-h-full max-w-6xl p-4;
}

.close-button {
    @apply absolute right-4 top-4 z-10 text-white transition-colors hover:text-gray-300;
    @apply rounded-full bg-black bg-opacity-50 p-2;
}

.image-container {
    @apply relative;
}

.main-image {
    @apply max-h-[70vh] max-w-full object-contain;
}

.nav-button {
    @apply absolute top-1/2 z-10 -translate-y-1/2 transform text-white transition-colors hover:text-gray-300;
    @apply rounded-full bg-black bg-opacity-50 p-3;
}

.nav-left {
    @apply left-4;
}

.nav-right {
    @apply right-4;
}

.photo-info {
    @apply mt-4 flex items-start justify-between text-white;
}

.photo-details {
    @apply flex-1;
}

.photo-title {
    @apply mb-1 text-xl font-semibold;
}

.photo-description {
    @apply mb-2 text-gray-300;
}

.photo-meta {
    @apply flex items-center space-x-4 text-sm text-gray-400;
}

.photo-actions {
    @apply flex items-center space-x-2;
}

.action-button {
    @apply flex items-center space-x-1 bg-white bg-opacity-10 px-3 py-2 hover:bg-opacity-20;
    @apply rounded text-sm text-white transition-colors;
}

.action-button.delete {
    @apply text-red-400 hover:text-red-300;
}

.thumbnails {
    @apply mt-4;
}

.thumbnails-container {
    @apply flex space-x-2 overflow-x-auto pb-2;
}

.thumbnail {
    @apply h-16 w-16 cursor-pointer rounded object-cover opacity-60 transition-opacity hover:opacity-100;
}

.thumbnail.active {
    @apply opacity-100 ring-2 ring-white;
}

/* Responsive design */
@media (max-width: 768px) {
    .lightbox-container {
        @apply p-2;
    }

    .photo-info {
        @apply flex-col space-y-4;
    }

    .photo-actions {
        @apply justify-center;
    }

    .main-image {
        @apply max-h-[50vh];
    }
}
</style>
