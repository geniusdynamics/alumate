<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Enhanced Statistics Counters Demo
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
          Showcasing animated statistics counters with real-time data integration, 
          accessibility features, error handling, and internationalization support.
        </p>
      </div>

      <!-- Demo Controls -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
          Demo Controls
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Layout
            </label>
            <select 
              v-model="demoConfig.layout" 
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option value="horizontal">Horizontal</option>
              <option value="vertical">Vertical</option>
              <option value="grid">Grid</option>
              <option value="compact">Compact</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Size
            </label>
            <select 
              v-model="demoConfig.size" 
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option value="sm">Small</option>
              <option value="md">Medium</option>
              <option value="lg">Large</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Animation Duration (ms)
            </label>
            <input 
              v-model.number="demoConfig.animationDuration" 
              type="number" 
              min="500" 
              max="5000" 
              step="100"
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Locale
            </label>
            <select 
              v-model="demoConfig.locale" 
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option value="en-US">English (US)</option>
              <option value="en-GB">English (UK)</option>
              <option value="de-DE">German</option>
              <option value="fr-FR">French</option>
              <option value="es-ES">Spanish</option>
              <option value="ja-JP">Japanese</option>
            </select>
          </div>
        </div>
        
        <div class="mt-4 flex flex-wrap gap-4">
          <label class="flex items-center">
            <input 
              v-model="demoConfig.showLastUpdated" 
              type="checkbox" 
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Show Last Updated</span>
          </label>
          
          <label class="flex items-center">
            <input 
              v-model="demoConfig.showActions" 
              type="checkbox" 
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Show Actions</span>
          </label>
          
          <label class="flex items-center">
            <input 
              v-model="demoConfig.enableRealTime" 
              type="checkbox" 
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable Real-time Updates</span>
          </label>
          
          <label class="flex items-center">
            <input 
              v-model="demoConfig.respectReducedMotion" 
              type="checkbox" 
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Respect Reduced Motion</span>
          </label>
        </div>
      </div>

      <!-- Statistics Demos -->
      <div class="space-y-12">
        <!-- Individual Alumni Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
          <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Individual Alumni Statistics</h3>
            <p class="text-blue-100">Real-time platform metrics for individual users</p>
          </div>
          <div class="p-6">
            <StatisticsDisplay
              :statistics="individualStatistics"
              :layout="demoConfig.layout"
              :size="demoConfig.size"
              :animation-duration="demoConfig.animationDuration"
              :animation-easing="'ease-out'"
              :stagger-delay="200"
              :locale="demoConfig.locale"
              :refresh-interval="30000"
              :retry-attempts="3"
              :respect-reduced-motion="demoConfig.respectReducedMotion"
              :show-last-updated="demoConfig.showLastUpdated"
              :show-actions="demoConfig.showActions"
              :show-refresh="true"
              :enable-real-time="demoConfig.enableRealTime"
              :cache-enabled="true"
              @animation-start="handleAnimationStart"
              @animation-complete="handleAnimationComplete"
              @data-loaded="handleDataLoaded"
              @data-error="handleDataError"
              @all-animations-complete="handleAllAnimationsComplete"
            />
          </div>
        </div>

        <!-- Institution Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
          <div class="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Institution Statistics</h3>
            <p class="text-green-100">Institutional engagement and partnership metrics</p>
          </div>
          <div class="p-6">
            <StatisticsDisplay
              :statistics="institutionStatistics"
              :layout="demoConfig.layout"
              :size="demoConfig.size"
              :animation-duration="demoConfig.animationDuration"
              :animation-easing="'ease-out'"
              :stagger-delay="200"
              :locale="demoConfig.locale"
              :refresh-interval="30000"
              :retry-attempts="3"
              :respect-reduced-motion="demoConfig.respectReducedMotion"
              :show-last-updated="demoConfig.showLastUpdated"
              :show-actions="demoConfig.showActions"
              :show-refresh="true"
              :enable-real-time="demoConfig.enableRealTime"
              :cache-enabled="true"
              @animation-start="handleAnimationStart"
              @animation-complete="handleAnimationComplete"
              @data-loaded="handleDataLoaded"
              @data-error="handleDataError"
              @all-animations-complete="handleAllAnimationsComplete"
            />
          </div>
        </div>

        <!-- Employer Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
          <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Employer Statistics</h3>
            <p class="text-orange-100">Recruitment and hiring effectiveness metrics</p>
          </div>
          <div class="p-6">
            <StatisticsDisplay
              :statistics="employerStatistics"
              :layout="demoConfig.layout"
              :size="demoConfig.size"
              :animation-duration="demoConfig.animationDuration"
              :animation-easing="'ease-out'"
              :stagger-delay="200"
              :locale="demoConfig.locale"
              :refresh-interval="30000"
              :retry-attempts="3"
              :respect-reduced-motion="demoConfig.respectReducedMotion"
              :show-last-updated="demoConfig.showLastUpdated"
              :show-actions="demoConfig.showActions"
              :show-refresh="true"
              :enable-real-time="demoConfig.enableRealTime"
              :cache-enabled="true"
              @animation-start="handleAnimationStart"
              @animation-complete="handleAnimationComplete"
              @data-loaded="handleDataLoaded"
              @data-error="handleDataError"
              @all-animations-complete="handleAllAnimationsComplete"
            />
          </div>
        </div>

        <!-- Error Simulation Demo -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
          <div class="bg-gradient-to-r from-red-600 to-pink-600 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Error Handling Demo</h3>
            <p class="text-red-100">Demonstrates error states and retry functionality</p>
          </div>
          <div class="p-6">
            <StatisticsDisplay
              :statistics="errorStatistics"
              :layout="demoConfig.layout"
              :size="demoConfig.size"
              :animation-duration="demoConfig.animationDuration"
              :animation-easing="'ease-out'"
              :stagger-delay="200"
              :locale="demoConfig.locale"
              :refresh-interval="30000"
              :retry-attempts="3"
              :respect-reduced-motion="demoConfig.respectReducedMotion"
              :show-last-updated="demoConfig.showLastUpdated"
              :show-actions="demoConfig.showActions"
              :show-refresh="true"
              :enable-real-time="demoConfig.enableRealTime"
              :cache-enabled="true"
              @animation-start="handleAnimationStart"
              @animation-complete="handleAnimationComplete"
              @data-loaded="handleDataLoaded"
              @data-error="handleDataError"
              @all-animations-complete="handleAllAnimationsComplete"
            />
          </div>
        </div>
      </div>

      <!-- Event Log -->
      <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Event Log</h3>
          <button 
            @click="clearEventLog"
            class="px-4 py-2 text-sm bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
          >
            Clear Log
          </button>
        </div>
        <div class="max-h-64 overflow-y-auto">
          <div v-if="eventLog.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-4">
            No events logged yet. Interact with the statistics above to see events.
          </div>
          <div v-else class="space-y-2">
            <div 
              v-for="(event, index) in eventLog" 
              :key="index"
              class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md text-sm"
            >
              <div class="flex-1">
                <span class="font-medium text-gray-900 dark:text-white">{{ event.type }}</span>
                <span class="text-gray-600 dark:text-gray-400 ml-2">{{ event.message }}</span>
              </div>
              <span class="text-xs text-gray-500 dark:text-gray-500">
                {{ formatTime(event.timestamp) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import StatisticsDisplay from '@/components/ComponentLibrary/Hero/StatisticsDisplay.vue'
import type { StatisticCounter } from '@/types/components'

// Demo configuration
const demoConfig = reactive({
  layout: 'horizontal' as 'horizontal' | 'vertical' | 'grid' | 'compact',
  size: 'md' as 'sm' | 'md' | 'lg',
  animationDuration: 2000,
  locale: 'en-US',
  showLastUpdated: false,
  showActions: true,
  enableRealTime: true,
  respectReducedMotion: true
})

// Event logging
const eventLog = ref<Array<{
  type: string
  message: string
  timestamp: Date
}>>([])

// Statistics data
const individualStatistics: StatisticCounter[] = [
  {
    id: 'alumni-count',
    value: 25000,
    label: 'Active Alumni',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: '/api/statistics/alumni-count'
  },
  {
    id: 'connections-made',
    value: 150000,
    label: 'Connections Made',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: '/api/statistics/connections-made'
  },
  {
    id: 'job-placements',
    value: 8500,
    label: 'Job Placements',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: '/api/statistics/job-placements'
  },
  {
    id: 'avg-salary-increase',
    value: 35,
    label: 'Avg Salary Increase',
    suffix: '%',
    animated: true,
    source: 'manual'
  }
]

const institutionStatistics: StatisticCounter[] = [
  {
    id: 'institutions-served',
    value: 500,
    label: 'Institutions Served',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: '/api/statistics/institutions-served'
  },
  {
    id: 'engagement-increase',
    value: 85,
    label: 'Engagement Increase',
    suffix: '%',
    animated: true,
    source: 'manual'
  },
  {
    id: 'donation-growth',
    value: 120,
    label: 'Donation Growth',
    suffix: '%',
    animated: true,
    source: 'manual'
  },
  {
    id: 'placement-rate',
    value: 94,
    label: 'Placement Rate',
    suffix: '%',
    animated: true,
    source: 'manual'
  }
]

const employerStatistics: StatisticCounter[] = [
  {
    id: 'qualified-candidates',
    value: 50000,
    label: 'Qualified Candidates',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: '/api/statistics/qualified-candidates'
  },
  {
    id: 'successful-hires',
    value: 12000,
    label: 'Successful Hires',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: '/api/statistics/successful-hires'
  },
  {
    id: 'time-to-hire',
    value: 40,
    label: 'Faster Hiring',
    suffix: '%',
    animated: true,
    source: 'manual'
  },
  {
    id: 'retention-rate',
    value: 92,
    label: 'Retention Rate',
    suffix: '%',
    animated: true,
    source: 'manual'
  }
]

const errorStatistics: StatisticCounter[] = [
  {
    id: 'invalid-endpoint',
    value: 1000,
    label: 'Invalid Endpoint Test',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: '/api/statistics/invalid-endpoint'
  },
  {
    id: 'network-error-test',
    value: 2000,
    label: 'Network Error Test',
    suffix: '+',
    animated: true,
    source: 'api',
    apiEndpoint: 'https://invalid-domain-that-does-not-exist.com/api/stats'
  },
  {
    id: 'working-manual',
    value: 500,
    label: 'Working Manual Stat',
    suffix: '+',
    animated: true,
    source: 'manual'
  }
]

// Event handlers
const handleAnimationStart = (statisticId: string) => {
  addEvent('Animation Start', `Started animating ${statisticId}`)
}

const handleAnimationComplete = (statisticId: string) => {
  addEvent('Animation Complete', `Finished animating ${statisticId}`)
}

const handleDataLoaded = (statisticId: string, value: number) => {
  addEvent('Data Loaded', `Loaded ${statisticId} with value: ${value}`)
}

const handleDataError = (statisticId: string, error: Error) => {
  addEvent('Data Error', `Failed to load ${statisticId}: ${error.message}`)
}

const handleAllAnimationsComplete = () => {
  addEvent('All Animations Complete', 'All statistics have finished animating')
}

// Utility functions
const addEvent = (type: string, message: string) => {
  eventLog.value.unshift({
    type,
    message,
    timestamp: new Date()
  })
  
  // Keep only last 50 events
  if (eventLog.value.length > 50) {
    eventLog.value = eventLog.value.slice(0, 50)
  }
}

const clearEventLog = () => {
  eventLog.value = []
}

const formatTime = (date: Date): string => {
  return date.toLocaleTimeString()
}
</script>

<style scoped>
/* Custom scrollbar for event log */
.max-h-64::-webkit-scrollbar {
  width: 6px;
}

.max-h-64::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-700 rounded;
}

.max-h-64::-webkit-scrollbar-thumb {
  @apply bg-gray-400 dark:bg-gray-500 rounded;
}

.max-h-64::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-500 dark:bg-gray-400;
}
</style>