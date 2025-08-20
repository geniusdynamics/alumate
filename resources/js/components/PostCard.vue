<template>
  <div class="post-card bg-white rounded-lg shadow-md p-4 mb-4">
    <div class="post-header flex items-center space-x-3 mb-3">
      <div class="avatar w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
        <span class="text-sm font-medium text-gray-700">
          {{ post.user?.name?.charAt(0).toUpperCase() || 'U' }}
        </span>
      </div>
      <div>
        <h3 class="font-medium text-gray-900">{{ post.user?.name || 'Anonymous' }}</h3>
        <p class="text-sm text-gray-500">{{ formatDate(post.created_at) }}</p>
      </div>
    </div>

    <div class="post-content mb-3">
      <h4 class="font-medium text-lg mb-2">{{ post.title }}</h4>
      <p class="text-gray-700">{{ post.content || post.description || 'Post content' }}</p>
    </div>

    <div class="post-actions flex items-center justify-between pt-3 border-t border-gray-200">
      <div class="flex items-center space-x-4">
        <button
          @click="handleLike"
          class="flex items-center space-x-1 text-gray-500 hover:text-blue-500"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
          <span class="text-sm">{{ post.engagement_counts?.like || 0 }}</span>
        </button>

        <button
          @click="handleComment"
          class="flex items-center space-x-1 text-gray-500 hover:text-blue-500"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <span class="text-sm">{{ post.engagement_counts?.comment || 0 }}</span>
        </button>
      </div>

      <button
        @click="handleShare"
        class="text-gray-500 hover:text-green-500"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import { format } from 'date-fns';

// Props
const props = defineProps({
  post: {
    type: Object,
    required: true
  }
});

// Emits
const emit = defineEmits(['engagement-updated', 'post-deleted']);

// Methods
const formatDate = (dateString) => {
  if (!dateString) return 'Recently';
  try {
    return format(new Date(dateString), 'MMM d, yyyy h:mm a');
  } catch {
    return 'Recently';
  }
};

const handleLike = () => {
  emit('engagement-updated', props.post.id, { type: 'like' });
};

const handleComment = () => {
  // Handle comment action
  console.log('Comment on post:', props.post.id);
};

const handleShare = () => {
  // Handle share action
  console.log('Share post:', props.post.id);
};
</script>

<style scoped>
.post-card {
  transition: all 0.2s ease-in-out;
}

.post-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>