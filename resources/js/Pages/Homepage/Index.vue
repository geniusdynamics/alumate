<template>
  <HomepageLayout 
    :title="pageTitle"
    :description="pageDescription"
    :keywords="pageKeywords"
  >
    <div class="homepage-container">
      <!-- Audience Selector -->
      <AudienceSelector 
        v-model:audience="currentAudience"
        @audience-changed="handleAudienceChange"
      />

    <!-- Hero Section -->
    <HeroSection 
      :audience="currentAudience"
      :hero-data="heroData"
      @cta-click="handleCTAClick"
    />

    <!-- Social Proof Section -->
    <SocialProofSection 
      :audience="currentAudience"
      :statistics="platformStatistics"
      :testimonials="testimonials"
    />

    <!-- Features Showcase -->
    <FeaturesShowcase 
      :audience="currentAudience"
      :features="platformFeatures"
    />

    <!-- Success Stories -->
    <SuccessStoriesSection 
      :audience="currentAudience"
      :stories="successStories"
    />

    <!-- Value Calculator (Individual) / Admin Dashboard Preview (Institutional) -->
    <ValueCalculator 
      v-if="currentAudience === 'individual'"
      @calculation-complete="handleCalculationComplete"
    />
    
    <AdminDashboardPreview 
      v-if="currentAudience === 'institutional'"
      @demo-request="handleDemoRequest"
    />

    <!-- Platform Preview -->
    <PlatformPreview 
      :audience="currentAudience"
    />

    <!-- Institutional Features (only for institutional audience) -->
    <InstitutionalFeatures 
      v-if="currentAudience === 'institutional'"
      @demo-request="handleDemoRequest"
    />

    <!-- Pricing Section -->
    <PricingSection 
      :audience="currentAudience"
      @trial-signup="handleTrialSignup"
      @demo-request="handleDemoRequest"
    />

    <!-- Trust Indicators -->
    <TrustIndicators 
      :audience="currentAudience"
    />

    <!-- Multiple Conversion CTAs -->
    <ConversionCTAs 
      :audience="currentAudience"
      @cta-click="handleCTAClick"
    />
    </div>
  </HomepageLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import { HomepageData, AudienceType, CTAClickEvent } from '@/types/homepage'

// Layout and Components
import HomepageLayout from '@/layouts/HomepageLayout.vue'
import AudienceSelector from '@/components/homepage/AudienceSelector.vue'
import HeroSection from '@/components/homepage/HeroSection.vue'
import SocialProofSection from '@/components/homepage/SocialProofSection.vue'
import FeaturesShowcase from '@/components/homepage/FeaturesShowcase.vue'
import SuccessStoriesSection from '@/components/homepage/SuccessStoriesSection.vue'
import ValueCalculator from '@/components/homepage/ValueCalculator.vue'
import AdminDashboardPreview from '@/components/homepage/AdminDashboardPreview.vue'
import PlatformPreview from '@/components/homepage/PlatformPreview.vue'
import InstitutionalFeatures from '@/components/homepage/InstitutionalFeatures.vue'
import PricingSection from '@/components/homepage/PricingSection.vue'
import TrustIndicators from '@/components/homepage/TrustIndicators.vue'
import ConversionCTAs from '@/components/homepage/ConversionCTAs.vue'

// Props
interface Props {
  audience: AudienceType
  content?: any
  abTests?: Record<string, any>
  userId?: string
  meta: {
    title: string
    description: string
    keywords: string
  }
}

const props = withDefaults(defineProps<Props>(), {
  audience: 'individual',
  content: () => ({}),
  abTests: () => ({}),
  userId: ''
})

// Reactive data
const currentAudience = ref<AudienceType>(props.audience)
const platformStatistics = ref(props.content?.statistics || {})
const testimonials = ref(props.content?.testimonials?.items || [])
const platformFeatures = ref(props.content?.features?.items || [])
const successStories = ref([])
const activeABTests = ref(props.abTests || {})

// Computed properties
const pageTitle = computed(() => {
  if (currentAudience.value === 'institutional') {
    return 'Transform Alumni Engagement - Institutional Solutions'
  }
  return 'Connect with Your Alumni Network - Professional Networking Platform'
})

const pageDescription = computed(() => {
  if (currentAudience.value === 'institutional') {
    return 'Increase alumni participation by 300% with custom branded mobile apps and comprehensive engagement platforms.'
  }
  return 'Join thousands of alumni advancing their careers through meaningful connections, mentorship, and professional opportunities.'
})

const pageKeywords = computed(() => {
  if (currentAudience.value === 'institutional') {
    return 'alumni engagement, institutional solutions, branded apps, university alumni'
  }
  return 'alumni network, professional networking, career advancement, mentorship'
})

const heroData = computed(() => {
  // Use content from props (which includes A/B test variants)
  const baseHero = props.content?.hero || {}
  const baseCTA = props.content?.cta || {}
  
  // Default fallbacks
  const defaults = {
    individual: {
      headline: 'Accelerate Your Career Through Alumni Connections',
      subtitle: 'Join thousands of alumni advancing their careers through meaningful networking and mentorship',
      primaryCTA: {
        text: 'Start Free Trial',
        action: 'trial',
        variant: 'primary',
        trackingEvent: 'hero_trial_click'
      },
      secondaryCTA: {
        text: 'Learn More',
        action: 'learn-more',
        variant: 'secondary',
        trackingEvent: 'hero_learn_more_click'
      },
      backgroundVideo: '/videos/individual-hero.mp4',
      backgroundImage: '/images/hero/individual-networking.jpg'
    },
    institutional: {
      headline: 'Transform Alumni Engagement with Your Branded Platform',
      subtitle: 'Increase alumni participation by 300% with custom mobile apps and comprehensive engagement tools',
      primaryCTA: {
        text: 'Request Demo',
        action: 'demo',
        variant: 'primary',
        trackingEvent: 'hero_demo_click'
      },
      secondaryCTA: {
        text: 'Download Case Studies',
        action: 'download',
        variant: 'secondary',
        trackingEvent: 'hero_case_study_click'
      },
      backgroundVideo: '/videos/institutional-hero.mp4',
      backgroundImage: '/images/hero/institutional-dashboard.jpg'
    }
  }
  
  const audienceDefaults = defaults[currentAudience.value]
  
  return {
    headline: baseHero.headline || audienceDefaults.headline,
    subtitle: baseHero.subtitle || audienceDefaults.subtitle,
    primaryCTA: baseCTA.primary || audienceDefaults.primaryCTA,
    secondaryCTA: baseCTA.secondary || audienceDefaults.secondaryCTA,
    backgroundVideo: baseHero.background_video || audienceDefaults.backgroundVideo,
    backgroundImage: baseHero.background_image || audienceDefaults.backgroundImage,
    testimonialRotation: props.content?.testimonials?.items?.slice(0, 3) || [],
    statisticsHighlight: [
      {
        key: 'alumni_count',
        value: platformStatistics.value.total_alumni || 25000,
        label: 'Alumni Connected',
        icon: 'users',
        animateOnScroll: true,
        format: 'number'
      },
      {
        key: 'salary_increase',
        value: platformStatistics.value.average_salary_increase || 42,
        label: 'Average Salary Increase',
        icon: 'trending-up',
        animateOnScroll: true,
        format: 'percentage'
      },
      {
        key: 'job_placements',
        value: platformStatistics.value.job_placements || 3200,
        label: 'Job Placements',
        icon: 'briefcase',
        animateOnScroll: true,
        format: 'number'
      }
    ]
  }
})

// Methods
const handleAudienceChange = (newAudience: AudienceType) => {
  currentAudience.value = newAudience
  loadAudienceSpecificData()
}

const handleCTAClick = async (event: CTAClickEvent) => {
  // Track CTA click with A/B test data
  await trackCTAClick(event)
  
  // Track A/B test conversions
  await trackABTestConversions('hero_cta_click', event)

  // Handle different CTA actions
  switch (event.action) {
    case 'trial':
      // Track trial conversion
      await trackABTestConversions('trial_signup', event)
      // Redirect to trial signup
      window.location.href = '/register?trial=true'
      break
    case 'demo':
      // Track demo conversion
      await trackABTestConversions('demo_request', event)
      // Open demo request modal or redirect
      handleDemoRequest()
      break
    case 'register':
      // Track registration conversion
      await trackABTestConversions('registration', event)
      // Redirect to registration
      window.location.href = '/register'
      break
    case 'learn-more':
      // Track learn more engagement
      await trackABTestConversions('learn_more_click', event)
      // Scroll to features section or show more info
      scrollToSection('features')
      break
    case 'download':
      // Track case study download
      await trackABTestConversions('case_study_download', event)
      // Trigger download
      window.open('/case-studies/download', '_blank')
      break
  }
}

const handleCalculationComplete = (result: any) => {
  // Handle value calculator completion
  trackEvent('calculator_completed', {
    audience: currentAudience.value,
    result: result
  })
}

const handleTrialSignup = (data: any) => {
  // Handle trial signup
  trackEvent('trial_signup', {
    audience: currentAudience.value,
    data: data
  })
}

const handleDemoRequest = (data?: any) => {
  // Handle demo request
  trackEvent('demo_request', {
    audience: currentAudience.value,
    data: data
  })
}

const loadAudienceSpecificData = async () => {
  try {
    // Load statistics
    const statsResponse = await fetch(`/api/homepage/statistics?audience=${currentAudience.value}`)
    platformStatistics.value = await statsResponse.json()

    // Load testimonials
    const testimonialsResponse = await fetch(`/api/homepage/testimonials?audience=${currentAudience.value}`)
    testimonials.value = await testimonialsResponse.json()

    // Load features
    const featuresResponse = await fetch(`/api/homepage/features?audience=${currentAudience.value}`)
    platformFeatures.value = await featuresResponse.json()

    // Load success stories
    const storiesResponse = await fetch(`/api/homepage/success-stories?audience=${currentAudience.value}`)
    successStories.value = await storiesResponse.json()
  } catch (error) {
    console.error('Error loading homepage data:', error)
  }
}

const trackEvent = (eventName: string, data: any) => {
  // Analytics tracking implementation
  if (typeof window !== 'undefined' && 'gtag' in window) {
    (window as any).gtag('event', eventName, data)
  }
  
  // Custom analytics service
  // analyticsService.track(eventName, data)
}

const trackCTAClick = async (event: CTAClickEvent) => {
  try {
    await fetch('/homepage/track-cta', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        action: event.action,
        section: event.section,
        audience: currentAudience.value,
        additional_data: event.additionalData,
        ab_tests: activeABTests.value
      })
    })
  } catch (error) {
    console.error('Error tracking CTA click:', error)
  }
}

const trackABTestConversions = async (goal: string, event: CTAClickEvent) => {
  try {
    // Track conversions for all active A/B tests
    for (const [testId, testData] of Object.entries(activeABTests.value)) {
      await fetch('/homepage/track-conversion', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          test_id: testId,
          variant_id: testData.variant_id,
          goal: goal,
          audience: currentAudience.value,
          additional_data: {
            action: event.action,
            section: event.section,
            ...event.additionalData
          }
        })
      })
    }
  } catch (error) {
    console.error('Error tracking A/B test conversions:', error)
  }
}

const scrollToSection = (sectionId: string) => {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}

// Lifecycle
onMounted(() => {
  loadAudienceSpecificData()
})

// Watch for audience changes
watch(currentAudience, () => {
  loadAudienceSpecificData()
})
</script>

<style scoped>
.homepage-container {
  @apply min-h-screen bg-white;
}

/* Mobile-first responsive design */
@media (max-width: 767px) {
  .homepage-container {
    @apply px-4;
  }
}

@media (min-width: 768px) {
  .homepage-container {
    @apply px-6;
  }
}

@media (min-width: 1024px) {
  .homepage-container {
    @apply px-8;
  }
}

@media (min-width: 1440px) {
  .homepage-container {
    @apply px-12;
  }
}
</style>