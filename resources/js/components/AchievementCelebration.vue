<template>
  <div class="achievement-celebration">
    <div class="celebration-header">
      <div class="user-info">
        <img 
          :src="celebration.user_achievement.user.avatar_url || '/default-avatar.png'"
          :alt="celebration.user_achievement.user.name"
          class="user-avatar"
        />
        <div>
          <h3 class="user-name">{{ celebration.user_achievement.user.name }}</h3>
          <p class="celebration-time">{{ formatTime(celebration.created_at) }}</p>
        </div>
      </div>
      
      <div class="achievement-info">
        <AchievementBadge 
          :achievement="celebration.user_achievement.achievement"
          :is-earned="true"
          :earned-at="celebration.user_achievement.earned_at"
          :show-actions="false"
          @click="showAchievementDetails"
        />
      </div>
    </div>
    
    <div class="celebration-content">
      <div class="celebration-message">
        {{ celebration.message }}
      </div>
      
      <div class="celebration-actions">
        <button
          @click="toggleCongratulation"
          :class="[
            'congratulate-btn',
            { 'congratulated': hasCongratulated }
          ]"
          :disabled="loading"
        >
          <Icon name="heart" />
          <span>{{ hasCongratulated ? 'Congratulated' : 'Congratulate' }}</span>
          <span class="count">{{ celebration.congratulations_count }}</span>
        </button>
        
        <button
          @click="showCongratulations = !showCongratulations"
          class="view-congratulations-btn"
        >
          <Icon name="message-circle" />
          <span>View Congratulations</span>
        </button>
        
        <button
          v-if="celebration.post_id"
          @click="viewPost"
          class="view-post-btn"
        >
          <Icon name="external-link" />
          <span>View Post</span>
        </button>
      </div>
    </div>
    
    <!-- Congratulations Section -->
    <div v-if="showCongratulations" class="congratulations-section">
      <div class="congratulations-header">
        <h4>Congratulations ({{ celebration.congratulations_count }})</h4>
        
        <div v-if="!hasCongratulated" class="add-congratulation">
          <textarea
            v-model="congratulationMessage"
            placeholder="Add a congratulatory message..."
            class="congratulation-input"
            rows="2"
          ></textarea>
          <button
            @click="addCongratulation"
            :disabled="loading"
            class="send-congratulation-btn"
          >
            <Icon name="send" />
          </button>
        </div>
      </div>
      
      <div class="congratulations-list">
        <div
          v-for="congratulation in congratulations"
          :key="congratulation.id"
          class="congratulation-item"
        >
          <img 
            :src="congratulation.user.avatar_url || '/default-avatar.png'"
            :alt="congratulation.user.name"
            class="congratulation-avatar"
          />
          <div class="congratulation-content">
            <div class="congratulation-header">
              <span class="congratulation-user">{{ congratulation.user.name }}</span>
              <span class="congratulation-time">{{ formatTime(congratulation.created_at) }}</span>
            </div>
            <p v-if="congratulation.message" class="congratulation-message">
              {{ congratulation.message }}
            </p>
          </div>
        </div>
        
        <div v-if="congratulations.length === 0" class="no-congratulations">
          No congratulations yet. Be the first to congratulate!
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AchievementBadge from './AchievementBadge.vue'
import Icon from './Icon.vue'

interface User {
  id: number
  name: string
  avatar_url?: string
}

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

interface UserAchievement {
  id: number
  user: User
  achievement: Achievement
  earned_at: string
}

interface Congratulation {
  id: number
  user: User
  message?: string
  created_at: string
}

interface Celebration {
  id: number
  user_achievement: UserAchievement
  message: string
  congratulations_count: number
  post_id?: number
  created_at: string
}

interface Props {
  celebration: Celebration
  currentUser?: User
}

const props = defineProps<Props>()

const showCongratulations = ref(false)
const congratulations = ref<Congratulation[]>([])
const congratulationMessage = ref('')
const loading = ref(false)
const hasCongratulated = ref(false)

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)
  
  if (diffInHours < 1) {
    return 'Just now'
  } else if (diffInHours < 24) {
    return `${Math.floor(diffInHours)}h ago`
  } else {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    })
  }
}

const toggleCongratulation = async () => {
  if (loading.value) return
  
  loading.value = true
  
  try {
    if (hasCongratulated.value) {
      await axios.delete(`/api/achievement-celebrations/${props.celebration.id}/congratulations`)
      hasCongratulated.value = false
    } else {
      await axios.post(`/api/achievement-celebrations/${props.celebration.id}/congratulations`, {
        message: congratulationMessage.value || null
      })
      hasCongratulated.value = true
      congratulationMessage.value = ''
    }
    
    // Refresh congratulations if showing
    if (showCongratulations.value) {
      await loadCongratulations()
    }
  } catch (error) {
    console.error('Error toggling congratulation:', error)
  } finally {
    loading.value = false
  }
}

const addCongratulation = async () => {
  if (loading.value || !congratulationMessage.value.trim()) return
  
  loading.value = true
  
  try {
    await axios.post(`/api/achievement-celebrations/${props.celebration.id}/congratulations`, {
      message: congratulationMessage.value
    })
    
    hasCongratulated.value = true
    congratulationMessage.value = ''
    await loadCongratulations()
  } catch (error) {
    console.error('Error adding congratulation:', error)
  } finally {
    loading.value = false
  }
}

const loadCongratulations = async () => {
  try {
    const response = await axios.get(`/api/achievement-celebrations/${props.celebration.id}/congratulations`)
    congratulations.value = response.data.data
    
    // Check if current user has congratulated
    if (props.currentUser) {
      hasCongratulated.value = congratulations.value.some(
        c => c.user.id === props.currentUser?.id
      )
    }
  } catch (error) {
    console.error('Error loading congratulations:', error)
  }
}

const showAchievementDetails = () => {
  // Navigate to achievement details page
  router.visit(`/achievements/${props.celebration.user_achievement.achievement.id}`)
}

const viewPost = () => {
  if (props.celebration.post_id) {
    router.visit(`/posts/${props.celebration.post_id}`)
  }
}

onMounted(() => {
  if (showCongratulations.value) {
    loadCongratulations()
  }
})
</script>

<style scoped>
.achievement-celebration {
  @apply bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-4;
}

.celebration-header {
  @apply flex items-start justify-between mb-4;
}

.user-info {
  @apply flex items-center space-x-3;
}

.user-avatar {
  @apply w-12 h-12 rounded-full object-cover;
}

.user-name {
  @apply font-semibold text-gray-900;
}

.celebration-time {
  @apply text-sm text-gray-500;
}

.achievement-info {
  @apply flex-1 max-w-md;
}

.celebration-content {
  @apply space-y-4;
}

.celebration-message {
  @apply text-gray-700 leading-relaxed;
}

.celebration-actions {
  @apply flex items-center space-x-4;
}

.congratulate-btn {
  @apply flex items-center space-x-2 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors;
}

.congratulate-btn.congratulated {
  @apply bg-red-50 border-red-300 text-red-700;
}

.congratulate-btn .count {
  @apply bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs;
}

.view-congratulations-btn,
.view-post-btn {
  @apply flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors;
}

.congratulations-section {
  @apply mt-6 pt-6 border-t border-gray-200;
}

.congratulations-header {
  @apply mb-4;
}

.congratulations-header h4 {
  @apply font-semibold text-gray-900 mb-3;
}

.add-congratulation {
  @apply flex space-x-2;
}

.congratulation-input {
  @apply flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.send-congratulation-btn {
  @apply px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.congratulations-list {
  @apply space-y-3;
}

.congratulation-item {
  @apply flex items-start space-x-3;
}

.congratulation-avatar {
  @apply w-8 h-8 rounded-full object-cover flex-shrink-0;
}

.congratulation-content {
  @apply flex-1 min-w-0;
}

.congratulation-header {
  @apply flex items-center space-x-2 mb-1;
}

.congratulation-user {
  @apply font-medium text-gray-900 text-sm;
}

.congratulation-time {
  @apply text-xs text-gray-500;
}

.congratulation-message {
  @apply text-sm text-gray-700;
}

.no-congratulations {
  @apply text-center text-gray-500 py-4;
}
</style>