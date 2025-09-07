<!-- ABOUTME: Recent activity monitoring component displaying system events and user actions -->
<!-- ABOUTME: Shows timeline of recent activities, user interactions, and system events with filtering options -->
<template>
  <div class="recent-activity-container">
    <div class="activity-header">
      <h3 class="activity-title">Recent Activity</h3>
      <div class="activity-controls">
        <select 
          v-model="selectedType" 
          @change="filterActivities"
          class="type-filter"
        >
          <option value="all">All Activities</option>
          <option value="user">User Actions</option>
          <option value="system">System Events</option>
          <option value="security">Security Events</option>
          <option value="error">Errors</option>
        </select>
        <button @click="refreshActivities" class="refresh-button" :disabled="loading">
          <svg class="refresh-icon" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Activity Stats -->
    <div class="activity-stats">
      <div class="stat-card">
        <div class="stat-icon user">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ activityStats.userActions }}</div>
          <div class="stat-label">User Actions</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon system">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ activityStats.systemEvents }}</div>
          <div class="stat-label">System Events</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon security">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ activityStats.securityEvents }}</div>
          <div class="stat-label">Security Events</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon error">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ activityStats.errors }}</div>
          <div class="stat-label">Errors</div>
        </div>
      </div>
    </div>

    <!-- Activity Timeline -->
    <div class="activity-timeline">
      <div v-if="loading" class="activity-loading">
        <div class="loading-spinner"></div>
        <span>Loading recent activities...</span>
      </div>

      <div v-else-if="filteredActivities.length === 0" class="no-activities">
        <svg class="no-activities-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p>No recent activities found</p>
      </div>

      <div v-else class="timeline-items">
        <div
          v-for="(activity, index) in filteredActivities"
          :key="activity.id"
          :class="[
            'timeline-item',
            `activity-${activity.type}`,
            { 'timeline-item-last': index === filteredActivities.length - 1 }
          ]"
        >
          <div class="timeline-marker">
            <div :class="['activity-dot', activity.type]"></div>
            <div v-if="index !== filteredActivities.length - 1" class="timeline-line"></div>
          </div>

          <div class="timeline-content">
            <div class="activity-header">
              <div class="activity-info">
                <div class="activity-title">{{ activity.title }}</div>
                <div class="activity-time">{{ formatTime(activity.timestamp) }}</div>
              </div>
              <div class="activity-badge">
                <span :class="['type-badge', activity.type]">{{ activity.type }}</span>
              </div>
            </div>

            <div class="activity-description">{{ activity.description }}</div>

            <div v-if="activity.metadata" class="activity-metadata">
              <div class="metadata-item" v-for="(value, key) in activity.metadata" :key="key">
                <span class="metadata-key">{{ formatKey(key) }}:</span>
                <span class="metadata-value">{{ value }}</span>
              </div>
            </div>

            <div v-if="activity.user" class="activity-user">
              <div class="user-avatar">
                <img v-if="activity.user.avatar" :src="activity.user.avatar" :alt="activity.user.name" />
                <div v-else class="avatar-placeholder">{{ activity.user.name.charAt(0) }}</div>
              </div>
              <div class="user-info">
                <div class="user-name">{{ activity.user.name }}</div>
                <div class="user-role">{{ activity.user.role }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasMoreActivities" class="load-more">
        <button @click="loadMoreActivities" class="load-more-button" :disabled="loadingMore">
          <svg v-if="loadingMore" class="loading-icon animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>{{ loadingMore ? 'Loading...' : 'Load More Activities' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface ActivityUser {
  id: string
  name: string
  role: string
  avatar?: string
}

interface Activity {
  id: string
  title: string
  description: string
  type: 'user' | 'system' | 'security' | 'error'
  timestamp: string
  user?: ActivityUser
  metadata?: Record<string, any>
}

interface Props {
  activities?: Activity[]
  maxDisplayed?: number
  autoRefresh?: boolean
  refreshInterval?: number
}

interface Emits {
  'activity-click': [activity: Activity]
  'load-more': []
  'refresh': []
}

const props = withDefaults(defineProps<Props>(), {
  activities: () => [],
  maxDisplayed: 10,
  autoRefresh: false,
  refreshInterval: 30000
})

const emit = defineEmits<Emits>()

// Refs
const loading = ref(false)
const loadingMore = ref(false)
const selectedType = ref('all')
const displayedCount = ref(props.maxDisplayed)

// Sample data
const sampleActivities = ref<Activity[]>([
  {
    id: '1',
    title: 'User Login',
    description: 'User successfully logged in from new device',
    type: 'user',
    timestamp: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
    user: { id: '1', name: 'John Doe', role: 'Admin' },
    metadata: { ip: '192.168.1.100', device: 'Chrome on Windows' }
  },
  {
    id: '2',
    title: 'System Backup Completed',
    description: 'Automated daily backup completed successfully',
    type: 'system',
    timestamp: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
    metadata: { size: '2.3 GB', duration: '45 minutes' }
  },
  {
    id: '3',
    title: 'Security Alert',
    description: 'Suspicious activity detected and blocked',
    type: 'security',
    timestamp: new Date(Date.now() - 30 * 60 * 1000).toISOString(),
    metadata: { threat: 'SQL Injection', source: 'WAF' }
  },
  {
    id: '4',
    title: 'Database Connection Error',
    description: 'Temporary database connection failure resolved',
    type: 'error',
    timestamp: new Date(Date.now() - 45 * 60 * 1000).toISOString(),
    metadata: { error: 'Connection timeout', duration: '2 minutes' }
  },
  {
    id: '5',
    title: 'Profile Updated',
    description: 'User profile information updated',
    type: 'user',
    timestamp: new Date(Date.now() - 60 * 60 * 1000).toISOString(),
    user: { id: '2', name: 'Jane Smith', role: 'User' },
    metadata: { fields: 'email, phone' }
  }
])

const activityStats = ref({
  userActions: 24,
  systemEvents: 12,
  securityEvents: 3,
  errors: 2
})

// Computed
const activitiesData = computed(() => {
  return props.activities.length > 0 ? props.activities : sampleActivities.value
})

const filteredActivities = computed(() => {
  let filtered = activitiesData.value

  if (selectedType.value !== 'all') {
    filtered = filtered.filter(activity => activity.type === selectedType.value)
  }

  return filtered
    .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
    .slice(0, displayedCount.value)
})

const hasMoreActivities = computed(() => {
  const totalFiltered = selectedType.value === 'all' 
    ? activitiesData.value.length 
    : activitiesData.value.filter(a => a.type === selectedType.value).length
  
  return displayedCount.value < totalFiltered
})

// Methods
const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`
  return date.toLocaleDateString()
}

const formatKey = (key: string): string => {
  return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1')
}

const filterActivities = () => {
  displayedCount.value = props.maxDisplayed
}

const refreshActivities = async () => {
  loading.value = true
  try {
    emit('refresh')
    await new Promise(resolve => setTimeout(resolve, 500))
    displayedCount.value = props.maxDisplayed
  } finally {
    loading.value = false
  }
}

const loadMoreActivities = async () => {
  loadingMore.value = true
  try {
    emit('load-more')
    await new Promise(resolve => setTimeout(resolve, 300))
    displayedCount.value += props.maxDisplayed
  } finally {
    loadingMore.value = false
  }
}

// Lifecycle
onMounted(() => {
  if (props.autoRefresh) {
    setInterval(refreshActivities, props.refreshInterval)
  }
})
</script>

<style scoped>
.recent-activity-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.activity-header {
  @apply flex items-center justify-between mb-6;
}

.activity-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.activity-controls {
  @apply flex items-center gap-3;
}

.type-filter {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.refresh-button {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
  @apply border border-gray-300 dark:border-gray-600 rounded-md;
  @apply hover:bg-gray-50 dark:hover:bg-gray-700;
  @apply transition-colors duration-200;
}

.refresh-icon {
  @apply w-4 h-4;
}

.activity-stats {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6;
}

.stat-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-4 flex items-center gap-3;
}

.stat-icon {
  @apply w-10 h-10 rounded-lg flex items-center justify-center;
}

.stat-icon.user {
  @apply bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400;
}

.stat-icon.system {
  @apply bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400;
}

.stat-icon.security {
  @apply bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400;
}

.stat-icon.error {
  @apply bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400;
}

.stat-icon svg {
  @apply w-5 h-5;
}

.stat-content {
  @apply flex-grow;
}

.stat-value {
  @apply text-xl font-bold text-gray-900 dark:text-white;
}

.stat-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.activity-timeline {
  @apply space-y-4;
}

.activity-loading {
  @apply flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-400;
}

.loading-spinner {
  @apply w-5 h-5 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin;
}

.no-activities {
  @apply flex flex-col items-center justify-center py-8 text-gray-500 dark:text-gray-400;
}

.no-activities-icon {
  @apply w-12 h-12 mb-3;
}

.timeline-items {
  @apply space-y-4;
}

.timeline-item {
  @apply flex gap-4;
}

.timeline-marker {
  @apply flex flex-col items-center;
}

.activity-dot {
  @apply w-3 h-3 rounded-full flex-shrink-0;
}

.activity-dot.user {
  @apply bg-blue-500;
}

.activity-dot.system {
  @apply bg-green-500;
}

.activity-dot.security {
  @apply bg-purple-500;
}

.activity-dot.error {
  @apply bg-red-500;
}

.timeline-line {
  @apply w-px h-full bg-gray-200 dark:bg-gray-600 mt-2;
  min-height: 2rem;
}

.timeline-content {
  @apply flex-grow pb-4;
}

.activity-header {
  @apply flex items-start justify-between mb-2;
}

.activity-info {
  @apply flex-grow;
}

.activity-title {
  @apply font-medium text-gray-900 dark:text-white;
}

.activity-time {
  @apply text-sm text-gray-500 dark:text-gray-400;
}

.activity-badge {
  @apply flex-shrink-0;
}

.type-badge {
  @apply px-2 py-1 text-xs font-medium rounded-full;
}

.type-badge.user {
  @apply bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-400;
}

.type-badge.system {
  @apply bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400;
}

.type-badge.security {
  @apply bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-400;
}

.type-badge.error {
  @apply bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400;
}

.activity-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-3;
}

.activity-metadata {
  @apply space-y-1 mb-3;
}

.metadata-item {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.metadata-key {
  @apply font-medium;
}

.metadata-value {
  @apply ml-1;
}

.activity-user {
  @apply flex items-center gap-3;
}

.user-avatar {
  @apply w-8 h-8 rounded-full overflow-hidden;
}

.user-avatar img {
  @apply w-full h-full object-cover;
}

.avatar-placeholder {
  @apply w-full h-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center;
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.user-info {
  @apply flex-grow;
}

.user-name {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.user-role {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.load-more {
  @apply text-center pt-4 border-t border-gray-200 dark:border-gray-700;
}

.load-more-button {
  @apply flex items-center gap-2 mx-auto px-4 py-2;
  @apply text-sm font-medium text-blue-600 dark:text-blue-400;
  @apply hover:text-blue-800 dark:hover:text-blue-300;
  @apply transition-colors duration-200;
}

.loading-icon {
  @apply w-4 h-4;
}

/* Responsive Design */
@media (max-width: 768px) {
  .activity-header {
    @apply flex-col items-start gap-3;
  }
  
  .activity-stats {
    @apply grid-cols-1;
  }
  
  .timeline-item {
    @apply gap-3;
  }
  
  .activity-header {
    @apply flex-col items-start gap-1;
  }
}

@media (max-width: 640px) {
  .recent-activity-container {
    @apply p-4;
  }
}
</style>