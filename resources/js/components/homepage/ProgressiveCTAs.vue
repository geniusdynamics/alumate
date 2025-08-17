<template>
  <div class="progressive-ctas">
    <!-- Low engagement CTAs -->
    <div v-if="engagementLevel === 'low'" class="engagement-low">
      <div class="soft-cta" v-for="cta in lowEngagementCTAs" :key="cta.id">
        <button 
          class="soft-cta-button"
          @click="handleCTAClick(cta)"
        >
          {{ cta.text }}
        </button>
      </div>
    </div>

    <!-- Medium engagement CTAs -->
    <div v-else-if="engagementLevel === 'medium'" class="engagement-medium">
      <div class="medium-cta" v-for="cta in mediumEngagementCTAs" :key="cta.id">
        <div class="cta-content">
          <h4 class="cta-title">{{ cta.title }}</h4>
          <p class="cta-description">{{ cta.description }}</p>
          <button 
            class="medium-cta-button"
            @click="handleCTAClick(cta)"
          >
            {{ cta.text }}
            <svg class="cta-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- High engagement CTAs -->
    <div v-else-if="engagementLevel === 'high'" class="engagement-high">
      <div class="high-engagement-banner">
        <div class="banner-content">
          <div class="banner-text">
            <h3 class="banner-title">{{ highEngagementContent.title }}</h3>
            <p class="banner-description">{{ highEngagementContent.description }}</p>
          </div>
          <div class="banner-actions">
            <button 
              class="primary-action-button"
              @click="handleCTAClick(highEngagementContent.primaryCTA)"
            >
              {{ highEngagementContent.primaryCTA.text }}
            </button>
            <button 
              v-if="highEngagementContent.secondaryCTA"
              class="secondary-action-button"
              @click="handleCTAClick(highEngagementContent.secondaryCTA)"
            >
              {{ highEngagementContent.secondaryCTA.text }}
            </button>
          </div>
        </div>
        
        <!-- Urgency indicators for high engagement -->
        <div class="urgency-indicators" v-if="showUrgencyIndicators">
          <div class="urgency-item" v-for="indicator in urgencyIndicators" :key="indicator.text">
            <component :is="indicator.icon" class="urgency-icon" />
            <span class="urgency-text">{{ indicator.text }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll-based CTAs -->
    <div class="scroll-based-ctas" v-if="scrollDepth > 50">
      <div 
        class="scroll-cta"
        v-for="cta in getScrollBasedCTAs()"
        :key="cta.id"
        :class="{ 'cta-visible': scrollDepth >= cta.triggerDepth }"
      >
        <div class="scroll-cta-content">
          <div class="scroll-cta-text">
            <span class="scroll-cta-label">{{ cta.label }}</span>
            <span class="scroll-cta-message">{{ cta.message }}</span>
          </div>
          <button 
            class="scroll-cta-button"
            @click="handleCTAClick(cta)"
          >
            {{ cta.text }}
          </button>
        </div>
      </div>
    </div>

    <!-- Contextual micro-CTAs -->
    <div class="micro-ctas" v-if="showMicroCTAs">
      <div 
        class="micro-cta"
        v-for="microCTA in activeMicroCTAs"
        :key="microCTA.id"
        :style="{ top: microCTA.position.top + 'px', left: microCTA.position.left + 'px' }"
      >
        <button 
          class="micro-cta-button"
          @click="handleCTAClick(microCTA)"
          :title="microCTA.tooltip"
        >
          <component :is="microCTA.icon" class="micro-cta-icon" />
          <span class="micro-cta-text">{{ microCTA.text }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import type { 
  AudienceType, 
  EngagementLevel, 
  ProgressiveCTA, 
  CTAClickEvent,
  MicroCTA,
  UrgencyIndicator 
} from '@/types/homepage'

interface Props {
  engagementLevel: EngagementLevel
  audience: AudienceType
  scrollDepth: number
}

interface Emits {
  (e: 'cta-click', event: CTAClickEvent): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()

// Reactive state
const showMicroCTAs = ref(false)
const showUrgencyIndicators = ref(false)
const activeMicroCTAs = ref<MicroCTA[]>([])

// Computed properties
const lowEngagementCTAs = computed(() => {
  const baseCTAs = [
    {
      id: 'learn-more',
      text: 'Learn More',
      action: 'learn-more',
      section: 'progressive-low'
    }
  ]
  
  if (props.audience === 'institutional') {
    return [
      ...baseCTAs,
      {
        id: 'view-features',
        text: 'View Features',
        action: 'view-features',
        section: 'progressive-low'
      }
    ]
  }
  
  return [
    ...baseCTAs,
    {
      id: 'see-success-stories',
      text: 'See Success Stories',
      action: 'see-success-stories',
      section: 'progressive-low'
    }
  ]
})

const mediumEngagementCTAs = computed(() => {
  if (props.audience === 'institutional') {
    return [
      {
        id: 'schedule-demo',
        title: 'Ready to See More?',
        description: 'Schedule a personalized demo to see how we can help your institution.',
        text: 'Schedule Demo',
        action: 'demo',
        section: 'progressive-medium'
      }
    ]
  }
  
  return [
    {
      id: 'start-trial',
      title: 'Ready to Get Started?',
      description: 'Join thousands of alumni advancing their careers.',
      text: 'Start Free Trial',
      action: 'trial',
      section: 'progressive-medium'
    }
  ]
})

const highEngagementContent = computed(() => {
  if (props.audience === 'institutional') {
    return {
      title: 'Transform Your Alumni Engagement Today',
      description: 'You\'ve seen the potential. Let\'s discuss how we can customize a solution for your institution.',
      primaryCTA: {
        id: 'contact-sales',
        text: 'Contact Sales',
        action: 'contact',
        section: 'progressive-high'
      },
      secondaryCTA: {
        id: 'download-resources',
        text: 'Download Resources',
        action: 'download',
        section: 'progressive-high'
      }
    }
  }
  
  return {
    title: 'Join Your Alumni Network Now',
    description: 'You\'ve seen the success stories. It\'s time to write your own.',
    primaryCTA: {
      id: 'join-now',
      text: 'Join Now',
      action: 'register',
      section: 'progressive-high'
    },
    secondaryCTA: {
      id: 'start-trial',
      text: 'Start Free Trial',
      action: 'trial',
      section: 'progressive-high'
    }
  }
})

const urgencyIndicators = computed((): UrgencyIndicator[] => {
  if (props.audience === 'institutional') {
    return [
      { icon: 'ClockIcon', text: 'Limited Q1 Pricing Available' },
      { icon: 'UserGroupIcon', text: 'Implementation Slots Filling Fast' },
      { icon: 'TrendingUpIcon', text: 'Join 500+ Universities' }
    ]
  }
  
  return [
    { icon: 'ClockIcon', text: 'Early Access Ending Soon' },
    { icon: 'UserGroupIcon', text: '50,000+ Alumni Already Joined' },
    { icon: 'TrendingUpIcon', text: 'Average 40% Salary Increase' }
  ]
})

// Methods
const getScrollBasedCTAs = (): ProgressiveCTA[] => {
  const baseCTAs = []
  
  if (props.scrollDepth >= 25) {
    baseCTAs.push({
      id: 'scroll-25',
      label: 'Interested?',
      message: 'See what others are saying',
      text: 'View Testimonials',
      action: 'view-testimonials',
      section: 'scroll-based',
      triggerDepth: 25
    })
  }
  
  if (props.scrollDepth >= 50) {
    const cta = props.audience === 'institutional' 
      ? {
          id: 'scroll-50',
          label: 'Ready to learn more?',
          message: 'Schedule a personalized demo',
          text: 'Get Demo',
          action: 'demo',
          section: 'scroll-based',
          triggerDepth: 50
        }
      : {
          id: 'scroll-50',
          label: 'Ready to get started?',
          message: 'Join thousands of successful alumni',
          text: 'Start Trial',
          action: 'trial',
          section: 'scroll-based',
          triggerDepth: 50
        }
    baseCTAs.push(cta)
  }
  
  if (props.scrollDepth >= 75) {
    baseCTAs.push({
      id: 'scroll-75',
      label: 'Almost there!',
      message: 'Don\'t miss out on this opportunity',
      text: props.audience === 'institutional' ? 'Contact Sales' : 'Join Now',
      action: props.audience === 'institutional' ? 'contact' : 'register',
      section: 'scroll-based',
      triggerDepth: 75
    })
  }
  
  return baseCTAs
}

const handleCTAClick = (cta: any) => {
  const event: CTAClickEvent = {
    action: cta.action,
    section: cta.section,
    audience: props.audience,
    additionalData: {
      engagementLevel: props.engagementLevel,
      scrollDepth: props.scrollDepth,
      ctaId: cta.id,
      ctaType: 'progressive'
    }
  }
  
  // Track the progressive CTA interaction
  trackEvent('progressive_cta_click', {
    ...event,
    progressiveType: cta.section
  })
  
  emit('cta-click', event)
}

const initializeMicroCTAs = () => {
  // Initialize contextual micro-CTAs based on page elements
  if (props.engagementLevel === 'high') {
    showMicroCTAs.value = true
    
    // Example micro-CTAs that could appear near specific content
    activeMicroCTAs.value = [
      {
        id: 'testimonial-cta',
        text: 'Join',
        icon: 'PlusIcon',
        tooltip: 'Join this success story',
        action: 'register',
        section: 'micro-cta',
        position: { top: 0, left: 0 } // Would be calculated based on actual elements
      }
    ]
  }
}

// Watchers
watch(() => props.engagementLevel, (newLevel) => {
  if (newLevel === 'high') {
    showUrgencyIndicators.value = true
    initializeMicroCTAs()
  }
})

watch(() => props.scrollDepth, (depth) => {
  if (depth > 80 && props.engagementLevel === 'high') {
    showUrgencyIndicators.value = true
  }
})

// Lifecycle
onMounted(() => {
  trackEvent('progressive_ctas_initialized', {
    audience: props.audience,
    engagementLevel: props.engagementLevel
  })
})
</script>

<style scoped>
.progressive-ctas {
  position: relative;
}

/* Low engagement styles */
.engagement-low {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin: 24px 0;
}

.soft-cta-button {
  background: transparent;
  color: #6b7280;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.soft-cta-button:hover {
  background: #f9fafb;
  color: #374151;
  border-color: #9ca3af;
}

/* Medium engagement styles */
.engagement-medium {
  margin: 32px 0;
}

.medium-cta {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  margin-bottom: 16px;
}

.cta-title {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 8px;
}

.cta-description {
  color: #64748b;
  margin-bottom: 16px;
  line-height: 1.5;
}

.medium-cta-button {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 12px 24px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.medium-cta-button:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.cta-arrow {
  width: 16px;
  height: 16px;
}

/* High engagement styles */
.engagement-high {
  margin: 40px 0;
}

.high-engagement-banner {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
  padding: 32px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.high-engagement-banner::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  pointer-events: none;
}

.banner-content {
  position: relative;
  z-index: 1;
}

.banner-title {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 12px;
}

.banner-description {
  font-size: 16px;
  opacity: 0.9;
  margin-bottom: 24px;
  line-height: 1.5;
}

.banner-actions {
  display: flex;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.primary-action-button {
  background: white;
  color: #667eea;
  border: none;
  border-radius: 8px;
  padding: 14px 28px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.primary-action-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.secondary-action-button {
  background: transparent;
  color: white;
  border: 2px solid white;
  border-radius: 8px;
  padding: 12px 24px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.secondary-action-button:hover {
  background: white;
  color: #667eea;
}

.urgency-indicators {
  display: flex;
  justify-content: center;
  gap: 32px;
  margin-top: 24px;
  flex-wrap: wrap;
}

.urgency-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  opacity: 0.9;
}

.urgency-icon {
  width: 16px;
  height: 16px;
}

/* Scroll-based CTA styles */
.scroll-based-ctas {
  position: fixed;
  top: 50%;
  right: 20px;
  transform: translateY(-50%);
  z-index: 100;
}

.scroll-cta {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  opacity: 0;
  transform: translateX(100%);
  transition: all 0.3s ease;
  max-width: 280px;
}

.scroll-cta.cta-visible {
  opacity: 1;
  transform: translateX(0);
}

.scroll-cta-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.scroll-cta-text {
  flex: 1;
}

.scroll-cta-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.scroll-cta-message {
  display: block;
  font-size: 14px;
  color: #374151;
  margin-top: 2px;
}

.scroll-cta-button {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.scroll-cta-button:hover {
  background: #2563eb;
}

/* Micro CTA styles */
.micro-ctas {
  position: relative;
}

.micro-cta {
  position: absolute;
  z-index: 50;
}

.micro-cta-button {
  background: #10b981;
  color: white;
  border: none;
  border-radius: 20px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 4px;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.micro-cta-button:hover {
  background: #059669;
  transform: scale(1.05);
}

.micro-cta-icon {
  width: 12px;
  height: 12px;
}

.micro-cta-text {
  font-size: 11px;
}

/* Responsive styles */
@media (max-width: 768px) {
  .scroll-based-ctas {
    position: static;
    transform: none;
    margin: 20px 0;
  }
  
  .scroll-cta {
    position: static;
    transform: none;
    opacity: 1;
    max-width: none;
  }
  
  .banner-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .urgency-indicators {
    flex-direction: column;
    gap: 16px;
  }
  
  .engagement-low {
    flex-direction: column;
    align-items: center;
  }
}
</style>