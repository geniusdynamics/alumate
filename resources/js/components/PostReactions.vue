<template>
  <div class="post-reactions">
    <!-- Reaction Buttons -->
    <div class="reaction-buttons flex items-center space-x-2 mb-2">
      <button
        v-for="reaction in reactionTypes"
        :key="reaction.type"
        @click="toggleReaction(reaction.type)"
        :class="[
          'reaction-btn flex items-center space-x-1 px-3 py-1 rounded-full text-sm transition-all duration-200',
          userEngagement.reactions.includes(reaction.type)
            ? `${reaction.activeClass} text-white`
            : 'bg-gray-100 hover:bg-gray-200 text-gray-600'
        ]"
        :disabled="loading"
      >
        <span :class="reaction.icon" class="text-sm"></span>
        <span v-if="stats[reaction.type] > 0">{{ stats[reaction.type] }}</span>
      </button>
    </div>

    <!-- Reaction Summary -->
    <div v-if="totalReactions > 0" class="reaction-summary text-sm text-gray-600 mb-2">
      <button
        @click="showReactionModal = true"
        class="hover:underline flex items-center space-x-1"
      >
        <div class="flex -space-x-1">
          <span
            v-for="(reaction, index) in topReactions"
            :key="reaction.type"
            :class="reaction.icon"
            class="text-xs"
          ></span>
        </div>
        <span>{{ reactionSummaryText }}</span>
      </button>
    </div>

    <!-- Reaction Details Modal -->
    <div
      v-if="showReactionModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="showReactionModal = false"
    >
      <div
        class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-96 overflow-y-auto"
        @click.stop
      >
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Reactions</h3>
          <button
            @click="showReactionModal = false"
            class="text-gray-400 hover:text-gray-600"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Reaction Tabs -->
        <div class="flex space-x-2 mb-4 border-b">
          <button
            v-for="reaction in reactionTypesWithCounts"
            :key="reaction.type"
            @click="selectedReactionType = reaction.type"
            :class="[
              'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
              selectedReactionType === reaction.type
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            ]"
          >
            <span :class="reaction.icon" class="mr-1"></span>
            {{ reaction.count }}
          </button>
        </div>

        <!-- Users List -->
        <div class="space-y-2">
          <div
            v-for="user in reactionUsers"
            :key="user.id"
            class="flex items-center space-x-3"
          >
            <img
              :src="user.avatar_url || '/default-avatar.png'"
              :alt="user.name"
              class="w-8 h-8 rounded-full"
            >
            <div>
              <div class="font-medium text-sm">{{ user.name }}</div>
              <div class="text-xs text-gray-500">@{{ user.username }}</div>
            </div>
          </div>
          <div v-if="loadingUsers" class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  postId: {
    type: Number,
    required: true
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  userEngagement: {
    type: Object,
    default: () => ({ reactions: [] })
  }
})

const emit = defineEmits(['updated'])

const loading = ref(false)
const showReactionModal = ref(false)
const selectedReactionType = ref('like')
const reactionUsers = ref([])
const loadingUsers = ref(false)

const reactionTypes = [
  { type: 'like', icon: 'fas fa-thumbs-up', activeClass: 'bg-blue-500' },
  { type: 'love', icon: 'fas fa-heart', activeClass: 'bg-red-500' },
  { type: 'celebrate', icon: 'fas fa-trophy', activeClass: 'bg-yellow-500' },
  { type: 'support', icon: 'fas fa-hands-helping', activeClass: 'bg-green-500' },
  { type: 'insightful', icon: 'fas fa-lightbulb', activeClass: 'bg-purple-500' }
]

const totalReactions = computed(() => {
  return reactionTypes.reduce((total, reaction) => {
    return total + (props.stats[reaction.type] || 0)
  }, 0)
})

const topReactions = computed(() => {
  return reactionTypes
    .filter(reaction => props.stats[reaction.type] > 0)
    .sort((a, b) => (props.stats[b.type] || 0) - (props.stats[a.type] || 0))
    .slice(0, 3)
})

const reactionTypesWithCounts = computed(() => {
  return reactionTypes
    .filter(reaction => props.stats[reaction.type] > 0)
    .map(reaction => ({
      ...reaction,
      count: props.stats[reaction.type]
    }))
})

const reactionSummaryText = computed(() => {
  if (totalReactions.value === 1) {
    return '1 reaction'
  }
  return `${totalReactions.value} reactions`
})

const toggleReaction = async (type) => {
  if (loading.value) return

  loading.value = true
  
  try {
    const isCurrentlyReacted = props.userEngagement.reactions.includes(type)
    const endpoint = isCurrentlyReacted ? 'unreact' : 'react'
    
    const response = await fetch(`/api/posts/${props.postId}/${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ type })
    })

    const data = await response.json()

    if (data.success) {
      emit('updated', {
        stats: data.stats,
        userEngagement: data.user_engagement
      })
    } else {
      console.error('Failed to toggle reaction:', data.message)
    }
  } catch (error) {
    console.error('Error toggling reaction:', error)
  } finally {
    loading.value = false
  }
}

const loadReactionUsers = async (type) => {
  if (loadingUsers.value) return

  loadingUsers.value = true
  
  try {
    const response = await fetch(`/api/posts/${props.postId}/reactions/users?type=${type}`)
    const data = await response.json()

    if (data.success) {
      reactionUsers.value = data.users
    }
  } catch (error) {
    console.error('Error loading reaction users:', error)
  } finally {
    loadingUsers.value = false
  }
}

// Watch for reaction type changes to load users
watch(selectedReactionType, (newType) => {
  if (showReactionModal.value) {
    loadReactionUsers(newType)
  }
})

// Load users when modal opens
watch(showReactionModal, (isOpen) => {
  if (isOpen && reactionTypesWithCounts.value.length > 0) {
    selectedReactionType.value = reactionTypesWithCounts.value[0].type
    loadReactionUsers(selectedReactionType.value)
  }
})
</script>

<style scoped>
.reaction-btn {
  @apply transition-all duration-200 hover:scale-105;
}

.reaction-btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.reaction-btn:disabled:hover {
  @apply scale-100;
}
</style>