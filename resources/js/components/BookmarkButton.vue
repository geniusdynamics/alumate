<template>
  <button
    @click="toggleBookmark"
    :disabled="loading"
    :class="[
      'bookmark-button flex items-center space-x-1 px-3 py-1 rounded-full text-sm transition-all duration-200',
      isBookmarked
        ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
    ]"
    :title="isBookmarked ? 'Remove bookmark' : 'Bookmark this post'"
  >
    <i
      :class="[
        'transition-all duration-200',
        isBookmarked ? 'fas fa-bookmark text-yellow-600' : 'far fa-bookmark',
        loading && 'fa-spin'
      ]"
    ></i>
    <span v-if="showText">
      {{ isBookmarked ? 'Bookmarked' : 'Bookmark' }}
    </span>
    <span v-if="showCount && bookmarkCount > 0" class="text-xs">
      {{ bookmarkCount }}
    </span>
  </button>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  postId: {
    type: Number,
    required: true
  },
  isBookmarked: {
    type: Boolean,
    default: false
  },
  bookmarkCount: {
    type: Number,
    default: 0
  },
  showText: {
    type: Boolean,
    default: true
  },
  showCount: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['updated'])

const loading = ref(false)

const toggleBookmark = async () => {
  if (loading.value) return

  loading.value = true

  try {
    const response = await fetch(`/api/posts/${props.postId}/bookmark`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    const data = await response.json()

    if (data.success) {
      emit('updated', {
        bookmarked: data.bookmarked,
        stats: data.stats
      })

      // Show visual feedback
      if (data.bookmarked) {
        showBookmarkAnimation()
      }
    } else {
      console.error('Failed to toggle bookmark:', data.message)
    }
  } catch (error) {
    console.error('Error toggling bookmark:', error)
  } finally {
    loading.value = false
  }
}

const showBookmarkAnimation = () => {
  // Create a temporary animation element
  const button = document.querySelector('.bookmark-button')
  if (button) {
    button.classList.add('bookmark-animation')
    setTimeout(() => {
      button.classList.remove('bookmark-animation')
    }, 600)
  }
}
</script>

<style scoped>
.bookmark-button {
  @apply transition-all duration-200 hover:scale-105;
}

.bookmark-button:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.bookmark-button:disabled:hover {
  @apply scale-100;
}

.bookmark-animation {
  animation: bookmarkPulse 0.6s ease-in-out;
}

@keyframes bookmarkPulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
    box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.3);
  }
  100% {
    transform: scale(1);
  }
}

/* Bookmark icon animation */
.fa-bookmark {
  transition: all 0.2s ease-in-out;
}

.fas.fa-bookmark {
  animation: bookmarkFill 0.3s ease-in-out;
}

@keyframes bookmarkFill {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}
</style>