<template>
  <div class="conversion-ctas">
    <!-- Strategic CTA Placement throughout page -->
    <div v-for="cta in strategicCTAs" :key="cta.id" :class="getCTAClasses(cta)">
      <component
        :is="getCTAComponent(cta.type)"
        :cta="cta"
        :audience="audience"
        @click="handleCTAClick"
      />
    </div>

    <!-- Exit Intent Popup -->
    <ExitIntentPopup
      v-if="showExitIntent"
      :audience="audience"
      :special-offer="exitIntentOffer"
      @close="handleExitIntentClose"
      @convert="handleExitIntentConvert"
    />

    <!-- Progressive CTAs based on engagement -->
    <ProgressiveCTAs
      :engagement-level="engagementLevel"
      :audience="audience"
      :scroll-depth="scrollDepth"
      @cta-click="handleCTAClick"
    />

    <!-- Mobile-optimized floating CTA -->
    <FloatingMobileCTA
      v-if="isMobile && showFloatingCTA"
      :audience="audience"
      :primary-cta="primaryMobileCTA"
      @click="handleCTAClick"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import { useAudienceDetection } from '@/composables/useAudienceDetection'
import { useScrollTracking } from '@/composables/useScrollTracking'
import { useExitIntent } from '@/composables/useExitIntent'
import ExitIntentPopup from './ExitIntentPopup.vue'
import ProgressiveCTAs from './ProgressiveCTAs.vue'
import FloatingMobileCTA from './FloatingMobileCTA.vue'
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
  strategicCTAs: StrategicCTA[]
  exitIntentOffer?: ExitIntentOffer
  primaryMobileCTA: CTAButton
}

const props = defineProps<Props>()

// Composables
const { trackEvent } = useAnalytics()
const { isMobile } = useAudienceDetection()
const { scrollDepth, isScrolling } = useScrollTracking()
const { showExitIntent, resetExitIntent } = useExitIntent()

// Reactive state
const engagementLevel = ref<EngagementLevel>('low')
const showFloatingCTA = ref(false)
const ctaInteractions = ref<Record<string, number>>({})

// Computed properties
const strategicCTAsFiltered = computed(() => {
  return props.strategicCTAs.filter(cta => 
    cta.audience === props.audience || cta.audience === 'both'
  )
})

// Methods
const getCTAComponent = (type: string) => {
  const components = {
    'contextual': ContextualCTA,
    'section': SectionCTA,
    'sticky-header': StickyHeaderCTA,
    'floating': FloatingMobileCTA
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
  
  // Update engagement level based on interactions
  updateEngagementLevel()
  
  // Track analytics event
  trackEvent('cta_click', {
    action: event.action,
    section: event.section,
    audience: props.audience,
    engagementLevel: engagementLevel.value,
    scrollDepth: scrollDepth.value,
    interactionCount: ctaInteractions.value[event.action],
    ...event.additionalData
  })
  
  // Handle specific CTA actions
  handleCTAAction(event)
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

const updateEngagementLevel = () => {
  const totalInteractions = Object.values(ctaInteractions.value).reduce((sum, count) => sum + count, 0)
  const scrollProgress = scrollDepth.value
  
  if (totalInteractions >= 3 || scrollProgress > 75) {
    engagementLevel.value = 'high'
  } else if (totalInteractions >= 1 || scrollProgress > 50) {
    engagementLevel.value = 'medium'
  } else {
    engagementLevel.value = 'low'
  }
}

const handleExitIntentClose = () => {
  trackEvent('exit_intent_popup_close', {
    audience: props.audience,
    engagementLevel: engagementLevel.value,
    scrollDepth: scrollDepth.value
  })
  resetExitIntent()
}

const handleExitIntentConvert = (action: string) => {
  trackEvent('exit_intent_conversion', {
    action,
    audience: props.audience,
    engagementLevel: engagementLevel.value,
    scrollDepth: scrollDepth.value
  })
  resetExitIntent()
  handleCTAClick({
    action,
    section: 'exit-intent',
    audience: props.audience
  })
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

// Lifecycle
onMounted(() => {
  // Show floating CTA after user scrolls past hero
  const handleScroll = () => {
    showFloatingCTA.value = scrollDepth.value > 25
  }
  
  window.addEventListener('scroll', handleScroll)
  
  // Track initial page view
  trackEvent('conversion_ctas_loaded', {
    audience: props.audience,
    ctaCount: strategicCTAsFiltered.value.length
  })
})

onUnmounted(() => {
  window.removeEventListener('scroll', () => {})
})
</script>

<style scoped>
.conversion-ctas {
  position: relative;
}

.strategic-cta {
  transition: all 0.3s ease;
}

.cta-contextual {
  position: relative;
  z-index: 10;
}

.cta-section {
  margin: 2rem 0;
}

.cta-sticky-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.cta-floating {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}

.cta-mobile-optimized {
  @apply md:hidden;
}

.cta-mobile-optimized .cta-button {
  min-height: 44px;
  min-width: 44px;
  font-size: 16px;
  padding: 12px 24px;
}

.cta-high-engagement {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

@media (max-width: 768px) {
  .cta-floating {
    bottom: 10px;
    right: 10px;
    left: 10px;
    right: 10px;
  }
}
</style>