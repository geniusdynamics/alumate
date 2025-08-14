<template>
  <div class="performance-demo">
    <div class="container mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
          Performance Monitoring Demo
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          This page demonstrates the performance monitoring integration with loading states, 
          skeleton screens, and real-time metrics.
        </p>
      </div>

      <!-- Performance Dashboard -->
      <div class="mb-8">
        <PerformanceDashboard />
      </div>

      <!-- Demo Controls -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Demo Controls</h2>
        <div class="flex flex-wrap gap-4">
          <button 
            @click="simulateApiCall" 
            :disabled="isLoading"
            class="btn btn-primary"
          >
            Simulate API Call
          </button>
          <button 
            @click="simulateFormSubmission" 
            :disabled="isLoading"
            class="btn btn-secondary"
          >
            Simulate Form Submit
          </button>
          <button 
            @click="simulateSearch" 
            :disabled="isLoading"
            class="btn btn-accent"
          >
            Simulate Search
          </button>
          <button 
            @click="triggerError" 
            class="btn btn-danger"
          >
            Trigger Error
          </button>
        </div>
      </div>

      <!-- Loading Optimizer Demo -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- User List with Skeleton -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">User List (Skeleton Demo)</h3>
          <LoadingOptimizer 
            :loading="userListLoading" 
            skeleton="user-list"
            :skeleton-props="{ count: 5 }"
            loading-message="Loading users..."
            show-hints
            @loading-start="trackInteraction('user-list-load-start')"
            @loading-complete="trackInteraction('user-list-load-complete')"
          >
            <div class="space-y-4">
              <div 
                v-for="user in users" 
                :key="user.id"
                class="flex items-center space-x-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <img 
                  :src="user.avatar" 
                  :alt="user.name"
                  class="w-12 h-12 rounded-full"
                  loading="lazy"
                >
                <div>
                  <div class="font-medium">{{ user.name }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</div>
                </div>
              </div>
            </div>
          </LoadingOptimizer>
          <button 
            @click="loadUsers" 
            :disabled="userListLoading"
            class="btn btn-primary mt-4"
          >
            Load Users
          </button>
        </div>

        <!-- Post List with Skeleton -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Post List (Skeleton Demo)</h3>
          <LoadingOptimizer 
            :loading="postListLoading" 
            skeleton="post-list"
            :skeleton-props="{ count: 3, showImage: true }"
            loading-message="Loading posts..."
            show-hints
          >
            <div class="space-y-6">
              <div 
                v-for="post in posts" 
                :key="post.id"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
              >
                <div class="flex items-center space-x-3 mb-3">
                  <img 
                    :src="post.author.avatar" 
                    :alt="post.author.name"
                    class="w-10 h-10 rounded-full"
                    loading="lazy"
                  >
                  <div>
                    <div class="font-medium">{{ post.author.name }}</div>
                    <div class="text-sm text-gray-500">{{ post.createdAt }}</div>
                  </div>
                </div>
                <h4 class="font-semibold mb-2">{{ post.title }}</h4>
                <p class="text-gray-600 dark:text-gray-400 mb-3">{{ post.content }}</p>
                <img 
                  v-if="post.image" 
                  :src="post.image" 
                  :alt="post.title"
                  class="w-full h-48 object-cover rounded-lg"
                  loading="lazy"
                >
              </div>
            </div>
          </LoadingOptimizer>
          <button 
            @click="loadPosts" 
            :disabled="postListLoading"
            class="btn btn-primary mt-4"
          >
            Load Posts
          </button>
        </div>
      </div>

      <!-- Real-time Metrics -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Real-time Performance Metrics</h3>
          <button 
            @click="isMonitoring ? stopMonitoring() : startMonitoring()"
            :class="isMonitoring ? 'btn-danger' : 'btn-primary'"
            class="btn"
          >
            {{ isMonitoring ? 'Stop Monitoring' : 'Start Monitoring' }}
          </button>
        </div>
        
        <div v-if="realTimeMetrics.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div 
            v-for="metric in realTimeMetrics.slice(-8)" 
            :key="metric.timestamp"
            class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div class="font-semibold">{{ metric.name }}</div>
            <div class="text-lg">{{ formatMetricValue(metric.name, metric.value) }}</div>
            <div class="text-xs text-gray-500">{{ formatTimestamp(metric.timestamp) }}</div>
          </div>
        </div>
        
        <div v-else class="text-center py-8 text-gray-500">
          {{ isMonitoring ? 'Waiting for metrics...' : 'Start monitoring to see real-time metrics' }}
        </div>
      </div>

      <!-- Performance Recommendations -->
      <div v-if="performanceData.recommendations.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Performance Recommendations</h3>
        <div class="space-y-3">
          <div 
            v-for="rec in performanceData.recommendations" 
            :key="rec.id"
            class="flex items-start space-x-3 p-3 rounded-lg"
            :class="getRecommendationClass(rec.priority)"
          >
            <component 
              :is="getRecommendationIcon(rec.priority)" 
              class="w-5 h-5 mt-0.5 flex-shrink-0"
            />
            <div>
              <div class="font-medium">{{ rec.title }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ rec.description }}
              </div>
              <div v-if="rec.impact" class="text-xs text-gray-500 mt-1">
                Expected improvement: {{ rec.impact }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePerformanceMonitoring, useRealTimePerformance } from '../../Composables/usePerformanceMonitoring'
import PerformanceDashboard from '../../Components/Performance/PerformanceDashboard.vue'
import LoadingOptimizer from '../../Components/Performance/LoadingOptimizer.vue'
import { 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  CheckCircleIcon 
} from '@heroicons/vue/24/outline'

// Performance monitoring
const {
  isLoading,
  performanceData,
  startLoading,
  endLoading,
  trackApiCall,
  trackInteraction,
  trackFormSubmission,
  trackSearch,
  getPerformanceRecommendations
} = usePerformanceMonitoring('PerformanceDemo')

// Real-time monitoring
const {
  realTimeMetrics,
  isMonitoring,
  startMonitoring,
  stopMonitoring
} = useRealTimePerformance()

// Demo data
const users = ref([])
const posts = ref([])
const userListLoading = ref(false)
const postListLoading = ref(false)

// Mock data
const mockUsers = [
  { id: 1, name: 'John Doe', email: 'john@example.com', avatar: 'https://via.placeholder.com/48' },
  { id: 2, name: 'Jane Smith', email: 'jane@example.com', avatar: 'https://via.placeholder.com/48' },
  { id: 3, name: 'Bob Johnson', email: 'bob@example.com', avatar: 'https://via.placeholder.com/48' },
  { id: 4, name: 'Alice Brown', email: 'alice@example.com', avatar: 'https://via.placeholder.com/48' },
  { id: 5, name: 'Charlie Wilson', email: 'charlie@example.com', avatar: 'https://via.placeholder.com/48' }
]

const mockPosts = [
  {
    id: 1,
    title: 'Welcome to the Alumni Platform',
    content: 'This is a sample post to demonstrate the performance monitoring features.',
    author: { name: 'John Doe', avatar: 'https://via.placeholder.com/40' },
    createdAt: '2 hours ago',
    image: 'https://via.placeholder.com/400x200'
  },
  {
    id: 2,
    title: 'Performance Optimization Tips',
    content: 'Learn how to optimize your web applications for better performance.',
    author: { name: 'Jane Smith', avatar: 'https://via.placeholder.com/40' },
    createdAt: '4 hours ago',
    image: 'https://via.placeholder.com/400x200'
  },
  {
    id: 3,
    title: 'Real-time Monitoring',
    content: 'Monitor your application performance in real-time with our tools.',
    author: { name: 'Bob Johnson', avatar: 'https://via.placeholder.com/40' },
    createdAt: '6 hours ago',
    image: 'https://via.placeholder.com/400x200'
  }
]

// Demo functions
const simulateApiCall = async () => {
  await trackApiCall(async () => {
    startLoading('api-call')
    
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, Math.random() * 2000 + 1000))
    
    endLoading('api-call', true)
    
    return { success: true, data: 'API call completed' }
  }, { endpoint: '/api/demo' })
}

const simulateFormSubmission = async () => {
  await trackFormSubmission(async () => {
    // Simulate form processing
    await new Promise(resolve => setTimeout(resolve, Math.random() * 1500 + 500))
    
    return { success: true, message: 'Form submitted successfully' }
  }, 'demo-form')
}

const simulateSearch = async () => {
  const query = 'performance monitoring'
  
  await trackSearch(async () => {
    startLoading('search')
    
    // Simulate search delay
    await new Promise(resolve => setTimeout(resolve, Math.random() * 1000 + 500))
    
    endLoading('search', true)
    
    return { results: [], total: 0 }
  }, query, { category: 'demo' })
}

const triggerError = async () => {
  try {
    await trackApiCall(async () => {
      startLoading('error-demo')
      
      // Simulate error
      await new Promise((_, reject) => 
        setTimeout(() => reject(new Error('Simulated error')), 1000)
      )
    }, { endpoint: '/api/error' })
  } catch (error) {
    endLoading('error-demo', false, error)
    console.error('Demo error:', error)
  }
}

const loadUsers = async () => {
  userListLoading.value = true
  
  try {
    // Simulate loading delay
    await new Promise(resolve => setTimeout(resolve, 2000))
    users.value = mockUsers
  } finally {
    userListLoading.value = false
  }
}

const loadPosts = async () => {
  postListLoading.value = true
  
  try {
    // Simulate loading delay
    await new Promise(resolve => setTimeout(resolve, 2500))
    posts.value = mockPosts
  } finally {
    postListLoading.value = false
  }
}

// Utility functions
const formatMetricValue = (name: string, value: number): string => {
  if (name === 'CLS') {
    return value.toFixed(3)
  }
  return `${Math.round(value)}ms`
}

const formatTimestamp = (timestamp: number): string => {
  return new Date(timestamp).toLocaleTimeString()
}

const getRecommendationClass = (priority: string): string => {
  switch (priority) {
    case 'high': return 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
    case 'medium': return 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800'
    case 'low': return 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800'
    default: return 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600'
  }
}

const getRecommendationIcon = (priority: string) => {
  switch (priority) {
    case 'high': return ExclamationTriangleIcon
    case 'medium': return InformationCircleIcon
    case 'low': return CheckCircleIcon
    default: return InformationCircleIcon
  }
}

// Initialize
onMounted(async () => {
  await getPerformanceRecommendations()
})
</script>

<style scoped>
.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-gray-500;
}

.btn-accent {
  @apply text-white bg-green-600 hover:bg-green-700 focus:ring-green-500;
}

.btn-danger {
  @apply text-white bg-red-600 hover:bg-red-700 focus:ring-red-500;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>