<template>
  <div class="hotspot-detail">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ hotspot.title }}
      </h3>
      <button
        @click="$emit('close')"
        class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
        aria-label="Close hotspot detail"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Description -->
    <p class="text-gray-600 mb-6 leading-relaxed">
      {{ hotspot.description }}
    </p>

    <!-- Feature Details -->
    <div v-if="featureDetails" class="mb-6">
      <h4 class="font-medium text-gray-900 mb-3">Feature Details</h4>
      <div class="bg-blue-50 rounded-lg p-4">
        <div class="flex items-start space-x-3">
          <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <div>
            <h5 class="font-medium text-blue-900 mb-1">{{ featureDetails.name }}</h5>
            <p class="text-sm text-blue-700">{{ featureDetails.description }}</p>
            
            <!-- Feature Benefits -->
            <div v-if="featureDetails.benefits && featureDetails.benefits.length > 0" class="mt-3">
              <ul class="space-y-1">
                <li
                  v-for="benefit in featureDetails.benefits"
                  :key="benefit"
                  class="flex items-start space-x-2"
                >
                  <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-sm text-blue-700">{{ benefit }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Usage Stats -->
    <div v-if="usageStats" class="mb-6">
      <h4 class="font-medium text-gray-900 mb-3">Usage Statistics</h4>
      <div class="grid grid-cols-2 gap-4">
        <div
          v-for="stat in usageStats"
          :key="stat.label"
          class="text-center p-3 bg-gray-50 rounded-lg"
        >
          <div class="text-2xl font-bold text-gray-900">{{ stat.value }}</div>
          <div class="text-sm text-gray-600">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- Interactive Demo -->
    <div v-if="hotspot.action" class="mb-6">
      <h4 class="font-medium text-gray-900 mb-3">Try It Yourself</h4>
      <div class="bg-green-50 rounded-lg p-4">
        <div class="flex items-start space-x-3">
          <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <div class="flex-grow">
            <p class="text-sm text-green-700 mb-3">
              {{ getActionDescription() }}
            </p>
            <button
              @click="handleAction"
              class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
            >
              {{ getActionButtonText() }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Related Features -->
    <div v-if="relatedFeatures && relatedFeatures.length > 0" class="mb-6">
      <h4 class="font-medium text-gray-900 mb-3">Related Features</h4>
      <div class="space-y-2">
        <button
          v-for="feature in relatedFeatures"
          :key="feature.id"
          @click="$emit('navigate-to-feature', feature.id)"
          class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <div class="flex items-center space-x-3">
            <div class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center">
              <svg class="w-3 h-3 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div>
              <div class="font-medium text-gray-900 text-sm">{{ feature.name }}</div>
              <div class="text-xs text-gray-600">{{ feature.description }}</div>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex space-x-3">
      <button
        @click="$emit('close')"
        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
      >
        Close
      </button>
      <button
        v-if="hotspot.feature"
        @click="learnMore"
        class="flex-1 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
      >
        Learn More
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Hotspot {
  x: number
  y: number
  title: string
  description: string
  feature?: string
  action?: string
}

interface FeatureDetail {
  name: string
  description: string
  benefits?: string[]
}

interface UsageStat {
  label: string
  value: string
}

interface RelatedFeature {
  id: string
  name: string
  description: string
}

interface Props {
  hotspot: Hotspot
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  close: []
  'navigate-to-feature': [featureId: string]
}>()

// Computed properties
const featureDetails = computed((): FeatureDetail | null => {
  if (!props.hotspot.feature) return null

  const featureMap: Record<string, FeatureDetail> = {
    networking: {
      name: 'Smart Alumni Networking',
      description: 'AI-powered connection recommendations based on your profile, interests, and career goals.',
      benefits: [
        'Personalized connection suggestions',
        'Industry-specific networking groups',
        'Professional conversation starters',
        'Mutual connection discovery'
      ]
    },
    jobs: {
      name: 'Exclusive Job Board',
      description: 'Access job opportunities shared exclusively within your alumni network.',
      benefits: [
        'Alumni-exclusive job postings',
        'Referral tracking system',
        'Application status updates',
        'Salary insights and benchmarks'
      ]
    },
    events: {
      name: 'Alumni Events',
      description: 'Discover and attend networking events, webinars, and reunions.',
      benefits: [
        'Local and virtual events',
        'RSVP and calendar integration',
        'Event networking features',
        'Post-event follow-up tools'
      ]
    },
    mentorship: {
      name: 'Career Mentorship',
      description: 'Get paired with experienced alumni mentors in your field.',
      benefits: [
        'Intelligent mentor matching',
        'Structured mentorship programs',
        'Goal tracking and progress monitoring',
        'Video call integration'
      ]
    },
    search: {
      name: 'Advanced Alumni Search',
      description: 'Find alumni using powerful search filters and criteria.',
      benefits: [
        'Multi-criteria search filters',
        'Saved search preferences',
        'Search result recommendations',
        'Privacy-controlled visibility'
      ]
    },
    profiles: {
      name: 'Professional Profiles',
      description: 'Comprehensive alumni profiles with career history and achievements.',
      benefits: [
        'Detailed career timelines',
        'Skills and expertise showcase',
        'Professional accomplishments',
        'Contact preferences'
      ]
    },
    navigation: {
      name: 'Mobile Navigation',
      description: 'Intuitive mobile interface optimized for on-the-go networking.',
      benefits: [
        'Touch-optimized interface',
        'Offline functionality',
        'Push notifications',
        'Quick action shortcuts'
      ]
    }
  }

  return featureMap[props.hotspot.feature] || null
})

const usageStats = computed((): UsageStat[] | null => {
  if (!props.hotspot.feature) return null

  const statsMap: Record<string, UsageStat[]> = {
    networking: [
      { label: 'Monthly Connections', value: '12K+' },
      { label: 'Success Rate', value: '94%' }
    ],
    jobs: [
      { label: 'Job Placements', value: '3.2K+' },
      { label: 'Average Salary Increase', value: '42%' }
    ],
    events: [
      { label: 'Events Hosted', value: '850+' },
      { label: 'Attendance Rate', value: '78%' }
    ],
    mentorship: [
      { label: 'Active Mentorships', value: '1.8K+' },
      { label: 'Success Rate', value: '89%' }
    ]
  }

  return statsMap[props.hotspot.feature] || null
})

const relatedFeatures = computed((): RelatedFeature[] => {
  if (!props.hotspot.feature) return []

  const relatedMap: Record<string, RelatedFeature[]> = {
    networking: [
      {
        id: 'mentorship',
        name: 'Find Mentors',
        description: 'Connect with experienced alumni for career guidance'
      },
      {
        id: 'events',
        name: 'Networking Events',
        description: 'Attend alumni events and meetups'
      }
    ],
    jobs: [
      {
        id: 'networking',
        name: 'Alumni Network',
        description: 'Build connections that lead to opportunities'
      },
      {
        id: 'mentorship',
        name: 'Career Mentorship',
        description: 'Get guidance on your job search'
      }
    ],
    events: [
      {
        id: 'networking',
        name: 'Alumni Network',
        description: 'Connect with attendees before and after events'
      }
    ]
  }

  return relatedMap[props.hotspot.feature] || []
})

// Methods
const getActionDescription = (): string => {
  const actionMap: Record<string, string> = {
    view_connections: 'Click to explore connection recommendations and see how the networking feature works.',
    view_jobs: 'Browse exclusive job opportunities and see how the job board helps alumni find positions.',
    view_events: 'Discover upcoming alumni events and networking opportunities in your area.',
    search_alumni: 'Try the advanced search functionality to find specific alumni.',
    view_profile: 'Explore a sample alumni profile to see the information available.',
    navigate_mobile: 'Experience the mobile interface and navigation.'
  }

  return actionMap[props.hotspot.action || ''] || 'Interact with this feature to see how it works.'
}

const getActionButtonText = (): string => {
  const buttonMap: Record<string, string> = {
    view_connections: 'View Connections',
    view_jobs: 'Browse Jobs',
    view_events: 'See Events',
    search_alumni: 'Try Search',
    view_profile: 'View Profile',
    navigate_mobile: 'Try Mobile'
  }

  return buttonMap[props.hotspot.action || ''] || 'Try Feature'
}

const handleAction = (): void => {
  // Track interaction
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'hotspot_action', {
      feature: props.hotspot.feature,
      action: props.hotspot.action,
      hotspot_title: props.hotspot.title
    })
  }

  // Simulate feature interaction
  console.log(`Simulating action: ${props.hotspot.action} for feature: ${props.hotspot.feature}`)
  
  // In a real implementation, this would trigger the actual feature demo
  // For now, we'll just show a success message
  alert(`Demo: ${getActionDescription()}`)
}

const learnMore = (): void => {
  // Track learn more click
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'hotspot_learn_more', {
      feature: props.hotspot.feature,
      hotspot_title: props.hotspot.title
    })
  }

  // Navigate to feature detail page
  if (props.hotspot.feature) {
    window.open(`/features/${props.hotspot.feature}`, '_blank')
  }
}
</script>

<style scoped>
.hotspot-detail {
  @apply max-w-md;
}

/* Smooth transitions */
.hotspot-detail * {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Button hover effects */
.hotspot-detail button:hover {
  transform: translateY(-1px);
}

.hotspot-detail button:active {
  transform: translateY(0);
}

/* Focus styles for accessibility */
.hotspot-detail button:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .hotspot-detail {
    @apply max-w-full;
  }
  
  .hotspot-detail .grid-cols-2 {
    @apply grid-cols-1;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .hotspot-detail *,
  .hotspot-detail button:hover,
  .hotspot-detail button:active {
    transition: none;
    transform: none;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .hotspot-detail .bg-blue-50 {
    background-color: #ffffff;
    border: 2px solid #2563eb;
  }
  
  .hotspot-detail .bg-green-50 {
    background-color: #ffffff;
    border: 2px solid #059669;
  }
  
  .hotspot-detail .bg-gray-50 {
    background-color: #ffffff;
    border: 1px solid #6b7280;
  }
  
  .hotspot-detail .text-gray-600 {
    color: #000000;
  }
}
</style>