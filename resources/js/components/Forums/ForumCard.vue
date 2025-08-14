<template>
  <div
    class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200 cursor-pointer"
    @click="$emit('click')"
  >
    <div class="p-6">
      <div class="flex items-start justify-between">
        <div class="flex items-start space-x-4 flex-1">
          <!-- Forum Icon -->
          <div
            class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl"
            :style="{ backgroundColor: forum.color }"
          >
            <span v-if="forum.icon">{{ forum.icon }}</span>
            <ChatBubbleLeftRightIcon v-else class="h-6 w-6" />
          </div>

          <!-- Forum Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
              <h3 class="text-lg font-semibold text-gray-900 truncate">
                {{ forum.name }}
              </h3>
              <span
                v-if="forum.visibility === 'private'"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
              >
                <LockClosedIcon class="h-3 w-3 mr-1" />
                Private
              </span>
              <span
                v-else-if="forum.visibility === 'group_only'"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800"
              >
                <UserGroupIcon class="h-3 w-3 mr-1" />
                Group Only
              </span>
            </div>

            <p v-if="forum.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
              {{ forum.description }}
            </p>

            <div v-if="forum.group" class="mt-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                <BuildingOfficeIcon class="h-3 w-3 mr-1" />
                {{ forum.group.name }}
              </span>
            </div>
          </div>
        </div>

        <!-- Forum Stats -->
        <div class="flex-shrink-0 text-right">
          <div class="text-sm text-gray-500">
            <div class="flex items-center space-x-4">
              <div class="text-center">
                <div class="text-lg font-semibold text-gray-900">{{ forum.topics_count }}</div>
                <div class="text-xs">Topics</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-semibold text-gray-900">{{ forum.posts_count }}</div>
                <div class="text-xs">Posts</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Latest Topics Preview -->
      <div v-if="forum.latest_topics && forum.latest_topics.length > 0" class="mt-4 pt-4 border-t border-gray-200">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Recent Topics</h4>
        <div class="space-y-2">
          <div
            v-for="topic in forum.latest_topics.slice(0, 3)"
            :key="topic.id"
            class="flex items-center justify-between text-sm"
          >
            <div class="flex items-center space-x-2 flex-1 min-w-0">
              <span
                v-if="topic.is_sticky"
                class="flex-shrink-0 w-4 h-4 text-yellow-500"
                title="Sticky"
              >
                📌
              </span>
              <span
                v-if="topic.is_announcement"
                class="flex-shrink-0 w-4 h-4 text-blue-500"
                title="Announcement"
              >
                📢
              </span>
              <span class="text-gray-900 truncate font-medium">{{ topic.title }}</span>
            </div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 flex-shrink-0">
              <span>{{ topic.posts_count }} posts</span>
              <span>•</span>
              <span>{{ formatTimeAgo(topic.last_post_at || topic.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Last Activity -->
      <div v-if="forum.last_activity_at" class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between text-sm text-gray-500">
          <span>Last activity</span>
          <span>{{ formatTimeAgo(forum.last_activity_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ChatBubbleLeftRightIcon,
  LockClosedIcon,
  UserGroupIcon,
  BuildingOfficeIcon
} from '@heroicons/vue/24/outline'

// Props
defineProps({
  forum: {
    type: Object,
    required: true
  }
})

// Emits
defineEmits(['click'])

// Methods
const formatTimeAgo = (date) => {
  const now = new Date()
  const past = new Date(date)
  const diffInSeconds = Math.floor((now - past) / 1000)
  
  if (diffInSeconds < 60) return 'just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  return `${Math.floor(diffInSeconds / 86400)}d ago`
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>