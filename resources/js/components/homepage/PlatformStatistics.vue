<template>
  <div class="platform-statistics">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          {{ title }}
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          {{ subtitle }}
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
        <div 
          v-for="i in 4" 
          :key="i"
          class="text-center animate-pulse"
        >
          <div class="h-16 bg-gray-200 rounded mb-2"></div>
          <div class="h-4 bg-gray-200 rounded w-3/4 mx-auto"></div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button 
          @click="fetchStatistics"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
        >
          Try Again
        </button>
      </div>

      <!-- Statistics Grid -->
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
        <div 
          v-for="stat in statistics" 
          :key="stat.key"
          class="text-center group"
          :class="{ 'animate-fade-in': isVisible }"
        >
          <div class="mb-3">
            <div 
              v-if="stat.icon"
              class="w-12 h-12 mx-auto mb-4 text-blue-600 group-hover:text-blue-700 transition-colors"
              v-html="getIconSvg(stat.icon)"
            ></div>
            
            <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
              <AnimatedCounter
                :target-value="stat.value"
                :format="stat.format"
                :suffix="stat.suffix"
                :animate="isVisible && stat.animateOnScroll"
                :duration="2000"
                :aria-label="`${stat.label}: ${stat.value}${stat.suffix || ''}`"
              />
            </div>
            
            <p class="text-sm md:text-base text-gray-600 font-medium">
              {{ stat.label }}
            </p>
          </div>
        </div>
      </div>

      <!-- Last Updated -->
      <div v-if="lastUpdated && !loading && !error" class="text-center mt-8">
        <p class="text-sm text-gray-500">
          Last updated: {{ formatDate(lastUpdated) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'
import type { PlatformStatistic, AudienceType } from '@/types/homepage'

interface Props {
  audience: AudienceType
  title?: string
  subtitle?: string
  autoFetch?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Trusted by Alumni Worldwide',
  subtitle: 'Join thousands of professionals advancing their careers through meaningful connections',
  autoFetch: true
})

// Reactive state
const statistics = ref<PlatformStatistic[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)
const statisticsContainer = ref<HTMLElement>()
const isVisible = ref(false)

// Computed properties
const audienceSpecificTitle = computed(() => {
  if (props.audience === 'institutional') {
    return 'Trusted by Leading Institutions'
  }
  return props.title
})

const audienceSpecificSubtitle = computed(() => {
  if (props.audience === 'institutional') {
    return 'See how universities and organizations are transforming alumni engagement'
  }
  return props.subtitle
})

// Use intersection observer to trigger animations when visible
const { stop } = useIntersectionObserver(
  statisticsContainer,
  ([{ isIntersecting }]) => {
    if (isIntersecting && !isVisible.value) {
      isVisible.value = true
      stop() // Stop observing once visible
    }
  },
  {
    threshold: 0.3,
    rootMargin: '50px'
  }
)

// API functions
const fetchStatistics = async (): Promise<void> => {
  loading.value = true
  error.value = null

  try {
    const response = await fetch(`/api/homepage/statistics?audience=${props.audience}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    
    if (data.success) {
      statistics.value = data.data.statistics
      lastUpdated.value = data.data.last_updated ? new Date(data.data.last_updated) : null
    } else {
      throw new Error(data.message || 'Failed to fetch statistics')
    }
  } catch (err) {
    console.error('Error fetching platform statistics:', err)
    error.value = err instanceof Error ? err.message : 'Failed to load statistics'
    
    // Fallback to mock data for development
    if (process.env.NODE_ENV === 'development') {
      statistics.value = getMockStatistics()
      lastUpdated.value = new Date()
    }
  } finally {
    loading.value = false
  }
}

// Mock data for development/fallback
const getMockStatistics = (): PlatformStatistic[] => {
  const baseStats: PlatformStatistic[] = [
    {
      key: 'total_alumni',
      value: 25000,
      label: 'Alumni Connected',
      icon: 'users',
      animateOnScroll: true,
      format: 'number',
      suffix: '+'
    },
    {
      key: 'successful_connections',
      value: 45000,
      label: 'Successful Connections',
      icon: 'network',
      animateOnScroll: true,
      format: 'number',
      suffix: '+'
    },
    {
      key: 'job_placements',
      value: 3200,
      label: 'Job Placements',
      icon: 'briefcase',
      animateOnScroll: true,
      format: 'number',
      suffix: '+'
    },
    {
      key: 'average_salary_increase',
      value: 42,
      label: 'Average Salary Increase',
      icon: 'trending-up',
      animateOnScroll: true,
      format: 'percentage'
    }
  ]

  if (props.audience === 'institutional') {
    return [
      {
        key: 'institutions_served',
        value: 150,
        label: 'Institutions Served',
        icon: 'building',
        animateOnScroll: true,
        format: 'number',
        suffix: '+'
      },
      {
        key: 'branded_apps_deployed',
        value: 45,
        label: 'Branded Apps Deployed',
        icon: 'mobile',
        animateOnScroll: true,
        format: 'number'
      },
      {
        key: 'average_engagement_increase',
        value: 300,
        label: 'Average Engagement Increase',
        icon: 'trending-up',
        animateOnScroll: true,
        format: 'percentage'
      },
      {
        key: 'admin_satisfaction_rate',
        value: 96,
        label: 'Admin Satisfaction Rate',
        icon: 'star',
        animateOnScroll: true,
        format: 'percentage'
      }
    ]
  }

  return baseStats
}

// Utility functions
const getIconSvg = (iconName: string): string => {
  const icons: Record<string, string> = {
    users: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>`,
    network: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M15 9H9v6h6V9zm-2 4h-2v-2h2v2zm8-2V9h-2V7c0-1.1-.9-2-2-2h-2V3h-2v2h-2V3H9v2H7c-1.1 0-2 .9-2 2v2H3v2h2v2H3v2h2v2c0 1.1.9 2 2 2h2v2h2v-2h2v2h2v-2h2c1.1 0 2-.9 2-2v-2h2v-2h-2v-2h2z"/></svg>`,
    briefcase: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M10 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1zM6 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1H7a1 1 0 01-1-1zM14 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1z"/></svg>`,
    'trending-up': `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6h-6z"/></svg>`,
    building: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>`,
    mobile: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM7 4h10v12H7V4zm5 15c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
    star: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`
  }
  
  return icons[iconName] || icons.users
}

const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

// Lifecycle hooks
onMounted(() => {
  if (props.autoFetch) {
    fetchStatistics()
  }
})

onUnmounted(() => {
  stop()
})

// Expose methods for parent components
defineExpose({
  fetchStatistics,
  refresh: fetchStatistics
})
</script>

<style scoped>
.platform-statistics {
  @apply py-16 bg-white;
}

.animate-fade-in {
  animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.group:hover .text-blue-600 {
  @apply text-blue-700;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .platform-statistics {
    @apply py-12;
  }
}

/* Loading animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .animate-fade-in,
  .animate-pulse {
    animation: none;
  }
  
  .group:hover .text-blue-600 {
    transition: none;
  }
}
</style>