<template>
  <div class="trust-badges">
    <div class="container mx-auto px-4">
      <div class="text-center mb-8">
        <h3 class="text-xl font-semibold text-gray-900 mb-2">
          {{ title }}
        </h3>
        <p v-if="subtitle" class="text-gray-600">
          {{ subtitle }}
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center space-x-8">
        <div 
          v-for="i in 4" 
          :key="i"
          class="animate-pulse"
        >
          <div class="w-24 h-16 bg-gray-200 rounded"></div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8">
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button 
          @click="fetchTrustBadges"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
        >
          Try Again
        </button>
      </div>

      <!-- Trust Badges Grid -->
      <div v-else-if="trustBadges.length > 0" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6 items-center justify-items-center">
        <div
          v-for="badge in trustBadges"
          :key="badge.id"
          class="trust-badge-item group relative"
          @mouseenter="showTooltip(badge.id)"
          @mouseleave="hideTooltip"
        >
          <!-- Badge Image -->
          <div class="relative">
            <img
              :src="badge.image"
              :alt="badge.name"
              class="h-12 w-auto object-contain grayscale group-hover:grayscale-0 transition-all duration-300 cursor-pointer"
              @error="handleImageError"
              @click="handleBadgeClick(badge)"
            />
            
            <!-- Verification Icon -->
            <div v-if="badge.verificationUrl" class="absolute -top-1 -right-1">
              <div class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Tooltip -->
          <div
            v-if="activeTooltip === badge.id"
            class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-10"
            role="tooltip"
            :aria-describedby="`tooltip-${badge.id}`"
          >
            <div class="bg-gray-900 text-white text-sm rounded-lg py-2 px-3 max-w-xs text-center shadow-lg">
              <div class="font-medium mb-1">{{ badge.name }}</div>
              <div class="text-xs text-gray-300">{{ badge.description }}</div>
              
              <!-- Tooltip Arrow -->
              <div class="absolute top-full left-1/2 transform -translate-x-1/2">
                <div class="border-4 border-transparent border-t-gray-900"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Company Logos Section -->
      <div v-if="showCompanyLogos && companyLogos.length > 0" class="mt-16">
        <div class="text-center mb-8">
          <h3 class="text-xl font-semibold text-gray-900 mb-2">
            {{ companyLogosTitle }}
          </h3>
          <p class="text-gray-600">
            {{ companyLogosSubtitle }}
          </p>
        </div>

        <!-- Company Logos Carousel -->
        <div class="relative overflow-hidden">
          <div 
            class="flex transition-transform duration-500 ease-in-out"
            :style="{ transform: `translateX(-${currentLogoSlide * logoSlideWidth}%)` }"
          >
            <div
              v-for="logo in companyLogos"
              :key="logo.id"
              class="flex-shrink-0 px-4"
              :style="{ width: `${100 / logosPerSlide}%` }"
            >
              <div class="flex items-center justify-center h-16">
                <img
                  :src="logo.logo"
                  :alt="`${logo.name} logo`"
                  class="h-8 w-auto object-contain grayscale hover:grayscale-0 transition-all duration-300 cursor-pointer"
                  @error="handleImageError"
                  @click="handleLogoClick(logo)"
                />
              </div>
            </div>
          </div>

          <!-- Auto-scroll indicator -->
          <div v-if="autoScrollLogos" class="flex justify-center mt-4 space-x-1">
            <div
              v-for="slide in totalLogoSlides"
              :key="slide"
              :class="[
                'w-2 h-2 rounded-full transition-colors',
                currentLogoSlide === slide - 1 ? 'bg-blue-600' : 'bg-gray-300'
              ]"
            />
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && trustBadges.length === 0" class="text-center py-8">
        <div class="text-gray-400 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
          </svg>
        </div>
        <p class="text-gray-600">No trust badges available at this time.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { TrustBadge, CompanyLogo, AudienceType } from '@/types/homepage'

interface Props {
  audience: AudienceType
  title?: string
  subtitle?: string
  showCompanyLogos?: boolean
  companyLogosTitle?: string
  companyLogosSubtitle?: string
  autoScrollLogos?: boolean
  autoScrollInterval?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Trusted & Secure',
  subtitle: 'Your data is protected by industry-leading security standards',
  showCompanyLogos: true,
  companyLogosTitle: 'Alumni Work At',
  companyLogosSubtitle: 'Join professionals from leading companies worldwide',
  autoScrollLogos: true,
  autoScrollInterval: 3000
})

// Reactive state
const trustBadges = ref<TrustBadge[]>([])
const companyLogos = ref<CompanyLogo[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const activeTooltip = ref<string | null>(null)
const currentLogoSlide = ref(0)
const logoScrollTimer = ref<number>()

// Computed properties
const logosPerSlide = computed(() => {
  // Responsive logos per slide
  return 6 // Desktop default, could be made responsive
})

const logoSlideWidth = computed(() => {
  return 100 / logosPerSlide.value
})

const totalLogoSlides = computed(() => {
  return Math.max(0, companyLogos.value.length - logosPerSlide.value + 1)
})

// Methods
const fetchTrustBadges = async (): Promise<void> => {
  loading.value = true
  error.value = null

  try {
    const response = await fetch(`/api/homepage/trust-badges?audience=${props.audience}`, {
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
      trustBadges.value = data.data.trust_badges || []
      companyLogos.value = data.data.company_logos || []
    } else {
      throw new Error(data.message || 'Failed to fetch trust badges')
    }
  } catch (err) {
    console.error('Error fetching trust badges:', err)
    error.value = err instanceof Error ? err.message : 'Failed to load trust badges'
    
    // Fallback to mock data for development
    if (process.env.NODE_ENV === 'development') {
      const mockData = getMockData()
      trustBadges.value = mockData.trustBadges
      companyLogos.value = mockData.companyLogos
    }
  } finally {
    loading.value = false
  }
}

const getMockData = (): { trustBadges: TrustBadge[], companyLogos: CompanyLogo[] } => {
  const trustBadges: TrustBadge[] = [
    {
      id: '1',
      name: 'SOC 2 Type II',
      image: '/images/badges/soc2-type2.png',
      description: 'SOC 2 Type II compliance ensures the highest standards of security, availability, and confidentiality.',
      verificationUrl: 'https://example.com/soc2-verification'
    },
    {
      id: '2',
      name: 'GDPR Compliant',
      image: '/images/badges/gdpr-compliant.png',
      description: 'Full compliance with the General Data Protection Regulation for EU data protection.',
      verificationUrl: 'https://example.com/gdpr-verification'
    },
    {
      id: '3',
      name: 'ISO 27001',
      image: '/images/badges/iso-27001.png',
      description: 'ISO 27001 certified information security management system.',
      verificationUrl: 'https://example.com/iso-verification'
    },
    {
      id: '4',
      name: 'Privacy Shield',
      image: '/images/badges/privacy-shield.png',
      description: 'EU-US Privacy Shield framework compliance for international data transfers.'
    },
    {
      id: '5',
      name: 'SSL Secured',
      image: '/images/badges/ssl-secured.png',
      description: '256-bit SSL encryption protects all data in transit.'
    },
    {
      id: '6',
      name: 'CCPA Compliant',
      image: '/images/badges/ccpa-compliant.png',
      description: 'California Consumer Privacy Act compliance for enhanced privacy rights.'
    }
  ]

  const companyLogos: CompanyLogo[] = [
    {
      id: '1',
      name: 'Google',
      logo: '/images/companies/google-logo.png',
      website: 'https://google.com',
      category: 'Technology'
    },
    {
      id: '2',
      name: 'Microsoft',
      logo: '/images/companies/microsoft-logo.png',
      website: 'https://microsoft.com',
      category: 'Technology'
    },
    {
      id: '3',
      name: 'Apple',
      logo: '/images/companies/apple-logo.png',
      website: 'https://apple.com',
      category: 'Technology'
    },
    {
      id: '4',
      name: 'Amazon',
      logo: '/images/companies/amazon-logo.png',
      website: 'https://amazon.com',
      category: 'Technology'
    },
    {
      id: '5',
      name: 'Meta',
      logo: '/images/companies/meta-logo.png',
      website: 'https://meta.com',
      category: 'Technology'
    },
    {
      id: '6',
      name: 'Netflix',
      logo: '/images/companies/netflix-logo.png',
      website: 'https://netflix.com',
      category: 'Entertainment'
    },
    {
      id: '7',
      name: 'Tesla',
      logo: '/images/companies/tesla-logo.png',
      website: 'https://tesla.com',
      category: 'Automotive'
    },
    {
      id: '8',
      name: 'Goldman Sachs',
      logo: '/images/companies/goldman-sachs-logo.png',
      website: 'https://goldmansachs.com',
      category: 'Finance'
    }
  ]

  return { trustBadges, companyLogos }
}

const handleImageError = (event: Event): void => {
  const img = event.target as HTMLImageElement
  img.src = '/images/placeholder-badge.png' // Fallback image
}

const showTooltip = (badgeId: string): void => {
  activeTooltip.value = badgeId
}

const hideTooltip = (): void => {
  activeTooltip.value = null
}

const handleBadgeClick = (badge: TrustBadge): void => {
  if (badge.verificationUrl) {
    window.open(badge.verificationUrl, '_blank', 'noopener,noreferrer')
  }
}

const handleLogoClick = (logo: CompanyLogo): void => {
  if (logo.website) {
    window.open(logo.website, '_blank', 'noopener,noreferrer')
  }
}

const startLogoAutoScroll = (): void => {
  if (props.autoScrollLogos && props.autoScrollInterval > 0 && totalLogoSlides.value > 1) {
    logoScrollTimer.value = window.setInterval(() => {
      currentLogoSlide.value = (currentLogoSlide.value + 1) % totalLogoSlides.value
    }, props.autoScrollInterval)
  }
}

const stopLogoAutoScroll = (): void => {
  if (logoScrollTimer.value) {
    clearInterval(logoScrollTimer.value)
    logoScrollTimer.value = undefined
  }
}

// Lifecycle hooks
onMounted(() => {
  fetchTrustBadges()
})

onUnmounted(() => {
  stopLogoAutoScroll()
})

// Start auto-scroll after data is loaded
const startAutoScrollWhenReady = (): void => {
  if (companyLogos.value.length > 0) {
    startLogoAutoScroll()
  }
}

// Watch for data changes to start auto-scroll
const unwatchLogos = ref<(() => void) | null>(null)
if (props.autoScrollLogos) {
  import('vue').then(({ watch }) => {
    unwatchLogos.value = watch(
      () => companyLogos.value.length,
      (newLength) => {
        if (newLength > 0) {
          startAutoScrollWhenReady()
          if (unwatchLogos.value) {
            unwatchLogos.value()
            unwatchLogos.value = null
          }
        }
      },
      { immediate: true }
    )
  })
}

// Expose methods for parent components
defineExpose({
  fetchTrustBadges,
  refresh: fetchTrustBadges
})
</script>

<style scoped>
.trust-badges {
  padding-top: 3rem;
  padding-bottom: 3rem;
  background-color: #f9fafb;
}

.trust-badge-item {
  @apply transition-all duration-300;
}

.trust-badge-item:hover {
  transform: translateY(-2px);
}

/* Smooth transitions */
.trust-badges * {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, transform, filter;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
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

/* Grayscale hover effects */
.grayscale {
  filter: grayscale(100%);
}

.grayscale:hover,
.group:hover .grayscale {
  filter: grayscale(0%);
}

/* Tooltip animations */
.trust-badge-item [role="tooltip"] {
  animation: fadeInUp 0.2s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translate(-50%, 10px);
  }
  to {
    opacity: 1;
    transform: translate(-50%, 0);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .trust-badges {
    padding-top: 2rem;
    padding-bottom: 2rem;
  }
  
  .trust-badges .grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .trust-badges .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .trust-badges *,
  .trust-badge-item,
  .trust-badge-item:hover {
    transition: none;
    animation: none;
    transform: none;
  }
}

/* Focus styles for keyboard navigation */
.trust-badges button:focus,
.trust-badges img:focus {
  outline: none;
  box-shadow: 0 0 0 2px #3b82f6, 0 0 0 4px rgba(59, 130, 246, 0.1);
  border-radius: 0.25rem;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .trust-badges [class*="bg-gray-50"] {
    background-color: #ffffff !important;
  }
  
  .trust-badges [class*="text-gray-600"] {
    color: #000000 !important;
  }
  
  .trust-badges [class*="bg-gray-900"] {
    background-color: #000000 !important;
  }
}

/* Print styles */
@media print {
  .trust-badges {
    break-inside: avoid;
  }
  
  .trust-badges .grayscale {
    filter: none;
  }
}
</style>