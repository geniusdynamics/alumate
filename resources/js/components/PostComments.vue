<template>
  <div class="post-comments">
    <!-- Comment Form -->
    <div class="mb-4">
      <CommentForm
        :post-id="postId"
        @submitted="handleCommentSubmitted"
        placeholder="Write a comment..."
      />
    </div>

    <!-- Comments List -->
    <div v-if="comments.length > 0" class="space-y-4">
      <CommentThread
        v-for="comment in comments"
        :key="comment.id"
        :comment="comment"
        :post-id="postId"
        @reply-submitted="handleReplySubmitted"
      />
    </div>

    <!-- Load More Comments -->
    <div v-if="hasMoreComments" class="text-center mt-4">
      <button
        @click="loadMoreComments"
        :disabled="loadingMore"
        class="text-blue-500 hover:text-blue-600 text-sm font-medium"
      >
        <i v-if="loadingMore" class="fas fa-spinner fa-spin mr-1"></i>
        Load more comments
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && comments.length === 0" class="text-center py-4">
      <i class="fas fa-spinner fa-spin text-gray-400"></i>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && comments.length === 0" class="text-center py-4 text-gray-500">
      No comments yet. Be the first to comment!
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import CommentForm from './CommentForm.vue'
import CommentThread from './CommentThread.vue'

const props = defineProps({
  postId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['stats-updated'])

const comments = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const hasMoreComments = ref(false)
const currentPage = ref(1)

onMounted(() => {
  loadComments()
})

const loadComments = async (page = 1) => {
  if (page === 1) {
    loading.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const response = await fetch(`/api/posts/${props.postId}/comments?page=${page}`)
    const data = await response.json()

    if (data.success) {
      if (page === 1) {
        comments.value = data.comments.data
      } else {
        comments.value.push(...data.comments.data)
      }
      
      hasMoreComments.value = data.comments.current_page < data.comments.last_page
      currentPage.value = data.comments.current_page
    }
  } catch (error) {
    console.error('Error loading comments:', error)
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const loadMoreComments = () => {
  if (!loadingMore.value && hasMoreComments.value) {
    loadComments(currentPage.value + 1)
  }
}

const handleCommentSubmitted = (comment) => {
  // Add new comment to the beginning of the list
  comments.value.unshift(comment)
  
  // Emit stats update
  emit('stats-updated')
}

const handleReplySubmitted = (reply, parentCommentId) => {
  // Find the parent comment and add the reply
  const parentComment = findCommentById(comments.value, parentCommentId)
  if (parentComment) {
    if (!parentComment.replies) {
      parentComment.replies = []
    }
    parentComment.replies.push(reply)
  }
  
  // Emit stats update
  emit('stats-updated')
}

const findCommentById = (commentsList, id) => {
  for (const comment of commentsList) {
    if (comment.id === id) {
      return comment
    }
    if (comment.replies && comment.replies.length > 0) {
      const found = findCommentById(comment.replies, id)
      if (found) return found
    }
  }
  return null
}

// Expose methods for parent component
defineExpose({
  refresh: () => loadComments(1)
})
</script>
</template>

<script>
// CommentThread component definition
import { defineComponent, ref } from 'vue'

const CommentThread = defineComponent({
  name: 'CommentThread',
  props: {
    comment: {
      type: Object,
      required: true
    },
    postId: {
      type: Number,
      required: true
    },
    depth: {
      type: Number,
      default: 0
    }
  },
  emits: ['reply-submitted'],
  setup(props, { emit }) {
    const showReplyForm = ref(false)
    const showReplies = ref(true)

    const toggleReplyForm = () => {
      showReplyForm.value = !showReplyForm.value
    }

    const handleReplySubmitted = (reply) => {
      showReplyForm.value = false
      emit('reply-submitted', reply, props.comment.id)
    }

    const formatDate = (dateString) => {
      const date = new Date(dateString)
      const now = new Date()
      const diffInSeconds = Math.floor((now - date) / 1000)

      if (diffInSeconds < 60) return 'just now'
      if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
      if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
      if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
      
      return date.toLocaleDateString()
    }

    return {
      showReplyForm,
      showReplies,
      toggleReplyForm,
      handleReplySubmitted,
      formatDate
    }
  },
  template: `
    <div :class="['comment-thread', { 'ml-8': depth > 0 }]">
      <div class="flex space-x-3">
        <img
          :src="comment.user.avatar_url || '/default-avatar.png'"
          :alt="comment.user.name"
          class="w-8 h-8 rounded-full flex-shrink-0"
        >
        <div class="flex-1">
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="flex items-center space-x-2 mb-1">
              <span class="font-medium text-sm">{{ comment.user.name }}</span>
              <span class="text-xs text-gray-500">@{{ comment.user.username }}</span>
              <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
            </div>
            <div class="text-sm" v-html="formatContent(comment.content)"></div>
          </div>
          
          <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
            <button
              @click="toggleReplyForm"
              class="hover:text-blue-500 font-medium"
            >
              Reply
            </button>
            <span v-if="comment.replies && comment.replies.length > 0">
              {{ comment.replies.length }} {{ comment.replies.length === 1 ? 'reply' : 'replies' }}
            </span>
          </div>

          <!-- Reply Form -->
          <div v-if="showReplyForm" class="mt-3">
            <CommentForm
              :post-id="postId"
              :parent-id="comment.id"
              @submitted="handleReplySubmitted"
              @cancelled="showReplyForm = false"
              placeholder="Write a reply..."
              auto-focus
            />
          </div>

          <!-- Replies -->
          <div v-if="comment.replies && comment.replies.length > 0 && showReplies" class="mt-3">
            <CommentThread
              v-for="reply in comment.replies"
              :key="reply.id"
              :comment="reply"
              :post-id="postId"
              :depth="depth + 1"
              @reply-submitted="$emit('reply-submitted', $event, reply.id)"
            />
          </div>
        </div>
      </div>
    </div>
  `,
  methods: {
    formatContent(content) {
      // Convert mentions to clickable links
      return content.replace(
        /@(\w+)/g,
        '<span class="text-blue-500 font-medium">@$1</span>'
      )
    }
  }
})

export { CommentThread }
</script>

<style scoped>
.comment-thread {
  @apply transition-all duration-200;
}

.comment-thread:hover {
  @apply bg-gray-50 bg-opacity-50 rounded-lg;
}
</style>