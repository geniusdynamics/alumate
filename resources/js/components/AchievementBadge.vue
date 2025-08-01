<template>
  <div 
    :class="[
      'achievement-badge',
      `rarity-${achievement.rarity}`,
      { 'featured': isFeatured, 'earned': isEarned }
    ]"
    @click="$emit('click')"
  >
    <div class="badge-container">
      <div class="badge-icon">
        <Icon 
          :name="achievement.icon || achievement.category_icon" 
          :class="['icon', `text-${rarityColor}-500`]"
        />
      </div>
      
      <div class="badge-content">
        <h4 class="badge-title">{{ achievement.name }}</h4>
        <p class="badge-description">{{ achievement.description }}</p>
        
        <div class="badge-meta">
          <span class="category">{{ categoryLabel }}</span>
          <span class="rarity" :class="`text-${rarityColor}-600`">
            {{ rarityLabel }}
          </span>
          <span class="points">{{ achievement.points }} pts</span>
        </div>
        
        <div v-if="earnedAt" class="earned-date">
          Earned {{ formatDate(earnedAt) }}
        </div>
      </div>
      
      <div v-if="showActions" class="badge-actions">
        <button
          v-if="isEarned && canToggleFeatured"
          @click.stop="toggleFeatured"
          :class="[
            'feature-btn',
            { 'featured': isFeatured }
          ]"
          :title="isFeatured ? 'Remove from featured' : 'Add to featured'"
        >
          <Icon name="star" />
        </button>
      </div>
    </div>
    
    <div v-if="showProgress && progress !== null" class="progress-bar">
      <div 
        class="progress-fill" 
        :style="{ width: `${Math.min(progress, 100)}%` }"
      ></div>
      <span class="progress-text">{{ Math.round(progress) }}%</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Icon from './Icon.vue'

interface Achievement {
  id: number
  name: string
  description: string
  icon?: string
  category: string
  category_icon: string
  rarity: string
  rarity_color: string
  points: number
}

interface Props {
  achievement: Achievement
  isEarned?: boolean
  isFeatured?: boolean
  earnedAt?: string
  showActions?: boolean
  showProgress?: boolean
  progress?: number | null
  canToggleFeatured?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isEarned: false,
  isFeatured: false,
  showActions: true,
  showProgress: false,
  progress: null,
  canToggleFeatured: true
})

const emit = defineEmits<{
  click: []
  toggleFeatured: []
}>()

const rarityColor = computed(() => props.achievement.rarity_color)

const categoryLabel = computed(() => {
  const categories: Record<string, string> = {
    career: 'Career',
    education: 'Education',
    community: 'Community',
    milestone: 'Milestone',
    special: 'Special'
  }
  return categories[props.achievement.category] || 'Unknown'
})

const rarityLabel = computed(() => {
  const rarities: Record<string, string> = {
    common: 'Common',
    uncommon: 'Uncommon',
    rare: 'Rare',
    epic: 'Epic',
    legendary: 'Legendary'
  }
  return rarities[props.achievement.rarity] || 'Unknown'
})

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const toggleFeatured = () => {
  emit('toggleFeatured')
}
</script>

<style scoped>
.achievement-badge {
  @apply bg-white rounded-lg shadow-md border-2 border-gray-200 p-4 cursor-pointer transition-all duration-200 hover:shadow-lg;
}

.achievement-badge.earned {
  @apply border-green-300 bg-green-50;
}

.achievement-badge.featured {
  @apply ring-2 ring-yellow-400 ring-opacity-50;
}

.achievement-badge.rarity-uncommon {
  @apply border-green-300;
}

.achievement-badge.rarity-rare {
  @apply border-blue-400;
}

.achievement-badge.rarity-epic {
  @apply border-purple-400;
}

.achievement-badge.rarity-legendary {
  @apply border-yellow-400 bg-gradient-to-br from-yellow-50 to-orange-50;
}

.badge-container {
  @apply flex items-start space-x-3;
}

.badge-icon {
  @apply flex-shrink-0 w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center;
}

.badge-icon .icon {
  @apply w-6 h-6;
}

.badge-content {
  @apply flex-1 min-w-0;
}

.badge-title {
  @apply text-lg font-semibold text-gray-900 mb-1;
}

.badge-description {
  @apply text-sm text-gray-600 mb-2;
}

.badge-meta {
  @apply flex items-center space-x-2 text-xs;
}

.badge-meta span {
  @apply px-2 py-1 rounded-full bg-gray-100 text-gray-700;
}

.earned-date {
  @apply text-xs text-gray-500 mt-2;
}

.badge-actions {
  @apply flex-shrink-0;
}

.feature-btn {
  @apply p-2 rounded-full text-gray-400 hover:text-yellow-500 transition-colors;
}

.feature-btn.featured {
  @apply text-yellow-500;
}

.progress-bar {
  @apply mt-3 relative bg-gray-200 rounded-full h-2;
}

.progress-fill {
  @apply bg-blue-500 h-full rounded-full transition-all duration-300;
}

.progress-text {
  @apply absolute inset-0 flex items-center justify-center text-xs font-medium text-gray-700;
}
</style>