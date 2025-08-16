<template>
  <div class="group-participation-chart">
    <div class="chart-header">
      <h4 class="chart-title">Group Participation</h4>
    </div>
    
    <div class="chart-container">
      <div class="participation-list">
        <div
          v-for="(group, index) in topGroups"
          :key="index"
          class="participation-item"
        >
          <div class="group-info">
            <span class="group-name">{{ group.name }}</span>
            <div class="group-stats">
              <span class="stat">{{ group.members_count }} members</span>
              <span class="stat">{{ group.posts_count }} posts</span>
            </div>
          </div>
          <div class="participation-bar">
            <div
              class="participation-fill"
              :style="{ width: `${getParticipationPercentage(group)}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface GroupData {
  name: string
  members_count: number
  posts_count: number
}

interface Props {
  data: GroupData[]
}

const props = defineProps<Props>()

const topGroups = computed(() => {
  if (!props.data || !Array.isArray(props.data)) {
    return []
  }
  return props.data.slice(0, 5)
})

const maxPosts = computed(() => {
  return Math.max(...topGroups.value.map(g => g.posts_count), 1)
})

const getParticipationPercentage = (group: GroupData): number => {
  return (group.posts_count / maxPosts.value) * 100
}
</script>

<style scoped>
.group-participation-chart {
  @apply w-full h-full;
}

.chart-header {
  @apply mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.chart-container {
  @apply space-y-4;
}

.participation-item {
  @apply space-y-2;
}

.group-info {
  @apply flex items-center justify-between;
}

.group-name {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.group-stats {
  @apply flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400;
}

.participation-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2;
}

.participation-fill {
  @apply h-2 bg-blue-500 rounded-full transition-all duration-300;
}
</style>