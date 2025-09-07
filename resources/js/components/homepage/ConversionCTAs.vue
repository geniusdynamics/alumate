<template>
  <div class="conversion-ctas">
    <!-- Simple Strategic CTA Placement -->
    <div v-for="cta in strategicCTAsFiltered" :key="cta.id" :class="getCTAClasses(cta)">
      <component
        :is="getCTAComponent(cta.type)"
        :cta="cta"
        :audience="audience"
        @click="handleCTAClick"
      />
    </div>

    <!-- Simple Mobile CTA (no floating popup) -->
    <div v-if="isMobile" class="simple-mobile-cta">
      <button 
        class="mobile-cta-button"
        @click="handleMobileCTA"
      >
        {{ primaryMobileCTA.text }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, withDefaults } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import { useAudienceDetection } from '@/composables/useAudienceDetection'
import ContextualCTA from './ContextualCTA.vue'
import SectionCTA from './SectionCTA.vue'
import StickyHeaderCTA from './StickyHeaderCTA.vue'
import type { 
  AudienceType, 
  CTAButton, 
  StrategicCTA, 
  ExitIntentOffer,
  EngagementLevel,
  CTAClickEvent 
} from '@/types/homepage'

interface Props {
  audience: AudienceType
  strategicCTAs?: StrategicCTA[]
  exitIntentOffer?: ExitIntentOffer
  primaryMobileCTA?: CTAButton
}

const props = withDefaults(defineProps<Props>(), {
  strategicCTAs: () => [],
  primaryMobileCTA: () => ({ text: 'Get Started', action: 'register', variant: 'primary' })
})

// Composables
const { trackEvent } = useAnalytics()
const { isMobile } = useAudienceDetection()

// Reactive state
const ctaInteractions = ref<Record<string, number>>({})
const engagementLevel = ref<EngagementLevel>('medium')

// Computed properties
const strategicCTAsFiltered = computed(() => {
  if (!props.strategicCTAs || !Array.isArray(props.strategicCTAs)) {
    return []
  }
  
  return props.strategicCTAs.filter(cta => {
    if (props.audience === 'institutional') {
      return cta.audiences.includes('institutional') || cta.audiences.includes('both')
    }
    return cta.audiences.includes('general') || cta.audiences.includes('both')
  })
})

const ctaClasses = computed(() => {
  return (cta: any) => {
    const baseClasses = 'inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md transition-all duration-200'
    const variantClasses = {
      primary: 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
      secondary: 'text-blue-700 bg-blue-100 hover:bg-blue-200 focus:ring-blue-500',
      outline: 'text-blue-700 border-blue-300 hover:bg-blue-50 focus:ring-blue-500'
    }
    
    return `${baseClasses} ${variantClasses[cta.variant] || variantClasses.primary}`
  }
})

// Methods
const getCTAComponent = (type: string) => {
  const components = {
    'contextual': ContextualCTA,
    'section': SectionCTA,
    'sticky-header': StickyHeaderCTA
  }
  return components[type] || ContextualCTA
}

const getCTAClasses = (cta: StrategicCTA) => {
  return [
    'strategic-cta',
    `cta-${cta.type}`,
    `cta-${cta.placement}`,
    {
      'cta-mobile-optimized': cta.mobileOptimized,
      'cta-high-engagement': engagementLevel.value === 'high',
      'cta-contextual': cta.contextual
    }
  ]
}

const handleCTAClick = (event: CTAClickEvent) => {
  // Track CTA interaction
  ctaInteractions.value[event.action] = (ctaInteractions.value[event.action] || 0) + 1
  
  // Track analytics event
  trackEvent('cta_click', {
    action: event.action,
    section: event.section,
    audience: props.audience,
    interactionCount: ctaInteractions.value[event.action],
    ...event.additionalData
  })
  
  // Handle specific CTA actions
  handleCTAAction(event)
}

const handleMobileCTA = () => {
  handleCTAClick({
    action: props.primaryMobileCTA.action,
    section: 'mobile-cta',
    audience: props.audience
  })
}

const handleCTAAction = (event: CTAClickEvent) => {
  switch (event.action) {
    case 'register':
      window.location.href = '/register'
      break
    case 'trial':
      window.location.href = '/trial'
      break
    case 'demo':
      window.location.href = '/demo'
      break
    case 'contact':
      window.location.href = '/contact'
      break
    case 'learn-more':
      // Scroll to next section or open modal
      scrollToNextSection(event.section)
      break
    default:
      if (event.additionalData?.href) {
        window.location.href = event.additionalData.href
      }
  }
}



const scrollToNextSection = (currentSection: string) => {
  // Implementation to scroll to next relevant section
  const sections = document.querySelectorAll('[data-section]')
  const currentIndex = Array.from(sections).findIndex(
    section => section.getAttribute('data-section') === currentSection
  )
  
  if (currentIndex >= 0 && currentIndex < sections.length - 1) {
    sections[currentIndex + 1].scrollIntoView({ behavior: 'smooth' })
  }
}

// Lifecycle hooks
onMounted(() => {
  // Track initial page view
  trackEvent('conversion_ctas_view', {
    audience: props.audience,
    strategicCTAsCount: props.strategicCTAs?.length || 0,
    hasMobileCTA: !!props.primaryMobileCTA
  })
})
</script>

<style scoped>
.strategic-ctas {
  @apply space-y-8;
}

.cta-section {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6;
}

.cta-grid {
  @apply grid gap-4;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.cta-button {
  @apply transition-all duration-200 transform hover:scale-105;
}

.mobile-cta {
  @apply fixed bottom-4 right-4 z-50 md:hidden;
}

@media (max-width: 768px) {
  .mobile-cta {
    @apply bottom-0 right-0 left-0 rounded-none p-4 bg-blue-600 text-white text-center;
  }
}
</style>